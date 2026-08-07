<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use App\Models\BlackList;
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
        $request->validate([
            'status' => ['nullable', 'in:active,api,delete,suspended,email-blacklist,phone-blacklist'],
            'level' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $selectedStatus = $status ?: $request->status;
        $customers = User::with(['customer.level'])->where('type', '!=', 'admin');

        if ($selectedStatus) {
            if ($selectedStatus == 'active') {
                $customers->where('status', 'active');
            } elseif ($selectedStatus == 'api') {
                $customers->where('type', 'api');
            } elseif ($selectedStatus == 'delete') {
                $customers->where('status', 'delete');
            } elseif ($selectedStatus == 'suspended') {
                $customers->where('status', 'suspended');
            } elseif ($selectedStatus == 'email-blacklist') {
                $customers->where('status', 'email-blacklist');
            } elseif ($selectedStatus == 'phone-blacklist') {
                $customers->where('status', 'phone-blacklist');
            } else {
                abort(404);
            }
        }

        if ($request->filled('search')) {
            $key = "%{$request->search}%";
            $customers->where(function ($query) use ($key) {
                $query->where('firstname', 'like', $key)
                    ->orWhere('lastname', 'like', $key)
                    ->orWhere('middlename', 'like', $key)
                    ->orWhere('username', 'like', $key)
                    ->orWhere('email', 'like', $key)
                    ->orWhere('phone', 'like', $key);
            });
        }

        if ($request->filled('email')) {
            $customers->where('email', 'like', '%' . trim($request->email) . '%');
        }

        if ($request->filled('username')) {
            $customers->where('username', 'like', '%' . trim($request->username) . '%');
        }

        if ($request->filled('mobile')) {
            $customers->where('phone', 'like', '%' . trim($request->mobile) . '%');
        }

        if ($request->filled('level')) {
            $customers->whereHas('customer', function ($query) use ($request) {
                $query->where('customer_level', $request->level);
            });
        }

        if ($request->from && $request->to) {
            $customers->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $customers->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $customers->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $customers = $customers->latest('id')->paginate(25)->withQueryString();

        $summary = User::where('users.type', '!=', 'admin')
            ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) AS active")
            ->selectRaw("SUM(CASE WHEN users.status = 'suspended' THEN 1 ELSE 0 END) AS suspended")
            ->selectRaw("SUM(CASE WHEN customers.kyc_status = 'verified' THEN 1 ELSE 0 END) AS verified")
            ->selectRaw("SUM(CASE WHEN users.created_at >= ? THEN 1 ELSE 0 END) AS new_this_month", [now()->startOfMonth()])
            ->first();

        $customer_levels = CustomerLevel::orderBy('name')->get();

        return view('admin.customers.index', compact('customers', 'customer_levels', 'summary', 'selectedStatus'));
    }

    function unverifiedCustomers(Request $request, $status = null)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $customers = User::where('type', '!=', 'admin')->whereNull('email_verified_at');

        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $customers->where(function ($query) use ($search) {
                $query->where('firstname', 'like', $search)
                    ->orWhere('lastname', 'like', $search)
                    ->orWhere('username', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        if ($request->from && $request->to) {
            $customers->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        } elseif ($request->from) {
            $customers->where('created_at', '>=', $request->from . ' 00:00:00');
        } elseif ($request->to) {
            $customers->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $summary = User::where('type', '!=', 'admin')
            ->selectRaw('SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified')
            ->selectRaw('SUM(CASE WHEN email_verified_at IS NULL AND created_at >= ? THEN 1 ELSE 0 END) AS new_this_month', [now()->startOfMonth()])
            ->selectRaw('SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS verified')
            ->first();

        $customers = $customers->select('id', 'firstname', 'lastname', 'email', 'phone', 'status', 'created_at', 'email_verified_at', 'username')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.unverified', compact('customers', 'summary'));
    }

    function verifyCustomer($customer, $internal=null)
    {
        $customer = User::where('id', $customer)->first();
        if ($customer) {
            $customer->update([
                'email_verified_at' => Carbon::now(),
            ]);

            return back()->with('message', 'Operation successful');
        } else {
            return back()->with('error', 'Customer not found');
        }
    }

    function deleteCustomer($customer, $internal=null)
    {
        $user = User::where('id', $customer)->first();

        if ($user && is_null($user->email_verified_at)) {
            if($user->customer){
                $user->customer->delete();
            }

            if ($user->reserved_accounts) {
                $user->reserved_accounts->delete();
            }

            $user->delete();

            if($internal){
                return true;
            }

            return back()->with('message', 'Operation successful');
        } else {
            if ($internal) {
                return true;
            }
            return back()->with('error', 'Customer not found or already verified');
        }
    }

    public function verifyMultiActions(Request $request){
        set_time_limit(3600);
        $customer_ids = $request->customer_ids;

        if(!empty($customer_ids)){
            $customer_ids = explode(',', $customer_ids);
            foreach($customer_ids as $id){
                if($request->action == 'verify'){
                    $this->verifyCustomer($id, 'internal');
                }

                if($request->action == 'delete'){
                    $this->deleteCustomer($id, 'internal');
                }
            }
        }

        return back()->with('message', 'Operation Successful');
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

    function singleCustomer(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return redirect(404);
        }

        $allowedTabs = ['account', 'transactions', 'downlines', 'kyc', 'reserved-account', 'actions'];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'account';

        $user = User::with(['customer.level'])->findOrFail($id);
        $customer = $user->customer->id;

        $curr = getSettings()->currency;
        $balance = $curr . number_format(walletBalance($user), 2) ?? 0;
        $ref = $curr . number_format(referralBalance($user), 2) ?? 0;
        $transactionSummary = $user->customer->transactions()
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN wallet_funding_provider IS NOT NULL THEN amount ELSE 0 END), 0) as funded_total')
            ->first();
        $transTotal = $curr . number_format((float) $transactionSummary->total, 2);
        $fundTotal = $curr . number_format((float) $transactionSummary->funded_total, 2);
        $balances = ['Wallet Balance' => $balance, 'Referral Earning' => $ref, 'Transaction Total' => $transTotal, 'Funds Total' => $fundTotal];
        $downlines = collect();
        $reservedAccount = collect();
        $transactions = null;
        $kycData = collect();
        $blacklists = collect();
        $customerLevels = collect();

        if ($activeTab === 'account') {
            $customerLevels = CustomerLevel::orderBy('order', 'ASC')->get();
        } elseif ($activeTab === 'transactions') {
            $transactions = $user->customer->transactions()->latest()->paginate(10);
        } elseif ($activeTab === 'downlines') {
            $downlines = ReferralEarning::where('customer_id', $customer)
                ->with('referredCustomer.user')
                ->latest()
                ->groupBy('referred_customer_id')
                ->get(['*', DB::raw('sum(amount) as total')]);
        } elseif ($activeTab === 'kyc') {
            $kycData = KycData::where('customer_id', $customer)->get()->keyBy('key');
        } elseif ($activeTab === 'reserved-account') {
            $reservedAccount = ReservedAccountNumber::with(['gateway', 'admin.user'])
                ->withCount('transactions')
                ->withSum('transactions as transaction_total', 'total_amount')
                ->where('customer_id', $customer)
                ->orderBy('created_at', 'desc')
                ->get();
            $kycData = KycData::where('customer_id', $customer)
                ->where('key', 'BVN')
                ->get()
                ->keyBy('key');
        } elseif ($activeTab === 'actions') {
            $blacklists = BlackList::whereIn('value', array_filter([$user->email, $user->phone]))
                ->get()
                ->keyBy('value');
        }

        return view(
            'admin.customers.single-customer',
            [
                'user' => $user,
                'downlines' => $downlines,
                'accounts' => $reservedAccount,
                'balances' => $balances,
                'customerLevels' => $customerLevels,
                'transactions' => $transactions,
                'kycData' => $kycData,
                'blacklists' => $blacklists,
                'activeTab' => $activeTab,
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
        $subject = "New Password";
        $body = '<p>Hello! ' . $user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your password has been reset by ADMIN on ' . config('app.name') . ' at ' . Carbon::now()->format('jS F, Y, h:iA') . '.</strong><br><br>Your new password is <br><strong>' . $request->new_password . '</strong><br>. If you did not request a password reset, Kindly notify us via WhatsApp us via whatsapp( ' . $settings->whatsapp_no . ') immediately.<b><hr/><br>Warm Regards. (' . config('app.name') . ')<br/></p>';

        logEmails($user->email, $subject, $body);

        return back()->with('message', 'Password successfully reset to: ' . $request->new_password);
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
        $items = 0;
        foreach ($input as $key => $value) {
            if(!empty($value)){
                // if($key == 'IDCARD'){
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
            'kyc_rejection_reason' => null,
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

    public function declineCustomerKyc(Request $request, Customer $customer)
    {
        $validated = $request->validateWithBag('kycRejection', [
            'kyc_rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $reason = trim($validated['kyc_rejection_reason']);

        $customer->update([
            "kyc_status" => 'unverified',
            'kyc_rejection_reason' => $reason,
        ]);

        KycData::where('customer_id', $customer->id)->update([
            'status' => 'declined',
        ]);

        $subject = "KYC Info Update";
        $body = '<p>Hello! ' . $customer->user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your KYC Information was declined on ' . config('app.name') . '.<br><br>';
        $body .= '<strong>Reason:</strong><br>' . nl2br(e($reason)) . '<br><br>Please revisit the KYC page, correct the information, and submit it again.<br/></p>';

        logEmails($customer->user->email, $subject, $body);

        return back()->with('message', 'KYC rejected and the customer has been notified of the reason.');

    }
}
