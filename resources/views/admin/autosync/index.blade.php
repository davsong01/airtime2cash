@extends('layouts.app')

@section('title', 'AutoSync Webhooks')

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 mt-1">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb p-0 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item">AutoSync operations</li>
                        <li class="breadcrumb-item active">Webhooks</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <section class="card mb-2">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div>
                            <span class="text-primary text-uppercase font-small-3 font-weight-bold">Settlement operations</span>
                            <h2 class="mb-50">AutoSync webhooks</h2>
                            <p class="text-muted mb-0">Monitor signed provider events and resolve delayed airtime settlements.</p>
                        </div>
                        <a href="{{ route('api.index') }}" class="btn btn-outline-primary mt-1 mt-md-0"><i class="bx bx-cog mr-50"></i>Provider settings</a>
                    </div>
                    <div class="row mt-2">
                        @foreach ([['Total webhooks', $summary->total, 'primary'], ['Pending', $summary->pending, 'warning'], ['Failed', $summary->failed, 'danger'], ['Processed', $summary->processed, 'success']] as [$label, $value, $color])
                            <div class="col-6 col-lg-3 mb-1 mb-lg-0">
                                <div class="border rounded p-1 h-100">
                                    <small class="text-muted d-block">{{ $label }}</small>
                                    <strong class="font-medium-4 text-{{ $color }}">{{ number_format((int) $value) }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card mb-2">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div><h4 class="mb-25">Webhook queue</h4><small>Oldest events appear first for sequential processing.</small></div>
                    <form method="GET" class="form-inline">
                        <select name="webhook_status" class="form-control form-control-sm mr-50">
                            <option value="">All statuses</option>
                            @foreach (['pending', 'processing', 'processed', 'failed', 'rejected'] as $status)
                                <option value="{{ $status }}" @selected(request('webhook_status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Transaction</th><th>Provider event</th><th>Received</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                        <tbody>
                            @forelse ($webhooks as $webhook)
                                @php
                                    $color = match($webhook->processing_status) { 'processed' => 'success', 'failed', 'rejected' => 'danger', 'processing' => 'info', default => 'warning' };
                                @endphp
                                <tr>
                                    <td>{{ $webhooks->firstItem() + $loop->index }}</td>
                                    <td><strong>{{ $webhook->transaction_id ?: 'Not matched' }}</strong><small class="d-block text-muted">{{ $webhook->customer?->user?->email ?: 'No customer match' }}</small></td>
                                    <td><strong>{{ $webhook->provider_status ?: 'Unknown status' }}</strong><small class="d-block text-muted text-truncate" style="max-width:260px">{{ $webhook->provider_reference ?: 'No provider reference' }}</small></td>
                                    <td>{{ $webhook->created_at->format('M j, Y') }}<small class="d-block text-muted">{{ $webhook->created_at->format('g:i:s A') }}</small></td>
                                    <td><span class="badge badge-light-{{ $color }}">{{ ucfirst($webhook->processing_status) }}</span><small class="d-block mt-25">{{ $webhook->attempts }} attempt(s)</small></td>
                                    <td class="text-right">
                                        <details class="d-inline-block text-left mr-50">
                                            <summary class="btn btn-sm btn-light-secondary">Inspect</summary>
                                            <div class="card position-absolute mt-50" style="right:2rem;z-index:20;max-width:720px;width:calc(100vw - 4rem)">
                                                <div class="card-body">
                                                    @if($webhook->last_error)<div class="alert alert-danger">{{ $webhook->last_error }}</div>@endif
                                                    <h6>Headers</h6><pre class="bg-light p-1 rounded text-wrap">{{ json_encode($webhook->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    <h6>Payload</h6><pre class="bg-light p-1 rounded text-wrap">{{ json_encode($webhook->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            </div>
                                        </details>
                                        @if($webhook->signature_valid && $webhook->processing_status !== 'processed')
                                            <form method="POST" action="{{ route('admin.autosync.webhooks.resolve', $webhook) }}" class="d-inline-block">
                                                @csrf
                                                <button class="btn btn-sm btn-primary" onclick="return confirm('Process this webhook now? Wallet settlement is idempotent.')"><i class="bx bx-refresh mr-25"></i>Resolve</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No AutoSync webhooks have been received.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($webhooks->hasPages())<div class="card-footer">{{ $webhooks->links() }}</div>@endif
            </section>

        </div>
    </div>
</div>
@endsection
