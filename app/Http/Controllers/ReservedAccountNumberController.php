<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionLog;
use App\Models\ReservedAccount;
use App\Models\ReservedAccountNumber;

class ReservedAccountNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $numbers = ReservedAccountNumber::with('transactions')->orderBy('customer_id')->get();
        return view('admin.customers.reserved_account_numbers', compact('numbers'));
    }

    public function delete(ReservedAccountNumber $account)
    {
        if ($account->paymentgateway_id == 1) {
            $delete = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->deleteReservedAccount($account->account_reference);
        }

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
        $transactions = TransactionLog::where('account_number', $account->account_number)->orderBy('created_at', 'DESC')->get();
        
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
}
