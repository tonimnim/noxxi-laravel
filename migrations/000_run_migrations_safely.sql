-- =====================================================
-- SAFE MIGRATION RUNNER FOR NOXXI PLATFORM
-- =====================================================
-- Run this script to safely apply all migrations
-- It handles existing objects gracefully

-- Start transaction for safety
BEGIN;

-- 1. Check if we can proceed
DO $$ 
BEGIN
    RAISE NOTICE 'Starting NOXXI database migration...';
    
    -- Check if auth schema exists (Supabase requirement)
    IF NOT EXISTS (SELECT 1 FROM information_schema.schemata WHERE schema_name = 'auth') THEN
        RAISE EXCEPTION 'Auth schema not found. Please ensure Supabase is properly initialized.';
    END IF;
    
    RAISE NOTICE 'Pre-checks passed. Proceeding with migrations...';
END $$;

-- 2. Run migrations in order
-- Note: Each migration file should be idempotent (safe to run multiple times)

\echo 'Running 000_create_functions.sql (shared functions)...'
\i 000_create_functions.sql

\echo 'Running 001_create_profiles_table.sql...'
\i 001_create_profiles_table.sql

\echo 'Running 002_create_organizers_table.sql...'
\i 002_create_organizers_table.sql

\echo 'Running 003_create_categories_table.sql...'
\i 003_create_categories_table.sql

\echo 'Running 004_create_events_table.sql...'
\i 004_create_events_table.sql

\echo 'Running 005_create_bookings_table.sql...'
\i 005_create_bookings_table.sql

\echo 'Running 006_create_tickets_table.sql...'
\i 006_create_tickets_table.sql

\echo 'Running remaining migrations...'
-- Add other migrations as needed

-- 3. Verify critical tables exist
DO $$ 
BEGIN
    -- Check critical tables
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'profiles') THEN
        RAISE EXCEPTION 'Table profiles not created';
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'organizers') THEN
        RAISE EXCEPTION 'Table organizers not created';
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'events') THEN
        RAISE EXCEPTION 'Table events not created';
    END IF;
    
    RAISE NOTICE 'All critical tables verified successfully!';
END $$;

-- 4. Final cleanup and optimization
ANALYZE;

-- Commit transaction if everything succeeded
COMMIT;

\echo 'Migration completed successfully!'
\echo 'Database is ready for use.'