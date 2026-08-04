<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoSyncApiLog;
use App\Models\AutoSyncWebhook;
use App\Services\AutoSyncWebhookProcessor;
use Illuminate\Http\Request;
use Throwable;

class AutoSyncOperationsController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.autosync.webhooks.index');
    }

    public function webhooks(Request $request)
    {
        $webhooks = AutoSyncWebhook::with(['customer.user', 'resolver.user'])
            ->when($request->webhook_status, fn ($query, $status) => $query->where('processing_status', $status))
            ->oldest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = AutoSyncWebhook::selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) AS pending")
            ->selectRaw("SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) AS failed")
            ->selectRaw("SUM(CASE WHEN processing_status = 'processed' THEN 1 ELSE 0 END) AS processed")
            ->first();

        return view('admin.autosync.index', compact('webhooks', 'summary'));
    }

    public function apiLogs(Request $request)
    {
        $apiLogs = AutoSyncApiLog::with('customer.user')
            ->when($request->operation, fn ($query, $operation) => $query->where('operation', $operation))
            ->when($request->api_status === 'success', fn ($query) => $query->whereBetween('response_status', [200, 299]))
            ->when($request->api_status === 'failed', fn ($query) => $query->where(function ($query) {
                $query->whereNull('response_status')->orWhere('response_status', '>=', 400);
            }))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = AutoSyncApiLog::selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN response_status BETWEEN 200 AND 299 THEN 1 ELSE 0 END) AS successful')
            ->selectRaw('SUM(CASE WHEN response_status IS NULL OR response_status >= 400 THEN 1 ELSE 0 END) AS failed')
            ->selectRaw('AVG(duration_ms) AS average_duration')
            ->first();

        $operations = AutoSyncApiLog::whereNotNull('operation')
            ->distinct()
            ->orderBy('operation')
            ->pluck('operation');

        return view('admin.autosync.api-logs', compact('apiLogs', 'summary', 'operations'));
    }

    public function resolve(AutoSyncWebhook $webhook, AutoSyncWebhookProcessor $processor)
    {
        try {
            $processor->process($webhook, auth()->user()->admin->id);
        } catch (Throwable $exception) {
            return back()->with('error', 'Webhook could not be resolved: ' . $exception->getMessage());
        }

        return back()->with('message', 'Webhook processed successfully. Settlement remained idempotent.');
    }
}
