<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use App\Services\WebhookProcessor;
use Illuminate\Console\Command;
use Throwable;

class ProcessWebhooks extends Command
{
    protected $signature = ':process-webhooks {--limit=25}';
    protected $description = 'Process pending webhooks sequentially';

    public function handle(WebhookProcessor $processor): int
    {
        Webhook::where('processing_status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['processing_status' => 'pending', 'last_error' => 'Recovered from a stale processing lock.']);

        $webhooks = Webhook::where('processing_status', 'pending')
            ->where('signature_valid', true)
            ->oldest('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        foreach ($webhooks as $webhook) {
            try {
                $processor->process($webhook);
                $this->line("Processed  webhook {$webhook->id}");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Webhook {$webhook->id}: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
