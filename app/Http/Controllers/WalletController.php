<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getWalletBalance($user)
    {
        return $user->customer->wallet ?? 0.00;
    }

    public function getReferralBalance($user)
    {
        return $user->customer->referal_wallet ?? 0;
    }

    public function airtime2cashBalance($user){
        return $user->customer->a2cashwallet ?? 0;
    }

    public function logWallet($data)
    {
        $wallet = Wallet::create([
            'customer_id' => $data['customer_id'] ?? auth()->user()->customer->id,
            'amount' => $data['total_amount'],
            'type' => $data['type'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
        ]);

        return $wallet;
    }

    public function updateCustomerWallet($user, $amount, $type)
    {
        if ($type == 'credit') {
            $user->customer->update([
                'wallet' => $user->customer->wallet + $amount,
            ]);
        } else {
            $user->customer->update([
                'wallet' => $user->customer->wallet - $amount,
            ]);
        }

        return;
    }

    public function updateReferralWallet($user, $amount, $type)
    {
        if ($type == 'credit') {
            $user->customer->update([
                'referal_wallet' => $user->customer->referal_wallet + $amount,
            ]);
        } else {
            $user->customer->update([
                'referal_wallet' => $user->customer->referal_wallet - $amount,
            ]);
        }

        return;
    }

    public function updatea2CashWallet($user, $amount, $type)
    {
        if ($type == 'credit') {
            $user->customer->update([
                'referal_wallet' => $user->customer->referal_wallet + $amount,
            ]);
        } else {
            $user->customer->update([
                'referal_wallet' => $user->customer->referal_wallet - $amount,
            ]);
        }

        return;
    }
}
