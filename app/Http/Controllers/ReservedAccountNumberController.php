<?php

namespace App\Http\Controllers;

use App\Models\API;
use Illuminate\Http\Request;
use App\Models\TransactionLog;
use App\Models\ReservedAccount;
use App\Models\ReservedAccountNumber;

class ReservedAccountNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'gateway' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);

        $numbers = ReservedAccountNumber::with([
            'customer.user:id,firstname,middlename,lastname,email,phone',
            'admin.user:id,firstname,lastname',
            'api:id,name',
        ])->withCount('transactions')
            ->withSum('transactions as transaction_total', 'total_amount');

        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $numbers->where(function ($query) use ($search) {
                $query->where('account_name', 'like', $search)
                    ->orWhere('account_number', 'like', $search)
                    ->orWhere('bank_name', 'like', $search)
                    ->orWhereHas('customer.user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', $search)
                            ->orWhere('phone', 'like', $search)
                            ->orWhere('firstname', 'like', $search)
                            ->orWhere('lastname', 'like', $search);
                    });
            });
        }

        if ($request->filled('gateway')) {
            $numbers->where('api_id', $request->gateway);
        }

        if ($request->filled('status')) {
            $numbers->where('status', $request->status);
        }

        $summary = ReservedAccountNumber::selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active")
            ->selectRaw('COUNT(DISTINCT customer_id) AS customers')
            ->first();
        $gateways = API::orderBy('name')->get(['id', 'name']);
        $numbers = $numbers->latest('id')->paginate(25)->withQueryString();

        return view('admin.customers.reserved_account_numbers', compact('numbers', 'summary', 'gateways'));
    }

    public function delete(ReservedAccountNumber $account)
    {
        $provider = $account->api;
        $controller = resolveProviderController($provider);

        if (! $controller || ! method_exists($controller, 'deleteReservedAccount')) {
            return back()->with('error', 'This reserved account provider cannot be managed here.');
        }

        $delete = $controller->deleteReservedAccount($account->account_reference);

        if ($delete['status'] == 'success') {
            return back()->with('message', 'Reserved Account Deleted successfully');
        } else {
            return back()->with('error', $delete['data']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ReservedAccountNumber $account)
    {
        $transactions = TransactionLog::with('customer.user')
            ->where('account_number', $account->account_number)
            ->orderBy('created_at', 'DESC')
            ->paginate(25)
            ->withQueryString();
        
        return view('admin.customers.reserved_account_number_transactions', compact('transactions','account'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReservedAccount $reservedAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReservedAccount $reservedAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReservedAccount $reservedAccount)
    {
        //
    }

    public function syncProviderIds()
    {
        $monnify = API::where('slug', 'monnify')->first();

        if (! $monnify) {
            return back()->with('error', 'Monnify provider record was not found.');
        }

        ReservedAccountNumber::query()->update(['api_id' => $monnify->id]);

        return back()->with('message', 'Reserved account provider ids refreshed successfully.');
    }
}
