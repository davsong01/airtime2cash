<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ann = Announcement::all();

        if (count($ann) < 1) {
            $ann = (object) [
                0 => (object) ['type' => 'popup', 'status' => 'inactive', 'message' => null, 'title' => 'Pop-up Announcement'],
                1 => (object) ['type' => 'scroll', 'status' => 'inactive', 'message' => null, 'title' => 'Scroll Announcement',]
            ];
        }
        return view ('admin.announcement.index', ['announcements' => $ann]);
    }

    function scroll () {
        $ann = Announcement::where('type', 'scroll')->paginate(20);
        return view ('admin.announcement.index', ['announcements' => $ann]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view ('admin.announcement.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        foreach($request->status as $key => $val) {
            Announcement::updateOrCreate([
                'type' => $request->type[$key]
            ], [
                'message' => $request->message[$key],
                'status' => $val,
                'title' => $request->title[$key]
            ]);
        }
        return back()->with('message', 'Announcement updated successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        return view ('admin.announcement.edit', ['announcement' => $announcement]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required',
            'status' => 'required',
            'type' => 'required',
            'message' => 'required',
        ]);

        $announcement->title = $request->title;
        $announcement->status = $request->status;
        $announcement->type = $request->type;
        $announcement->message = $request->message;
        $announcement->save();

        return redirect()->route('announcement.index')->with('message', "{$announcement->title} was updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        //
    }
}
