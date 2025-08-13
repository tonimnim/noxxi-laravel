-- =====================================================
-- BOOKINGS TABLE (formerly orders)
-- =====================================================
-- Professional booking system for events, cinema, sports, experiences

-- Drop existing objects if they exist
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'bookings') THEN
        DROP TRIGGER IF EXISTS handle_bookings_updated_at ON public.bookings;
        DROP TRIGGER IF EXISTS handle_new_booking_trigger ON public.bookings;
        DROP TRIGGER IF EXISTS booking_reference_trigger ON public.bookings;
    END IF;
END $$;
DROP FUNCTION IF EXISTS public.handle_new_booking() CASCADE;
DROP FUNCTION IF EXISTS public.generate_booking_reference() CASCADE;
DROP FUNCTION IF EXISTS public.expire_pending_bookings() CASCADE;
DROP FUNCTION IF EXISTS public.calculate_booking_fees() CASCADE;
DROP TABLE IF EXISTS public.bookings CASCADE;

-- =====================================================
-- 1. BOOKINGS TABLE
-- =====================================================
CREATE TABLE public.bookings (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    event_id UUID REFERENCES public.events(id) ON DELETE RESTRICT NOT NULL,
    
    -- Booking details
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    subtotal DECIMAL(10,2) NOT NULL,
    service_fee DECIMAL(10,2) DEFAULT 0,
    payment_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    
    -- Contact info (stored at booking time for record keeping)
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    
    -- Status tracking
    status TEXT DEFAULT 'pending' CHECK (status IN (
        'pending',
        'confirmed',
        'cancelled',
        'expired',
        'refunded'
    )),
    
    -- Payment information
    payment_status TEXT DEFAULT 'unpaid' CHECK (payment_status IN (
        'unpaid',       -- No payment attempt
        'processing',   -- Payment in progress
        'paid',         -- Payment successful
        'failed',       -- Payment failed
        'refunded',     -- Payment refunded
        'partial_refund' -- Partially refunded
    )),
    payment_method TEXT CHECK (payment_method IN (
        'mpesa',
        'card',
        'bank_transfer',
        'paypal',
        'stripe',
        'cash',
        'crypto'
    )),
    payment_reference VARCHAR(100),
    payment_provider_data JSONB DEFAULT '{}'::jsonb, -- Store provider-specific data
    
    -- Promo codes and discounts
    promo_code VARCHAR(50),
    promo_details JSONB DEFAULT '{}'::jsonb,
    
    -- Additional booking info
    booking_metadata JSONB DEFAULT '{}'::jsonb, -- Flexible field for extra data
    ticket_types JSONB NOT NULL, -- Store selected ticket types and quantities
    /* Example ticket_types structure:
    [
        {
            "name": "VIP",
            "price": 5000,
            "quantity": 2,
            "description": "VIP seating with complimentary drinks"
        },
        {
            "name": "Regular",
            "price": 2000,
            "quantity": 3,
            "description": "Standard entry"
        }
    ]
    */
    
    -- Session tracking
    ip_address INET,
    user_agent TEXT,
    booking_source TEXT DEFAULT 'web', -- web, mobile_app, api, admin
    
    -- Timestamps
    expires_at TIMESTAMPTZ DEFAULT (NOW() + INTERVAL '15 minutes'),
    confirmed_at TIMESTAMPTZ,
    cancelled_at TIMESTAMPTZ,
    refunded_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_bookings_reference ON public.bookings(booking_reference);
CREATE INDEX idx_bookings_user_id ON public.bookings(user_id);
CREATE INDEX idx_bookings_event_id ON public.bookings(event_id);
CREATE INDEX idx_bookings_status ON public.bookings(status);
CREATE INDEX idx_bookings_payment_status ON public.bookings(payment_status);
CREATE INDEX idx_bookings_created_at ON public.bookings(created_at DESC);
CREATE INDEX idx_bookings_expires_at ON public.bookings(expires_at) WHERE status = 'pending';
CREATE INDEX idx_bookings_payment_reference ON public.bookings(payment_reference) WHERE payment_reference IS NOT NULL;

-- Add comments for documentation
COMMENT ON TABLE public.bookings IS 'Booking records for all event types - replaces orders table';
COMMENT ON COLUMN public.bookings.booking_reference IS 'Human-friendly reference like BK-20240105-A7B3';
COMMENT ON COLUMN public.bookings.ticket_types IS 'JSON array of ticket types with quantities and prices';
COMMENT ON COLUMN public.bookings.payment_provider_data IS 'Provider-specific data like M-Pesa receipt, Stripe charge ID';

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.bookings ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Users can view their own bookings
CREATE POLICY "Users can view own bookings" 
    ON public.bookings FOR SELECT 
    USING (user_id = auth.uid());

-- Users can create bookings for themselves
CREATE POLICY "Users can create bookings" 
    ON public.bookings FOR INSERT 
    WITH CHECK (user_id = auth.uid());

-- Users can update their pending bookings (for cancellation)
CREATE POLICY "Users can update own pending bookings" 
    ON public.bookings FOR UPDATE 
    USING (
        user_id = auth.uid() 
        AND status = 'pending'
    )
    WITH CHECK (
        user_id = auth.uid()
        AND status IN ('pending', 'cancelled')
    );

-- Organizers can view bookings for their events
CREATE POLICY "Organizers can view event bookings" 
    ON public.bookings FOR SELECT 
    USING (
        EXISTS (
            SELECT 1 FROM public.events e
            JOIN public.organizers o ON e.organizer_id = o.id
            WHERE e.id = bookings.event_id 
            AND o.user_id = auth.uid()
        )
    );

-- Managers can view bookings for events they manage
CREATE POLICY "Managers can view managed event bookings" 
    ON public.bookings FOR SELECT 
    USING (
        EXISTS (
            SELECT 1 FROM public.events e
            JOIN public.organizer_managers om ON om.organizer_id = e.organizer_id
            WHERE e.id = bookings.event_id 
            AND om.user_id = auth.uid()
            AND om.is_active = true
            AND (om.event_ids IS NULL OR bookings.event_id = ANY(om.event_ids))
        )
    );

-- Admins have full access
CREATE POLICY "Admins have full access to bookings" 
    ON public.bookings FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'admin'
        )
    );

