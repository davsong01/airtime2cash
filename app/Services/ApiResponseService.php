<?php

namespace App\Services;
use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Variation;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\WalletController;
use App\Models\TransactionLog;

class ApiResponseService
{
    public function __construct(private ResponseService $responseService) {
    
    }

    public function generateAccessToken() {
        $payload = [
            "public_key" => $this->posWebServicePublicKey,
            "type" => "service_access"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, url($this->posWebServiceUrl."/api/auth/generate_key"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Accept: application/json',
                'private-key: ' . $this->posWebServicePrivateKey
            )
        );

        $response = curl_exec($ch);
        $decodedResponse = json_decode($response, true);
        
        if(isset($decodedResponse['status']) && $decodedResponse['status'] == "1000" && isset($decodedResponse['data'])) {
            ServiceAuthToken::updateOrCreate(["service" => "POS_SERVICE_ACCESS_TOKEN"],[
                'service' => "POS_SERVICE_ACCESS_TOKEN",
                "token" => $decodedResponse['data']['key']
            ]);

            return $this->responseService->formatServiceResponse("success", "Token set successfully", [], null);
        }

        return $this->responseService->formatServiceResponse("failed", "Token failed to set", [], null);
    }

    public function getCategories(){
        try {
            $categories = Category::where('status', 'active')->where('type', 'general')->orderBy('order', 'ASC')->select('display_name','status','slug', 'description')->get();
            return $this->responseService->formatServiceResponse("success", "Retrieved successfully", [], $categories);
        } catch (\Throwable $err) {
            $errorMessage = env("ENT") == "live" ? "Internal Server Exception" : $err->getMessage();
            return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
        }
    }

    public function getProductsByCategory($slug){
        $category = Category::with([
            'products' => function ($query) {
                return $query->where('status', 'active');
            }
        ])->where('type','general')->where('status', 'active')->where('slug', $slug)->first();

        if (empty($category)) {
            return $this->responseService->formatServiceResponse("error", '', ['Invalid slug provided'], null);
        }
        
        $products = $category->products->select('id', 'display_name', 'slug', 'description', 'has_variations');
        
        $data = [
            'display_name' => $category->display_name,
            'products' => $products,
        ];

        return $this->responseService->formatServiceResponse("success", "Retrieved successfully", [], $data);        
    }


    public function getVariationsByProductSlug($slug){
        $product = Product::where('slug',$slug)->where('status','active')->where('has_variations','yes')->where('type', 'general')->first();
        if(empty($product)) return $this->responseService->formatServiceResponse("error", '', ['Invalid product slug provided'], null);
        
        $variations = Variation::select('id','product_id','slug','category_id','system_price as price','system_name as name','min','max','fixed_price')->where('product_id', $product->id)->where('status', 'active')->orderBy('id', 'DESC')->get();
        
        $allVariations = [];
        foreach ($variations as $key => $variation) {
            $req = new Request([
                'variation_id' => $variation->id,
                'raw' => 'yes',
            ]);

            // $discount = app('App\Http\Controllers\TransactionController')->getCustomerDiscount($req);

            // $variation->discount = $discount;

            if (in_array($variation->category->unique_element, verifiableUniqueElements()) || in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
                $variation->verifiable = 'yes';
            } else {
                $variation->verifiable = 'no';
            }

            if (($variation->fixed_price == 'Yes') && empty($variation->system_price) || $variation->system_price < 0) {
                unset($variations[$key]);
            }

            if (in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
                $variation->unique_element = specialVerifiableVariations()[$variation->slug];
            } else {
                $variation->unique_element = $variation->category->unique_element;
            }

            $allVariations[] = [
                'name' => $variation->name,
                'price' =>  $variation->price,
                'variation_slug' =>  $variation->slug,
                'min' => $variation->min, 
                'max' => $variation->max,
                'fixed_price' => $variation->fixed_price,
                'verifiable' => $variation->verifiable,
                'unique_element' => $variation->unique_element,
            ];
        }

        $variations = [
            'product_name' => $product->display_name,
            'variations' => $allVariations,
        ];

        return $this->responseService->formatServiceResponse("success", "Retrieved successfully", [], $variations);        
    }

    public function verifyBiller(Request $request)
    {
        $variation = Variation::where('slug', $request->variation_slug)->first();
        
        if (in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
            $element = specialVerifiableVariations()[$variation->slug];
        } else {
            $element = $variation->category->unique_element;
        }

        $product = $variation->product;
        $api = $variation->api;
        $file_name = $variation->api->file_name;

        $request['product_name'] = $product->name ?? null;
        $request['variation_name'] = $variation->slug ?? null;
        $request['category_id'] = $product->category->id ?? null;
        $request['product_slug'] = $variation->product->slug ?? null;
        $request['network'] = $variation->network ?? null;

        $request['unique_element'] = $request->billersCode;

        $data = [
            'variation' => $variation,
            'product' => $product,
            'api' => $api,
            'request' => $request
        ];
    
        // Get Api
        $verify = app("App\Http\Controllers\Providers\KingsVtuController")->verify($data);
        
        if (isset($verify) && $verify['status_code'] == 1) {
            if (isset($verify['raw_response'])) {
                $refinedData = app('App\Http\Controllers\TransactionController')->refineAndLogBiller($verify, $variation->category, $request['unique_element'], $request['product_slug']);
            }

            return $this->responseService->formatServiceResponse("success", "Retrieved successfully", [], $refinedData);        

        } else {
            $res = $verify['message'] ?? 'Biller not reachable at the moment, please try again later';
            return $this->responseService->formatServiceResponse("failed", $res, [], null);
        }
    }

    public function getBalance($user){
        $wallet = new WalletController();
        $balance = $wallet->getWalletBalance($user);
        $balances = [
            'wallet_balance' => $balance,
        ];

        return $this->responseService->formatServiceResponse("success", "Retrieved successfully", [], $balances);        
    }

