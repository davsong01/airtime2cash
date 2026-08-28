<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PaymentProcessors\SquadController;
use App\Http\Controllers\Providers\MonnifyController;
use App\Http\Controllers\Providers\AutoSyncController;
use App\Http\Controllers\WalletController;
use App\Models\Airtime2CashTransactions;
use App\Models\API;
use App\Models\Customer;
use App\Models\Bank;
use App\Models\BillerLog;
use App\Models\BlackList;
use App\Models\Category;
use App\Models\Discount;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ReferralEarning;
use App\Models\ReservedAccountCallback;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Variation;
use App\Models\Wallet;
use App\Services\AutoSyncService;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class TransactionController extends Controller
{
    public function showProductsPage($slug)
    {
        $category = Category::with([
            'products' => function ($query) {
                return $query->where('status', 'active')->get();
            }
        ])->where('status', 'active')->where('slug', $slug)->first();

        $selectedProductId = request()->integer('product');
        $selectedProduct = null;

        if ($selectedProductId && $category) {
            $selectedProduct = $category->products
                ->where('id', $selectedProductId)
                ->where('status', 'active')
                ->first();
        }

        if (!empty($category) && $category->status == 'active') {
            return view(themeView('customer', 'single_category_page'), compact('category', 'selectedProduct'));
        } else {
            return back();
        }
    }

    public function airtimeToCash()
    {
        if (! $this->customerCanAccessService('a2c')) {
            return $this->serviceUnavailableResponse('Airtime 2 Cash', 'a2c');
        }

        if (auth()->user()->customer->kyc_status == 'unverified') {
            $kycLink = '<a href="' . route('update.kyc.details') . '"><strong>complete your KYC now</strong></a>';

            return back()->with(
                'error',
                'Identity verification is required before you can convert airtime to cash. Please ' . $kycLink . ' to continue.'
            );
        }
        $category = Category::with([
            'products' => function ($query) {
                return $query->where('status', 'active')
                    ->where('type', 'airtime2cash')
                    ->where(function ($query) {
                        $query->where('manual_status', 'active')
                            ->orWhere('auto_share_status', 'active');
                    });
            }
        ])->where('status', 'active')->where('type', 'airtime2cash')->first();

        if (empty($category)) {
            return back()->with('error', 'Airtime to Cash is not currently available.');
        }

        $levelId = $this->activeCustomerLevelId(auth()->user());

        foreach ($category->products as $product) {
            $manualRate = $levelId ? $product->customer_level_transfer_price($levelId, 'manual') : null;
            $autoShareRate = $levelId ? $product->customer_level_transfer_price($levelId, 'auto_share') : null;

            $product->manual_discounted_rate = ((float) ($manualRate ?? 0) >= 1) ? $manualRate : $product->rate;
            $product->auto_share_discounted_rate = ((float) ($autoShareRate ?? 0) >= 1) ? $autoShareRate : ($product->auto_share_rate ?? $product->rate);
        }

        $banks = Bank::active()->orderBy('bank_name')->get();
        $activeProvider = API::query()
            ->whereKey(getSettings()?->auto_share_provider_id)
            ->where('status', 'active')
            ->first();

        if (!empty($category) && $category->status == 'active') {
            return view(themeView('customer', 'airtime2cash_page'), compact('category', 'banks', 'activeProvider'));
        } else {
            return back();
        }
    }

    public function walletToBank($slug)
    {
        if (! $this->customerCanAccessService('wallet2bank')) {
            return $this->serviceUnavailableResponse('Wallet 2 Bank', 'wallet2bank');
        }

        $route = '<a style="color: yellow;" href="' . route("update.kyc.details") . '">HERE</a>';

        if (auth()->user()->customer->kyc_status == 'unverified') {
            return back()->with('error', 'Wallet to bank conversion is only available for fully verified clients, please click ' . $route . ' to get verified');
        }

        $product = Product::where('slug', $slug)->first();
        $verificationProviderId = getSettings()->bank_verification_provider_id ?: getSettings()->bank_transfer_provider_id;
        $verificationProvider = API::query()
            ->whereKey($verificationProviderId)
            ->where('status', 'active')
            ->first();
        $banks = getWalletToBankBanks($verificationProvider);
        $pricingProvider = API::query()
            ->whereKey(getSettings()->bank_transfer_provider_id)
            ->where('status', 'active')
            ->first();
        $activeProvider = $pricingProvider;
        $pricingBands = $pricingProvider?->pricing_data ?? [];
        $pricingEnabled = (bool) ($pricingProvider?->pricing_data_status ?? false);
        $pricingAvailable = $pricingEnabled && !empty($pricingBands);
        $pricingAmountRange = getBankTransferPricingAmountRange($pricingProvider?->id);
        $providerMin = 60;
        $minimumCharge = $pricingAvailable
            ? getBankTransferChargeDetails($providerMin, $pricingProvider?->id)
            : ['transfer_fee' => 0];
        $minimumRequiredBalance = $providerMin + (float) ($minimumCharge['transfer_fee'] ?? 0);
        $walletBalance = walletBalance(auth()->user());

        if (!empty($product) && $product->status == 'active') {
            return view(themeView('customer', 'wallet2bank_transfer_page'), compact('product', 'banks', 'pricingProvider', 'activeProvider', 'pricingBands', 'pricingAmountRange', 'providerMin', 'minimumRequiredBalance', 'pricingEnabled', 'pricingAvailable', 'walletBalance'));
        } else {
            return back();
        }
    }

    public function initializeTransaction(Request $request)
    {
        $blacklist = bounceBlacklist($request->phone ?? $request->unique_element, auth()->user()->email, $request->email);

        if ($blacklist) {
            return back()->with('error', 'Account blacklisted!, kindly reach out to support!');
        }

        // Check Transaction pin
        $pinCheck = $this->checkTransactionPin($request);

        if (!$pinCheck) {
            return back()->with('error', 'Invalid Transaction PIN!');
        }

        // Get product
        $product = Product::where('id', $request->product)->first();
        $category = $product->category_id;

        if (empty($product)) {
            return back()->with('error', 'The selected product/service does not seem to exist, kindly check your selection');
        }

        $element = $product->category->unique_element;
        $request['unique_element'] = $request->unique_element ?? $request->$element;

        if ($product->has_variations == 'yes') {
            $variation = Variation::where('id', $request->variation)->where('product_id', $product->id)->first();

            if ($variation->fixed_price == 'Yes') {
                $request['amount'] = $variation->system_price;
            } elseif ($product->allow_subscription_type == 'yes' && $variation->category->unique_element == 'iuc_number') {
                if (!empty($request->bouquet) && $request->bouquet == 'renew') {
                    $req = new Request([
                        'unique_element' => $request['unique_element'],
                        'variation' => $variation->id,
                    ]);
                    $res = $this->verify($req);
                    if (isset($res['renewal_amount'])) {
                        $request['amount'] = $res['renewal_amount'];
                    } else {
                        $request['amount'] = $variation->system_price;
                    }
                } else {
                    $request['amount'] = $variation->system_price;
                }
            } else {
                $request['amount'] = $this->removeCharsInAmount($request->amount);
            }
        } else {
            if ($product->fixed_price == 'yes') {
                $request['amount'] = $product->system_price;
            } else {
                $request['amount'] = $this->removeCharsInAmount($request->amount);
            }
        }

        // Verify Meter
        if ($product->allow_meter_validation) {
            $meterValidation = $this->validateMeter($product);
            if (isset($meterValidation) && $meterValidation['code'] == 0) {
                return back()->with('error', $meterValidation['error']);
            }
        }

        $request['discount'] = 0;

        if ($product->has_variations == 'yes') {
            $discount = $this->getDiscount($variation, 'variation', $request['amount'], 'yes');
        } else {
            $discount = $this->getDiscount($product, 'product', $request['amount'], 'yes');
        }

        $discountedAmount = $discount['discounted_price'];
        $disCountApplied = $discount['discount_applied'];

        $request['quantity'] = $request->quantity ?? 1;
        $request['total_amount'] = $discountedAmount * $request['quantity'];
        $request['discount'] = $disCountApplied * $request['quantity'];


        // Get Wallet Balance
        $wallet = new WalletController();
        $balance = $wallet->getWalletBalance(auth()->user());

        if ($balance < $request['total_amount']) {
            return back()->with('error', 'Insufficient Wallet Balance, Please try again');
        }

        // Log Wallet
        $request_id = $this->generateRequestId();
        $request['type'] = 'debit';
        $request['customer_id'] = auth()->user()->customer->id;
        $request['transaction_id'] = 'A2C-' . $request_id;
        $request['request_id'] = $request_id;
        $request['payment_method'] = 'wallet';
        $request['balance_before'] = $balance;
        $request['ip_address'] = $this->getIpAddress();
        $request['domain_name'] = $this->getDomainName();
        $request['customer_email'] = auth()->user()->email;
        $request['customer_phone'] = auth()->user()->phone;
        $request['customer_name'] = auth()->user()->firstname;
        $request['variation_id'] = $variation->id ?? null;
        $request['product_id'] = $product->id;
        $request['product_name'] = $product->name;
        $request['variation_name'] = $variation->slug ?? null;
        $request['category_id'] = $product->category->id;
        $request['api_id'] = $variation->api->id ?? $product->api_id;
        $request['product_slug'] = $variation->product->slug ?? $product->slug;
        $request['variation_slug'] = $variation->slug ?? null;
        $request['network'] = $variation->network ?? null;
        $request['reason'] = 'Product Purchase';
        $request['subscription_type'] = $variation->bouquet ?? 'change';

        // Log basic transaction
        $transaction = $this->logTransaction($request->all());

        // Log wallet
        $wal = $wallet->logWallet($request->all());

        // Update Customer Wallet
        $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], $request['type']);

        // Process Transaction
        try {
            //code...
            $transaction = $this->processTransaction($request->all(), $transaction, $product, $variation ?? null);
        } catch (\Throwable $th) {
            \Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);
            return back()->with('error', 'An error occured, please try again later');
        }

        // Log Transaction Email
        $this->sendTransactionEmail($transaction, auth()->user());
        return redirect(route('transaction.status', $transaction->transaction_id));
    }

    public function initializeAirtime2CashTransaction(Request $request)
    {
        $request->validate([
            'product' => ['required', 'integer'],
            'transfer_mode' => ['required', 'in:manual,auto_share'],
        ]);

        if (! $this->customerCanAccessService('a2c')) {
            return $this->serviceUnavailableResponse('Airtime 2 Cash', 'a2c');
        }

        $statusColumn = $request->transfer_mode === 'auto_share'
            ? 'auto_share_status'
            : 'manual_status';

        $product = Product::where('id', $request->product)
            ->where('type', 'airtime2cash')
            ->where('status', 'active')
            ->where($statusColumn, 'active')
            ->first();

        if (empty($product)) {
            return back()->withInput()->with('error', 'The selected network is not available for this transfer method.');
        }

        $levelId = $this->activeCustomerLevelId(auth()->user());

        if ($request->transfer_mode === 'manual') {
            $discountedRate = $levelId ? $product->customer_level_transfer_price($levelId, 'manual') : null;
            $rate = ((float) ($discountedRate ?? 0) >= 1) ? $discountedRate : $product->rate;
            $profitPercentage = $product->manual_profit_percentage;
        } else {
            $discountedRate = $levelId ? $product->customer_level_transfer_price($levelId, 'auto_share') : null;
            $rate = ((float) ($discountedRate ?? 0) >= 1) ? $discountedRate : ($product->auto_share_rate ?? $product->rate);
            $profitPercentage = $product->auto_share_profit_percentage;
        }

        $min = $product->effectiveTransferMin($request->transfer_mode) ?? (float) ($product->min ?? 0);
        $max = $product->effectiveTransferMax($request->transfer_mode) ?? (float) ($product->max ?? 0);
        $amount = $this->removeCharsInAmount($request->amount);

        $amount_charged = ($rate / 100) * $amount;
        $amount_paid = $amount - $amount_charged;
        $profitPercentage = is_numeric($profitPercentage) ? (float) $profitPercentage : 0;
        $profit = $profitPercentage > 0 ? (($amount / 100) * $profitPercentage) : 0;

        if ($amount > 0 && $amount >= $min && $amount <= $max) {
            $amount = $amount - (($rate / 100) * $amount);
        } else {
            return back()->with('error', 'Invalid amount entered');
        }

        $transaction_id = 'A2C-' . $this->generateRequestId();
        $bank = Bank::active()->where('cbn_code', $request->bank)->first();
        if (!empty($bank)) {
            $bank_name = $bank->bank_name;
            $request['bank_id'] = $bank->id;
        } else {
            $bank_name = '';
        }

        $transaction = [
            'amount_charged' => $amount_charged,
            'amount_paid' => $amount_paid,
            'charge_rate' => $rate,
            'profit_percentage' => $profitPercentage,
            'profit' => $profit,
            'transfer_mode' => $request->transfer_mode,
            'product_id' => $product->id,
            'customer_id' => auth()->user()->customer->id,
            'type' => 'credit',
            'transaction_id' => $transaction_id,
            'total_amount' => $amount_charged + $amount_paid,
            'phone_numbers' => $request->phone,
            'payment_method' => $request->payment_method,
            'bank_code' => $request->bank,
            'bank_name' => $bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            // 'ip_address' => $request->ip,
        ];


        // Process Transaction
        try {
            $log = Airtime2CashTransactions::updateOrCreate(
                ['transaction_id' => $transaction_id],
                $transaction
            );

            $customer = auth()->user()->customer;
            $currentBalance = (float) ($customer->wallet ?? 0);
            $this->upsertAirtime2CashTransactionLog($log, $customer, [
                'status' => 'pending',
                'descr' => 'Airtime2Cash request initiated.',
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance,
                'provider_status' => 'initiated',
            ]);

            if ($request->input('transfer_mode') === 'auto_share') {
                $providerId = getSettings()?->auto_share_provider_id;
                $provider = API::query()->find($providerId);

                if (! $provider) {
                    return response()->json([
                        'status' => false,
                        'message' => 'The auto-transfer provider is not configured.',
                    ], 422);
                }

                $log->update(['provider_id' => $providerId]);

                if ($provider->slug == 'autosync') {
                    $response = (new AutoSyncService())->initiate(
                        $log,
                        $provider
                    );

                    return response()->json([
                        'status' => true,
                        'message' => $response['message'] ?? 'Share PIN submitted successfully. Enter the OTP sent to your phone.',
                        'data' => [
                            'transaction_id' => $log->transaction_id,
                            'phone' => $log->phone ?? $request->input('phone'),
                            'provider_response' => $response,
                        ],
                    ]);
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => 'The selected auto-transfer provider is not supported.',
                    ], 422);
                }
            }

              // Log Transaction Email
            $subject = "Airtime2Cash Transaction Alert";

            $body = '<p>Hello ' . e(auth()->user()->name) . ',</p>';
            $body .= '<p style="line-height: 2;">You have just indicated a request to convert your airtime to cash on ' . e(config('app.name')) . '.<br>
                Please find below the details of the transaction:</p>';

                        $body .= '
                <p style="line-height: 1.8;">
                <strong>Amount Charged:</strong> ' . e(getSettings()->currency) . number_format($log->amount_charged, 2) . '<br>
                <strong>Amount to Receive:</strong> ' . e(getSettings()->currency) . number_format($log->amount_paid, 2) . '<br>
                <strong>Charge Rate:</strong> ' . number_format($log->charge_rate) . '%<br>
                <strong>Profit Percentage:</strong> ' . number_format((float) ($log->profit_percentage ?? 0), 2) . '%<br>
                <strong>Profit:</strong> ' . e(getSettings()->currency) . number_format((float) ($log->profit ?? 0), 2) . '<br>
                <strong>Transfer Method:</strong> ' . e($log->transfer_mode === 'auto_share' ? 'Auto Transfer' : 'Manual Transfer') . '<br>
                <strong>Total Credit to transfer:</strong> ' . e(getSettings()->currency) . number_format($log->total_amount, 2) . '<br>
                <strong>Phone Numbers:</strong> ' . e($log->phone_numbers) . '<br>
                <strong>Transaction ID:</strong> ' . e($log->transaction_id) . '<br>
                <strong>Network:</strong> ' . e($product->name) . '<br>
                <strong>Payment Method:</strong> ' . e($log->payment_method) . '<br>
                <strong>Transaction Date:</strong> ' . date("M jS, Y g:iA", strtotime($log->created_at)) . '<br>';

            if ($log->payment_method == 'Transfer to Bank Account') {
                $body .= '
                <strong>Bank Name:</strong> ' . e($log->bank_name) . '<br>
                <strong>Account Name:</strong> ' . e($log->account_name) . '<br>
                <strong>Account Number:</strong> ' . e($log->account_number) . '<br>';
            }

            $body .= '<br>Warm Regards,<br>' . e(config('app.name')) . '.</p>';

            $email = $request->email;
            logEmails($email, $subject, $body);

            // build formatted plain-text message for WhatsApp/Telegram
            $message = "Hello Admin, I want to convert Airtime to cash on " . config("app.name") . ". Please find below details of the transaction:\n\n" .
                "*Name:* " . auth()->user()->name . "\n" .
                "*Amount Charged:* " . getSettings()->currency . number_format($log->amount_charged, 2) . "\n" .
                "*Amount to Receive:* " . getSettings()->currency . number_format($log->amount_paid, 2) . "\n" .
                "*Charge Rate:* " . number_format($log->charge_rate) . "%\n" .
                "*Transfer Method:* " . ($log->transfer_mode === 'auto_share' ? 'Auto Transfer' : 'Manual Transfer') . "\n" .
                "*Total Credit to transfer:* " . getSettings()->currency . number_format($log->total_amount, 2) . "\n" .
                "*Phone Numbers:* " . $log->phone_numbers . "\n" .
                "*Transaction ID:* " . $log->transaction_id . "\n" .
                "*Network:* " . $product->name . "\n" .
                "*Payment Method:* " . $log->payment_method . "\n" .
                "*Transaction Date:* " . date("M jS, Y g:iA", strtotime($log->created_at)) . "\n";

            if ($log->payment_method == 'Transfer to Bank Account') {
                $message .= "\n*Bank Name:* " . $log->bank_name .
                    "\n*Account Name:* " . $log->account_name .
                    "\n*Account Number:* " . $log->account_number;
            }

            // generate correct link
            if (getSettings()->a2cash_chat_engine == 'telegram') {
                $chatLink = "https://t.me/" . ltrim(getSettings()->telegram_username, '@') . "?text=" . urlencode($message);
            } else {
                $chatLink = "https://api.whatsapp.com/send?phone=" . getSettings()->whatsapp_number . "&text=" . urlencode($message);
            }

            return redirect()->away($chatLink);
        } catch (\Throwable $exception) {
            Log::error('Airtime-to-cash transaction failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'transaction_id' => $transaction_id,
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $exception->getMessage() ?? 'The transaction could not be completed. Please try again later.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'The transaction could not be completed. Please try again later.');
        }
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
        ]);


        $transaction = Airtime2CashTransactions::where('transaction_id', $validated['transaction_id'])->firstOrFail();

        if ($transaction->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'OTP can only be resent for a pending Auto Transfer.',
            ], 422);
        }

        if (blank($transaction->provider_request_ref)) {
            return response()->json([
                'status' => false,
                'message' => 'The provider transaction reference is missing.',
            ], 422);
        }

        try {
            $provider = API::query()->find(
                $transaction->provider_id ?: getSettings()?->auto_share_provider_id
            );

            if (! $provider) {
                return response()->json([
                    'status' => false,
                    'message' => 'The auto-transfer provider is not configured.',
                ], 422);
            }

            $response = match ($provider->slug) {
                'autosync' => app(AutoSyncService::class)->resendOtp(
                    $transaction,
                    $provider
                ),
                default => throw new RuntimeException(
                    "Auto-transfer provider '{$provider->slug}' does not support OTP resend."
                ),
            };

            return response()->json([
                'status' => true,
                'message' => $response['message'] ?? 'OTP resent successfully.',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'phone' => $transaction->phone_numbers,
                    'provider_response' => $response,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            Log::error('Auto Transfer OTP resend failed.', [
                'message' => $exception->getMessage(),
                'transaction_id' => $transaction->transaction_id,
                'customer_id' => $transaction->customer_id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'The OTP could not be resent. Please try again later.',
            ], 500);
        }
    }


    public function initializeWalletToBankTransaction(Request $request, Product $product)
    {
        if (! $this->customerCanAccessService('wallet2bank')) {
            return $this->serviceUnavailableResponse('Wallet 2 Bank', 'wallet2bank');
        }

        if (empty($product)) {
            return back()->with('error', 'The selected product/service does not seem to exist, kindly try again');
        }

        $settings = getSettings();
        $availableTransferModes = array_values(array_filter([
            (($settings->wallet_to_bank_transfer_auto_status ?? 'enabled') === 'enabled') ? 'auto_share' : null,
            (($settings->wallet_to_bank_transfer_manual_status ?? 'enabled') === 'enabled') ? 'manual' : null,
        ]));

        if (empty($availableTransferModes)) {
            return back()->with('error', 'Wallet to bank transfer is currently unavailable. Please try again later.');
        }

        $request->validate([
            'transfer_mode' => ['nullable', Rule::in($availableTransferModes)],
        ]);

        $providerMin = 60;
        $amount = (float) $this->removeCharsInAmount($request->amount);
        $chargeDetails = getBankTransferChargeDetails($amount);
        if (!($chargeDetails['pricing_enabled'] ?? false)) {
            return back()->with('error', 'Wallet to bank transfer pricing is configured yet.');
        }

        if (!($chargeDetails['pricing_available'] ?? false)) {
            return back()->with('error', 'Wallet to bank transfer pricing is not configured yet.');
        }

        if (!($chargeDetails['matched'] ?? false)) {
            $pricingRange = getBankTransferPricingAmountRange($chargeDetails['provider_id'] ?? null);
            $rangeText = $pricingRange['range_text'] ?? null;

            if (!empty($rangeText)) {
                return back()->with('error', 'Valid transfer amounts are ' . $rangeText . '.');
            }

            return back()->with('error', 'Unable to determine the valid transfer amount range from the active pricing bands.');
        }

        $transferFee = (float) ($chargeDetails['transfer_fee'] ?? 0);
        $totalDebit = (float) ($chargeDetails['total_debit'] ?? ($amount + $transferFee));
        $walletBal = walletBalance(auth()->user());

        if ($amount < $providerMin) {
            return back()->with('error', 'Amount too low. You must transfer at least ₦' . number_format($providerMin));
        }

        if ($walletBal < $totalDebit) {
            return back()->with('error', 'Insufficient wallet balance. This transfer will debit ₦' . number_format($totalDebit, 2));
        }

        $bank = Bank::active()->where('cbn_code', $request->bank)->first();

        if (!empty($bank)) {
            $bank_name = $bank->bank_name;
            $request['bank_id'] = $bank->id;
            $request['bank_code'] = $bank->cbn_code;
        } else {
            return back()->with('error', 'Invalid bank selected');
        }

        $request['quantity'] = 1;
        $request['transfer_amount'] = $amount;
        $request['amount'] = $amount;
        $request['total_amount'] = $totalDebit;
        $request['provider_charge'] = $transferFee;
        $request['provider_fee'] = (float) ($chargeDetails['provider_fee'] ?? 0);
        $request['extra_charge'] = (float) ($chargeDetails['extra_charge'] ?? 0);
        $request['pricing_band_name'] = $chargeDetails['band_name'] ?? null;
        $request['charge_breakdown'] = $chargeDetails['charge_breakdown'] ?? [];

        // Get Wallet Balance
        $balance = $walletBal;

        if ($balance < $request['total_amount']) {
            return redirect(route('dashboard'))->with('error', 'Insufficient Wallet Balance, Please try again');
        }

        $request_id = $this->generateRequestId();
        $request['type'] = 'debit';
        $request['customer_id'] = auth()->user()->customer->id;
        $request['transaction_id'] = 'W2B-' . $request_id;
        $request['request_id'] = $request_id;
        $request['payment_method'] = 'wallet';
        $request['balance_before'] = $balance;
        $request['balance_after'] = $balance;
        $request['ip_address'] = $this->getIpAddress();
        $request['domain_name'] = $this->getDomainName();
        $request['customer_email'] = auth()->user()->email;
        $request['customer_phone'] = auth()->user()->phone;
        $request['customer_name'] = auth()->user()->firstname;
        $request['product_id'] = $product->id;
        $request['product_name'] = $product->name;
        $request['category_id'] = $product?->category?->id;
        $request['reason'] = 'Wallet to Bank Transfer';
        $request['unique_element'] = 'Wallet2Bank';
        $request['discount'] = 0;
        $request['api_id'] = $chargeDetails['provider_id'] ?? getSettings()->bank_transfer_provider_id ?? null;
        $request['transfer_mode'] = $request->input('transfer_mode', $availableTransferModes[0]);

        if (! in_array($request['transfer_mode'], $availableTransferModes, true)) {
            return back()->with('error', 'The selected transfer method is currently unavailable.');
        }

        $wallet = new WalletController();

        try {
            DB::transaction(function () use (&$transaction, $wallet, $request) {
                $customer = Customer::query()
                    ->whereKey(auth()->user()->customer->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $recentDuplicate = $this->findRecentWalletToBankDuplicate($customer, $request->all());

                if ($recentDuplicate) {
                    throw new RuntimeException(
                        'This looks like a duplicate wallet to bank transaction. Please wait 5 minutes and try again with the same parameters.'
                    );
                }

                $balance = (float) ($customer->wallet ?? 0);

                if ($balance < (float) $request['total_amount']) {
                    throw new RuntimeException('Insufficient wallet balance. This transfer will debit ₦' . number_format((float) $request['total_amount'], 2));
                }

                $request['balance_before'] = $balance;
                $request['balance_after'] = $balance - (float) $request['total_amount'];
                $request['status'] = 'pending';
                $request['user_status'] = 'pending';
                $request['descr'] = $request['transfer_mode'] === 'manual'
                    ? 'Wallet to Bank Transfer initiated for manual processing.'
                    : 'Wallet to Bank Transfer initiated.';

                $transaction = $this->logTransaction($request->all());

                $wallet->logWallet($request->all());
                $wallet->applyCustomerBalanceChange($customer, 'wallet', (float) $request['total_amount'], $request['type']);
            });
        } catch (\Throwable $th) {
            Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);

            if ($th instanceof RuntimeException && str_contains($th->getMessage(), 'duplicate wallet to bank transaction')) {
                return back()->with('error', $th->getMessage());
            }

            if ($th instanceof RuntimeException && str_contains($th->getMessage(), 'Insufficient wallet balance')) {
                return back()->with('error', $th->getMessage());
            }

            return back()->with('error', 'An error occured, please try again later');
        }

        if ($request['transfer_mode'] === 'manual') {
            try {
                $message = "Hello Admin, I want to request a wallet to bank transfer on " . config('app.name') . ". Please find below details of the transaction:\n\n" .
                    "*Name:* " . auth()->user()->name . "\n" .
                    "*Amount to Transfer:* " . getSettings()->currency . number_format($transaction->amount, 2) . "\n" .
                    "*Transfer Fee:* " . getSettings()->currency . number_format((float) $transaction->provider_charge, 2) . "\n" .
                    "*Total Debit:* " . getSettings()->currency . number_format($transaction->total_amount, 2) . "\n" .
                    "*Transfer Mode:* Manual Transfer\n" .
                    "*Transaction ID:* " . $transaction->transaction_id . "\n" .
                    "*Bank Name:* " . $bank->bank_name . "\n" .
                    "*Account Name:* " . $request->account_name . "\n" .
                    "*Account Number:* " . $request->account_number . "\n" .
                    "*Transaction Date:* " . date("M jS, Y g:iA", strtotime($transaction->created_at)) . "\n\n" .
                    "I am aware that manual resolution is subject to an admin being online and may take longer during busy periods.";

                $subject = "Wallet to Bank Manual Transfer Alert";
                $body = '<p>Hello ' . e(auth()->user()->name) . ',</p>';
                $body .= '<p style="line-height: 2;">Your wallet to bank transfer request has been received and queued for manual processing on ' . e(config('app.name')) . '.<br>
                    Please find below the details of the transaction:</p>';
                $body .= '
                    <p style="line-height: 1.8;">
                    <strong>Amount to Transfer:</strong> ' . e(getSettings()->currency) . number_format($transaction->amount, 2) . '<br>
                    <strong>Transfer Fee:</strong> ' . e(getSettings()->currency) . number_format((float) $transaction->provider_charge, 2) . '<br>
                    <strong>Total Debit:</strong> ' . e(getSettings()->currency) . number_format($transaction->total_amount, 2) . '<br>
                    <strong>Transfer Method:</strong> Manual Transfer<br>
                    <strong>Transaction ID:</strong> ' . e($transaction->transaction_id) . '<br>
                    <strong>Bank Name:</strong> ' . e($bank->bank_name) . '<br>
                    <strong>Account Name:</strong> ' . e($request->account_name) . '<br>
                    <strong>Account Number:</strong> ' . e($request->account_number) . '<br>
                    <strong>Transaction Date:</strong> ' . date("M jS, Y g:iA", strtotime($transaction->created_at)) . '<br>';
                $body .= '<br>Warm Regards,<br>' . e(config('app.name')) . '.</p>';

                try {
                    logEmails(auth()->user()->email, $subject, $body);
                } catch (\Throwable $emailException) {
                    Log::warning('Wallet-to-bank manual email log failed.', [
                        'message' => $emailException->getMessage(),
                        'transaction_id' => $transaction->transaction_id,
                        'user_id' => auth()->id(),
                    ]);
                }

                if (getSettings()->a2cash_chat_engine == 'telegram') {
                    $chatLink = "https://t.me/" . ltrim(getSettings()->telegram_username, '@') . "?text=" . urlencode($message);
                } else {
                    $chatLink = "https://api.whatsapp.com/send?phone=" . getSettings()->whatsapp_number . "&text=" . urlencode($message);
                }

                return redirect()->away($chatLink);
            } catch (\Throwable $th) {
                Log::error('Wallet-to-bank manual handoff failed.', [
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'transaction_id' => $transaction->transaction_id,
                    'user_id' => auth()->id(),
                ]);

                return redirect(route('transaction.status', $transaction->transaction_id))
                    ->with('warning', 'Your manual transfer request was created, but the WhatsApp handoff could not be opened. Please contact support with your transaction ID.');
            }
        }

        try {
            $transfer = $this->transferToBankAccount($bank->cbn_code, $request->account_number, $request->account_name, $request['amount'], $transaction);
            $providerStatus = strtolower((string) ($transfer['provider_status'] ?? $transfer['status'] ?? 'failed'));
            $transferStatus = strtolower((string) ($transfer['status'] ?? 'failed'));

            if (in_array($transferStatus, ['success', 'successful', 'completed'], true)) {
                $status = 'success';
                $user_status = 'success';
                $description = 'Wallet to Bank Transfer transaction was completed';
                $balance_after = $request['balance_before'] - $request['total_amount'];
                $failure_reason = null;
            } elseif (in_array($providerStatus, ['pending', 'processing', 'initiated'], true) || $transferStatus === 'pending') {
                $status = 'pending';
                $user_status = 'pending';
                $description = 'Wallet to Bank Transfer is pending provider confirmation.';
                $balance_after = $request['balance_before'] - $request['total_amount'];
                $failure_reason = null;
            } else {
                DB::transaction(function () use ($wallet, $request, $transaction) {
                    $refund = [
                        'customer_id' => $request['customer_id'],
                        'type' => 'credit',
                        'total_amount' => $request['total_amount'],
                        'transaction_id' => $request['transaction_id'],
                        'reason' => 'Wallet to Bank Transfer refund',
                        'payment_method' => 'wallet',
                    ];

                    $wallet->logWallet($refund);
                    $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');
                });

                $status = 'failed';
                $user_status = 'failed';
                $description = 'Wallet to Bank Transfer transaction could not be completed';
                $balance_after = $request['balance_before'];
                $failure_reason = $transfer['error'] ?? 'Wallet to Bank Transfer transaction could not be completed';
            }

            $transaction->update([
                'balance_after' => $balance_after,
                'api_response' => $transfer['api_response'] ?? null,
                'failure_reason' => $failure_reason ?? null,
                'status' => $status ?? '',
                'descr' => $description ?? null,
                'user_status' => $user_status ?? null,
            ]);

            $this->sendTransactionEmail($transaction, auth()->user());

            return redirect(route('transaction.status', $transaction->transaction_id));
        } catch (\Throwable $th) {
            DB::transaction(function () use ($wallet, $request, $transaction, $th) {
                $wallet->logWallet([
                    'customer_id' => $request['customer_id'],
                    'type' => 'credit',
                    'total_amount' => $request['total_amount'],
                    'transaction_id' => $request['transaction_id'],
                    'reason' => 'Wallet to Bank Transfer refund',
                    'payment_method' => 'wallet',
                ]);

                $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');

                $transaction->update([
                    'balance_after' => $request['balance_before'],
                    'status' => 'failed',
                    'user_status' => 'failed',
                    'failure_reason' => $th->getMessage(),
                    'descr' => 'Wallet to Bank Transfer transaction could not be completed',
                ]);
            });

            Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);

            return back()->with('error', 'An error occured, please try again later');
        }
    }

    private function customerCanAccessService(string $service): bool
    {
        $customer = auth()->user()?->customer;

        if (! $customer) {
            return false;
        }

        return match ($service) {
            'wallet2bank' => (bool) ($customer->can_access_w2bank ?? false),
            'a2c' => (bool) ($customer->can_access_a2c ?? false),
            default => true,
        };
    }

    private function serviceUnavailableResponse(string $serviceLabel, string $serviceKey)
    {
        $settings = getSettings();
        $adminWhatsappNumber = preg_replace('/\D+/', '', (string) ($settings->whatsapp_number ?? ''));
        $message = "This service ({$serviceLabel}) is not available for you at the moment, please contact admin to set it up.";
        $whatsappMessage = $serviceLabel . ' access request from ' . (auth()->user()?->email ?? 'customer') . '. Please enable this service for my account.';

        return response()->view(themeView('customer', 'service_unavailable'), [
            'serviceLabel' => $serviceLabel,
            'serviceKey' => $serviceKey,
            'message' => $message,
            'adminWhatsappNumber' => $adminWhatsappNumber,
            'adminWhatsappLink' => filled($adminWhatsappNumber)
                ? 'https://api.whatsapp.com/send?phone=' . $adminWhatsappNumber . '&text=' . urlencode($whatsappMessage)
                : null,
        ], 403);
    }

    private function findRecentWalletToBankDuplicate(Customer $customer, array $request): ?TransactionLog
    {
        $query = TransactionLog::query()
            ->where('customer_id', $customer->id)
            ->where('reason', 'Wallet to Bank Transfer')
            ->where('payment_method', 'wallet')
            ->where('amount', (float) ($request['amount'] ?? 0))
            ->where('bank_id', $request['bank_id'] ?? null)
            ->where('account_number', $request['account_number'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('id');

        if (Schema::hasColumn('transaction_logs', 'transfer_mode') && ! empty($request['transfer_mode'])) {
            $query->where('transfer_mode', $request['transfer_mode']);
        }

        if (Schema::hasColumn('transaction_logs', 'bank_code') && ! empty($request['bank_code'])) {
            $query->where('bank_code', $request['bank_code']);
        }

        return $query->first();
    }

    public function transactionStatus($transaction_id)
    {
        $transaction = TransactionLog::with(['product', 'variation', 'bank'])
            ->where(function ($query) use ($transaction_id) {
                $query->where('transaction_id', $transaction_id)
                    ->orWhere('reference_id', $transaction_id);
            })
            ->firstOrFail();

        return view(themeView('customer', 'transaction_status'), compact('transaction'));
    }

    public function Airtime2CashTransactionStatus($transaction_id)
    {
        $transaction = Airtime2CashTransactions::with('product')
            ->where('transaction_id', $transaction_id)
            ->where('customer_id', auth()->user()->customer->id)
            ->firstOrFail();

        return view(themeView('customer', 'airtime_2_cash_transaction_status'), compact('transaction'));
    }

    public function transactionReceipt($transaction_id)
    {
        $transaction = TransactionLog::with(['product', 'category', 'variation', 'bank'])
            ->where('id', $transaction_id)
            ->firstOrFail()
            ->toArray();

        $receiptView = themeView('customer', 'receipts.transaction_receipt');
        $pdf = Pdf::loadView($receiptView, ['transaction' => $transaction])->setPaper('a4', 'portrait');

        return $pdf->download($transaction['transaction_id'] . '.pdf');
    }

    public function airtime2CashTransactionReceipt($transaction_id)
    {
        $transaction = Airtime2CashTransactions::with(['product:id,name,image', 'customer'])->where('id', $transaction_id)->first()->toArray();

        $pdf = Pdf::loadView('customer.receipts.airtime2cash_transaction_receipt', ['transaction' => $transaction])->setPaper('a4', 'portrait');
        return $pdf->download($transaction['transaction_id'] . '.pdf');
        // return view('customer.receipts.airtime2cash_transaction_receipt', compact('transaction'));
    }



    public function processTransaction($request, $transaction, $product, $variation)
    {
        $failure_reason = '';
        $api = $variation->api ?? $product->api;
        // Get Api
        $query = app("App\Http\Controllers\Providers\KingsVtuController")->query($request, $variation->api ?? $product->api, $variation, $product);

        try {
            //code...
            DB::beginTransaction();
            if (isset($query) && $query['status_code'] == 1) {
                $user = auth()->user();
                $this->referralReward($user->referral, $request['total_amount'], $user->customer->id, $request['transaction_id'], $product->referral_percentage);
                $res = [
                    'status' => $query['status'],
                    'message' => 'Transaction Successful!',
                ];

                $user_status = 'success';
                $balance_after = $request['balance_before'] - $request['total_amount'];
            } else  if (isset($query) && $query['status_code'] == 2) {
                $user = auth()->user();
                // $this->referralReward($user->referral, $request['total_amount'], $user->customer->id, $request['transaction_id'], $product->referral_percentage);
                $res = [
                    'status' => $query['status'],
                    'message' => 'Transaction Pending!',
                ];

                $user_status = 'pending';
                $balance_after = $request['balance_before'] - $request['total_amount'];
            } else if (isset($query) && $query['status_code'] == 0) {
                // Log wallet
                $wallet = new WalletController();
                $request['type'] = 'credit';
                $wallet->logWallet($request);
                $failure_reason = $query['error'] ?? $query['message'];

                // Update Customer Wallet
                $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');
                $balance_after = $request['balance_before'];
                $user_status = 'failed';
            } else {
                $user_status = 'failed';
                $res = [
                    'status' => $query['status'],
                    'message' => 'Transaction Successful!',
                ];

                $balance_after = $request['balance_before'] - $request['total_amount'];
            }

            $extra_info = [];
            $customer_details = BillerLog::where('service_id', $transaction->product->slug)->where('billers_code', $transaction->unique_element)->first();
            if (!empty($customer_details)) {
                $customer_details = json_decode($customer_details->refined_data, true);
            } else {
                $customer_details = [];
            }

            $info = $query['extra_info'] ?? [];
            $extra_info = array_merge($info, $customer_details);
            // Update Transaction
            $transaction->update([
                'balance_after' => $balance_after,
                'request_data' => $query['payload'],
                'api_response' => $query['api_response'] ?? null,
                'failure_reason' => $failure_reason,
                'extras' => $query['extras'] ?? null,
                'status' => $query['status'] ?? 'attention-required',
                'descr' => $query['description'],
                'extra_info' => !empty($extra_info) ? json_encode($extra_info) : null,
                'user_status' => $user_status ?? null
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $wallet = new WalletController();
            $request['type'] = 'credit';
            $wallet->logWallet($request);
            $failure_reason = $query['error'] ?? $query['message'];

            // Update Customer Wallet
            $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');
            $balance_after = $request['balance_before'];

            $transaction->update([
                'balance_after' => $balance_after
            ]);
            // \Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);
        }

        return $transaction;
    }

    public function processAirtime2CashTransaction(Request $request): JsonResponse
    {
        $transaction = Airtime2CashTransactions::with(['product', 'provider', 'customer.user'])
            ->where('transaction_id', $request->transaction_id)
            ->firstOrFail();

        $provider = $transaction->provider;

        if (! $provider) {
            return response()->json([
                'status' => false,
                'message' => 'The transaction provider is not configured.',
            ], 422);
        }

        $providerController = match ($provider->slug) {
            'autosync' => app(AutoSyncController::class),
            default => null,
        };

        if (! $providerController) {
            return response()->json([
                'status' => false,
                'message' => "Unsupported provider: {$provider->slug}",
            ], 422);
        }

        try {
            $providerResponse = $providerController->query(
                transaction: $transaction,
                otp: $request->string('otp')->toString(),
                provider: $provider
            );

            $providerStatus = strtolower((string) data_get(
                $providerResponse,
                'data.transaction.status',
                'failed'
            ));

            $statusCode = match ($providerStatus) {
                'successful' => 1,
                'pending', 'processing', 'initiated' => 2,
                default => 0,
            };

            DB::beginTransaction();

            $transaction = Airtime2CashTransactions::with('customer.user')
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $customer = Customer::query()
                ->whereKey($transaction->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Only prevents duplicate wallet credit; provider response determines success.
            $alreadySettled = $transaction->status === 'successful';
            $balanceBefore = (float) ($customer->wallet ?? 0);
            $balanceAfter = $balanceBefore;

            if ($statusCode === 1 && ! $alreadySettled && (float) $transaction->amount_paid > 0) {
                $wallet = new WalletController();
                $balanceAfter = $balanceBefore + (float) $transaction->amount_paid;

                $wallet->logWallet([
                    'customer_id' => $transaction->customer_id,
                    'type' => 'credit',
                    'total_amount' => (float) $transaction->amount_paid,
                    'transaction_id' => $transaction->transaction_id,
                    'reason' => 'Airtime-to-cash conversion',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ]);

                $wallet->applyCustomerBalanceChange($customer, 'wallet', (float) $transaction->amount_paid, 'credit');
            }

            if ($statusCode === 1 && $alreadySettled) {
                $balanceAfter = $balanceBefore;
            }

            $this->upsertAirtime2CashTransactionLog($transaction, $customer, [
                'status' => $statusCode === 1 ? 'successful' : ($statusCode === 2 ? 'pending' : 'failed'),
                'descr' => $statusCode === 1
                    ? 'Airtime2Cash conversion completed successfully.'
                    : ($statusCode === 2
                        ? 'Airtime2Cash conversion is pending provider confirmation.'
                        : 'Airtime2Cash conversion failed.'),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'provider_status' => $providerStatus,
                'api_response' => $providerResponse,
            ]);

            $transaction->update([
                'provider_status' => $providerStatus,
                'bank_transfer_api_response' => json_encode($providerResponse, JSON_THROW_ON_ERROR),
                'status' => match ($statusCode) {
                    1 => 'successful',
                    2 => 'pending',
                    default => 'failed',
                },

                'decline_reason' => $statusCode === 0
                    ? ($providerResponse['message'] ?? data_get($providerResponse, 'data.transaction.details'))
                    : null,

                'completed_at' => in_array($statusCode, [0, 1], true)
                    ? ($transaction->completed_at ?? now())
                    : $transaction->completed_at,
            ]);

            DB::commit();

            if ($statusCode === 1) {
                return response()->json([
                    'status' => true,
                    'transaction_status' => 'successful',
                    'message' => $providerResponse['message'] ?? 'Airtime conversion completed successfully.',
                    'redirect' => route('customer.airtime2cash.transaction.history'),
                    'data' => [
                        'transaction_id' => $transaction->transaction_id,
                        'provider_status' => $providerStatus,
                    ],
                ]);
            }

            if ($statusCode === 2) {
                return response()->json([
                    'status' => true,
                    'transaction_status' => 'pending',
                    'message' => $providerResponse['message']
                        ?? 'Your airtime conversion is pending. Please do not retry while it is being processed.',
                    'reset_flow' => true,
                    'data' => [
                        'transaction_id' => $transaction->transaction_id,
                        'provider_status' => $providerStatus,
                    ],
                ], 202);
            }

            return response()->json([
                'status' => false,
                'transaction_status' => 'failed',
                'message' => $providerResponse['message']
                    ?? data_get($providerResponse, 'data.transaction.details')
                    ?? 'The airtime conversion could not be completed.',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'provider_status' => $providerStatus,
                ],
            ], 422);
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Airtime-to-cash settlement failed.', [
                'message' => $exception->getMessage(),
                'transaction_id' => $transaction->transaction_id,
                'provider' => $provider->slug,
            ]);

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function resolveWalletAction(int $statusCode, Airtime2CashTransactions $transaction): string
    {
        if ($statusCode !== 1) {
            return 'none';
        }

        return $transaction->payment_method === 'Transfer to Wallet'
            ? 'credit'
            : 'none';
    }

    public function verify(Request $request, $admin = null)
    {
        $variation = Variation::where('id', $request->variation)->first();

        if (in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
            $element = specialVerifiableVariations()[$variation->slug];
        } else {
            $element = $variation->category->unique_element;
        }

        $unique_elementX = ucfirst(str_replace("_", " ", $element));

        if (empty($admin)) {
            $validator = Validator::make($request->all(), [
                'unique_element' => 'required'
            ]);

            if ($validator->fails()) {
                $res = [
                    'status' => '0',
                    'message' => $unique_elementX . ' is required',
                    'title' => 'Please fill all fields',
                ];

                return response()->json($res);
            }
        }


        $product = $variation->product;
        $api = $variation->api;

        $request['product_name'] = $product->name ?? null;
        $request['variation_name'] = $variation->slug ?? null;
        $request['category_id'] = $product->category->id ?? null;
        $request['product_slug'] = $variation->product->slug ?? null;
        $request['network'] = $variation->network ?? null;

        $request['unique_element'] = $request->unique_element;

        $data = [
            'variation' => $variation,
            'product' => $product,
            'api' => $api,
            'request' => $request
        ];

        // Get Api
        $verify = app("App\Http\Controllers\Providers\KingsVtuController")->verify($data);

        if (isset($verify) && $verify['status_code'] == 1) {
            $res = [
                'status' => $verify['status_code'],
                'message' => $verify['message'],
                'title' => $verify['title'],
                'renewal_amount' => $verify['renewal_amount']
            ];

            if (isset($verify['raw_response'])) {
                $this->refineAndLogBiller($verify, $variation->category, $request['unique_element'], $request['product_slug']);
            }
        } else if (isset($query) && $query['status_code'] == 0) {
            $res = [
                'status' => $verify['status_code'],
                'message' => $verify['message'],
                'title' => $verify['title'],
            ];
        } else {
            $res = [
                'status' => $verify['status_code'] ?? 0,
                'message' => $verify['message'] ?? 'Biller not reachable at the moment, please try again later',
                'title' => $verify['title'] ?? 'Not Reachable',
            ];
        }

        if ($request->ajax()) {
            return response()->json($res);
        } else {
            return $res;
        }
    }

    public function checkTransactionPin($request)
    {
        $pin = base64_decode(base64_decode(base64_decode(auth()->user()->transaction_pin)));
        if ($pin == $request->transaction_pin) {
            return true;
        } else {
            return false;
        }
    }

    public function getCustomerDiscount(Request $request)
    {
        if (!empty($request->product_id)) {
            $resource = Product::where('id', $request->product_id)->first();
            $type = 'product';
        }

        if (!empty($request->variation_id)) {
            $resource = Variation::where('id', $request->variation_id)->first();
            $type = 'variation';
        }

        $discount = $this->getDiscount($resource, $type, $request->amount, 'yes');
        // $suffix = (isset($discount['type']) && $discount['type'] == 'percentage') ? number_format($discount['rate']) . '% off' : 'Discounted to ' . getSettings()->currency . number_format($discount['rate'], 2);
        $suffix = '';

        if (!empty($request->raw) && $request->raw == 'yes') {
            return [
                'discount' => $discount['rate'],
                'message' => '<span class="pay">You will pay </span><strong><span class="rate">' . getSettings()->currency . number_format($discount['discounted_price']) . '</span></strong><span class="suffix"></span>',
            ];
        } else {
            return response()->json([
                'discount' => $discount['rate'],
                'message' => '<span class="pay">You will pay </span><strong><span class="rate">' . getSettings()->currency . number_format($discount['discounted_price']) . '</span></strong><span class="suffix"></span>',
            ]);
        }
    }

    private function activeCustomerLevelId(?User $user): ?int
    {
        $level = $user?->customer?->level;

        if (empty($level) || !((bool) ($level->status ?? false))) {
            return null;
        }

        return (int) $level->id;
    }

    public function getDiscount($resource, $type, $amount = null, $getRate = null)
    {
        $discount = 0;
        $level = $this->activeCustomerLevelId(auth()->user());
        $amount = $amount;

        if ($type == 'variation') {
            $findDiscount = Discount::where(['customer_level' => $level, 'variation_id' => $resource->id])->where('price', '>', 0)->first();
            if ($resource->fixed_price == 'Yes') {
                $amount = $resource->system_price;
            }
        }

        if ($type == 'product') {
            $findDiscount = Discount::where(['customer_level' => $level, 'product_id' => $resource->id])->where('price', '>', 0)->first();
        }

        if (!empty($findDiscount) && $findDiscount->price > 0) {
            $price = $findDiscount->price;
            if ($resource->category->discount_type == 'flat') {
                $discount = $price;
                $discounted_price = $discount;
            }

            if ($resource->category->discount_type == 'percentage' && !empty($amount)) {
                $discount = ($price / 100) * $amount;
                $discounted_price = $amount - $discount;
            }
        }

        $discounted_price = floor($discounted_price ?? $amount); // to floor down percentage based discounts
        // $discounted_price = intval(floor($discounted_price ?? $amount)); // to floor down percentage based discounts

        if ($resource->fixed_price == 'Yes') {
            $discounted_price = $discounted_price <= $resource->system_price ? $discounted_price : $resource->system_price;
        }
        $discounted_price = $discounted_price <= 0 ? $resource->system_price : $discounted_price;

        if (!empty($getRate)) {

            $response = [
                'amount' => $amount ?? 0,
                'discount' => $discount ?? 0,
                'discounted_price' => $discounted_price ?? 0,
                'rate' => $price ?? 0,
                'type' => $resource->category->discount_type ?? '',
                'discount_applied' => !empty($discounted_price) ? $amount - $discounted_price : 0,

            ];

            return $response;
        } else {
            return $discount;
        }
    }

    public function validateMeter()
    {
    }

    public function logTransaction($data)
    {
        $pre = [
            'status' => $data['status'] ?? 'initiated',
            'reference_id' => $data['request_id'],
            'transaction_id' => $data['transaction_id'],
            'payment_method' => $data['payment_method'],
            'customer_id' => $data['customer_id'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_name' => $data['customer_name'],
            'discount' => $data['discount'] ?? null,
            'unit_price' => $data['amount'],
            'quantity' => $data['quantity'] ?? 1,
            'total_amount' => $data['total_amount'],
            'amount' => $data['amount'],
            'balance_before' => $data['balance_before'],
            'balance_after' => $data['balance_after'] ?? ($data['balance_before'] - $data['total_amount']),
            'descr' => $data['descr'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'variation_id' => $data['variation_id'] ?? null,
            'variation_name' => $data['variation_name'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unique_element' => $data['unique_element'],
            'ip_address' => $data['ip_address']  ?? Session::get('ip_address') ?? null,
            'domain_name' => Session::get('domain_name') ?? null,
            'app_version' => Session::get('app_version') ?? null,
            'api_id' => $data['api_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'provider_charge' => $data['provider_charge'] ?? null,
            'charge_breakdown' => $data['charge_breakdown'] ?? null,
            'bank_id' => $data['bank_id'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
        ];

        $trans = TransactionLog::create($pre);
        return $trans;
    }

    private function upsertAirtime2CashTransactionLog(
        Airtime2CashTransactions $transaction,
        Customer $customer,
        array $overrides = []
    ): TransactionLog {
        $customerUser = $customer->relationLoaded('user') ? $customer->user : $customer->user()->first();
        $amount = (float) ($transaction->amount_paid ?? 0);
        $balanceBefore = (float) ($overrides['balance_before'] ?? ($customer->wallet ?? 0));
        $status = $overrides['status'] ?? 'pending';
        $balanceAfter = array_key_exists('balance_after', $overrides)
            ? (float) $overrides['balance_after']
            : ($status === 'pending' ? $balanceBefore : $balanceBefore + $amount);

        $payload = [
            'status' => $status,
            'reference_id' => $transaction->transaction_id,
            'transaction_id' => $transaction->transaction_id,
            'payment_method' => $transaction->payment_method ?? 'wallet',
            'customer_id' => $customer->id,
            'customer_email' => $customerUser?->email,
            'customer_phone' => $customerUser?->phone,
            'customer_name' => $customerUser?->firstname ?? $customerUser?->name,
            'discount' => 0,
            'unit_price' => $amount,
            'quantity' => 1,
            'total_amount' => (float) ($transaction->total_amount ?? $amount),
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'descr' => $overrides['descr'] ?? $transaction->description ?? 'Airtime2Cash transaction',
            'product_id' => $transaction->product_id,
            'product_name' => $transaction->product?->name,
            'variation_id' => $transaction->variation_id ?? null,
            'variation_name' => $transaction->variation_name ?? null,
            'category_id' => $transaction->product?->category?->id,
            'unique_element' => 'Airtime2Cash Payment',
            'ip_address' => $overrides['ip_address'] ?? $this->getIpAddress(),
            'domain_name' => $overrides['domain_name'] ?? $this->getDomainName(),
            'app_version' => Session::get('app_version') ?? null,
            'api_id' => $transaction->provider_id ?? $transaction->product?->api_id,
            'reason' => 'Airtime2Cash Payment',
            'provider_charge' => $transaction->amount_charged ?? null,
            'charge_breakdown' => $overrides['charge_breakdown'] ?? null,
            'bank_id' => $transaction->bank_id ?? null,
            'account_name' => $transaction->account_name ?? null,
            'account_number' => $transaction->account_number ?? null,
        ];

        if (Schema::hasColumn('transaction_logs', 'transfer_mode')) {
            $payload['transfer_mode'] = $transaction->transfer_mode ?? null;
        }

        if (array_key_exists('provider_status', $overrides) && Schema::hasColumn('transaction_logs', 'provider_status')) {
            $payload['provider_status'] = $overrides['provider_status'];
        }

        if (array_key_exists('api_response', $overrides)) {
            $payload['api_response'] = is_array($overrides['api_response'])
                ? json_encode($overrides['api_response'], JSON_THROW_ON_ERROR)
                : $overrides['api_response'];
        }

        if (array_key_exists('request_data', $overrides) && Schema::hasColumn('transaction_logs', 'request_data')) {
            $payload['request_data'] = is_array($overrides['request_data'])
                ? json_encode($overrides['request_data'], JSON_THROW_ON_ERROR)
                : $overrides['request_data'];
        }

        return TransactionLog::updateOrCreate(
            ['transaction_id' => $transaction->transaction_id],
            $payload
        );
    }

    public function removeCharsInAmount($code)
    {
        $chars = str_split($code);
        $str2 = "";
        foreach ($chars as $char) {
            if ($char != '#') {
                $str2 .= $char;
            }
        }
        $code = trim(preg_replace('/[^0-9\.]+/i', '', $str2));
        return $code;
    }

    public function customerTransactionHistory(Request $request)
    {
        $transactions = TransactionLog::with(['product', 'variation', 'wallet'])->where('customer_id', auth()->user()->customer->id)->where('status', '!=', 'initiated');

        if (!empty($request->service)) {
            $transactions = $transactions->where('product_id', $request->service);
        }

        if (!empty($request->reason)) {
            $transactions = $transactions->where('reason', $request->reason);
        }

        if (!empty($request->transaction_id)) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if (!empty($request->status)) {
            $transactions = $transactions->where('status', $request->status);
        }

        if (!empty($request->unique_element)) {
            $transactions = $transactions->where('unique_element', 'LIKE', "%" . $request->unique_element . "%");
        }

        if (!empty($request->from) && !empty($request->to)) {
            $from = $request->from . " 00:00:00";
            $to = $request->to . " 23:59:59";
            $transactions = $transactions->whereBetween('created_at', [$from, $to]);
        }

        $transactions = $transactions->orderBy('created_at', 'DESC')->paginate(50);

        $products = Product::where('status', 'active')->where('type', 'general')->get();
        return view(themeView('customer', 'mytransactions'), compact('transactions', 'products'));
    }

    public function customerAirtime2CashTransactionHistory(Request $request)
    {
        $request->validate([
            'transfer_mode' => ['nullable', 'in:manual,auto_share'],
        ]);

        $transactions = Airtime2CashTransactions::with(['product', 'customer'])->where('customer_id', auth()->user()->customer->id);

        if (!empty($request->product_id)) {
            $transactions = $transactions->where('product_id', $request->product_id);
        }

        if (!empty($request->transaction_id)) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if (!empty($request->status)) {
            $transactions = $transactions->where('status', $request->status);
        }

        if (!empty($request->transfer_mode)) {
            $transactions = $transactions->where('transfer_mode', $request->transfer_mode);
        }

        if (!empty($request->from) && !empty($request->to)) {
            $from = $request->from . " 00:00:00";
            $to = $request->to . " 23:59:59";
            $transactions = $transactions->whereBetween('created_at', [$from, $to]);
        }

        $transactions = $transactions->orderBy('created_at', 'DESC')->paginate(50);

        $products = Product::where('type', 'airtime2cash')->where('status', 'active')->orderBy('created_at', 'DESC')->get();

        return view(themeView('customer', 'airtime_to_cash_transactions'), compact('transactions', 'products'));
    }



    public function showTransactionReportPage(Request $request, ExcelService $export)
    {
        if (!empty($request->type)) {
            if ($request->type == 'transaction') {
                $data = TransactionLog::with(['product', 'variation', 'wallet'])->where('customer_id', auth()->user()->customer->id)->where('status', '!=', 'initiated');

                if (!empty($request->category)) {
                    $data = $data->where('category_id', $request->category);
                }

                if (!empty($request->unique_element)) {
                    $transactions = $data->where('unique_element', 'LIKE', "%" . $request->unique_element . "%");
                }

                if (!empty($request->status)) {
                    if ($request->status == 'delivered') {
                        $data = $data->whereIn('status', ['success', 'delivered']);
                    } else {
                        $data = $data->where('status', 'failed');
                    }
                }
            }

            if ($request->type == 'wallet') {
                $data = Wallet::where('customer_id', auth()->user()->customer->id);
            }

            if ($request->type == 'earning') {
                $data = ReferralEarning::where('customer_id', auth()->user()->customer->id);
            }

            if (!empty($request->from) && !empty($request->to)) {
                $from = $request->from . " 00:00:00";
                $to = $request->to . " 23:59:59";
                $data = $data->whereBetween('created_at', [$from, $to]);
            }

            $data = $data->orderBy('created_at', 'DESC')->get()->toArray();
            $format = [];
            foreach ($data as $data) {
                if ($request->type == 'earnings') {
                    $details = Customer::with('user')->where('id', $data['customer_id'])->first();
                    $data['customer_username'] = $details->username;
                }

                if (isset($data['reason'])) {
                    $row['Reason'] = $data['reason'];
                }

                if (isset($data['extras'])) {
                    $row['Extras'] = $data['extras'];
                }

                if (isset($data['product_name'])) {
                    $row['Product Name'] = $data['product_name'];
                }
                if (isset($data['variation_name'])) {
                    $row['Variation Name'] = $data['variation_name'];
                }

                if (isset($data['unique_element'])) {
                    $row['Unique Element'] = $data['unique_element'];
                }

                if (isset($data['descr'])) {
                    $row['Description'] = $data['descr'];
                }

                if (isset($data['payment_method'])) {
                    $row['Payment Method'] = $data['payment_method'];
                }

                if (isset($data['customer_email'])) {
                    $row['Customer Email'] = $data['customer_email'];
                }

                if (isset($data['customer_username'])) {
                    $row['Customer Username'] = $data['customer_username'];
                }

                if (isset($data['customer_phone'])) {
                    $row['Customer Phone'] = $data['customer_phone'];
                }

                $row['Type'] = $data['type'];
                $row['Transaction ID'] = $data['transaction_id'];
                $row['Amount'] = $data['amount'];

                if (isset($data['unit_price'])) {
                    $row['Unit Price'] = $data['unit_price'];
                }

                if (isset($data['provider_charge'])) {
                    $row['Convenience Fee'] = $data['provider_charge'];
                }

                if (isset($data['discount'])) {
                    $row['Discount'] = $data['discount'];
                }

                if (isset($data['total_amount'])) {
                    $row['Total Amount'] = $data['total_amount'];
                }

                if (isset($data['balance_before'])) {
                    $row['Initial Balance'] = $data['balance_before'];
                }

                if (isset($data['balance_after'])) {
                    $row['Final Balance'] = $data['balance_after'];
                }

                $row['Date'] = $data['created_at'];

                $format[] = $row;
            }

            $fileName = $request->type . '_report-' . rand(919, 9999) . '-' . date('Y-m-d H:i:s', time());
            return $export->fastExcelExport($format, $fileName);
        }


        $products = Product::where('status', 'active')->where('type', 'geneeral')->get();
        $categories = Category::where('status', 'active')->get();
        return view(themeView('customer', 'reports'), compact('products', 'categories'));
    }

    function referralReward($ref, $amount, $customer_id, $transaction_id, $referral_percentage)
    {
        if ($ref) {
            if (!empty($referral_percentage)) {
                $sett = getSettings();
                $percentage = $referral_percentage;
                $user = User::where('username', $ref)->first();
                $curUser = auth()->user();

                if ($user) {
                    if ($sett->referral_system_status == 'active') {
                        $cut = $percentage;
                        $cal = ($cut / 100) * $amount;

                        $customer = $user->customer;
                        $current = $customer->referal_wallet;

                        $sum = $current + $cal;
                        $this->logEarnings('credit', $customer->id, $customer_id, $cal, $current, $sum, $transaction_id);
                        $customer->referal_wallet = $sum;
                        $customer->save();
                        $host = env('APP_URL');
                        $rewardMail = <<<__here
                            Dear $user->firstname $user->lastname,

                            Congratulations! We are excited to inform you that you have earned a commission from a transaction made by your referred friend. Your support and engagement in our referral program are truly appreciated.

                            Here are the details of the transaction:

                            Referred Friend's Name: $curUser->firstname

                            Commission Earned: $cal

                            Total Commission Earned: $cal

                            Transaction Details: <a href="$host/downlines/$curUser->id">click here</a>

                            Your dedication to spreading the word about our services is making a real impact, and we are grateful for your continued support. As a token of our appreciation, we have credited your wallet with the earned commission.

                            Thank you once again for being a valued member of our community. We look forward to your continued success in our referral program!
                            __here;

                        logEmails($user->email, 'Referral Commission', $rewardMail);
                    }
                }
            }
        }
    }

    public function logEarnings($type, $customer, $referred, $amount, $before, $after, $transaction_id)
    {
        $ref = ReferralEarning::create([
            'type' => $type,
            'customer_id' => $customer,
            'referred_customer_id' => $referred,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'transaction_id' => $transaction_id,
        ]);

        return $ref;
    }

    public function transView(Request $request)
    {
        $request->validate([
            'api' => ['nullable', 'integer'],
            'service' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:delivered,success,failed,attention-required'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $baseQuery = TransactionLog::whereNotNull('product_id');
        $metrics = (clone $baseQuery)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('delivered', 'success') THEN amount ELSE 0 END), 0) AS successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) AS failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'attention-required' THEN amount ELSE 0 END), 0) AS attention_required")
            ->first();

        $transactions = $baseQuery->with(['category', 'variation', 'api', 'airtime2cash'])->latest();
        $products = Product::orderBy('display_name')->get(['id', 'display_name']);
        $apis = API::query()->orderBy('name')->get(['id', 'name', 'slug']);

        if ($request->email) {
            $transactions->where('customer_email', 'like', '%' . trim($request->email) . '%');
        }

        if ($request->phone) {
            $transactions->where('customer_phone', 'like', '%' . trim($request->phone) . '%');
        }

        if ($request->service) {
            $transactions->where('product_id', $request->service);
        }
        if ($request->api) {
            $transactions->where('api_id', $request->api);
        }
        if ($request->transaction_id) {
            $transactions->where('transaction_id', 'like', '%' . trim($request->transaction_id) . '%');
        }
        if ($request->unique_element) {
            $transactions->where('unique_element', 'like', '%' . trim($request->unique_element) . '%');
        }
        if ($request->status) {
            $transactions->where('status', $request->status);
        }

        if ($request->from && $request->to) {
            $transactions->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $transactions->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $transactions->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $transactions = $transactions->paginate(20)->withQueryString();

        return view('admin.transaction.index', [
            'transactions' => $transactions,
            'products' => $products,
            'apis' => $apis,
            'success' => $metrics->successful,
            'failed' => $metrics->failed,
            'attention_required' => $metrics->attention_required,
            'query' => $request->query(),
        ]);
    }

    public function walletTransView(Request $request)
    {
        $request->validate([
            'type' => ['nullable', 'in:credit,debit'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $metrics = Wallet::selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS credit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS debit")
            ->first();
        $transactions = Wallet::with(['customer.user:id,firstname,middlename,lastname,email,phone', 'airtime2cash', 'transaction_log:id,transaction_id'])->latest();

        if ($request->email) {
            $email = trim($request->email);
            $transactions->whereHas('customer.user', fn ($query) => $query->where('email', 'like', '%' . $email . '%'));
        }

        if ($request->transaction_id) {
            $transactions->where('transaction_id', 'like', '%' . trim($request->transaction_id) . '%');
        }

        if ($request->type) {
            $transactions->where('type', $request->type);
        }

        if ($request->from && $request->to) {
            $transactions->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $transactions->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $transactions->where('created_at', '<=', $request->to . ' 23:59:59');
        }
        $transactions = $transactions->paginate(20)->withQueryString();

        return view('admin.transaction.wallet_log', [
            'transactions' => $transactions,
            'debit' => $metrics->debit,
            'credit' => $metrics->credit,
            'query' => $request->query(),
        ]);
    }

    public function walletFundingLogView(Request $request)
    {
        $request->validate([
            'payment_provider' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:delivered,success,failed,attention-required'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $baseQuery = TransactionLog::query()
            ->with(['customer.user:id,firstname,lastname,email,phone', 'api:id,name,slug'])
            ->where('unique_element', 'WALLET-FUNDING')
            ->whereNotNull('api_id');
        $metrics = (clone $baseQuery)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('delivered', 'success') THEN amount ELSE 0 END), 0) AS successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) AS failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'attention-required' THEN amount ELSE 0 END), 0) AS attention_required")
            ->selectRaw("COUNT(*) AS total")
            ->first();
        $transactions = $baseQuery->latest();
        $providers = API::query()->where('is_payment_gateway', true)->orderBy('name')->get(['id', 'name', 'slug']);

        if ($request->email) {
            $transactions->where('customer_email', 'like', '%' . trim($request->email) . '%');
        }

        if ($request->transaction_id) {
            $transactions->where('transaction_id', 'like', '%' . trim($request->transaction_id) . '%');
        }

        if ($request->payment_provider) {
            $transactions->where('api_id', $request->payment_provider);
        }

        if ($request->status) {
            $transactions->where('status', $request->status);
        }

        if ($request->from && $request->to) {
            $transactions->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $transactions->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $transactions->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $transactions = $transactions->paginate(20)->withQueryString();

        return view('admin.transaction.wallet_funding', [
            'providers' => $providers,
            'transactions' => $transactions,
            'success' => $metrics->successful,
            'failed' => $metrics->failed,
            'attention_required' => $metrics->attention_required,
            'total' => $metrics->total,
            'query' => $request->query(),
        ]);
    }

    public function airtimeToCashTransactions(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,declined'],
            'transfer_mode' => ['nullable', 'in:manual,auto_share'],
            'product_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $baseQuery = Airtime2CashTransactions::where('type', 'credit');
        $metrics = (clone $baseQuery)
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN amount_charged ELSE 0 END), 0) AS conversion_income")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_paid ELSE 0 END), 0) AS pending_value")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) AS declined_count")
            ->selectRaw("SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) AS today_count")
            ->first();
    // dd($metrics);
        $transactions = $baseQuery->with(['product:id,name,display_name', 'customer.user:id,firstname,middlename,lastname,email,phone']);

        if ($request->email) {
            $email = trim($request->email);
            $transactions->whereHas('customer.user', function ($query) use ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            });
        }

        if ($request->transaction_id) {
            $transactions->where('transaction_id', 'like', '%' . trim($request->transaction_id) . '%');
        }

        if ($request->status) {
            $transactions->where('status', $request->status);
        }

        if ($request->transfer_mode) {
            $transactions->where('transfer_mode', $request->transfer_mode);
        }

        if ($request->product_id) {
            $transactions->where('product_id', $request->product_id);
        }

        if ($request->from && $request->to) {
            $transactions->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59',
            ]);
        } elseif ($request->from) {
            $transactions->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $transactions->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $transactions = $transactions
            ->with(['product', 'provider', 'wallets', 'transactionLog'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'pending' THEN created_at END ASC")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $products = Product::where('type', 'airtime2cash')->orderBy('name')->get(['id', 'name']);

        return view('admin.transaction.airtime2cash_transactions', [
            'transactions' => $transactions,
            'metrics' => $metrics,
            'products' => $products,
            'query' => $request->query(),
        ]);
    }

    public function walletEarningView(Request $request)
    {
        $request->validate([
            'type' => ['nullable', 'in:credit,debit'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $metrics = ReferralEarning::selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS credit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS debit")
            ->selectRaw('COUNT(*) AS total')
            ->first();
        $transactions = ReferralEarning::with(['customer.user:id,firstname,middlename,lastname,email,phone', 'referredCustomer.user:id,firstname,middlename,lastname,email,phone', 'transaction:id,transaction_id'])->latest();


        if ($request->upline_email) {
            $email = trim($request->upline_email);
            $transactions->whereHas('customer.user', fn ($query) => $query->where('email', 'like', '%' . $email . '%'));
        }

        if ($request->downline_email) {
            $email = trim($request->downline_email);
            $transactions->whereHas('referredCustomer.user', fn ($query) => $query->where('email', 'like', '%' . $email . '%'));
        }

        if ($request->transaction_id) {
            $transactions->where('transaction_id', 'like', '%' . trim($request->transaction_id) . '%');
        }

        if ($request->type) {
            $transactions->where('type', $request->type);
        }

        if ($request->from && $request->to) {
            $transactions->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $transactions->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $transactions->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $transactions = $transactions->paginate(20)->withQueryString();

        return view('admin.transaction.earning_log', [
            'transactions' => $transactions,
            'success' => $metrics->credit,
            'failed' => $metrics->debit,
            'total' => $metrics->total,
            'query' => $request->query(),
        ]);
    }

    public function singleTransactionView(TransactionLog $transaction)
    {
        $transaction->loadMissing(['bank', 'api']);

        return view('admin.transaction.single_transaction', compact('transaction'));
    }

    public function singleAirtimeTransactionView(Airtime2CashTransactions $transaction)
    {
        $transaction->loadMissing(['product', 'provider', 'customer.user', 'wallets', 'transactionLog']);

        $settings = getSettings();
        $verificationProviderId = $settings?->bank_verification_provider_id ?: $settings?->bank_transfer_provider_id;
        $verificationProvider = $verificationProviderId
            ? API::query()
                ->whereKey($verificationProviderId)
                ->where('status', 'active')
                ->first()
            : null;
        $banks = $verificationProvider ? getWalletToBankBanks($verificationProvider) : collect();
        return view('admin.transaction.single_airtime2cash_transaction', compact('transaction', 'banks'));
    }

    public function requeryAirtimeTransaction(Airtime2CashTransactions $transaction): JsonResponse
    {
        $transaction->loadMissing(['provider']);

        $providerPayload = $transaction->provider_response;

        if ($transaction->provider?->slug === 'autosync') {
            try {
                $providerPayload = app(AutoSyncService::class)->queryTransaction(
                    $transaction,
                    $transaction->provider
                );
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => false,
                    'message' => $th->getMessage(),
                    'transaction_status' => $transaction->status,
                    'provider_status' => $transaction->provider_status ?? $transaction->status ?? 'pending',
                    'provider' => $transaction->provider?->name,
                    'response' => $providerPayload,
                ], 422);
            }
        } elseif (! is_array($providerPayload) && filled($transaction->bank_transfer_api_response)) {
            $decodedBankResponse = json_decode((string) $transaction->bank_transfer_api_response, true);
            $providerPayload = is_array($decodedBankResponse) ? $decodedBankResponse : $providerPayload;
        }

        $providerStatus = strtolower((string) data_get(
            $providerPayload,
            'data.transaction.status',
            data_get($providerPayload, 'provider_status', $transaction->provider_status ?? $transaction->status ?? 'pending')
        ));

        return response()->json([
            'status' => true,
            'message' => 'Airtime-to-cash provider status loaded successfully.',
            'transaction_status' => $transaction->status,
            'provider_status' => $providerStatus,
            'provider' => $transaction->provider?->name,
            'response' => $providerPayload,
        ]);
    }

    function debitCustomerPage()
    {
        return view('admin.transaction.debit_customer');
    }

    function creditCustomerPage()
    {
        return view('admin.transaction.credit_customer');
    }

    function processCreditDebit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric',
            'reason' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user)
            return back()->with('error', 'Account not found!');
        if (str_contains(url()->previous(), 'debit'))
            $type = 'debit';
        else if (str_contains(url()->previous(), 'credit'))
            $type = 'credit';

        if (!$type)
            return back()->with('error', 'Hmph, something went wrong!');

        $controller = new TransactionController();
        $amount = $controller->removeCharsInAmount($request->amount);
        $currAmount = walletBalance($user);

        if ($type == 'credit') {
            $data['balance_after'] = $currAmount + $amount;
        } else {
            $data['balance_after'] = $currAmount - $amount;
        }

        $requestId = $controller->generateRequestId();
        $tid = 'A2C-' . $requestId;
        $reason = $type == 'debit' ? 'ADMIN-DEBIT' : 'ADMIN-CREDIT';
        try {
            //code...
            $data['type'] = $type;
            $data['customer_id'] = $user->customer->id;
            $data['transaction_id'] = $tid;
            $data['request_id'] = $requestId;
            $data['payment_method'] = 'wallet';
            $data['balance_before'] = $currAmount;
            $data['amount'] = $amount;
            $data['total_amount'] = $amount;
            $data['customer_email'] = $user->email;
            $data['customer_phone'] = $user->phone;
            $data['customer_name'] = $user->firstname;
            $data['unique_element'] = 'wallet';
            $data['discount'] = 0;
            $data['unit_price'] = $amount;
            $data['descr'] = $request->reason;
            $data['reason'] = $reason;
            $data['status'] = 'delivered';
            $data['admin_id'] = auth()->user()->admin->id;

            $controller->logTransaction($data);

            DB::transaction(function () use ($type, $amount, $tid, $user, $reason) {
                $wallet = new WalletController();
                $wallet->logWallet([
                    'customer_id' => $user->customer->id,
                    'type' => $type,
                    'total_amount' => $amount,
                    'reason' => $reason,
                    'transaction_id' => $tid,
                    'payment_method' => 'ADMIN-FUNDING',
                ]);
                $wallet->updateCustomerWallet($user, $amount, $type);
            });
            $sign = getSettings()->currency;
            return back()->with('message', "Customer wallet has been {$type}ed with {$sign}" . number_format($amount));
        } catch (\Exception $e) {

            return back()->with('error', 'An error occured' . $e->getMessage() . $e->getLine() . $e->getFile());
        }
    }

    public function resolvePendingTransactionAction(Request $request, TransactionLog $transaction)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:credit_customer,failed,successful,process,authorize_monnify,resend_monnify_otp'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
            'authorization_code' => ['nullable', 'string', 'max:20'],
        ]);

        $transaction->loadMissing(['api', 'customer.user', 'bank', 'product']);
        $status = strtolower((string) ($transaction->status ?? ''));
        $providerStatus = $this->transactionProviderStatus($transaction);
        $providerSlug = strtolower((string) ($transaction->api?->slug ?? ''));
        $isMonnifyPendingAuthorization = $providerSlug === 'monnify' && $providerStatus === 'pending_authorization';

        if (! in_array($status, ['pending', 'initiated', 'processing'], true) && ! ($isMonnifyPendingAuthorization && in_array($validated['action'], ['authorize_monnify', 'resend_monnify_otp'], true))) {
            return back()->with('error', 'Only pending transactions can be resolved from this modal.');
        }

        try {
            return match ($validated['action']) {
                'credit_customer' => $this->resolvePendingTransactionByCredit(
                    $transaction,
                    (float) ($validated['amount'] ?? $transaction->total_amount ?? $transaction->amount ?? 0),
                    $validated['reason'] ?? 'Pending transaction refunded by ADMIN'
                ),
                'failed' => $this->resolvePendingTransactionByStatus(
                    $transaction,
                    'failed',
                    $validated['reason'] ?? 'Manually marked as failed by ADMIN'
                ),
                'successful' => $this->resolvePendingTransactionByStatus(
                    $transaction,
                    'success',
                    $validated['reason'] ?? 'Manually marked as successful by ADMIN'
                ),
                'process' => $this->resolvePendingTransactionByProvider($transaction),
                'authorize_monnify' => $this->authorizePendingMonnifyTransaction(
                    $transaction,
                    (string) ($validated['authorization_code'] ?? '')
                ),
                'resend_monnify_otp' => $this->resendPendingMonnifyOtp($transaction),
                default => back()->with('error', 'Unsupported resolution action.'),
            };
        } catch (Throwable $exception) {
            return back()->with('error', 'Unable to resolve transaction: ' . $exception->getMessage());
        }
    }

    private function resolvePendingTransactionByCredit(TransactionLog $transaction, float $amount, string $reason)
    {
        $user = $transaction->customer?->user;

        if (! $user || ! $user->customer) {
            return back()->with('error', 'Unable to locate the customer wallet for this transaction.');
        }

        if ($amount <= 0) {
            return back()->with('error', 'Refund amount must be greater than zero.');
        }

        $wallet = new WalletController();
        $balanceBefore = $wallet->getWalletBalance($user);

        $refundRequest = [
            'customer_id' => $user->customer->id,
            'type' => 'credit',
            'total_amount' => $amount,
            'transaction_id' => $transaction->transaction_id,
            'reason' => $reason,
            'payment_method' => 'ADMIN-REFUND',
        ];

        $wallet->logWallet($refundRequest);
        $wallet->updateCustomerWallet($user, $amount, 'credit');

        $this->markTransactionResolved(
            $transaction,
            'failed',
            $reason,
            $reason,
            $balanceBefore + $amount,
        );

        return back()->with('message', 'Customer has been credited and the transaction was closed.');
    }

    private function resolvePendingTransactionByStatus(TransactionLog $transaction, string $status, string $reason)
    {
        $this->markTransactionResolved(
            $transaction,
            $status,
            $reason,
            $status === 'failed' ? $reason : null,
            $transaction->balance_after,
        );

        return back()->with('message', 'Transaction updated successfully.');
    }

    private function resolveTransactionProvider(TransactionLog $transaction, bool $allowFallback = true): ?API
    {
        $provider = $transaction->api ?: API::query()->find($transaction->api_id);

        if ($provider) {
            return $provider;
        }

        if (! $allowFallback) {
            return null;
        }

        return API::query()
            ->whereKey(getSettings()->bank_transfer_provider_id)
            ->where('status', 'active')
            ->first();
    }

    private function requeryPendingTransactionWithProvider(TransactionLog $transaction, API $provider): array|null
    {
        $controller = resolveProviderController($provider);

        if (! $controller) {
            return null;
        }

        $transactionType = strtolower((string) ($transaction->product?->type ?? $transaction->unique_element ?? $transaction->reason ?? ''));

        if ($transactionType === 'wallet2bank' || str_contains($transactionType, 'wallet to bank')) {
            if (! method_exists($controller, 'requery')) {
                return null;
            }

            return (array) $controller->requery($transaction);
        }

        if (method_exists($controller, 'verifyTransaction')) {
            $reference = $transaction->reference_id
                ?: $transaction->transaction_reference
                ?: $transaction->request_id
                ?: $transaction->transaction_id;

            return (array) $controller->verifyTransaction((string) $reference);
        }

        return null;
    }

    private function applyProviderVerificationResult(TransactionLog $transaction, array $response, ?string $resolutionSource = null, bool $persistApiResponse = true): array
    {
        if (($response['status'] ?? null) === 'skipped') {
            return [
                'status' => 'skipped',
                'message' => $response['message'] ?? 'Transaction skipped.',
            ];
        }

        $providerStatus = strtolower((string) data_get($response, 'provider_status', data_get($response, 'status', 'pending')));
        $isSuccessful = in_array($providerStatus, ['successful', 'success', 'completed'], true)
            || (bool) data_get($response, 'status', false) === true;
        $isFailed = in_array($providerStatus, ['failed', 'rejected', 'declined', 'error'], true);
        $sourceNote = $resolutionSource ? '[' . $resolutionSource . '] ' : '';

        if ($isSuccessful) {
            $bulkMeta = $resolutionSource ? [
                'resolution_source' => trim($resolutionSource),
                'resolution_note' => $sourceNote . 'Transaction processed successfully after provider verification.',
            ] : null;

        $this->markTransactionResolved(
            $transaction,
            'success',
            'Transaction processed successfully after provider verification.',
            null,
            $transaction->balance_after,
            $providerStatus,
            $persistApiResponse ? $response : null,
            null,
            $bulkMeta,
            $resolutionSource === 'CRON/System' ? now() : null,
        );

            return [
                'status' => 'success',
                'message' => 'Transaction processed successfully.',
            ];
        }

        if ($isFailed) {
            $this->refundResolvedTransactionIfNeeded($transaction, 'Transaction failed after provider verification.');
            $bulkMeta = $resolutionSource ? [
                'resolution_source' => trim($resolutionSource),
                'resolution_note' => $sourceNote . data_get($response, 'message', 'Transaction failed after provider verification.'),
            ] : null;

            $this->markTransactionResolved(
                $transaction,
                'failed',
                'Transaction failed after provider verification.',
                $sourceNote . data_get($response, 'message', 'Transaction failed after provider verification.'),
                $transaction->balance_before,
                $providerStatus,
                $persistApiResponse ? $response : null,
                null,
                $bulkMeta,
                $resolutionSource === 'CRON/System' ? now() : null,
            );

            return [
                'status' => 'failed',
                'message' => 'Transaction failed after provider verification.',
            ];
        }

        $pendingDescription = 'Provider still returned a pending response after requery.';
        $pendingExtras = $sourceNote . $pendingDescription;
        $bulkMeta = $resolutionSource ? [
            'resolution_source' => trim($resolutionSource),
            'resolution_note' => $pendingExtras,
            'provider_message' => data_get($response, 'message', $pendingDescription),
        ] : null;

        $this->markTransactionResolved(
            $transaction,
            'pending',
            $pendingDescription,
            $sourceNote . data_get($response, 'message', $pendingDescription),
            $transaction->balance_after,
            $providerStatus,
            $persistApiResponse ? $response : null,
            null,
            $bulkMeta,
            null,
        );

        return [
            'status' => 'pending',
            'message' => 'Provider still returned a pending response. The transaction remains pending.',
        ];
    }

    private function resolvePendingTransactionByProvider(TransactionLog $transaction)
    {
        $provider = $this->resolveTransactionProvider($transaction);

        if (! $provider) {
            return back()->with('error', 'No active provider is attached to this transaction.');
        }

        $response = $this->requeryPendingTransactionWithProvider($transaction, $provider);

        if (! is_array($response)) {
            return back()->with('error', 'This provider does not support transaction processing.');
        }

        $result = $this->applyProviderVerificationResult($transaction, $response);

        return match ($result['status'] ?? 'pending') {
            'success' => back()->with('message', 'Transaction processed successfully.'),
            'failed' => back()->with('message', 'Transaction failed after provider verification.'),
            default => back()->with('warning', 'Provider still returned a pending response. The transaction remains pending.'),
        };
    }

    public function requeryPendingTransactionsByApi(Request $request, ?API $api = null, ?int $pick = null)
    {
        $pickValue = $request->has('pick') ? (int) $request->input('pick') : $pick;
        $limit = is_numeric($pickValue) && (int) $pickValue > 0
            ? min((int) $pickValue, 500)
            : null;

        $query = TransactionLog::with(['product', 'variation', 'bank', 'customer.user', 'api'])
            ->where('status', 'pending')
            ->orderBy('id', 'ASC');

        if ($api) {
            $query->where('api_id', $api->id);
        }

        if ($limit !== null) {
            $query->take($limit);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return back()->with('warning', $api
                ? 'No pending transactions were found for this provider.'
                : 'No pending transactions were found.');
        }

        $summary = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'pending' => 0,
            'skipped' => 0,
        ];

        foreach ($transactions as $transaction) {
            try {
                $provider = $this->resolveTransactionProvider($transaction, false);

                if (! $provider) {
                    $summary['skipped']++;
                    continue;
                }

                $response = $this->requeryPendingTransactionWithProvider($transaction, $provider);

                if (! is_array($response)) {
                    $summary['skipped']++;
                    continue;
                }

                $result = $this->applyProviderVerificationResult($transaction, $response, 'CRON/System', false);
                $summary['processed']++;
                $summary[$result['status'] ?? 'pending'] = ($summary[$result['status'] ?? 'pending'] ?? 0) + 1;
            } catch (\Throwable $exception) {
                $summary['skipped']++;
                Log::warning('Bulk provider requery failed for a transaction.', [
                    'api_id' => $api->id,
                    'transaction_id' => $transaction->transaction_id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('message', sprintf(
            'Bulk requery completed%s. Processed: %d, Successful: %d, Failed: %d, Pending: %d, Skipped: %d.',
            $api ? ' for ' . $api->name : '',
            $summary['processed'],
            $summary['successful'],
            $summary['failed'],
            $summary['pending'],
            $summary['skipped'],
        ));
    }

    private function authorizePendingMonnifyTransaction(TransactionLog $transaction, string $authorizationCode)
    {
        $provider = $transaction->api ?: API::query()
            ->whereKey(getSettings()->bank_transfer_provider_id)
            ->where('status', 'active')
            ->first();

        if (! $provider || strtolower((string) ($provider->slug ?? '')) !== 'monnify') {
            return back()->with('error', 'This transaction is not attached to Monnify.');
        }

        if ($this->transactionProviderStatus($transaction) !== 'pending_authorization') {
            return back()->with('error', 'This Monnify transaction is not waiting for OTP authorization.');
        }

        if (blank($authorizationCode)) {
            return back()->with('error', 'Please enter the OTP sent to the Monnify registered email address.');
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'authorizeTransfer')) {
            return back()->with('error', 'Monnify OTP authorization is not available on this provider controller.');
        }

        if (! method_exists($controller, 'singleTransferStatus')) {
            return back()->with('error', 'Monnify transfer status lookup is not available on this provider controller.');
        }

        $reference = $this->monnifyTransferReference($transaction);
        $authorizationResponse = $controller->authorizeTransfer($reference, $authorizationCode);
        $statusResponse = $controller->singleTransferStatus($reference);
        $response = $statusResponse;

        $providerStatus = strtolower((string) data_get($statusResponse, 'provider_status', data_get($statusResponse, 'status', 'failed')));
        $responseStatus = strtolower((string) data_get($statusResponse, 'status', 'failed'));
        $authorizationError = data_get($authorizationResponse, 'error', data_get($authorizationResponse, 'message'));
        $statusError = data_get($statusResponse, 'error', data_get($statusResponse, 'message'));
        $flashError = filled($authorizationError)
            ? $authorizationError
            : (filled($statusError) ? $statusError : 'Monnify OTP authorization failed.');
        $isSuccessful = $responseStatus === 'success'
            && in_array($providerStatus, ['successful', 'success', 'completed', 'authorized'], true);
        $isFailed = in_array($providerStatus, ['failed', 'rejected', 'declined', 'error', 'expired'], true)
            || $responseStatus === 'failed';

        if ($isSuccessful) {
            $this->markTransactionResolved(
                $transaction,
                'success',
                'Transfer completed successfully.',
                null,
                $transaction->balance_after,
                $providerStatus,
                $response,
            );

            return back()->with('message', 'Monnify transfer authorized successfully.');
        }

        if ($isFailed) {
            $this->refundResolvedTransactionIfNeeded($transaction, 'Monnify transfer failed after OTP authorization.');
            $this->markTransactionResolved(
                $transaction,
                'failed',
                'Transfer could not be completed.',
                $flashError,
                $transaction->balance_before,
                $providerStatus,
                $response,
            );

            return back()->with('error', $flashError);
        }

        return filled($authorizationError)
            ? back()->with('error', $authorizationError)
            : back()->with('warning', 'Transfer is still awaiting authorization.');
    }

    private function resendPendingMonnifyOtp(TransactionLog $transaction)
    {
        $provider = $transaction->api ?: API::query()
            ->whereKey(getSettings()->bank_transfer_provider_id)
            ->where('status', 'active')
            ->first();

        if (! $provider || strtolower((string) ($provider->slug ?? '')) !== 'monnify') {
            return back()->with('error', 'This transaction is not attached to Monnify.');
        }

        if ($this->transactionProviderStatus($transaction) !== 'pending_authorization') {
            return back()->with('error', 'Only Monnify transfers awaiting authorization can request a new OTP.');
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'resendOtp')) {
            return back()->with('error', 'Monnify OTP resend is not available on this provider controller.');
        }

        $reference = $this->monnifyTransferReference($transaction);
        $response = $controller->resendOtp($reference);
        $providerStatus = strtolower((string) data_get($response, 'provider_status', data_get($response, 'status', 'failed')));
        $isSuccessful = in_array($providerStatus, ['successful', 'success', 'resent'], true)
            || (bool) data_get($response, 'status', false) === true;

        if ($isSuccessful) {
            return back()->with('message', 'Monnify OTP has been resent to the registered email address.');
        }

        return back()->with('error', data_get($response, 'error', data_get($response, 'message', 'Unable to resend the Monnify OTP at the moment.')));
    }

    private function refundResolvedTransactionIfNeeded(TransactionLog $transaction, string $reason): void
    {
        if (strtolower((string) ($transaction->type ?? '')) !== 'debit') {
            return;
        }

        $user = $transaction->customer?->user;

        if (! $user || ! $user->customer) {
            return;
        }

        $amount = (float) ($transaction->total_amount ?? $transaction->amount ?? 0);

        if ($amount <= 0) {
            return;
        }

        $wallet = new WalletController();
        $wallet->logWallet([
            'customer_id' => $user->customer->id,
            'type' => 'credit',
            'total_amount' => $amount,
            'transaction_id' => $transaction->transaction_id,
            'reason' => $reason,
            'payment_method' => 'ADMIN-REFUND',
        ]);
        $wallet->updateCustomerWallet($user, $amount, 'credit');
    }

    private function markTransactionResolved(TransactionLog $transaction, string $status, string $descr, ?string $failureReason = null, ?float $balanceAfter = null, ?string $providerStatus = null, mixed $apiResponse = null, ?string $extras = null, ?array $extraInfo = null, $resolutionDate = null): void
    {
        $updates = [
            'status' => $status,
            'user_status' => $status,
            'descr' => $descr,
            'failure_reason' => $failureReason,
            'admin_id' => data_get(auth()->user(), 'admin.id'),
            'balance_after' => $balanceAfter ?? $transaction->balance_after,
        ];

        if ($providerStatus !== null && Schema::hasColumn('transaction_logs', 'provider_status')) {
            $updates['provider_status'] = $providerStatus;
        }

        if ($apiResponse !== null) {
            $updates['api_response'] = is_array($apiResponse) ? json_encode($apiResponse) : $apiResponse;
        }

        if ($extras !== null) {
            $updates['extras'] = $extras;
        }

        if ($extraInfo !== null) {
            $currentExtraInfo = $this->decodedTransactionExtraInfo($transaction);
            $updates['extra_info'] = json_encode(array_merge($currentExtraInfo, $extraInfo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($resolutionDate !== null && Schema::hasColumn('transaction_logs', 'resolution_date')) {
            $updates['resolution_date'] = $resolutionDate;
        }

        $transaction->update($updates);
    }

    private function monnifyTransferReference(TransactionLog $transaction): string
    {
        $requestData = $this->decodedTransactionRequestData($transaction);
        $responseData = $this->decodedTransactionApiResponse($transaction);

        return (string) (
            data_get($responseData, 'responseBody.reference')
            ?: data_get($responseData, 'responseBody.paymentReference')
            ?: data_get($responseData, 'reference')
            ?: data_get($requestData, 'reference')
            ?: data_get($requestData, 'transaction_id')
            ?: data_get($transaction, 'transaction_id')
        );
    }

    private function decodedTransactionRequestData(TransactionLog $transaction): array
    {
        $requestData = $transaction->request_data ?? [];

        if (is_array($requestData)) {
            return $requestData;
        }

        $decoded = json_decode((string) $requestData, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodedTransactionApiResponse(TransactionLog $transaction): array
    {
        $apiResponse = $transaction->api_response ?? [];

        if (is_array($apiResponse)) {
            return $apiResponse;
        }

        $decoded = json_decode((string) $apiResponse, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodedTransactionExtraInfo(TransactionLog $transaction): array
    {
        $extraInfo = $transaction->extra_info ?? [];

        if (is_array($extraInfo)) {
            return $extraInfo;
        }

        $decoded = json_decode((string) $extraInfo, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function transactionProviderStatus(TransactionLog $transaction): string
    {
        $apiResponse = $this->decodedTransactionApiResponse($transaction);
        $providerStatus = data_get($apiResponse, 'responseBody.status')
            ?: data_get($apiResponse, 'provider_status')
            ?: data_get($apiResponse, 'status')
            ?: data_get($transaction, 'provider_status')
            ?: '';

        return strtolower((string) $providerStatus);
    }

    public function queryWallet(Request $request, TransactionLog $transactionlog)
    {
        $type = $request->type;
        $check = Wallet::where('transaction_id', $transactionlog->transaction_id)
            ->where('type', $type)
            ->get();

        $type = strtoupper($request->type);
        $message = '';

        if ($check->count() > 0) {
            $status = 'success';
            $message .= '<h4 align="center" style="color:green"><b>QUERY SUCCESSFUL</b><br><span class="fa fa-check-circle text-success mr-1" style="font-size:19px">' . $check->count() . '</span></h4><hr style="margin:6px 0px 6px 0px"><table><tbody>';

            foreach ($check as $wallet) {
                $message .= '<tr><th>TransID:</th>
                <td>&nbsp;' . $wallet->transaction_id . '</td>
                </tr>
                <tr><th>Amt:</th>
                <td>&nbsp;' . getSettings()->currency . number_format($wallet->amount, 2) . '</td>
                </tr>
                <tr><th>Date:</th>
                <td>&nbsp;' . $wallet->created_at . '</td>
                <tr><th>Reason:</th>
                <td>&nbsp;' . $wallet->reason . '</td>
                </tr>';
            }

            $message .= '</tbody></table><br><center><button class="btn btn-success btn-sm">' . $type . ' DONE</button></center></div>';
        } else {
            $status = 'failed';
            $message .= '<h4 align="center" style="color:red"><b>QUERY FAILED</b></h4><hr style="margin:6px 0px 6px 0px"><center><button class="btn btn-danger btn-sm">' . $type . ' NOT DONE</button></center>';
        }

        $ret = [
            'status' => $status,
            'message' => $message
        ];

        return response()->json($ret);
    }

    public function requery($transactionlog = null)
    {
        if (!$transactionlog) return ['status' => 'failed'];

        $trans = TransactionLog::find($transactionlog);
        if (!$trans) return ['status' => 'failed', 'message' => 'Transaction not found!'];

        if ($trans->product->type == 'wallet2bank') {
            $provider = $trans->api ?: API::query()
                ->whereKey(getSettings()->bank_transfer_provider_id)
                ->where('status', 'active')
                ->first();

            $controller = resolveProviderController($provider);
            $query = $controller && method_exists($controller, 'requery')
                ? $controller->requery($trans)
                : ['status' => 'failed', 'message' => 'No supported bank transfer provider found.'];
        } else {
            if ($trans->reason == 'WALLET-FUNDING') {
                $provider = $trans->provider ?: $trans->api;
                $providerSlug = strtolower((string) ($provider?->slug ?? ''));

                if ($providerSlug === 'monnify') {
                    $monnify = new MonnifyController($provider);
                    return $monnify->verifyTransaction($trans->transaction_reference);
                }

                if ($providerSlug === 'squad') {
                    $squad = new SquadController($provider);
                    return $squad->verifyTransaction($trans->transaction_reference);
                }
            }else{
                $query = app("App\Http\Controllers\Providers\KingsVtuController")->requery($trans);
            }
        }

        return $query;
    }


    public function requeryCallback(Request $request, $reference)
    {
        if (! $request->ajax() && ! $request->expectsJson()) {
            return back()->with('message', 'Use the Query button on the callback analysis page to view the response.');
        }

        $transaction = ReservedAccountCallback::where('transaction_reference', $reference)->first();

        if (! $transaction) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Callback record not found.',
                'reference' => $reference,
            ], 404);
        }

        if (strtolower((string) ($transaction->gateway?->slug ?? '')) === 'monnify') {
            $monnify = new MonnifyController($transaction->gateway);
            $result = $monnify->verifyTransaction($reference);

            return response()->json([
                'status' => $result['status'] ?? 'failed',
                'message' => ($result['status'] ?? 'failed') === 'success'
                    ? 'Monnify query completed successfully.'
                    : 'Monnify query did not return a successful payment state.',
                'reference' => $reference,
                'provider' => $transaction->gateway?->name,
                'response' => $result,
            ]);
        }

        if (strtolower((string) ($transaction->gateway?->slug ?? '')) === 'squad') {
            $squad = new SquadController($transaction->gateway);
            $result = $squad->verifyTransaction($reference);

            return response()->json([
                'status' => $result['status'] ?? 'failed',
                'message' => ($result['status'] ?? 'failed') === 'success'
                    ? 'Squad query completed successfully.'
                    : 'Squad query did not return a successful payment state.',
                'reference' => $reference,
                'provider' => $transaction->gateway?->name,
                'response' => $result,
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Unsupported provider for callback requery.',
            'reference' => $reference,
        ], 422);

    }

    public function changeTransactionMethod(Request $request, Airtime2CashTransactions $transaction)
    {

        $transaction->update([
            'payment_method' => $request->payment_method,
            'bank_code' => $request->bank ?? $transaction->bank_code,
            'account_number' => $request->account_number ?? $transaction->account_number,
            'account_name' => $request->account_name ?? $transaction->account_name,
        ]);

        return back()->with('Operation successful');
    }

    public function approveAirtime2CashTransactions(Request $request, Airtime2CashTransactions $transaction)
    {
        //Update wallet balance if payment method is Transfer to Wallet
        if ($transaction->payment_method == 'Transfer to Wallet') {
            try {
                DB::transaction(function () use ($transaction): void {
                    $wallet = new WalletController();
                    $customer = Customer::query()
                        ->whereKey($transaction->customer_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $balanceBefore = (float) ($customer->wallet ?? 0);
                    $amount = (float) $transaction->amount_paid;
                    $balanceAfter = $balanceBefore + $amount;

                    $walletData = [
                        'type' => 'credit',
                        'customer_id' => $transaction->customer_id,
                        'transaction_id' => $transaction->transaction_id,
                        'payment_method' => 'wallet',
                        'ip_address' => $this->getIpAddress(),
                        'domain_name' => $this->getDomainName(),
                        'customer_email' => $transaction->customer->user->email,
                        'customer_phone' => $transaction->customer->user->phone,
                        'customer_name' => $transaction->customer->user->firstname,
                        'product_id' => $transaction->product_id,
                        'product_name' => $transaction->product->name,
                        'unique_element' => 'Airtime2Cash Payment',
                        'category_id' => $transaction->product?->category?->id,
                        'discount' => 0,
                        'reason' => 'Airtime2Cash Payment',
                        'status' => 'delivered',
                        'unit_price' => $amount,
                        'quantity' => 1,
                        'total_amount' => $amount,
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'descr' => $transaction->description,
                        'api_id' => $transaction->provider_id,
                    ];

                    $this->upsertAirtime2CashTransactionLog($transaction, $customer, [
                        'status' => 'delivered',
                        'descr' => $transaction->description ?? 'Airtime2Cash Request was approved and completed by ADMIN',
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'provider_status' => 'successful',
                    ]);

                    $wallet->logWallet($walletData);
                    $wallet->updateCustomerWallet($transaction->customer->user, $amount, 'credit');
                });

                $status = 'success';
                $error = '';
            } catch (\Throwable $th) {
                Log::error('Airtime2Cash manual approval failed.', [
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'transaction_id' => $transaction->transaction_id,
                    'admin_id' => auth()->id(),
                ]);

                return back()->with('error', 'An error occured when performing action: ' . $th->getMessage());
            }
        } else {
            // Perform Transfer to bank actions
            $status = 'success';
        }

        try {
            if ($status == 'success') {
                $transaction->update([
                    'status' => 'approved',
                    'description' => 'Airtime2Cash Request was approved and completed by ADMIN',
                    'approved_by' => auth()->user()->admin->id,
                    'provider_id' => getSettings()->bank_transfer_provider_id ?? null,
                    'completed_at' => now(),
                    'provider_status' => 'successful',
                ]);

                $subject = "Airtime2Cash Transaction Update";
                $body = '<p>Hello! ' . $transaction->customer->user->name . ',</p>';
                $body .= '<p style="line-height: 2.0;">Your Transaction with transaction ID : <strong>' . $transaction->transaction_id . '</strong> has been updated to: ' . ucfirst($transaction->status) . '<br><strong>Date Updated:</strong> ' . date("M jS, Y g:iA", strtotime($transaction->updated_at)) . '<br><br>
                Warm Regards. (' . config('app.name') . ')<br/>
                </p>';
                $email = $transaction->customer->user->email;

                logEmails($email, $subject, $body);
                return back()->with('message', 'Operation successful');
            } else {
                return back()->with('error', 'An error occured when performing action: ' . $error);
            }
        } catch (\Throwable $th) {
            return back()->with('error', 'An error occured when performing action: ' . $th->getMessage());
        }
    }

    public function transferToBankAccount(
        string $bankCode,
        string $accountNumber,
        string $accountName,
        string|float $amount,
        ?TransactionLog $transaction = null
    ): array {
        $provider = API::query()
            ->whereKey(getSettings()->bank_transfer_provider_id)
            ->where('status', 'active')
            ->first();

        if (! $provider) {
            return [
                'status' => 'failed',
                'error' => 'No active bank transfer provider configured.',
            ];
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'transfer')) {
            return [
                'status' => 'failed',
                'error' => "Unsupported bank transfer provider: {$provider->slug}",
            ];
        }

        $bank = Bank::active()->where('cbn_code', $bankCode)->first();

        if (! $bank) {
            return [
                'status' => 'failed',
                'error' => 'Selected bank is unavailable or inactive.',
            ];
        }

        $resolvedBankCode = resolveProviderBankCode($bank, $provider) ?: $bankCode;

        $data = [
            'bank_code' => $resolvedBankCode,
            'provider_bank_code' => $resolvedBankCode,
            'cbn_code' => $bank->cbn_code,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'amount' => (float) $amount,
            'transaction_id' => $transaction?->transaction_id,
            'provider_id' => $provider->id,
            'provider_slug' => $provider->slug,
        ];

        $response = $controller->transfer($data);

        if ($transaction) {
            $transaction->update([
                'api_id' => $provider->id,
                'bank_id' => $bank?->id,
                'bank_code' => $bank->cbn_code,
                'api_response' => $response['api_response'] ?? $response,
                'request_data' => json_encode(
                    $response['request_data'] ?? $data
                ),
                'failure_reason' => $response['status'] === 'success'
                    ? null
                    : ($response['error'] ?? 'Bank transfer failed.'),
                'descr' => $response['status'] === 'success'
                    ? 'Bank transfer completed successfully.'
                    : ($response['error'] ?? 'Bank transfer failed.'),
            ]);
        }

        return $response;
    }

    public function declineAirtime2CashTransactions(Request $request, Airtime2CashTransactions $transaction)
    {
        $transaction->update([
            'status' => 'declined',
            'description' => 'Airtime2Cash Request was declined by ADMIN',
            'approved_by' => auth()->user()->admin->id,
            'decline_reason' => $request->decline_reason,
            'completed_at' => $transaction->completed_at ?? now(),
        ]);

        $subject = "Airtime2Cash Transaction Update";
        $body = '<p>Hello! ' . $transaction->customer->user->name . ',</p>';
        $body .= '<p style="line-height: 2.0;">Your Transaction with transaction ID : <strong>' . $transaction->transaction_id . '</strong> has been updated to: ' . ucfirst($transaction->status) . '<br><strong>Date Updated:</strong> ' . date("M jS, Y g:iA", strtotime($transaction->updated_at)) . '<br>
        Decline Reason: ' . $transaction->decline_reason . '<br><br>
            Warm Regards. (' . config('app.name') . ')<br/>
            </p>';
        $email = $transaction->customer->user->email;

        logEmails($email, $subject, $body);

        return back()->with('message', 'Operation successful');
    }

    public function verifyBankDetails(Request $request)
    {
        $providerId = getSettings()->bank_verification_provider_id ?: getSettings()->bank_transfer_provider_id;
        $provider = API::where('id', $providerId)->where('status', 'active')->first();

        if (! $provider) {
            return response()->json([
                'status' => false,
                'message' => 'No active bank verification provider configured.',
            ], 422);
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'verifyBankDetails')) {
            return response()->json([
                'status' => false,
                'message' => "Bank verification is not supported for {$provider->slug}.",
            ], 422);
        }

        $accountNumber = trim((string) $request->input('account_number', ''));

        if ($accountNumber !== '') {
            $cached = BillerLog::query()
                ->where('service_id', $provider->slug)
                ->where('billers_code', $accountNumber)
                ->latest('id')
                ->first();

            if ($cached) {
                $refinedData = json_decode((string) $cached->refined_data, true);
                $rawData = json_decode((string) $cached->raw_data, true);

                return response()->json([
                    'status' => true,
                    'message' => 'Account details loaded from cache.',
                    'data' => [
                        'provider' => $cached->provider,
                        'account_number' => $accountNumber,
                        'refined_data' => is_array($refinedData) ? $refinedData : [],
                        'raw_response' => is_array($rawData) ? $rawData : [],
                        'cached' => true,
                    ],
                    'raw_response' => is_array($rawData) ? $rawData : [],
                ]);
            }
        }

        $response = $controller->verifyBankDetails($request->all());
        $payload = $response instanceof JsonResponse
            ? $response->getData(true)
            : (is_array($response) ? $response : []);

        if (! (bool) data_get($payload, 'status', false)) {
            return $response;
        }

        $providerResponse = data_get($payload, 'raw_response')
            ?? data_get($payload, 'data')
            ?? $payload;

        if (is_array($providerResponse)) {
            $refinedData = array_filter([
                'Bank Name' => data_get($providerResponse, 'data.bank_name')
                    ?? data_get($providerResponse, 'bank_name')
                    ?? data_get($providerResponse, 'bank'),
                'Account Name' => data_get($providerResponse, 'data.account_name')
                    ?? data_get($providerResponse, 'account_name')
                    ?? data_get($providerResponse, 'accountName'),
                'Account Number' => data_get($providerResponse, 'data.account_number')
                    ?? data_get($providerResponse, 'account_number')
                    ?? $accountNumber,
            ], fn ($value) => filled($value));

            BillerLog::updateOrCreate([
                'service_id' => $provider->slug,
                'billers_code' => $accountNumber,
            ], [
                'service_id' => $provider->slug,
                'billers_code' => $accountNumber,
                'provider' => $provider->slug,
                'refined_data' => json_encode($refinedData),
                'raw_data' => json_encode($providerResponse),
            ]);
        }

        return $response;
    }
}
