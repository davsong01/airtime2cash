<?php

namespace App\Http\Controllers;

use App\Models\EmailApiSetting;
use App\Models\EmailSetting;
use Illuminate\Http\Request;

class EmailApiController extends Controller
{
    public function edit(){
        $mailsetting = EmailApiSetting::first();
        return view('admin.email_settings', ['mailsetting' => $mailsetting]);
    }

    public function update(Request $request){
        $data = $this->validate($request, [
            "MAIL_DRIVER" => "required",
            "MAIL_HOST" => "required",
            "MAIL_PORT" => "required",
            "MAIL_USERNAME" => "required",
            "MAIL_PASSWORD" => "required",
            "MAIL_ENCRYPTION" => "required",
            "MAIL_FROM_ADDRESS" => "required",
            "MAIL_FROM_NAME" => "required",
            "MAIL_REPLY_TO_ADDRESS" => "nullable",
            "MAIL_REPLY_TO_NAME" => "nullable"
        ]);
        
        // $emailsetting = \DB::table('email_settings')->where('id', 1)->first();
        $emailsetting = EmailApiSetting::first();
       
        if(empty($emailsetting)){
            EmailApiSetting::create([
                'MAIL_DRIVER' => $data['MAIL_DRIVER'],
                "MAIL_HOST" => $data["MAIL_HOST"],
                "MAIL_PORT" => $data["MAIL_PORT"],
                "MAIL_USERNAME" => $data["MAIL_USERNAME"],
                "MAIL_PASSWORD" => $data["MAIL_PASSWORD"],
                "MAIL_ENCRYPTION" => $data["MAIL_ENCRYPTION"],
                "MAIL_FROM_ADDRESS" => $data["MAIL_FROM_ADDRESS"],
                "MAIL_FROM_NAME" => $data["MAIL_FROM_NAME"],
                "MAIL_REPLY_TO_ADDRESS" => $data["MAIL_REPLY_TO_ADDRESS"],
                "MAIL_REPLY_TO_NAME" => $data["MAIL_REPLY_TO_NAME"]
            ]);
        }else{
            $emailsetting->update([
                'MAIL_DRIVER' => $data['MAIL_DRIVER'],
                "MAIL_HOST" => $data["MAIL_HOST"],
                "MAIL_PORT" => $data["MAIL_PORT"],
                "MAIL_USERNAME" => $data["MAIL_USERNAME"],
                "MAIL_PASSWORD" => $data["MAIL_PASSWORD"],
                "MAIL_ENCRYPTION" => $data["MAIL_ENCRYPTION"],
                "MAIL_FROM_ADDRESS" => $data["MAIL_FROM_ADDRESS"],
                "MAIL_FROM_NAME" => $data["MAIL_FROM_NAME"],
                "MAIL_REPLY_TO_ADDRESS" => $data["MAIL_REPLY_TO_ADDRESS"],
                "MAIL_REPLY_TO_NAME" => $data["MAIL_REPLY_TO_NAME"]
            ]);
        }
        
        return back()->with('Email Setup completed');
    }
}
