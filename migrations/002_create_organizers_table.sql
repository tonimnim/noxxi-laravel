-- =====================================================
-- ORGANIZERS TABLE - Global-ready ticketing platform
-- =====================================================
-- For users who sell tickets/bookings on the platform

-- Drop existing objects if they exist
DO $$ 
BEGIN
    -- Drop trigger if table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'organizers') THEN
        DROP TRIGGER IF EXISTS on_organizer_status_change ON public.organizers;
        DROP TRIGGER IF EXISTS on_profile_organizer_role ON public.profiles;
        DROP TRIGGER IF EXISTS handle_organizers_updated_at ON public.organizers;
    END IF;
END $$;

-- Drop functions
DROP FUNCTION IF EXISTS public.handle_organizer_approval() CASCADE;
DROP FUNCTION IF EXISTS public.increment_organizer_stats() CASCADE;
DROP FUNCTION IF EXISTS public.generate_api_key() CASCADE;
DROP FUNCTION IF EXISTS public.handle_organizer_role_change() CASCADE;
DROP FUNCTION IF EXISTS public.can_manage_organizer_events() CASCADE;

-- Drop table
DROP TABLE IF EXISTS public.organizers CASCADE;

-- =====================================================
-- 1. ORGANIZERS TABLE
-- =====================================================
CREATE TABLE public.organizers (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE UNIQUE NOT NULL,
    
    -- Business Information (Required for registration)
    business_name VARCHAR(255) NOT NULL,
    business_email VARCHAR(255) NOT NULL,
    business_description TEXT,
    business_logo_url TEXT,
    
    -- Location & Localization
    business_country VARCHAR(3) DEFAULT 'KE',
    business_address TEXT,
    business_timezone TEXT DEFAULT 'Africa/Nairobi',
    
    -- Payment Configuration (Flexible for global use)
    payment_methods JSONB DEFAULT '[]'::jsonb,
    /* Example payment_methods structure:
    [
        {
            "provider": "mpesa",
            "enabled": true,
            "config": {
                "till_number": "123456",
                "paybill": "123456"
            },
            "currencies": ["KES"],
            "is_primary": true
        },
        {
            "provider": "stripe",
            "enabled": true,
            "config": {
                "account_id": "acct_xxx",
                "publishable_key": "pk_xxx"
            },
            "currencies": ["USD", "EUR", "GBP"],
            "is_primary": false
        },
        {
            "provider": "bank",
            "enabled": true,
            "config": {
                "account_name": "Business Ltd",
                "account_number": "xxx",
                "bank_name": "Standard Bank",
                "swift_code": "xxx",
                "iban": "xxx"
            },
            "currencies": ["ZAR"],
            "is_primary": false
        },
        {
            "provider": "paypal",
            "enabled": true,
            "config": {
                "merchant_id": "xxx",
                "client_id": "xxx"
            },
            "currencies": ["USD", "EUR"],
            "is_primary": false
        },
        {
            "provider": "crypto",
            "enabled": true,
            "config": {
                "wallet_addresses": {
                    "BTC": "xxx",
                    "ETH": "xxx",
                    "USDT": "xxx"
                }
            },
            "currencies": ["BTC", "ETH", "USDT"],
            "is_primary": false
        }
    ]
    */
    default_currency VARCHAR(3) DEFAULT 'KES',
    
    -- Statistics (Updated via triggers)
    total_events INTEGER DEFAULT 0,
    total_tickets_sold INTEGER DEFAULT 0,
    total_revenue JSONB DEFAULT '{}'::jsonb, -- Revenue per currency e.g. {"KES": 50000, "USD": 1000}
    rating DECIMAL(3,2),
    total_reviews INTEGER DEFAULT 0,
    
    -- API & Webhooks
    api_key VARCHAR(255) UNIQUE,
    webhook_url TEXT,
    webhook_secret VARCHAR(255),
    webhook_events JSONB DEFAULT '["ticket.purchased", "ticket.validated", "event.created", "event.cancelled"]'::jsonb,
    
    -- Settings
    commission_rate DECIMAL(5,2) DEFAULT 10.00, -- Platform commission percentage
    settlement_period_days INTEGER DEFAULT 7, -- Days before funds settlement
    auto_approve_events BOOLEAN DEFAULT false, -- Auto-approve new events if verified
    
    -- Status flags
    is_active BOOLEAN DEFAULT true,
    is_featured BOOLEAN DEFAULT false,
    
    -- Metadata
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    approved_at TIMESTAMPTZ,
    approved_by UUID REFERENCES public.profiles(id)
);

-- Create indexes for performance
CREATE INDEX idx_organizers_user_id ON public.organizers(user_id);
CREATE INDEX idx_organizers_api_key ON public.organizers(api_key) WHERE api_key IS NOT NULL;
CREATE INDEX idx_organizers_business_country ON public.organizers(business_country);
CREATE INDEX idx_organizers_is_active ON public.organizers(is_active);
CREATE INDEX idx_organizers_created_at ON public.organizers(created_at DESC);

-- Add comments for documentation
COMMENT ON TABLE public.organizers IS 'Event organizers who can create events and sell tickets';
COMMENT ON COLUMN public.organizers.payment_methods IS 'JSON array of payment provider configurations';
COMMENT ON COLUMN public.organizers.total_revenue IS 'Revenue tracked per currency as JSON object';

