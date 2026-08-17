<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Discount;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['api', 'variations'])
            ->withCount([
                'transactions',
                'variations as variations_transactions_count' => function ($query) {
                    $query->whereHas('transaction');
                },
            ])
            ->orderBy('created_at', 'DESC')
            ->get();
        return view('admin.product.index', compact('products'));
    }

    public function destroy(Product $product)
    {
        if ($this->hasAttachedTransactions($product)) {
            return back()->with('error', 'This product cannot be deleted because it already has transactions attached.');
        }

        DB::transaction(function () use ($product): void {
            Discount::where('product_id', $product->id)->delete();

            $variationIds = Variation::where('product_id', $product->id)->pluck('id');

            if ($variationIds->isNotEmpty()) {
                Discount::whereIn('variation_id', $variationIds)->delete();
                Variation::whereIn('id', $variationIds)->delete();
            }

            $product->delete();
        });

        return back()->with('message', 'Product deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::with('variations')->whereIn('id', $validated['product_ids'])->get();
        $deleted = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if ($this->hasAttachedTransactions($product)) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($product): void {
                Discount::where('product_id', $product->id)->delete();

                $variationIds = $product->variations->pluck('id');

                if ($variationIds->isNotEmpty()) {
                    Discount::whereIn('variation_id', $variationIds)->delete();
                    Variation::whereIn('id', $variationIds)->delete();
                }

                $product->delete();
            });

            $deleted++;
        }

        $message = $deleted . ' product(s) deleted successfully.';

        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' product(s) were skipped because they have attached transactions.';
        }

        return back()->with('message', $message);
    }

    public function pullProducts()
    {
        $categories = Category::all();
        foreach ($categories as $category) {
            $products = app("App\Http\Controllers\Providers\KingsVtuController")->getProducts($category->slug);
            Log::info($products);
            if (isset($products['status']) && $products['status'] == 'success') {
                $products = $products['data']['products'] ?? [];

                if (!empty($products)) {
                    foreach ($products as $key => $product) {
                        $allproducts = Product::pluck('slug')->toArray();
                        if(!in_array($product['slug'], $allproducts)){
                            Product::create(
                                [
                                    "category_id" => $category->id,
                                    "name" => $product['display_name'],
                                    "display_name" => $product['display_name'],
                                    "slug" => $product['slug'],
                                    "status" => 'inactive',
                                    "description" => $product['description'] ?? null,
                                    "min" => $product['min'] ?? null,
                                    "max" => $product['max'] ?? null,
                                    "allow_quantity" => $product['allow_quantity'] ?? null,
                                    "fixed_price" => $product['fixed_price'] ?? null,
                                    "allow_subscription_type" => $product['allow_subscription_type'] ?? null,
                                    "has_variations" => $product['has_variations'] ?? null,
                                    "image" => $product['image'] ?? null ,
                                ]
                            );
                        }
                    }

                }
            }
        }

        return back()->with('message', 'Products successfully pulled, please proceed to update products');
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->where('type', 'general')->get();
        $apis = API::where('status', 'active')->get();
        $customerlevel = CustomerLevel::enabled()->orderBy('order', 'ASC')->get();

        return view('admin.product.create', compact('categories', 'apis', 'customerlevel'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "display_name" => "required",
            "category" => "required",
            "description" => "nullable",
            "seo_title" => "nullable",
            "seo_keywords" => "nullable",
            "slug" => "required",
            "api" => "required",
            "status" => "required",
            "seo_description" => "nullable",
            "route" => "required",
            "has_variations" => "required",
            "fixed_price" => "nullable",
            "system_price" => "nullable",
            "image" => "required|mimes:jpeg,png|max:1024",
            "allow_subscription_type" => 'nullable',
            "show_in_menu" => 'nullable|boolean',
            'referral_percentage' => 'nullable',
        ]);

        if (!empty($request->image)) {
            $image = $this->uploadFile($request->image, 'products');
        }

        $product = Product::updateOrCreate(
            [
                "name" => $request->name,
                "display_name" => $request->display_name,
                "category_id" => $request->category,
                "description" => $request->description,
                "seo_title" => $request->seo_title,
                "seo_keywords" => $request->seo_keywords,
                "slug" => $request->slug,
                "has_variations" => $request->has_variations,
                "api_id" => $request->api,
            ],
            [
                "name" => $request->name,
                "display_name" => $request->display_name,
                "category_id" => $request->category,
                "description" => $request->description,
                "seo_title" => $request->seo_title,
                "seo_keywords" => $request->seo_keywords,
                "slug" => $request->slug,
                "api_id" => $request->api,
                "status" => $request->status,
                "has_variations" => $request->has_variations,
                "seo_description" => $request->seo_description,
                "image" => $image ?? null,
                "fixed_price" => $request->fixed_price,
                "system_price" => $request->system_price,
                "allow_quantity" => $request->allow_quantity,
                "quantity_graduation" => $request->quantity_graduation,
                "min" => $request->min,
                "max" => $request->max,
                "servercode" => $request->servercode,
                "allow_subscription_type" => $request->allow_subscription_type ?? 'no',
                "show_in_menu" => $request->boolean('show_in_menu'),
                'referral_percentage' => $request->referral_percentage,
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

        return redirect(route('product.edit', $product->id))->with('message', 'Product Added Successfully');
    }

    public function duplicateProduct(Request $request, Product $product)
    {
        $newProduct = $product->replicate();
        $newProduct->name = $product->name . '_copy';
        $newProduct->display_name = $product->display_name . '_copy';
        $newProduct->slug = $product->slug . '_copy';
        $newProduct->save();

        return back()->with('message', 'Product Duplicated succesfully');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', 'active')->where('type', 'general')->get();
        $variations = Variation::where('product_id', $product->id)->where('api_id', $product->api_id)->get();
        $apis = API::where('status', 'active')->get();
        $customerlevel = CustomerLevel::enabled()->orderBy('order', 'ASC')->get();

        return view('admin.product.edit', compact('categories', 'apis', 'product', 'variations', 'customerlevel'));
    }

    public function update(Product $product, Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "display_name" => "required",
            "category" => "required",
            "description" => "nullable",
            "seo_title" => "nullable",
            "seo_keywords" => "nullable",
            "slug" => "required",
            "api" => "required",
            "status" => "required",
            "seo_description" => "nullable",
            "route" => "required",
            "has_variations" => "required",
            "image" => "nullable|mimes:jpeg,png|max:1024",
            "fixed_price" => 'nullable',
            "system_price" => 'nullable',
            "allow_qantity" => 'nullable',
            "quantity_graduation" => 'nullable',
            "min" => 'nullable',
            "max" => 'nullable',
            "allow_subscription_type" => "nullable",
            "show_in_menu" => 'nullable|boolean',
            'referral_percentage' => 'nullable',

        ]);

        if (!empty($request->image)) {
            $image = $this->uploadFile($request->image, 'products');
            $image = url('/').'/'.$image;
        }else{
            $image = $product->image;
        }
        $product->update([
            "name" => $request->name,
            "display_name" => $request->display_name,
            "category_id" => $request->category,
            "description" => $request->description,
            "seo_title" => $request->seo_title,
            "seo_keywords" => $request->seo_keywords,
            "slug" => $request->slug,
            "api_id" => $request->api,
            "status" => $request->status,
            "has_variations" => $request->has_variations,
            "seo_description" => $request->seo_description,
            "image" => $image ?? $product->image,
            "fixed_price" => $request->fixed_price,
            "min" => $request->min,
            "max" => $request->max,
            "system_price" => $request->system_price,
            "allow_quantity" => $request->allow_quantity,
            "servercode" => $request->servercode,
            "quantity_graduation" => $request->quantity_graduation,
            "allow_subscription_type" => $request->allow_subscription_type,
            "show_in_menu" => $request->boolean('show_in_menu'),

            'referral_percentage' => $request->referral_percentage,
        ]);

        $productLevel = $request->productlevel;
        // $productLevel = array_filter($request->productlevel);

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

    protected function hasAttachedTransactions(Product $product): bool
    {
        if ($product->transactions()->exists()) {
            return true;
        }

        $variationIds = $product->variations()->pluck('id');

        if ($variationIds->isEmpty()) {
            return false;
        }

        return TransactionLog::whereIn('variation_id', $variationIds)->exists();
    }
}
