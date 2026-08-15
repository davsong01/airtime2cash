<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CustomerLevel;

class Airtime2CashController extends Controller
{
    public function index()
    {
        $products = Product::where('type', 'airtime2cash')->with(['api'])->orderBy('created_at', 'DESC')->get();
        return view('admin.airtime2cash.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->where('type', 'airtime2cash')->get();
        $customerlevel = CustomerLevel::enabled()->orderBy('order', 'ASC')->get();
        
        return view('admin.airtime2cash.create', compact('categories', 'customerlevel'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "category" => "required",
            "description" => "nullable",
            "seo_title" => "nullable",
            "seo_keywords" => "nullable",
            "status" => "required",
            "seo_description" => "nullable",
            "fixed_price" => "nullable",
            "rate" => "required|numeric|min:0|max:100",
            "manual_profit_percentage" => "nullable|numeric|min:0|max:100",
            "manual_level_rate" => "nullable|array",
            "manual_level_rate.*" => "nullable|numeric|min:0|max:100",
            "auto_share_rate" => "required|numeric|min:0|max:100",
            "auto_share_profit_percentage" => "nullable|numeric|min:0|max:100",
            "auto_share_level_rate" => "nullable|array",
            "auto_share_level_rate.*" => "nullable|numeric|min:0|max:100",
            "manual_status" => "required|in:active,inactive",
            "auto_share_status" => "required|in:active,inactive",
            "instruction" => "nullable|string",
            "auto_share_instruction" => "nullable|string",
            "auto_share_product_code" => "nullable|string|max:100|required_if:auto_share_status,active",
            "min" => "required|numeric|min:0",
            "max" => "required|numeric|gte:min",
            "image" => "required|mimes:jpeg,png|max:1024",
        ]);

        if (!empty($request->image)) {
            $image = $this->uploadFile($request->image, 'products');
        }
        $slug = strtolower('airtime2cash-'.Str::slug($request->name));
    
        $product = Product::updateOrCreate(
            [
                "name" => $request->name,
                "category_id" => $request->category,
                "slug" => $slug,
            ],
            [
                "name" => $request->name,
                "has_variations" => 'no',
                "display_name" => $request->name,
                "category_id" => $request->category,
                "description" => $request->description,
                "seo_title" => $request->seo_title,
                "seo_keywords" => $request->seo_keywords,
                "slug" => $slug,
                "api_id" => 1,
                "rate" => $request->rate,
                "manual_profit_percentage" => $request->filled('manual_profit_percentage') ? $request->manual_profit_percentage : null,
                "auto_share_rate" => $request->auto_share_rate,
                "auto_share_profit_percentage" => $request->filled('auto_share_profit_percentage') ? $request->auto_share_profit_percentage : null,
                "manual_status" => $request->manual_status,
                "auto_share_status" => $request->auto_share_status,
                "status" => $request->status,
                "has_variations" => 'no',
                "seo_description" => $request->seo_description,
                "image" => $image ?? null,
                "fixed_price" => $request->fixed_price,
                "system_price" => $request->system_price,
                "min" => $request->min,
                "max" => $request->max,
                "type" => 'airtime2cash',
                "instruction" => $request->instruction,
                "auto_share_instruction" => $request->auto_share_instruction,
                "auto_share_product_code" => $request->auto_share_product_code,
            ]
        );

        $this->syncAirtimeCustomerLevelRates($product, $request->manual_level_rate ?? [], 'manual');
        $this->syncAirtimeCustomerLevelRates($product, $request->auto_share_level_rate ?? [], 'auto_share');

        return redirect(route('airtime2cash.edit', $product->id))->with('message', 'Product Added Successfully');
    }

    public function edit($id)
    {
        $product = Product::where('id', $id)->first();
        $categories = Category::where('status', 'active')->where('type', 'airtime2cash')->get();

        $customerlevel = CustomerLevel::enabled()->orderBy('order', 'ASC')->get();

        return view('admin.airtime2cash.edit', compact('categories', 'product', 'customerlevel'));
    }

    public function update(Request $request, $id){
        $this->validate($request, [
            "name" => "required",
            "category" => "required",
            "status" => "required",
            "rate" => "required|numeric|min:0|max:100",
            "manual_profit_percentage" => "nullable|numeric|min:0|max:100",
            "manual_level_rate" => "nullable|array",
            "manual_level_rate.*" => "nullable|numeric|min:0|max:100",
            "auto_share_rate" => "required|numeric|min:0|max:100",
            "auto_share_profit_percentage" => "nullable|numeric|min:0|max:100",
            "auto_share_level_rate" => "nullable|array",
            "auto_share_level_rate.*" => "nullable|numeric|min:0|max:100",
            "manual_status" => "required|in:active,inactive",
            "auto_share_status" => "required|in:active,inactive",
            "instruction" => "nullable|string",
            "auto_share_instruction" => "nullable|string",
            "auto_share_product_code" => "nullable|string|max:100|required_if:auto_share_status,active",
            "min" => "required|numeric|min:0",
            "max" => "required|numeric|gte:min",
            "image" => "nullable|mimes:jpeg,png|max:1024",
        ]);

        $product = Product::where('id', $id)->first();
        
        if (!empty($request->image)) {
            $image = $this->uploadFile($request->image, 'products');
        }else{
            $image = $product->image;
        }

        $slug = strtolower('airtime2cash-' . Str::slug($request->name));
        
        $product->update(
            [
                "name" => $request->name,
                "has_variations" => 'no',
                "display_name" => $request->name,
                "category_id" => $request->category,
                "description" => $request->description,
                "seo_title" => $request->seo_title,
                "seo_keywords" => $request->seo_keywords,
                "slug" => $slug,
                "api_id" => 1,
                "rate" => $request->rate,
                "manual_profit_percentage" => $request->filled('manual_profit_percentage') ? $request->manual_profit_percentage : null,
                "auto_share_rate" => $request->auto_share_rate,
                "auto_share_profit_percentage" => $request->filled('auto_share_profit_percentage') ? $request->auto_share_profit_percentage : null,
                "manual_status" => $request->manual_status,
                "auto_share_status" => $request->auto_share_status,
                "status" => $request->status,
                "has_variations" => 'no',
                "seo_description" => $request->seo_description,
                "image" => $image ?? null,
                "fixed_price" => $request->fixed_price,
                "system_price" => $request->system_price,
                "min" => $request->min,
                "max" => $request->max,
                "type" => 'airtime2cash',
                "instruction" => $request->instruction,
                "auto_share_instruction" => $request->auto_share_instruction,
                "auto_share_product_code" => $request->auto_share_product_code,
            ]
        );

        $this->syncAirtimeCustomerLevelRates($product, $request->manual_level_rate ?? [], 'manual');
        $this->syncAirtimeCustomerLevelRates($product, $request->auto_share_level_rate ?? [], 'auto_share');
        
        return back()->with('message', 'Update Successfull');
    }

    private function syncAirtimeCustomerLevelRates(Product $product, array $rates, string $transferMode): void
    {
        foreach ($rates as $levelId => $price) {
            $normalizedPrice = $this->normalizeAirtimeCustomerLevelRate($price);

            Discount::updateOrCreate([
                'customer_level' => $levelId,
                'product_id' => $product->id,
                'transfer_mode' => $transferMode,
            ], [
                'status' => 'active',
                'customer_level' => $levelId,
                'product_id' => $product->id,
                'transfer_mode' => $transferMode,
                'price' => $normalizedPrice,
            ]);
        }
    }

    private function normalizeAirtimeCustomerLevelRate($price): ?float
    {
        if ($price === null || $price === '') {
            return null;
        }

        if (!is_numeric($price)) {
            return null;
        }

        $price = (float) $price;

        return $price > 0 ? $price : null;
    }
}
