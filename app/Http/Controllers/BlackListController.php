<?php

namespace App\Http\Controllers;

use App\Models\BlackList;
use Illuminate\Http\Request;

class BlackListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bliacklist = BlackList::paginate(20);
        return view('admin.customers.blacklist', ['customers' => $bliacklist]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'value' => 'required',
            'status' => 'required'
        ]);

        BlackList::create([
            'type' => $request->type,
            'value' => $request->value
        ]);
        return back()->with('message', 'Item added to blacklist successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(BlackList $blackList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlackList $blackList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BlackList $blackList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlackList $blackList)
    {
        //
    }

    public function status(Request $request)
    {
        $request->validate([
            'status' => 'required',
            'id' => 'required|integer'
        ]);

        $status = $request->status == 'active' ? 'in-active' : 'active';
        $black = BlackList::find($request->id)->update(['status' => $status]);

        if ($black) {
            return ['code' => 1, 'status' => $status, 'message' => 'Success'];
        } else {
            return ['code' => 0, 'message' => 'Failed to set status'];
        }
    }
}
