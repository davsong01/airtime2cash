<?php

namespace App\Http\Controllers;

use App\Models\BillerLog;
use Illuminate\Http\Request;

class BillerLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $billers = BillerLog::orderBy('created_at', 'DESC')->get();
        return view('admin.biller_log.index', compact('billers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BillerLog $billerLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BillerLog $billerlog)
    {
        // $customerlevel = CustomerLevel::orderBy('order', 'ASC')->get();

        // return view('admin.product.edit', compact('categories', 'apis', 'product', 'variations', 'customerlevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BillerLog $billerlog, Request $request)
    {
        // $this->validate($request, [
        //     "name" => "required",
        //     "display_name" => "required",
        //     "category" => "required",
        //     "description" => "nullable",
        //     "seo_title" => "nullable",
        //     "seo_keywords" => "nullable",
        //     "slug" => "required",
        //     "api" => "required",
        //     "status" => "required",
        //     "seo_description" => "nullable",
        //     "route" => "required",
        //     "has_variations" => "required",
        //     "image" => "nullable|mimes:jpeg,png|max:1024",
        //     "fixed_price" => 'nullable',
        //     "system_price" => 'nullable',
        //     "allow_qantity" => 'nullable',
        //     "quantity_graduation" => 'nullable',
        //     "min" => 'nullable',
        //     "max" => 'nullable',
        //     "allow_subscription_type" => "nullable"
        // ]);

        // if (!empty($request->image)) {
        //     $image = $this->uploadFile($request->image, 'products');
        // }

        // $product->update([
        //     "name" => $request->name,
        //     "display_name" => $request->display_name,
        //     "category_id" => $request->category,
        //     "description" => $request->description,
        //     "seo_title" => $request->seo_title,
        //     "seo_keywords" => $request->seo_keywords,
        //     "slug" => $request->slug,
        //     "api_id" => $request->api,
        //     "status" => $request->status,
        //     "has_variations" => $request->has_variations,
        //     "seo_description" => $request->seo_description,
        //     "image" => $image ?? $product->image,
        //     "fixed_price" => $request->fixed_price,
        //     "min" => $request->min,
        //     "max" => $request->max,
        //     "system_price" => $request->system_price,
        //     "allow_quantity" => $request->allow_quantity,
        //     "quantity_graduation" => $request->quantity_graduation,
        //     "allow_subscription_type" => $request->allow_subscription_type,
        // ]);

        // return back()->with('message', 'Update Successfull');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BillerLog $billerlog)
    {
        $billerlog->delete();

        return back()->with('message', 'Deleted Succesfully');
    }
}
