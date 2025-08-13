-- =====================================================
-- PAYOUTS TABLE
-- =====================================================
-- Track payments to organizers for their ticket sales

-- Drop existing objects if they exist
DROP TABLE IF EXISTS public.payouts CASCADE;

-- =====================================================
-- 1. PAYOUTS TABLE
-- =====================================================
CREATE TABLE public.payouts (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    organizer_id UUID REFERENCES public.organizers(id) ON DELETE CASCADE NOT NULL,
    
    -- Payout details
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    
    -- Period covered
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Status
    status TEXT DEFAULT 'pending' CHECK (status IN (
        'pending',
        'processing',
        'paid',
        'failed'
    )),
    
    -- Payment details
    payment_method TEXT,
    payment_reference VARCHAR(100),
    
    -- Timestamps
    paid_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_payouts_organizer ON public.payouts(organizer_id);
CREATE INDEX idx_payouts_status ON public.payouts(status);
CREATE INDEX idx_payouts_period ON public.payouts(period_start, period_end);

-- =====================================================
-- 2. ENABLE ROW LEVEL SECURITY
-- =====================================================
ALTER TABLE public.payouts ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 3. RLS POLICIES
-- =====================================================

-- Organizers can view their own payouts
CREATE POLICY "Organizers view own payouts" 
    ON public.payouts FOR SELECT 
    USING (
        organizer_id IN (
            SELECT id FROM public.organizers 
            WHERE user_id = auth.uid()
        )
    );

-- Admins have full access
CREATE POLICY "Admins manage payouts" 
    ON public.payouts FOR ALL 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() 
            AND role = 'admin'
        )
    );