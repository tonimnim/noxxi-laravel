-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
-- Simple in-app notification system for users

-- Drop existing objects if they exist
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'notifications') THEN
        DROP TRIGGER IF EXISTS handle_notifications_updated_at ON public.notifications;
        DROP TRIGGER IF EXISTS notify_on_booking_confirmation ON public.bookings;
    END IF;
END $$;

DROP TABLE IF EXISTS public.notifications CASCADE;

-- =====================================================
-- 1. NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE public.notifications (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    
    -- Notification details
    type TEXT CHECK (type IN (
        -- Booking notifications
        'booking_confirmed',
        'booking_cancelled',
        'booking_reminder',
        'ticket_transferred',
        
        -- Event notifications
        'event_reminder',
        'event_cancelled',
        'event_updated',
        'event_starting_soon',
        
        -- Payment notifications
        'payment_successful',
        'payment_failed',
        'refund_processed',
        
        -- System notifications
        'system_announcement',
        'promotion',
        'account_update'
    )) NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Related entities (optional)
    related_event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    related_booking_id UUID REFERENCES public.bookings(id) ON DELETE CASCADE,
    related_ticket_id UUID REFERENCES public.tickets(id) ON DELETE CASCADE,
    
    -- Metadata for additional data
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Status
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMPTZ,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_notifications_user_id ON public.notifications(user_id);
CREATE INDEX idx_notifications_type ON public.notifications(type);
CREATE INDEX idx_notifications_is_read ON public.notifications(is_read);
CREATE INDEX idx_notifications_created_at ON public.notifications(created_at DESC);
CREATE INDEX idx_notifications_related_event ON public.notifications(related_event_id) WHERE related_event_id IS NOT NULL;
CREATE INDEX idx_notifications_related_booking ON public.notifications(related_booking_id) WHERE related_booking_id IS NOT NULL;

-- Add comments for documentation
COMMENT ON TABLE public.notifications IS 'Simple in-app notification system';
COMMENT ON COLUMN public.notifications.type IS 'Notification type for filtering and display';
COMMENT ON COLUMN public.notifications.metadata IS 'Additional data like URLs, action buttons, etc';

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Users can only view their own notifications
CREATE POLICY "Users view own notifications" 
    ON public.notifications FOR SELECT 
    USING (user_id = auth.uid());

-- Users can update their own notifications (mark as read)
CREATE POLICY "Users update own notifications" 
    ON public.notifications FOR UPDATE 
    USING (user_id = auth.uid())
    WITH CHECK (user_id = auth.uid());

-- System can insert notifications (via service role)
CREATE POLICY "System inserts notifications" 
    ON public.notifications FOR INSERT 
    WITH CHECK (true);

-- Users can delete their own notifications
CREATE POLICY "Users delete own notifications" 
    ON public.notifications FOR DELETE 
    USING (user_id = auth.uid());

-- =====================================================
-- 4. FUNCTIONS
-- =====================================================

-- Function to mark notification as read
CREATE OR REPLACE FUNCTION public.mark_notification_read(
    p_notification_id UUID
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE public.notifications
    SET 
        is_read = true,
        read_at = NOW()
    WHERE 
        id = p_notification_id 
        AND user_id = auth.uid()
        AND is_read = false;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to mark all notifications as read for a user
CREATE OR REPLACE FUNCTION public.mark_all_notifications_read()
RETURNS INTEGER AS $$
DECLARE
    updated_count INTEGER;
BEGIN
    UPDATE public.notifications
    SET 
        is_read = true,
        read_at = NOW()
    WHERE 
        user_id = auth.uid()
        AND is_read = false;
    
    GET DIAGNOSTICS updated_count = ROW_COUNT;
    RETURN updated_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to create notification
CREATE OR REPLACE FUNCTION public.create_notification(
    p_user_id UUID,
    p_type TEXT,
    p_title VARCHAR,
    p_message TEXT,
    p_related_event_id UUID DEFAULT NULL,
    p_related_booking_id UUID DEFAULT NULL,
    p_related_ticket_id UUID DEFAULT NULL,
    p_metadata JSONB DEFAULT '{}'::jsonb
)
RETURNS UUID AS $$
DECLARE
    v_notification_id UUID;
BEGIN
    INSERT INTO public.notifications (
        user_id,
        type,
        title,
        message,
        related_event_id,
        related_booking_id,
        related_ticket_id,
        metadata
    ) VALUES (
        p_user_id,
        p_type,
        p_title,
        p_message,
        p_related_event_id,
        p_related_booking_id,
        p_related_ticket_id,
        p_metadata
    ) RETURNING id INTO v_notification_id;
    
    RETURN v_notification_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to send booking confirmation notification
CREATE OR REPLACE FUNCTION public.notify_booking_confirmation()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
        PERFORM public.create_notification(
            NEW.user_id,
            'booking_confirmed',
            'Booking Confirmed!',
            'Your booking ' || NEW.booking_reference || ' has been confirmed. Check your tickets.',
            (SELECT event_id FROM public.bookings WHERE id = NEW.id),
            NEW.id,
            NULL,
            jsonb_build_object(
                'booking_reference', NEW.booking_reference,
                'amount', NEW.total_amount,
                'currency', NEW.currency
            )
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to send event reminder notifications
CREATE OR REPLACE FUNCTION public.send_event_reminders()
RETURNS INTEGER AS $$
DECLARE
    v_count INTEGER := 0;
    v_event RECORD;
    v_booking RECORD;
BEGIN
    -- Find events happening in the next 24 hours
    FOR v_event IN 
        SELECT * FROM public.events 
        WHERE event_date BETWEEN NOW() AND NOW() + INTERVAL '24 hours'
        AND status = 'published'
    LOOP
        -- Find all confirmed bookings for this event
        FOR v_booking IN 
            SELECT DISTINCT b.user_id, b.id as booking_id
            FROM public.bookings b
            WHERE b.event_id = v_event.id
            AND b.status = 'confirmed'
            AND NOT EXISTS (
                -- Don't send if already sent in last 20 hours
                SELECT 1 FROM public.notifications n
                WHERE n.user_id = b.user_id
                AND n.related_event_id = v_event.id
                AND n.type = 'event_reminder'
                AND n.created_at > NOW() - INTERVAL '20 hours'
            )
        LOOP
            PERFORM public.create_notification(
                v_booking.user_id,
                'event_reminder',
                'Event Tomorrow: ' || v_event.title,
                'Your event "' || v_event.title || '" is tomorrow at ' || 
                TO_CHAR(v_event.event_date, 'HH24:MI') || '. Don''t forget your tickets!',
                v_event.id,
                v_booking.booking_id,
                NULL,
                jsonb_build_object(
                    'event_date', v_event.event_date,
                    'venue', v_event.venue_name,
                    'city', v_event.city
                )
            );
            v_count := v_count + 1;
        END LOOP;
    END LOOP;
    
    RETURN v_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to clean old read notifications (older than 30 days)
CREATE OR REPLACE FUNCTION public.clean_old_notifications()
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM public.notifications
    WHERE is_read = true
    AND read_at < NOW() - INTERVAL '30 days';
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 5. TRIGGERS
-- =====================================================

-- Trigger for booking confirmation notifications
CREATE TRIGGER notify_on_booking_confirmation
    AFTER UPDATE ON public.bookings
    FOR EACH ROW
    WHEN (NEW.status = 'confirmed' AND OLD.status != 'confirmed')
    EXECUTE FUNCTION public.notify_booking_confirmation();

-- Trigger to update updated_at (if we add this column later)
-- CREATE TRIGGER handle_notifications_updated_at
--     BEFORE UPDATE ON public.notifications
--     FOR EACH ROW
--     EXECUTE FUNCTION public.handle_updated_at();

-- =====================================================
-- 6. SCHEDULED JOBS (Create via Supabase Dashboard)
-- =====================================================
-- Send event reminders every hour:
-- SELECT cron.schedule('event-reminders', '0 * * * *', 'SELECT public.send_event_reminders();');

-- Clean old notifications daily:
-- SELECT cron.schedule('clean-notifications', '0 3 * * *', 'SELECT public.clean_old_notifications();');

-- =====================================================
-- 7. ENABLE REALTIME
-- =====================================================
ALTER PUBLICATION supabase_realtime ADD TABLE public.notifications;