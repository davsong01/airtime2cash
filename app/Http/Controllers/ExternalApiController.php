<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Validator;

class ExternalApiController extends Controller
{
    public function __construct(private ApiResponseService $apiResponseService, private ResponseService $responseService)
    {
    }

    public function toJson($response)
    {
        if ($response['status'] == "success") {
            return response()->json($response, 200);
        } elseif ($response['status'] == "failed") {
            return response()->json($response, 422);
        } elseif ($response['status'] == "error") {
            return response()->json($response, 422);
        } elseif ($response['status'] == "unknown") {
            return response()->json($response, 500);
        }
    }

    public function getCategories()
    {
        $categories = $this->apiResponseService->getCategories();
        return $this->toJson($categories);
    }

    public function getProductsByCategory($category_slug)
    {
        $products = $this->apiResponseService->getProductsByCategory($category_slug);
        return $this->toJson($products);
    }

    public function getVariationsByProductSlug($product_slug)
    {
        $products = $this->apiResponseService->getVariationsByProductSlug($product_slug);
        return $this->toJson($products);
    }

    public function verifyBiller(Request $request){
        $validator = Validator::make($request->all(), [
            "variation_slug" => "required|string",
            "billersCode" => "required|string",
            "product_slug" => "required|string"
        ]);

        if ($validator->fails()) {
            return $this->responseService->formatServiceResponse("error", "", $validator->errors()->all(), null);
        }

        $verifyBiller = $this->apiResponseService->verifyBiller($request);
        return $this->toJson($verifyBiller);
    }

    public function getBalance(){
        $balance = $this->apiResponseService->getBalance(auth()->user());
        return $this->toJson($balance);
    }

    public function makePayment(Request $request){
        $validator = Validator::make($request->all(), [
            "product_slug" => "required|string",
            "variation_slug" => "sometimes|string",
            "billersCode" => "required|string",
            "request_id" => "required|string",
            "phone" => "required|string",
        ]);

        if ($validator->fails()) {
            return $this->toJson($this->responseService->formatServiceResponse("error", "", $validator->errors()->all(), null));
        }
        return $this->toJson($this->apiResponseService->initializeTransaction($request));
    }

    public function queryTransaction($request_id){

    }
    
}
