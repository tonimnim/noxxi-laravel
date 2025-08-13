-- =====================================================
-- EVENTS TABLE MIGRATION - SAFE RE-RUN VERSION
-- =====================================================
-- DEPENDENCIES: Run these files FIRST:
-- 1. 000_create_functions.sql (for handle_updated_at function)
-- 2. 001_create_profiles.sql
-- 3. 002_create_organizers.sql
-- 4. 003_create_event_categories.sql

-- First, remove table from publication if it exists
DO $$ 
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_publication_tables 
        WHERE pubname = 'supabase_realtime' 
        AND schemaname = 'public' 
        AND tablename = 'events'
    ) THEN
        ALTER PUBLICATION supabase_realtime DROP TABLE public.events;
    END IF;
END $$;

-- Drop ALL existing triggers on events table (comprehensive cleanup)
DO $$ 
DECLARE
    trigger_record RECORD;
BEGIN
    -- Drop all triggers on events table if it exists
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'events') THEN
        FOR trigger_record IN 
            SELECT tgname 
            FROM pg_trigger t
            JOIN pg_class c ON t.tgrelid = c.oid
            WHERE c.relname = 'events' 
            AND NOT t.tgisinternal
        LOOP
            EXECUTE format('DROP TRIGGER IF EXISTS %I ON public.events', trigger_record.tgname);
        END LOOP;
    END IF;
END $$;

-- Explicitly drop known triggers (for clarity)
DROP TRIGGER IF EXISTS generate_event_slug ON public.events;
DROP TRIGGER IF EXISTS validate_event_ticket_types ON public.events;
DROP TRIGGER IF EXISTS update_event_status_trigger ON public.events;
DROP TRIGGER IF EXISTS handle_events_updated_at ON public.events;

-- Drop existing functions if they exist
DROP FUNCTION IF EXISTS public.handle_event_slug() CASCADE;
DROP FUNCTION IF EXISTS public.validate_ticket_types() CASCADE;
DROP FUNCTION IF EXISTS public.update_event_status() CASCADE;

-- Drop existing indexes if they exist (for re-running migration)
DROP INDEX IF EXISTS idx_events_organizer_id;
DROP INDEX IF EXISTS idx_events_category_id;
DROP INDEX IF EXISTS idx_events_slug;
DROP INDEX IF EXISTS idx_events_status;
DROP INDEX IF EXISTS idx_events_event_date;
DROP INDEX IF EXISTS idx_events_city;
DROP INDEX IF EXISTS idx_events_featured;
DROP INDEX IF EXISTS idx_events_location;
DROP INDEX IF EXISTS idx_events_tags;
DROP INDEX IF EXISTS idx_events_search;

-- Drop existing table if needed (uncomment if you want to recreate)
-- DROP TABLE IF EXISTS public.events CASCADE;

