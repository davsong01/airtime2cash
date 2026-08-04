@extends('sneat.layouts.app')

@section('title', 'Announcements')

@section('page-css')
    <style>
        .announcement-card {
            border-inline-start: 3px solid var(--bs-primary) !important;
        }

        .announcement-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: .75rem;
        }

        .announcement-content > :last-child {
            margin-bottom: 0;
        }
    </style>
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Updates',
        'title' => 'Announcements',
        'subtitle' => 'Important information from ' . config('app.name') . '.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4">
        @forelse($announcements as $announcement)
            <div class="col-12">
                <article class="announcement-card card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex align-items-start gap-3 gap-md-4">
                            <span class="announcement-icon bg-label-primary">
                                <i class="bx bx-news fs-4"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $announcement->title }}</h5>
                                        <small class="text-muted">Updated {{ $announcement->updated_at->diffForHumans() }}</small>
                                    </div>
                                    <span class="badge bg-label-primary align-self-start">Announcement</span>
                                </div>
                                <div class="announcement-content text-body">
                                    {!! $announcement->message !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body py-5 text-center">
                        <span class="announcement-icon bg-label-secondary mb-3">
                            <i class="bx bx-news fs-4"></i>
                        </span>
                        <h5 class="mb-0">No active announcements</h5>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($announcements->hasPages())
        <div class="mt-4">
            {{ $announcements->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endsection
