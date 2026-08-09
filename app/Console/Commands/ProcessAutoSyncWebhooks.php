<?php

namespace App\Console\Commands;

use App\Models\Webhook;
use App\Services\WebhookProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAutoSyncWebhooks extends Command
{
    protected $signature = 'process:webhooks {--limit=25}';
    protected $description = 'Process pending webhooks sequentially';

    public function handle(WebhookProcessor $processor): int
    {
        $staleRecovered = Webhook::where('processing_status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['processing_status' => 'pending', 'last_error' => 'Recovered from a stale processing lock.']);

        $webhooks = Webhook::where('processing_status', 'pending')
            ->where('signature_valid', true)
            ->oldest('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($webhooks as $webhook) {
            try {
                $beforeStatus = $webhook->processing_status;
                $processor->process($webhook);
                $this->line("Processed  webhook {$webhook->id}");

                if ($beforeStatus === 'processed') {
                    $skipped++;
                } else {
                    $processed++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Webhook {$webhook->id}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $summary = [
            'picked' => $webhooks->count(),
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
            'stale_recovered' => $staleRecovered,
        ];

        $message = sprintf(
            'Webhook cron run: picked=%d processed=%d failed=%d skipped=%d stale_recovered=%d',
            $summary['picked'],
            $summary['processed'],
            $summary['failed'],
            $summary['skipped'],
            $summary['stale_recovered'],
        );

        $this->info($message);
        Log::info($message, $summary);

        return self::SUCCESS;
    }
}
