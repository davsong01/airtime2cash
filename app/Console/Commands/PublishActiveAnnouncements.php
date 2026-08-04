<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Notifications\AnnouncementNotification;
use App\Services\AnnouncementNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublishActiveAnnouncements extends Command
{
    protected $signature = 'announcements:publish-active {--force : Publish even if the announcement was previously synced}';

    protected $description = 'Publish active legacy announcements as customer database notifications';

    public function handle(AnnouncementNotificationService $notificationService): int
    {
        if (!Schema::hasTable('notifications')) {
            $this->error('The notifications table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $announcements = Announcement::query()
            ->where('status', 'active')
            ->where('type', 'scroll')
            ->get();

        if ($announcements->isEmpty()) {
            $this->info('There are no active announcements to publish.');

            return self::SUCCESS;
        }

        $published = 0;

        foreach ($announcements as $announcement) {
            $alreadyPublished = DB::table('notifications')
                ->where('type', AnnouncementNotification::class)
                ->where('data->announcement_id', $announcement->id)
                ->exists();

            if ($alreadyPublished && !$this->option('force')) {
                $this->line("Skipped {$announcement->title}; it has already been published.");
                continue;
            }

            $recipients = $notificationService->publish($announcement);
            $published += $recipients;
            $this->info("Published {$announcement->title} to {$recipients} customers.");
        }

        $this->info("Created {$published} customer notifications.");

        return self::SUCCESS;
    }
}
