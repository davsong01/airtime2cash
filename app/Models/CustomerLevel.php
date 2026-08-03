<?php

namespace App\Models;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerLevel extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function customers(){
        return $this->hasMany(Customer::class, 'customer_level');
    }

    public function variation_price($variation_id)
    {
        $price = null;
        $price = Discount::where('variation_id', $variation_id)->value('price');
        return $price;
    }

    public function product_price($product_id)
    {
        $price = null;
        $price = Discount::where('variation_id', $product_id)->whereNull('variation_id')->value('price');
        return $price;
    }

    public function transaction(){
        return $this->hasOne(TransactionLog::class, 'upgrade_level');
    }
}
