-- =====================================================
-- VIEWS (Virtual Tables - Not Physical Storage)
-- =====================================================
-- These are saved SQL queries that act like tables but don't store data

-- Drop existing views if they exist
DROP VIEW IF EXISTS public.event_availability CASCADE;
DROP VIEW IF EXISTS public.organizer_dashboard CASCADE;
DROP VIEW IF EXISTS public.upcoming_events CASCADE;

-- =====================================================
-- 1. EVENT AVAILABILITY VIEW
-- =====================================================
-- Real-time view showing ticket availability for all events
CREATE VIEW public.event_availability AS
SELECT 
    e.id as event_id,
    e.title,
    e.slug,
    e.event_date,
    e.venue_name,
    e.city as location,
    e.capacity as total_capacity,
    e.tickets_sold,
    e.capacity - e.tickets_sold as available_tickets,
    
    -- Calculate availability percentage
    CASE 
        WHEN e.capacity > 0 THEN 
            ROUND(((e.capacity - e.tickets_sold)::DECIMAL / e.capacity) * 100, 2)
        ELSE 0 
    END as availability_percentage,
    
    -- Status based on availability
    CASE 
        WHEN e.status != 'published' THEN 'not_available'
        WHEN e.tickets_sold >= e.capacity THEN 'sold_out'
        WHEN (e.capacity - e.tickets_sold) <= 10 THEN 'almost_sold_out'
        WHEN ((e.capacity - e.tickets_sold)::DECIMAL / e.capacity) <= 0.2 THEN 'selling_fast'
        ELSE 'available'
    END as availability_status,
    
    -- Pricing info
    e.min_price,
    e.max_price,
    e.currency,
    
    -- Event status
    e.status as event_status,
    
    -- Category info
    ec.name as category_name,
    ec.slug as category_slug
    
FROM public.events e
LEFT JOIN public.event_categories ec ON e.category_id = ec.id
WHERE e.event_date >= CURRENT_DATE;

-- Add comment
COMMENT ON VIEW public.event_availability IS 'Real-time ticket availability for all upcoming events';

