<?php

namespace App\Http\Controllers;

use App\Models\KycData;
use App\Models\Customer;
use Illuminate\Http\Request;

class KycDataController extends Controller
{

    public function adminKycIndex(Request $request)
    {
        $allowedStatuses = ['review', 'awaiting-approval', 'pending', 'unverified', 'verified'];
        $status = in_array($request->query('status'), $allowedStatuses, true)
            ? $request->query('status')
            : null;
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);

        $summary = Customer::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN kyc_status IN ('awaiting-approval', 'pending') THEN 1 ELSE 0 END) as review_queue")
            ->selectRaw("SUM(CASE WHEN kyc_status = 'verified' THEN 1 ELSE 0 END) as verified")
            ->selectRaw("SUM(CASE WHEN kyc_status = 'unverified' THEN 1 ELSE 0 END) as unverified")
            ->first();

        $customers = Customer::query()
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select([
                'customers.id',
                'customers.user_id',
                'customers.kyc_status',
                'customers.updated_at',
                'users.firstname',
                'users.lastname',
                'users.username',
                'users.email',
                'users.phone',
            ])
            ->when($status === 'review', fn ($query) => $query->whereIn('customers.kyc_status', ['awaiting-approval', 'pending']))
            ->when($status && $status !== 'review', fn ($query) => $query->where('customers.kyc_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('users.firstname', 'like', "%{$search}%")
                        ->orWhere('users.lastname', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('customers.kyc_review_priority')
            ->orderBy('customers.updated_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.customers.kyc_data', compact('customers', 'summary'));
    }

    public function customerSuggestions(Request $request)
    {
        $term = mb_substr(trim((string) $request->query('q')), 0, 100);

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $parts = preg_split('/\s+/', $term, 2);
        $prefix = addcslashes($term, '%_\\') . '%';

        $customers = Customer::query()
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select([
                'customers.user_id',
                'customers.kyc_status',
                'users.firstname',
                'users.lastname',
                'users.username',
                'users.email',
                'users.phone',
            ])
            ->where(function ($query) use ($parts, $prefix) {
                if (count($parts) === 2) {
                    $first = addcslashes($parts[0], '%_\\') . '%';
                    $last = addcslashes($parts[1], '%_\\') . '%';

                    $query->where(function ($query) use ($first, $last) {
                        $query->where('users.firstname', 'like', $first)
                            ->where('users.lastname', 'like', $last);
                    });
                } else {
                    $query->where('users.firstname', 'like', $prefix)
                        ->orWhere('users.lastname', 'like', $prefix)
                        ->orWhere('users.username', 'like', $prefix)
                        ->orWhere('users.email', 'like', $prefix)
                        ->orWhere('users.phone', 'like', $prefix);
                }
            })
            ->orderBy('users.firstname')
            ->orderBy('users.lastname')
            ->limit(8)
            ->get()
            ->map(function ($customer) {
                return [
                    'name' => trim($customer->firstname . ' ' . $customer->lastname) ?: 'Unnamed customer',
                    'username' => $customer->username,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'status' => $customer->kyc_status,
                ];
            });

        return response()->json($customers)
            ->header('Cache-Control', 'private, no-store');
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
