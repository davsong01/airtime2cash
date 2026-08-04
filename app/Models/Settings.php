<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'captcha_settings' => 'array',
        'google_dashboard_ad_enabled' => 'boolean',
    ];
}
