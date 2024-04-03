<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use App\Models\BlackList;
use App\Models\Variation;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Models\TransactionLog;
use App\Services\ExcelService;
use App\Models\ReferralEarning;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\WalletController;
use App\Models\BillerLog;
use App\Models\PaymentGateway;

class TransactionController extends Controller
{
    public function showProductsPage($slug)
    {
        $category = Category::with([
            'products' => function ($query) {
                return $query->where('status', 'active')->get();
            }
        ])->where('status', 'active')->where('slug', $slug)->first();
    
        if (!empty($category) && $category->status == 'active') {
            return view('customer.single_category_page', compact('category'));
        } else {
            return back();
        }
    }


    public function initializeTransaction(Request $request)
    {
        $blacklist = $this->bounceBlacklist($request->phone ?? $request->unique_element, auth()->user()->email, $request->email);

        if ($blacklist) {
            return back()->with('error', 'Account blacklisted!, kindly reach out to support!');
        }

        // Check Transaction pin
        $pinCheck = $this->checkTransactionPin($request);

        if (!$pinCheck) {
            return back()->with('error', 'Invalid Transaction PIN!');
        }

        // Get product
        $product = Product::where('id', $request->product)->first();
        $category = $product->category_id;

        if (empty ($product)) {
            return back()->with('error', 'The selected product/service does not seem to exist, kindly check your selection');
        }

        $element = $product->category->unique_element;
        $request['unique_element'] = $request->unique_element ?? $request->$element;

        if ($product->has_variations == 'yes') {
            $variation = Variation::where('id', $request->variation)->where('product_id', $product->id)->first();

            if ($variation->fixed_price == 'Yes') {
                $request['amount'] = $variation->system_price;
            } elseif ($product->allow_subscription_type == 'yes' && $variation->category->unique_element == 'iuc_number') {
                if (!empty ($request->bouquet) && $request->bouquet == 'renew') {
                    $req = new Request([
                        'unique_element' => $request['unique_element'],
                        'variation' => $variation->id,
                    ]);
                    $res = $this->verify($req);
                    if (isset ($res['renewal_amount'])) {
                        $request['amount'] = $res['renewal_amount'];
                    } else {
                        $request['amount'] = $variation->system_price;
                    }
                } else {
                    $request['amount'] = $variation->system_price;
                }
            } else {
                $request['amount'] = $this->removeCharsInAmount($request->amount);
            }
        } else {
            if ($product->fixed_price == 'yes') {
                $request['amount'] = $product->system_price;
            } else {
                $request['amount'] = $this->removeCharsInAmount($request->amount);
            }
        }

        // Verify Meter
        if ($product->allow_meter_validation) {
            $meterValidation = $this->validateMeter($product);
            if (isset ($meterValidation) && $meterValidation['code'] == 0) {
                return back()->with('error', $meterValidation['error']);
            }
        }

        $request['discount'] = 0;

        if ($product->has_variations == 'yes') {
            $discount = $this->getDiscount($variation, 'variation', $request['amount'], 'yes');
        } else {
            $discount = $this->getDiscount($product, 'product', $request['amount'], 'yes');
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
        $request_id = $this->generateRequestId();
        $request['type'] = 'debit';
        $request['customer_id'] = auth()->user()->customer->id;
        $request['transaction_id'] = 'KVTU-' . $request_id;
        $request['request_id'] = $request_id;
        $request['payment_method'] = 'wallet';
        $request['balance_before'] = $balance;
        $request['ip_address'] = $this->getIpAddress();
        $request['domain_name'] = $this->getDomainName();
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

        // Log basic transaction
        $transaction = $this->logTransaction($request->all());

        // Log wallet
        $wal = $wallet->logWallet($request->all());

        // Update Customer Wallet
        $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], $request['type']);

