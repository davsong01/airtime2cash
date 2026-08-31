<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\User;
use App\Models\KycData;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CustomerLevel;
use App\Models\PaymentGateway;
use App\Models\TransactionLog;
use App\Models\Airtime2CashTransactions;
use Illuminate\Support\Carbon;
use App\Models\ReferralEarning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\TransactionPinResetToken;
use App\Models\Webhook;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $customer = $this->customerOfTheMonth();

        if (auth()->user()->type == 'admin') {
            $currency = getSettings()?->currency ?? 'NGN';
            $walletSummary = Customer::selectRaw('COALESCE(SUM(wallet), 0) AS wallet_total')
                ->selectRaw('COALESCE(SUM(referal_wallet), 0) AS referral_total')
                ->selectRaw('COALESCE(SUM(a2cashwallet), 0) AS a2cash_total')
                ->first();

            $customerSummary = User::where('users.type', '!=', 'admin')
                ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw("SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) AS active")
                ->selectRaw("SUM(CASE WHEN users.status = 'suspended' THEN 1 ELSE 0 END) AS suspended")
                ->selectRaw("SUM(CASE WHEN customers.kyc_status = 'verified' THEN 1 ELSE 0 END) AS kyc_verified")
                ->first();

            $transactionSummary = TransactionLog::selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) AS today')
                ->selectRaw("SUM(CASE WHEN status IN ('success', 'delivered', 'completed') THEN 1 ELSE 0 END) AS successful")
                ->selectRaw("SUM(CASE WHEN status IN ('failed', 'declined') THEN 1 ELSE 0 END) AS failed")
                ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('success', 'delivered', 'completed') THEN provider_charge ELSE 0 END), 0) AS recorded_charges")
                ->first();

            $airtimeToCashSummary = Airtime2CashTransactions::where('type', 'credit')
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count")
                ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN COALESCE(profit, 0) ELSE 0 END), 0) AS recorded_charges")
                ->first();
            $recordedEarnings = (float) $transactionSummary->recorded_charges
                + (float) $airtimeToCashSummary->recorded_charges;

            $active_customers = TransactionLog::distinct()->count('customer_id');
            $apis = API::withCount('products')
                ->withCount('transactions')
                ->withSum('transactions', 'total_amount')
                ->orderBy('name')
                ->get();
            $recentTransactions = TransactionLog::with([
                'customer.user:id,firstname,middlename,lastname,email',
                'product:id,name,display_name',
            ])->latest()->limit(8)->get();
            $autoSyncWebhookSummary = Webhook::selectRaw('COUNT(*) AS total')
                ->selectRaw("SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) AS pending")
                ->selectRaw("SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) AS failed")
                ->first();

            return view('admin.dashboard', compact(
                'customer',
                'customerSummary',
                'walletSummary',
                'transactionSummary',
                'airtimeToCashSummary',
                'recordedEarnings',
                'active_customers',
                'apis',
                'recentTransactions',
                'autoSyncWebhookSummary',
                'currency'
            ));
        } else {
            return view(themeView('customer', 'dashboard'), compact('customer'));
        }
    }

    public function dashboardWidgets($type)
    {
        $transactions = TransactionLog::join('wallets', 'wallets.transaction_id', '=', 'transaction_logs.transaction_id')
        ->select(
            DB::raw("SUM(CASE WHEN wallets.type = 'debit' AND status IN ('success', 'delivered') THEN total_amount ELSE 0 END) as total_debit"),
            DB::raw("COUNT(CASE WHEN wallets.type = 'debit' AND status IN ('success', 'delivered') THEN 1 ELSE NULL END) as debit_count"),
            DB::raw("SUM(CASE WHEN wallets.type = 'credit' AND status IN ('success', 'delivered') THEN total_amount ELSE 0 END) as total_credit"),
            DB::raw("COUNT(CASE WHEN wallets.type = 'credit' AND status IN ('success', 'delivered') THEN 1 ELSE NULL END) as credit_count")
        )->first();


        if ($type == 'transactions') {
            $debit = $transactions->total_debit;
            $debit_count = $transactions->debit_count;
            $credit = $transactions->total_credit;
            $credit_count = $transactions->credit_count;

            $res = [
                'data' => '<span style="color:black">All: ' . getSettings()->currency . ' (' . number_format($credit + $debit) . ')</span><br><span style="color:green">Credit: ' . getSettings()->currency . number_format($credit) . '<small>(' . $credit_count . ')</small></span> <br><span style="color:red">Debit: ' . getSettings()->currency . number_format($debit) . ' <small>(' . $debit_count . ')</small></span><br>',
            ];
        }

        if ($type == 'referrals') {
            $referrals = ReferralEarning::select(
                DB::raw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_referral_credit"),
                DB::raw("COUNT(CASE WHEN type = 'credit' THEN 1 ELSE NULL END) as referral_credit_count"),
                DB::raw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_referral_debit"),
                DB::raw("COUNT(CASE WHEN type = 'debit' THEN 1 ELSE NULL END) as referral_debit_count")
            )
                ->first();

            $referral_credit = $referrals->total_referral_credit;
            $referral_credit_count = $referrals->referral_credit_count;
            $referral_debit = $referrals->total_referral_debit;
            $referral_debit_count = $referrals->referral_debit_count;

            $res = [
                'data' => '<span style="color:black">All: ' . getSettings()->currency . number_format($referral_credit + $referral_debit) . '(' . $referral_debit_count + $referral_credit_count . ') </span><br><span style="color:green">Credit: ' . getSettings()->currency . number_format($referral_credit) . '<small>(' . $referral_credit_count . ')</small></span> <br><span style="color:red">Debit: ' . getSettings()->currency . number_format($referral_debit) . '<small>(' . $referral_debit_count . ')</small></span><br>',
            ];
        }

        return $res;
    }

    public function customerOfTheMonth()
    {
        $firstdayofmonth = Carbon::today()->startOfMonth();
        $now = Carbon::now();

        $successfulTransactionStatuses = ['completed', 'delivered', 'success', 'approved', 'successful'];

        $productTransactions = DB::table('transaction_logs')
            ->selectRaw('customer_id, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereIn('reason', ['Product Purchase', 'Airtime2Cash Payment'])
            ->whereIn('status', $successfulTransactionStatuses)
            ->whereRaw('COALESCE(completed_at, created_at) BETWEEN ? AND ?', [$firstdayofmonth, $now])
            ->groupBy('customer_id');

        $airtimeTransactions = DB::table('airtime2_cash_transactions as a2c')
            ->selectRaw('a2c.customer_id, COUNT(*) as count, COALESCE(SUM(a2c.total_amount), 0) as total_amount')
            ->whereIn('a2c.status', ['approved', 'successful'])
            ->whereRaw('COALESCE(a2c.completed_at, a2c.created_at) BETWEEN ? AND ?', [$firstdayofmonth, $now])
            ->whereNotExists(function ($query) use ($successfulTransactionStatuses) {
                $query->select(DB::raw(1))
                    ->from('transaction_logs as tl')
                    ->whereColumn('tl.transaction_id', 'a2c.transaction_id')
                    ->where('tl.reason', 'Airtime2Cash Payment')
                    ->whereIn('tl.status', $successfulTransactionStatuses);
            })
            ->groupBy('a2c.customer_id');

        $winner = DB::query()
            ->fromSub(
                $productTransactions->unionAll($airtimeTransactions),
                'customer_month_activity'
            )
            ->selectRaw('customer_id, SUM(count) as count, SUM(total_amount) as total_amount')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->first();

        if (! $winner) {
            return null;
        }

        $winner->customer = Customer::with('user')->find($winner->customer_id);

        return $winner;
    }

    public function resetTransactionPin()
    {
        return view(themeView('customer', 'reset_pin'));
    }

    public function createTransactionPin(){
        return view(themeView('customer', 'create_pin'));
    }

    public function showUpgradeForm()
    {
        $currentLevelId = (int) (auth()->user()->customer?->level?->id ?? 0);
        $levels = CustomerLevel::enabled()->orderBy('order', 'ASC')->where('id', '>', $currentLevelId)->get();
        return view(themeView('customer', 'upgrade_level'), compact('levels'));
    }

    public function showLoadWalletPge()
    {
        $provider = resolvePaymentGatewayProvider();
        $gateway = $provider ? resolvePaymentGatewaySetting($provider->slug) : resolvePaymentGatewaySetting();
        return view(themeView('customer', 'load_wallet'), compact('gateway', 'provider'));
    }

    public function upgradeAccount(Request $request)
    {
        $level = CustomerLevel::enabled()->where('id', $request->level)->first();
        if (!$level) {
            return back()->with('error', 'Level not found');
        }

        $price = $level->upgrade_amount;
        $wallet = new WalletController();
        $balance = $wallet->getWalletBalance(auth()->user());

        if ($balance < $price) {
            return back()->with('error', 'Insufficient Wallet Balance, Please try again');
        }

        // Log Wallet
        $request_id = $this->generateRequestId();
        $request['type'] = 'debit';
        $request['customer_id'] = auth()->user()->customer->id;
        $request['transaction_id'] = 'KVTUPGRD-' . $request_id;
        $request['request_id'] = $request_id;
        $request['payment_method'] = 'wallet';
        $request['balance_before'] = $balance;
        $request['ip_address'] = $this->getIpAddress();
        $request['domain_name'] = $this->getDomainName();
        $request['customer_email'] = auth()->user()->email;
        $request['customer_phone'] = auth()->user()->phone;
        $request['customer_name'] = auth()->user()->firstname;
        $request['reason'] = 'LEVEL-UPGRADE';
        $request['amount'] = $price;
        $request['total_amount'] = $price;
        $request['discount'] = 0;
        $request['quantity'] = 1;
        $request['unique_element'] = 'LEVEL-UPGRADE';

        try {
            DB::beginTransaction();
            // Log basic transaction
            $transaction = app('App\Http\Controllers\TransactionController')->logTransaction($request->all());
            if($level->make_api_level == 'yes'){
                $transaction->update([
                    'balance_after' => $balance - $price,
                    'upgrade_level' => $level->id,
                    'status' => 'pending',
                    'descr' => 'Level Upgrade from ' . auth()->user()->customer->level->name . ' to ' . $level->name . ' was requested',
                ]);
            }else{
                $transaction->update([
                    'balance_after' => $balance - $price,
                    'status' => 'success',
                    'descr' => 'Level Upgrade from ' . auth()->user()->customer->level->name . ' to ' . $level->name . ' was successful',
                ]);
            }

            $user = auth()->user();
            app('App\Http\Controllers\TransactionController')->referralReward($user->referral, $request['total_amount'], $user->customer->id, $request_id, 50);

            // Log wallet
            $wal = $wallet->logWallet($request->all());

            // Update Customer Wallet
            $wallet->updateCustomerWallet(auth()->user(), $price, $request['type']);

            // Update customer level
            if ($level->make_api_level == 'no') {
                auth()->user()->customer->update([
                    'customer_level' => $level->id
                ]);
            }

            DB::commit();
            if ($level->make_api_level == 'yes') {
                return redirect()->away(getSettings()->support_link);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            // \Log::error(['Upgrade Error' => 'Message: '.$th->getMessage().' File: '.$th->getFile().' Line: '.$th->getLine()]);
            return redirect(route('dashboard'))->with('error', 'An error occured while trying to upgrade, please try again later');
        }

        // Log transaction email
        $this->sendTransactionEmail($transaction, auth()->user());
        return redirect(route('dashboard'))->with('message', 'Upgrade successful');
    }

    public function processCreateTransactionPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'transaction_pin' => 'numeric|required|min:1000|max:99999',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->messages()->first());
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Incorrect password !!!');
        }

        $new_pin = base64_encode(base64_encode(base64_encode($request->transaction_pin)));

        auth()->user()->transaction_pin = $new_pin;
        auth()->user()->save();
        // Send email
        $subject = "New Transaction PIN set";
        $body = '<p>Hello! ' . auth()->user()->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">You have successfully created a transaction PIN on account ' . config('app.name') . ' Please use your PIN when carrying out transactions.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails(auth()->user()->email, $subject, $body);

        return redirect(route('dashboard'))->with('message', 'You have successfully created a transaction PIN');
    }

    public function processResetTransactionPin(Request $request)
    {
        if(auth()->user()->type == 'customer'){
            $validator = Validator::make($request->all(), [
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->messages()->first());
            }

            if (!Hash::check($request->password, auth()->user()->password)) {
                return back()->with('error', 'Incorrect password !!!');
            }

            $user = auth()->user();
            $this->sendTransactionPinResetToken($user);

            return back()->with('message', 'We have sent you a Transaction Pin reset email, please check your email and follow the instructions to reset Transaction PIN');
        }else{
            $user = User::where('id',$request->user_id)->first();
            $this->sendTransactionPinResetToken($user);

            return back()->with('message', 'Transaction Pin reset email sent to '.$user->email);
        }


    }

    public function sendTransactionPinResetToken($user){
        $settings = getsettings();

        $token = Str::random(60);
        $hashedToken = base64_encode(base64_encode($token));
        $expiry = Carbon::now()->addMinute(15);

        TransactionPinResetToken::updateOrCreate(['user_id' => $user->id, 'status' => 'pending'], [
            'user_id' => $user->id,
            'token' => $token,
            'expiry' => $expiry,
        ]);

        // Send email
        $subject = "You requested a Transaction PIN Reset Link";
        $body = '<p>Hello! ' . $user->firstname . '</p>';
        $reset_link = url('/') . "/confirm_reset_pin?token=" . $hashedToken;
        $body .= '<p style="line-height: 2.0;">Please <strong>login</strong> to your account on ' . config('app.name') . ' and click the button below to reset your transaction pin. <br> <a class="btn btn-info" target="_blank" href="' . $reset_link . '" style="position: margin:5px; relative;margin-bottom:50px;color: #fff;font-weight: 500;padding: 8px 20px;font-size: 13px;line-height: 24px;letter-spacing: 0.01em;border-radius: 4px;border: 1px solid;transition: all .4s ease;background:#950eb3;text-align: center;white-space: nowrap;vertical-align: middle;text-decoration:none">RESET PIN</a><br/><br><strong>Please note that this link expires after ' . $expiry->format('jS F, Y, h:iA') . '.</strong><br><br>If you did not request a Transaction PIN change, Kindly notify us via WhatsApp us via whatsapp( ' . $settings->whatsapp_no . ') immediately.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails($user->email, $subject, $body);
    }

    public function resetPin2(Request $request)
    {
        $decode = base64_decode(base64_decode($request->token));
        $row = TransactionPinResetToken::where(['user_id' => auth()->user()->id, 'status' => 'pending'])->first();

        if (!$row) {
            return redirect(route('customer.reset.pin'))->with('warning', 'Transaction PIN reset link invalid. Please try to reset Transaction PIN again!!!');
        }

        if ($row->expiry < Carbon::now()) {
            $row->status = 'completed';
            $row->save();
            return redirect(route('customer.reset.pin'))->with('error', 'PIN reset link has expired, please try to reset PIN again!!!');
        }

        if ($decode !== $row->token) {
            return redirect(route('customer.reset.pin'))->with('error', 'Invalid token link, please check your email and click the Transaction PIN reset link we sent you or resend a new reset link!!!');
        }

        $row->status = 'completed';
        $row->save();

        return view('auth.reset_pin');
    }

    public function finalProcessPin(Request $request)
    {
        $settings = $this->settings();

        $validator = Validator::make($request->all(), [
            'new_transaction_pin' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->messages()->first());
        }

        $new_pin = base64_encode(base64_encode(base64_encode($request->new_transaction_pin)));

        auth()->user()->transaction_pin = $new_pin;
        auth()->user()->save();

        // Send email
        $subject = "New Transaction Pin";
        $body = '<p>Hello! ' . auth()->user()->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">You have successfully changed your Transaction PIN on ' . config('app.name') . ' on ' . Carbon::now()->format('jS F, Y, h:iA') . '.</strong><br><br>If you did not request a Transaction PIN change, Kindly notify us via WhatsApp us via whatsapp( ' . $settings->whatsapp_no . ') immediately.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails(auth()->user()->email, $subject, $body);

        return redirect(route('dashboard'))->with('message', 'Transaction PIN changed successfully');
    }

    public function updateKycInfo()
    {
        $kyc = $this->getKycStatus(auth()->user());
        $kycRecords = KycData::where('customer_id', auth()->user()->customer->id)
            ->get()
            ->keyBy('key');

        return view(themeView('customer', 'edit_kyc_details'), compact('kyc', 'kycRecords'));
    }

    public function apiSettings(){
        return view(themeView('customer', 'api_settings'));
    }

    public function processUpdateKycInfo(Request $request)
    {
        $customer = auth()->user()->customer;
        $hasDeclinedField = $this->customerHasDeclinedKycField($customer->id);

        $isResubmission = $hasDeclinedField;
        $kycFieldStatuses = [
            'FIRST_NAME' => data_get(kycStatus('FIRST_NAME', $customer->id), 'status', 'unverified'),
            'MIDDLE_NAME' => data_get(kycStatus('MIDDLE_NAME', $customer->id), 'status', 'unverified'),
            'LAST_NAME' => data_get(kycStatus('LAST_NAME', $customer->id), 'status', 'unverified'),
            'DOB' => data_get(kycStatus('DOB', $customer->id), 'status', 'unverified'),
            'BVN' => data_get(kycStatus('BVN', $customer->id), 'status', 'unverified'),
            'IDCARDTYPE' => data_get(kycStatus('IDCARDTYPE', $customer->id), 'status', 'unverified'),
            'IDCARD' => data_get(kycStatus('IDCARD', $customer->id), 'status', 'unverified'),
        ];

        if (collect($kycFieldStatuses)->every(fn ($status) => $status === 'verified')) {
            return back()->with(
                'error',
                'Your verified KYC details cannot be changed from the customer portal. Please contact support if your information needs to be corrected.'
            );
        }

        $rules = [
            'PHONE_NUMBER' => ['nullable'],
        ];

        foreach (array_keys($kycFieldStatuses) as $field) {
            $fieldRequired = $kycFieldStatuses[$field] !== 'verified'
                && (! $isResubmission || $kycFieldStatuses[$field] === 'declined');

            $rules[$field] = match ($field) {
                'BVN' => $fieldRequired ? ['required', 'digits:11'] : ['nullable', 'digits:11'],
                'IDCARD' => $fieldRequired ? ['required', 'image', 'mimes:jpg,jpeg', 'max:500'] : ['nullable', 'image', 'mimes:jpg,jpeg', 'max:500'],
                default => $fieldRequired ? ['required'] : ['nullable'],
            };
        }

        $input = $request->validate($rules, [
            'IDCARD.required' => 'Please upload your ID card.',
            'IDCARD.image'    => 'The uploaded file must be a valid image.',
            'IDCARD.mimes'    => 'Only JPG and JPEG images are allowed.',
            'IDCARD.max'      => 'Your ID card image must not be larger than 500 KB. Please upload a smaller image. You may upload a screenshot or clear photo of your ID card. Ensure all details are clearly visible.',
        ]);

        $input = array_filter($input, static fn ($value) => ! is_null($value));

        if (!empty($request->IDCARD)) {
            $input['IDCARD'] = $this->uploadFile($request->IDCARD, 'kyc');
        }

        $input['PHONE_NUMBER'] = $customer->user->phone;

        // $instantVerify = ['FIRST_NAME', 'LAST_NAME', 'MIDDLE_NAME', 'DOB', 'PHONE_NUMBER', 'COUNTRY', 'STATE', 'LGA', 'DOB', 'IDCARD', 'IDCARDTYPE'];
        // foreach ($input as $key => $value) {
        //     if (in_array($key, $instantVerify)) {
        //         $this->updateKycData($key, $value, auth()->user()->customer->id, 'verified');
        //     } else {
        //         $this->updateKycData($key, $value, auth()->user()->customer->id, 'unverified');
        //     }
        // }

        // $firstname = $input['FIRST_NAME'] ?? auth()->user()->firstname;
        // $lastname = $input['LAST_NAME'] ?? auth()->user()->lastname;
        // $middlename = $input['MIDDLE_NAME'] ?? auth()->user()->middlename;

        // auth()->user()->update([
        //     "firstname" => $firstname,
        //     "middlename" => $middlename,
        //     "lastname" => $lastname,
        // ]);
        foreach ($input as $key => $value) {
            $this->updateKycData($key, $value, $customer->id, 'unverified');
        }

        // verify BVN automatically
        // $this->updateKycData('BVN', $request->BVN, auth()->user()->customer->id, 'verified');

        $customer->update([
            "kyc_status" => 'awaiting-approval',
            'kyc_rejection_reason' => null,
        ]);

        // Create reserved account
        // $name = $firstname . ' ' . $lastname . ' ' . $middlename;

        // $data = [
        //     'BVN' => $request->BVN ?? kycStatus('BVN', auth()->user()->customer->id)['value'],
        //     'customerName' => $name,
        //     'accountName' => $firstname,
        //     'customerEmail' => auth()->user()->email,
        //     'customer_id' => auth()->user()->customer->id,
        //     'getAllAvailableBanks' => true,
        // ];

        // $reserved = createReservedAccount($data);

        // if ($reserved['status'] && $reserved['status'] == 'success') {
        //     return back()->with('message', 'KYC Update completed and reserved accounts created');
        // } else {
        //     $error = $reserved['data'] ?? 'Please refresh this page';
        //     return back()->with('error', 'Error: ' . $error);
        // }
        return back()->with('message', 'KYC Info Update. Awaiting Admin approval');

    }

    private function customerHasDeclinedKycField(int $customerId): bool
    {
        return KycData::where('customer_id', $customerId)
            ->where('status', 'declined')
            ->exists();
    }

    public function updateKycData($key, $value, $customer_id, $status = null)
    {
        if(!empty($status)){
            KycData::updateOrCreate([
                'customer_id' => $customer_id,
                'key' => $key,
            ], [
                'customer_id' => $customer_id,
                'key' => $key,
                'value' => $value,
                'status' => $status
            ]);
        }else{
            KycData::updateOrCreate([
                'customer_id' => $customer_id,
                'key' => $key,
            ], [
                'customer_id' => $customer_id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        // if (!$check) {
        //     KycData::updateOrCreate([
        //         'customer_id' => $customer_id,
        //         'key' => $key,
        //         'value' => $value,

        //     ], [
        //         'customer_id' => $customer_id,
        //         'key' => $key,
        //         'value' => $value,
        //         'status' => $status
        //     ]);
        // }

        return;
    }

    public function getKycStatus($user)
    {
        $kyc_data = KycData::where(['customer_id' => $user->customer->id])->first();

        return [
            'nin' => $kyc_data->nin ?? 'unverified',
            'bvn' => $kyc_data->bvn ?? 'unverified',
            'email' => $kyc_data->email ?? 'unverified',
            'phone' => $kyc_data->phone ?? 'unverified',
        ];
    }


    public function downlines($id = null)
    {
        $refs = ReferralEarning::where('customer_id', auth()->user()->customer->id)->where('type', 'credit')->orderBy('created_at','DESC');
        if ($id) {
            $refs = $refs->where('referred_customer_id', $id)->get();
        } else {
            $refs = $refs->groupBy('referred_customer_id')->get();
        }

        return view(themeView('customer', 'downlines'), ['refs' => $refs, 'check' => $id]);
    }

    public function allDownlines(){
        $refs = User::where('referral', auth()->user()->username)->orderBy('created_at','DESC')->get();

        return view(themeView('customer', 'referals'), ['refs' => $refs]);

    }

    function downlinesWithdrawal()
    {
        return view(themeView('customer', 'withdraw_earning'));
    }

    function processWithdrawal(Request $request)
    {
        $request->validate(([
            'amount' => 'required|integer',
        ]));

        $currAmount = referralBalance(auth()->user());
        $controller = new TransactionController();
        $amount = $controller->removeCharsInAmount($request->amount);

        if ($amount < 1) {
            return back()->with('error', 'Invalid value entered, try again!');
        } elseif ($amount > $currAmount) {
            return back()->with('error', 'Insuffient funds, amount to withdraw cannot be more than ' . $currAmount);
        }

        $requestId = $controller->generateRequestId();
        $tid = 'A2C-' . $requestId;
        $customer = auth()->user()->customer;

        try {
            DB::beginTransaction();
            //code...
            $data['type'] = 'credit';
            $data['customer_id'] = $customer->id;
            $data['transaction_id'] = $tid;
            $data['request_id'] = $requestId;
            $data['payment_method'] = 'REFERRAL-WALLET';
            $data['balance_before'] = walletBalance(auth()->user());
            $data['amount'] = $amount;
            $data['total_amount'] = $amount;
            $data['customer_email'] = auth()->user()->email;
            $data['customer_phone'] = auth()->user()->phone;
            $data['customer_name'] = auth()->user()->firstname;
            $data['unique_element'] = 'WALLET-FUNDING';
            $data['reason'] = 'WALLET-FUNDING';
            $data['descr'] = 'Withdrawal of ' . getSettings()->currency . number_format($amount, 2) . ' from referral balance was successful';
            $data['discount'] = 0;
            $data['unit_price'] = $amount;
            $data['balance_after'] = walletBalance(auth()->user()) + $amount;
            $data['status'] = 'delivered';

            $controller->logTransaction($data);
            $controller->logEarnings(
                'debit',
                $customer->id,
                $customer->id,
                $amount,
                $currAmount,
                $currAmount - $amount,
                $tid,
            );

            $wallet = new WalletController();
            $wallet->logWallet([
                'type' => 'credit',
                'total_amount' => $amount,
                'payment_method' => 'REFERRAL',
                'reason' => 'REFFERAL BALANCE WITHDRAWN TO WALLET',
                'transaction_id' => $tid,
            ]);

            $wallet->updateCustomerWallet(auth()->user(), $amount, 'credit');
            $wallet->updateReferralWallet(auth()->user(), $amount, 'debit');

            DB::commit();
            $user = auth()->user();
            $host = env('APP_URL');
            $transEmail = <<<__here
            Dear $user->firstname $user->lastname,

We hope this email finds you well. We are delighted to inform you that an amount of $amount has been successfully credited to your wallet.

Transaction Details:

Transaction ID: <a href="$host/customer-transaction_status/$tid">click here</a><br>
Credited Amount: $amount<br>
This credit to your wallet provides you with the flexibility to seamlessly make transactions and enjoy our services. Whether it's making a purchase, availing discounts, or accessing exclusive features, your wallet balance is now ready for use.
__here;

            logEmails($user->email, 'Wallet Credit', $transEmail);
            return back()->with('message', getSettings()->currency . number_format($amount, 2) . " withdrawn to wallet successfully!");
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return back()->with('error', "Transaction could not be completed, try again! " . $th->getMessage());
        }
    }
}
