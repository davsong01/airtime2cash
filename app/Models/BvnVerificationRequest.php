<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BvnVerificationRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'name_match' => 'boolean',
        'request_data' => 'array',
        'provider_response' => 'array',
        'billing_amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];
}