-- =====================================================
-- 4. FUNCTIONS
-- =====================================================

-- Function to generate booking reference
CREATE OR REPLACE FUNCTION public.generate_booking_reference()
RETURNS TRIGGER AS $$
DECLARE
    new_reference TEXT;
    reference_exists BOOLEAN;
    counter INTEGER := 0;
BEGIN
    LOOP
        -- Format: BK-YYYYMMDD-XXXX (e.g., BK-20240105-A7B3)
        new_reference := 'BK-' || 
                        TO_CHAR(NOW(), 'YYYYMMDD') || '-' || 
                        UPPER(SUBSTRING(md5(random()::text || counter::text), 1, 4));
        
        -- Check if reference exists
        SELECT EXISTS(
            SELECT 1 FROM public.bookings 
            WHERE booking_reference = new_reference
        ) INTO reference_exists;
        
        EXIT WHEN NOT reference_exists;
        counter := counter + 1;
        
        -- Prevent infinite loop
        IF counter > 1000 THEN
            RAISE EXCEPTION 'Could not generate unique booking reference';
        END IF;
    END LOOP;
    
    NEW.booking_reference := new_reference;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to handle new booking
CREATE OR REPLACE FUNCTION public.handle_new_booking()
RETURNS TRIGGER AS $$
BEGIN
    -- Generate booking reference if not provided
    IF NEW.booking_reference IS NULL THEN
        -- This will be handled by separate trigger
        NULL;
    END IF;
    
    -- Calculate total amount
    NEW.total_amount := NEW.subtotal + 
                       COALESCE(NEW.service_fee, 0) + 
                       COALESCE(NEW.payment_fee, 0) - 
                       COALESCE(NEW.discount_amount, 0);
    
    -- Ensure total is not negative
    IF NEW.total_amount < 0 THEN
        NEW.total_amount := 0;
    END IF;
    
    -- Set customer info from profile if not provided
    IF NEW.customer_name IS NULL OR NEW.customer_name = '' THEN
        SELECT full_name INTO NEW.customer_name 
        FROM public.profiles 
        WHERE id = NEW.user_id;
    END IF;
    
    IF NEW.customer_email IS NULL OR NEW.customer_email = '' THEN
        SELECT email INTO NEW.customer_email 
        FROM public.profiles 
        WHERE id = NEW.user_id;
    END IF;
    
    IF NEW.customer_phone IS NULL OR NEW.customer_phone = '' THEN
        SELECT phone_number INTO NEW.customer_phone 
        FROM public.profiles 
        WHERE id = NEW.user_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to expire pending bookings
CREATE OR REPLACE FUNCTION public.expire_pending_bookings()
RETURNS INTEGER AS $$
DECLARE
    expired_count INTEGER;
