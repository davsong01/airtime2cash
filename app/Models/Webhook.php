<?php

namespace App\Models;

use App\Models\API;
use Illuminate\Support\Str;
use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'webhooks';

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Airtime2CashTransactions::class, 'transaction_id', 'transaction_id');
    }

    public function transactionLog()
    {
        return $this->belongsTo(TransactionLog::class, 'transaction_id', 'transaction_id');
    }

    public function linkedTransaction()
    {
        if ($this->relationLoaded('transaction') && $this->transaction) {
            return $this->transaction;
        }

        if ($this->relationLoaded('transactionLog') && $this->transactionLog) {
            return $this->transactionLog;
        }

        if ($this->transaction) {
            return $this->transaction;
        }

        return $this->transactionLog;
    }

    public function hasUnresolvedTransaction(): bool
    {
        $transaction = $this->linkedTransaction();

        if (! $transaction) {
            return false;
        }

        $status = strtolower((string) ($transaction->status ?? ''));

        return ! in_array($status, [
            'approved',
            'declined',
            'failed',
            'successful',
            'success',
            'completed',
            'delivered',
        ], true);
    }

    public function isWalletToBankTransaction(): bool
    {
        $transaction = $this->linkedTransaction();

        if (! $transaction instanceof TransactionLog) {
            return false;
        }

        return Str::of((string) ($transaction->product?->type ?? $transaction->unique_element ?? ''))
            ->lower()
            ->contains('wallet2bank');
    }

    public function isAirtime2CashTransaction(): bool
    {
        return $this->linkedTransaction() instanceof Airtime2CashTransactions;
    }

    public function resolver()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function provider()
    {
        return $this->belongsTo(API::class, 'api_id');
    }
}
