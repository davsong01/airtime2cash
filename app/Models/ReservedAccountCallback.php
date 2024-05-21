<?php

namespace App\Models;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservedAccountCallback extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function transaction(){
        return $this->belongsTo(TransactionLog::class, 'transaction_reference','transaction_id');
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'provider_id');
    }
}
