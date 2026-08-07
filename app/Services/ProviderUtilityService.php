<?php
namespace App\Services;

use App\Models\API;

class ProviderUtilityService {

    public function sendWarningEmail(API $api)
    {
        try {
            //code...
            if ($api->warning_threshold_status == 'active' && $api->warning_threshold > $api->balance ) {
                $email = getSettings()->official_email;

                $subject = "Low balance alert for " . $api->name . " on " . config('app.name');
                $body = '<p>Hello Admin,</p>';
                $body .= '<p style="line-height: 2.0;">Your wallet balance for ' .$api->name. ' on '. config("app.name") . ' is running low.<br>
                You currently have '.getSettings()->currency.number_format($api->balance, 2).' remaning in your '.$api->name.' wallet. Please recharge<br></p><br><br>Warm Regards';

                logEmails($email, $subject, $body);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
