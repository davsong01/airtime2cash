<?php

namespace App\Console\Commands;

use App\Models\AutoSyncWebhook;
use App\Services\AutoSyncWebhookProcessor;
use Illuminate\Console\Command;
use Throwable;

class ProcessAutoSyncWebhooks extends Command
{
    protected $signature = 'autosync:process-webhooks {--limit=25}';
    protected $description = 'Process pending AutoSync webhooks sequentially';

    public function handle(AutoSyncWebhookProcessor $processor): int
    {
        AutoSyncWebhook::where('processing_status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['processing_status' => 'pending', 'last_error' => 'Recovered from a stale processing lock.']);

        $webhooks = AutoSyncWebhook::where('processing_status', 'pending')
            ->where('signature_valid', true)
            ->oldest('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        foreach ($webhooks as $webhook) {
            try {
                $processor->process($webhook);
                $this->line("Processed AutoSync webhook {$webhook->id}");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Webhook {$webhook->id}: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
