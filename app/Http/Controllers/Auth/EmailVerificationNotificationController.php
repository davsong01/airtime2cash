<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\EmailApiSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $provider = EmailApiSetting::first();

        try {
            //code...
            if (!empty($provider)) {
                config([
                    'mail.driver' => $provider->MAIL_DRIVER,
                    'mail.host' => $provider->MAIL_HOST,
                    'mail.port' => $provider->MAIL_PORT,
                    'mail.encryption' => $provider->MAIL_ENCRYPTION,
                    'mail.username' => $provider->MAIL_USERNAME,
                    'mail.password' => $provider->MAIL_PASSWORD,
                    'mail.replyToName' => $provider->MAIL_REPLY_TO_NAME,
                    'mail.replyToAddress' => $provider->MAIL_REPLY_TO_ADDRESS,
                    'mail.from' => [
                        'address' => $provider->MAIL_FROM_ADDRESS,
                        'name' => $provider->MAIL_FROM_NAME,
                    ],
                ]);
                $current['provider'] = $provider->toArray();
            }

            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $th) {
            \Log::error($th->getMessage() . ' Line: ' . $th->getLine() . ' Filename: ' . $th->getFile());
        }

        
        return back()->with('status', 'Verification Link sent');
    }
}
