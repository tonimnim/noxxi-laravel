-- Create transactions table for financial tracking
CREATE TABLE IF NOT EXISTS public.transactions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    type TEXT CHECK (type IN ('ticket_sale', 'refund', 'payout', 'commission')) NOT NULL,
    booking_id UUID REFERENCES public.bookings(id),
    organizer_id UUID REFERENCES public.organizers(id),
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    commission_amount DECIMAL(10,2),
    net_amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    status TEXT CHECK (status IN ('pending', 'completed', 'failed')) DEFAULT 'pending',
    processed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    metadata JSONB DEFAULT '{}'::jsonb,
    -- Ensure booking_id is required for ticket_sale and refund
    CONSTRAINT valid_booking_reference CHECK (
        (type IN ('ticket_sale', 'refund') AND booking_id IS NOT NULL) OR
        (type IN ('payout', 'commission'))
    ),
    -- Ensure organizer_id is required for payout
    CONSTRAINT valid_organizer_reference CHECK (
        (type = 'payout' AND organizer_id IS NOT NULL) OR
        (type != 'payout')
    )
);

-- Create indexes for performance
CREATE INDEX idx_transactions_booking_id ON public.transactions(booking_id) WHERE booking_id IS NOT NULL;
CREATE INDEX idx_transactions_organizer_id ON public.transactions(organizer_id) WHERE organizer_id IS NOT NULL;
CREATE INDEX idx_transactions_type ON public.transactions(type);
CREATE INDEX idx_transactions_status ON public.transactions(status);
CREATE INDEX idx_transactions_created_at ON public.transactions(created_at DESC);
CREATE INDEX idx_transactions_payment_reference ON public.transactions(payment_reference) WHERE payment_reference IS NOT NULL;

-- Enable Row Level Security
ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;

-- Create policies for transactions table
-- Users can view their own transactions
CREATE POLICY "Users can view own transactions" ON public.transactions
    FOR SELECT USING (
        -- User's own bookings
        booking_id IN (SELECT id FROM public.bookings WHERE user_id = auth.uid()) OR
        -- Organizer's transactions
        organizer_id IN (SELECT id FROM public.organizers WHERE user_id = auth.uid()) OR
        -- Admin access
        EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role = 'admin')
    );

-- System can create transactions
CREATE POLICY "System can create transactions" ON public.transactions
    FOR INSERT WITH CHECK (true);

-- System can update transactions
CREATE POLICY "System can update transactions" ON public.transactions
    FOR UPDATE USING (true);

-- Enable realtime for transaction updates
ALTER PUBLICATION supabase_realtime ADD TABLE public.transactions;

-- Function to create transaction after successful payment
CREATE OR REPLACE FUNCTION public.create_ticket_sale_transaction(
    p_order_id UUID,
    p_payment_reference VARCHAR(100),
    p_payment_method VARCHAR(50)
)
RETURNS UUID AS $$
DECLARE
    v_order RECORD;
    v_organizer_id UUID;
    v_transaction_id UUID;
    v_commission DECIMAL(10,2);
    v_net_amount DECIMAL(10,2);
BEGIN
    -- Get order details
    SELECT * INTO v_order FROM public.orders WHERE id = p_order_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Order not found';
    END IF;
    
    -- Get organizer_id from event
    SELECT e.organizer_id INTO v_organizer_id
    FROM public.events e
    WHERE e.id = v_order.event_id;
    
    -- Calculate commission (10% of subtotal)
    v_commission := ROUND(v_order.subtotal * 0.10, 2);
    v_net_amount := v_order.total_amount - v_commission;
    
    -- Create transaction record
    INSERT INTO public.transactions (
        type,
        order_id,
        organizer_id,
        amount,
        currency,
        commission_amount,
        net_amount,
        payment_method,
        payment_reference,
        status,
        processed_at,
        metadata
    ) VALUES (
        'ticket_sale',
        p_order_id,
        v_organizer_id,
        v_order.total_amount,
        v_order.currency,
        v_commission,
        v_net_amount,
        p_payment_method,
        p_payment_reference,
        'completed',
        NOW(),
        jsonb_build_object(
            'event_id', v_order.event_id,
            'ticket_count', v_order.ticket_count,
            'user_id', v_order.user_id
        )
    ) RETURNING id INTO v_transaction_id;
    
    -- Update order status
    UPDATE public.orders
    SET 
        status = 'paid',
        paid_at = NOW(),
        payment_reference = p_payment_reference,
        payment_method = p_payment_method
    WHERE id = p_order_id;
    
    -- Create tickets
    PERFORM public.create_tickets_for_order(p_order_id);
    
    -- Update organizer revenue
    UPDATE public.organizers
    SET total_revenue = total_revenue + v_net_amount
    WHERE id = v_organizer_id;
    
    RETURN v_transaction_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to process refund
