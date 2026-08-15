<?php

namespace App\Models;

use App\Models\User;
use App\Models\KycData;
use App\Models\CustomerLevel;
use App\Models\ReservedAccountNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function level()
    {
        return $this->belongsTo(CustomerLevel::class, 'customer_level')->withDefault([
            'name' => 'Unassigned',
            'status' => false,
        ]);
    }

    public function kycdata()
    {
        return $this->belongsTo(KycData::class, 'customer_id');
    }

    public function reserved_accounts()
    {
        return $this->hasMany(ReservedAccountNumber::class, 'customer_id')->orderBy('created_at', 'DESC');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function multiplekycdata()
    {
        return $this->hasMany(KycData::class);
    }
}
