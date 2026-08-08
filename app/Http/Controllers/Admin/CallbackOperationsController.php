<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\API;
use App\Models\ApiRequestLog;
use App\Models\AutoSyncWebhook;
use App\Models\Webhook;
use App\Services\AutoSyncWebhookProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class CallbackOperationsController extends Controller
{
    public function logWebhook(Request $request, int $provider_id){
        dd($request->all());
        return app(WebhookService::class)->logWebhookResponse($request, $provider_id);
    }

    public function analyzeProviderCallbackResponse($pick){
        return app(WebhookService::class)->analyzeWebhookResponse($pick);
    }
    
    public function index()
    {
        return redirect()->route('admin.webhooks.index');
    }

    public function webhooks(Request $request)
    {
        $webhooks = Webhook::with(['customer.user', 'resolver.user', 'provider'])
            ->when($request->filled('api_id'), fn ($query) => $query->where('api_id', $request->api_id))
            ->when($request->filled('processing_status'), fn ($query) => $query->where('processing_status', $request->processing_status))
            ->when($request->filled('provider_status'), fn ($query) => $query->where('provider_status', 'like', '%'.$request->provider_status.'%'))
            ->when($request->has('signature_valid') && $request->signature_valid !== '', fn ($query) => $query->where('signature_valid', (int) $request->signature_valid))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->customer_id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($query) use ($search) {
                    $query->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('provider_reference', 'like', "%{$search}%")
                        ->orWhere('request_ref', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->to_date))
            ->oldest('id')
            ->paginate(20)
            ->withQueryString();

        $providers = API::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $summary = Webhook::selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) AS pending")
            ->selectRaw("SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) AS failed")
            ->selectRaw("SUM(CASE WHEN processing_status = 'processed' THEN 1 ELSE 0 END) AS processed")
            ->first();

        return view('admin.callback.index', compact(
            'webhooks',
            'summary',
            'providers'
        ));
    }

    public function apiLogs(Request $request)
    {
        $apiLogs = ApiRequestLog::with(['customer.user', 'provider'])
            ->when($request->filled('operation'), fn ($query) => $query->where('operation', $request->operation))
            ->when($request->filled('api_id'), fn ($query) => $query->where('api_id', $request->api_id))
            ->when($request->filled('transaction_id'), fn ($query) => $query->where('transaction_id', 'like', '%'.$request->transaction_id.'%'))
            ->when($request->filled('method'), fn ($query) => $query->where('method', strtoupper($request->method)))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($query) use ($search) {
                    $query->where('endpoint', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('customer.user', function ($query) use ($search) {
                            $query->where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('provider_status'), function ($query) use ($request) {
                $status = $request->provider_status;

                $query->where(function ($query) use ($status) {
                    $query->where('response_body', 'like', '%"status":"'.$status.'"%')
                        ->orWhere('response_body', 'like', '%"status": "'.$status.'"%');
                });
            })
            ->when($request->filled('http_status'), function ($query) use ($request) {
                match ($request->http_status) {
                    'success' => $query->whereBetween('response_status', [200, 299]),
                    'client_error' => $query->whereBetween('response_status', [400, 499]),
                    'server_error' => $query->whereBetween('response_status', [500, 599]),
                    'no_response' => $query->whereNull('response_status'),
                    default => null,
                };
            })
            ->when($request->filled('min_duration'), fn ($query) => $query->where('duration_ms', '>=', (int) $request->min_duration))
            ->when($request->filled('max_duration'), fn ($query) => $query->where('duration_ms', '<=', (int) $request->max_duration))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->to_date))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = ApiRequestLog::selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN response_status BETWEEN 200 AND 299 THEN 1 ELSE 0 END) AS successful')
            ->selectRaw('SUM(CASE WHEN response_status IS NULL OR response_status >= 400 THEN 1 ELSE 0 END) AS failed')
            ->selectRaw('AVG(duration_ms) AS average_duration')
            ->first();

        $providers = API::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $operations = ApiRequestLog::query()
            ->whereNotNull('operation')
            ->distinct()
            ->orderBy('operation')
            ->pluck('operation');

        return view('admin.callback.api-logs', compact(
            'apiLogs',
            'summary',
            'operations',
            'providers'
        ));
    }

    public function clearApiRequestLogs(): RedirectResponse
    {
        ApiRequestLog::query()->truncate();

        return back()->with(
            'success',
            'API request logs cleared successfully.'
        );
    }

    public function clearWebhookLogs(): RedirectResponse
    {
        Webhook::query()->truncate();

        return back()->with(
            'success',
            'Webhook logs cleared successfully.'
        );
    }

    public function resolve(Webhook $webhook)
    {
        try {
            $processor->process($webhook, auth()->user()->admin->id);
        } catch (Throwable $exception) {
            return back()->with('error', 'Webhook could not be resolved: ' . $exception->getMessage());
        }

        return back()->with('message', 'Webhook processed successfully. Settlement remained idempotent.');
    }
}
