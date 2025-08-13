-- =====================================================
-- ORGANIZER MANAGERS TABLE
-- =====================================================
-- Permission management for users who can scan tickets

-- Drop existing if exists
DROP TABLE IF EXISTS public.organizer_managers CASCADE;

-- Create organizer_managers table
CREATE TABLE public.organizer_managers (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    
    -- References (foreign key added after table creation)
    organizer_id UUID NOT NULL,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    granted_by UUID REFERENCES public.profiles(id) NOT NULL,
    
    -- Permissions
    permissions JSONB DEFAULT '{
        "can_scan_tickets": true,
        "can_validate_entries": true,
        "can_view_analytics": false,
        "can_manage_events": false,
        "can_manage_other_managers": false
    }'::jsonb NOT NULL,
    
    -- Event scope (null means all organizer's events)
    event_ids UUID[] DEFAULT NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    
    -- Validity period
    valid_from TIMESTAMPTZ DEFAULT NOW(),
    valid_until TIMESTAMPTZ DEFAULT NULL,
    
    -- Metadata
    notes TEXT,
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- Ensure one active manager permission per user per organizer
    CONSTRAINT unique_active_manager UNIQUE(organizer_id, user_id, is_active)
);

-- Create indexes
CREATE INDEX idx_organizer_managers_organizer ON public.organizer_managers(organizer_id);
CREATE INDEX idx_organizer_managers_user ON public.organizer_managers(user_id);
CREATE INDEX idx_organizer_managers_granted_by ON public.organizer_managers(granted_by);
CREATE INDEX idx_organizer_managers_active ON public.organizer_managers(is_active) WHERE is_active = true;
CREATE INDEX idx_organizer_managers_validity ON public.organizer_managers(valid_from, valid_until);

-- Enable RLS
ALTER TABLE public.organizer_managers ENABLE ROW LEVEL SECURITY;

-- RLS Policies
CREATE POLICY "Managers can view own permissions" 
    ON public.organizer_managers FOR SELECT 
    USING (user_id = auth.uid());

CREATE POLICY "Organizers can view their managers" 
    ON public.organizer_managers FOR SELECT 
    USING (
        organizer_id IN (
            SELECT id FROM public.organizers WHERE user_id = auth.uid()
        )
    );

CREATE POLICY "Organizers can manage their managers" 
    ON public.organizer_managers FOR ALL 
    USING (
        organizer_id IN (
            SELECT id FROM public.organizers WHERE user_id = auth.uid()
        )
    );

CREATE POLICY "Admins have full access to organizer_managers" 
    ON public.organizer_managers FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Helper Functions
CREATE OR REPLACE FUNCTION public.is_manager_for_organizer(
    check_user_id UUID,
    check_organizer_id UUID,
    check_event_id UUID DEFAULT NULL
)
RETURNS BOOLEAN AS $$
DECLARE
    has_permission BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 FROM public.organizer_managers
        WHERE user_id = check_user_id
        AND organizer_id = check_organizer_id
        AND is_active = true
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_until IS NULL OR valid_until >= NOW())
        AND (
            check_event_id IS NULL 
            OR event_ids IS NULL 
            OR check_event_id = ANY(event_ids)
        )
    ) INTO has_permission;
    
    RETURN has_permission;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger for updated_at
CREATE TRIGGER handle_organizer_managers_updated_at
    BEFORE UPDATE ON public.organizer_managers
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- Enable realtime
ALTER PUBLICATION supabase_realtime ADD TABLE public.organizer_managers;

-- =====================================================
-- ADD FOREIGN KEY CONSTRAINT
-- =====================================================
ALTER TABLE public.organizer_managers 
    ADD CONSTRAINT fk_organizer_managers_organizer 
    FOREIGN KEY (organizer_id) 
    REFERENCES public.organizers(id) 
    ON DELETE CASCADE;

-- =====================================================
-- ADD POLICY TO ORGANIZERS TABLE
-- =====================================================
CREATE POLICY "Managers can view their organizers" 
    ON public.organizers FOR SELECT 
    USING (
        EXISTS (
            SELECT 1 FROM public.organizer_managers 
            WHERE organizer_managers.organizer_id = organizers.id 
            AND organizer_managers.user_id = auth.uid()
            AND organizer_managers.is_active = true
        )
    );

-- =====================================================
-- ADD FUNCTION FOR ORGANIZER EVENT MANAGEMENT
-- =====================================================
CREATE OR REPLACE FUNCTION public.can_manage_organizer_events(
    p_user_id UUID,
    p_organizer_id UUID
)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        -- User is the organizer
        SELECT 1 FROM public.organizers 
        WHERE id = p_organizer_id AND user_id = p_user_id
        
        UNION
        
        -- User is a manager with event management permission
        SELECT 1 FROM public.organizer_managers 
        WHERE organizer_id = p_organizer_id 
        AND user_id = p_user_id
        AND is_active = true
        AND (permissions->>'can_manage_events')::boolean = true
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;