<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Arr;
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
            return view('customer.edit_profile');
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
}
