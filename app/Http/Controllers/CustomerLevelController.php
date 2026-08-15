<?php

namespace App\Http\Controllers;

use App\Models\CustomerLevel;
use Illuminate\Http\Request;

class CustomerLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $levels = CustomerLevel::withCount('customers')->orderBy('order', 'desc')->get();

        return view('admin.customerlevel.index', compact('levels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.customerlevel.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'order' => 'required|unique:customer_levels,order',
            'upgrade_amount' => 'required',
            'status' => 'required|boolean',
        ]);

        CustomerLevel::create([
            'name' => $request->name,
            'order' => $request->order,
            'upgrade_amount' => $request->upgrade_amount,
            'status' => $request->boolean('status'),
        ]);

        return redirect(route('customerlevel.index'))->with('message', 'Created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerLevel $customerLevel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerLevel $customerlevel)
    {
        return view('admin.customerlevel.edit', compact('customerlevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerLevel $customerlevel)
    {
        $this->validate($request, [
            'name' => 'required',
            'order' => 'required|unique:customer_levels,order,' . $customerlevel->id,
            'upgrade_amount' => 'required',
            'status' => 'required|boolean',
        ]);

        $customerlevel->update([
            'name' => $request->name,
            'order' => $request->order,
            'upgrade_amount' => $request->upgrade_amount,
            'status' => $request->boolean('status'),
        ]);

        return redirect(route('customerlevel.index'))->with('message', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerLevel $customerlevel)
    {
        if ($customerlevel->customers->count() > 0) {
            return back()->with('error', 'Level cannot be deleted because it has customers');
        } else {
            $customerlevel->delete();

            return redirect(route('customerlevel.index'))->with('message', 'Level deleted successfully');
        }
    }
}
