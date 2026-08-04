@php
    $announcementNotifications = auth()->user()
        ->notifications()
        ->where('type', \App\Notifications\AnnouncementNotification::class)
        ->latest()
        ->limit(6)
        ->get();
    $announcementUnreadCount = auth()->user()
        ->unreadNotifications()
        ->where('type', \App\Notifications\AnnouncementNotification::class)
        ->count();
@endphp

<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
    <a
        class="nav-link dropdown-toggle hide-arrow"
        href="javascript:void(0);"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-label="Notifications"
    >
        <span class="position-relative">
            <i class="icon-base bx bx-bell icon-md"></i>
            @if($announcementUnreadCount > 0)
                <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
            @endif
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end p-0">
        <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
                <h6 class="mb-0 me-auto">Notifications</h6>
                @if($announcementUnreadCount > 0)
                    <div class="d-flex align-items-center h6 mb-0">
                        <span class="badge bg-label-primary me-2">{{ $announcementUnreadCount }} New</span>
                        <form action="{{ route('customer.notifications.read-all') }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-icon p-2" type="submit" title="Mark all as read" aria-label="Mark all announcements as read">
                                <i class="icon-base bx bx-envelope-open text-heading"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </li>
        <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush">
                @forelse($announcementNotifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                    @endphp
                    <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $isUnread ? '' : 'marked-as-read' }}">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar">
                                    <span class="avatar-initial rounded-circle {{ $isUnread ? 'bg-label-primary' : 'bg-label-secondary' }}">
                                        <i class="icon-base bx bx-megaphone"></i>
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('customer.notifications.index') }}#notification-{{ $notification->id }}" class="flex-grow-1 text-body">
                                <h6 class="small mb-1">{{ $data['title'] ?? 'Announcement' }}</h6>
                                <small class="mb-1 d-block text-body">{{ \Illuminate\Support\Str::limit(strip_tags($data['message'] ?? ''), 90) }}</small>
                                <small class="text-body-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                            </a>
                            @if($isUnread)
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <form action="{{ route('customer.notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-notifications-read btn btn-sm btn-icon" type="submit" title="Mark as read" aria-label="Mark announcement as read">
                                            <span class="badge badge-dot"></span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item py-5 text-center">
                        <i class="icon-base bx bx-bell-off icon-md text-muted mb-2"></i>
                        <div class="small text-muted">No announcements yet</div>
                    </li>
                @endforelse
            </ul>
        </li>
        <li class="border-top">
            <div class="d-grid p-4">
                <a class="btn btn-primary btn-sm" href="{{ route('customer.notifications.index') }}">
                    View all notifications
                </a>
            </div>
        </li>
    </ul>
</li>
