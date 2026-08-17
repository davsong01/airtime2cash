<?php

namespace App\Models;

use App\Models\API;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function variations()
    {
        return $this->hasMany(Variation::class)->orderBy('created_at', 'DESC');
    }

    public function transactions()
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function api()
    {
        return $this->belongsTo(API::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function customer_level_price($level)
    {
        $price = null;
        $price = Discount::where(['product_id' => $this->id, 'customer_level' => $level])->value('price');
        return $price;
    }

    public function customer_level_transfer_price($level, string $transferMode)
    {
        $price = Discount::where([
            'product_id' => $this->id,
            'customer_level' => $level,
            'transfer_mode' => $transferMode,
        ])->where('price', '>', 0)->value('price');

        if ($price === null && $transferMode === 'manual') {
            $price = Discount::where([
                'product_id' => $this->id,
                'customer_level' => $level,
            ])->whereNull('transfer_mode')->where('price', '>', 0)->value('price');
        }

        return $price;
    }
}
