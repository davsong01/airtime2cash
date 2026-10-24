<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = [];

    protected $casts = [
        'bank_verification_enabled' => 'boolean',
    ];

    public function bankVerificationProvider()
    {
        return $this->belongsTo(API::class, 'bank_verification_provider_id');
    }
}
