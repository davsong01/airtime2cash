<?php

namespace App\Http\Controllers;

use App\Models\KycData;
use App\Models\Customer;
use Illuminate\Http\Request;

class KycDataController extends Controller
{

    public function adminKycIndex()
    {
        // $customers = Customer::with('user')->orderby('awaiting-approval','pending')->get();
        $customers = Customer::with('user')
            ->orderByRaw("FIELD(kyc_status, 'awaiting-approval', 'pending','unverified') ASC")
            ->get();
        // dd($customers);
        return view('admin.customers.kyc_data', compact('customers'));
    }

    public function verifyBVN($bvn)
    {
        $verify = app('App\Http\Controllers\PaymentProcessors\MonnifyController')->verifyBvn($bvn);

        return $verify;
    }

    public function getLgaByStateName($state)
    {
        $lgas = getLgas($state);
        $res = '';

        if (!empty($lgas)) {
            foreach ($lgas as $lga) {
                $res .= '<option value="' . $lga . '">' . $lga . '</option>';
            }
        }

        return response()->json($res);
    }


    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        //
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
    public function show(KycData $kycData)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KycData $kycData)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KycData $kycData)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KycData $kycData)
    {
        //
    }
}
