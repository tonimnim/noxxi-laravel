<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackService
{
    /**
     * Paystack API base URL
     */
    private ?string $baseUrl;

    /**
     * Paystack secret key
     */
    private ?string $secretKey;

    /**
     * Paystack public key
     */
    private ?string $publicKey;

    /**
     * Currency to smallest unit multipliers
     */
    private const CURRENCY_MULTIPLIERS = [
        'NGN' => 100,  // Naira to kobo
        'GHS' => 100,  // Cedi to pesewas
        'ZAR' => 100,  // Rand to cents
        'USD' => 100,  // Dollar to cents
        'KES' => 100,  // Shilling to cents
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');

        // Only throw exception if not in testing environment
        if (app()->environment() !== 'testing' && (! $this->secretKey || ! $this->publicKey)) {
            throw new \Exception('Paystack API keys not configured');
        }
    }

    /**
     * Initialize a transaction with Paystack
     *
     * @throws \Exception
     */
    public function initializeTransaction(array $data): array
    {
        // Validate required fields
        if (! isset($data['email']) || ! isset($data['amount']) || ! isset($data['reference'])) {
            throw new \InvalidArgumentException('Missing required fields: email, amount, reference');
        }

        // Convert amount to smallest currency unit
        $currency = $data['currency'] ?? 'NGN';
        $amountInSmallestUnit = $this->convertToSmallestUnit($data['amount'], $currency);

        // Prepare request data
        $requestData = [
            'email' => $data['email'],
            'amount' => $amountInSmallestUnit,
            'reference' => $data['reference'],
            'currency' => $currency,
            'callback_url' => $data['callback_url'] ?? config('services.paystack.callback_url'),
            'metadata' => $data['metadata'] ?? [],
            'channels' => $data['channels'] ?? ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
        ];

        try {
            $httpClient = Http::withToken($this->secretKey)
                ->timeout(30);
                
            // Disable SSL verification for local development only
            if (app()->environment('local')) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false,
                ]);
            }
            
            $response = $httpClient->post("{$this->baseUrl}/transaction/initialize", $requestData);

            if (! $response->successful()) {
                Log::error('Paystack transaction initialization failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new \Exception('Failed to initialize Paystack transaction: '.($response->json()['message'] ?? 'Unknown error'));
            }

            $responseData = $response->json();

            if (! isset($responseData['status']) || $responseData['status'] !== true) {
                throw new \Exception('Paystack initialization failed: '.($responseData['message'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'authorization_url' => $responseData['data']['authorization_url'],
                'access_code' => $responseData['data']['access_code'],
                'reference' => $responseData['data']['reference'],
            ];

        } catch (\Exception $e) {
            Log::error('Paystack initialization error', [
                'error' => $e->getMessage(),
                'reference' => $data['reference'],
            ]);
            throw $e;
        }
    }

    /**
     * Verify a transaction with Paystack
     *
     * @throws \Exception
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $httpClient = Http::withToken($this->secretKey)
                ->timeout(30);
                
            // Disable SSL verification for local development only
            if (app()->environment('local')) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false,
                ]);
            }
            
            $response = $httpClient->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if (! $response->successful()) {
                Log::error('Paystack transaction verification failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'reference' => $reference,
                ]);
                throw new \Exception('Failed to verify Paystack transaction');
            }

            $responseData = $response->json();

            if (! isset($responseData['status']) || $responseData['status'] !== true) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Verification failed',
                ];
            }

            $transactionData = $responseData['data'];

            // Convert amount back from smallest unit
            $amount = $this->convertFromSmallestUnit(
                $transactionData['amount'],
                $transactionData['currency']
            );

            return [
                'success' => true,
                'status' => $transactionData['status'],
                'reference' => $transactionData['reference'],
                'amount' => $amount,
                'currency' => $transactionData['currency'],
                'paid_at' => $transactionData['paid_at'] ?? null,
                'channel' => $transactionData['channel'] ?? null,
                'customer' => [
                    'email' => $transactionData['customer']['email'] ?? null,
                    'customer_code' => $transactionData['customer']['customer_code'] ?? null,
                ],
                'authorization' => $transactionData['authorization'] ?? null,
                'metadata' => $transactionData['metadata'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Paystack verification error', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);
            throw $e;
        }
    }

    /**
     * Validate webhook signature from Paystack
     *
     * @param  string  $payload  The raw request body
     * @param  string  $signature  The x-paystack-signature header value
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);

        // Use hash_equals for timing-safe comparison
        return hash_equals($computedSignature, $signature);
    }

    /**
     * Process webhook event from Paystack
     */
    public function processWebhookEvent(array $payload): array
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('Processing Paystack webhook event', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        switch ($event) {
            case 'charge.success':
                return [
                    'event' => 'payment_success',
                    'reference' => $data['reference'],
                    'amount' => $this->convertFromSmallestUnit($data['amount'], $data['currency']),
                    'currency' => $data['currency'],
                    'channel' => $data['channel'] ?? null,
                    'paid_at' => $data['paid_at'] ?? null,
                    'customer' => $data['customer'] ?? [],
                    'authorization' => $data['authorization'] ?? [],
                    'metadata' => $data['metadata'] ?? [],
                ];

            case 'charge.failed':
                return [
                    'event' => 'payment_failed',
                    'reference' => $data['reference'],
                    'message' => $data['message'] ?? 'Payment failed',
                ];

            case 'refund.processed':
                return [
                    'event' => 'refund_processed',
                    'reference' => $data['transaction_reference'] ?? null,
                    'refund_reference' => $data['reference'] ?? null,
                    'amount' => $this->convertFromSmallestUnit($data['amount'], $data['currency']),
                    'currency' => $data['currency'],
                ];

            default:
                return [
                    'event' => $event,
                    'data' => $data,
                ];
        }
    }

    /**
     * Create a refund on Paystack
     */
    public function createRefund(array $data): array
    {
        try {
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ]);
            
            // Disable SSL verification for local development only
            if (app()->environment('local')) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false,
                ]);
            }
            
            $response = $httpClient->post($this->baseUrl.'/refund', [
                'transaction' => $data['transaction'],
                'amount' => $data['amount'] ?? null, // Optional - full refund if not specified
                'currency' => $data['currency'] ?? 'NGN',
                'customer_note' => $data['customer_note'] ?? null,
                'merchant_note' => $data['merchant_note'] ?? null,
            ]);

            $result = $response->json();

            if ($response->successful() && $result['status'] === true) {
                Log::info('Paystack refund created', [
                    'transaction' => $data['transaction'],
                    'amount' => $data['amount'] ?? 'full',
                ]);

                return $result;
            }

            Log::error('Paystack refund failed', [
                'response' => $result,
            ]);

            return [
                'status' => false,
                'message' => $result['message'] ?? 'Refund failed',
            ];

        } catch (\Exception $e) {
            Log::error('Paystack refund error', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Convert amount to smallest currency unit (kobo, pesewas, cents)
     */
    private function convertToSmallestUnit(float $amount, string $currency): int
    {
        $multiplier = self::CURRENCY_MULTIPLIERS[strtoupper($currency)] ?? 100;

        return (int) round($amount * $multiplier);
    }

    /**
     * Convert amount from smallest currency unit back to main unit
     */
    private function convertFromSmallestUnit(int $amount, string $currency): float
    {
        $multiplier = self::CURRENCY_MULTIPLIERS[strtoupper($currency)] ?? 100;

        return $amount / $multiplier;
    }

    /**
     * Generate a unique payment reference
     */
    public function generateReference(string $prefix = 'PAY'): string
    {
        return $prefix.'_'.strtoupper(Str::random(20)).'_'.time();
    }
}
