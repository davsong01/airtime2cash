<?php

namespace App\Http\Controllers;

use App\Models\API;
use Illuminate\Http\Request;

class APIController extends Controller
{
    public function index()
    {
        $apis = API::withCount('products')->get();
        return view('admin.api.index', compact('apis'));
    }

    public function create()
    {
        return view('admin.api.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "warning_threshold_status" => "nullable",
            "warning_threshold" => "nullable",
            "status" => "required",
            "api_key" => "nullable",
            "sandbox_base_url" => "nullable",
            "live_base_url" => "nullable",
            "secret_key" => "nullable",
            "public_key" => "nullable",
            "slug" => "required|string|max:100",
            "file_name" => "nullable|string|max:255",
        ]);

        API::updateOrCreate([
            "name" => $request->name,
            "warning_threshold_status" => $request->warning_threshold_status,
            "warning_threshold" => $request->warning_threshold,
            "status" => $request->status,
            "api_key" => $request->api_key,
            "secret_key" => $request->secret_key,
            "public_key" => $request->public_key,
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "file_name" => $request->file_name,
        ]);

        return redirect(route('api.index'))->with('message', 'Added successfully');
    }

    public function edit(API $api)
    {
        return view('admin.api.edit', compact('api'));
    }

    public function update(Request $request, API $api)
    {
        $this->validate($request, [
            "name" => "required",
            "warning_threshold_status" => "nullable",
            "warning_threshold" => "nullable",
            "status" => "required",
            "api_key" => "nullable",
            "secret_key" => "nullable",
            "public_key" => "nullable",
            "sandbox_base_url" => "nullable",
            "live_base_url" => "nullable",
            "slug" => "required|string|max:100",
            "file_name" => "nullable|string|max:255",
        ]);

        $api->update([
            "name" => $request->name,
            "warning_threshold_status" => $request->warning_threshold_status,
            "warning_threshold" => $request->warning_threshold,
            "status" => $request->status,
            "api_key" => $request->api_key,
            "secret_key" => $request->secret_key,
            "public_key" => $request->public_key,
            "sandbox_base_url" => $request->sandbox_base_url,
            "live_base_url" => $request->live_base_url,
            "slug" => $request->slug,
            "file_name" => $request->file_name,
        ]);

        return back()->with('message', 'Updated successfully');
    }

    public function getBalance(API $api)
    {
        $res = app("App\Http\Controllers\Providers\KingsVtuController")->balance();
        
        return response()->json($res);
    }
}
