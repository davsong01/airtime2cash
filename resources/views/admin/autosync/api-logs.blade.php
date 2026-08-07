@extends('layouts.app')

@section('title', 'AutoSync API Request Logs')

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
                        <li class="breadcrumb-item active">API Request Logs</li>
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
                            <span class="text-primary text-uppercase font-small-3 font-weight-bold">Provider diagnostics</span>
                            <h2 class="mb-50">API request logs</h2>
                            <p class="text-muted mb-0">Review AutoSync requests, responses, failures, and response times. Sensitive credentials are redacted.</p>
                        </div>
                        <a href="{{ route('admin.autosync.api-request.clear') }}" onsubmit="confirm('This will truncate all logs, are you sure?')" class="btn btn-danger mt-1 mt-md-0"><i class="bx bx-cog mr-50"></i>Clear Log</a>
                    </div>
                    <div class="row mt-2">
                        @foreach ([['Total requests', $summary->total, 'primary'], ['Successful', $summary->successful, 'success'], ['Failed', $summary->failed, 'danger'], ['Average response', $summary->average_duration === null ? '-' : number_format((float) $summary->average_duration) . ' ms', 'info']] as [$label, $value, $color])
                            <div class="col-6 col-lg-3 mb-1 mb-lg-0">
                                <div class="border rounded p-1 h-100">
                                    <small class="text-muted d-block">{{ $label }}</small>
                                    <strong class="font-medium-4 text-{{ $color }}">{{ is_numeric($value) ? number_format((int) $value) : $value }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                    <div class="mb-1 mb-lg-0"><h4 class="mb-25">Request and response history</h4><small>Newest provider exchanges appear first.</small></div>
                    <form method="GET" class="form-inline">
                        <select name="operation" class="form-control form-control-sm mr-50">
                            <option value="">All operations</option>
                            @foreach($operations as $operation)
                                <option value="{{ $operation }}" @selected(request('operation') === $operation)>{{ str($operation)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        <select name="api_status" class="form-control form-control-sm mr-50">
                            <option value="">All responses</option>
                            <option value="success" @selected(request('api_status') === 'success')>Successful</option>
                            <option value="failed" @selected(request('api_status') === 'failed')>Failed</option>
                        </select>
                        <button class="btn btn-sm btn-primary mr-50">Filter</button>
                        @if(request()->hasAny(['operation', 'api_status']))
                            <a href="{{ route('admin.autosync.api-logs.index') }}" class="btn btn-sm btn-light-secondary">Clear</a>
                        @endif
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Operation</th><th>Transaction</th><th>HTTP</th><th>Duration</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                            @forelse($apiLogs as $log)
                                <tr>
                                    <td>{{ $apiLogs->firstItem() + $loop->index }}</td>
                                    <td><strong>{{ str($log->operation)->replace('_', ' ')->title() }}</strong><small class="d-block text-muted text-truncate" style="max-width:300px">{{ $log->method }} {{ $log->endpoint }}</small></td>
                                    <td>{{ $log->transaction_id ?: '-' }}<small class="d-block text-muted">{{ $log->customer?->user?->email }}</small></td>
                                    @php
                                        $body = is_array($log->response_body)
                                            ? $log->response_body
                                            : json_decode($log->response_body ?? '{}', true);

                                        $providerStatus = strtolower(
                                            (string) data_get($body, 'data.transaction.status', 'failed')
                                        );

                                        [$badge, $label] = match ($providerStatus) {
                                            'successful' => ['success', 'Successful'],
                                            'pending', 'processing', 'initiated' => ['warning', 'Pending'],
                                            default => ['danger', 'Failed'],
                                        };
                                    @endphp

                                    <td>
                                        <span class="badge badge-light-{{ $badge }}">
                                            {{ $label }}
                                        </span>

                                        <small class="d-block text-muted">
                                            HTTP {{ $log->response_status ?: '-' }}
                                        </small>
                                    </td>
                                    <td>{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '-' }}</td>
                                    <td>{{ $log->created_at->format('M j, Y') }}<small class="d-block text-muted">{{ $log->created_at->format('g:i:s A') }}</small></td>
                                    <td class="text-left">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light-primary"
                                            data-toggle="modal"
                                            data-target="#logModal{{ $log->id }}"
                                        >
                                            <i class="bx bx-show"></i> View
                                        </button>

                                        <div
                                            class="modal fade"
                                            id="logModal{{ $log->id }}"
                                            tabindex="-1"
                                            role="dialog"
                                            aria-hidden="true"
                                        >
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h5 class="modal-title mb-0">
                                                                {{ str($log->operation)->replace('_', ' ')->title() }}
                                                            </h5>
                                                            <small class="text-muted">
                                                                {{ $log->method }} {{ $log->endpoint }}
                                                            </small>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            class="close"
                                                            data-dismiss="modal"
                                                        >
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>

                                                    <div class="modal-body">

                                                        @if($log->error)
                                                            <div class="alert alert-danger">
                                                                {{ $log->error }}
                                                            </div>
                                                        @endif

                                                        <div class="row mb-2">
                                                            <div class="col-md-6">
                                                                <strong>Transaction</strong><br>
                                                                {{ $log->transaction_id ?: '-' }}
                                                            </div>

                                                            <div class="col-md-6">
                                                                <strong>Customer</strong><br>
                                                                {{ $log->customer?->user?->email ?? '-' }}
                                                            </div>

                                                            <div class="col-md-4 mt-1">
                                                                <strong>Status</strong><br>

                                                                <span class="badge badge-light-{{ $badge }}">
                                                                    {{ $label }}
                                                                </span>

                                                                <small class="d-block text-muted">
                                                                    HTTP {{ $log->response_status ?: '-' }}
                                                                </small>
                                                            </div>

                                                            <div class="col-md-4 mt-1">
                                                                <strong>Duration</strong><br>
                                                                {{ $log->duration_ms ? $log->duration_ms.' ms' : '-' }}
                                                            </div>

                                                            <div class="col-md-4 mt-1">
                                                                <strong>Created</strong><br>
                                                                {{ $log->created_at->format('M j, Y g:i:s A') }}
                                                            </div>
                                                        </div>

                                                        <hr>

                                                        <div class="mb-2">
                                                            <h6>Request Headers</h6>

                                                            <pre class="bg-light rounded p-2 text-wrap">{{ json_encode($log->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>

                                                        <div class="mb-2">
                                                            <h6>Request Payload</h6>

                                                            <pre class="bg-light rounded p-2 text-wrap">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>

                                                        <div class="mb-2">
                                                            <h6>Response Headers</h6>

                                                            <pre class="bg-light rounded p-2 text-wrap">{{ json_encode($log->response_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>

                                                        <div>
                                                            <h6>Response Body</h6>

                                                            <pre class="bg-light rounded p-2 text-wrap">{{ json_encode($log->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">
                                                        <button
                                                            type="button"
                                                            class="btn btn-light-secondary"
                                                            data-dismiss="modal"
                                                        >
                                                            Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No AutoSync API calls have been logged.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($apiLogs->hasPages())<div class="card-footer">{{ $apiLogs->links() }}</div>@endif
            </section>
        </div>
    </div>
</div>
@endsection
