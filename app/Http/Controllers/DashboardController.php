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
use Illuminate\Support\Carbon;
use App\Models\ReferralEarning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\TransactionPinResetToken;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $customer = $this->customerOfTheMonth();
        if (auth()->user()->type == 'admin') {
            $transaction_debit = TransactionLog::join('wallets', 'wallets.transaction_id', '=', 'transaction_logs.transaction_id')
                ->where('wallets.type', 'debit')->whereIn('status', ['success', 'delivered']);

            $transaction_credit = TransactionLog::join('wallets', 'wallets.transaction_id', '=', 'transaction_logs.transaction_id')
                ->where('wallets.type', 'credit')->whereIn('status', ['success', 'delivered']);

            $debit = $transaction_debit->sum('total_amount');
            $debit_count = $transaction_debit->count();

            $credit = $transaction_credit->sum('total_amount');
            $credit_count = $transaction_credit->count();

            $referralC = ReferralEarning::where('type', 'credit');
            $referral_credit = $referralC->sum('amount');
            $referral_credit_count = $referralC->count();

            $referralD = ReferralEarning::where('type', 'debit');
            $referral_debit = $referralD->sum('amount');
            $referral_debit_count = $referralD->count();
            $total_wallet_balance = Customer::sum('wallet');

            $kyc_verified = User::join('customers', 'customers.user_id', 'users.id')->where('users.type', 'customer')->where('kyc_status', 'verified')->count();
            $active_customers = TransactionLog::distinct('customer_id')->count();
            $customers = User::where('type', 'customer')->count();

            $apis = API::get();
            
            return view('admin.dashboard', compact('customer',  'credit', 'credit_count', 'debit', 'debit_count', 'referral_debit', 'referral_credit', 'referral_credit_count', 'referral_debit_count', 'kyc_verified', 'active_customers', 'customers', 'total_wallet_balance','apis'));
        } else {
            return view('customer.dashboard', compact('customer'));
        }
    }

    public function customerOfTheMonth()
    {
        $firstdayofmonth = Carbon::today()->startOfMonth();

        $count = TransactionLog::with('customer')->where('reason', 'Product Purchase')
        ->whereIn('status',['completed','delivered','success'])
        ->addSelect(DB::raw('SUM(total_amount) as total_amount, COUNT(id) as count,customer_id'))
            ->groupBy('customer_id')
            ->whereBetween('created_at', [$firstdayofmonth, Carbon::now()])
            ->orderBy('total_amount', 'DESC')->first();
        return $count;
    }

    public function resetTransactionPin()
    {
        return view('customer.reset_pin');
    }

    public function createTransactionPin(){
        return view('customer.create_pin');
    }

    public function showUpgradeForm()
    {
        $levels = CustomerLevel::orderBy('order', 'ASC')->where('id', '>', auth()->user()->customer->level->id)->get();
        return view('customer.upgrade_level', compact('levels'));
    }

    public function showLoadWalletPge()
    {
        $gateway = PaymentGateway::where('status', 'active')->first();
        return view('customer.load_wallet', compact('gateway'));
    }

    public function upgradeAccount(Request $request)
    {
        $level = CustomerLevel::where('id', $request->level)->first();
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
        return view('customer.edit_kyc_details', compact('kyc'));
    }

    public function apiSettings(){
        return view('customer.api_settings');
    }

    public function processUpdateKycInfo(Request $request)
    {
        $input = $this->validate($request, [
            "FIRST_NAME" => "nullable",
            "MIDDLE_NAME" => "nullable",
            "LAST_NAME" => "nullable",
            "PHONE_NUMBER" => "nullable",
            "COUNTRY" => "nullable",
            "STATE" => "nullable",
            "LGA" => "nullable",
            "DOB" => "nullable",
            "BVN" => "nullable|string|min:11|max:11",
            "IDCARD" => "sometimes|image|max:1024",
            "IDCARDTYPE" => "nullable"
        ]);

        if (!empty($request->IDCARD)) {
            $input['IDCARD'] = $this->uploadFile($request->IDCARD, 'kyc');
        }

        $instantVerify = ['FIRST_NAME', 'LAST_NAME', 'MIDDLE_NAME', 'DOB', 'PHONE_NUMBER', 'COUNTRY', 'STATE', 'LGA', 'DOB', 'IDCARD', 'IDCARDTYPE'];
        foreach ($input as $key => $value) {
            if (in_array($key, $instantVerify)) {
                $this->updateKycData($key, $value, auth()->user()->customer->id, 'verified');
            } else {
                $this->updateKycData($key, $value, auth()->user()->customer->id, 'unverified');
            }
        }

        $firstname = $input['FIRST_NAME'] ?? auth()->user()->firstname;
        $lastname = $input['LAST_NAME'] ?? auth()->user()->lastname;
        $middlename = $input['MIDDLE_NAME'] ?? auth()->user()->middlename;

        auth()->user()->update([
            "firstname" => $firstname,
            "middlename" => $middlename,
            "lastname" => $lastname,
        ]);

        // verify BVN automatically
        $this->updateKycData('BVN', $request->BVN, auth()->user()->customer->id, 'verified');

        auth()->user()->customer->update([
            "kyc_status" => 'verified',
        ]);

        // Create reserved account
        $name = $firstname . ' ' . $lastname . ' ' . $middlename;

        $data = [
            'BVN' => $request->BVN ?? kycStatus('BVN', auth()->user()->customer->id)['value'],
            'customerName' => $name,
            'accountName' => $firstname,
            'customerEmail' => auth()->user()->email,
            'customer_id' => auth()->user()->customer->id,
            'getAllAvailableBanks' => true,
        ];

        $reserved = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->createReservedAccount($data);
        
        if ($reserved['status'] && $reserved['status'] == 'success') {
            return back()->with('message', 'KYC Update completed');
        } else {
            $error = $reserved['data'] ?? 'Please refresh this page';
            return back()->with('error', 'Error: ' . $error);
        }
    }
    
    public function updateKycData($key, $value, $customer_id, $status)
    {
        $check = KycData::where(['customer_id' => $customer_id, 'key' => $key, 'status' => 'verified'])->first();
        if (!$check) {
            KycData::updateOrCreate([
                'customer_id' => $customer_id,
                'key' => $key,
            ], [
                'customer_id' => $customer_id,
                'key' => $key,
                'value' => $value,
                'status' => $status
            ]);
        }

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
       
        return view('customer.downlines', ['refs' => $refs, 'check' => $id]);
    }

    public function allDownlines(){
        $refs = User::where('referral', auth()->user()->username)->orderBy('created_at','DESC')->get();
        
        return view('customer.referals', ['refs' => $refs]);

    }

    function downlinesWithdrawal()
    {
        return view('customer.withdraw_earning');
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
        $tid = 'KVTU-' . $requestId;
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
                'amount' => $amount,
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
