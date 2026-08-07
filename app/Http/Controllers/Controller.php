<?php

namespace App\Http\Controllers;

use App\Mail\EmailMessages;
use App\Models\BillerLog;
use App\Models\EmailApiSetting;
use App\Models\EmailLog;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Image;
use InvalidArgumentException;
use RuntimeException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function basicApiCall($url, $payload, $headers, $method = 'POST')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($method == "GET") {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method == "PUT") {
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif (!empty($timeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        // \Log::info(['response' => $response, 'url' => $url, 'method' => $method, 'headers'=> $headers]);
        return json_decode($response, true);
    }


    public function uploadFile($file, $location = null, $width = null, $height = null)
    {
        $folder = $this->getPathToSaveFile($location);
        $name = uniqid(11) . '.' . $file->getClientOriginalExtension();

        if (substr($file->getMimeType(), 0, 5) == 'image') {
            $imageFile = Image::make($file);
            $imgWidth = $width ?? $imageFile->width();
            $imgHeight = $height ?? $imageFile->height();

            if (!is_null($imgWidth) && !is_null($imgHeight)) {
                if ($imgWidth < $imgHeight) {
                    $imageFile->resize($imgWidth, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                } else {
                    $imageFile->resize(null, $imgWidth, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
            }
        }

        $imageFile->save($this->storageRootPath() . '/' . $folder . '/' . $name);

        return $folder . '/' . $name;
    }

    private function storageRootPath(): string
    {
        $path = env('ENT') === 'local'
            ? env('LOCAL_STORAGE_PATH')
            : env('LIVE_STORAGE_PATH');

        return rtrim($path ?: public_path(), '/');
    }

    public function getPathToSaveFile($location = null)
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        if ($location) {
            $location = $location . '/' . $year . '/' . $month . '/' . $day;
        } else {
            $location = $year . '/' . $month . '/' . $day;
        }

        $path = $this->storageRootPath();

        if (!is_dir($path . '/' . $location)) {
            mkdir($path . '/' . $location, 0775, true);
        }

        return $location;
    }

    public function getIpAddress()
    {
        return Session::get('ip_address') ?? null;
    }

    public function getDomainName()
    {
        return Session::get('domain_name') ?? null;
    }

    public function getAppVersion()
    {
        return 1;
    }

    // public function settings(){
    //     return GeneralSetting::first();
    // }
    public function generateRequestId()
    {
        date_default_timezone_set("Africa/Lagos");
        $trx = date("YmdHi") . rand(1000000, 9999999);
        return $trx;
    }

    public function sendTransactionEmail($transaction, $user)
    {
        if (getSettings()->transaction_email_notification == 'yes') {
            $variation_name =  isset($transaction->variation) ? ' | ' . $transaction->variation->system_name : '';
            $product =  $transaction->product->name ?? '' .  $variation_name;
            $extras = isset($transaction->extras) ? $transaction->extras : '';
            $name = $user->firstname ?? 'Customer';
            $subject = "Transaction Alert";
            $body = '<p>Hello! ' . $name . ',</p>';
            $body .= '<p style="line-height: 2.0;">A transaction has just occured on your account on ' . config('app.name') . ' <br>Please find below the details of the transaction: <br>
            <strong>Transaction Purpose:</strong> ' . $transaction->reason . '<br>
            <strong>Transaction Status:</strong> ' . ucfirst($transaction->user_status) . '<br>
            <strong>Transaction Id:</strong> ' . $transaction->transaction_id . '<br>
            <strong>Transaction Date:</strong> ' . date("M jS, Y g:iA", strtotime($transaction->created_at)) . '<br>
            <strong>Transaction Description:</strong> ' . ucfirst($transaction->descr);

            if (!empty($transaction->extras)) {
                $body .= '<br> <strong>Extras:</strong> ' . $extras . '<br>';
            }

            if (!empty($transaction->unique_element)) {
                $body .= '<br><strong>Biller:</strong> ' . $transaction->unique_element . '<br>';
            }

            if (!empty($product)) {
                $body .= '<strong>Product:</strong> ' . $product . '<br>';
            }

            if (!empty($transaction->extra_info)) {
                foreach (json_decode($transaction->extra_info) as $key => $info) {
                    $body .= '<strong>' . $key . '</strong> ' . $product . '<br>';
                }
            }

            $body .= '<strong>Unit Price:</strong> ' . getSettings()->currency . $transaction->unit_price . '<br>';

            if (!empty($transaction->provider_charge)) {
                $body .= '<strong>Convenience Fee:</strong> ' . getSettings()->currency . number_format($transaction->provider_charge, 2) . '<br>';
            }

            $body .= '<strong>Quantity:</strong> ' . $transaction->quantity . '<br>
            <strong>Discount Applied:</strong> ' . getSettings()->currency . number_format($transaction->discount, 2) . '<br>
            <strong>Total Amount Paid:</strong> ' . getSettings()->currency . number_format($transaction->total_amount, 2) . '<br>
            <strong>Initial Balance:</strong> ' . getSettings()->currency . number_format($transaction->balance_before, 2) . '<br>
            <strong>Final Balance: </strong>' . getSettings()->currency . number_format($transaction->balance_after, 2) . '<br>
            <br>Warm Regards. (' . config('app.name') . ')<br/>
            </p>';

            $email = $user->email ?? 'noreply@airtime2cash.com';
            logEmails($email, $subject, $body);
        }
    }


    public function refineAndLogBiller($data, $category, $biller, $serviceId)
    {
        try {
            //code...
            $refinedData = [];
            if (isset($data['raw_response'])) {
                $dataX = $data['raw_response']['content'];

                if ($category->unique_element == 'profile_id') {
                    $refinedData['Profile_Id'] = $data['raw_response']['content']['profile_Id'] ?? null;
                }

                if ($category->unique_element == 'meter_number') {
                    $refinedData["Customer Name"] = $data['raw_response']['content']['Customer_Name'] ?? null;
                    $refinedData["Address"] = $data['raw_response']['content']['Address'] ?? null;
                    $refinedData["Meter Number"] = $data['raw_response']['content']['Meter_Number'] ?? null;
                }else

                if ($category->unique_element == 'iuc_number') {
                    $refinedData["Customer Name"] = $data['raw_response']['content']['Customer_Name'] ?? null;
                    $refinedData["IUC Number"] = $data['raw_response']['content']['Customer_Number'] ?? null;
                }else

                if ($category->unique_element == 'account_id') {
                    $refinedData["Customer Name"] = $data['raw_response']['content']['Customer_Name'] ?? null;
                    $refinedData["Account ID"] = $data['raw_response']['content']['Customer_Number'] ?? null;
                }

                $refinedData = array_unique(array_filter($refinedData));
                BillerLog::updateOrCreate(['billers_code' => $biller, 'service_id' => $serviceId],[
                    'billers_code' => $biller,
                    'service_id' => $serviceId,
                    'provider' => $data['provider'],
                    'refined_data' => json_encode($refinedData),
                    'raw_data' => json_encode($data['raw_response'])
                ]);

                return $refinedData;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function cronSendEmails()
    {
        set_time_limit(3000);
        $hourlyRate = 150;
        $oneHourAgo = Carbon::now()->subHours(1);
        ini_set('max_execution_time', 600); //5 minutes
        // $this->purgeSentEmails();
        // Check how many have been sent within the hour
        $sentWithinTheHour = EmailLog::where('status', 'sent')->whereBetween('sent_at', [$oneHourAgo, now()])->count();

        // \Log::info('Emails sending start at: ' .now());
        if ($sentWithinTheHour >= $hourlyRate) {
            \Log::info(now() . ' : Hourly email rate exceeded on server. ' . $sentWithinTheHour . ' emails already sent this hour');
            echo ('Hourly email rate exceeded on server. ' . $sentWithinTheHour . ' emails already sent this hour');
        } else {
            $pick = $hourlyRate - $sentWithinTheHour;

            $emails = EmailLog::where('status', 'pending')->whereNull('sent_at')->take($pick)->get();
            $count = 0;

            foreach ($emails as $email) {
                $data['recipient'] = $email->recipient;
                $data['content'] = $email->content;
                $data['subject'] = $email->subject;

                $res = $this->sendEmailReal($data);

                if (isset($res) && ($res['message'] && $res['message'] == 'success')) {
                    $count++;
                    $email->status = 'sent';
                    $email->sent_at = now();
                    $email->save();
                } else {
                    $email->errors = $res['error'] ?? 'unknown error';
                    $email->save();
                }
            }
            echo $count . ' emails sent successfully';
        }
    }

    public function sendEmailReal($current)
    {
        $provider = EmailApiSetting::first();

        if(!empty($provider)){
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

        try {
            Mail::to($current['recipient'])->send(new EmailMessages([
                'subject' => $current['subject'],
                'body' => $current['content'],
                'provider' => $current['provider'] ?? [],
            ]));

            return [
                'message' => 'success',
            ];
        } catch (\Exception $e) {
            return [
                'message' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkRequestIDFormat($requestId) {
        $requestIdTime = date("Y-m-d H:i", strtotime(substr($requestId,0,12)));

        if($requestIdTime > Carbon::now()->addMinutes(5) || $requestIdTime < Carbon::now()->addMinutes(-5)){
            return false;
        }else{
            return true;
        }
    }
}
