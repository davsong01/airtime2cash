<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Support\Facades\Notification;

class AnnouncementNotificationService
{
    public function publish(Announcement $announcement): int
    {
        if ($announcement->status !== 'active' || $announcement->type !== 'scroll') {
            return 0;
        }

        $notified = 0;

        User::query()
            ->where('type', 'customer')
            ->select('id')
            ->chunkById(250, function ($customers) use ($announcement, &$notified) {
                Notification::send($customers, new AnnouncementNotification($announcement));
                $notified += $customers->count();
            });

        return $notified;
    }
}