-- Note: Foreign key for organizer_managers will be added in 002a file

-- =====================================================
-- 3. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.organizers ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 4. RLS POLICIES FOR ORGANIZERS
-- =====================================================

-- Public can view active organizers (for event listings)
CREATE POLICY "Public can view active organizers" 
    ON public.organizers FOR SELECT 
    USING (is_active = true);

-- Users can view their own organizer data
CREATE POLICY "Users can view own organizer data" 
    ON public.organizers FOR SELECT 
    USING (user_id = auth.uid());

-- Users can update their own organizer data (except verification fields)
CREATE POLICY "Users can update own organizer data" 
    ON public.organizers FOR UPDATE 
    USING (user_id = auth.uid())
    WITH CHECK (user_id = auth.uid());

-- Users with organizer role can insert their organizer profile
CREATE POLICY "Organizer role users can create organizer profile" 
    ON public.organizers FOR INSERT 
    WITH CHECK (
        user_id = auth.uid() 
        AND EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'organizer'
        )
    );

-- Admins have full access
CREATE POLICY "Admins have full access to organizers" 
    ON public.organizers FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'admin'
        )
    );

-- Note: Manager policy will be added after organizer_managers table is created

-- =====================================================
-- 5. FUNCTIONS
-- =====================================================

-- Function to generate unique API key
CREATE OR REPLACE FUNCTION public.generate_api_key()
RETURNS TEXT AS $$
DECLARE
    new_key TEXT;
    key_exists BOOLEAN;
BEGIN
    LOOP
        -- Generate a random API key with prefix (pk for public, sk for secret)
        new_key := 'noxxi_pk_live_' || encode(gen_random_bytes(32), 'hex');
        
        -- Check if key already exists
        SELECT EXISTS(SELECT 1 FROM public.organizers WHERE api_key = new_key) INTO key_exists;
        
        -- Exit loop if unique key found
        EXIT WHEN NOT key_exists;
    END LOOP;
    
    RETURN new_key;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to auto-create organizer when user role changes to organizer
CREATE OR REPLACE FUNCTION public.handle_organizer_role_change()
RETURNS TRIGGER AS $$
BEGIN
    -- When a user's role changes to organizer, create organizer profile
    IF NEW.role = 'organizer' AND (OLD.role IS NULL OR OLD.role != 'organizer') THEN
        -- Check if organizer profile already exists
        IF NOT EXISTS (SELECT 1 FROM public.organizers WHERE user_id = NEW.id) THEN
            INSERT INTO public.organizers (
                user_id,
                business_name,
                business_email,
                business_country
            ) VALUES (
                NEW.id,
                COALESCE(NEW.full_name, 'My Business'),
                NEW.email,
                NEW.country_code
            );
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to handle organizer approval
CREATE OR REPLACE FUNCTION public.handle_organizer_approval()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.is_active = true AND OLD.is_active != true THEN
        -- Set approval timestamp
        NEW.approved_at = NOW();
        
        -- Generate API key if not exists
        IF NEW.api_key IS NULL THEN
            NEW.api_key = public.generate_api_key();
        END IF;
        
        -- Generate webhook secret if not exists
        IF NEW.webhook_secret IS NULL THEN
            NEW.webhook_secret = encode(gen_random_bytes(32), 'hex');
        END IF;
    END IF;
    
    -- Update the updated_at timestamp
    NEW.updated_at = NOW();
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to update organizer stats (called from other triggers)
CREATE OR REPLACE FUNCTION public.increment_organizer_stats(
    p_organizer_id UUID,
    p_tickets_sold INTEGER DEFAULT 0,
    p_revenue DECIMAL DEFAULT 0,
    p_currency VARCHAR DEFAULT 'KES'
)
RETURNS VOID AS $$
BEGIN
    UPDATE public.organizers
    SET 
        total_tickets_sold = total_tickets_sold + p_tickets_sold,
        total_revenue = jsonb_set(
            total_revenue,
            ARRAY[p_currency],
            to_jsonb(COALESCE((total_revenue->p_currency)::numeric, 0) + p_revenue)
        ),
        updated_at = NOW()
    WHERE id = p_organizer_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Note: can_manage_organizer_events function will be added after organizer_managers table is created

-- =====================================================
-- 6. TRIGGERS
-- =====================================================

-- Trigger when profile role changes to organizer
CREATE TRIGGER on_profile_organizer_role
    AFTER UPDATE ON public.profiles
    FOR EACH ROW
    WHEN (NEW.role = 'organizer' AND (OLD.role IS NULL OR OLD.role != 'organizer'))
    EXECUTE FUNCTION public.handle_organizer_role_change();

-- Trigger for organizer approval
CREATE TRIGGER on_organizer_status_change
    BEFORE UPDATE ON public.organizers
    FOR EACH ROW
    WHEN (OLD.is_active IS DISTINCT FROM NEW.is_active)
    EXECUTE FUNCTION public.handle_organizer_approval();

-- Trigger to update updated_at
CREATE TRIGGER handle_organizers_updated_at
    BEFORE UPDATE ON public.organizers
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- =====================================================
-- 7. ENABLE REALTIME
-- =====================================================
ALTER PUBLICATION supabase_realtime ADD TABLE public.organizers;