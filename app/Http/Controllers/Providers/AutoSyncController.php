<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\Product;
use App\Services\AutoSyncService;
use App\Services\AutoSyncSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class AutoSyncController extends Controller
{
    private function credentials()
    {
        return API::where('slug', 'autosync')->firstOrFail();
    }

    public function initiate(Request $request, AutoSyncService $autoSync): JsonResponse
    {
        // normalize camelCase payloads from different clients (sharePin -> share_pin)
        if ($request->has('sharePin') && !$request->has('share_pin')) {
            $request->merge(['share_pin' => $request->input('sharePin')]);
        }

        $validated = $request->validate([
            'product' => ['required', 'integer'],
            'transfer_mode' => ['required', Rule::in(['auto_share'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email' => ['required', 'email'],
            'payment_method' => ['required', Rule::in(['Transfer to Wallet'])],
            'agreement' => ['accepted'],
            'share_pin' => ['required', 'digits_between:4,8'],
        ], [
            'phone.regex' => 'Enter a valid phone number using digits only.',
            'payment_method.in' => 'Auto Transfer currently supports wallet payout only.',
            'share_pin.digits_between' => 'Enter a valid airtime share PIN containing 4 to 8 digits.',
        ]);

        $customer = $request->user()->customer;
        $product = Product::whereKey($validated['product'])
            ->where('type', 'airtime2cash')
            ->where('status', 'active')
            ->where('auto_share_status', 'active')
            ->first();

        if (!$product || !$product->auto_share_product_code) {
            return response()->json(['message' => 'This network is not configured for Auto Transfer.'], 422);
        }

        $grossAmount = (float) $validated['amount'];
        if ($grossAmount < (float) $product->min || $grossAmount > (float) $product->max) {
            return response()->json(['message' => 'Enter an amount within the configured network limits.'], 422);
        }

        if (app(TransactionController::class)->bounceBlacklist($validated['phone'], $request->user()->email, $validated['email'])) {
            return response()->json(['message' => 'This account cannot use Auto Transfer. Please contact support.'], 403);
        }

        $transactionId = 'A2C-' . $this->generateRequestId();
        $rate = (float) ($product->auto_share_rate ?? $product->rate);

        try {
            $response = $autoSync->initiate([
                'request_ref' => $transactionId,
                'phone' => $validated['phone'],
                'product_id' => $product->auto_share_product_code,
                'amount' => $grossAmount,
                'sharePin' => $validated['share_pin'],
            ], [
                'customer_id' => $customer->id,
                'transaction_id' => $transactionId,
            ]);

        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $providerTransaction = data_get($response, 'data.transaction', []);
        $providerReference = $providerTransaction['reference'] ?? null;
        if (!$providerReference) {
            return response()->json(['message' => 'AutoSync did not return a transaction reference. Please try again.'], 502);
        }
        // logger()->info('AutoSync Initiate Response', [
        //     'transaction_id' => $transactionId,
        //     'provider_reference' => $providerReference,
        //     'response' => $response,
        // ]);
        $amountCharged = ($rate / 100) * $grossAmount;
        $transaction = Airtime2CashTransactions::create([
            'amount_charged' => $amountCharged,
            'amount_paid' => $grossAmount - $amountCharged,
            'charge_rate' => $rate,
            'transfer_mode' => 'auto_share',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'type' => 'credit',
            'transaction_id' => $transactionId,
            'total_amount' => $grossAmount,
            'phone_numbers' => $validated['phone'],
            'payment_method' => 'Transfer to Wallet',
            'status' => 'pending',
            'description' => 'Auto Transfer initiated. Awaiting OTP confirmation.',
            'provider_reference' => $providerReference,
            'provider_request_ref' => $providerTransaction['request_ref'] ?? $transactionId,
            'provider_status' => $providerTransaction['status'] ?? 'pending',
            'provider_response' => json_encode($autoSync->redact($response)),
        ]);

        return response()->json([
            'message' => $response['message'] ?? 'OTP sent successfully.',
            'transaction_id' => $transaction->transaction_id,
            'phone' => $transaction->phone_numbers,
        ]);
    }

    public function complete(Request $request, AutoSyncService $autoSync, AutoSyncSettlementService $settlement): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'otp' => ['required', 'digits_between:4,10'],
        ]);
        
        $transaction = $this->customerTransaction($request, $validated['transaction_id']);

        if ($transaction->status === 'approved') {
            return response()->json([
                'message' => 'This Auto Transfer has already been completed.',
                'redirect' => route('airtime2cash.transaction.status', $transaction->transaction_id),
            ]);
        }

        try {
            return Cache::lock('autosync-complete:' . $transaction->id, 45)->block(2, function () use ($autoSync, $settlement, $transaction, $validated) {
                $response = $autoSync->complete($transaction->provider_reference, $validated['otp'], [
                    'customer_id' => $transaction->customer_id,
                    'transaction_id' => $transaction->transaction_id,
                    'provider_reference' => $transaction->provider_reference,
                    'amount' => $transaction->total_amount,
                ]);
                $providerTransaction = data_get($response, 'data.transaction', []);
                $providerStatus = $providerTransaction['status'] ?? null;

                if ($providerStatus !== 'successful') {
                    $transaction->update([
                        'provider_status' => $providerStatus ?? 'pending',
                        'provider_response' => json_encode($autoSync->redact($response)),
                        'description' => $response['message'] ?? 'Auto Transfer is still being processed.',
                    ]);

                    return response()->json(['message' => $response['message'] ?? 'Auto Transfer is still being processed.'], 422);
                }

                $completedAmount = (float) ($providerTransaction['amount'] ?? $transaction->total_amount);
                $settled = $settlement->settle($transaction, $completedAmount, $autoSync->redact($response));

                return response()->json([
                    'message' => 'Airtime shared successfully. Your wallet has been credited.',
                    'redirect' => route('airtime2cash.transaction.status', $settled->transaction_id),
                ]);
            });
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The transfer could not be completed right now. Please try again.'], 503);
        }
    }

    public function resendOtp(Request $request, AutoSyncService $autoSync): JsonResponse
    {
        $validated = $request->validate(['transaction_id' => ['required', 'string']]);
        $transaction = $this->customerTransaction($request, $validated['transaction_id']);

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'OTP can only be resent for a pending Auto Transfer.'], 422);
        }

        try {
            $response = $autoSync->resendOtp($transaction->provider_reference, [
                'customer_id' => $transaction->customer_id,
                'transaction_id' => $transaction->transaction_id,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $transaction->update([
            'provider_status' => data_get($response, 'data.transaction.status', 'pending'),
            'provider_response' => json_encode($autoSync->redact($response)),
        ]);

        return response()->json(['message' => $response['message'] ?? 'OTP sent successfully.']);
    }

    private function customerTransaction(Request $request, string $transactionId): Airtime2CashTransactions
    {
        return Airtime2CashTransactions::where('transaction_id', $transactionId)
            ->where('customer_id', $request->user()->customer->id)
            ->where('transfer_mode', 'auto_share')
            ->firstOrFail();
    }

}
