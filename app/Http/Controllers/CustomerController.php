<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\CustomerLevel;
use App\Models\KycData;
use App\Models\ReferralEarning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\ReservedAccountNumber;

class CustomerController extends Controller
{
    function customers(Request $request, $status = null)
    {
        $customers = User::where('type', '!=', 'admin');

        if ($status) {
            if ($status == 'active') {
                $customers->where('status', 'active');
            } elseif ($status == 'api') {
                $customers->where('type', 'api');
            } elseif ($status == 'suspended') {
                $customers->where('status', 'suspended');
            } elseif ($status == 'email-blacklist') {
                $customers->where('status', 'email-blacklist');
            } elseif ($status == 'phone-blacklist') {
                $customers->where('status', 'phone-blacklist');
            } else {
                return redirect(404);
            }
        }

        if (isset($request->search)) {
            $key = "%{$request->search}%";
            $customers = $customers->where('firstname', 'like', $key)
                ->orWhere('lastname', 'like', $key)
                ->orWhere('middlename', 'like', $key)
                ->orWhere('username', 'like', $key)
                ->orWhere('phone', 'like', $key);
        }

        $customers = $customers->latest()->get();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function addReservedAccounts(Request $request, Customer $customer)
    {
        $data = [
            'BVN' => $request->bvn ?? kycStatus('BVN', $customer->id)['value'],
            'customerName' => $customer->user->name,
            'accountName' => $customer->user->firstname,
            'customerEmail' => $customer->user->email,
            'customer_id' => $customer->id,
            'preferredBanks' => $request->bank,
            'getAllAvailableBanks' => false
        ];

        $admin_id = auth()->user()->admin->id;
        $reserved = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->createReservedAccount($data, $admin_id);

        if ($reserved['status'] && $reserved['status'] == 'success') {
            return back()->with('message', 'Reserved Account(s) crearted successfully');
        } else {
            return back()->with('error', 'Error: ' . $reserved['data'] ?? 'Something went wrong');
        }
    }

    function singleCustomer($id)
    {
        if (!is_numeric($id)) {
            return redirect(404);
        }

        $user = User::findOrFail($id);
        $customer = $user->customer->id;
        $downlines = ReferralEarning::where('customer_id', $customer)
            ->latest()
            ->groupBy('referred_customer_id')
            ->get(['*', DB::raw('sum(amount) as total')]);

        $curr = getSettings()->currency;
        $balance = $curr . number_format(walletBalance($user), 2) ?? 0;
        $ref = $curr . number_format(referralBalance($user), 2) ?? 0;
        $transTotal = $curr . number_format($user->customer->transactions()->first([DB::raw('sum(amount) as total')], 2)->total) ?? 0;
        $fundTotal = $curr . number_format($user->customer->transactions()->whereNotNull('wallet_funding_provider')->first([DB::raw('sum(amount) as total')], 2)->total) ?? 0;
        $balances = ['Wallet Balance' => $balance, 'Referral Earning' => $ref, 'Transaction Total' => $transTotal, 'Funds Total' => $fundTotal];
        $reservedAccount = ReservedAccountNumber::where('customer_id', $customer)->orderBy('created_at', 'desc')->get();

        $customerLevels = CustomerLevel::orderBy('order','ASC')->get();

        return view(
            'admin.customers.single-customer',
            [
                'user' => $user,
                'downlines' => $downlines,
                'accounts' => $reservedAccount,
                'balances' => $balances,
                'customerLevels' => $customerLevels
            ]
        );
    }

    function updateCustomer(Request $request, $id = null)
    {
        $val = $request->validate([
            'status' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
        ]);

        $user = User::where('id', $id)->first();
        $user->update($request->except(['_token', 'ip', 'customerlevel']));

        if(!empty($request->customerlevel)){
            $level = CustomerLevel::where('id', $request->customerlevel)->first();
            $user->customer->customer_level = $level->id;

            if(!empty($level->transaction)){
                $level->transaction->update([
                    'status' => 'success',
                    'descr' => 'Level Upgrade from ' . $user->customer->level->name . ' to ' . $level->name . ' was successful',
                ]);
                $user->customer->api_access = 'active';
            }
            $user->customer->save();
        }
        return back()->with('message', 'Update successful!');

    }

    function filterEmail(Request $request)
    {
        $key = "%$request->key%";
        $user = User::where('email', 'like', $key)->get();

        return $user->toArray();
    }

    public function resetTransactionPin(Request $request, User $user){
        $this->validate($request, [
            'new_transaction_pin' => 'required',
        ]);

        $new_pin = base64_encode(base64_encode(base64_encode($request->new_transaction_pin)));
        $settings = getSettings();
        $user->transaction_pin = $new_pin;
        $user->save();

        // Send email
        $subject = "New Transaction Pin";
        $body = '<p>Hello! ' . $user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your transaction PIN has been reset by ADMIN on ' . config('app.name') . ' at ' . Carbon::now()->format('jS F, Y, h:iA') . '.</strong><br><br>Your new transaction PIN is <br><strong>'.$request->new_transaction_pin. '</strong><br>. If you did not request a Transaction PIN change, Kindly notify us via WhatsApp us via whatsapp( ' . $settings->whatsapp_no . ') immediately.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails($user->email, $subject, $body);

        return back()->with('message', 'Transaction PIN successfully reset to: '.$request->new_transaction_pin);
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->validate($request, [
            'new_password' => 'required',
        ]);

        $password = Hash::make($request->new_password);

        $settings = getSettings();
        $user->password = $password;
        $user->save();
        
        // Send email
        $subject = "New Password Pin";
        $body = '<p>Hello! ' . $user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your password has been reset by ADMIN on ' . config('app.name') . ' at ' . Carbon::now()->format('jS F, Y, h:iA') . '.</strong><br><br>Your new password is <br><strong>' . $request->new_password . '</strong><br>. If you did not request a password reset, Kindly notify us via WhatsApp us via whatsapp( ' . $settings->whatsapp_no . ') immediately.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails($user->email, $subject, $body);

        return back()->with('message', 'Transaction PIN successfully reset to: ' . $request->new_password);
    }

    public function processCustomerUpdateKycInfo(Request $request, Customer $customer)
    {
        $input = $this->validate($request, [
            "FIRST_NAME" => "nullable",
            "MIDDLE_NAME" => "nullable",
            "LAST_NAME" => "nullable",
            "PHONE_NUMBER" => "nullable",
            "BVN" => "nullable",
            "IDCARDTYPE" => "nullable",
            "IDCARD" => "nullable"
        ]);

        $user = $customer->user;
        
        if (!empty($request->IDCARD)) {
            $input['IDCARD'] = $this->uploadFile($request->IDCARD, 'kyc');
        }else{
            $input['IDCARD'] = kycStatus('IDCARD', $user->customer->id)['value'];
        }
        // dd($input, kycStatus('IDCARD', $user->customer->id)['value']);
        $items = 0;
        foreach ($input as $key => $value) {
            if(!empty($value)){
                // if($key == 'IDCARD'){
                //     dd($value, 'sdds');
                // }
                app('App\Http\Controllers\DashboardController')->updateKycData($key, $value, $customer->id);
                $items += 1;
            }
        }

        if ($items == count($input)) {
            $firstname = $input['FIRST_NAME'];
            $lastname = $input['LAST_NAME'];
            $middlename = $input['MIDDLE_NAME'];
    
            $user->update([
                "firstname" => $firstname,
                "middlename" => $middlename,
                "lastname" => $lastname,
            ]);
        }

        return back()->with('message', 'Information Update completed, click approve to generate reserved account');
        
    }

    public function approveCustomerKyc(Customer $customer){
        $customer->update([
            "kyc_status" => 'verified',
        ]);
        
        KycData::where('customer_id', $customer->id)->update([
            'status' => 'verified',
        ]);

        $data = [
            'BVN' => kycStatus('BVN', $customer->id)['value'],
            'customerName' => $customer->user->username,
            'accountName' => kycStatus('FIRST_NAME', $customer->id)['value'],
            'customerEmail' => $customer->user->email,
            'customer_id' => $customer->id,
            'getAllAvailableBanks' => true,
        ];

        // Log email
        $subject = "KYC Info Update";
        $body = '<p>Hello! ' . $customer->user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your KYC Information has been approved ' . config('app.name') . '<br><br> You can now carry out transactions<br/></p>';

        logEmails($customer->user->email, $subject, $body);

        $reserved = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->createReservedAccount($data);
        if ($reserved['status'] && $reserved['status'] == 'success') {
            return back()->with('message', 'KYC Approved succesfully and reserved accounts created');
        } else {
            return back()->with('error', 'KYC Approved succesfully but NO reserved accounts created');
        }
        
    }

    public function declineCustomerKyc(Customer $customer)
    {
        $customer->update([
            "kyc_status" => 'unverified',
        ]);

        KycData::where('customer_id', $customer->id)->update([
            'status' => 'declined',
        ]);

        $subject = "KYC Info Update";
        $body = '<p>Hello! ' . $customer->user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your KYC Information was declined on ' . config('app.name') . '<br><br> Please revisit the page and enter your details again.<br/></p>';

        logEmails($customer->user->email, $subject, $body);

        return back()->with('message', 'Operation successful');

    }
}
