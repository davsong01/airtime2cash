<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Discount;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VariationController extends Controller
{
    public function pullVariations(Product $product)
    {
        $variations = app("App\Http\Controllers\Providers\KingsVtuController")->getVariations($product->slug);
       
        if (isset($variations['status']) && $variations['status'] == 'success') {
            $variations = $variations['data']['variations'] ?? [];
            if (!empty($variations)) {
                foreach ($variations as $key => $variation) {
                    $variation = Variation::updateOrCreate([
                        'slug' => $variation['variation_slug'],
                    ], [
                        'product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'api_id' => $product->api_id,
                        'api_name' =>  $variation['name'] ?? null,
                        'slug' => $variation['variation_slug'] ?? null,
                        'system_name' =>  $variation['name'],
                        'fixed_price' => $variation['fixed_price'],
                        'api_price' => $variation['price'],
                        'system_price' => $variation['price'],
                        'min' => $variation['min'] ?? null,
                        'max' => $variation['max'] ?? null,
                        'verifiable' => $variation['verifiable'] ?? null,
                        'unique_element' => $variation['unique_element'] ?? null,
                        'status' => 'inactive'
                    ]);
                }

                return back()->with('message', 'Variations successfully pulled, please proceed to update variations');
            } else {
                return back()->with('error', 'Variations could not be pulled from provider');
            }
        } else {
            return back()->with('error', 'Error while pulling categories' . $variations['errors'] ?? '');
        }

        return view('admin.category.index', compact('categories'));
    }
    public function getCustomerVariations(Product $product)
    {
        $variations = Variation::where('product_id', $product->id)->where('status', 'active')->orderBy('system_price', 'ASC')->get();
        foreach ($variations as $key => $variation) {
            $req = new Request([
                'variation_id' => $variation->id,
                'raw' => 'yes',
            ]);

            $discount = app('App\Http\Controllers\TransactionController')->getCustomerDiscount($req);
            
            $variation->discount = $discount;
    
            // dd(in_array('utme-no-mock', array_keys(specialVerifiableVariations())), specialVerifiableVariations());
            if (in_array($variation->category->unique_element, verifiableUniqueElements()) || in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
                $variation->verifiable = 'yes';
            } else {
                $variation->verifiable = 'no';
            }

            if (($variation->fixed_price == 'Yes') && empty($variation->system_price) || $variation->system_price < 0) {
                unset($variations[$key]);
            }
            
            if (in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
                $variation->unique_element = specialVerifiableVariations()[$variation->slug];
            } else {
                $variation->unique_element = $variation->category->unique_element;
            }
        }

        return response()->json($variations);
    }

    public function updateVariations(Request $request)
    {
        if (isset($request->level)) {
            foreach ($request->level as $key => $level) {
                foreach ($level as $k => $price) {
                    // if (!empty($price)) {
                        Discount::updateOrCreate([
                            'customer_level' => $key,
                            'product_id' => $request->product_id,
                            'variation_id' => $k,
                        ], [
                            'status' => 'active',
                            'customer_level' => $key,
                            'product_id' => $request->product_id,
                            'variation_id' => $k,
                            'price' => $price ?? 0
                        ]);
                    // }
                }
            }
        }

        foreach ($request->variation_id as $variation) {
            $data = [
                'api_name' => $request->api_name[$variation],
                'api_price' => $request->api_price[$variation],
                'system_name' => $request->system_name[$variation],
                'slug' => $request->slug[$variation],
                'system_price' => $request->system_price[$variation],
                'fixed_price' => $request->fixed_price[$variation],
                'min' => $request->min[$variation] ?? null,
                'max' => $request->max[$variation] ?? null,
                'status' => $request->status[$variation],
            ];

            Variation::where('id', $variation)->update($data);
        }

        \Session::flash('page', 2);
        return back()->with('message', 'Variations Updated succesfully');
    }

    public function addManualVariations(Request $request, Product $product)
    {
        // Create the variation
        if (isset($request->system_name)) {
            foreach ($request->system_name as $key => $variation) {
                // dd($variation, $key, $request->all(), $request->slug[$key]);
                $variation = Variation::updateOrCreate([
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'api_id' => $product->api_id,
                    'api_name' =>  $request->system_name[$key],
                    'slug' => $request->slug[$key],
                ], [
                    'product_id' => $request->product_id,
                    'category_id' => $product->category_id,
                    'api_id' => $product->api_id,
                    'api_name' =>  $request->system_name[$key],
                    'slug' => $request->slug[$key],
                    'system_name' =>  $request->system_name[$key],
                    'fixed_price' => $request->fixed_price[$key],
                    'api_price' => $request->system_price[$key],
                    'system_price' => $request->system_price[$key],
                    'min' => $request->minimum_amount[$key] ?? null,
                    'max' => $request->maximum_amount[$key] ?? null,
                    'status' => $request->status[$key]
                ]);

                if (isset($request->level)) {
                    foreach ($request->level as $l => $level) {
                        foreach ($level as $price) {
                            if (!empty($price)) {
                                Discount::updateOrCreate([
                                    'customer_level' => $l,
                                    'product_id' => $product->id,
                                    'variation_id' => $variation->id,
                                ], [
                                    'status' => 'active',
                                    'customer_level' => $l,
                                    'product_id' => $product->id,
                                    'variation_id' => $variation->id,
                                    'price' => $level[$key]
                                ]);
                            }
                        }
                    }
                }
            }
        }

        \Session::flash('page', 2);
        return back()->with('message', 'Variations added succesfully');
    }

    public function deleteVariations(Variation $variation)
    {

        if ($variation->discounts->count() > 0) {
            foreach ($variation->discounts as $dist) {
                $dist->delete();
            }
        }

        // dd($variation);
        $variation->delete();

        return back()->with('message', 'Variation deleted successfully');
        // Discount::
    }
}
