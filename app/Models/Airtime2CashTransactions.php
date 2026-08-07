<?php

namespace App\Models;

use App\Models\API;
use App\Models\Customer;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Airtime2CashTransactions extends Model
{
    protected $guarded = [];
    protected $casts = ['provider_response' => 'array'];
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
}