-- =====================================================
-- 2. ORGANIZER DASHBOARD VIEW
-- =====================================================
-- Summary statistics for each organizer
CREATE VIEW public.organizer_dashboard AS
WITH booking_stats AS (
    SELECT 
        e.organizer_id,
        b.currency,
        COUNT(DISTINCT b.id) as total_bookings,
        COUNT(DISTINCT CASE 
            WHEN b.created_at >= CURRENT_DATE THEN b.id 
        END) as bookings_today,
        COUNT(DISTINCT CASE 
            WHEN b.created_at >= CURRENT_DATE - INTERVAL '7 days' THEN b.id 
        END) as bookings_this_week,
        COUNT(DISTINCT CASE 
            WHEN b.created_at >= DATE_TRUNC('month', CURRENT_DATE) THEN b.id 
        END) as bookings_this_month,
        
        SUM(CASE 
            WHEN b.created_at >= CURRENT_DATE THEN b.quantity 
            ELSE 0 
        END) as tickets_sold_today,
        
        SUM(CASE 
            WHEN b.created_at >= CURRENT_DATE - INTERVAL '7 days' THEN b.quantity 
            ELSE 0 
        END) as tickets_sold_this_week,
        
        -- Simple revenue sums
        SUM(CASE 
            WHEN b.status = 'confirmed' 
            THEN b.total_amount - b.service_fee 
            ELSE 0 
        END) as total_revenue,
        
        SUM(CASE 
            WHEN b.status = 'confirmed' AND b.created_at >= CURRENT_DATE - INTERVAL '7 days'
            THEN b.total_amount - b.service_fee 
            ELSE 0 
        END) as revenue_this_week,
        
        SUM(CASE 
            WHEN b.status = 'confirmed' AND b.created_at >= DATE_TRUNC('month', CURRENT_DATE)
            THEN b.total_amount - b.service_fee 
            ELSE 0 
        END) as revenue_this_month
        
    FROM public.events e
    LEFT JOIN public.bookings b ON b.event_id = e.id
    GROUP BY e.organizer_id, b.currency
),
aggregated_stats AS (
    SELECT 
        organizer_id,
        SUM(total_bookings) as total_bookings,
        SUM(bookings_today) as bookings_today,
        SUM(bookings_this_week) as bookings_this_week,
        SUM(bookings_this_month) as bookings_this_month,
        SUM(tickets_sold_today) as tickets_sold_today,
        SUM(tickets_sold_this_week) as tickets_sold_this_week,
        jsonb_object_agg(
            COALESCE(currency, 'KES'),
            total_revenue
        ) as total_revenue,
        jsonb_object_agg(
            COALESCE(currency, 'KES') || '_week',
            revenue_this_week
        ) as revenue_this_week,
        jsonb_object_agg(
            COALESCE(currency, 'KES') || '_month',
            revenue_this_month
        ) as revenue_this_month
    FROM booking_stats
    GROUP BY organizer_id
),
event_stats AS (
    SELECT 
        organizer_id,
        COUNT(*) as total_events,
        COUNT(CASE WHEN status = 'published' THEN 1 END) as published_events,
        COUNT(CASE WHEN event_date >= CURRENT_DATE THEN 1 END) as upcoming_events,
        COUNT(CASE WHEN event_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days' THEN 1 END) as events_next_30_days,
        MIN(CASE WHEN event_date >= CURRENT_DATE THEN event_date END) as next_event_date
    FROM public.events
    GROUP BY organizer_id
)
SELECT 
    o.id as organizer_id,
    o.business_name,
    o.is_active,
    o.rating,
    
    -- Event statistics
    COALESCE(es.total_events, 0) as total_events,
    COALESCE(es.published_events, 0) as published_events,
    COALESCE(es.upcoming_events, 0) as upcoming_events,
    COALESCE(es.events_next_30_days, 0) as events_next_30_days,
    es.next_event_date,
    
    -- Booking statistics
    COALESCE(ast.total_bookings, 0) as total_bookings,
    COALESCE(ast.bookings_today, 0) as bookings_today,
    COALESCE(ast.bookings_this_week, 0) as bookings_this_week,
    COALESCE(ast.bookings_this_month, 0) as bookings_this_month,
    COALESCE(ast.tickets_sold_today, 0) as tickets_sold_today,
    COALESCE(ast.tickets_sold_this_week, 0) as tickets_sold_this_week,
    
    -- Revenue (JSON objects by currency)
    COALESCE(ast.total_revenue, '{}'::jsonb) as total_revenue,
    COALESCE(ast.revenue_this_week, '{}'::jsonb) as revenue_this_week,
    COALESCE(ast.revenue_this_month, '{}'::jsonb) as revenue_this_month,
    
    -- Account info
    o.created_at as member_since,
    o.is_active
    
FROM public.organizers o
LEFT JOIN event_stats es ON es.organizer_id = o.id
LEFT JOIN aggregated_stats ast ON ast.organizer_id = o.id;

-- Add comment
COMMENT ON VIEW public.organizer_dashboard IS 'Dashboard statistics for organizers including events, bookings, and revenue';

-- =====================================================
-- 3. UPCOMING EVENTS VIEW
-- =====================================================
-- Simple view for homepage showing upcoming events
CREATE VIEW public.upcoming_events AS
SELECT 
    e.id,
    e.title,
    e.slug,
    e.description,
    e.event_date,
    e.end_date,
    e.venue_name,
    e.city as location,
    e.cover_image_url as image_url,
    e.min_price,
    e.max_price,
    e.currency,
    e.capacity,
    e.tickets_sold,
    e.capacity - e.tickets_sold as available_tickets,
    
    -- Availability status
    CASE 
        WHEN e.tickets_sold >= e.capacity THEN 'sold_out'
        WHEN (e.capacity - e.tickets_sold) <= 10 THEN 'almost_sold_out'
        WHEN ((e.capacity - e.tickets_sold)::DECIMAL / e.capacity) <= 0.2 THEN 'selling_fast'
        ELSE 'available'
    END as status,
    
    -- Is it happening soon?
    CASE 
        WHEN e.event_date <= CURRENT_DATE + INTERVAL '7 days' THEN true
        ELSE false
    END as is_soon,
    
    -- Is it featured?
    e.featured as is_featured,
    
    -- Category info
    ec.id as category_id,
    ec.name as category_name,
    ec.slug as category_slug,
    ecp.name as parent_category_name,
    ecp.slug as parent_category_slug,
    
    -- Organizer info
    o.id as organizer_id,
    o.business_name as organizer_name,
    o.is_active as organizer_active,
    o.rating as organizer_rating
    
FROM public.events e
INNER JOIN public.event_categories ec ON e.category_id = ec.id
LEFT JOIN public.event_categories ecp ON ec.parent_id = ecp.id
INNER JOIN public.organizers o ON e.organizer_id = o.id
WHERE 
    e.status = 'published'
    AND e.event_date >= CURRENT_DATE
    AND o.is_active = true
ORDER BY 
    e.featured DESC,
    e.event_date ASC;

-- Add comment
COMMENT ON VIEW public.upcoming_events IS 'All upcoming published events for homepage and listings';

-- =====================================================
-- 4. GRANT PERMISSIONS
-- =====================================================
-- Views inherit RLS from underlying tables, but we can grant explicit permissions

-- Everyone can see event availability
GRANT SELECT ON public.event_availability TO anon, authenticated;

-- Only authenticated users can see upcoming events
GRANT SELECT ON public.upcoming_events TO authenticated;

-- Organizer dashboard is restricted via RLS on underlying tables
GRANT SELECT ON public.organizer_dashboard TO authenticated;

-- =====================================================
-- 5. CREATE INDEXES ON BASE TABLES FOR VIEW PERFORMANCE
-- =====================================================
-- These indexes help the views perform better

-- For event_availability view
CREATE INDEX IF NOT EXISTS idx_events_date_status 
    ON public.events(event_date, status);

-- For organizer_dashboard view  
CREATE INDEX IF NOT EXISTS idx_bookings_organizer_dates 
    ON public.bookings(created_at DESC, status);

-- For upcoming_events view
CREATE INDEX IF NOT EXISTS idx_events_upcoming 
    ON public.events(event_date, status) 
    WHERE status = 'published';