        // Process Transaction
        try {
            //code...
            $transaction = $this->processTransaction($request->all(), $transaction, $product, $variation ?? null);
        } catch (\Throwable $th) {
            \Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);
            return back()->with('error', 'An error occured, please try again later');
        }

        // Log Transaction Email
        $this->sendTransactionEmail($transaction, auth()->user());
        return redirect(route('transaction.status', $transaction->transaction_id));
    }

    public function transactionStatus($transaction_id)
    {
        $transaction = TransactionLog::where('transaction_id', $transaction_id)->first();
        return view('customer.transaction_status', compact('transaction'));
    }

    public function transactionReceipt($transaction_id)
    {
        $transaction = TransactionLog::with(['product', 'category', 'variation'])->where('id', $transaction_id)->first()->toArray();

        $pdf = Pdf::loadView('customer.receipts.transaction_receipt', ['transaction' => $transaction])->setPaper('a4', 'portrait');
        return $pdf->download($transaction['transaction_id'] . '.pdf');
        // return view('customer.receipts.transaction_receipt', compact('transaction'));
    }

    public function processTransaction($request, $transaction, $product, $variation)
    {
        $failure_reason = '';
        $api = $variation->api ?? $product->api;
        // Get Api
        $file_name = $api->file_name;
        $request['servercode'] = $variation->product->servercode ?? $product->servercode;
        $query = app("App\Http\Controllers\Providers\\" . $file_name)->query($request, $variation->api ?? $product->api, $variation, $product);
        
        try {
            //code...
            DB::beginTransaction();
            if (isset ($query) && $query['status_code'] == 1) {
                $user = auth()->user();
                $this->referralReward($user->referral, $request['total_amount'], $user->customer->id, $request['transaction_id'], $product->referral_percentage);
                $res = [
                    'status' => $query['status'],
                    'message' => 'Transaction Successful!',
                ];

                $user_status = 'success';
                $balance_after = $request['balance_before'] - $request['total_amount'];
            } else if (isset ($query) && $query['status_code'] == 0) {
                // Log wallet
                $wallet = new WalletController();
                $request['type'] = 'credit';
                $wallet->logWallet($request);
                $failure_reason = $query['error'] ?? $query['message'];

                // Update Customer Wallet
                $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');
                $balance_after = $request['balance_before'];
                $user_status = 'failed';
            } else {
                $user_status = 'failed';
                $res = [
                    'status' => $query['status'],
                    'message' => 'Transaction Successful!',
                ];

                $balance_after = $request['balance_before'] - $request['total_amount'];
            }

            $extra_info = [];
            $customer_details = BillerLog::where('service_id', $transaction->product->slug)->where('billers_code', $transaction->unique_element)->first();
            if(!empty($customer_details)){
                $customer_details = json_decode($customer_details->refined_data, true);
            }else{
                $customer_details = [];
            }
            
            $info = $query['extra_info'] ?? [];
            $extra_info = array_merge($info, $customer_details);
            // Update Transaction
            $transaction->update([
                'balance_after' => $balance_after,
                'request_data' => $query['payload'],
                'api_response' => $query['api_response'] ?? null,
                'failure_reason' => $failure_reason,
                'extras' => $query['extras'] ?? null,
                'status' => $query['status'] ?? 'attention-required',
                'descr' => $query['description'],
                'extra_info' => !empty($extra_info) ? json_encode($extra_info) : null,
                'user_status' => $user_status ?? null
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $wallet = new WalletController();
            $request['type'] = 'credit';
            $wallet->logWallet($request);
            $failure_reason = $query['error'] ?? $query['message'];

            // Update Customer Wallet
            $wallet->updateCustomerWallet(auth()->user(), $request['total_amount'], 'credit');
            $balance_after = $request['balance_before'];

            $transaction->update([
                'balance_after' => $balance_after
            ]);
            // \Log::error(['Transaction Error' => 'Message: ' . $th->getMessage() . ' File: ' . $th->getFile() . ' Line: ' . $th->getLine()]);
        }
        
        return $transaction;
    }

    public function verify(Request $request, $admin = null)
    {
        $variation = Variation::where('id', $request->variation)->first();

        if (in_array($variation->slug, array_keys(specialVerifiableVariations()))) {
            $element = specialVerifiableVariations()[$variation->slug];
        } else {
            $element = $variation->category->unique_element;
        }

        $unique_elementX = ucfirst(str_replace("_", " ", $element));

        if (empty ($admin)) {
            $validator = Validator::make($request->all(), [
                'unique_element' => 'required'
            ]);

            if ($validator->fails()) {
                $res = [
                    'status' => '0',
                    'message' => $unique_elementX . ' is required',
                    'title' => 'Please fill all fields',
                ];

                return response()->json($res);
            }
        }


        $product = $variation->product;
        $api = $variation->api;
        $file_name = $variation->api->file_name;

        $request['product_name'] = $product->name ?? null;
        $request['variation_name'] = $variation->slug ?? null;
        $request['category_id'] = $product->category->id ?? null;
        $request['product_slug'] = $variation->product->slug ?? null;
        $request['network'] = $variation->network ?? null;

        $request['unique_element'] = $request->unique_element;

        $data = [
            'variation' => $variation,
            'product' => $product,
            'api' => $api,
            'request' => $request
        ];

        // Get Api
        $verify = app("App\Http\Controllers\Providers\\" . $file_name)->verify($data);

        if (isset ($verify) && $verify['status_code'] == 1) {
            $res = [
                'status' => $verify['status_code'],
                'message' => $verify['message'],
                'title' => $verify['title'],
                'renewal_amount' => $verify['renewal_amount']
            ];
        
            if(isset($verify['raw_response'])){
                $this->refineAndLogBiller($verify, $variation->category,$request['unique_element'],$request['product_slug']);
            }
        } else if (isset($query) && $query['status_code'] == 0) {
            $res = [
                'status' => $verify['status_code'],
                'message' => $verify['message'],
                'title' => $verify['title'],
            ];
        } else {
            $res = [
                'status' => $verify['status_code'] ?? 0,
                'message' => $verify['message'] ?? 'Biller not reachable at the moment, please try again later',
                'title' => $verify['title'] ?? 'Not Reachable',
            ];
        }

        if ($request->ajax()) {
            return response()->json($res);
        } else {
            return $res;
        }
    }

    public function checkTransactionPin($request)
    {
        $pin = base64_decode(base64_decode(base64_decode(auth()->user()->transaction_pin)));
        if ($pin == $request->transaction_pin) {
            return true;
        } else {
            return false;
        }
    }

    public function getCustomerDiscount(Request $request)
    {
        if (!empty ($request->product_id)) {
            $resource = Product::where('id', $request->product_id)->first();
            $type = 'product';
        }

        if (!empty ($request->variation_id)) {
            $resource = Variation::where('id', $request->variation_id)->first();
            $type = 'variation';
        }

        $discount = $this->getDiscount($resource, $type, $request->amount, 'yes');        
        // $suffix = (isset($discount['type']) && $discount['type'] == 'percentage') ? number_format($discount['rate']) . '% off' : 'Discounted to ' . getSettings()->currency . number_format($discount['rate'], 2);
        $suffix = '';
    
        if(!empty($request->raw) && $request->raw == 'yes'){
            return [
                'discount' => $discount['rate'],
                'message' => '<span class="pay">You will pay </span><strong><span class="rate">' . getSettings()->currency . number_format($discount['discounted_price']) . '</span></strong><span class="suffix"></span>',
            ];
        } else {
            return response()->json([
                'discount' => $discount['rate'],
                'message' => '<span class="pay">You will pay </span><strong><span class="rate">' . getSettings()->currency . number_format($discount['discounted_price']) . '</span></strong><span class="suffix"></span>',
            ]);
        }
    }

    public function getDiscount($resource, $type, $amount = null, $getRate = null)
    {
        $discount = 0;
        $level = auth()->user()->customer->customer_level;
        $amount = $amount;

        if($type == 'variation'){
            $findDiscount = Discount::where(['customer_level' => $level, 'variation_id' => $resource->id])->where('price', '>', 0)->first();
            if($resource->fixed_price == 'Yes'){
                $amount = $resource->system_price;
            }
        }

        if ($type == 'product') {
            $findDiscount = Discount::where(['customer_level' => $level, 'product_id' => $resource->id])->where('price', '>',0)->first();
        }

        if (!empty ($findDiscount) && $findDiscount->price > 0) {
            $price = $findDiscount->price;
            if ($resource->category->discount_type == 'flat') {
                $discount = $price;
                $discounted_price = $discount;
            }

            if ($resource->category->discount_type == 'percentage' && !empty ($amount)) {
                $discount = ($price / 100) * $amount;
                $discounted_price = $amount - $discount;
            }

        }

        $discounted_price = floor($discounted_price ?? $amount); // to floor down percentage based discounts
        // $discounted_price = intval(floor($discounted_price ?? $amount)); // to floor down percentage based discounts
        
        if($resource->fixed_price == 'Yes'){
            $discounted_price = $discounted_price <= $resource->system_price ? $discounted_price : $resource->system_price;
        } 
        // dd($resource->category->discount_type, $amount, $discount, $discounted_price);
        $discounted_price = $discounted_price <= 0 ? $resource->system_price : $discounted_price;
        
        if(!empty($getRate)){

            $response = [
                'amount' => $amount ?? 0,
                'discount' => $discount ?? 0,
                'discounted_price' => $discounted_price ?? 0,
                'rate' => $price ?? 0,
                'type' => $resource->category->discount_type ?? '',
                'discount_applied' => !empty ($discounted_price) ? $amount - $discounted_price : 0,

            ];

            return $response;
        } else {
            return $discount;
        }
    }

    // public function getProductDiscount($product)
    // {
    //     $discount = 0;
    //     $level = auth()->user()->customer->customer_level;

    //     $findDiscount = Discount::where(['customer_level' => $level, 'product_id' => $product->id])->first();

    //     if (!empty($findDiscount)) {
    //         $discount = $findDiscount->price;
    //     }

    //     return $discount;
    // }

    public function validateMeter()
    {
    }

    public function logTransaction($data)
    {
        $pre = [
            'status' => $data['status'] ?? 'initiated',
            'reference_id' => $data['request_id'],
            'transaction_id' => $data['transaction_id'],
            'payment_method' => $data['payment_method'],
            'customer_id' => $data['customer_id'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_name' => $data['customer_name'],
            'discount' => $data['discount'] ?? null,
            'unit_price' => $data['amount'],
            'quantity' => $data['quantity'] ?? 1,
            'total_amount' => $data['total_amount'],
            'amount' => $data['amount'],
            'balance_before' => $data['balance_before'],
            'balance_after' => $data['balance_after'] ?? ($data['balance_before'] - $data['total_amount']),
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'variation_id' => $data['variation_id'] ?? null,
            'variation_name' => $data['variation_name'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unique_element' => $data['unique_element'],
            'ip_address' => $data['ip_address'] ?? null,
            'domain_name' => $data['domain_name'] ?? null,
            'app_version' => Session::get('app_version') ?? null,
            'api_id' => $data['api_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'wallet_funding_provider' => $data['wallet_funding_provider'] ?? null,
            'provider_charge' => $data['provider_charge'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'ip_address' => Session::get('ip_address') ?? null,
            'domain_name' => Session::get('domain_name') ?? null,
            'descr' => $data['descr'] ?? null,
        ];

        $trans = TransactionLog::create($pre);
        return $trans;
    }

    public function removeCharsInAmount($code)
    {
        $chars = str_split($code);
        $str2 = "";
        foreach ($chars as $char) {
            if ($char != '#') {
                $str2 .= $char;
            }
        }
        $code = trim(preg_replace('/[^0-9\.]+/i', '', $str2));
        return $code;
    }

    public function customerTransactionHistory(Request $request)
    {
        $transactions = TransactionLog::with(['product', 'variation', 'wallet'])->where('customer_id', auth()->user()->customer->id)->where('status', '!=', 'initiated');

        if (!empty ($request->service)) {
            $transactions = $transactions->where('product_id', $request->service);
        }

        if (!empty ($request->reason)) {
            $transactions = $transactions->where('reason', $request->reason);
        }

        if (!empty ($request->transaction_id)) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if (!empty ($request->status)) {
            $transactions = $transactions->where('status', $request->status);
        }

        if (!empty ($request->unique_element)) {
            $transactions = $transactions->where('unique_element', 'LIKE', "%" . $request->unique_element . "%");
        }

        if (!empty ($request->from) && !empty ($request->to)) {
            $from = $request->from . " 00:00:00";
            $to = $request->to . " 23:59:59";
            $transactions = $transactions->whereBetween('created_at', [$from, $to]);
        }

        $transactions = $transactions->orderBy('created_at', 'DESC')->paginate(20);

        $products = Product::where('status', 'active')->get();
        return view('customer.mytransactions', compact('transactions', 'products'));
    }

    public function showTransactionReportPage(Request $request, ExcelService $export)
    {
        if (!empty ($request->type)) {
            if ($request->type == 'transaction') {
                $data = TransactionLog::with(['product', 'variation', 'wallet'])->where('customer_id', auth()->user()->customer->id)->where('status', '!=', 'initiated');

                if (!empty ($request->category)) {
                    $data = $data->where('category_id', $request->category);
                }

                if (!empty ($request->unique_element)) {
                    $transactions = $data->where('unique_element', 'LIKE', "%" . $request->unique_element . "%");
                }

                if (!empty ($request->status)) {
                    if ($request->status == 'delivered') {
                        $data = $data->whereIn('status', ['success', 'delivered']);
                    } else {
                        $data = $data->where('status', 'failed');
                    }
                }
            }

            if ($request->type == 'wallet') {
                $data = Wallet::where('customer_id', auth()->user()->customer->id);
            }

            if ($request->type == 'earning') {
                $data = ReferralEarning::where('customer_id', auth()->user()->customer->id);
            }

            if (!empty ($request->from) && !empty ($request->to)) {
                $from = $request->from . " 00:00:00";
                $to = $request->to . " 23:59:59";
                $data = $data->whereBetween('created_at', [$from, $to]);
            }

            $data = $data->orderBy('created_at', 'DESC')->get()->toArray();
            $format = [];
            foreach ($data as $data) {
                if ($request->type == 'earnings') {
                    $details = Customer::with('user')->where('id', $data['customer_id'])->first();
                    $data['customer_username'] = $details->username;
                }

                if (isset ($data['reason'])) {
                    $row['Reason'] = $data['reason'];
                }

                if (isset ($data['extras'])) {
                    $row['Extras'] = $data['extras'];
                }

                if (isset ($data['product_name'])) {
                    $row['Product Name'] = $data['product_name'];
                }
                if (isset ($data['variation_name'])) {
                    $row['Variation Name'] = $data['variation_name'];
                }

                if (isset ($data['unique_element'])) {
                    $row['Unique Element'] = $data['unique_element'];
                }

                if (isset ($data['descr'])) {
                    $row['Description'] = $data['descr'];
                }

                if (isset ($data['payment_method'])) {
                    $row['Payment Method'] = $data['payment_method'];
                }

                if (isset ($data['customer_email'])) {
                    $row['Customer Email'] = $data['customer_email'];
                }

                if (isset ($data['customer_username'])) {
                    $row['Customer Username'] = $data['customer_username'];
                }

                if (isset ($data['customer_phone'])) {
                    $row['Customer Phone'] = $data['customer_phone'];
                }

                $row['Type'] = $data['type'];
                $row['Transaction ID'] = $data['transaction_id'];
                $row['Amount'] = $data['amount'];

                if (isset ($data['unit_price'])) {
                    $row['Unit Price'] = $data['unit_price'];
                }

                if (isset ($data['provider_charge'])) {
                    $row['Convenience Fee'] = $data['provider_charge'];
                }

                if (isset ($data['discount'])) {
                    $row['Discount'] = $data['discount'];
                }

                if (isset ($data['total_amount'])) {
                    $row['Total Amount'] = $data['total_amount'];
                }

                if (isset ($data['balance_before'])) {
                    $row['Initial Balance'] = $data['balance_before'];
                }

                if (isset ($data['balance_after'])) {
                    $row['Final Balance'] = $data['balance_after'];
                }

                $row['Date'] = $data['created_at'];

                $format[] = $row;
            }

            $fileName = $request->type . '_report-' . rand(919, 9999) . '-' . date('Y-m-d H:i:s', time());
            return $export->fastExcelExport($format, $fileName);
        }


        $products = Product::where('status', 'active')->get();
        $categories = Category::where('status', 'active')->get();
        return view('customer.reports', compact('products', 'categories'));
    }

    function referralReward($ref, $amount, $customer_id, $transaction_id, $referral_percentage)
    {
        if ($ref) {
            if(!empty($referral_percentage)){
                $sett = getSettings();
                $percentage = $referral_percentage;
                $user = User::where('username', $ref)->first();
                $curUser = auth()->user();

                if ($user) {
                    if ($sett->referral_system_status == 'active') {
                        $cut = $percentage;
                        $cal = ($cut / 100) * $amount;
    
                        $customer = $user->customer;
                        $current = $customer->referal_wallet;
    
                        $sum = $current + $cal;
                        $this->logEarnings(
                            'credit',
                            $customer->id,
                            $customer_id,
                            $cal,
                            $current,
                            $sum,
                            $transaction_id,
                        );
                        $customer->referal_wallet = $sum;
                        $customer->save();
                        $host = env('APP_URL');
                        $rewardMail = <<<__here
                            Dear $user->firstname $user->lastname,
    
                            Congratulations! We are excited to inform you that you have earned a commission from a transaction made by your referred friend. Your support and engagement in our referral program are truly appreciated.
    
                            Here are the details of the transaction:
    
                            Referred Friend's Name: $curUser->firstname
    
                            Commission Earned: $cal
    
                            Total Commission Earned: $cal
    
                            Transaction Details: <a href="$host/downlines/$curUser->id">click here</a>
    
                            Your dedication to spreading the word about our services is making a real impact, and we are grateful for your continued support. As a token of our appreciation, we have credited your wallet with the earned commission.
    
                            Thank you once again for being a valued member of our community. We look forward to your continued success in our referral program!
                            __here;
    
                        logEmails($user->email, 'Referral Commission', $rewardMail);
                    }
                }
            }
        }
    }

    public function logEarnings($type, $customer, $referred, $amount, $before, $after, $transaction_id)
    {
        $ref = ReferralEarning::create([
            'type' => $type,
            'customer_id' => $customer,
            'referred_customer_id' => $referred,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'transaction_id' => $transaction_id,
        ]);

        return $ref;
    }

    public function bounceBlacklist($phone, $user, $mail = null)
    {
        $blacklist = BlackList::where('status', 'active')->whereRaw(" (value = ? or value = ? or value = ?)", [$mail, $phone, $user])->first();

        if ($blacklist)
            return true;
        return false;
    }

    public function transView(Request $request)
    {
        $transactions = TransactionLog::whereNotNull('product_id')->latest();
        $transactionsS = clone $transactions;
        $transactionsA = clone $transactions;
        $transactionsF = clone $transactions;
        $totalTransSuccess = $transactionsS->whereIn('status', ['delivered', 'success'])->sum('amount');
        $totalTransFailed = $transactionsF->where('status', 'failed')->sum('amount');
        $totalTransAttention = $transactionsA->where('status', 'attention-required')->sum('amount');
        $products = Product::all();

        if ($request->email) {
            $transactions = $transactions->where('customer_email', $request->email);
        }

        if ($request->phone) {
            $transactions = $transactions->where('customer_phone', $request->phone);
        }

        if ($request->service) {
            $transactions = $transactions->where('product_id', $request->service);
        }
        if ($request->transaction_id) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }
        if ($request->unique_element) {
            $transactions = $transactions->where('unique_element', $request->unique_element);
        }
        if ($request->status) {
            $transactions = $transactions->where('status', $request->status);
        }
        if ($request->from) {
            $time = $request->from . ' 00:00:00';
            $transactions = $transactions->where('created_at', '>', $time);
        }
        if ($request->to) {
            $time = $request->to . ' 00:00:00';
            $transactions = $transactions->where('created_at', $time);
        }

        $transactions = $transactions->paginate(20);

        return view('admin.transaction.index', [
            'transactions' => $transactions,
            'products' => $products,
            'success' => $totalTransSuccess,
            'failed' => $totalTransFailed,
            'attention_required' => $totalTransAttention,
            'query' => $request->query(),
        ]);
    }

    public function walletTransView(Request $request)
    {
        $transactions = Wallet::latest();
        $transactionsD = clone $transactions;
        $transactionsC = clone $transactions;

        $debit = $transactionsD->where('type', 'debit')->sum('amount');
        $credit = $transactionsC->where('type', 'credit')->sum('amount');

        if ($request->email) {
            $user = User::where('email', $request->email)->first();
            if (!empty ($user)) {
                $customer = $user->customer;
                $id = $customer->id;
                $transactions = $transactions->where('customer_id', $id);
            }
        }

        if ($request->transaction_id) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if ($request->type) {
            $transactions = $transactions->where('type', $request->type);
        }

        if ($request->from) {
            $time = $request->from . ' 00:00:00';
            $transactions = $transactions->where('created_at', '>', $time);
        }
        if ($request->to) {
            $time = $request->to . ' 00:00:00';
            $transactions = $transactions->where('created_at', $time);
        }

        $transactions = $transactions->paginate(20);

        return view('admin.transaction.wallet_log', [
            'transactions' => $transactions,
            'debit' => $debit,
            'credit' => $credit,
            'query' => $request->query(),
        ]);
    }

    public function walletFundingLogView(Request $request)
    {
        $transactions = TransactionLog::whereNotNull('wallet_funding_provider')->where('unique_element', 'WALLET-FUNDING')->latest();
        $transactionsS = clone $transactions;
        $transactionsA = clone $transactions;
        $transactionsF = clone $transactions;
        $totalTransSuccess = $transactionsS->whereIn('status', ['delivered', 'success'])->sum('amount');
        $totalTransFailed = $transactionsF->where('status', 'failed')->sum('amount');
        $totalTransAttention = $transactionsA->where('status', 'attention-required')->sum('amount');
        $providers = PaymentGateway::latest();

        if ($request->email) {
            $user = User::where('email', $request->email)->first();
            if (!empty ($user)) {
                $customer = $user->customer;
                $id = $customer->id;
                $transactions = $transactions->where('customer_id', $id);
            }
        }

        if ($request->transaction_id) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if ($request->type) {
            $transactions = $transactions->where('type', $request->type);
        }

        if ($request->from) {
            $time = $request->from . ' 00:00:00';
            $transactions = $transactions->where('created_at', '>', $time);
        }
        if ($request->to) {
            $time = $request->to . ' 00:00:00';
            $transactions = $transactions->where('created_at', $time);
        }

        $transactions = $transactions->paginate(20);

        return view('admin.transaction.wallet_funding', [
            'providers' => $providers,
            'transactions' => $transactions,
            'success' => $totalTransSuccess,
            'failed' => $totalTransFailed,
            'attention_required' => $totalTransAttention,
            'query' => $request->query(),
        ]);
    }

    public function walletEarningView(Request $request)
    {
        $transactions = ReferralEarning::latest();

        $transactionsS = clone $transactions;
        $transactionsF = clone $transactions;
        $totalTransSuccess = $transactionsS->where('type', 'credit')->sum('amount');
        $totalTransFailed = $transactionsF->where('type', 'debit')->sum('amount');


        if ($request->upline_email) {
            $user = User::where('email', $request->upline_email)->first();
            if (!empty ($user)) {
                $customer = $user->customer;
                $id = $customer->id;
                $transactions = $transactions->where('customer_id', $id);
            }
        }

        if ($request->downline_email) {
            $user = User::where('email', $request->downline_email)->first();
            if (!empty ($user)) {
                $customer = $user->customer;
                $id = $customer->id;
                $transactions = $transactions->where('referred_customer_id', $id);
            }
        }

        if ($request->transaction_id) {
            $transactions = $transactions->where('transaction_id', $request->transaction_id);
        }

        if ($request->type) {
            $transactions = $transactions->where('type', $request->type);
        }

        if ($request->from) {
            $time = $request->from . ' 00:00:00';
            $transactions = $transactions->where('created_at', '>', $time);
        }
        if ($request->to) {
            $time = $request->to . ' 00:00:00';
            $transactions = $transactions->where('created_at', $time);
        }

        $transactions = $transactions->paginate(20);

        return view('admin.transaction.earning_log', [
            'transactions' => $transactions,
            'success' => $totalTransSuccess,
            'failed' => $totalTransFailed,
            'query' => $request->query(),
        ]);
    }

    public function singleTransactionView(TransactionLog $transaction)
    {
        return view('admin.transaction.single_transaction', compact('transaction'));
    }

    function debitCustomerPage()
    {
        return view('admin.transaction.debit_customer');
    }

    function creditCustomerPage()
    {
        return view('admin.transaction.credit_customer');
    }

    function processCreditDebit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric',
            'reason' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user)
            return back()->with('error', 'Account not found!');
        if (str_contains(url()->previous(), 'debit'))
            $type = 'debit';
        else if (str_contains(url()->previous(), 'credit'))
            $type = 'credit';

        if (!$type)
            return back()->with('error', 'Hmph, something went wrong!');

        $controller = new TransactionController();
        $amount = $controller->removeCharsInAmount($request->amount);
        $currAmount = walletBalance($user);

        if ($type == 'credit') {
            $data['balance_after'] = $currAmount + $amount;
        } else {
            $data['balance_after'] = $currAmount - $amount;
        }

        $requestId = $controller->generateRequestId();
        $tid = 'KVTU-' . $requestId;
        $reason = $type == 'debit' ? 'ADMIN-DEBIT' : 'ADMIN-CREDIT';
        try {
            //code...
            $data['type'] = $type;
            $data['customer_id'] = $user->customer->id;
            $data['transaction_id'] = $tid;
            $data['request_id'] = $requestId;
            $data['payment_method'] = 'wallet';
            $data['balance_before'] = $currAmount;
            $data['amount'] = $amount;
            $data['total_amount'] = $amount;
            $data['customer_email'] = $user->email;
            $data['customer_phone'] = $user->phone;
            $data['customer_name'] = $user->firstname;
            $data['unique_element'] = 'wallet';
            $data['discount'] = 0;
            $data['unit_price'] = $amount;
            $data['descr'] = $request->reason;
            $data['reason'] = $reason;
            $data['status'] = 'delivered';
            $data['admin_id'] = auth()->user()->admin->id;

            $controller->logTransaction($data);

            DB::transaction(function () use ($type, $amount, $tid, $user, $reason) {
                $wallet = new WalletController();
                $wallet->logWallet([
                    'customer_id' => $user->customer->id,
                    'type' => $type,
                    'amount' => $amount,
                    'reason' => $reason,
                    'transaction_id' => $tid,
                    'payment_method' => 'ADMIN-FUNDING',
                ]);
                $wallet->updateCustomerWallet($user, $amount, $type);
            });
            $sign = getSettings()->currency;
            return back()->with('message', "Customer wallet has been {$type}ed with {$sign}" . number_format($amount));
        } catch (\Exception $e) {

            return back()->with('error', 'An error occured' . $e->getMessage() . $e->getLine() . $e->getFile());
        }
    }

    public function queryWallet(Request $request, TransactionLog $transactionlog)
    {
        $type = $request->type;
        $check = Wallet::where('transaction_id', $transactionlog->transaction_id)
            ->where('type', $type)
            ->get();

        $type = strtoupper($request->type);
        $message = '';

        if ($check->count() > 0) {
            $status = 'success';
            $message .= '<h4 align="center" style="color:green"><b>QUERY SUCCESSFUL</b><br><span class="fa fa-check-circle text-success mr-1" style="font-size:19px">' . $check->count() . '</span></h4><hr style="margin:6px 0px 6px 0px"><table><tbody>';

            foreach ($check as $wallet) {
                $message .= '<tr><th>TransID:</th>
                <td>&nbsp;' . $wallet->transaction_id . '</td>
                </tr>
                <tr><th>Amt:</th>
                <td>&nbsp;' . getSettings()->currency . number_format($wallet->amount, 2) . '</td>
                </tr>
                <tr><th>Date:</th>
                <td>&nbsp;' . $wallet->created_at . '</td>
                <tr><th>Reason:</th>
                <td>&nbsp;' . $wallet->reason . '</td>
                </tr>';
            }

            $message .= '</tbody></table><br><center><button class="btn btn-success btn-sm">' . $type . ' DONE</button></center></div>';
        } else {
            $status = 'failed';
            $message .= '<h4 align="center" style="color:red"><b>QUERY FAILED</b></h4><hr style="margin:6px 0px 6px 0px"><center><button class="btn btn-danger btn-sm">' . $type . ' NOT DONE</button></center>';
        }

        $ret = [
            'status' => $status,
            'message' => $message
        ];

        return response()->json($ret);
    }

    public function requery($transactionlog = null)
    {
        if (!$transactionlog) return ['status' => 'failed'];

        $trans = TransactionLog::find($transactionlog);

        if (!$trans) return ['status' => 'failed', 'message' => 'Transaction not found!'];

        // $requestId = explode('KVTU-', $trans->transaction_id)[1];

        // $api = $trans->api;
        $query = app("App\Http\Controllers\Providers\\" . $trans->api->file_name)->requery($trans);

        return $query;
    }
}
