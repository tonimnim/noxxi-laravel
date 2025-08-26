<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialCalculationService $financialService;

    protected User $organizer;

    protected Organizer $organizerModel;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financialService = app(FinancialCalculationService::class);

        // Create organizer user with 15% commission rate
        $this->organizer = User::factory()->create(['role' => 'organizer']);
        $this->organizerModel = Organizer::factory()->create([
            'user_id' => $this->organizer->id,
            'commission_rate' => 15.0, // 15% organizer commission
        ]);

        // Create event with specific commission settings
        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizerModel->id,
            'platform_fee' => null, // Will test different scenarios
            'commission_rate' => null,
            'commission_type' => 'percentage',
        ]);
    }

    /**
     * Test commission calculation with event platform_fee (highest priority)
     */
    public function test_platform_fee_overrides_all_other_commission_settings()
    {
        // Set platform_fee on event
        $this->event->update(['platform_fee' => 5.0]); // 5% platform fee

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $commission = $this->financialService->calculatePlatformCommission($booking);

        $this->assertEquals(50.0, $commission['amount']); // 5% of 1000
        $this->assertEquals(5.0, $commission['rate']);
        $this->assertEquals('percentage', $commission['type']);
        $this->assertEquals('event_platform_fee', $commission['source']);
    }

    /**
     * Test commission calculation with event commission_rate (second priority)
     */
    public function test_event_commission_rate_overrides_organizer_commission()
    {
        // Set commission_rate on event (no platform_fee)
        $this->event->update([
            'platform_fee' => null,
            'commission_rate' => 8.0,
            'commission_type' => 'percentage',
        ]);

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $commission = $this->financialService->calculatePlatformCommission($booking);

        $this->assertEquals(80.0, $commission['amount']); // 8% of 1000
        $this->assertEquals(8.0, $commission['rate']);
        $this->assertEquals('percentage', $commission['type']);
        $this->assertEquals('event_commission', $commission['source']);
    }

    /**
     * Test fixed commission type
     */
    public function test_fixed_commission_type()
    {
        // Set fixed commission on event
        $this->event->update([
            'platform_fee' => null,
            'commission_rate' => 100.0, // Fixed 100 per booking
            'commission_type' => 'fixed',
        ]);

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
        ]);

        $commission = $this->financialService->calculatePlatformCommission($booking);

        $this->assertEquals(100.0, $commission['amount']); // Fixed 100
        $this->assertEquals(100.0, $commission['rate']);
        $this->assertEquals('fixed', $commission['type']);
        $this->assertEquals('event_commission', $commission['source']);
    }

    /**
     * Test organizer commission rate (third priority)
     */
    public function test_organizer_commission_rate_used_when_no_event_settings()
    {
        // No platform_fee or commission_rate on event
        $this->event->update([
            'platform_fee' => null,
            'commission_rate' => null,
        ]);

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $commission = $this->financialService->calculatePlatformCommission($booking);

        $this->assertEquals(150.0, $commission['amount']); // 15% of 1000
        $this->assertEquals(15.0, $commission['rate']);
        $this->assertEquals('percentage', $commission['type']);
        $this->assertEquals('organizer_commission', $commission['source']);
    }

    /**
     * Test default commission when nothing is set
     */
    public function test_default_commission_when_no_settings()
    {
        // Create organizer without commission rate
        $newOrganizer = Organizer::factory()->create([
            'commission_rate' => null,
        ]);

        $event = Event::factory()->create([
            'organizer_id' => $newOrganizer->id,
            'platform_fee' => null,
            'commission_rate' => null,
        ]);

        $booking = Booking::factory()->create([
            'event_id' => $event->id,
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $commission = $this->financialService->calculatePlatformCommission($booking);

        $this->assertEquals(100.0, $commission['amount']); // Default 10% of 1000
        $this->assertEquals(10.0, $commission['rate']);
        $this->assertEquals('percentage', $commission['type']);
        $this->assertEquals('default', $commission['source']);
    }

    /**
     * Test gateway fee calculation
     */
    public function test_gateway_fee_calculation()
    {
        // Test M-Pesa fee (1.5%)
        $mpesaFee = $this->financialService->calculateGatewayFee(1000.00, 'paystack', 'mpesa');
        $this->assertEquals(15.0, $mpesaFee);

        // Test card fee (2.9%)
        $cardFee = $this->financialService->calculateGatewayFee(1000.00, 'paystack', 'card');
        $this->assertEquals(29.0, $cardFee);

        // Test Apple Pay fee (2.9%)
        $appleFee = $this->financialService->calculateGatewayFee(1000.00, 'paystack', 'apple_pay');
        $this->assertEquals(29.0, $appleFee);

        // Test bank transfer fee (1.5%)
        $bankFee = $this->financialService->calculateGatewayFee(1000.00, 'paystack', 'bank_transfer');
        $this->assertEquals(15.0, $bankFee);
    }

    /**
     * Test organizer net amount calculation
     */
    public function test_organizer_net_amount_calculation()
    {
        $this->event->update(['platform_fee' => 10.0]); // 10% platform fee

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $financials = $this->financialService->calculateOrganizerNetAmount(
            $booking,
            'paystack',
            'mpesa'
        );

        $this->assertEquals(1000.00, $financials['gross_amount']);
        $this->assertEquals(15.00, $financials['gateway_fee']); // 1.5% for M-Pesa
        $this->assertEquals(100.00, $financials['platform_commission']); // 10% of 1000
        $this->assertEquals(885.00, $financials['net_amount']); // 1000 - 15 - 100
        $this->assertEquals('event_platform_fee', $financials['commission_source']);
    }

    /**
     * Test financial transaction creation
     */
    public function test_financial_transaction_creation()
    {
        $this->event->update(['commission_rate' => 12.0]); // 12% commission

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
            'currency' => 'KES',
        ]);

        $transaction = $this->financialService->createFinancialTransaction(
            $booking,
            'paystack',
            'card',
            ['test_meta' => 'value']
        );

        $this->assertEquals(Transaction::TYPE_TICKET_SALE, $transaction->type);
        $this->assertEquals(2000.00, $transaction->amount);
        $this->assertEquals('KES', $transaction->currency);
        $this->assertEquals(240.00, $transaction->commission_amount); // 12% of 2000
        $this->assertEquals(58.00, $transaction->payment_processing_fee); // 2.9% of 2000
        $this->assertEquals(1702.00, $transaction->net_amount); // 2000 - 240 - 58
        $this->assertEquals('paystack', $transaction->payment_gateway);
        $this->assertEquals('card', $transaction->payment_method);
        $this->assertEquals(Transaction::STATUS_PENDING, $transaction->status);

        // Check metadata
        $metadata = $transaction->metadata;
        $this->assertEquals('value', $metadata['test_meta']);
        $this->assertEquals('event_commission', $metadata['commission_source']);
        $this->assertArrayHasKey('financial_breakdown', $metadata);
    }

    /**
     * Test refund amount calculations
     */
    public function test_refund_amount_calculations()
    {
        $originalTransaction = Transaction::factory()->create([
            'amount' => 1000.00,
            'commission_amount' => 100.00, // 10% commission
            'payment_processing_fee' => 29.00, // 2.9% gateway fee
            'net_amount' => 871.00,
        ]);

        // Full refund
        $fullRefund = $this->financialService->calculateRefundAmounts($originalTransaction, 1000.00);
        $this->assertEquals(1000.00, $fullRefund['refund_amount']);
        $this->assertEquals(100.00, $fullRefund['commission_refund']); // Commission returned
        $this->assertEquals(0.00, $fullRefund['gateway_fee_refund']); // Gateway keeps fee
        $this->assertEquals(900.00, $fullRefund['net_refund']); // 1000 - 100
        $this->assertFalse($fullRefund['is_partial']);

        // Partial refund (50%)
        $partialRefund = $this->financialService->calculateRefundAmounts($originalTransaction, 500.00);
        $this->assertEquals(500.00, $partialRefund['refund_amount']);
        $this->assertEquals(50.00, $partialRefund['commission_refund']); // 50% of commission
        $this->assertEquals(0.00, $partialRefund['gateway_fee_refund']); // Gateway keeps fee
        $this->assertEquals(450.00, $partialRefund['net_refund']); // 500 - 50
        $this->assertTrue($partialRefund['is_partial']);
    }

    /**
     * Test refund transaction creation
     */
    public function test_refund_transaction_creation()
    {
        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
        ]);

        $originalTransaction = Transaction::factory()->create([
            'booking_id' => $booking->id,
            'organizer_id' => $this->organizerModel->id,
            'user_id' => $booking->user_id,
            'amount' => 1000.00,
            'commission_amount' => 100.00,
            'payment_processing_fee' => 15.00,
            'net_amount' => 885.00,
            'currency' => 'KES',
            'payment_gateway' => 'paystack',
            'payment_method' => 'mpesa',
        ]);

        $refundTransaction = $this->financialService->createRefundTransaction(
            $originalTransaction,
            500.00, // Partial refund
            'Customer request'
        );

        $this->assertEquals(Transaction::TYPE_REFUND, $refundTransaction->type);
        $this->assertEquals(-500.00, $refundTransaction->amount);
        $this->assertEquals(-50.00, $refundTransaction->commission_amount);
        $this->assertEquals(0.00, $refundTransaction->payment_processing_fee);
        $this->assertEquals(-450.00, $refundTransaction->net_amount);
        $this->assertEquals('REFUND-'.$booking->booking_reference, $refundTransaction->payment_reference);

        // Check metadata
        $metadata = $refundTransaction->metadata;
        $this->assertEquals($originalTransaction->id, $metadata['original_transaction_id']);
        $this->assertEquals('Customer request', $metadata['refund_reason']);
        $this->assertTrue($metadata['partial_refund']);
    }

    /**
     * Test booking financial summary
     */
    public function test_booking_financial_summary()
    {
        $this->event->update(['commission_rate' => 20.0]); // 20% commission

        $booking = Booking::factory()->create([
            'event_id' => $this->event->id,
            'booking_reference' => 'TEST-REF',
            'currency' => 'NGN',
            'ticket_quantity' => 5,
            'subtotal' => 5000.00,
            'service_fee' => 250.00,
            'total_amount' => 5250.00,
            'payment_status' => 'paid',
        ]);

        $transaction = Transaction::factory()->create([
            'booking_id' => $booking->id,
            'type' => Transaction::TYPE_TICKET_SALE,
            'payment_processing_fee' => 78.75, // 1.5% of 5250
        ]);

        $summary = $this->financialService->getBookingFinancialSummary($booking);

        $this->assertEquals('TEST-REF', $summary['booking_reference']);
        $this->assertEquals('NGN', $summary['currency']);
        $this->assertEquals(5, $summary['ticket_quantity']);
        $this->assertEquals(5000.00, $summary['subtotal']);
        $this->assertEquals(250.00, $summary['service_fee']);
        $this->assertEquals(5250.00, $summary['total_amount']);
        $this->assertEquals(1000.00, $summary['commission']['amount']); // 20% of 5000
        $this->assertEquals(20.0, $summary['commission']['rate']);
        $this->assertEquals('event_commission', $summary['commission']['source']);
        $this->assertEquals(78.75, $summary['gateway_fee']);
        $this->assertEquals(4171.25, $summary['organizer_net']); // 5250 - 1000 - 78.75
        $this->assertEquals('paid', $summary['payment_status']);
        $this->assertEquals($transaction->id, $summary['transaction_id']);
    }

    /**
     * Test organizer financial reconciliation
     */
    public function test_organizer_financial_reconciliation()
    {
        // Create multiple transactions
        Transaction::factory()->count(3)->create([
            'organizer_id' => $this->organizerModel->id,
            'type' => Transaction::TYPE_TICKET_SALE,
            'status' => Transaction::STATUS_COMPLETED,
            'amount' => 1000.00,
            'commission_amount' => 100.00,
            'payment_processing_fee' => 29.00,
            'net_amount' => 871.00,
        ]);

        // Create a refund
        Transaction::factory()->create([
            'organizer_id' => $this->organizerModel->id,
            'type' => Transaction::TYPE_REFUND,
            'status' => Transaction::STATUS_COMPLETED,
            'amount' => -500.00,
            'commission_amount' => -50.00,
            'payment_processing_fee' => 0.00,
            'net_amount' => -450.00,
        ]);

        $reconciliation = $this->financialService->reconcileOrganizerFinancials(
            $this->organizerModel
        );

        $this->assertEquals(3000.00, $reconciliation['summary']['gross_sales']);
        $this->assertEquals(500.00, $reconciliation['summary']['refunds']);
        $this->assertEquals(2500.00, $reconciliation['summary']['net_sales']);
        $this->assertEquals(250.00, $reconciliation['summary']['platform_commission']); // 300 - 50
        $this->assertEquals(87.00, $reconciliation['summary']['gateway_fees']); // 3 * 29
        $this->assertEquals(2163.00, $reconciliation['summary']['organizer_net_revenue']); // (3*871) - 450
        $this->assertEquals(4, $reconciliation['transaction_count']);
    }
}