CREATE OR REPLACE FUNCTION public.create_refund_transaction(
    p_order_id UUID,
    p_amount DECIMAL(10,2),
    p_reason TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
    v_order RECORD;
    v_original_transaction RECORD;
    v_transaction_id UUID;
    v_tickets_to_cancel UUID[];
BEGIN
    -- Get order details
    SELECT * INTO v_order FROM public.orders WHERE id = p_order_id;
    
    IF NOT FOUND OR v_order.status != 'paid' THEN
        RAISE EXCEPTION 'Order not found or not paid';
    END IF;
    
    -- Get original transaction
    SELECT * INTO v_original_transaction
    FROM public.transactions
    WHERE order_id = p_order_id AND type = 'ticket_sale';
    
    -- Validate refund amount
    IF p_amount > v_order.total_amount THEN
        RAISE EXCEPTION 'Refund amount exceeds order total';
    END IF;
    
    -- Create refund transaction
    INSERT INTO public.transactions (
        type,
        order_id,
        organizer_id,
        amount,
        currency,
        commission_amount,
        net_amount,
        payment_method,
        payment_reference,
        status,
        metadata
    ) VALUES (
        'refund',
        p_order_id,
        v_original_transaction.organizer_id,
        -p_amount, -- Negative amount for refund
        v_order.currency,
        -ROUND(p_amount * 0.10, 2), -- Reverse commission
        -ROUND(p_amount * 0.90, 2), -- Reverse net amount
        v_order.payment_method,
        'REF-' || v_order.order_number,
        'pending',
        jsonb_build_object(
            'reason', p_reason,
            'original_transaction_id', v_original_transaction.id,
            'partial_refund', p_amount < v_order.total_amount
        )
    ) RETURNING id INTO v_transaction_id;
    
    -- Cancel tickets if full refund
    IF p_amount = v_order.total_amount THEN
        UPDATE public.tickets
        SET status = 'cancelled'
        WHERE order_id = p_order_id AND status = 'valid';
        
        -- Update order status
        UPDATE public.orders
        SET status = 'refunded'
        WHERE id = p_order_id;
    END IF;
    
    RETURN v_transaction_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to calculate daily revenue
CREATE OR REPLACE FUNCTION public.calculate_daily_revenue(
    p_date DATE DEFAULT CURRENT_DATE
)
RETURNS TABLE (
    total_sales DECIMAL,
    total_commission DECIMAL,
    total_net_revenue DECIMAL,
    transaction_count INTEGER,
    refund_amount DECIMAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'ticket_sale' THEN amount ELSE 0 END), 0) as total_sales,
        COALESCE(SUM(CASE WHEN type = 'ticket_sale' THEN commission_amount ELSE 0 END), 0) as total_commission,
        COALESCE(SUM(CASE WHEN type = 'ticket_sale' THEN net_amount ELSE 0 END), 0) as total_net_revenue,
        COUNT(CASE WHEN type = 'ticket_sale' THEN 1 END)::INTEGER as transaction_count,
        COALESCE(ABS(SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END)), 0) as refund_amount
    FROM public.transactions
    WHERE DATE(created_at) = p_date
    AND status = 'completed';
END;
$$ LANGUAGE plpgsql;

-- Function to get organizer balance
CREATE OR REPLACE FUNCTION public.get_organizer_balance(
    p_organizer_id UUID
)
RETURNS TABLE (
    total_sales DECIMAL,
    total_payouts DECIMAL,
    pending_balance DECIMAL,
    available_balance DECIMAL
) AS $$
BEGIN
    RETURN QUERY
    WITH sales AS (
        SELECT COALESCE(SUM(net_amount), 0) as total
        FROM public.transactions
        WHERE organizer_id = p_organizer_id
        AND type = 'ticket_sale'
        AND status = 'completed'
    ),
    payouts AS (
        SELECT COALESCE(SUM(amount), 0) as total
        FROM public.transactions
        WHERE organizer_id = p_organizer_id
        AND type = 'payout'
        AND status = 'completed'
    ),
    refunds AS (
        SELECT COALESCE(SUM(net_amount), 0) as total
        FROM public.transactions
        WHERE organizer_id = p_organizer_id
        AND type = 'refund'
        AND status = 'completed'
    )
    SELECT 
        sales.total as total_sales,
        payouts.total as total_payouts,
        0::DECIMAL as pending_balance, -- Will be calculated based on payout schedule
        (sales.total + refunds.total - payouts.total) as available_balance
    FROM sales, payouts, refunds;
END;
$$ LANGUAGE plpgsql;