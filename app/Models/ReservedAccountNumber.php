<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservedAccountNumber extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionLog::class, 'account_number', 'account_number');
    }

    public function api()
    {
        return $this->belongsTo(API::class, 'api_id');
    }

    public function payment_gateway()
    {
        return $this->api();
    }

    public function gateway()
    {
        return $this->api();
    }
}
