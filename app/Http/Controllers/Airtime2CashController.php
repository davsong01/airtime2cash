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
        $customerlevel = CustomerLevel::orderBy('order', 'ASC')->get();
        
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
            "rate" => "nullable",
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
            ]
        );

        if (isset($request->productlevel) && isset($product)) {
            foreach ($request->productlevel as $key => $price) {
                Discount::updateOrCreate([
                    'customer_level' => $key,
                    'product_id' => $product->id,
                ], [
                    'status' => 'active',
                    'customer_level' => $key,
                    'product_id' => $product->id,
                    'price' => $price ?? 0
                ]);
            }
        }

        return redirect(route('airtime2cash.edit', $product->id))->with('message', 'Product Added Successfully');
    }

    public function edit($id)
    {
        $product = Product::where('id', $id)->first();
        $categories = Category::where('status', 'active')->where('type', 'airtime2cash')->get();

        $customerlevel = CustomerLevel::orderBy('order', 'ASC')->get();

        return view('admin.airtime2cash.edit', compact('categories', 'product', 'customerlevel'));
    }

    public function update(Request $request, $id){
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
            ]
        );

        $productLevel = $request->productlevel;

        if (isset($productLevel) && count($productLevel) > 0 && isset($product)) {
            foreach ($productLevel as $key => $price) {
                Discount::updateOrCreate([
                    'customer_level' => $key,
                    'product_id' => $product->id,
                ], [
                    'customer_level' => $key,
                    'product_id' => $product->id,
                    'price' => $price ?? 0
                ]);
            }
        }
        
        return back()->with('message', 'Update Successfull');
    }
}
