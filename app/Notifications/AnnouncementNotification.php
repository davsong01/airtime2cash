<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'message' => $this->announcement->message,
            'announcement_type' => $this->announcement->type,
            'published_at' => now()->toIso8601String(),
        ];
    }
}
