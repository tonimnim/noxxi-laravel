-- =====================================================
-- TICKETS TABLE
-- =====================================================
-- Individual tickets generated from bookings

-- Drop existing objects if they exist
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'tickets') THEN
        DROP TRIGGER IF EXISTS ensure_ticket_code_unique ON public.tickets;
        DROP TRIGGER IF EXISTS handle_new_ticket_trigger ON public.tickets;
        DROP TRIGGER IF EXISTS ticket_code_trigger ON public.tickets;
        DROP TRIGGER IF EXISTS handle_tickets_updated_at ON public.tickets;
        DROP TRIGGER IF EXISTS create_tickets_on_booking_confirmation ON public.bookings;
    END IF;
END $$;
DROP FUNCTION IF EXISTS public.check_ticket_code_unique() CASCADE;
DROP FUNCTION IF EXISTS public.handle_new_ticket() CASCADE;
DROP FUNCTION IF EXISTS public.generate_ticket_code() CASCADE;
DROP FUNCTION IF EXISTS public.generate_ticket_qr() CASCADE;
DROP FUNCTION IF EXISTS public.scan_ticket() CASCADE;
DROP FUNCTION IF EXISTS public.transfer_ticket() CASCADE;
DROP FUNCTION IF EXISTS public.create_tickets_for_booking() CASCADE;
DROP TABLE IF EXISTS public.tickets CASCADE;

-- =====================================================
-- 1. TICKETS TABLE
-- =====================================================
CREATE TABLE public.tickets (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    booking_id UUID REFERENCES public.bookings(id) ON DELETE CASCADE NOT NULL,
    event_id UUID REFERENCES public.events(id) ON DELETE RESTRICT NOT NULL,
    
    -- Ticket identification
    ticket_code VARCHAR(20) UNIQUE NOT NULL,
    qr_code TEXT, -- Base64 encoded QR image or URL to QR service
    ticket_hash VARCHAR(255) NOT NULL, -- For secure validation
    
    -- Ticket details
    ticket_type VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    seat_number VARCHAR(50), -- For seated events (e.g., Cinema: Row A, Seat 12)
    seat_section VARCHAR(50), -- For venues with sections (e.g., VIP, Regular, Balcony)
    
    -- Holder information (can be different from booking customer)
    holder_name VARCHAR(255) NOT NULL,
    holder_email VARCHAR(255) NOT NULL,
    holder_phone VARCHAR(20),
    assigned_to UUID REFERENCES public.profiles(id), -- If assigned to specific user
    
    -- Status tracking
    status TEXT DEFAULT 'valid' CHECK (status IN (
        'valid',        -- Active ticket
        'used',         -- Already scanned/used
        'cancelled',    -- Cancelled (from booking cancellation)
        'transferred',  -- Transferred to another user
        'expired'       -- Past event date
    )),
    
    -- Scanning/validation tracking
    used_at TIMESTAMPTZ,
    used_by UUID REFERENCES public.profiles(id), -- Who scanned it (organizer/manager)
    entry_gate VARCHAR(50), -- Which gate/entrance was used
    device_fingerprint VARCHAR(255), -- Device that scanned
    
    -- Transfer tracking
    transferred_from UUID REFERENCES public.profiles(id),
    transferred_to UUID REFERENCES public.profiles(id),
    transferred_at TIMESTAMPTZ,
    transfer_reason TEXT,
    
    -- Special requirements
    special_requirements TEXT, -- Dietary, accessibility, etc.
    notes TEXT, -- Internal notes
    
    -- Validity period
    valid_from TIMESTAMPTZ,
    valid_until TIMESTAMPTZ,
    
    -- Offline support
    offline_validation_data JSONB, -- Data for offline ticket validation
    /* Example offline_validation_data:
    {
        "event_title": "Jazz Night",
        "event_date": "2024-01-15T19:00:00",
        "venue_name": "Nairobi Theatre",
        "organizer_name": "Entertainment Ltd",
        "ticket_type": "VIP",
        "seat": "A12",
        "validation_key": "encrypted_hash"
    }
    */
    
    -- Metadata
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_tickets_booking_id ON public.tickets(booking_id);
CREATE INDEX idx_tickets_event_id ON public.tickets(event_id);
CREATE INDEX idx_tickets_ticket_code ON public.tickets(ticket_code);
CREATE INDEX idx_tickets_status ON public.tickets(status);
CREATE INDEX idx_tickets_assigned_to ON public.tickets(assigned_to) WHERE assigned_to IS NOT NULL;
CREATE INDEX idx_tickets_transferred_to ON public.tickets(transferred_to) WHERE transferred_to IS NOT NULL;
CREATE INDEX idx_tickets_used_at ON public.tickets(used_at) WHERE used_at IS NOT NULL;
CREATE INDEX idx_tickets_valid_period ON public.tickets(valid_from, valid_until);

-- Add comments for documentation
COMMENT ON TABLE public.tickets IS 'Individual tickets generated from confirmed bookings';
COMMENT ON COLUMN public.tickets.ticket_code IS 'Unique code like TKT-ABC123XY for scanning';
COMMENT ON COLUMN public.tickets.qr_code IS 'QR code image or URL for mobile scanning';
COMMENT ON COLUMN public.tickets.offline_validation_data IS 'Encrypted data for offline validation in mobile apps';

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.tickets ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Users can view tickets from their bookings or tickets transferred to them
CREATE POLICY "Users see own or transferred tickets" 
    ON public.tickets FOR SELECT 
    USING (
        -- Tickets from user's bookings
        booking_id IN (
            SELECT id FROM public.bookings 
            WHERE user_id = auth.uid()
        )
        OR 
        -- Tickets assigned/transferred to user
        assigned_to = auth.uid()
        OR 
        transferred_to = auth.uid()
    );

-- Users can update tickets they own (for transfers)
CREATE POLICY "Users can transfer own tickets" 
    ON public.tickets FOR UPDATE 
    USING (
        booking_id IN (
            SELECT id FROM public.bookings 
            WHERE user_id = auth.uid()
        )
        AND status = 'valid'
    )
    WITH CHECK (
        booking_id IN (
            SELECT id FROM public.bookings 
            WHERE user_id = auth.uid()
        )
    );

-- Organizers can view and update tickets for their events
CREATE POLICY "Organizers manage event tickets" 
    ON public.tickets FOR ALL 
    USING (
        event_id IN (
            SELECT e.id FROM public.events e
            JOIN public.organizers o ON e.organizer_id = o.id
            WHERE o.user_id = auth.uid()
        )
    );

-- Managers can view and scan tickets for events they manage
CREATE POLICY "Managers can scan tickets" 
    ON public.tickets FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.events e
            JOIN public.organizer_managers om ON om.organizer_id = e.organizer_id
            WHERE e.id = tickets.event_id 
            AND om.user_id = auth.uid()
            AND om.is_active = true
            AND (om.permissions->>'can_scan_tickets')::boolean = true
            AND (om.event_ids IS NULL OR tickets.event_id = ANY(om.event_ids))
        )
    );

