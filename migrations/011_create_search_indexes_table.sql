-- =====================================================
-- SEARCH INDEXES TABLE
-- =====================================================
-- Optimized full-text search functionality

-- Drop existing objects if they exist
DROP TABLE IF EXISTS public.search_indexes CASCADE;

-- =====================================================
-- 1. SEARCH INDEXES TABLE
-- =====================================================
CREATE TABLE public.search_indexes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    
    -- Reference to source
    entity_type TEXT CHECK (entity_type IN ('event', 'organizer', 'category')) NOT NULL,
    entity_id UUID NOT NULL,
    
    -- Searchable content
    title TEXT NOT NULL,
    description TEXT,
    tags TEXT[],
    
    -- Full-text search vectors
    search_vector tsvector,
    
    -- Metadata for ranking
    popularity_score INTEGER DEFAULT 0,
    relevance_score DECIMAL(3,2) DEFAULT 1.0,
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    
    -- Timestamps
    indexed_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for search
CREATE INDEX idx_search_vector ON public.search_indexes USING GIN(search_vector);
CREATE INDEX idx_search_entity ON public.search_indexes(entity_type, entity_id);
CREATE INDEX idx_search_active ON public.search_indexes(is_active);
CREATE INDEX idx_search_popularity ON public.search_indexes(popularity_score DESC);

-- Create unique constraint
CREATE UNIQUE INDEX idx_search_unique_entity ON public.search_indexes(entity_type, entity_id);

-- =====================================================
-- 2. FUNCTIONS
-- =====================================================

-- Function to update search vector
CREATE OR REPLACE FUNCTION public.update_search_vector()
RETURNS TRIGGER AS $$
BEGIN
    NEW.search_vector := 
        setweight(to_tsvector('english', COALESCE(NEW.title, '')), 'A') ||
        setweight(to_tsvector('english', COALESCE(NEW.description, '')), 'B') ||
        setweight(to_tsvector('english', COALESCE(array_to_string(NEW.tags, ' '), '')), 'C');
    
    NEW.updated_at := NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to index an event
CREATE OR REPLACE FUNCTION public.index_event(p_event_id UUID)
RETURNS void AS $$
DECLARE
    v_event RECORD;
BEGIN
    SELECT 
        e.id,
        e.title,
        e.description,
        e.tickets_sold,
        ec.name as category_name,
        o.business_name as organizer_name
    INTO v_event
    FROM public.events e
    JOIN public.event_categories ec ON e.category_id = ec.id
    JOIN public.organizers o ON e.organizer_id = o.id
    WHERE e.id = p_event_id;
    
    IF FOUND THEN
        INSERT INTO public.search_indexes (
            entity_type,
            entity_id,
            title,
            description,
            tags,
            popularity_score,
            is_active
        ) VALUES (
            'event',
            p_event_id,
            v_event.title,
            v_event.description,
            ARRAY[v_event.category_name, v_event.organizer_name],
            v_event.tickets_sold,
            true
        )
        ON CONFLICT (entity_type, entity_id) 
        DO UPDATE SET
            title = EXCLUDED.title,
            description = EXCLUDED.description,
            tags = EXCLUDED.tags,
            popularity_score = EXCLUDED.popularity_score,
            updated_at = NOW();
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to perform search
CREATE OR REPLACE FUNCTION public.search_events(
    p_query TEXT,
    p_limit INTEGER DEFAULT 20,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE (
    event_id UUID,
    title TEXT,
    description TEXT,
    rank REAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        si.entity_id as event_id,
        si.title,
        si.description,
        ts_rank(si.search_vector, plainto_tsquery('english', p_query)) * si.relevance_score as rank
    FROM public.search_indexes si
    WHERE 
        si.entity_type = 'event'
        AND si.is_active = true
        AND si.search_vector @@ plainto_tsquery('english', p_query)
    ORDER BY rank DESC, si.popularity_score DESC
    LIMIT p_limit
    OFFSET p_offset;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 3. TRIGGERS
-- =====================================================

-- Trigger to update search vector
CREATE TRIGGER update_search_vector_trigger
    BEFORE INSERT OR UPDATE ON public.search_indexes
    FOR EACH ROW
    EXECUTE FUNCTION public.update_search_vector();

-- Trigger to index events on insert/update
CREATE OR REPLACE FUNCTION public.auto_index_event()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'published' THEN
        PERFORM public.index_event(NEW.id);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER index_event_on_change
    AFTER INSERT OR UPDATE ON public.events
    FOR EACH ROW
    WHEN (NEW.status = 'published')
    EXECUTE FUNCTION public.auto_index_event();

-- =====================================================
-- 4. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.search_indexes ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 5. RLS POLICIES
-- =====================================================

-- Everyone can search
CREATE POLICY "Public can search" 
    ON public.search_indexes FOR SELECT 
    USING (is_active = true);

-- Only system can modify search indexes
CREATE POLICY "System manages indexes" 
    ON public.search_indexes FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'admin'
        )
    );