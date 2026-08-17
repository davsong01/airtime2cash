<?php

namespace App\Http\Controllers;

use App\Models\API;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Discount;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\Variation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $apis = API::query()
            ->whereIn('slug', ['kingsvtu', 'vtpass'])->orderBy('name')
            ->get(['id', 'name']);

        $baseQuery = Product::query()
            ->with(['api:id,name', 'category:id,name', 'variations' => function ($query) {
                $query->select('id', 'product_id', 'status', 'api_id', 'system_name', 'api_name', 'slug', 'created_at');
            }])
            ->withCount([
                'transactions',
                'variations as variations_transactions_count' => function ($query) {
                    $query->whereHas('transaction');
                },
                'variations as active_variations_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('display_name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->filled('api_id'), fn ($query) => $query->where('api_id', $request->api_id))
            ->when($request->filled('variations'), fn ($query) => $query->where('has_variations', $request->variations === 'yes' ? 'yes' : 'no'))
            ->orderBy('created_at', 'DESC');

        $summaryQuery = clone $baseQuery;

        $products = (clone $baseQuery)
            ->paginate(15)
            ->withQueryString();

        $summary = (object) [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'with_variations' => (clone $summaryQuery)->where('has_variations', 'yes')->count(),
            'deletable' => (clone $summaryQuery)
                ->whereDoesntHave('transactions')
                ->whereDoesntHave('variations', function ($query) {
                    $query->whereHas('transaction');
                })
                ->count(),
        ];

        return view('admin.product.index', compact('products', 'summary', 'categories', 'apis'));
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
        try {
            $categories = Category::all();
            $controller = app("App\Http\Controllers\Providers\KingsVtuController");

            foreach ($categories as $category) {
                try {
                    $response = $controller->getProducts($category->slug);

                    if (($response['status'] ?? null) !== 'success') {
                        Log::warning('Product pull failed for category', [
                            'category' => $category->slug,
                            'response' => $response,
                        ]);

                        continue;
                    }

                    $products = $response['data']['products'] ?? [];

                    foreach ($products as $product) {
                        if (empty($product['slug'])) {
                            continue;
                        }

                        $model = Product::firstOrNew([
                            'slug' => $product['slug'],
                        ]);

                        if (! $model->exists) {
                            $model->status = 'inactive';
                        }

                        $model->fill([
                            'category_id' => $category->id,
                            'name' => $product['display_name'] ?? $product['slug'],
                            'display_name' => $product['display_name'] ?? $product['slug'],
                            'description' => $product['description'] ?? null,
                            'min' => $product['min'] ?? null,
                            'max' => $product['max'] ?? null,
                            'allow_quantity' => $product['allow_quantity'] ?? null,
                            'fixed_price' => $product['fixed_price'] ?? null,
                            'allow_subscription_type' => $product['allow_subscription_type'] ?? null,
                            'has_variations' => $product['has_variations'] ?? null,
                            'image' => $product['image'] ?? null,
                        ]);

                        $model->save();
                    }
                } catch (Exception $e) {
                    Log::error('Failed pulling products for category', [
                        'category_id' => $category->id,
                        'category' => $category->slug,
                        'message' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            return back()->with('message', 'Products successfully pulled and updated.');
        } catch (Exception $e) {
            Log::error('Product pull failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'Unable to pull products. Please try again.');
        }
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
