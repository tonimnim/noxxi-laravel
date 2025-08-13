-- =====================================================
-- REFUND REQUESTS TABLE
-- =====================================================
-- Simple refund tracking for customer bookings

-- Drop existing objects if they exist
DROP TABLE IF EXISTS public.refund_requests CASCADE;

-- =====================================================
-- 1. REFUND REQUESTS TABLE
-- =====================================================
CREATE TABLE public.refund_requests (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    booking_id UUID REFERENCES public.bookings(id) ON DELETE CASCADE NOT NULL,
    
    -- Refund details
    reason TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    
    -- Status
    status TEXT DEFAULT 'pending' CHECK (status IN (
        'pending',
        'approved',
        'rejected',
        'processed'
    )),
    
    -- Processing details
    processed_by UUID REFERENCES public.profiles(id),
    processing_notes TEXT,
    
    -- Timestamps
    processed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_refunds_booking ON public.refund_requests(booking_id);
CREATE INDEX idx_refunds_status ON public.refund_requests(status);
CREATE INDEX idx_refunds_created ON public.refund_requests(created_at DESC);

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.refund_requests ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Users can view their own refund requests
CREATE POLICY "Users view own refunds" 
    ON public.refund_requests FOR SELECT 
    USING (
        booking_id IN (
            SELECT id FROM public.bookings 
            WHERE user_id = auth.uid()
        )
    );

-- Users can create refund requests for their bookings
CREATE POLICY "Users create refund requests" 
    ON public.refund_requests FOR INSERT 
    WITH CHECK (
        booking_id IN (
            SELECT id FROM public.bookings 
            WHERE user_id = auth.uid()
        )
    );

-- Admins and organizers can manage refunds
CREATE POLICY "Admins manage refunds" 
    ON public.refund_requests FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'admin'
        )
        OR
        booking_id IN (
            SELECT b.id FROM public.bookings b
            JOIN public.events e ON b.event_id = e.id
            JOIN public.organizers o ON e.organizer_id = o.id
            WHERE o.user_id = auth.uid()
        )
    );