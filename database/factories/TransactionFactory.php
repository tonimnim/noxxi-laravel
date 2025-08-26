<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);
        $commissionAmount = round($amount * 0.10, 2); // 10% commission
        $gatewayFee = round($amount * 0.015, 2); // 1.5% gateway fee
        $netAmount = $amount - $commissionAmount - $gatewayFee;

        return [
            'type' => Transaction::TYPE_TICKET_SALE,
            'booking_id' => Booking::factory(),
            'organizer_id' => Organizer::factory(),
            'user_id' => User::factory(),
            'amount' => $amount,
            'currency' => 'KES',
            'commission_amount' => $commissionAmount,
            'platform_commission' => $commissionAmount,
            'payment_processing_fee' => $gatewayFee,
            'paystack_fee' => $gatewayFee,
            'net_amount' => $netAmount,
            'payment_gateway' => 'paystack',
            'payment_method' => 'mpesa',
            'payment_reference' => 'TRX-'.strtoupper(Str::random(10)),
            'gateway_reference' => 'PAY-'.strtoupper(Str::random(12)),
            'status' => Transaction::STATUS_PENDING,
            'failure_reason' => null,
            'processed_at' => null,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_FAILED,
            'failure_reason' => 'Payment declined by gateway',
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction is a refund.
     */
    public function refund(): static
    {
        return $this->state(function (array $attributes) {
            $amount = abs($attributes['amount']);
            $commissionAmount = abs($attributes['commission_amount']);
            $gatewayFee = 0; // Gateway keeps fee on refunds
            $netAmount = $amount - $commissionAmount;

            return [
                'type' => Transaction::TYPE_REFUND,
                'amount' => -$amount,
                'commission_amount' => -$commissionAmount,
                'platform_commission' => -$commissionAmount,
                'payment_processing_fee' => $gatewayFee,
                'paystack_fee' => $gatewayFee,
                'net_amount' => -$netAmount,
                'payment_reference' => 'REFUND-'.strtoupper(Str::random(8)),
            ];
        });
    }

    /**
     * Indicate that the transaction is a payout.
     */
    public function payout(): static
    {
        return $this->state(function (array $attributes) {
            $amount = abs($attributes['amount']);

            return [
                'type' => Transaction::TYPE_PAYOUT,
                'amount' => -$amount,
                'commission_amount' => 0,
                'platform_commission' => 0,
                'payment_processing_fee' => 0,
                'paystack_fee' => 0,
                'net_amount' => -$amount,
                'payment_reference' => 'PAYOUT-'.strtoupper(Str::random(8)),
            ];
        });
    }

    /**
     * Set specific currency for the transaction.
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }

    /**
     * Set specific payment method.
     */
    public function withPaymentMethod(string $method): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => $method,
        ]);
    }
}
