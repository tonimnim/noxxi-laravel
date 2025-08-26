<?php

namespace App\Services;

use App\Models\Payout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackTransferService
{
    private string $baseUrl;

    private string $secretKey;

    private bool $testMode;

    public function __construct()
    {
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
        $this->secretKey = config('services.paystack.secret_key');
        $this->testMode = false; // Not needed - Paystack determines mode from key prefix
    }

    /**
     * Create a transfer recipient
     */
    public function createRecipient(array $data): array
    {
        try {
            $payload = [
                'type' => $data['type'], // 'mobile_money' or 'bank_account'
                'name' => $data['name'],
                'currency' => $data['currency'] ?? 'KES',
            ];

            if ($data['type'] === 'mobile_money') {
                $payload['bank_code'] = 'MPESA'; // For M-Pesa
                $payload['account_number'] = $this->formatPhoneNumber($data['phone_number']);
            } else {
                $payload['bank_code'] = $data['bank_code'];
                $payload['account_number'] = $data['account_number'];
            }

            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/transferrecipient", $payload);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'recipient_code' => $result['data']['recipient_code'],
                    'data' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to create recipient',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack create recipient error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error creating transfer recipient: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a transfer
     */
    public function initiateTransfer(Payout $payout): array
    {
        try {
            // Create recipient first
            $recipientData = $this->prepareRecipientData($payout);
            $recipient = $this->createRecipient($recipientData);

            if (! $recipient['success']) {
                return $recipient;
            }

            // Initiate the transfer
            $amount = $payout->net_amount * 100; // Convert to smallest currency unit
            $reference = 'PAYOUT_'.strtoupper(Str::random(10));

            $payload = [
                'source' => 'balance',
                'amount' => $amount,
                'recipient' => $recipient['recipient_code'],
                'reason' => "Payout for {$payout->organizer->business_name}",
                'currency' => $payout->currency ?? 'KES',
                'reference' => $reference,
            ];

            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/transfer", $payload);

            if ($response->successful()) {
                $result = $response->json();

                // Update payout record
                $payout->update([
                    'status' => 'processing',
                    'payment_reference' => $result['data']['transfer_code'],
                    'transaction_reference' => $reference,
                    'processed_at' => now(),
                ]);

                return [
                    'success' => true,
                    'transfer_code' => $result['data']['transfer_code'],
                    'reference' => $reference,
                    'data' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to initiate transfer',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack transfer error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error initiating transfer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify a transfer status
     */
    public function verifyTransfer(string $reference): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/transfer/verify/{$reference}");

            if ($response->successful()) {
                $result = $response->json();
                $status = $result['data']['status'];

                return [
                    'success' => true,
                    'status' => $status,
                    'data' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to verify transfer',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verify transfer error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error verifying transfer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * List banks for a country
     */
    public function listBanks(string $country = 'kenya'): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/bank", [
                    'country' => $country,
                    'currency' => $this->getCurrencyForCountry($country),
                ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'banks' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch banks',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list banks error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error fetching banks: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify account number
     */
    public function verifyAccountNumber(string $accountNumber, string $bankCode): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/bank/resolve", [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'account_name' => $result['data']['account_name'],
                    'account_number' => $result['data']['account_number'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid account details',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verify account error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error verifying account: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process batch transfers
     */
    public function processBatchTransfers(array $payoutIds): array
    {
        $results = [];

        foreach ($payoutIds as $payoutId) {
            $payout = Payout::find($payoutId);

            if (! $payout || $payout->status !== 'approved') {
                $results[$payoutId] = [
                    'success' => false,
                    'message' => 'Payout not found or not approved',
                ];

                continue;
            }

            $result = $this->initiateTransfer($payout);
            $results[$payoutId] = $result;

            // Add delay to avoid rate limiting
            sleep(1);
        }

        return $results;
    }

    /**
     * Prepare recipient data from payout
     */
    private function prepareRecipientData(Payout $payout): array
    {
        $organizer = $payout->organizer;

        if ($payout->payment_method === 'mpesa') {
            return [
                'type' => 'mobile_money',
                'name' => $organizer->business_name,
                'phone_number' => $organizer->mpesa_number,
                'currency' => $payout->currency ?? 'KES',
            ];
        }

        // Bank transfer
        return [
            'type' => 'bank_account',
            'name' => $organizer->bank_account_name ?? $organizer->business_name,
            'bank_code' => $this->getBankCode($organizer->bank_name),
            'account_number' => decrypt($organizer->bank_account_number),
            'currency' => $payout->currency ?? 'KES',
        ];
    }

    /**
     * Format phone number for M-Pesa
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present
        if (strlen($phone) === 9) {
            $phone = '254'.$phone;
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = '254'.substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Get bank code from bank name
     */
    private function getBankCode(string $bankName): string
    {
        $bankCodes = [
            'KCB' => '043',
            'Equity' => '050',
            'Cooperative' => '011',
            'Standard Chartered' => '002',
            'Barclays' => '003',
            'DTB' => '063',
            'NCBA' => '007',
            'Stanbic' => '031',
            'I&M' => '057',
            'Family' => '070',
        ];

        return $bankCodes[$bankName] ?? '000';
    }

    /**
     * Get currency for country
     */
    private function getCurrencyForCountry(string $country): string
    {
        $currencies = [
            'kenya' => 'KES',
            'nigeria' => 'NGN',
            'south_africa' => 'ZAR',
            'ghana' => 'GHS',
            'uganda' => 'UGX',
            'tanzania' => 'TZS',
            'egypt' => 'EGP',
        ];

        return $currencies[strtolower($country)] ?? 'USD';
    }
}
