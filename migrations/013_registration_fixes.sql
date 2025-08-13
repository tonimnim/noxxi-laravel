-- =====================================================
-- REGISTRATION SECURITY & AUDIT FIXES
-- =====================================================
-- Run this AFTER all other migrations to fix registration issues

-- 1. Create audit log table for security tracking
CREATE TABLE IF NOT EXISTS public.audit_logs (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    event_type VARCHAR(100) NOT NULL,
    event_status VARCHAR(50),
    ip_address INET,
    user_agent TEXT,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Index for querying
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON public.audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_event_type ON public.audit_logs(event_type);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON public.audit_logs(created_at DESC);

-- 2. Add registration tracking to profiles
ALTER TABLE public.profiles 
ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMPTZ,
ADD COLUMN IF NOT EXISTS phone_verified_at TIMESTAMPTZ,
ADD COLUMN IF NOT EXISTS registration_ip INET,
ADD COLUMN IF NOT EXISTS registration_completed_at TIMESTAMPTZ;

-- 3. Add security fields to organizers
ALTER TABLE public.organizers
ADD COLUMN IF NOT EXISTS approval_required BOOLEAN DEFAULT true,
ADD COLUMN IF NOT EXISTS approval_notes TEXT,
ADD COLUMN IF NOT EXISTS risk_score INTEGER DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_security_review TIMESTAMPTZ;

-- 4. Create rate limiting table
CREATE TABLE IF NOT EXISTS public.rate_limits (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL, -- IP or email
    action VARCHAR(100) NOT NULL, -- registration, login, etc
    attempts INTEGER DEFAULT 1,
    first_attempt_at TIMESTAMPTZ DEFAULT NOW(),
    last_attempt_at TIMESTAMPTZ DEFAULT NOW(),
    blocked_until TIMESTAMPTZ,
    UNIQUE(identifier, action)
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_identifier ON public.rate_limits(identifier);
CREATE INDEX IF NOT EXISTS idx_rate_limits_blocked_until ON public.rate_limits(blocked_until);

-- 5. Function to check rate limits
CREATE OR REPLACE FUNCTION public.check_rate_limit(
    p_identifier VARCHAR(255),
    p_action VARCHAR(100),
    p_max_attempts INTEGER DEFAULT 3,
    p_window_minutes INTEGER DEFAULT 15
)
RETURNS BOOLEAN AS $$
DECLARE
    v_record RECORD;
    v_now TIMESTAMPTZ := NOW();
BEGIN
    -- Get existing record
    SELECT * INTO v_record
    FROM public.rate_limits
    WHERE identifier = p_identifier AND action = p_action;
    
    -- No record, create one
    IF NOT FOUND THEN
        INSERT INTO public.rate_limits (identifier, action, attempts)
        VALUES (p_identifier, p_action, 1);
        RETURN true;
    END IF;
    
    -- Check if blocked
    IF v_record.blocked_until IS NOT NULL AND v_record.blocked_until > v_now THEN
        RETURN false;
    END IF;
    
    -- Check if window expired
    IF v_record.first_attempt_at < (v_now - (p_window_minutes || ' minutes')::INTERVAL) THEN
        -- Reset counter
        UPDATE public.rate_limits
        SET attempts = 1,
            first_attempt_at = v_now,
            last_attempt_at = v_now,
            blocked_until = NULL
        WHERE identifier = p_identifier AND action = p_action;
        RETURN true;
    END IF;
    
    -- Within window, check attempts
    IF v_record.attempts >= p_max_attempts THEN
        -- Block for double the window time
        UPDATE public.rate_limits
        SET blocked_until = v_now + ((p_window_minutes * 2) || ' minutes')::INTERVAL,
            last_attempt_at = v_now
        WHERE identifier = p_identifier AND action = p_action;
        RETURN false;
    END IF;
    
    -- Increment attempts
    UPDATE public.rate_limits
    SET attempts = attempts + 1,
        last_attempt_at = v_now
    WHERE identifier = p_identifier AND action = p_action;
    
    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 6. Function to validate email format
CREATE OR REPLACE FUNCTION public.is_valid_email(email TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$';
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- 7. Function to validate phone format
CREATE OR REPLACE FUNCTION public.is_valid_phone(phone TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    -- International phone format
    RETURN phone ~ '^\+?[1-9]\d{1,14}$';
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- 8. Add constraints for validation
ALTER TABLE public.profiles
ADD CONSTRAINT valid_email CHECK (is_valid_email(email)),
ADD CONSTRAINT valid_phone CHECK (is_valid_phone(phone_number));

ALTER TABLE public.organizers
ADD CONSTRAINT valid_business_email CHECK (is_valid_email(business_email));

-- 9. Trigger to log registration attempts
CREATE OR REPLACE FUNCTION public.log_registration_attempt()
RETURNS TRIGGER AS $$
BEGIN
    -- Log successful registration
    INSERT INTO public.audit_logs (
        user_id,
        event_type,
        event_status,
        metadata
    ) VALUES (
        NEW.id,
        'user_registration',
        'success',
        jsonb_build_object(
            'email', NEW.email,
            'role', NEW.role,
            'timestamp', NOW()
        )
    );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS log_registration ON public.profiles;
CREATE TRIGGER log_registration
    AFTER INSERT ON public.profiles
    FOR EACH ROW
    EXECUTE FUNCTION public.log_registration_attempt();

-- 10. Clean up duplicate prevention
CREATE OR REPLACE FUNCTION public.prevent_duplicate_profile()
RETURNS TRIGGER AS $$
BEGIN
    -- Check if profile already exists
    IF EXISTS (
        SELECT 1 FROM public.profiles 
        WHERE (email = NEW.email OR phone_number = NEW.phone_number)
        AND id != NEW.id
    ) THEN
        RAISE EXCEPTION 'Email or phone number already registered';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS prevent_duplicate_profile ON public.profiles;
CREATE TRIGGER prevent_duplicate_profile
    BEFORE INSERT OR UPDATE ON public.profiles
    FOR EACH ROW
    EXECUTE FUNCTION public.prevent_duplicate_profile();

-- 11. RLS Policies for new tables
ALTER TABLE public.audit_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rate_limits ENABLE ROW LEVEL SECURITY;

-- Admin can view all audit logs
CREATE POLICY "Admins can view audit logs" ON public.audit_logs
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Users can view their own audit logs
CREATE POLICY "Users can view own audit logs" ON public.audit_logs
    FOR SELECT
    USING (user_id = auth.uid());

-- Only system can manage rate limits
CREATE POLICY "System manages rate limits" ON public.rate_limits
    FOR ALL
    USING (false)
    WITH CHECK (false);

-- Grant necessary permissions
GRANT EXECUTE ON FUNCTION public.check_rate_limit TO authenticated;
GRANT EXECUTE ON FUNCTION public.is_valid_email TO authenticated;
GRANT EXECUTE ON FUNCTION public.is_valid_phone TO authenticated;

COMMENT ON TABLE public.audit_logs IS 'Security audit trail for all user actions';
COMMENT ON TABLE public.rate_limits IS 'Rate limiting for preventing abuse';
COMMENT ON FUNCTION public.check_rate_limit IS 'Check if action is rate limited';

-- Success message
DO $$
BEGIN
    RAISE NOTICE 'Registration security fixes applied successfully';
END $$;