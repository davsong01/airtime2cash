<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function logWebhook(Request $request, int $provider_id){
        return app(WebhookService::class)->logWebhookResponse($request, $provider_id);
    }

    public function analyzeProviderCallbackResponse($pick){
        return app(WebhookService::class)->analyzeWebhookResponse($pick);
    }

}
