<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\API;
use App\Models\Customer;
use App\Models\BlackList;
use App\Models\Bank;
use Illuminate\Http\Request;
use App\Models\CustomerLevel;
use App\Models\KycData;
use App\Models\ReferralEarning;
use App\Models\Airtime2CashTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\ReservedAccountNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Services\BvnVerificationBillingService;

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

        $customers = $customers->latest('id')->paginate(50)->withQueryString();

        $summary = User::where('users.type', '!=', 'admin')
            ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) AS active")
            ->selectRaw("SUM(CASE WHEN users.status = 'suspended' THEN 1 ELSE 0 END) AS suspended")
            ->selectRaw("SUM(CASE WHEN customers.kyc_status = 'verified' THEN 1 ELSE 0 END) AS verified")
            ->selectRaw("SUM(CASE WHEN users.created_at >= ? THEN 1 ELSE 0 END) AS new_this_month", [now()->startOfMonth()])
            ->first();

        $customer_levels = CustomerLevel::enabled()->orderBy('name')->get();
        $activeCustomerLevels = $customer_levels;

        return view('admin.customers.index', compact('customers', 'customer_levels', 'activeCustomerLevels', 'summary', 'selectedStatus'));
    }

    public function bulkActions(Request $request)
    {
        $this->validate($request, [
            'action' => ['required', 'in:activate,deactivate,suspend,delete,move_level,enable_w2bank_manual_access,disable_w2bank_manual_access,enable_w2bank_auto_access,disable_w2bank_auto_access,enable_a2c_access,disable_a2c_access'],
            'customer_ids' => ['required', 'string'],
            'level_id' => ['nullable', 'integer'],
        ]);

        $customerIds = collect(explode(',', $request->customer_ids))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($customerIds)) {
            return back()->with('error', 'Please select at least one customer');
        }

        if ($request->action === 'move_level') {
            $this->validate($request, [
                'level_id' => ['required', 'integer', 'exists:customer_levels,id'],
            ]);

            $level = CustomerLevel::enabled()->where('id', $request->level_id)->first();

            if (!$level) {
                return back()->with('error', 'Please select an enabled customer level');
            }

            Customer::whereIn('user_id', $customerIds)->update([
                'customer_level' => $level->id,
            ]);

            return back()->with('message', 'Selected customers moved to ' . $level->name . ' successfully');
        }

        if (in_array($request->action, [
            'enable_w2bank_manual_access',
            'disable_w2bank_manual_access',
            'enable_w2bank_auto_access',
            'disable_w2bank_auto_access',
            'enable_a2c_access',
            'disable_a2c_access',
        ], true)) {
            $updates = match ($request->action) {
                'enable_w2bank_manual_access' => ['can_access_w2bank' => 1],
                'disable_w2bank_manual_access' => ['can_access_w2bank' => 0],
                'enable_w2bank_auto_access' => ['can_access_w2bank_auto' => 1],
                'disable_w2bank_auto_access' => ['can_access_w2bank_auto' => 0],
                'enable_a2c_access' => ['can_access_a2c' => 1],
                'disable_a2c_access' => ['can_access_a2c' => 0],
            };

            Customer::whereIn('user_id', $customerIds)->update($updates);

            return back()->with('message', 'Selected customers updated successfully');
        }

        $status = match ($request->action) {
            'activate' => 'active',
            'deactivate' => 'inactive',
            'suspend' => 'suspended',
            'delete' => 'delete',
        };

        User::whereIn('id', $customerIds)
            ->where('type', '!=', 'admin')
            ->update([
                'status' => $status,
            ]);

        return back()->with('message', 'Selected customers updated successfully');
    }

    function unverifiedCustomers(Request $request, $status = null)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $customers = User::where('type', '!=', 'admin')
            ->where(function ($query) {
                $query->whereNull('email_verified_at')
                    ->orWhere('email_verified_at', '')
                    ->orWhere('email_verified_at', '0000-00-00 00:00:00');
            })
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'verified');
            });

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
        $reserved = createReservedAccount($data, $admin_id);

        if ($reserved['status'] && $reserved['status'] == 'success') {
            return back()->with('message', 'Reserved Account(s) created successfully');
        } else {
            return back()->with('error', 'Error: ' . $reserved['data'] ?? 'Something went wrong');
        }
    }

    function singleCustomer(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return redirect(404);
        }

        $allowedTabs = ['account', 'transactions', 'airtime2cash-transactions', 'downlines', 'kyc', 'reserved-account', 'actions'];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'account';

        $user = User::with(['customer.level'])->findOrFail($id);
        $customer = $user->customer->id;

        $curr = getSettings()?->currency ?? '₦';
        $balance = $curr . number_format(walletBalance($user), 2) ?? 0;
        $ref = $curr . number_format(referralBalance($user), 2) ?? 0;
        $transactionSummary = $user->customer->transactions()
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN api_id IS NOT NULL AND unique_element = \'WALLET-FUNDING\' THEN amount ELSE 0 END), 0) as funded_total')
            ->first();
        $airtimeTransactionSummary = Airtime2CashTransactions::where('customer_id', $customer)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('approved', 'successful') THEN total_amount ELSE 0 END), 0) as successful_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END), 0) as declined_count")
            ->first();
        $transTotal = $curr . number_format((float) $transactionSummary->total, 2);
        $fundTotal = $curr . number_format((float) $transactionSummary->funded_total, 2);
        $a2cTotal = $curr . number_format((float) $airtimeTransactionSummary->total, 2);
        $balances = ['Wallet Balance' => $balance, 'Referral Earning' => $ref, 'Transaction Total' => $transTotal, 'A2C Total' => $a2cTotal, 'Funds Total' => $fundTotal];
        $settings = getSettings();
        $downlines = collect();
        $reservedAccount = collect();
        $availableReservedBanks = collect();
        $banks = collect();
        $transactions = null;
        $airtimeTransactions = null;
        $kycData = collect();
        $blacklists = collect();
        $customerLevels = collect();

        if ($activeTab === 'account') {
            $customerLevels = CustomerLevel::enabled()->orderBy('order', 'ASC')->get();
            $banks = getWalletToBankBanks();
        } elseif ($activeTab === 'transactions') {
            $transactions = $user->customer->transactions()->latest()->paginate(10);
        } elseif ($activeTab === 'airtime2cash-transactions') {
            $airtimeTransactions = Airtime2CashTransactions::with(['product', 'provider', 'wallets'])
                ->where('customer_id', $customer)
                ->latest()
                ->paginate(10);
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
            $reservedBankCodes = $reservedAccount
                ->pluck('bank_code')
                ->filter()
                ->map(fn ($code) => trim((string) $code))
                ->unique()
                ->values()
                ->all();
            $reservedBankOptions = collect([
                (object) ['cbn_code' => '50515', 'bank_name' => 'Moniepoint'],
                (object) ['cbn_code' => '035', 'bank_name' => 'Wema Bank'],
                (object) ['cbn_code' => '058', 'bank_name' => 'Guaranty Trust Bank'],
            ]);
            $availableReservedBanks = $reservedBankOptions
                ->reject(fn ($bank) => in_array((string) $bank->cbn_code, $reservedBankCodes, true))
                ->values();
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
                'airtimeTransactions' => $airtimeTransactions,
                'kycData' => $kycData,
                'blacklists' => $blacklists,
                'activeTab' => $activeTab,
                'availableReservedBanks' => $availableReservedBanks,
                'banks' => $banks,
                'settings' => $settings,
            ]
        );
    }

    function updateCustomer(Request $request, $id = null)
    {
        $validated = $request->validate([
            'status' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'can_access_w2bank' => ['nullable', 'in:0,1'],
            'can_access_w2bank_auto' => ['nullable', 'in:0,1'],
            'can_access_a2c' => ['nullable', 'in:0,1'],
        ]);

        $level = null;

        if (! empty($request->customerlevel)) {
            $level = CustomerLevel::enabled()->where('id', $request->customerlevel)->first();

            if (! $level) {
                return back()->with('error', 'Please select an enabled customer level');
            }
        }

        DB::transaction(function () use ($request, $id, $validated, $level) {
            $user = User::query()->findOrFail($id);
            $customer = Customer::query()->where('user_id', $user->id)->lockForUpdate()->first();

            $user->update($request->except(['_token', 'ip', 'customerlevel', 'can_access_w2bank', 'can_access_w2bank_auto', 'can_access_a2c']));

            if ($customer) {
                $customer->forceFill([
                    'can_access_w2bank' => (int) ($validated['can_access_w2bank'] ?? 0),
                    'can_access_w2bank_auto' => (int) ($validated['can_access_w2bank_auto'] ?? 0),
                    'can_access_a2c' => (int) ($validated['can_access_a2c'] ?? 0),
                ])->save();
            }

            if ($level && $customer) {
                $previousLevelName = $customer->level?->name ?? 'Unassigned';

                $customer->customer_level = $level->id;

                if (!empty($level->transaction)) {
                    $level->transaction->update([
                        'status' => 'success',
                        'descr' => 'Level Upgrade from ' . $previousLevelName . ' to ' . $level->name . ' was successful',
                    ]);
                    $customer->api_access = 'active';
                }

                $customer->save();
            }
        });

        return back()->with('message', 'Update successful!');

    }

    public function updateWalletBankAccount(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'wallet_bank_bank' => ['required', 'string', 'max:50'],
            'wallet_bank_account_name' => ['nullable', 'string', 'max:255'],
            'wallet_bank_account_number' => ['nullable', 'string', 'max:50'],
            'wallet_bank_profile_name' => ['nullable', 'string', 'max:255'],
            'wallet_bank_verified_name' => ['nullable', 'string', 'max:255'],
            'wallet_bank_verified_at' => ['nullable', 'date'],
            'wallet_bank_verification_response' => ['nullable', 'string'],
        ]);

        $existing = is_array($customer->wallet_bank_account) ? $customer->wallet_bank_account : [];
        $bankReference = trim((string) $validated['wallet_bank_bank']);
        $bank = getWalletToBankBanks()->first(function (Bank $bank) use ($bankReference) {
            if (is_numeric($bankReference) && (int) $bankReference === (int) $bank->id) {
                return true;
            }

            return strcasecmp(trim((string) $bank->cbn_code), $bankReference) === 0
                || strcasecmp(trim((string) $bank->bank_name), $bankReference) === 0;
        });

        if (! $bank) {
            return back()->with('error', 'Please select a valid active bank.');
        }

        $accountNumber = trim((string) ($validated['wallet_bank_account_number'] ?? data_get($existing, 'account_number', '')));
        if ($accountNumber === '') {
            return back()->with('error', 'Please provide an account number.');
        }

        $submittedAccountName = trim((string) ($validated['wallet_bank_account_name'] ?? ''));

        $profileName = filled($validated['wallet_bank_profile_name'] ?? null)
            ? trim((string) $validated['wallet_bank_profile_name'])
            : data_get($existing, 'profile_name');

        $next = [
            'bank_id' => $bank->id,
            'bank_name' => $bank->bank_name,
            'bank_code' => $bank->cbn_code,
            'account_name' => filled($submittedAccountName) ? $submittedAccountName : data_get($existing, 'account_name'),
            'account_number' => $accountNumber,
            'profile_name' => $profileName,
            'verified_name' => filled($validated['wallet_bank_verified_name'] ?? null)
                ? $validated['wallet_bank_verified_name']
                : data_get($existing, 'verified_name'),
            'verified_at' => filled($validated['wallet_bank_verified_at'] ?? null)
                ? Carbon::parse($validated['wallet_bank_verified_at'])->toDateTimeString()
                : data_get($existing, 'verified_at', now()->toDateTimeString()),
        ];

        $verificationResponse = $validated['wallet_bank_verification_response'] ?? null;
        if (filled($verificationResponse)) {
            $decoded = json_decode($verificationResponse, true);
            $next['verification_response'] = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : $verificationResponse;
        } elseif (array_key_exists('verification_response', $existing)) {
            $next['verification_response'] = $existing['verification_response'];
        }

        $customer->forceFill([
            'wallet_bank_account' => array_filter($next, static fn ($value) => ! is_null($value) && $value !== ''),
        ])->save();

        return back()->with('message', 'Wallet to bank account details updated successfully.');
    }

    public function deleteWalletBankAccount(Customer $customer)
    {
        $customer->forceFill([
            'wallet_bank_account' => null,
        ])->save();

        return back()->with('message', 'Wallet to bank account details deleted successfully.');
    }

    private function verifyWalletBankDetails(array $data): array
    {
        $providerId = getSettings()->bank_verification_provider_id ?: getSettings()->bank_transfer_provider_id;
        $provider = API::where('id', $providerId)->where('status', 'active')->first();

        if (! $provider) {
            return [
                'status' => false,
                'message' => 'No active bank verification provider configured.',
            ];
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'verifyBankDetails')) {
            return [
                'status' => false,
                'message' => "Bank verification is not supported for {$provider->slug}.",
            ];
        }

        $response = $controller->verifyBankDetails($data);

        return $response instanceof JsonResponse
            ? $response->getData(true)
            : (is_array($response) ? $response : []);
    }

    private function extractVerifiedAccountName(array $response): string
    {
        $candidates = [
            data_get($response, 'data.account_name'),
            data_get($response, 'data.accountName'),
            data_get($response, 'responseBody.accountName'),
            data_get($response, 'responseBody.account_name'),
            data_get($response, 'account_name'),
            data_get($response, 'accountName'),
            data_get($response, 'data.data.account_name'),
            data_get($response, 'data.data.accountName'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function normalizeBankAccountName(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return trim((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }

    private function sortedNameTokens(string $value): array
    {
        $tokens = preg_split('/\s+/', trim(mb_strtolower((string) $value))) ?: [];

        $tokens = array_values(array_filter(array_map(function ($token) {
            $token = preg_replace('/[^a-z0-9]+/i', '', $token);

            return trim((string) $token);
        }, $tokens), fn ($token) => $token !== ''));

        sort($tokens);

        return $tokens;
    }

    private function namesMatch(string $left, string $right): bool
    {
        $normalizedLeft = $this->normalizeBankAccountName($left);
        $normalizedRight = $this->normalizeBankAccountName($right);

        if ($normalizedLeft === $normalizedRight) {
            return true;
        }

        return $this->sortedNameTokens($left) === $this->sortedNameTokens($right);
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

    public function reviewCustomerKycField(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in([
                'FIRST_NAME',
                'MIDDLE_NAME',
                'LAST_NAME',
                'PHONE_NUMBER',
                'DOB',
                'BVN',
                'IDCARDTYPE',
                'IDCARD',
            ])],
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'value' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $field = $validated['field'];
        $action = $validated['action'];
        $fieldLabel = $this->kycFieldLabel($field);
        $pairedField = in_array($field, ['IDCARDTYPE', 'IDCARD'], true)
            ? ($field === 'IDCARDTYPE' ? 'IDCARD' : 'IDCARDTYPE')
            : null;
        $submittedValue = trim((string) ($validated['value'] ?? ''));
        $existing = KycData::query()
            ->where('customer_id', $customer->id)
            ->where('key', $field)
            ->first();
        $pairedExisting = $pairedField
            ? KycData::query()
                ->where('customer_id', $customer->id)
                ->where('key', $pairedField)
                ->first()
            : null;
        $storedValue = filled($submittedValue)
            ? $submittedValue
            : trim((string) data_get($existing, 'value', ''));
        $pairedValue = trim((string) data_get($pairedExisting, 'value', ''));

        if ($action === 'approve' && blank($storedValue) && $field !== 'IDCARD') {
            return response()->json([
                'status' => false,
                'message' => 'This field cannot be approved without a value.',
            ], 422);
        }

        DB::transaction(function () use ($customer, $field, $action, $storedValue, $validated, $fieldLabel, $pairedField, $pairedValue) {
            $reviewNote = $action === 'reject' && filled($validated['reason'] ?? null)
                ? trim((string) $validated['reason'])
                : null;

            $reviewData = [
                'customer_id' => $customer->id,
                'key' => $field,
                'value' => $storedValue,
                'status' => $action === 'approve' ? 'verified' : 'declined',
                'review_note' => $reviewNote,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ];

            KycData::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'key' => $field,
                ],
                $reviewData
            );

            if ($pairedField) {
                KycData::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'key' => $pairedField,
                    ],
                    [
                        'customer_id' => $customer->id,
                        'key' => $pairedField,
                        'value' => $pairedValue,
                        'status' => $action === 'approve' ? 'verified' : 'declined',
                        'review_note' => $reviewNote,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]
                );
            }

            if ($action === 'approve') {
                if ($field === 'FIRST_NAME') {
                    $customer->user?->forceFill(['firstname' => $storedValue])->save();
                } elseif ($field === 'MIDDLE_NAME') {
                    $customer->user?->forceFill(['middlename' => $storedValue])->save();
                } elseif ($field === 'LAST_NAME') {
                    $customer->user?->forceFill(['lastname' => $storedValue])->save();
                } elseif ($field === 'PHONE_NUMBER') {
                    $customer->user?->forceFill(['phone' => $storedValue])->save();
                } elseif ($field === 'BVN' && filled($storedValue)) {
                    $customer->forceFill([
                        'bvn' => $storedValue,
                    ])->save();
                }
            } else {
                $customer->forceFill([
                    'kyc_status' => 'unverified',
                    'kyc_rejection_reason' => $fieldLabel . ' rejected: ' . trim((string) $validated['reason']),
                ])->save();
            }
        });

        if ($action === 'approve' && $this->kycFieldsAreFullyVerified($customer->id)) {
            $this->finalizeCustomerKycApproval($customer);
        }

        return response()->json([
            'status' => true,
            'message' => $action === 'approve'
                ? ($fieldLabel . ' approved successfully.')
                : ($fieldLabel . ' rejected and returned to the customer for correction.'),
            'field' => $field,
            'field_label' => $fieldLabel,
            'action' => $action,
            'customer_kyc_status' => $customer->fresh()->kyc_status,
        ]);
    }

    public function verifyCustomerBvn(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'bvn' => ['nullable', 'digits:11'],
        ]);

        $bvn = trim((string) ($validated['bvn'] ?? data_get(kycStatus('BVN', $customer->id), 'value', $customer->bvn)));

        if (blank($bvn)) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide a BVN before verification.',
            ], 422);
        }

        $settings = getSettings();
        if (($settings?->bvn_verification_mode ?? 'manual') !== 'auto') {
            return response()->json([
                'status' => false,
                'message' => 'BVN verification is currently set to manual mode. Please review and approve the BVN field manually.',
            ], 422);
        }

        if ((float) ($settings?->bvn_verification_charge ?? 0) <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'BVN verification charge is not set. Please configure the charge in admin settings before verifying BVN.',
            ], 422);
        }

        $providerId = $settings?->bvn_verification_provider_id
            ?: $settings?->bank_verification_provider_id
            ?: $settings?->bank_transfer_provider_id;
        $provider = API::query()
            ->where('id', $providerId)
            ->where('status', 'active')
            ->first();

        if (! $provider) {
            return response()->json([
                'status' => false,
                'message' => 'No active BVN verification provider configured.',
            ], 422);
        }

        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'verifyBvn')) {
            return response()->json([
                'status' => false,
                'message' => 'The selected provider does not support BVN verification.',
            ], 422);
        }

        $verification = $controller->verifyBvn($bvn, $customer);
        $verificationData = $verification instanceof JsonResponse ? $verification->getData(true) : (is_array($verification) ? $verification : []);
        $providerResponse = data_get($verificationData, 'api_response', $verificationData);
        $payload = data_get($verificationData, 'payload', []);
        $verifiedName = $this->extractBvnVerifiedName($providerResponse);
        $profileName = $this->customerProfileName($customer->user);
        $providerNameMatch = filter_var(data_get($providerResponse, 'responseBody.bvnInformationMatch', false), FILTER_VALIDATE_BOOL);
        $nameMatch = $providerNameMatch || ($verifiedName !== '' && $this->namesMatch($profileName, $verifiedName));
        $verificationStatus = (($verificationData['status'] ?? null) === 'success') ? 'verified' : 'unverified';

        DB::transaction(function () use ($customer, $bvn, $verificationStatus, $verificationData, $provider, $providerResponse, $payload, $verifiedName, $profileName, $nameMatch) {
            $customer->forceFill([
                'bvn' => $bvn,
                'bvn_verification_status' => $verificationStatus,
                'bvn_data' => [
                    'provider_id' => $provider->id,
                    'provider_slug' => $provider->slug,
                    'provider_name' => $provider->name,
                    'status' => $verificationStatus,
                    'name_match' => $nameMatch,
                    'profile_name' => $profileName,
                    'verified_name' => $verifiedName,
                    'verified_at' => now()->toDateTimeString(),
                    'payload' => $payload,
                    'response' => $providerResponse,
                    'message' => data_get($verificationData, 'message'),
                ],
            ])->save();

            KycData::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'key' => 'BVN',
                ],
                [
                    'value' => $bvn,
                    'status' => $verificationStatus,
                    'review_note' => null,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]
            );
        });

        $billingSummary = [
            'status' => 'skipped',
            'settled' => true,
            'amount' => 0,
            'transaction_id' => null,
        ];

        if ($verificationStatus === 'verified') {
            try {
                $billingSummary = app(BvnVerificationBillingService::class)->recordCharge($customer->fresh(['user']), (float) ($settings?->bvn_verification_charge ?? 0), [
                    'bvn' => $bvn,
                    'customer_id' => $customer->id,
                    'provider_id' => $provider->id,
                    'provider_slug' => $provider->slug,
                    'provider_name' => $provider->name,
                    'verification_reference' => data_get($providerResponse, 'responseBody.requestId')
                        ?? data_get($providerResponse, 'requestId')
                        ?? data_get($verificationData, 'request_id')
                        ?? $bvn,
                    'profile_name' => $profileName,
                    'verified_name' => $verifiedName,
                    'name_match' => $nameMatch,
                    'verified_at' => now()->toDateTimeString(),
                    'status' => $verificationStatus,
                    'settled_description' => 'BVN verification fee was charged successfully.',
                    'pending_description' => 'BVN verification fee is pending wallet funding.',
                ]);
            } catch (\Throwable $throwable) {
                \Log::error('Unable to record BVN verification billing charge.', [
                    'customer_id' => $customer->id,
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                ]);
            }
        }

        return response()->json([
            'status' => $verificationStatus === 'verified',
            'message' => $verificationStatus === 'verified'
                ? 'BVN verified successfully.'
                : 'BVN verification failed.',
            'verification_status' => $verificationStatus,
            'name_match' => $nameMatch,
            'profile_name' => $profileName,
            'verified_name' => $verifiedName,
            'provider_response' => $providerResponse,
            'stored_data' => $customer->fresh()->bvn_data,
            'billing_status' => $billingSummary['status'] ?? 'skipped',
            'billing_settled' => $billingSummary['settled'] ?? true,
            'billing_amount' => $billingSummary['amount'] ?? 0,
            'billing_transaction_id' => data_get($billingSummary, 'transaction.transaction_id'),
        ]);
    }

    public function approveCustomerKyc(Customer $customer){
        $result = $this->finalizeCustomerKycApproval($customer, true);

        return back()->with(
            $result['reserved_account_created']
                ? 'message'
                : 'error',
            $result['reserved_account_created']
                ? 'KYC Approved succesfully and reserved accounts created'
                : 'KYC Approved succesfully but NO reserved accounts created'
        );
    }

    private function kycFieldsAreFullyVerified(int $customerId): bool
    {
        $reviewableFields = ['FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'PHONE_NUMBER', 'DOB', 'BVN', 'IDCARDTYPE', 'IDCARD'];
        $kycRows = KycData::where('customer_id', $customerId)
            ->whereIn('key', $reviewableFields);

        return $kycRows->count() === count($reviewableFields)
            && ! (clone $kycRows)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'verified');
            })
            ->exists();
    }

    private function finalizeCustomerKycApproval(Customer $customer, bool $force = false): array
    {
        $customer->refresh();

        if (! $force && $customer->kyc_status === 'verified') {
            return [
                'reserved_account_created' => true,
            ];
        }

        KycData::where('customer_id', $customer->id)->update([
            'status' => 'verified',
        ]);

        $customer->update([
            'kyc_status' => 'verified',
            'kyc_rejection_reason' => null,
        ]);

        $data = [
            'BVN' => data_get(kycStatus('BVN', $customer->id), 'value'),
            'customerName' => $customer->user->username,
            'accountName' => data_get(kycStatus('FIRST_NAME', $customer->id), 'value'),
            'customerEmail' => $customer->user->email,
            'customer_id' => $customer->id,
            'getAllAvailableBanks' => true,
        ];

        $subject = "KYC Info Update";
        $body = '<p>Hello! ' . $customer->user->firstname . '</p>';
        $body .= '<p style="line-height: 2.0;">Your KYC Information has been approved ' . config('app.name') . '<br><br> You can now carry out transactions<br/></p>';

        logEmails($customer->user->email, $subject, $body);

        $reserved = createReservedAccount($data);

        return [
            'reserved_account_created' => (($reserved['status'] ?? null) === 'success'),
            'reserved_account_response' => $reserved,
        ];
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

    private function kycFieldLabel(string $key): string
    {
        return match ($key) {
            'FIRST_NAME' => 'First name',
            'MIDDLE_NAME' => 'Middle name',
            'LAST_NAME' => 'Last name',
            'PHONE_NUMBER' => 'Phone number',
            'DOB' => 'Date of birth',
            'BVN' => 'BVN',
            'IDCARDTYPE' => 'ID card type',
            'IDCARD' => 'Identity document',
            default => ucfirst(strtolower(str_replace('_', ' ', $key))),
        };
    }

    private function customerProfileName(?User $user): string
    {
        return trim(collect([
            $user?->firstname,
            $user?->middlename,
            $user?->lastname,
        ])->filter()->implode(' '));
    }

    private function extractBvnVerifiedName(array $response): string
    {
        $candidates = [
            data_get($response, 'responseBody.name'),
            data_get($response, 'responseBody.bvnName'),
            data_get($response, 'responseBody.accountName'),
            data_get($response, 'responseBody.customerName'),
            data_get($response, 'data.name'),
            data_get($response, 'data.accountName'),
            data_get($response, 'data.customerName'),
            data_get($response, 'name'),
            data_get($response, 'customerName'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}