-- Admins have full access
CREATE POLICY "Admins have full access to tickets" 
    ON public.tickets FOR ALL 
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

-- Function to generate unique ticket code
CREATE OR REPLACE FUNCTION public.generate_ticket_code()
RETURNS TRIGGER AS $$
DECLARE
    new_code TEXT;
    code_exists BOOLEAN;
    counter INTEGER := 0;
BEGIN
    LOOP
        -- Format: TKT-XXXXXXXX (8 random chars)
        new_code := 'TKT-' || 
                   UPPER(SUBSTRING(
                       md5(random()::text || clock_timestamp()::text || counter::text), 
                       1, 8
                   ));
        
        -- Check if code exists
        SELECT EXISTS(
            SELECT 1 FROM public.tickets 
            WHERE ticket_code = new_code
        ) INTO code_exists;
        
        EXIT WHEN NOT code_exists;
        counter := counter + 1;
        
        -- Prevent infinite loop
        IF counter > 1000 THEN
            RAISE EXCEPTION 'Could not generate unique ticket code';
        END IF;
    END LOOP;
    
    NEW.ticket_code := new_code;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to generate ticket hash for security
CREATE OR REPLACE FUNCTION public.generate_ticket_hash(
    p_ticket_code TEXT,
    p_event_id UUID,
    p_booking_id UUID,
    p_created_at TIMESTAMPTZ
)
RETURNS TEXT AS $$
BEGIN
    -- Create secure hash using multiple fields
    RETURN encode(
        digest(
            p_ticket_code || 
            p_event_id::TEXT || 
            p_booking_id::TEXT || 
            p_created_at::TEXT || 
            COALESCE(current_setting('app.ticket_secret', true), 'noxxi_secret_2024'),
            'sha256'
        ),
        'hex'
    );
END;
$$ LANGUAGE plpgsql;

-- Function to handle new ticket
CREATE OR REPLACE FUNCTION public.handle_new_ticket()
RETURNS TRIGGER AS $$
DECLARE
    v_event RECORD;
