-- =====================================================
-- EVENT CATEGORIES TABLE
-- =====================================================
-- Categories for organizing different types of events/tickets

-- Drop existing objects if they exist
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'event_categories') THEN
        DROP TRIGGER IF EXISTS handle_event_categories_updated_at ON public.event_categories;
        DROP TRIGGER IF EXISTS generate_category_slug ON public.event_categories;
    END IF;
END $$;

DROP FUNCTION IF EXISTS public.handle_category_slug() CASCADE;
DROP FUNCTION IF EXISTS public.generate_slug(TEXT) CASCADE;
DROP TABLE IF EXISTS public.event_categories CASCADE;

-- =====================================================
-- 1. EVENT CATEGORIES TABLE
-- =====================================================
CREATE TABLE public.event_categories (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    
    -- Visual elements
    icon_url TEXT,
    banner_url TEXT,
    color_hex VARCHAR(7), -- For UI theming
    
    -- Hierarchy
    parent_id UUID REFERENCES public.event_categories(id) ON DELETE CASCADE,
    display_order INTEGER DEFAULT 0,
    
    -- Description for better UX
    description TEXT,
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    is_featured BOOLEAN DEFAULT false,
    
    -- Metadata for future expansion
    metadata JSONB DEFAULT '{}'::jsonb,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_event_categories_slug ON public.event_categories(slug);
CREATE INDEX idx_event_categories_parent_id ON public.event_categories(parent_id);
CREATE INDEX idx_event_categories_is_active ON public.event_categories(is_active);
CREATE INDEX idx_event_categories_display_order ON public.event_categories(display_order);

-- Add comments for documentation
COMMENT ON TABLE public.event_categories IS 'Hierarchical categories for events and experiences';
COMMENT ON COLUMN public.event_categories.slug IS 'URL-friendly unique identifier';
COMMENT ON COLUMN public.event_categories.color_hex IS 'Hex color for category theming in UI';

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.event_categories ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Everyone can view active categories
CREATE POLICY "Categories are public" 
    ON public.event_categories FOR SELECT 
    USING (is_active = true);

-- Only admins can manage categories
CREATE POLICY "Admins can manage categories" 
    ON public.event_categories FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE profiles.id = auth.uid() AND profiles.role = 'admin'
        )
    );

-- =====================================================
-- 4. FUNCTIONS
-- =====================================================

-- Function to generate slug from name
CREATE OR REPLACE FUNCTION public.generate_slug(input_text TEXT)
RETURNS TEXT AS $$
BEGIN
    RETURN LOWER(
        REGEXP_REPLACE(
            REGEXP_REPLACE(
                TRIM(input_text),
                '[^a-zA-Z0-9\s-]', '', 'g'
            ),
            '\s+', '-', 'g'
        )
    );
END;
$$ LANGUAGE plpgsql;

-- Function to handle category slug generation
CREATE OR REPLACE FUNCTION public.handle_category_slug()
RETURNS TRIGGER AS $$
DECLARE
    base_slug TEXT;
    final_slug TEXT;
    counter INTEGER := 1;
BEGIN
    -- Generate slug if not provided
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        NEW.slug = public.generate_slug(NEW.name);
    END IF;
    
    -- Ensure slug is unique
    base_slug := NEW.slug;
    final_slug := base_slug;
    
    WHILE EXISTS (
        SELECT 1 FROM public.event_categories 
        WHERE slug = final_slug 
        AND id != COALESCE(NEW.id, gen_random_uuid())
    ) LOOP
        final_slug := base_slug || '-' || counter;
        counter := counter + 1;
    END LOOP;
    
    NEW.slug := final_slug;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 5. TRIGGERS
-- =====================================================

-- Trigger for slug generation
CREATE TRIGGER generate_category_slug
    BEFORE INSERT OR UPDATE ON public.event_categories
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_category_slug();

-- Trigger to update updated_at
CREATE TRIGGER handle_event_categories_updated_at
    BEFORE UPDATE ON public.event_categories
    FOR EACH ROW
    EXECUTE FUNCTION public.handle_updated_at();

-- =====================================================
-- 6. INSERT DEFAULT CATEGORIES
-- =====================================================

-- Insert main categories
INSERT INTO public.event_categories (name, slug, display_order, color_hex, description) VALUES
    ('Events', 'events', 1, '#FF6B6B', 'Concerts, festivals, comedy shows, and more'),
    ('Sports', 'sports', 2, '#4ECDC4', 'Live sports events and competitions'),
    ('Cinema', 'cinema', 3, '#45B7D1', 'Movies and film experiences'),
    ('Experiences', 'experiences', 4, '#96CEB4', 'Tours and unique activities');

-- Insert subcategories for Events
WITH events_parent AS (
    SELECT id FROM public.event_categories WHERE slug = 'events'
)
INSERT INTO public.event_categories (name, slug, parent_id, display_order)
SELECT name, slug, events_parent.id, ord 
FROM events_parent,
(VALUES
    ('Concerts', 'concerts', 1),
    ('Festivals', 'festivals', 2),
    ('Comedy Shows', 'comedy', 3),
    ('Theatre & Arts', 'theatre', 4),
    ('Conferences', 'conferences', 5),
    ('Religious', 'religious', 6)
) AS t(name, slug, ord);

-- Insert subcategories for Sports
WITH sports_parent AS (
    SELECT id FROM public.event_categories WHERE slug = 'sports'
)
INSERT INTO public.event_categories (name, slug, parent_id, display_order)
SELECT name, slug, sports_parent.id, ord 
FROM sports_parent,
(VALUES
    ('Football', 'football', 1),
    ('Rugby', 'rugby', 2),
    ('Athletics', 'athletics', 3),
    ('Basketball', 'basketball', 4),
    ('Safari Rally', 'safari-rally', 5),
    ('Cricket', 'cricket', 6),
    ('Tennis', 'tennis', 7),
    ('Golf', 'golf', 8),
    ('Marathon', 'marathon', 9),
    ('Boxing', 'boxing', 10)
) AS t(name, slug, ord);

-- Cinema is a standalone category without subcategories
-- Users will see all cinema events under this single category

-- Insert subcategories for Experiences
WITH exp_parent AS (
    SELECT id FROM public.event_categories WHERE slug = 'experiences'
)
INSERT INTO public.event_categories (name, slug, parent_id, display_order)
SELECT name, slug, exp_parent.id, ord 
FROM exp_parent,
(VALUES
    ('Safari & Tours', 'safari-tours', 1),
    ('Wellness & Spa', 'wellness-spa', 2),
    ('Game Parks', 'game-parks', 3),
    ('Cultural Tours', 'cultural-tours', 4)
) AS t(name, slug, ord);

-- =====================================================
-- 7. ENABLE REALTIME (Optional)
-- =====================================================
-- Categories don't change often, realtime might not be needed
-- ALTER PUBLICATION supabase_realtime ADD TABLE public.event_categories;