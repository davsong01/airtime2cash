<?php

namespace App\Models;

use App\Models\Wallet;
use App\Models\Product;
use App\Models\Category;
use App\Models\Bank;
use Illuminate\Database\Eloquent\Model;
use App\Models\Airtime2CashTransactions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionLog extends Model
{
    use HasFactory;
    protected $appends = ['type'];
    protected $guarded = [];
    protected $casts = [
        'charge_breakdown' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            if ($transaction->shouldMarkCompletedAt() && blank($transaction->completed_at)) {
                $transaction->completed_at = now();
            }
        });
    }

    public function shouldMarkCompletedAt(): bool
    {
        return in_array(
            strtolower((string) ($this->status ?? '')),
            $this->terminalStatuses(),
            true
        );
    }

    public function successfulStatuses(): array
    {
        return ['success', 'successful', 'completed', 'approved', 'delivered'];
    }

    public function failedStatuses(): array
    {
        return ['failed', 'declined', 'rejected', 'cancelled', 'canceled'];
    }

    public function terminalStatuses(): array
    {
        return array_values(array_unique(array_merge(
            $this->successfulStatuses(),
            $this->failedStatuses()
        )));
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'transaction_id', 'transaction_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'transaction_id', 'transaction_id');
    }

    public function provider()
    {
        return $this->belongsTo(PaymentGateway::class, 'wallet_funding_provider');
    }

    public function api()
    {
        return $this->belongsTo(API::class, 'api_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getTypeAttribute()
    {
        return $this->wallet->type ?? 'new';
    }

    public function upgrade_level(){
        return $this->belongsTo(CustomerLevel::class, 'upgrade_level');
    }

    public function airtime2cash(){
        return $this->belongsTo(Airtime2CashTransactions::class, 'transaction_id', 'transaction_id');
    }
}
