@extends('sneat.layouts.app')

@section('title', 'Notifications')

@section('page-css')
    <style>
        .notification-card.is-unread {
            border-inline-start: 3px solid var(--bs-primary) !important;
        }

        .notification-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: .75rem;
        }

        .notification-content > :last-child {
            margin-bottom: 0;
        }
    </style>
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Inbox',
        'title' => 'Notifications',
        'subtitle' => $unreadCount > 0 ? $unreadCount . ' unread notification' . ($unreadCount === 1 ? '' : 's') : 'You are up to date.',
        'actions' => $unreadCount > 0 ? [[
            'label' => 'Mark all as read',
            'href' => '#mark-all-notifications',
            'class' => 'btn-label-primary',
            'icon' => 'bx bx-envelope-open',
        ]] : [],
    ])

    @include('sneat.layouts.alerts')

    @if($unreadCount > 0)
        <form id="mark-all-notifications" action="{{ route('customer.notifications.read-all') }}" method="POST" class="d-none">
            @csrf
        </form>
    @endif

    <div class="row g-4">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
            @endphp
            <div class="col-12" id="notification-{{ $notification->id }}">
                <article class="notification-card card border-0 {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="notification-icon {{ $isUnread ? 'bg-label-primary' : 'bg-label-secondary' }}">
                                <i class="bx bx-bell fs-4"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h5 class="mb-0">{{ $data['title'] ?? 'Notification' }}</h5>
                                            @if($isUnread)
                                                <span class="badge bg-primary">New</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <div class="notification-content text-body mb-3">
                                    {!! $data['message'] ?? '' !!}
                                </div>
                                @if($isUnread)
                                    <form action="{{ route('customer.notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm btn-label-primary" type="submit">
                                            <i class="bx bx-check me-1"></i> Mark as read
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body py-5 text-center">
                        <span class="notification-icon bg-label-secondary mb-3">
                            <i class="bx bx-bell-off fs-4"></i>
                        </span>
                        <h5 class="mb-0">No notifications yet</h5>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endsection

@section('page-script')
    <script>
        document.querySelector('a[href="#mark-all-notifications"]')?.addEventListener('click', function (event) {
            event.preventDefault();
            document.getElementById('mark-all-notifications')?.submit();
        });
    </script>
@endsection
