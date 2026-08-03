<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    use HasFactory;

    protected $guarded = [];

    function referredCustomer () {
        return $this->belongsTo(Customer::class, 'referred_customer_id', 'id');
    }

    function customer () {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    function transaction () {
        return $this->belongsTo(TransactionLog::class, 'transaction_id', 'transaction_id');
    }

    public function total_earnings(){
        $total = ReferralEarning::where('customer_id', $this->customer_id)->where('referred_customer_id', $this->referred_customer_id)->sum('amount');
        return $total;
    }
}
