<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::orderBy('bank_name')->get();
        $stats = [
            'total' => $banks->count(),
            'active' => $banks->where('status', 'active')->count(),
            'inactive' => $banks->where('status', 'inactive')->count(),
        ];

        return view('admin.banks.index', compact('banks', 'stats'));
    }

    public function create()
    {
        $providers = $this->bankProviders();

        return view('admin.banks.edit', compact('providers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateBank($request);

        Bank::create($data);

        return redirect()->route('banks.index')->with('message', 'Bank added successfully');
    }

    public function edit(Bank $bank)
    {
        $providers = $this->bankProviders();

        return view('admin.banks.edit', compact('bank', 'providers'));
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $this->validateBank($request);

        $bank->update($data);

        return redirect()->route('banks.index')->with('message', 'Bank updated successfully');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();

        return back()->with('message', 'Bank deleted successfully');
    }

    private function validateBank(Request $request): array
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'cbn_code' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'provider_codes' => ['nullable', 'array'],
            'provider_codes.*' => ['nullable', 'string', 'max:255'],
        ]);

        $providerCodes = $this->decodeProviderCodes($validated['provider_codes'] ?? null);

        return [
            'bank_name' => $validated['bank_name'],
            'cbn_code' => $validated['cbn_code'],
            'status' => $validated['status'],
            'provider_codes' => $providerCodes,
        ];
    }

    private function decodeProviderCodes($providerCodes): array
    {
        if (is_array($providerCodes)) {
            return array_filter($providerCodes, fn ($value) => filled($value));
        }

        if (blank($providerCodes)) {
            return [];
        }

        $decoded = json_decode($providerCodes, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function bankProviders()
    {
        return API::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('is_bank_transfer', true)
                    ->orWhere('is_bank_verification', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
