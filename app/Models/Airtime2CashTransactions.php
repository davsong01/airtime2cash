<?php

namespace App\Models;

use App\Models\API;
use App\Models\Customer;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Wallet;

class Airtime2CashTransactions extends Model
{
    protected $guarded = [];
    protected $casts = [
        'provider_response' => 'array',
        'completed_at' => 'datetime',
        'profit_percentage' => 'decimal:2',
        'profit' => 'decimal:2',
    ];
    use HasFactory;

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(API::class, 'provider_id');
    }

    public function transactionLog()
    {
        return $this->hasOne(TransactionLog::class, 'transaction_id', 'transaction_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'transaction_id', 'transaction_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
