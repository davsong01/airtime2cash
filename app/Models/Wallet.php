<?php

namespace App\Models;

use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Model;
use App\Models\Airtime2CashTransactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id','amount','balance_before','balance_after','type','transaction_id','reason','payment_method'];

    public function transaction_log(){
        return $this->hasOne(TransactionLog::class, 'transaction_id','transaction_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function airtime2cash()
    {
        return $this->belongsTo(Airtime2CashTransactions::class, 'transaction_id', 'transaction_id');
    }
}