    public function initializeTransaction(Request $request){
        try {
            //code...
            $blacklist = app('App\Http\Controllers\TransactionController')->bounceBlacklist($request->phone ?? $request->unique_element, auth()->user()->email, $request->email);

            if ($blacklist) {
                return $this->responseService->formatServiceResponse("error", '', ['Account blacklisted!, kindly reach out to support!'], null);
            }

            // Get product
            $product = Product::where('slug', $request->product_slug)->first();
            $category = $product->category_id;
            
            if (empty($product)) {
                return $this->responseService->formatServiceResponse("error", '', ['Invalid product slug!, kindly try again!'], null);
            }
            // Log external API
            $element = $product->category->unique_element;
            $request['unique_element'] = $request->unique_element ?? $request->$element;

            if ($product->has_variations == 'yes') {
                $variation = Variation::where('slug', $request->variation_slug)->where('product_id', $product->id)->first();
                if ($variation->fixed_price == 'Yes') {
                    $request['amount'] = $variation->system_price;
                } elseif ($product->allow_subscription_type == 'yes' && $variation->category->unique_element == 'iuc_number') {
                    if (!empty($request->bouquet) && $request->bouquet == 'renew') {
                        $req = new Request([
                            'unique_element' => $request['unique_element'],
                            'variation' => $variation->id,
                        ]);
                        $res = app('App\Http\Controllers\TransactionController')->verify($req);
                        if (isset($res['renewal_amount'])) {
                            $request['amount'] = $res['renewal_amount'];
                        } else {
                            $request['amount'] = $variation->system_price;
                        }
                    } else {
                        $request['amount'] = $variation->system_price;
                    }
                } else {
                    if (empty($request['amount'])) {
                        return $this->responseService->formatServiceResponse("error", '', ['Amount is required for purchase of '.$product->name.'!'], null);
                    }else{
                        $request['amount'] = app('App\Http\Controllers\TransactionController')->removeCharsInAmount($request->amount);
                    }

                    if($request['amount'] < $variation->min && !empty($variation->min)){
                        return $this->responseService->formatServiceResponse("error", '', ['You cannot purchase below ' .$variation->min. ' for this product!'], null);
                    }

                    if ($request['amount'] > $variation->max && !empty($variation->max)) {
                        return $this->responseService->formatServiceResponse("error", '', ['You cannot purchase above ' . $variation->max . ' for this product!'], null);
                    }
                }
            } else {
                if ($product->fixed_price == 'yes') {
                    $request['amount'] = $product->system_price;
                } else {
                    if (empty($request['amount'])) {
                        return $this->responseService->formatServiceResponse("error", '', ['Amount is required for purchase of ' . $product->name . '!'], null);
                    }

                    $request['amount'] = app('App\Http\Controllers\TransactionController')->removeCharsInAmount($request->amount);
                }

                if ($request['amount'] < $product->min && !empty($product->min)) {
                    return $this->responseService->formatServiceResponse("error", '', ['You cannot purchase below ' . $product->min . ' for this product!'], null);
                }
            
                if ($request['amount'] > $product->max && !empty($product->max)) {
                    return $this->responseService->formatServiceResponse("error", '', ['You cannot purchase above ' . $product->max . ' for this product!'], null);
                }
            }
        

            // Verify Meter
            if ($product->allow_meter_validation) {
                // $meterValidation = app('App\Http\Controllers\TransactionController')->validateMeter($product);
                // if (isset($meterValidation) && $meterValidation['code'] == 0) {
                //     return back()->with('error', $meterValidation['error']);
                // }
            }

            $request['discount'] = 0;
            
            if ($product->has_variations == 'yes') {
                $discount = app('App\Http\Controllers\TransactionController')->getDiscount($variation, 'variation', $request['amount'], 'yes');
            } else {
                $discount = app('App\Http\Controllers\TransactionController')->getDiscount($product, 'product', $request['amount'], 'yes');
            }
            
            $discountedAmount = $discount['discounted_price'];
            $disCountApplied = $discount['discount_applied'];

            $request['quantity'] = $request->quantity ?? 1;
            $request['total_amount'] = $discountedAmount * $request['quantity'];
            $request['discount'] = $disCountApplied * $request['quantity'];

            // Get Wallet Balance
            $wallet = new WalletController();
            $balance = $wallet->getWalletBalance(auth()->user());
            
            if ($balance < $request['total_amount']) {
                return back()->with('error', 'Insufficient Wallet Balance, Please try again');
            }

            // Log Wallet
            $request['type'] = 'debit';
            $request['customer_id'] = auth()->user()->customer->id;
            $request['payment_method'] = 'wallet';
            $request['balance_before'] = $balance;
            $request['ip_address'] = app('App\Http\Controllers\Controller')->getIpAddress();
            $request['domain_name'] = app('App\Http\Controllers\Controller')->getDomainName();
            $request['customer_email'] = auth()->user()->email;
            $request['customer_phone'] = auth()->user()->phone;
            $request['customer_name'] = auth()->user()->firstname;
            $request['variation_id'] = $variation->id ?? null;
            $request['product_id'] = $product->id;
            $request['product_name'] = $product->name;
            $request['variation_name'] = $variation->slug ?? null;
            $request['category_id'] = $product->category->id;
            $request['api_id'] = $variation->api->id ?? $product->api_id;
            $request['product_slug'] = $variation->product->slug ?? $product->slug;
            $request['variation_slug'] = $variation->slug ?? null;
            $request['network'] = $variation->network ?? null;
            $request['reason'] = 'Product Purchase';
            $request['subscription_type'] = $variation->bouquet ?? 'change';

            // Check request id format
            $check = TransactionLog::where('reference_id', $request->request_id)->first();
            if(!empty($check)){
                return $this->responseService->formatServiceResponse("failed", '', ['DUPLICATE REQUEST ID DETECTED'], null);
            }

            if (app('App\Http\Controllers\Controller')->checkRequestIDFormat($request->request_id) == false) {
                $log = "IMPROPER REQUEST ID";
                //get full message
                if (strlen($request->request_id) < 13) {
                    $log .= "- DOES NOT CONTAIN DATE";
                } elseif (!is_numeric(substr($request->request_id, 0, 8))) {
                    $log .= ": IMPROPER DATE FORMAT – FIRST 8 CHARACTERS MUST BE DATE (TODAY’S DATE – YYYYMMDD)";
                } elseif (substr($request->request_id, 0, 8) != date("Ymd")) {
                    $log .= "- NOT TODAY’S DATE – FIRST 8 CHARACTERS MUST BE TODAY’S DATE IN THIS FORMAT: YYYYMMDD";
                } elseif (substr($request->request_id, 8, 2) != date("H")) {
                    $log .= "-  INCORRECT TIME – MAKE SURE YOU ARE USING GMT+1 AND YOUR HOUR IS IN 24 HOURLY FORMAT";
                }
                return $this->responseService->formatServiceResponse("failed", '', [$log], null);

            }

            $request['request_id'] = $request->request_id;
            $request['transaction_id'] = 'A2C-' .  $request['request_id'];
            $request['unique_element'] = $request->billersCode;

            // Log basic transaction
            $transaction = app('App\Http\Controllers\TransactionController')->logTransaction($request->all());

            // Log wallet
            $wal = $wallet->logWallet($request->all());

            // Update Customer Wallet
            $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], $request['type']);
        } catch (\Throwable $th) {
            
        }
        // Process Transaction
        try {
            //code...
            $transaction = app('App\Http\Controllers\TransactionController')->processTransaction($request->all(), $transaction, $product, $variation ?? null);
        } catch (\Throwable $th) {
            \Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);

            return $this->responseService->formatServiceResponse("unknown", '', ['An error occured, please try again later'], null);
        }
       
        // Log Transaction Email
        app('App\Http\Controllers\TransactionController')->sendTransactionEmail($transaction, auth()->user());
        
        if(isset($transaction) && $transaction->status == 'success'){
            $data = [
                'status' => $transaction->status,
                'request_id' => $transaction->reference_id,
                "transaction_id" => $transaction->transaction_id,
                'purchase_status' => $transaction->user_status,
                'amount' => $transaction->amount,
                'discount' => $transaction->discount,
                'total_amount' => $transaction->total_amount,
                'response_description' => $transaction->descr,
                'transaction_date' => $transaction->created_at,
                'extras' => $transaction->extras,
                'extra_info' => !empty($transaction->extra_info) ? json_decode($transaction->extra_info, true) : []
            ];


            return $this->responseService->formatServiceResponse("success", '', [], $data);

        }
       
    }




    // public function doCurl(string $url, string $method="GET", mixed $payload=null) {

    //     $serviceToken = ServiceAuthToken::where(["service" => "POS_SERVICE_ACCESS_TOKEN"])->first();

    //     // dd($this->posWebServiceUrl.$url, $serviceToken->token);
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, url($this->posWebServiceUrl.$url));
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //     if($method == "POST") {
    //         curl_setopt($ch, CURLOPT_POST, true);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    //     }

    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt(
    //         $ch,
    //         CURLOPT_HTTPHEADER,
    //         array(
    //             'Content-Type: application/json',
    //             'Accept: application/json',
    //             'access-key: ' . $serviceToken->token
    //         )
    //     );
    //     $response = curl_exec($ch);
    //     return $response;
    // }

    // /**
    //  * @param Request $request Takes a request object containing the user inputs
    //  * @param int $isJson checks if receiver requires response in json format
    //  *
    //  */
    // public function applyForPos(int $customerId, string $platform, int $quantity, string $address, string $area, string $state, string $consent) {
    //     $customer = Customer::find($customerId);

    //     if(empty($customer)) {
    //         return $this->responseService->formatServiceResponse("failed", "Kindly login and try again", [], null);
    //     }

    //     $cusID = $customer->id;
    //     $user = $customer->user;

    //     PosRequest::create([
    //         'customer_id' => $cusID,
    //         'email' => $user->email,
    //         'phone' => $user->phone,
    //         'consented' => $consent == 'on' ? 'Yes' : 'No',
    //         'status' => 'pending',
    //         'platform' => $platform,
    //         'address' => $address,
    //         'area' => $area,
    //         'state' => $state,
    //         'quantity' => $quantity
    //     ]);

    //     return $this->responseService->formatServiceResponse("success", "Application successful", [], null);
    // }

    // public function createPosDevice(string $imei, string $mac_address, string $serialNo, string $manufacturer, string $type, string $model) {
    //     PosDevice::create([
    //         "serial_no" => $serialNo,
    //         "manufacturer" => $manufacturer,
    //         "imei" => $imei,
    //         "mac_address", $mac_address,
    //         "type" => $type,
    //         "model" => $model
    //     ]);

    //     return $this->responseService->formatServiceResponse("success", "Created successful", [], null);
    // }

    // public function fetchPosDevicById(int $id) {
    //     $posDevice = PosDevice::find($id);

    //     if(empty($posDevice)) {
    //         return $this->responseService->formatServiceResponse("failed", "POS Device not found", [], null);
    //     }

    //     return $this->responseService->formatServiceResponse("success", "Fetched successful", [], $posDevice);
    // }

    // public function updatePosDevice(int $id, string $serialNo, string $manufacturer, string $type, string $model, string $status) {
    //     $posDevice = $this->fetchPosDevicById($id);

    //     if($posDevice["status"] == "failed") {
    //         return $posDevice;
    //     }

    //     PosDevice::where(["id" => $id])->update([
    //         "serial_no" => $serialNo,
    //         "manufacturer" => $manufacturer,
    //         "type" => $type,
    //         "model" => $model,
    //         "status" => $status
    //     ]);

    //     return $this->responseService->formatServiceResponse("success", "Updated successful", [], null);
    // }

    // public function filterPosDevices(array $statuses=null) {
    //     $devices = (new \App\Models\PosDevice)->newQuery();

    //     if(!empty($statuses)) {
    //         $devices = $devices->whereIn('status', $statuses);
    //     }

    //     $devices = $devices->get();
    //     return $devices;
    // }

    // public function updatePosCallback($type, $destination,$payload,$max_no_of_tries, $requires_auth, $customer_id, $reference = null) {
    //     $newposcallback = PosCallbacks::create([
    //         "type" => $type,
    //         "status" => "pending",
    //         "destination" => $destination,
    //         "payload" => json_encode($payload),
    //         "customer_id" => $customer_id,
    //         "reference" => $reference,
    //         "max_no_of_tries" => $max_no_of_tries,
    //         "next_trial_time" => Carbon::now()->addMinutes(1),
    //         "requires_auth" => $requires_auth,
    //         "domain_id" => \Request::get("domain_id")
    //     ]);
    //     return $newposcallback;
    // }

    // public function cronPosCallback(PosCallbacks $posCallbacks) {
    //     if ($posCallbacks->status == 'sent') {
    //         return 0;
    //     }
    //     $posCallbacks->update([
    //         "trial_last_time" => Carbon::now(),
    //         "no_of_tries" =>  $posCallbacks->no_of_tries == null ? 1 : $posCallbacks->no_of_tries + 1
    //     ]);
    //     $data = json_decode($posCallbacks->payload, true);
    //     if ($posCallbacks->requires_auth == 1) {
    //         $token = self::createAuthKey(
    //             $posCallbacks->customer_id,
    //             $posCallbacks->type,
    //             $posCallbacks->domain_id,
    //             $posCallbacks->reference
    //         );
    //         if(isset($token["data"])) {
    //             $data["auth_key"] = $token["data"];
    //         }else{
    //             return 1;
    //         }
    //     }
    //     try {
    //         $curl = curl_init();
    //         curl_setopt_array(
    //             $curl,
    //             array(
    //                 CURLOPT_URL => $posCallbacks->destination,
    //                 CURLOPT_RETURNTRANSFER => true,
    //                 CURLOPT_ENCODING => '',
    //                 CURLOPT_MAXREDIRS => 10,
    //                 CURLOPT_TIMEOUT => 0,
    //                 CURLOPT_FOLLOWLOCATION => true,
    //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST => 'POST',
    //                 CURLOPT_POSTFIELDS => json_encode($data),
    //                 CURLOPT_HTTPHEADER => [
    //                     'Content-Type: application/json',
    //                 ]
    //             )
    //         );
    //         $response = curl_exec($curl);
    //         $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    //         curl_close($curl);

    //         if($httpStatus == 200) {
    //             $posCallbacks->update([
    //                 "status" => "sent",
    //                 // next_trial_time
    //             ]);
    //             return 1;
    //         }
    //         $next_trial_time = null;
    //         if($posCallbacks->no_of_tries == 1) {
    //             $next_trial_time = Carbon::now()->addMinutes(5);
    //         }
    //         if($posCallbacks->no_of_tries == 2) {
    //             $next_trial_time = Carbon::now()->addMinutes(5);
    //         }
    //         if($posCallbacks->no_of_tries == 3) {
    //             $next_trial_time = Carbon::now()->addMinutes(30);
    //         }

    //         if($posCallbacks->no_of_tries == 4) {
    //             $next_trial_time = Carbon::now()->addHours(6);
    //         }

    //         if($posCallbacks->no_of_tries == 5) {
    //             $next_trial_time = Carbon::now()->addHours(12);
    //         }
    //         if($httpStatus != 200) {
    //             $posCallbacks->update([
    //                 "next_trial_time" => $next_trial_time
    //             ]);
    //             return 1;
    //         }

    //     } catch (\Throwable $th) {
    //         logs()->info($th);
    //     }
    // }

    // public function fetchPosTerminalById(int $id) {
    //     $posTerminal = PosTerminal::find($id);

    //     if(empty($posTerminal)) {
    //         return $this->responseService->formatServiceResponse("failed", "POS Terminal not found", [], null);
    //     }

    //     return $this->responseService->formatServiceResponse("success", "Fetched successful", [], $posTerminal);
    // }

    // public function fetchPosTerminalByCustomerId(int $customerId) {
    //     $posTerminals = PosTerminal::where(['customer_id' => $customerId])->orderBy("id","desc")->get();

    //     return $this->responseService->formatServiceResponse("success", "Fetched successful", [], $posTerminals);
    // }

    // public function createTerminal(int $customerId, int $deviceId, string $name, string $phone, string $setupStatus, string $address, string $area, string $state, int $settlementType, int $isCashout, int $isTransfer, int $isBills, int $lastAdminId, int $monthlyTargetQty, float $monthlyTargetValue, string $status) {
    //     $customer = Customer::find($customerId);
    //     if(empty($customer)) {
    //         return $this->responseService->formatServiceResponse("failed", "Customer not found", [], null);
    //     }
    //     $hasRight = CustomerRole::where(['customer_id' => $customer->id, "role_id" => env("POS_AGENT_ID")])->first();
    //     if(empty($hasRight)) {
    //         return $this->responseService->formatServiceResponse("failed", "Customer does not have right", [], null);
    //     }
    //     $device = $this->fetchPosDevicById($deviceId);
    //     $device = $device['data'];

    //     DB::beginTransaction();

    //     $max = DB::table('pos_terminals')->max('terminal_id');

    //     if ($max < 10000) {
    //         $count = 10000;
    //     } else {
    //         $count = $max + 1;
    //     }

    //     try{
    //         $terminal = PosTerminal::create([
    //             "customer_id" => $customer->id,
    //             "device_id" => $device->id,
    //             "terminal_id" => $count,
    //             "name" => $name,
    //             "phone_number" => $phone,
    //             "device_serial" => $device->serial_no,
    //             "setup_status" => $setupStatus,
    //             "address" => $address,
    //             "area" => $area,
    //             "state" => $state,
    //             "settlement_type" => $settlementType,
    //             "is_cashout" => $isCashout,
    //             "is_transfer" => $isTransfer,
    //             "is_bills" => $isBills,
    //             "last_admin_id" => $lastAdminId,
    //             "monthly_target_qty" => $monthlyTargetQty,
    //             "monthly_target_value" => $monthlyTargetValue,
    //             'status' => $status
    //         ]);

    //         $this->createDeviceTrack($customer->id, $terminal->id, $device->id, $lastAdminId, "assigned");

    //         $this->updatePosDevice($device->id, $device->serial_no, $device->manufacturer, $device->type, $device->model, "assigned");

    //         DB::commit();

    //         // $this->extendToPosWebService($customer->id, $terminal->id);

    //         return $this->responseService->formatServiceResponse("success", "Created successfully", [], null);
    //     }catch(Exception $err) {
    //         DB::rollback();

    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "Error: Contact Tech Support" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("failed", $errorMessage, [], null);
    //     }
    // }

    // public function extendToPosWebService(int $customerId, int $terminalId=null) {
    //     try{
    //         $customerToExtend = Customer::where(["id" => $customerId])->select("id", "user_id", "status", "customer_type", "category", "created_at", "updated_at", "domain_id")->first();

    //         $userToExtend = User::where(["id" => $customerToExtend->user_id])->select("id", "name", "lastname", "middle_name", "email", "phone", "status", "created_at", "updated_at", "domain_id", "duplicate_check")->first();

    //         $userToExtend->makeHidden(['personal_details_verification_status', 'bvn_verification_status', 'identity_verification_status', 'address_verification_status', 'faceid_verification_status']);

    //         $terminalToExtend = null;
    //         if(!empty($terminalId)) {
    //             $terminalToExtend = PosTerminal::where(["id" => $terminalId])->select("id", "customer_id", "device_id", "terminal_id", "name", "phone_number", "device_serial", "status", "setup_status", "address", "area", "state", "settlement_type", "is_cashout", "is_transfer", "is_bills", "monthly_target_qty", "monthly_target_value","created_at", "updated_at")->first();
    //         }

    //         $reservedAccountNumbers = ReservedAccountNumbers::where(['customer_id' => $customerId])->whereNotNull("terminal_id")->select("id", "account_reference", "account_number", "account_name", "bank_name", "terminal_id", "created_at", "updated_at")->get();

    //         $url = "";

    //         $dataToExtend = [
    //             "user" => $userToExtend,
    //             "customer" => $customerToExtend,
    //             "terminal" => $terminalToExtend,
    //             "reserved_accounts" => $reservedAccountNumbers
    //         ];

    //         // TERMINAL SETUP;
    //         $this->updatePosCallback('TERMINAL SETUP',$this->posWebServiceUrl.'/hooks/terminal_setup',$dataToExtend, 3, 1, $customerId);
    //         return;
    //     }catch(Exception $err) {
    //         dd($err);
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "Error: Failed to extend to POS web service" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("failed", $errorMessage, [], null);
    //     }
    // }


    // public function editTerminal(int $id, int $deviceId, string $name, string $phone, string $setupStatus, string $address, string $area, string $state, int $settlementType, int $isCashout, int $isTransfer, int $isBills, int $lastAdminId, int $monthlyTargetQty, float $monthlyTargetValue, string $status) {
    //     $posTerminal = $this->fetchPosTerminalById($id);

    //     if($posTerminal["status"] == "failed") {
    //         return $posTerminal;
    //     }

    //     $posTerminal = $posTerminal['data'];

    //     $device = $this->fetchPosDevicById($deviceId);
    //     if($device["status"] == "failed") {
    //         return $device;
    //     }
    //     $device = $device['data'];

    //     if($device->id != $posTerminal->device_id) {
    //         $prevDevice = $this->fetchPosDevicById($posTerminal->device_id);
    //         if($prevDevice["status"] == "failed") {
    //             return $prevDevice;
    //         }
    //         $prevDevice = $prevDevice['data'];

    //         DB::beginTransaction();
    //         try{
    //             $this->createDeviceTrack($posTerminal->customer_id, $posTerminal->terminal_id, $posTerminal->device_id, $lastAdminId, "unassigned");

    //             $this->updatePosDevice($posTerminal->device_id, $prevDevice->serial_no, $prevDevice->manufacturer, $prevDevice->type, $prevDevice->model, "unassigned");

    //             PosTerminal::where(["id" => $posTerminal->id])->update([
    //                 "device_id" => $device->id,
    //                 "name" => $name,
    //                 "phone_number" => $phone,
    //                 "device_serial" => $device->serial_no,
    //                 "setup_status" => $setupStatus,
    //                 "address" => $address,
    //                 "area" => $area,
    //                 "state" => $state,
    //                 "settlement_type" => $settlementType,
    //                 "is_cashout" => $isCashout,
    //                 "is_transfer" => $isTransfer,
    //                 "is_bills" => $isBills,
    //                 "last_admin_id" => $lastAdminId,
    //                 "monthly_target_qty" => $monthlyTargetQty,
    //                 "monthly_target_value" => $monthlyTargetValue,
    //                 "status" => $status
    //             ]);

    //             $this->createDeviceTrack($posTerminal->customer_id, $posTerminal->terminal_id, $device->id, $lastAdminId, "assigned");

    //             $this->updatePosDevice($device->id, $device->serial_no, $device->manufacturer, $device->type, $device->model, "assigned");

    //             DB::commit();

    //             $this->extendToPosWebService($posTerminal->customer_id, $terminal->id);

    //             return $this->responseService->formatServiceResponse("success", "Updated successfully", [], null);
    //         }catch(Exception $err) {
    //             DB::rollback();

    //             Log::error($err);
    //             $errorMessage = env("ENT") == "live" ? "Error: Contact Tech Support" : $err->getMessage();
    //             return $this->responseService->formatServiceResponse("failed", $errorMessage, [], null);
    //         }
    //     }else{
    //         PosTerminal::where(["id" => $posTerminal->id])->update([
    //             "name" => $name,
    //             "phone_number" => $phone,
    //             "setup_status" => $setupStatus,
    //             "address" => $address,
    //             "area" => $area,
    //             "state" => $state,
    //             "settlement_type" => $settlementType,
    //             "is_cashout" => $isCashout,
    //             "is_transfer" => $isTransfer,
    //             "is_bills" => $isBills,
    //             "last_admin_id" => $lastAdminId,
    //             "monthly_target_qty" => $monthlyTargetQty,
    //             "monthly_target_value" => $monthlyTargetValue,
    //             "status" => $status
    //         ]);

    //         return $this->responseService->formatServiceResponse("success", "Updated successfully", [], null);
    //     }
    // }

    // public function createDeviceTrack(int $customerId, int $terminalId, int $deviceId, int $adminId, string $action, string $issueNote=null) {
    //     PosDeviceTrack::create([
    //         'customer_id' => $customerId,
    //         "terminal_id" => $terminalId,
    //         "device_id" => $deviceId,
    //         "admin_id" => $adminId,
    //         "action" => $action,
    //         "issue_note" => $issueNote
    //     ]);

    //     return $this->responseService->formatServiceResponse("success", "Created successfully", [], null);
    // }

    // public function constrRandStr(int $length) {
    //     $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    //     $charactersLength = strlen($characters);
    //     $randomString = '';
    //     for ($i = 0; $i < $length; $i++) {
    //         $randomString .= $characters[random_int(0, $charactersLength - 1)];
    //     }
    //     $randomString = "VPA".$randomString;
    //     return $randomString;
    // }

    // /**
    //  * @param int $customerId
    //  * Customer details is sent to the POS Terminal web service for duplication
    //  *  @return Redirect
    //  */
    // public function approvePosApplication(int $customerId) {}

    // /**
    //  *
    //  *  Fetch all POS applications approved or disapproved
    //  *
    //  */
    // public function fetchPosApplications() {}

    // /**
    //  * @param Request $request Takes a request object containing the user inputs
    //  * Checks and validates the authentication key along side its purpose
    //  */
    // public function confirmAuthenticationKey(Request $request) {}

    // /**
    //  * Add new device
    //  */
    // public function setupPosDevice () {}

    // /**
    //  * Allocate device to new customer
    //  */
    // public function allocateDeviceToCustomer () {}

    // /**
    //  * @param int $deviceId
    //  * @param int $customerId
    //  * Retrieve POS device from customer
    //  *  @return Redirect
    //  */
    // public function retrieveDevice(int $deviceId, int $customerId) {}

    // /**
    //  * @param string $pin the new pin
    //  * Send this details with an authentication key created and registered in this function
    //  */
    // public function setupDevicePin(string $pin) {}

    // public function fetchCategories() {
    //     try{
    //          $categories = Category::where(['domain_id' => 1, 'status' => 'active'])
    //          ->whereIn('slug', ['airtime', 'data', 'tv-subscription', 'electricity-bill', 'education'])
    //         ->orderBy('cat_order', 'ASC')
    //         ->select('id', 'display_name AS name', 'slug', 'status')
    //         ->get();

    //         return $this->responseService->formatServiceResponse("success", "Fetched successfully", [], $categories);
    //     }catch( \Throwable $err ) {
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // /**
    //  * Fetch bills payment services
    //  */
    // public function fetchServiceProducts(int $id) {
    //     try{
    //         $products = $this->fetchCategoryProducts($id);
    //         return $this->responseService->formatServiceResponse("success", "Fetched successfully", [], $products);
    //     }catch( \Throwable $err ) {
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // public function fetchCategoryProducts(int $categoryId) {
    //     $products = Product::select(
    //         'id',
    //         'name',
    //         'slug',
    //         'serviceID',
    //         'image',
    //         'status',
    //         'suspend_message AS status_message',
    //         'category_id AS service_category_id',
    //         'product_type',
    //         'quantity_label',
    //         'is_quantity',
    //         'allow_verification'
    //     )->whereIn('status', ['active', 'in-suspend'])->where(['category_id' => $categoryId, 'domain_id' => 1])->get();

    //     // $platformSettings  = ParameterVassociate::where(['domain_id'=>\Request::get('domain_id')])->first();

    //     foreach ($products as  $product) {
    //         $getUniqueElement = DB::table('product_extras')->where(['product_id' => $product->id, 'unique' => 'Yes'])->orderBy('id', 'asc')->first();
    //         // $getDynamics = DB::table('product_extras')->where(['product_id' => $product->id, 'unique' => 'No'])->orderBy('id', 'asc')->select('name', 'description', 'type', 'options', 'ancestor_label', 'ancestor_description', 'is_required')->get();
    //         // $formDynamics = [];

    //         $product->image = 'https:' . env('BASE_URL') . 'resources/products/200X200/' . $product->image;
    //         $product->variation_label = "";
    //         $product->biller_identifier_name = "";

    //         $varitionsQuery = Variation::where(['product_id' => $product->id, 'status' => 'active']);
    //         $variationsQueryClone = clone $varitionsQuery;
    //         $variationsCount = $variationsQueryClone->count();

    //         if ($variationsCount > 0) {
    //             $variations = $varitionsQuery->orderBy('ordering', 'ASC')->get();

    //             foreach ($variations as $key => $value) {
    //                 $variation_name = $value->variation1;
    //                 $var_n['id'] = (int)$value->id;
    //                 $var_n['variation'] = $value->value1;
    //                 $var_n['amount'] = (float)$value->amount;
    //                 $var_n['identifier'] = $value->identifier;
    //                 $var_n['groupin'] = $value->groupin;
    //                 $var_n['product_id'] = $pid;
    //                 $var_n['validity'] = $value->validity;

    //                 $variation_array[] = $var_n;
    //             }

    //             $product->variations = $variation_array;
    //             $product->variation_label = $variation_name;
    //             // $formDynamics[] = $this->constrFormDynamics($variation_name, "variation_code", "select", "", $variation_array, true, false);
    //         }

    //         if (!empty($getUniqueElement)) {
    //             $product->biller_identifier_name = $getUniqueElement->name;
    //             // $formDynamics[] = $this->constrFormDynamics($getUniqueElement->name, "billersCode", "text", $description, [], true, false);
    //         }

    //         // if(in_array($product->id, [5, 11])) {
    //         //     $formDynamics[] = $this->constrFormDynamics("Subscription Type", "subscription_type", "select", "", ['renew' => "Renew", 'change' => "Change Bouquet"], true, false);
    //         // }

    //         // foreach($getDynamics as $getDynamic) {
    //         //     if($getDynamic->type == 'TextInput') {
    //         //         $formDynamics[] = $this->constrFormDynamics($getDynamic->description, $getDynamic->name, "text", "", [], ($getDynamic->is_required == TRUE), false);
    //         //     }elseif($getDynamic->type == 'MultipleSelect') {
    //         //         $formDynamics[] = $this->constrFormDynamics($getDynamic->description, $getDynamic->name, "multiple-select", "", [], ($getDynamic->is_required == TRUE), false);
    //         //     }elseif($getDynamic->type == 'DateField') {
    //         //         $formDynamics[] = $this->constrFormDynamics($getDynamic->description, $getDynamic->name, "date", "", [], ($getDynamic->is_required == TRUE), false);
    //         //     }elseif($getDynamic->type == 'Select' && checkGenerations($getDynamic->options)=='orphan') {
    //         //         $formDynamics[] = $this->constrFormDynamics($getDynamic->description, $getDynamic->name, "select", "", [], ($getDynamic->is_required == TRUE), false);
    //         //     }
    //         // }

    //         // $formDynamics[] = $this->constrFormDynamics('Phone Number', 'phone', "text", "", [], true, false);

    //         // $formDynamics[] = $this->constrFormDynamics('Email Address', 'email', "text", "", [], false, false);

    //         // $formDynamics[] = $this->constrFormDynamics('Amount', 'amount', "text", "", [], true, false);

    //         // if ($product->is_quantity == 1) {
    //         //     $formDynamics[] = $this->constrFormDynamics(!empty($product->quantity_label) ? $product->quantity_label : 'Quantity', 'quantity', "number", "", [], false, false);
    //         // }

    //         // if($platformSettings->is_voucher == 1) {
    //         //     $formDynamics[] = $this->constrFormDynamics('Discount Voucher', 'voucher', "text", "", [], false, false);
    //         // }

    //         // $product->formDynamics = $formDynamics;
    //     }

    //     return $products;
    // }

    // public function constrFormDynamics(string $label, string $name, string $type, string $description=null, array $options, bool $isRequired, bool $isDependent) {
    //     return [
    //                 "label" => $label,
    //                 "name" => $name,
    //                 "description" => $description,
    //                 "type" => $type,
    //                 "options" => $options,
    //                 "required" => $isRequired,
    //                 "isDependent" => $isDependent
    //             ];
    // }

    // /**
    //  * Verify merchant
    //  */
    // public function verifyMerchant(string $serviceID, string $billersCode, string $type=null) {
    //     try{
    //         if($serviceID == "smile-direct") {
    //             if(filter_var($billersCode, FILTER_VALIDATE_EMAIL)) {
    //                 $merchantVerify = app('App\Http\Controllers\Api\SmileDataController');
    //                 $x_view = $merchantVerify->validateEmailAPI($billersCode);

    //                 if (!empty($x_view) && isset($x_view['code'])) {
    //                     if ($x_view['code'] == 1) {
    //                         return $this->responseService->formatServiceResponse("success", "Fetched successfully", [], $x_view['data']);
    //                     } else {
    //                         return $this->responseService->formatServiceResponse("failed", $x_view['data'], [], null);
    //                     }
    //                 } else {
    //                     return $this->responseService->formatServiceResponse("failed", 'BILLER NOT REACHEABLE AT THIS POINT', [], null);
    //                 }
    //             }else{
    //                 $merchantVerify = app('App\Http\Controllers\Api\SmileDataController');
    //                 $x_view = $merchantVerify->validatePhone($billersCode);

    //                 if (!empty($x_view) && isset($x_view['code'])) {
    //                     if ($x_view['code'] == 1) {
    //                         return $this->responseService->formatServiceResponse("success", "Fetched successfully", [], $x_view['data']);
    //                     } else {
    //                         return $this->responseService->formatServiceResponse("failed", $x_view['data'], [], null);
    //                     }
    //                 } else {
    //                     return $this->responseService->formatServiceResponse("failed", 'BILLER NOT REACHEABLE AT THIS POINT', [], null);
    //                 }
    //             }
    //         }else{
    //             $merchantVerify = app('App\Http\Controllers\Api\MerchantVerify');
    //             $x_view = $merchantVerify->verify($serviceID, $billersCode, $type);

    //             if (!empty($x_view) && isset($x_view['code'])) {
    //                 if ($x_view['code'] == 1) {
    //                     return $this->responseService->formatServiceResponse("success", "Fetched successfully", [], $x_view['data']);
    //                 }else{
    //                     return $this->responseService->formatServiceResponse("failed", $x_view['data'], [], null);
    //                 }
    //             }else {
    //                 return $this->responseService->formatServiceResponse("failed", 'BILLER NOT REACHEABLE AT THIS POINT', [], null);
    //             }
    //         }
    //     }catch(\Throwable $err) {
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // /**
    //  * Complete bills payment transaction and checks for auth keys
    //  */
    // public function completeBillspaymentTransaction(array $payload) {
    //     try{
    //         if(!isset($payload['auth_key'])) {
    //             return $this->responseService->formatServiceResponse("failed", 'Invalid Authentication mode', [], null);
    //         }
    //         //validate
    //         $isValid = $this->validateAuthKey($payload['auth_key']);

    //         if(!$isValid['status']) {
    //             return $this->responseService->formatServiceResponse("failed", 'Invalid Authentication mode', [], null);
    //         }

    //         //check if request already exist
    //         $exist = $this->existPosWebServiceRequest($payload);

    //         if($exist) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Request ID already exist', [], null);
    //             return $response;
    //         }

    //         try{
    //             $posWebServiceRequest = $this->logPosWebServiceRequest($payload);
    //         }catch(\Throwable $err) {
    //             $errorMessage = env("ENT") == "live" ? "Likely Duplicate" : $err->getMessage();
    //             return $this->responseService->formatServiceResponse("failed", $errorMessage, [], null);
    //         }

    //         //validate entry
    //         $productDets = Product::where(['domain_id' => 1, 'serviceID' => $payload['serviceID']])->first();
    //         if(empty($productDets)) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Invalid Product', [], null);
    //             $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //             return $response;
    //         }

    //         $request = new \Illuminate\Http\Request($payload);

    //         $product_amount = $request->amount;

    //         $getUniqueElement = DB::table('product_extras')->where(['product_id' => $productDets->id])->orderBy('id', 'asc')->select('name')->first();
    //         if (!empty($getUniqueElement)) {
    //             $productArray['unique_element'] = $request->billersCode;
    //             if (!in_array($productDets->id, [42])) {
    //                 $request[$getUniqueElement->name] = $request->billersCode;
    //             }
    //         } else {
    //             $productArray['unique_element'] = $request['phone'];
    //         }

    //         $customer = Customer::find($request['customer_id']);
    //         if(empty($customer)) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Customer does not exist', [], null);
    //             $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //             return $response;
    //         }

    //         // $amount = floor($data['amount'] * 100) / 100;
    //         $expectedDebitAmount = floor(((float)$request['total_amount']) * 100) / 100;
    //         $actualDebitAmount = floor(((float)$request['total_debit_amount']) * 100) / 100;

    //         if($expectedDebitAmount != $actualDebitAmount) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Failed wallet debit', [], null);
    //             $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //             return $response;
    //         }

    //         // $requeryPosTerminalWalletAction = $this->requeryWalletAction($request->request_id,"debit",$expectedDebitAmount);

    //         // if(isset($requeryPosTerminalWalletAction['status']) && $requeryPosTerminalWalletAction['status'] != "success") {
    //         //     $response = $this->responseService->formatServiceResponse("failed", 'Failed wallet debit', [], null);
    //         //     $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //         //     return $response;
    //         // }

    //         $checkTime = Order::where(['unique_element' => $productArray['unique_element']])->orderBy('created_at', 'DESC')->first();
    //         if ($checkTime) {
    //             $datetime1 = time();
    //             $datetime2 = strtotime($checkTime->created_at);
    //             $timeDifference = $datetime1 - $datetime2;
    //             if ($timeDifference < 15) {
    //                 $response = $this->responseService->formatServiceResponse("failed", 'Seems like duplicate transacton. allow a space of 15 seconds before using the same recepient', [], null);
    //                 $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //                 return $response;
    //             }
    //         }

    //         //inits state
    //         $request['domain_id'] = 11;
    //         $request['serviceID'] = $productDets->serviceID;
    //         $transArray['domain_id']  = $request['domain_id'];
    //         $transArray['status']  = 'initiated';
    //         $transArray['channel']  = 'pos';
    //         $transArray['transactionId'] = (microtime(true) * 10000) . rand(100000, 999999) . rand(100000, 999999);
    //         $transArray['external_requestId'] = $request->request_id;
    //         $transArray['method']  = 'pos-wallet';
    //         $transArray['platform'] = 'pos';
    //         $transArray['is_api']  = 0;
    //         $transArray['discount'] =  NULL;
    //         $transArray['customer_id'] =  $customer->id;
    //         $transArray['email'] = $customer->user->email;
    //         $transArray['phone'] = $customer->user->phone;
    //         $transArray['type']  = $productDets->category->name;
    //         $transArray['amount'] =  $request->amount;
    //         $transArray['convinience_fee']  = $request->convinience_fee;
    //         $transArray['total_amount'] =   $request->amount - $request->commission + $request->convinience_fee;
    //         $transArray['quantity']  = $request->quantity;
    //         $transArray['unit_price']  = $request->unit_price;

    //         // CALCULATE AND ADD UP SERVICE COMMISSION TO TRANSACTION_ALL
    //         $servcomm = app('App\Http\Controllers\Controller')->getServiceCommission($productDets, $request->amount, ($request->special_commission_key ?? ""));
    //         $transArray['service_commission'] = ($customer->special_agent == 0) ? $servcomm : 0;

    //         $transArray['ip']  = $request['ip'];
    //         $transArray['unique_element'] = $productArray['unique_element'];
    //         $transArray['product_name'] = $productDets->name;
    //         $transArray['product_id'] = $productDets->id;

    //         $createGeneralTransaction = app('App\Http\Controllers\AllTransaction')->create($transArray, rand(1, 1000));

    //         $removeKeys = ['ip', 'unique_element', 'product_name', 'product_id', 'app_version'];
    //         foreach ($removeKeys as $key) {
    //             unset($transArray[$key]);
    //         }
    //         $filteredTransArr = app('App\Http\Controllers\AllTransaction')->filterTransactionArr($transArray);
    //         CreateTransactionJob::dispatch($filteredTransArr);

    //         if ($request['serviceID'] == 'bank-deposit') {
    //             $bank['beneficiary_code'] = $request['variation_code'];
    //             $bank['beneficiary_bank'] = $request['variation_code'];
    //             $bank['beneficiary_account_number'] = $request['billersCode'];
    //             $bank['beneficiary_phone_number'] = $request['phone'];
    //             $bank['beneficiary_email'] = $customer->user->email;
    //             $bank['amount'] = $request['amount'];

    //             $van = app('App\Http\Controllers\Api\MerchantVerify')->verify($request['serviceID'], $request['billersCode'], $request['variation_code']);
    //             if (isset($van['accountName'])) {
    //                 $bank['beneficiary_name'] = $van['accountName'];
    //             } else {
    //                 $bank['beneficiary_name'] = "Unverified NUBAN";
    //             }

    //             $bank['sender_name'] = $customer->user->name;
    //             $bank['email'] = $customer->user->email;
    //             $bank['sender_phone_number'] = $request['phone'];
    //             $bank['status'] = 'initiated';
    //             $bank['remarks'] = 'Transfer to ' . $request['billersCode'] . ' ' . ($request['domain_url']??"");
    //             $bank['transaction_id'] = null;
    //             $bank['transaction_all_id'] = $createGeneralTransaction->id;
    //             $bank['transactionId'] = $transArray['transactionId'];
    //             BankDeposit::create($bank);
    //         }

    //         $this->updatePosWebServiceRequest($posWebServiceRequest, ["transaction_all_id" => $createGeneralTransaction->id, "transactionId" => $createGeneralTransaction->transactionId]);

    //         $productArray['domain_id'] = $request['domain_id'];
    //         $productArray['product_id'] = $productDets->id;
    //         $productArray['transaction_id'] = null;
    //         $productArray['customer_id'] = $customer->id;
    //         $productArray['amount'] = $product_amount;
    //         $productArray['variation_id'] = (!empty($request->var_idx)) ? $request->var_idx : NULL;
    //         $productArray['status'] = 'pending';
    //         $productArray['recepient'] = $request->phone;
    //         $productArray['amount'] = $request->unit_price;
    //         $productArray['quantity'] = $request->quantity;
    //         $request->amount = $request->unit_price;
    //         $request['email'] = $customer->user->email;

    //         $productArray['j_data'] = json_encode($request->except('_token', 'domain_url', 'domain_short_name', 'domain_absolute_url'));

    //         $productArray['transaction_all_id'] = $createGeneralTransaction->id;
    //         $productArray['transactionId'] = $transArray['transactionId'];
    //         $orderDets = Order::create($productArray);
    //         $data['code'] = '1';

    //         $orderController = new OrderController;

    //         $transArray['status'] = "completed";

    //         $createGeneralTransaction = app('App\Http\Controllers\AllTransaction')->update($transArray['transactionId'],['status' => 'completed']);
    //         $filteredTransArr = app('App\Http\Controllers\AllTransaction')->filterTransactionArr($transArray);
    //         CreateTransactionJob::dispatch($filteredTransArr);

    //         $orderResponse = $orderController->orderProcessing($transArray['transactionId'],'pos-wallet', null);

    //         //get Success Email
    //         $emailMessage = ServiceMessage::where(['category_id'=>$productDets->category->id,'status' =>'success','type'=>'email'])->first()->message;

    //         // Log Action
    //         $log_action = 'transaction';
    //         $log_description = 'Customer Transaction was for ' . $productDets->name . ' was ' . $transArray['status'];
    //         $userConditionController = new UserConditionController();
    //         $userConditionController->customeLog($log_action, $log_description, $transArray['amount'], $transArray['status'], $transArray['transactionId']);

    //         $newMessage = ucwords(str_replace(
    //             array("[transactionId]", "[product]", "[number]", "[amount]", "[unique-element]"),
    //             array($transArray['transactionId'], $transArray[$productDets->name], $transArray['phone'], $transArray['total_amount'], $transArray['unique_element']),
    //             $emailMessage
    //         ));
    //         $btn = app('App\Http\Controllers\ApiMessagesController')->receiptBTN($transArray['transactionId']);
    //         $newMessage = $newMessage . $btn;

    //         $dets['message'] = $newMessage;
    //         $dets['transID'] = $createGeneralTransaction->id;
    //         // <<==================<< I STARTED FROM HERE >>=================>>
    //         // FOR EMAIL
    //         $messageController = app('App\Http\Controllers\MessagesTransactionController');

    //         $x_products = explode(',', env('ELECTRICITY_IDS'));
    //         $x_productsX = explode(',', env('PIN_PRODUCTS'));

    //         if ($transArray['channel'] != "api" && !in_array($transArray['product_id'], $x_products) && !in_array($transArray['product_id'], $x_productsX) && ($transArray['product_id'] != env('FOREIGN_AIRTIME_ID'))) {
    //             // if(empty($user)){
    //             $messageController->sendEmailOrSms($transArray['email'], $newMessage, 'email', null, 'Transaction Notification [' . $transArray[$productDets->name] . ']', null, null, $transArray['transactionId']);
    //             // }
    //         }

    //         $transactionDets = TransactionAll::where(['transactionId' => $transArray['transactionId']])->select(['status', 'product_name', 'unique_element', 'unit_price', 'quantity', 'service_verification', 'channel', 'commission', 'total_amount', 'discount', 'type', 'email', 'phone', 'name', 'convinience_fee', 'amount', 'platform', 'method', 'transactionId'])->first();

    //         $response = $this->responseService->formatServiceResponse("success", 'Transaction processed', [], $transactionDets);
    //         $this->updatePosWebServiceRequest($posWebServiceRequest, ["response" => json_encode($response)]);
    //         return $response;
    //     }catch(\Throwable $err) {
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // public function validatePurchaseInputs(array $payload) {
    //     try{
    //         $toValidate = array(
    //             'serviceID'   => $payload['serviceID'] ?? null,
    //             'phone'        => $payload['phone'] ?? null,
    //             'request_id'   => $payload['request_id'] ?? null,
    //             'customer_id'   => $payload['customer_id'] ?? null,
    //         );

    //         $validator = Validator::make($toValidate, [
    //             'serviceID' => "required|string",
    //             'phone' => "required|string",
    //             'request_id' => "required|string",
    //             'customer_id' => "required|integer",
    //         ]);

    //         if($validator->fails()) {
    //             return $this->responseService->formatServiceResponse("error", '', $validator->errors()->all(), null);
    //         }

    //         $special_commission_key = '';

    //         //validate entry
    //         $productDets = Product::where(['domain_id' => 1, 'serviceID' => $payload['serviceID']])->first();
    //         if(empty($productDets)) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Invalid Product', [], null);
    //             return $response;
    //         }

    //         if ($productDets->status == 'suspend') {
    //             $response = $this->responseService->formatServiceResponse("failed", $productDets->suspend_message, [], null);
    //             return $response;
    //         }
    //         if ($productDets->status == 'in-active') {
    //             $response = $this->responseService->formatServiceResponse("failed", $productDets->name . ' is In-Active', [], null);
    //             return $response;
    //         }

    //         $request = new \Illuminate\Http\Request($payload);
    //         if ($productDets->parent_product_id == env("FOREIGN_AIRTIME_ID")) {
    //             $foreignAirtimeRes = app("App\Http\Controllers\ForeignAirtimeController")->processForeignAirtime($request, $productDets->parent_product_id, true);

    //             if ($foreignAirtimeRes['code'] == "01") {
    //                 $response = $this->responseService->formatServiceResponse("failed", $foreignAirtimeRes['message'], [], null);
    //                 return $response;
    //             }

    //             if ($foreignAirtimeRes['code'] == "02") {
    //                 $response = $this->responseService->formatServiceResponse("failed", $foreignAirtimeRes['message'], [], null);
    //                 return $response;
    //             }

    //             $request = $foreignAirtimeRes['request'];
    //         }

    //         $variation_count = Variation::where(['product_id' => $productDets->id, 'status' => 'active'])->count();
    //         if ($variation_count > 0) {
    //             if (
    //                 (in_array($productDets->id, [5, 11]) && isset($request->subscription_type) && $request->subscription_type == "renew")
    //                 || (in_array($productDets->id, [5, 11]) && (!isset($request->subscription_type) || empty($request->subscription_type)) && (!isset($request->variation_code) || empty($request->variation_code)))
    //             ) {
    //                 $request['variation_code'] = "";
    //                 if (!empty($request->subscription_type)) {
    //                 } else {
    //                     $request['subscription_type'] = "renew";
    //                 }

    //                 $product_amount = $request->amount;
    //             }else{
    //                 $validator = Validator::make($request->all(), [
    //                     'variation_code' => "required|string"
    //                 ]);

    //                 if($validator->fails()) {
    //                     $response = $this->responseService->formatServiceResponse("error", '', ['variation is needed for this service'], null);
    //                     return $response;
    //                 }

    //                 $getVariation = Variation::where(['identifier' => $request->variation_code, 'product_id' => $productDets->id, 'status' => 'active'])->first();
    //                 if (!$getVariation) {
    //                     $response = $this->responseService->formatServiceResponse("error", '', ["Variation code does not exist"], null);
    //                     return $response;
    //                 }
    //                 $request['var_idx'] = $getVariation->id;
    //                 $request['identifier'] = $getVariation->identifier;
    //                 $request['subscription_type'] = "change";

    //                 if ($getVariation->amount > 1) {
    //                     // VARIATION PRICE CHECK
    //                     if ($productDets->allow_variation_amount_edit == '1') {
    //                         if (empty($request->amount) || !isset($request->amount)) {
    //                             $request['amount'] = $getVariation->amount;
    //                         }

    //                         $validator = Validator::make($request->all(), [
    //                             'amount' => "required"
    //                         ]);

    //                         if($validator->fails()) {
    //                             $response = $this->responseService->formatServiceResponse("error", '', ['amount is needed for this service'], null);
    //                             return $response;
    //                         }
    //                     } else {
    //                         if (empty($request->amount) || !isset($request->amount)) {
    //                             $request['amount'] = $getVariation->amount;
    //                         } else {
    //                             $request['amount'] = $request->amount;
    //                         }
    //                     }
    //                 }else {
    //                     $validator = Validator::make($request->all(), [
    //                         'amount' => "required"
    //                     ]);

    //                     if($validator->fails()) {
    //                         $response = $this->responseService->formatServiceResponse("error", '', ['amount is needed for this service'], null);
    //                         return $response;
    //                     }
    //                     $request['amount'] = $request->amount;
    //                     $product_amount = $request->amount;
    //                 }
    //             }
    //         }else{
    //             $validator = Validator::make($request->all(), [
    //                             'amount' => "required"
    //                         ]);

    //             if($validator->fails()) {
    //                 $response = $this->responseService->formatServiceResponse("error", '', ['amount is needed for this service'], null);
    //                 return $response;
    //             }

    //             $request['amount'] = $request->amount;
    //             $product_amount = $request->amount;
    //         }

    //         if (in_array($productDets->id, [5, 11])) {
    //             if (isset($request->subscription_type) && $request->subscription_type == "renew" && (!isset($request->amount) || empty($request->amount))) {
    //                 $subscriptionTypeDetails = app("App\Http\Controllers\CpMultichoiceController")->intellisenseSubscription($request->billersCode, $productDets->serviceID);

    //                 if (!empty($subscriptionTypeDetails) && isset($subscriptionTypeDetails['type'])) {
    //                     if ($subscriptionTypeDetails['type'] == "renew") {
    //                         $request['amount'] = $request->amount;
    //                     } else {
    //                         $request['amount'] = 0;
    //                     }
    //                 } else {
    //                     $request['amount'] = 0;
    //                 }
    //             } elseif (isset($request->subscription_type) && in_array(strtolower($request->subscription_type), ['renew', 'change'])) {
    //             } elseif (!isset($request->subscription_type) && (!isset($request->identifier) || (isset($request->identifier) && empty($request->identifier)))) {
    //                 $request['subscription_type'] = "renew";
    //             } else {
    //                 $variationType = VariationProvider::where(["product_id" => $productDets->id, "api_id" => '62', "code" => $request->identifier])->first();
    //                 if ((!isset($request->subscription_type) || empty($request->subscription_type)) && isset($request->billersCode) && !empty($variationType)) {
    //                     $subscriptionTypeDetails = app("App\Http\Controllers\CpMultichoiceController")->intellisenseSubscription($request->billersCode, $productDets->serviceID, $variationType->p_code);

    //                     if (!empty($subscriptionTypeDetails) && isset($subscriptionTypeDetails['type'])) {
    //                         $request['subscription_type'] = $subscriptionTypeDetails['type'];
    //                     } else {}
    //                 }
    //             }
    //         }

    //         $validator = Validator::make($request->all(), [
    //                             'amount' => "required"
    //                         ]);

    //         if($validator->fails()) {
    //             $response = $this->responseService->formatServiceResponse("error", '', ['amount is needed for this service'], null);
    //             return $response;
    //         }

    //         // CLEAN UP AMOUNT FIGURE
    //         $request['amount'] = app('App\Http\Controllers\BillersRuleController')->removeCharsInAmount($request->amount);
    //         // CLEAN UP AMOUNT FIGURE

    //         if ((!empty($productDets->min)) && (!empty($productDets->max))) {
    //             if (($request->amount < $productDets->min)) {
    //                 $response = $this->responseService->formatServiceResponse("failed", 'Below minimum amount allowed', [], null);
    //                 return $response;
    //             } elseif (($request->amount > $productDets->max)) {
    //                 $response = $this->responseService->formatServiceResponse("failed", 'Above maximum amount allowed', [], null);
    //                 return $response;
    //             }
    //         }elseif (!empty($productDets->api_min) && empty($productDets->api_max)) {
    //             if (($request->amount < $productDets->api_min)) {
    //                 $response = $this->responseService->formatServiceResponse("failed", 'Below minimum amount allowed', [], null);
    //                 return $response;
    //             }
    //         }

    //         $getUniqueElement = DB::table('product_extras')->where(['product_id' => $productDets->id])->orderBy('id', 'asc')->select('name')->first();
    //         if (!empty($getUniqueElement)) {
    //             // check for billersCode
    //             $validator = Validator::make($request->all(), [
    //                             'billersCode' => "required|string"
    //                         ]);

    //             if($validator->fails()) {
    //                 $response = $this->responseService->formatServiceResponse("error", '', ['billersCode is empty'], null);
    //                 return $response;
    //             }
    //             $productArray['unique_element'] = $request->billersCode;
    //             if (!in_array($productDets->id, [42])) {
    //                 $request[$getUniqueElement->name] = $request->billersCode;
    //             }
    //         } else {
    //             $productArray['unique_element'] = $request->billersCode;
    //             $request['phone'] = $request->billersCode;
    //         }

    //         if (in_array($productDets->category_id, [7])) {
    //             if($productDets->serviceID == 'ikeja-electric') {
    //                 $MerchantVerify = app('App\Http\Controllers\Api\MerchantVerify');
    //                 $verify = $MerchantVerify->verify($productDets->serviceID,$productArray['unique_element'],$request['identifier'],$request['amount']);
    //                 if (!empty($verify)) {
    //                     $verify = (array)$verify;
    //                     if ($verify['code'] == '1') {
    //                         if(!empty($verify['data']['Customer_Account_Type']) && in_array($verify['data']['Customer_Account_Type'], ["NMD","MD"])) {
    //                             $special_commission_key = strtolower($verify['data']['Customer_Account_Type']);
    //                         }
    //                     }else{
    //                         $vArr = [
    //                             'prepaid'   => 'prepaid',
    //                             'postpaid'  => 'postpaid'
    //                         ];
    //                         $former = $request['identifier'];
    //                         unset($vArr[$request['identifier']]);
    //                         foreach($vArr as $v){
    //                             $request['identifier'] = $v;
    //                         }
    //                         //=========== REFACTOR METER TYPE ===========//
    //                         $MerchantVerify = app('App\Http\Controllers\Api\MerchantVerify');
    //                         $verify = $MerchantVerify->verify($productDets->serviceID,$productArray['unique_element'],$request['identifier'],$request['amount']);

    //                         if (!empty($verify)) {
    //                             if (($verify['code'] == '1')) {
    //                                 if(!empty($verify['data']['Customer_Account_Type']) && in_array($verify['data']['Customer_Account_Type'], ["NMD","MD"])) {
    //                                     $special_commission_key = strtolower($verify['data']['Customer_Account_Type']);
    //                                 }
    //                             }else{
    //                                 $request['identifier'] = $former;
    //                             }
    //                         }else{
    //                             $request['identifier'] = $former;
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         $request['special_commission_key'] = $special_commission_key;

    //         if ($productDets->is_quantity == 1) {
    //             //check Min and Max Quantity
    //             if (!empty($request->quantity)) {
    //                 if ((!empty($productDets->min_quantity)) && (!empty($productDets->max_quantity))) {
    //                     if (($request->quantity < $productDets->min_quantity)) {
    //                         $response = $this->responseService->formatServiceResponse("failed", 'Below minimum quantity allowed', [], null);
    //                         return $response;
    //                     } elseif (($request->quantity > $productDets->max_quantity)) {
    //                         $response = $this->responseService->formatServiceResponse("failed", 'Above minimum quantity allowed', [], null);
    //                         return $response;
    //                     }
    //                 } elseif ((!empty($productDets->min_quantity)) && (empty($productDets->max_quantity))) {
    //                     if (($request->quantity < $productDets->min_quantity)) {
    //                         $response = $this->responseService->formatServiceResponse("failed", 'Below minimum quantity allowed', [], null);
    //                         return $response;
    //                     }
    //                 } elseif ((empty($productDets->min_quantity)) && (!empty($productDets->max_quantity))) {
    //                     if (($request->quantity > $productDets->max_quantity)) {
    //                         $response = $this->responseService->formatServiceResponse("failed", 'Above minimum quantity allowed', [], null);
    //                         return $response;
    //                     }
    //                 }
    //                 $request['unit_price'] = $request->amount;
    //                 $request['amount'] = $request->amount * $request->quantity;
    //             } else {
    //                 $request['unit_price'] = $request->amount;
    //                 $request['quantity'] = 1;
    //             }
    //         } else {
    //             $request['unit_price'] = $request->amount;
    //             $request['quantity'] = 1;
    //         }

    //         $customer = Customer::find($request['customer_id']);
    //         if(empty($customer)) {
    //             $response = $this->responseService->formatServiceResponse("failed", 'Customer does not exist', [], null);
    //             return $response;
    //         }

    //         $request['convinience_fee']  = (!empty($productDets->convinience_fee)) ? $productDets->convinience_fee : 0;

    //         // check convinience fee
    //         $IndividualConvFee = ConvinienceFee::where(['product_id' => $productDets->id, 'medium' => 'pos', 'for' => 'individual', 'customer_id' => $customer->id, 'status' => 'active'])->first();

    //         //check General Product Convinience Fee
    //         $generalConvFee = ConvinienceFee::where(['product_id' => $productDets->id, 'medium' => 'pos', 'for' => 'general', 'status' => 'active'])->first();
    //         if (($IndividualConvFee)) {
    //             if ($IndividualConvFee->type == 'flat') {
    //                 $request['convinience_fee'] =  $IndividualConvFee->amount;
    //             } else {
    //                 $request['convinience_fee'] = (($IndividualConvFee->amount / 100) * $request->amount);
    //             }
    //         } elseif (($generalConvFee)) {
    //             if ($generalConvFee->type == 'flat') {
    //                 $request['convinience_fee'] = $generalConvFee->amount;
    //             } else {
    //                 $request['convinience_fee'] = (($generalConvFee->amount / 100) * $request->amount);
    //             }
    //         } else {
    //             if (!empty($productDets->convinience_fee)) {
    //                 if ($productDets->convinience_type == 'flat') { //flat rate
    //                     $request['convinience_fee'] = $productDets->convinience_fee;
    //                 } elseif ($productDets->convinience_type == 'percentage') { //percentage rate
    //                     $request['convinience_fee'] = (($productDets->convinience_fee / 100) * $request->amount);
    //                 } else { // no commission
    //                     $request['convinience_fee'] = $productDets->convinience_fee;
    //                 }
    //             } else {
    //                 $request['convinience_fee'] = 0;
    //             }
    //         }

    //         $request['total_amount'] = $request['amount'] + $request['convinience_fee'];

    //         $response = $this->responseService->formatServiceResponse("success", 'Validated successfully', [], $request->all());
    //         return $response;
    //     }catch(\Throwable $err) {
    //         Log::error($err);
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // public function extendAutoWalletFunding(int $reservedAccountCallbackId, int $terminalId, int $customerId, float $amount, float $totalAmount, string $accountNo, float $processingFee) {
    //     $data = [
    //         "auth_key" => $this->generateAuthKey("auto-wallet-funding", $customerId),
    //         "reserved_account_callback_id" => $reservedAccountCallbackId,
    //         "customer_id" => $customerId,
    //         "terminal_id" => $terminalId,
    //         "amount" => $amount,
    //         "total_amount" => $totalAmount,
    //         "account_number" => $accountNo,
    //         "processing_fee" => $processingFee
    //     ];

    //     Log::info(json_encode($data));
    // }

    // public function generateAuthKey(string $type, int $customerId) {
    //     $authKey = $this->constrRandStr(12).rand(11111111,99999999);

    //     WebServiceAuthKey::create([
    //         "key" => $authKey,
    //         "type" => $type,
    //         "customer_id" => $customerId,
    //         "expiry_date" => date("Y-m-d H:i:s", \strtotime("+10 mins")),
    //         "domain_id" => \Request::get('domain_id')
    //     ]);

    //     return $authKey;
    // }

    // public function validateInternalAuthKey(string $key, string $type, int $customerId) {
    //     $exist = WebServiceAuthKey::where([
    //         "key" => $key,
    //         "type" => $type,
    //         "customer_id" => $customerId,
    //     ])->where("expiry_date", "<=", date("Y-m-d H:i:s"))->first();

    //     if(!empty($exist)) {
    //         return true;
    //     }

    //     return false;
    // }

    // public function validateAuthKey(string $authKey) {
    //     //Query POS Web service
    //     $response = $this->doCurl(url: "/api/auth/validate_key", method:"POST", payload: ["key" => $authKey]);

    //     $response = json_decode($response, true);
    //     if(isset($response['status']) && $response['status'] == "1000" && isset($response['data'])) {
    //         if($response['data']['type'] == "bills_payment" && (date("Y-m-d H:i:s", \strtotime($response['data']['expriry_date'])) > date("Y-m-d H:i:s", \strtotime("-1 hours")))) {
    //             return ['status' => true];
    //             // return ['status' => true, "total_amount_debited" => "600"];
    //         }
    //     }

    //     return ['status' => false];
    // }

    // public function logPosWebServiceRequest($payload) {
    //     $customerId = $payload['customer_id'];
    //     $requestId = $payload['request_id'];

    //     $posWebServiceRequest = PosWebServiceRequest::create([
    //         "request_id" => $requestId,
    //         "customer_id" => $customerId,
    //         "payload" => json_encode($payload),
    //         "duplicate_check" => $customerId.$requestId
    //     ]);

    //     return $posWebServiceRequest;
    // }

    // public function existPosWebServiceRequest($payload) {
    //     $requestId = $payload['request_id'];

    //     $from = date("Y-m-d")." 00:00:00";
    //     $to = date("Y-m-d")." 23:59:59";

    //     $posWebServiceRequest = PosWebServiceRequest::where([
    //         "request_id" => $requestId
    //     ])->whereBetween('created_at', [$from, $to])->first();

    //     return !empty($posWebServiceRequest) ? true : false;
    // }

    // public function updatePosWebServiceRequest(PosWebServiceRequest $posWebServiceRequest, array $updates) {
    //     PosWebServiceRequest::where(['id' => $posWebServiceRequest->id])->update($updates);
    // }

    // /**
    //  * Callback for transaction resolution which will be sent to POS web service
    //  */
    // public function callbackForTransactionResolution(Request $request) {}

    // /**
    //  * Requery transaction from POS web service
    //  */
    // public function requeryTransaction(array $payload) {
    //     $validator = Validator::make($payload, [
    //         'request_id' => "required|string",
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->responseService->formatServiceResponse("error", '', $validator->errors()->all(), null);
    //     }

    //     try {
    //         $request_id = $payload['request_id'];
    //         $year = substr($request_id, 0, 4);
    //         $month = substr($request_id, 4, 2);
    //         $day = substr($request_id, 6,2);

    //         $from = $year . "-" . $month . "-" . $day . " 00:00:00";
    //         $to = $year."-".$month."-".$day." 23:59:59";
    //         $from = Carbon::parse($from);
    //         $to = Carbon::parse($to);

    //         $pos_web_service_request = PosWebServiceRequest::whereBetween('created_at', [$from, $to])->where('request_id', $request_id)->first();

    //         if (empty($pos_web_service_request->transactionId)) {
    //             return $this->responseService->formatServiceResponse("failed", "request_id: ".$request_id." not found", [], null);
    //         }

    //         //==========[[ ERROR TRANSACTIONS RESPONSE ]]===========//
    //         if (empty($pos_web_service_request->transactionId)) {
    //             return $this->responseService->formatServiceResponse("failed", "Transaction not found for request_id: ".$request_id, [], null);
    //         }
    //         //==========[[ ERROR TRANSACTIONS RESPONSE ]]===========//

    //         $year = app('App\Http\Controllers\PullArchiveController')->getYearByTransId($pos_web_service_request->transactionId);

    //         if (empty($year)) {
    //             $transactionDets = TransactionAll::where(['transactionId' => $pos_web_service_request->transactionId])->select(['status', 'product_name', 'unique_element', 'unit_price', 'quantity', 'service_verification', 'channel', 'commission', 'total_amount', 'discount', 'type', 'email', 'phone', 'name', 'convinience_fee', 'amount', 'platform', 'method', 'transactionId'])->first();
    //         } else {
    //             $actions = [];
    //             $actions['where'][] = ['transactionId' => $pos_web_service_request->transactionId];
    //             $actions['select'] = ['status', 'product_name', 'unique_element', 'unit_price', 'quantity', 'service_verification', 'channel', 'commission', 'total_amount', 'discount', 'type', 'email', 'phone', 'name', 'extras', 'receipt_extras', 'convinience_fee', 'amount', 'platform', 'method', 'transactionId', 'product_id'];
    //             $actions['first'] = null;

    //             $transactionDets = app("App\Http\Controllers\TransactionAllCentralController")->filterArchivedTransaction($year, $actions);
    //         }

    //         $response = $this->responseService->formatServiceResponse("success", 'Requery processed', [], $transactionDets);
        
    //         return $response;
    //     } catch (\Throwable $err) {
    //         $errorMessage = env("ENT") == "live" ? "VTpass Internal Server Exception" : $err->getMessage();
    //         return $this->responseService->formatServiceResponse("unknown", $errorMessage, [], null);
    //     }
    // }

    // /**
    //  * Verify wallet action on POS web service
    //  */
    // public function requeryWalletAction(string $transactionId, string $action, float $amount) {
    //     //Query POS Web service
    //     $response = [
    //         "status" => "success",
    //         "data" => [
    //             "amount" => (float)500,
    //             "type" => "debit"
    //         ]
    //     ];

    //     if(!isset($response['status']) || !isset($response['data']) || !isset($response['data']['amount'])) {
    //         return [
    //             "status" => "failed",
    //             "raw" => json_encode($response)
    //         ];
    //     }

    //     if($response['status'] && $response['status'] != "success") {
    //         return [
    //             "status" => "failed",
    //             "raw" => json_encode($response)
    //         ];
    //     }

    //     $inAmount = floor(((float)$response['data']['amount']) * 100) / 100;
    //     if($inAmount < $amount) {
    //         return [
    //             "status" => "pending",
    //             "raw" => json_encode($response)
    //         ];
    //     }

    //     return [
    //         "status" => "success",
    //         "raw" => json_encode($response)
    //     ];
    // }

    // /**
    //  * Fetch wallet balance
    //  */
    // public function fetchPosWalletBalance() {}

    // /**
    //  * This function is called from the POS web service and checks for auth keys
    //  */
    // public function changeDevicePin() {}

    // /**
    //  * Verify commission given
    //  */
    // public function requeryCommissionGiven() {}

    // /**
    //  * Send request to POS web service to check and give out commission
    //  */
    // public function commissionTranscheck() {}

    // public function createAuthKey($customer, $type, $domain_id, $reference = null)
    // {
    //     try {
    //         //code...
    //         $time = Carbon::now()->addMinutes(5);
    //         $key = $this->randomStringGenerator(64, lower: false);
    //         $create = PosCallbackAuthKeys::create([
    //             'customer_id' => $customer,
    //             'type' => $type,
    //             'status' => 'pending',
    //             'expired_at' => $time,
    //             'key' => $key,
    //             'reference' => $reference,
    //             'domain_id' => $domain_id
    //         ]);

    //         if ($create) {
    //             return $this->responseService->formatServiceResponse("success", 'Key created successfully', [], $key);
    //         } else {
    //             return $this->responseService->formatServiceResponse("failed", 'Failed to create key', [], null);
    //         }
    //     } catch (\Throwable $th) {
    //         return $this->responseService->formatServiceResponse("unknown", 'Failed to create key', [], null);
    //     }
    // }

    // public function verifyAuthKey($customer, $key, $type, $reference = null)
    // {
    //     try {
    //         //code...
    //         $time = Carbon::now();
    //         $check = PosCallbackAuthKeys::where(['key' => $key, 'status' => 'pending'])
    //             ->where('type', $type)
    //             ->where('customer_id', $customer)
    //             ->where('expired_at', '>', $time);

    //         if ($reference) $check->where('reference', $reference);

    //         $check = $check->first();
    //         if ($check) {
    //             PosCallbackAuthKeys::where(['id' => $check->id])->update([
    //                 'status' => 'used'
    //             ]);

    //             return $this->responseService->formatServiceResponse("success", 'Token verified', [], null);
    //         } else {
    //             return $this->responseService->formatServiceResponse("failed", 'Invalid Key', [], null);
    //         }
    //     } catch (\Throwable $th) {
    //         Log::error($th);
    //         return $this->responseService->formatServiceResponse("uknown", 'Token mismatch, try again', [], null);
    //     }

    // }

    // public function randomStringGenerator ($n, bool $number = true, bool $cap = true, bool $lower = true) {
    //     $str = '';
    //     if ($number) $str .= '0123456789';
    //     if ($cap) $str .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    //     if ($lower) $str .= 'abcdefghijklmnopqrstuvwxyz';
    //     $randomString = '';

    //     for ($i = 0; $i < $n; $i++) {
    //         $index = rand(0, strlen($str) - 1);
    //         $randomString .= $str[$index];
    //     }

    //     return $randomString;
    // }

    // protected function verifyAccessKeys($public, $secret)
    // {
    //     return $public == env('POS_WEB_SERVICE_PUBLIC_KEY') && $secret == env('POS_WEB_SERVICE_PRIVATE_KEY');
    // }

    // public function createPosAccessToken($type, $domain_id, $public, $secret)
    // {
    //     try {
    //         if (!$this->verifyAccessKeys($public, $secret))
    //             return $this->responseService->formatServiceResponse("failed", 'Invalid keys!', [], null);
    //         $token = $this->randomStringGenerator(64);
    //         $time = Carbon::now()->addDay();

    //         $create = PosAccessToken::create([
    //             'token' => $token,
    //             'type' => $type,
    //             'expired_at' => $time,
    //             'domain_id' => $domain_id
    //         ]);

    //         if ($create) {
    //             return $this->responseService->formatServiceResponse("success", 'Token created', [], $token);
    //         } else {
    //             return $this->responseService->formatServiceResponse("failed", 'Could not create token', [], null);
    //         }
    //     } catch (\Throwable $th) {
    //         return $this->responseService->formatServiceResponse("unknown", 'Could not create token!' . $th->getMessage(), [], null);
    //     }
    // }

    // public function verifyPosAccessToken($incomingToken, $domain_id)
    // {
    //     try {
    //         if ($incomingToken) {
    //             $timing = date("Y-m-d H:i:s");
    //             $token = PosAccessToken::where(["token" => $incomingToken])->where('expired_at', '>', $timing)
    //                 ->where('domain_id', $domain_id)
    //                 ->first();

    //             if (!empty($token)) {
    //                 return $this->responseService->formatServiceResponse("success", 'Validated successfully', [], null);
    //             } else {
    //                 return $this->responseService->formatServiceResponse("failed", 'Invalid Token!', [], null);
    //             }
    //         } else {
    //             return $this->responseService->formatServiceResponse("failed", 'Invalid Token!', [], null);
    //         }
    //     } catch (\Throwable $th) {
    //         return $this->responseService->formatServiceResponse("unknown", $th->getMessage(), [], null);
    //     }
    // }
}
