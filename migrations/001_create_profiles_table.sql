-- =====================================================
-- PROFILES TABLE - Using auth.users.id as Primary Key
-- =====================================================
-- This creates a 1:1 relationship where profiles.id = auth.users.id
-- No separate UUID generation, preventing ID confusion

-- Drop existing tables and functions if they exist
DO $$ 
BEGIN
    -- Drop trigger on auth.users if exists
    IF EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'on_auth_user_created') THEN
        DROP TRIGGER on_auth_user_created ON auth.users;
    END IF;
    
    -- Drop trigger on profiles if table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'profiles') THEN
        DROP TRIGGER IF EXISTS handle_profiles_updated_at ON public.profiles;
    END IF;
END $$;

-- Drop function and table
DROP FUNCTION IF EXISTS public.handle_new_user() CASCADE;
DROP TABLE IF EXISTS public.profiles CASCADE;

-- =====================================================
-- 1. PROFILES TABLE - Core user identity
-- =====================================================
CREATE TABLE public.profiles (
    -- Use auth.users.id as primary key (no separate UUID)
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    
    -- Required fields
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    role TEXT CHECK (role IN ('admin', 'organizer', 'user')) DEFAULT 'user' NOT NULL,
    
    -- Optional fields
    avatar_url TEXT,
    country_code VARCHAR(3) DEFAULT 'KE',
    city VARCHAR(100),
    
    -- Preferences and settings
    notification_preferences JSONB DEFAULT '{"email": true, "sms": true, "push": true}'::jsonb,
    
    -- Status flags
    is_active BOOLEAN DEFAULT true,
    is_verified BOOLEAN DEFAULT false,
    
    -- Metadata for flexible storage
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Timestamps
    last_active_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_profiles_email ON public.profiles(email);
CREATE INDEX idx_profiles_phone_number ON public.profiles(phone_number);
CREATE INDEX idx_profiles_role ON public.profiles(role);
CREATE INDEX idx_profiles_is_active ON public.profiles(is_active);
CREATE INDEX idx_profiles_full_name ON public.profiles(full_name);

-- Add comments for documentation
COMMENT ON TABLE public.profiles IS 'Core user profiles - uses auth.users.id as primary key for 1:1 relationship';
COMMENT ON COLUMN public.profiles.id IS 'Same as auth.users.id - no separate UUID';
COMMENT ON COLUMN public.profiles.role IS 'Base role: admin, organizer, or user. Managers are users with permissions in organizer_managers table';
COMMENT ON COLUMN public.profiles.metadata IS 'Flexible JSONB field for additional user data';


-- =====================================================
-- 3. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 4. RLS POLICIES FOR PROFILES
-- =====================================================

-- Everyone can view active profiles (for user search, event attendees, etc.)
CREATE POLICY "Public profiles are viewable by everyone" 
    ON public.profiles FOR SELECT 
    USING (is_active = true);

-- Users can update their own profile
CREATE POLICY "Users can update own profile" 
    ON public.profiles FOR UPDATE 
    USING (auth.uid() = id)
    WITH CHECK (auth.uid() = id);

-- Users can insert their own profile (handled by trigger, but policy needed)
CREATE POLICY "Users can insert own profile" 
    ON public.profiles FOR INSERT 
    WITH CHECK (auth.uid() = id);

-- Admins have full access
CREATE POLICY "Admins have full access to profiles" 
    ON public.profiles FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );


-- =====================================================
-- 6. FUNCTIONS
-- =====================================================

-- Function to handle new user creation
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    -- Insert profile with same ID as auth.users.id
    INSERT INTO public.profiles (
        id,
        email,
        full_name,
        phone_number,
        role,
        metadata
    ) VALUES (
        NEW.id,  -- Use auth.users.id as profiles.id
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'full_name', NEW.email),
        COALESCE(NEW.raw_user_meta_data->>'phone_number', 'PENDING'),
        COALESCE(NEW.raw_user_meta_data->>'role', 'user'),
        COALESCE(NEW.raw_user_meta_data->'metadata', '{}'::jsonb)
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- handle_updated_at function is defined in 000_create_functions.sql

-- Manager functions will be created after organizer_managers table

-- =====================================================
-- 7. TRIGGERS
-- =====================================================

-- Trigger to automatically create profile on user signup
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW 
    EXECUTE FUNCTION public.handle_new_user();

-- Trigger to update updated_at on profiles
CREATE TRIGGER handle_profiles_updated_at
    BEFORE UPDATE ON public.profiles
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();


-- =====================================================
-- 8. ENABLE REALTIME
-- =====================================================
ALTER PUBLICATION supabase_realtime ADD TABLE public.profiles;