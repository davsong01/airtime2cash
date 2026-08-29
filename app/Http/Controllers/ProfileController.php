<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        if (auth()->user()->type == 'admin') {
            return view('admin.edit_profile');
        } else {
            $walletBankAccount = auth()->user()?->customer?->wallet_bank_account ?? null;
            $walletBankAccountMatchesProfile = false;

            if (! empty($walletBankAccount)) {
                $walletBankAccountMatchesProfile = $this->namesMatch(
                    $this->customerProfileName(auth()->user()),
                    (string) data_get($walletBankAccount, 'profile_name', '')
                );
            }

            $banks = getWalletToBankBanks();
            $adminWhatsappNumber = preg_replace('/\D+/', '', (string) (getSettings()->whatsapp_number ?? ''));
            $adminWhatsappLink = filled($adminWhatsappNumber)
                ? 'https://api.whatsapp.com/send?phone=' . $adminWhatsappNumber . '&text=' . urlencode('Please help me delete my locked wallet to bank account details on ' . config('app.name') . '.')
                : null;

            return view(themeView('customer', 'edit_profile'), compact('banks', 'walletBankAccount', 'walletBankAccountMatchesProfile', 'adminWhatsappLink'));
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if(!empty($request->new_transaction_pin)){
            $request['transaction_pin'] = base64_encode(base64_encode(base64_encode($request->new_transaction_pin)));
        }

        if (!empty($request->new_password)) {
            $request['password'] = Hash::make($request->new_password);
        }

        auth()->user()->update(Arr::except($request->all(), ['_token','new_password','new_transaction_pin']));
        // if ($request->user()->isDirty('email')) {
        //     $request->user()->email_verified_at = null;
        // }

        // $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'Profile Updated');
    }

    public function storeWalletBankAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'bank' => ['required', 'string'],
            'account_number' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $customer = $user?->customer;

        if (! $customer) {
            return back()->with('error', 'Customer record not found.');
        }

        if (! empty($customer->wallet_bank_account)) {
            return back()->with('error', 'Your wallet to bank account details are already locked. Please contact admin to delete them before adding a new one.');
        }

        $bankReference = trim((string) $request->bank);
        $bank = getWalletToBankBanks()->first(function (Bank $bank) use ($bankReference) {
            if (is_numeric($bankReference) && (int) $bankReference === (int) $bank->id) {
                return true;
            }

            return strcasecmp(trim((string) $bank->cbn_code), $bankReference) === 0
                || strcasecmp(trim((string) $bank->bank_name), $bankReference) === 0;
        });

        if (! $bank) {
            return back()->with('error', 'Invalid bank selected.');
        }

        $verification = $this->verifyWalletBankDetails([
            'bank_code' => $bank->cbn_code,
            'account_number' => trim((string) $request->account_number),
        ]);

        if (! (bool) data_get($verification, 'status', false)) {
            return back()->with('error', data_get($verification, 'message', 'Unable to verify bank details right now.'));
        }

        $providerResponse = data_get($verification, 'raw_response')
            ?? data_get($verification, 'data')
            ?? $verification;
        $verifiedAccountName = $this->extractVerifiedAccountName($providerResponse);
        $profileName = $this->customerProfileName($user);

        if (blank($verifiedAccountName)) {
            return back()->with('error', 'The verification provider did not return an account name. Please try another bank account.');
        }
        if (! $this->namesMatch($profileName, $verifiedAccountName)) {
            return back()->with('error', 'The verified account name does not match the name on your profile. Please update your profile name or contact admin.');
        }

        $customer->forceFill([
            'wallet_bank_account' => [
                'bank_id' => $bank->id,
                'bank_code' => $bank->cbn_code,
                'bank_name' => $bank->bank_name,
                'account_number' => trim((string) $request->account_number),
                'account_name' => $verifiedAccountName,
                'profile_name' => $profileName,
                'verified_name' => $verifiedAccountName,
                'verified_at' => now()->toDateTimeString(),
                // 'verificaetion_response' => $providerResponse,
            ],
        ])->save();

        return back()->with('message', 'Wallet to bank account details saved and locked successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    function generateKeys()
    {
        $user = auth()->user();

        $public = 'KPK-'.str()->random(30);
        $secret = 'KSK-' . str()->random(30);
        $api = !empty($user->api_key) ? $user->api_key : strrev(md5($user->username));

        $user->update([
            'api_key' => $api,
            'public_key' => Hash::make($public),
            'secret_key' => Hash::make($secret),
        ]);

        return [
            'code' => 1,
            'data' => [
                'api_key' => $api,
                'public' => $public,
                'secret' => $secret,
            ],
        ];
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

    private function customerProfileName(?User $user): string
    {
        return trim(collect([
            $user?->firstname,
            $user?->middlename,
            $user?->lastname,
        ])->filter()->implode(' '));
    }

    private function normalizeBankAccountName(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return trim((string) preg_replace('/[^a-z0-9]+/i', '', $value));
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
}
