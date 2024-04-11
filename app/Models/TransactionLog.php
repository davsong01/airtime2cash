<?php

namespace App\Models;

use App\Models\Wallet;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use App\Models\Airtime2CashTransactions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionLog extends Model
{
    use HasFactory;
    protected $appends = ['type'];
    protected $guarded = [];

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
