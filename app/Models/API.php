<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Variation;
use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class API extends Model
{

    use HasFactory;
    protected $guarded = [];
    protected $table = 'apis';
    protected $casts = [
        'pricing_data' => 'array',
        'extra_charges' => 'array',
        'pricing_data_status' => 'boolean',
        'is_bank_transfer' => 'boolean',
        'is_bank_verification' => 'boolean',
        'is_bvn_verification' => 'boolean',
        'is_auto_share' => 'boolean',
        'is_payment_gateway' => 'boolean',
        'charge' => 'float',
        'reserved_account_payment_charge' => 'float',
        'reserved_account_payment_charge_type' => 'string',
        'availability_score' => 'integer',
        'availability_check_transactions_count' => 'integer',
        'successful_transactions' => 'integer',
        'failed_transactions' => 'integer',
        'availability_checked_at' => 'datetime',
    ];

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => date("M jS, Y", strtotime($value)),
        );
    }

    protected function availabilityStatusClass(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => match ($attributes['availability_status'] ?? null) {
                'unstable' => 'unstable',
                'degraded', 'poor', 'fair', 'moderate' => 'degraded',
                'stable', 'very_stable' => 'stable',
                'healthy', 'excellent' => 'healthy',
                default => null,
            },
        );
    }

    protected function availabilityStatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => match ($attributes['availability_status'] ?? null) {
                'unstable' => 'Unstable',
                'degraded', 'poor', 'fair', 'moderate' => 'Degraded',
                'stable', 'very_stable' => 'Stable',
                'healthy', 'excellent' => 'Healthy',
                default => null,
            },
        );
    }
    
    public function products(){
        return $this->hasMany(Product::class, 'api_id');
    }

    public function variations()
    {
        return $this->hasMany(Variation::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionLog::class, 'api_id');
    }

}
