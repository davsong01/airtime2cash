<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'provider_codes' => 'array',
        'provider_meta' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function api()
    {
        return $this->belongsTo(API::class, 'api_id');
    }
}
