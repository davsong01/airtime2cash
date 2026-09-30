<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\API;
use App\Models\Airtime2CashTransactions;
use App\Services\AirtimeToCashAutomationService;
use Illuminate\Support\Str;
use RuntimeException;

class AirtimeToCashAutomationController extends Controller
{
    public function __construct(
        private readonly AirtimeToCashAutomationService $service
    ) {
    }

    public function initiate(
        Airtime2CashTransactions $transaction,
        API $provider,
        bool $checkQuota = false
    ): array {
        $networkName = $this->service->networkNameForTransaction($transaction);
        $sender = $this->senderForTransaction($transaction);
        $context = $this->context($transaction);

        if ($checkQuota) {
            $quotaResponse = $this->service->checkQuota(
                networkName: $networkName,
                amount: (float) $transaction->total_amount,
                provider: $provider,
                context: $context,
            );

            if (! $this->quotaAvailable($quotaResponse)) {
                throw new RuntimeException(
                    (string) ($quotaResponse['message'] ?? 'No recipient is currently available for this amount.')
                );
            }
        }

        $response = $this->service->generateOtp(
            networkName: $networkName,
            sender: $sender,
            provider: $provider,
            context: $context,
        );

        if ((int) ($response['code'] ?? 0) !== 2000) {
            throw new RuntimeException((string) ($response['message'] ?? 'OTP could not be generated.'));
        }

        $transaction->update([
            'provider_session_id' => null,
            'provider_status' => 'otp_sent',
            'provider_response' => $response,
        ]);

        return [
            'status' => true,
            'stage' => 'otp',
            'message' => $response['message'] ?? 'OTP sent successfully.',
            'transaction_id' => $transaction->transaction_id,
            'phone' => $transaction->phone_numbers,
            'provider_response' => $response,
        ];
    }

    public function verifyOtp(
        Airtime2CashTransactions $transaction,
        string $otp,
        API $provider
    ): array {
        $response = $this->service->verifyOtp(
            networkName: $this->service->networkNameForTransaction($transaction),
            sender: $this->senderForTransaction($transaction),
            otp: $otp,
            provider: $provider,
            context: $this->context($transaction),
        );

        $code = (int) ($response['code'] ?? 0);
        $sessionId = data_get($response, 'data.sessionId');

        if ($code === 2000 && filled($sessionId)) {
            $loginResponse = $this->service->loginWithSessionId(
                networkName: $this->service->networkNameForTransaction($transaction),
                sender: $this->senderForTransaction($transaction),
                sessionId: (string) $sessionId,
                provider: $provider,
                context: $this->context($transaction),
            );

            $loginCode = (int) ($loginResponse['code'] ?? 0);
            if ($loginCode !== 2000) {
                $transaction->update([
                    'provider_status' => $loginCode === 4010 ? 'session_expired' : 'otp_pending',
                    'provider_response' => [
                        'verify' => $response,
                        'login' => $loginResponse,
                    ],
                ]);

                return [
                    'status' => false,
                    'stage' => 'otp',
                    'otp_invalid' => false,
                    'session_expired' => $loginCode === 4010,
                    'message' => $loginResponse['message'] ?? 'The provider session could not be opened.',
                    'transaction_id' => $transaction->transaction_id,
                    'provider_response' => $loginResponse,
                ];
            }

            $transaction->update([
                'provider_session_id' => $sessionId,
                'provider_status' => 'otp_verified',
                'provider_response' => [
                    'verify' => $response,
                    'login' => $loginResponse,
                ],
            ]);

            return [
                'status' => true,
                'stage' => 'pin',
                'message' => 'OTP verified. Enter your airtime share PIN to continue.',
                'transaction_id' => $transaction->transaction_id,
                'provider_response' => [
                    'verify' => $response,
                    'login' => $loginResponse,
                ],
            ];
        }

        $transaction->update([
            'provider_status' => $code === 4010 ? 'session_expired' : 'otp_pending',
            'provider_response' => $response,
        ]);

        return [
            'status' => false,
            'stage' => $code === 4010 ? 'otp' : 'otp',
            'otp_invalid' => $code !== 4010,
            'session_expired' => $code === 4010,
            'message' => $response['message'] ?? 'OTP verification failed.',
            'transaction_id' => $transaction->transaction_id,
            'provider_response' => $response,
        ];
    }

    public function transfer(
        Airtime2CashTransactions $transaction,
        string $pin,
        API $provider
    ): array {
        if (blank($transaction->provider_session_id)) {
            throw new RuntimeException('The provider session has expired. Please request a new OTP.');
        }

        $response = $this->service->transferTransaction(
            transaction: $transaction,
            pin: $pin,
            sessionId: (string) $transaction->provider_session_id,
            provider: $provider,
        );

        $code = (int) ($response['code'] ?? 0);
        $providerStatus = match ($code) {
            2000 => 'successful',
            4000 => 'pending',
            4010 => 'session_expired',
            default => 'failed',
        };

        $transaction->update([
            'provider_status' => $providerStatus,
            'provider_response' => $response,
            'provider_session_id' => $providerStatus === 'session_expired'
                ? null
                : $transaction->provider_session_id,
        ]);

        return [
            'status' => $code === 2000 ? 'ok' : ($code === 4000 ? 'pending' : 'error'),
            'message' => $response['message'] ?? 'Airtime transfer could not be completed.',
            'provider_code' => $code,
            'provider_status' => $providerStatus,
            'data' => [
                'transaction' => [
                    'status' => $providerStatus,
                    'details' => $response['message'] ?? null,
                    'amount' => data_get($response, 'data.amountConverted'),
                ],
            ],
            'provider_response' => $response,
        ];
    }

    public function resendOtp(
        Airtime2CashTransactions $transaction,
        API $provider
    ): array {
        return $this->initiate($transaction, $provider);
    }

    public function query(Airtime2CashTransactions $transaction, API $provider): array
    {
        return [
            'status' => 'pending',
            'provider_status' => $transaction->provider_status ?: 'pending',
            'message' => 'This provider does not expose a transaction status endpoint. The transaction remains pending until the transfer response is received.',
            'api_response' => $transaction->provider_response ?: [],
        ];
    }

    public function requery($transaction): array
    {
        $airtimeTransaction = $transaction instanceof Airtime2CashTransactions
            ? $transaction
            : $transaction->airtime2cash;

        return $this->query($airtimeTransaction, $airtimeTransaction->provider);
    }

    private function quotaAvailable(array $response): bool
    {
        $message = Str::lower((string) ($response['message'] ?? ''));

        return str_contains($message, 'available')
            && ! str_contains($message, 'unavailable')
            && ! str_contains($message, 'unavailability');
    }

    private function context(Airtime2CashTransactions $transaction): array
    {
        return [
            'customer_id' => $transaction->customer_id,
            'transaction_id' => $transaction->transaction_id,
        ];
    }

    private function senderForTransaction(Airtime2CashTransactions $transaction): string
    {
        $sender = trim((string) $transaction->phone_numbers);

        if ($sender === '' || str_contains($sender, ',')) {
            throw new RuntimeException('This provider supports one sender phone number per transaction.');
        }

        return $sender;
    }
}
