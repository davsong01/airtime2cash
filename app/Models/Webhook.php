<?php

namespace App\Models;

use App\Models\API;
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

    public function resolver()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function provider()
    {
        return $this->belongsTo(API::class, 'api_id');
    }
}