BEGIN
    WITH expired AS (
        UPDATE public.bookings
        SET 
            status = 'expired',
            updated_at = NOW()
        WHERE 
            status = 'pending' 
            AND expires_at < NOW()
        RETURNING 1
    )
    SELECT COUNT(*) INTO expired_count FROM expired;
    
    RETURN expired_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to calculate booking fees
CREATE OR REPLACE FUNCTION public.calculate_booking_fees(
    p_subtotal DECIMAL,
    p_payment_method TEXT,
    p_currency VARCHAR DEFAULT 'KES'
) 
RETURNS TABLE (
    service_fee DECIMAL,
    payment_fee DECIMAL,
    total_fees DECIMAL
) AS $$
BEGIN
    -- Calculate service fee (5% of subtotal - platform fee)
    service_fee := ROUND(p_subtotal * 0.05, 2);
    
    -- Calculate payment method fees
    CASE p_payment_method
        WHEN 'mpesa' THEN 
            -- M-Pesa: 1.5% with minimum of 10 KES
            payment_fee := GREATEST(ROUND(p_subtotal * 0.015, 2), 10);
        WHEN 'card' THEN 
            -- Card: 2.9% + 20 KES
            payment_fee := ROUND(p_subtotal * 0.029, 2) + 20;
        WHEN 'stripe' THEN 
            -- Stripe: 2.9% + 30 cents (converted to local currency)
            payment_fee := ROUND(p_subtotal * 0.029, 2) + 
                          CASE p_currency
                              WHEN 'KES' THEN 40
                              WHEN 'USD' THEN 0.30
                              ELSE 30
                          END;
        WHEN 'paypal' THEN 
            -- PayPal: 3.4% + fixed fee
            payment_fee := ROUND(p_subtotal * 0.034, 2) + 
                          CASE p_currency
                              WHEN 'KES' THEN 50
                              WHEN 'USD' THEN 0.49
                              ELSE 40
                          END;
        WHEN 'crypto' THEN 
            -- Crypto: 1% network fee
            payment_fee := ROUND(p_subtotal * 0.01, 2);
        WHEN 'bank_transfer' THEN 
            -- Bank transfer: Fixed fee
            payment_fee := CASE p_currency
                              WHEN 'KES' THEN 150
                              WHEN 'USD' THEN 15
                              ELSE 100
                          END;
        ELSE 
            payment_fee := 0;
    END CASE;
    
    total_fees := service_fee + payment_fee;
    
    RETURN QUERY SELECT service_fee, payment_fee, total_fees;
END;
$$ LANGUAGE plpgsql;

-- Function to confirm booking after payment
CREATE OR REPLACE FUNCTION public.confirm_booking(
    p_booking_id UUID,
    p_payment_reference VARCHAR,
    p_payment_data JSONB DEFAULT '{}'::jsonb
)
RETURNS BOOLEAN AS $$
DECLARE
    v_booking RECORD;
BEGIN
    -- Get booking details
    SELECT * INTO v_booking 
    FROM public.bookings 
    WHERE id = p_booking_id 
    AND status = 'pending';
    
    IF NOT FOUND THEN
        RETURN FALSE;
    END IF;
    
    -- Update booking status
    UPDATE public.bookings
    SET 
        status = 'confirmed',
        payment_status = 'paid',
        payment_reference = p_payment_reference,
        payment_provider_data = payment_provider_data || p_payment_data,
        confirmed_at = NOW(),
        updated_at = NOW()
    WHERE id = p_booking_id;
    
    -- Create tickets will be handled by tickets table trigger
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 5. TRIGGERS
-- =====================================================

-- Trigger to generate booking reference
CREATE TRIGGER booking_reference_trigger
    BEFORE INSERT ON public.bookings
    FOR EACH ROW
    WHEN (NEW.booking_reference IS NULL)
    EXECUTE FUNCTION public.generate_booking_reference();

-- Trigger to handle new booking
CREATE TRIGGER handle_new_booking_trigger
    BEFORE INSERT ON public.bookings
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_new_booking();

-- Trigger to update updated_at
CREATE TRIGGER handle_bookings_updated_at
    BEFORE UPDATE ON public.bookings
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- =====================================================
-- 6. SCHEDULED JOBS (Create via Supabase Dashboard)
-- =====================================================
-- Run expire_pending_bookings() every 5 minutes via pg_cron:
-- SELECT cron.schedule('expire-bookings', '*/5 * * * *', 'SELECT public.expire_pending_bookings();');

-- =====================================================
-- 7. ENABLE REALTIME
-- =====================================================
ALTER PUBLICATION supabase_realtime ADD TABLE public.bookings;