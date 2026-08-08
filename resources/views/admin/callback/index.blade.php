@extends('layouts.app')

@section('title', 'Webhooks')

@section('page-css')
    <style>
        .webhook-summary-card { border: 1px solid #ececec; box-shadow: none; }
        .webhook-summary-box { height: 100%; padding: 1rem; border: 1px solid #ececec; border-radius: .5rem; background: #fff; }
        .webhook-summary-box small { display: block; margin-bottom: .25rem; color: #828d99; }
        .webhook-summary-box strong { font-size: 1.4rem; }
        .webhook-provider { font-weight: 600; color: #263446; }
        .webhook-ref { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .webhook-status { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
        .webhook-status.success { color: #168f5b; background: rgba(40,199,111,.1); }
        .webhook-status.warning { color: #b8860b; background: rgba(255,159,67,.12); }
        .webhook-status.danger { color: #d94c4c; background: rgba(234,84,85,.1); }
        .webhook-status.info { color: #168aad; background: rgba(0,207,232,.1); }
        .webhook-json { max-height: 340px; overflow: auto; font-size: .78rem; background: #f8f9fa; border: 1px solid #ececec; border-radius: .4rem; padding: 1rem; white-space: pre-wrap; word-break: break-word; }
        .webhook-table td, .webhook-table th { vertical-align: middle; }
    </style>
@endsection

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>

    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 mt-1">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb p-0 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item">Callback Operations</li>
                        <li class="breadcrumb-item active">Webhooks</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <section class="card webhook-summary-card mb-2">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div>
                            <span class="text-primary text-uppercase font-small-3 font-weight-bold">Settlement Operations</span>
                            <h2 class="mb-50">Webhook Monitor</h2>
                            <p class="text-muted mb-0">Review provider callbacks, signature verification, processing attempts and settlement state.</p>
                        </div>

                        <a href="{{ route('admin.api-request.clear') }}" onsubmit="confirm('This will truncate all webhook data, are you sure?')" class="btn btn-danger mt-1 mt-md-0"><i class="bx bx-cog mr-50"></i>Clear Webhooks</a>
                    </div>

                    <div class="row mt-2">
                        @foreach([
                            ['Total Webhooks', $summary->total, 'primary'],
                            ['Pending', $summary->pending, 'warning'],
                            ['Failed', $summary->failed, 'danger'],
                            ['Processed', $summary->processed, 'success'],
                        ] as [$label, $value, $color])
                            <div class="col-6 col-lg-3 mb-1 mb-lg-0">
                                <div class="webhook-summary-box">
                                    <small>{{ $label }}</small>
                                    <strong class="text-{{ $color }}">{{ number_format((int) $value) }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                    <form method="GET" class="w-100">
                        <div class="row">
                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="api_id">Provider</label>
                                    <select name="api_id" id="api_id" class="form-control">
                                        <option value="">All providers</option>
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}" @selected((string) request('api_id') === (string) $provider->id)>
                                                {{ $provider->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </fieldset>
                            </div>

                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="processing_status">Processing Status</label>
                                    <select name="processing_status" id="processing_status" class="form-control">
                                        <option value="">All statuses</option>
                                        @foreach(['pending', 'processing', 'processed', 'failed', 'rejected'] as $status)
                                            <option value="{{ $status }}" @selected(request('processing_status') === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </fieldset>
                            </div>

                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="provider_status">Provider Status</label>
                                    <input
                                        type="text"
                                        name="provider_status"
                                        id="provider_status"
                                        class="form-control "
                                        value="{{ request('provider_status') }}"
                                        placeholder="e.g successful, pending"
                                    >
                                </fieldset>
                            </div>

                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="signature_valid">Signature</label>
                                    <select name="signature_valid" id="signature_valid" class="form-control ">
                                        <option value="">All</option>
                                        <option value="1" @selected(request('signature_valid') === '1')>Valid</option>
                                        <option value="0" @selected(request('signature_valid') === '0')>Invalid</option>
                                    </select>
                                </fieldset>
                            </div>

                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="search">Transaction / Reference</label>
                                    <input
                                        type="text"
                                        name="search"
                                        id="search"
                                        class="form-control "
                                        value="{{ request('search') }}"
                                        placeholder="Transaction ID, request ref, provider ref"
                                    >
                                </fieldset>
                            </div>

                            <div class="col-md-2">
                                <fieldset class="form-group">
                                    <label for="from_date">From</label>
                                    <input
                                        type="date"
                                        name="from_date"
                                        id="from_date"
                                        class="form-control "
                                        value="{{ request('from_date') }}"
                                    >
                                </fieldset>
                            </div>

                            <div class="col-md-2">
                                <fieldset class="form-group">
                                    <label for="to_date">To</label>
                                    <input
                                        type="date"
                                        name="to_date"
                                        id="to_date"
                                        class="form-control "
                                        value="{{ request('to_date') }}"
                                    >
                                </fieldset>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    @if(request()->hasAny([
                                        'api_id',
                                        'processing_status',
                                        'provider_status',
                                        'signature_valid',
                                        'search',
                                        'customer_id',
                                        'from_date',
                                        'to_date'
                                    ]))
                                        <a href="{{ route('admin.autosync.webhooks.index') }}" class="btn btn-sm btn-light-secondary mr-50">
                                            <i class="bx bx-x mr-25"></i>
                                            Clear
                                        </a>
                                    @endif

                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bx bx-filter-alt mr-25"></i>
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover webhook-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Provider</th>
                                <th>Transaction</th>
                                <th>Provider Event</th>
                                <th>Received</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($webhooks as $webhook)
                                @php
                                    $color = match($webhook->processing_status) {
                                        'processed' => 'success',
                                        'failed', 'rejected' => 'danger',
                                        'processing' => 'info',
                                        default => 'warning',
                                    };

                                    $icon = match($webhook->processing_status) {
                                        'processed' => 'bx-check-circle',
                                        'failed', 'rejected' => 'bx-x-circle',
                                        'processing' => 'bx-loader-alt bx-spin',
                                        default => 'bx-time-five',
                                    };

                                    $linkedTransaction = $webhook->linkedTransaction();
                                    $canResolve = $webhook->hasUnresolvedTransaction();
                                @endphp

                                <tr>
                                    <td>{{ $webhooks->firstItem() + $loop->index }}</td>

                                    <td>
                                        <div class="webhook-provider">
                                            {{ $webhook->provider?->name ?? 'Unknown Provider' }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $webhook->provider?->slug ?? 'No provider linked' }}
                                        </small>
                                    </td>

                                    <td>
                                        <strong>{{ $webhook->transaction_id ?: 'Not matched' }}</strong>
                                        <small class="d-block text-muted">
                                            {{ $webhook->customer?->user?->email ?: 'No customer match' }}
                                        </small>
                                    </td>

                                    <td>
                                        <strong>{{ $webhook->provider_status ?: 'Unknown status' }}</strong>
                                        <small class="d-block text-muted webhook-ref" title="{{ $webhook->provider_reference }}">
                                            {{ $webhook->provider_reference ?: 'No provider reference' }}
                                        </small>
                                    </td>

                                    <td>
                                        {{ $webhook->created_at->format('M j, Y') }}
                                        <small class="d-block text-muted">
                                            {{ $webhook->created_at->format('g:i:s A') }}
                                        </small>
                                    </td>

                                    <td>
                                        <span class="webhook-status {{ $color }}">
                                            <i class="bx {{ $icon }}"></i>
                                            {{ ucfirst($webhook->processing_status) }}
                                        </span>

                                        <small class="d-block mt-25 text-muted">
                                            {{ $webhook->attempts }} attempt(s)
                                        </small>
                                    </td>

                                    <td class="text-right">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light-secondary mr-50"
                                            data-toggle="modal"
                                            data-target="#webhookModal{{ $webhook->id }}"
                                        >
                                            <i class="bx bx-show mr-25"></i>
                                            Inspect
                                        </button>

                                        @if($canResolve)
                                            <form method="POST" action="{{ route('admin.autosync.webhooks.resolve', $webhook) }}" class="d-inline-block">
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-primary"
                                                    onclick="return confirm('Resolve this transaction from the attached webhook now?')"
                                                >
                                                    <i class="bx bx-refresh mr-25"></i>
                                                    Resolve
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                <div
                                    class="modal fade"
                                    id="webhookModal{{ $webhook->id }}"
                                    tabindex="-1"
                                    role="dialog"
                                    aria-hidden="true"
                                >
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title mb-25">
                                                        Webhook Exchange
                                                    </h5>

                                                    <small class="text-muted">
                                                        {{ $webhook->provider?->name ?? 'Unknown Provider' }}
                                                        ·
                                                        {{ $webhook->transaction_id ?: 'Unmatched transaction' }}
                                                    </small>
                                                </div>

                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="row mb-2">
                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Provider</small>
                                                        <strong>{{ $webhook->provider?->name ?? '-' }}</strong>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Processing Status</small>
                                                        <span class="webhook-status {{ $color }}">
                                                            {{ ucfirst($webhook->processing_status) }}
                                                        </span>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Signature</small>
                                                        <span class="badge badge-light-{{ $webhook->signature_valid ? 'success' : 'danger' }}">
                                                            {{ $webhook->signature_valid ? 'Valid' : 'Invalid' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="row mb-2">
                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Attached Transaction</small>
                                                        <strong>{{ $webhook->transaction_id ?: 'Not matched' }}</strong>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Transaction Status</small>
                                                        <span class="badge badge-light-{{ $webhook->hasUnresolvedTransaction() ? 'warning' : 'success' }}">
                                                            {{ $linkedTransaction?->status ? ucfirst($linkedTransaction->status) : 'Not resolved' }}
                                                        </span>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <small class="text-muted d-block">Bank</small>
                                                        <strong>{{ $linkedTransaction?->bank?->bank_name ?? $linkedTransaction?->bank_name ?? '-' }}</strong>
                                                    </div>
                                                </div>

                                                @if($webhook->last_error)
                                                    <div class="alert alert-danger">
                                                        <strong>Last Error:</strong>
                                                        {{ $webhook->last_error }}
                                                    </div>
                                                @endif

                                                <h6>Headers</h6>
                                                <pre class="webhook-json">{{ json_encode($webhook->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                                <h6 class="mt-2">Payload</h6>
                                                <pre class="webhook-json">{{ json_encode($webhook->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        No webhooks have been received.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($webhooks->hasPages())
                    <div class="card-footer">
                        {{ $webhooks->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
