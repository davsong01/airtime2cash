<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $mails = EmailLog::where('status', 'sent')->orderBy('created_at')->orderBy('status')->paginate(50);
        return view('admin.emails.index', ['emails' => $mails]);
    }

    public function pending () {
        $mails = EmailLog::where('status', 'pending')->orderBy('created_at','desc')->paginate(50);
        return view('admin.emails.index', ['emails' => $mails]);
    }

    public function resend(Request $request, EmailLog $id)
    {
        $this->sendEmailReal($id->toArray());
        return back()->with('message', 'Email resent successfully!');
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
    public function show(EmailLog $emailLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailLog $emailLog)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailLog $id)
    {
        $id->content = $request->message;
        $id->save();
        return back()->with('message', 'Email has been updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailLog $id)
    {
        $id->delete();
        return back()->with('message', 'Delete successful');
    }

    public function sendMail ($count = 100) {
        sendEmail($count);
    }

    public function sweep () {
        EmailLog::where('status', '!=', 'pending')->delete();

        return back()->with('message', 'Emails cleared successfully');
    }

    public function send(EmailLog $log)
    {
        $res = $this->sendEmailReal($log->toArray());

        if (isset($res) && ($res['message'] && $res['message'] == 'success')) {
            $log->status = 'sent';
            $log->errors = null;
            $log->sent_at = now();
            $log->save();

            return back()->with('message', 'Email sent!');
        } else {
            $log->errors = $res['error'] ?? 'unknown error';
            $log->save();

            return back()->with('error', 'There was an error while sending email ... '. $res['error']);

        }
    }
}
