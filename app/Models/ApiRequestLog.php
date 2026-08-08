<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';

    protected $guarded = [];

    protected $casts = [
        'request_headers' => 'array',
        'request_payload' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