-- Create events table
CREATE TABLE IF NOT EXISTS public.events (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    organizer_id UUID REFERENCES public.organizers(id) ON DELETE CASCADE NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    category_id UUID REFERENCES public.event_categories(id) NOT NULL,
    venue_name VARCHAR(255) NOT NULL,
    venue_address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    city VARCHAR(100) NOT NULL,
    event_date TIMESTAMPTZ NOT NULL,
    end_date TIMESTAMPTZ,
    ticket_types JSONB DEFAULT '[]'::jsonb,
    capacity INTEGER NOT NULL,
    tickets_sold INTEGER DEFAULT 0,
    min_price DECIMAL(10,2),
    max_price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'KES',
    images JSONB DEFAULT '[]'::jsonb,
    cover_image_url TEXT,
    tags TEXT[] DEFAULT '{}',
    status TEXT CHECK (status IN ('draft', 'published', 'cancelled', 'postponed', 'completed')) DEFAULT 'draft',
    featured BOOLEAN DEFAULT false,
    featured_until TIMESTAMPTZ,
    requires_approval BOOLEAN DEFAULT false,
    age_restriction INTEGER,
    terms_conditions TEXT,
    refund_policy TEXT,
    offline_mode_data JSONB,
    seo_keywords TEXT[] DEFAULT '{}',
    view_count INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    published_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for better performance (if they don't exist)
CREATE INDEX IF NOT EXISTS idx_events_organizer_id ON public.events(organizer_id);
CREATE INDEX IF NOT EXISTS idx_events_category_id ON public.events(category_id);
CREATE INDEX IF NOT EXISTS idx_events_slug ON public.events(slug);
CREATE INDEX IF NOT EXISTS idx_events_status ON public.events(status);
CREATE INDEX IF NOT EXISTS idx_events_event_date ON public.events(event_date);
CREATE INDEX IF NOT EXISTS idx_events_city ON public.events(city);
CREATE INDEX IF NOT EXISTS idx_events_featured ON public.events(featured) WHERE featured = true;
CREATE INDEX IF NOT EXISTS idx_events_location ON public.events(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_events_tags ON public.events USING GIN (tags);
CREATE INDEX IF NOT EXISTS idx_events_search ON public.events USING GIN (to_tsvector('english', title || ' ' || COALESCE(description, '')));

-- Enable Row Level Security
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;

-- Drop existing policies if they exist
DROP POLICY IF EXISTS "Anyone can view published events" ON public.events;
DROP POLICY IF EXISTS "Organizers can create events" ON public.events;
DROP POLICY IF EXISTS "Organizers can update own events" ON public.events;
DROP POLICY IF EXISTS "Organizers can delete own events" ON public.events;

-- Create policies for events table
-- Anyone can view published events
CREATE POLICY "Anyone can view published events" ON public.events
    FOR SELECT USING (status = 'published' OR organizer_id IN (
        SELECT id FROM public.organizers WHERE user_id = auth.uid()
    ));

-- Organizers can create events
CREATE POLICY "Organizers can create events" ON public.events
    FOR INSERT WITH CHECK (
        organizer_id IN (
            SELECT id FROM public.organizers 
            WHERE user_id = auth.uid() AND is_active = true
        )
    );

-- Organizers can update their own events
CREATE POLICY "Organizers can update own events" ON public.events
    FOR UPDATE USING (
        organizer_id IN (
            SELECT id FROM public.organizers WHERE user_id = auth.uid()
        )
    );

-- Organizers can delete their own draft events only
CREATE POLICY "Organizers can delete own draft events" ON public.events
    FOR DELETE USING (
        status = 'draft' AND organizer_id IN (
            SELECT id FROM public.organizers WHERE user_id = auth.uid()
        )
    );

-- Enable realtime (only if not already added)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_publication_tables 
        WHERE pubname = 'supabase_realtime' 
        AND schemaname = 'public' 
        AND tablename = 'events'
    ) THEN
        ALTER PUBLICATION supabase_realtime ADD TABLE public.events;
    END IF;
END $$;

-- Function to generate event slug
CREATE OR REPLACE FUNCTION public.handle_event_slug()
RETURNS TRIGGER AS $$
DECLARE
    base_slug TEXT;
    new_slug TEXT;
    counter INTEGER := 1;
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        -- Generate base slug from title and date
        base_slug = public.generate_slug(NEW.title || '-' || TO_CHAR(NEW.event_date, 'DD-Mon'));
        new_slug = base_slug;
        
        -- Ensure uniqueness
        WHILE EXISTS (SELECT 1 FROM public.events WHERE slug = new_slug AND id != COALESCE(NEW.id, gen_random_uuid())) LOOP
            new_slug = base_slug || '-' || counter;
            counter := counter + 1;
        END LOOP;
        
        NEW.slug = new_slug;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to validate ticket types JSONB
CREATE OR REPLACE FUNCTION public.validate_ticket_types()
RETURNS TRIGGER AS $$
BEGIN
    -- Ensure ticket_types is an array
    IF jsonb_typeof(NEW.ticket_types) != 'array' THEN
        RAISE EXCEPTION 'ticket_types must be an array';
    END IF;
    
    -- Validate each ticket type has required fields
    IF NEW.ticket_types IS NOT NULL AND NEW.ticket_types != '[]'::jsonb THEN
        FOR i IN 0..jsonb_array_length(NEW.ticket_types) - 1 LOOP
            IF NOT (NEW.ticket_types->i ? 'name' AND 
                    NEW.ticket_types->i ? 'price' AND 
                    NEW.ticket_types->i ? 'quantity') THEN
                RAISE EXCEPTION 'Each ticket type must have name, price, and quantity';
            END IF;
        END LOOP;
    END IF;
    
    -- Calculate min and max prices
    IF NEW.ticket_types IS NOT NULL AND jsonb_array_length(NEW.ticket_types) > 0 THEN
        NEW.min_ticket_price = (
            SELECT MIN((ticket->>'price')::DECIMAL)
            FROM jsonb_array_elements(NEW.ticket_types) AS ticket
        );
        NEW.max_ticket_price = (
            SELECT MAX((ticket->>'price')::DECIMAL)
            FROM jsonb_array_elements(NEW.ticket_types) AS ticket
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to update event status based on date
CREATE OR REPLACE FUNCTION public.update_event_status()
RETURNS TRIGGER AS $$
BEGIN
    -- Auto-complete events that have ended
    IF NEW.end_date IS NOT NULL AND NEW.end_date < NOW() AND NEW.status = 'published' THEN
        NEW.status = 'completed';
    END IF;
    
    -- Set published_at when status changes to published
    IF NEW.status = 'published' AND OLD.status != 'published' THEN
        NEW.published_at = NOW();
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create triggers (drop first to ensure clean state)
DROP TRIGGER IF EXISTS generate_event_slug ON public.events;
CREATE TRIGGER generate_event_slug
    BEFORE INSERT OR UPDATE ON public.events
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_event_slug();

DROP TRIGGER IF EXISTS validate_event_ticket_types ON public.events;
CREATE TRIGGER validate_event_ticket_types
    BEFORE INSERT OR UPDATE ON public.events
    FOR EACH ROW
    EXECUTE FUNCTION public.validate_ticket_types();

DROP TRIGGER IF EXISTS update_event_status_trigger ON public.events;
CREATE TRIGGER update_event_status_trigger
    BEFORE UPDATE ON public.events
    FOR EACH ROW
    EXECUTE FUNCTION public.update_event_status();

-- Create updated_at trigger
DROP TRIGGER IF EXISTS handle_events_updated_at ON public.events;
CREATE TRIGGER handle_events_updated_at
    BEFORE UPDATE ON public.events
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- Function to increment view count
CREATE OR REPLACE FUNCTION public.increment_event_view(event_id UUID)
RETURNS VOID AS $$
BEGIN
    UPDATE public.events
    SET view_count = view_count + 1
    WHERE id = event_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;