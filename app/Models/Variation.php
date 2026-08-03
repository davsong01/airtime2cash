<?php

namespace App\Models;

use App\Models\API;
use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Variation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function api()
    {
        return $this->belongsTo(API::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function transaction()
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function customer_level_price($level)
    {
        $price = null;
        $price = Discount::where(['variation_id' => $this->id, 'customer_level' => $level])->value('price');
        return $price;
    }
}
