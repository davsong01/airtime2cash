<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Notifications\AnnouncementNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::query()
            ->where('type', 'popup')
            ->where('status', 'active')
            ->latest('updated_at')
            ->paginate(10);

        return view('sneat.customer.announcements', compact('announcements'));
    }

    public function notifications(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->where('type', AnnouncementNotification::class)
            ->latest()
            ->paginate(10);

        $unreadCount = $request->user()
            ->unreadNotifications()
            ->where('type', AnnouncementNotification::class)
            ->count();

        return view('sneat.customer.notifications', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('type', AnnouncementNotification::class)
            ->findOrFail($notification);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', AnnouncementNotification::class)
            ->update(['read_at' => now()]);

        return back()->with('message', 'All announcements marked as read.');
    }
}