BEGIN
    -- Generate ticket code if not provided
    IF NEW.ticket_code IS NULL THEN
        -- Handled by separate trigger
        NULL;
    END IF;
    
    -- Generate ticket hash
    NEW.ticket_hash := public.generate_ticket_hash(
        NEW.ticket_code,
        NEW.event_id,
        NEW.booking_id,
        NEW.created_at
    );
    
    -- Get event details for validity period
    SELECT * INTO v_event 
    FROM public.events 
    WHERE id = NEW.event_id;
    
    -- Set validity period if not provided
    IF NEW.valid_from IS NULL THEN
        -- Valid from 24 hours before event
        NEW.valid_from := v_event.event_date - INTERVAL '24 hours';
    END IF;
    
    IF NEW.valid_until IS NULL THEN
        -- Valid until end of event (or 6 hours after start if no end time)
        NEW.valid_until := COALESCE(
            v_event.end_date, 
            v_event.event_date + INTERVAL '6 hours'
        );
    END IF;
    
    -- Set offline validation data
    IF NEW.offline_validation_data IS NULL THEN
        NEW.offline_validation_data := jsonb_build_object(
            'event_title', v_event.title,
            'event_date', v_event.event_date,
            'venue_name', v_event.venue_name,
            'ticket_type', NEW.ticket_type,
            'seat', NEW.seat_number,
            'validation_key', SUBSTRING(NEW.ticket_hash, 1, 16)
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to create tickets after booking confirmation
CREATE OR REPLACE FUNCTION public.create_tickets_for_booking(
    p_booking_id UUID
)
RETURNS INTEGER AS $$
DECLARE
    v_booking RECORD;
    v_event RECORD;
    v_ticket_type JSONB;
    v_quantity INTEGER;
    v_created_count INTEGER := 0;
BEGIN
    -- Get booking details
    SELECT * INTO v_booking 
    FROM public.bookings 
    WHERE id = p_booking_id 
    AND status = 'confirmed';
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Booking not found or not confirmed';
    END IF;
    
    -- Get event details
    SELECT * INTO v_event 
    FROM public.events 
    WHERE id = v_booking.event_id;
    
    -- Create tickets based on ticket types in booking
    FOR v_ticket_type IN SELECT * FROM jsonb_array_elements(v_booking.ticket_types)
    LOOP
        v_quantity := (v_ticket_type->>'quantity')::INTEGER;
        
        FOR i IN 1..v_quantity LOOP
            INSERT INTO public.tickets (
                booking_id,
                event_id,
                ticket_type,
                price,
                currency,
                holder_name,
                holder_email,
                holder_phone
            ) VALUES (
                p_booking_id,
                v_booking.event_id,
                v_ticket_type->>'name',
                (v_ticket_type->>'price')::DECIMAL,
                v_booking.currency,
                v_booking.customer_name,
                v_booking.customer_email,
                v_booking.customer_phone
            );
            
            v_created_count := v_created_count + 1;
        END LOOP;
    END LOOP;
    
    -- Update event tickets sold count
    UPDATE public.events 
    SET tickets_sold = tickets_sold + v_created_count
    WHERE id = v_booking.event_id;
    
    -- Update organizer stats
    PERFORM public.increment_organizer_stats(
        v_event.organizer_id,
        v_created_count,
        v_booking.total_amount - v_booking.service_fee,
        v_booking.currency
    );
    
    RETURN v_created_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to scan/validate ticket
CREATE OR REPLACE FUNCTION public.scan_ticket(
    p_ticket_code TEXT,
    p_scanner_id UUID,
    p_device_fingerprint TEXT DEFAULT NULL,
    p_entry_gate TEXT DEFAULT NULL
)
RETURNS JSONB AS $$
DECLARE
    v_ticket RECORD;
    v_event RECORD;
    v_can_scan BOOLEAN;
BEGIN
    -- Get ticket and event details
    SELECT t.*, e.title as event_title, e.event_date, e.venue_name
    INTO v_ticket
    FROM public.tickets t
    JOIN public.events e ON e.id = t.event_id
    WHERE t.ticket_code = p_ticket_code;
    
    IF NOT FOUND THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Invalid ticket code',
            'code', 'INVALID_CODE'
        );
    END IF;
    
    -- Check if scanner is authorized
    SELECT public.is_manager_for_organizer(p_scanner_id, 
        (SELECT organizer_id FROM public.events WHERE id = v_ticket.event_id),
        v_ticket.event_id
    ) OR EXISTS (
        SELECT 1 FROM public.events e
        JOIN public.organizers o ON o.id = e.organizer_id
        WHERE e.id = v_ticket.event_id AND o.user_id = p_scanner_id
    ) INTO v_can_scan;
    
    IF NOT v_can_scan THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Unauthorized scanner',
            'code', 'UNAUTHORIZED'
        );
    END IF;
    
    -- Check ticket status
    IF v_ticket.status = 'used' THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Ticket already used',
            'code', 'ALREADY_USED',
            'used_at', v_ticket.used_at,
            'entry_gate', v_ticket.entry_gate
        );
    END IF;
    
    IF v_ticket.status = 'cancelled' THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Ticket has been cancelled',
            'code', 'CANCELLED'
        );
    END IF;
    
    -- Check validity period
    IF NOW() < v_ticket.valid_from THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Ticket not yet valid',
            'code', 'NOT_YET_VALID',
            'valid_from', v_ticket.valid_from
        );
    END IF;
    
    IF NOW() > v_ticket.valid_until THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Ticket has expired',
            'code', 'EXPIRED',
            'valid_until', v_ticket.valid_until
        );
    END IF;
    
    -- SUCCESS - Mark ticket as used
    UPDATE public.tickets
    SET 
        status = 'used',
        used_at = NOW(),
        used_by = p_scanner_id,
        device_fingerprint = p_device_fingerprint,
        entry_gate = p_entry_gate,
        updated_at = NOW()
    WHERE id = v_ticket.id;
    
    RETURN jsonb_build_object(
        'success', true,
        'message', 'Ticket validated successfully',
        'code', 'VALID',
        'ticket_type', v_ticket.ticket_type,
        'holder_name', v_ticket.holder_name,
        'seat_number', v_ticket.seat_number,
        'seat_section', v_ticket.seat_section,
        'event_title', v_ticket.event_title,
        'event_date', v_ticket.event_date,
        'venue_name', v_ticket.venue_name
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to transfer ticket to another user
CREATE OR REPLACE FUNCTION public.transfer_ticket(
    p_ticket_id UUID,
    p_from_user_id UUID,
    p_to_email VARCHAR,
    p_reason TEXT DEFAULT NULL
)
RETURNS JSONB AS $$
DECLARE
    v_ticket RECORD;
    v_to_user_id UUID;
    v_to_user RECORD;
BEGIN
    -- Verify ticket ownership and status
    SELECT * INTO v_ticket
    FROM public.tickets t
    JOIN public.bookings b ON b.id = t.booking_id
    WHERE t.id = p_ticket_id 
    AND b.user_id = p_from_user_id
    AND t.status = 'valid';
    
    IF NOT FOUND THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Ticket not found or cannot be transferred'
        );
    END IF;
    
    -- Find recipient user
    SELECT id, full_name, email INTO v_to_user
    FROM public.profiles
    WHERE email = p_to_email;
    
    IF NOT FOUND THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Recipient user not found'
        );
    END IF;
    
    -- Transfer the ticket
    UPDATE public.tickets
    SET 
        transferred_from = p_from_user_id,
        transferred_to = v_to_user.id,
        assigned_to = v_to_user.id,
        transferred_at = NOW(),
        transfer_reason = p_reason,
        holder_name = v_to_user.full_name,
        holder_email = v_to_user.email,
        status = 'transferred',
        updated_at = NOW()
    WHERE id = p_ticket_id;
    
    RETURN jsonb_build_object(
        'success', true,
        'message', 'Ticket transferred successfully',
        'transferred_to', v_to_user.full_name,
        'transferred_to_email', v_to_user.email
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 5. TRIGGERS
-- =====================================================

-- Trigger to generate ticket code
CREATE TRIGGER ticket_code_trigger
    BEFORE INSERT ON public.tickets
    FOR EACH ROW
    WHEN (NEW.ticket_code IS NULL)
    EXECUTE FUNCTION public.generate_ticket_code();

-- Trigger to handle new ticket
CREATE TRIGGER handle_new_ticket_trigger
    BEFORE INSERT ON public.tickets
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_new_ticket();

-- Trigger to update updated_at
CREATE TRIGGER handle_tickets_updated_at
    BEFORE UPDATE ON public.tickets
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- Trigger to create tickets when booking is confirmed
CREATE OR REPLACE FUNCTION public.auto_create_tickets()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
        PERFORM public.create_tickets_for_booking(NEW.id);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER create_tickets_on_booking_confirmation
    AFTER UPDATE ON public.bookings
    FOR EACH ROW
    WHEN (NEW.status = 'confirmed' AND OLD.status != 'confirmed')
    EXECUTE FUNCTION public.auto_create_tickets();

-- =====================================================
-- 6. ENABLE REALTIME
-- =====================================================
ALTER PUBLICATION supabase_realtime ADD TABLE public.tickets;