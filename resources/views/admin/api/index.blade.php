@extends('layouts.app')

@section('title', 'API Providers')

@section('page-css')
    <style>
        .provider-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:1.25rem; }
        .provider-card { border:1px solid #e8e8e8; border-radius:.75rem; box-shadow:0 .35rem 1rem rgba(0,0,0,.04); }
        .provider-card .card-body { padding:1.25rem; }
        .provider-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
        .provider-name { font-size:1.05rem; font-weight:700; color:#263446; }
        .provider-slug { color:#8a94a6; font-size:.78rem; margin-top:.15rem; }
        .provider-status { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; }
        .provider-status.active { color:#168f5b; background:rgba(40,199,111,.1); }
        .provider-status.inactive { color:#d94c4c; background:rgba(234,84,85,.1); }
        .provider-webhook { margin-top:1rem; }
        .provider-webhook-label { font-size:.72rem; font-weight:700; color:#6c757d; text-transform:uppercase; margin-bottom:.45rem; }
        .provider-webhook .form-control { background:#f8f9fa; font-size:.8rem; }
        .provider-actions { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:1rem; padding-top:1rem; border-top:1px solid #eee; }
        .provider-balance { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .65rem; border-radius:.4rem; background:#f4f5f7; font-weight:700; }
        .provider-balance.muted { color:#6c757d; font-weight:600; }
        .provider-summary-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1.25rem; }
        .provider-summary-card { padding:1rem 1.05rem; border-radius:.85rem; border:1px solid #e7edf5; background:linear-gradient(145deg, #ffffff, #f7fbff); box-shadow:0 .35rem 1rem rgba(0,0,0,.04); }
        .provider-summary-label { display:block; font-size:.72rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:#7a869a; }
        .provider-summary-value { display:block; margin-top:.2rem; font-size:1.45rem; font-weight:800; color:#24364b; line-height:1.1; }
        .provider-summary-meta { display:block; margin-top:.35rem; color:#6c7a89; font-size:.8rem; }
        .provider-summary-card.is-good { border-color:rgba(40,199,111,.18); background:linear-gradient(145deg, rgba(255,255,255,.98), rgba(240,255,246,.9)); }
        .provider-summary-card.is-warning { border-color:rgba(255,193,7,.2); background:linear-gradient(145deg, rgba(255,255,255,.98), rgba(255,250,236,.9)); }
        .provider-summary-card.is-info { border-color:rgba(0,123,255,.18); background:linear-gradient(145deg, rgba(255,255,255,.98), rgba(240,247,255,.9)); }
        .provider-summary-card.is-danger { border-color:rgba(220,53,69,.18); background:linear-gradient(145deg, rgba(255,255,255,.98), rgba(255,241,243,.9)); }
        .provider-summary-chip { display:inline-flex; align-items:center; gap:.35rem; margin-top:.7rem; padding:.33rem .6rem; border-radius:999px; font-size:.7rem; font-weight:800; }
        .provider-summary-chip.unstable { color:#991b1b; background:rgba(239,68,68,.12); }
        .provider-summary-chip.degraded { color:#111827; background:rgba(17,24,39,.12); }
        .provider-summary-chip.stable { color:#9a6700; background:rgba(245,158,11,.14); }
        .provider-summary-chip.healthy { color:#166534; background:rgba(34,197,94,.12); }
        .provider-availability { margin-top:1rem; padding:1rem; border-radius:.75rem; background:#f8fbff; border:1px solid #e4edf7; }
        .provider-availability-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.8rem; }
        .provider-availability-title { font-size:.75rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; color:#6c7a89; }
        .provider-availability-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.34rem .65rem; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; }
        .provider-availability-badge.unstable { color:#991b1b; background:rgba(239,68,68,.12); }
        .provider-availability-badge.degraded { color:#111827; background:rgba(17,24,39,.12); }
        .provider-availability-badge.stable { color:#9a6700; background:rgba(245,158,11,.15); }
        .provider-availability-badge.healthy { color:#166534; background:rgba(34,197,94,.14); }
        .provider-availability-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.75rem; }
        .provider-availability-item { padding:.7rem .8rem; border-radius:.65rem; background:#fff; border:1px solid #edf2f7; }
        .provider-availability-item span { display:block; font-size:.68rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#8a94a6; }
        .provider-availability-item strong { display:block; margin-top:.2rem; color:#263446; font-size:.95rem; }
        .provider-availability-empty { color:#6c757d; font-size:.82rem; }
        @media (max-width: 991.98px) { .provider-grid { grid-template-columns:1fr; } }
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
                        <li class="breadcrumb-item active">API Providers</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <div class="card mb-2">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="mb-25">API Providers</h3>
                        <p class="text-muted mb-0">Manage provider configuration, webhook endpoints and balances.</p>
                    </div>
                    <a href="{{ $monitorUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                        <i class="bx bx-refresh me-25"></i>
                        Refresh monitor
                    </a>
                </div>
            </div>

            <div class="provider-summary-grid">
                <div class="provider-summary-card {{ ($availabilitySummary['average_score'] ?? null) === null ? 'is-warning' : ((($availabilitySummary['average_score'] ?? null) < 26) ? 'is-danger' : ((($availabilitySummary['average_score'] ?? null) < 51) ? 'is-info' : ((($availabilitySummary['average_score'] ?? null) < 76) ? 'is-warning' : 'is-good'))) }}">
                    <span class="provider-summary-label">Average availability</span>
                    <span class="provider-summary-value">{{ $availabilitySummary['average_score'] !== null ? $availabilitySummary['average_score'] . '%' : 'N/A' }}</span>
                    <span class="provider-summary-meta">{{ number_format((int) $availabilitySummary['providers']) }} providers in monitor</span>
                    <span class="provider-summary-chip {{ ($availabilitySummary['average_score'] ?? null) === null ? 'stable' : ((($availabilitySummary['average_score'] ?? null) < 26) ? 'unstable' : ((($availabilitySummary['average_score'] ?? null) < 51) ? 'degraded' : ((($availabilitySummary['average_score'] ?? null) < 76) ? 'stable' : 'healthy'))) }}">
                        <i class="bx bx-pulse"></i>
                        {{ ($availabilitySummary['average_score'] ?? null) === null ? 'Not checked' : ((($availabilitySummary['average_score'] ?? null) < 26) ? 'Unstable' : ((($availabilitySummary['average_score'] ?? null) < 51) ? 'Degraded' : ((($availabilitySummary['average_score'] ?? null) < 76) ? 'Stable' : 'Healthy'))) }}
                    </span>
                </div>
                <div class="provider-summary-card is-info">
                    <span class="provider-summary-label">Providers checked</span>
                    <span class="provider-summary-value">{{ number_format((int) $availabilitySummary['checked_providers']) }}</span>
                    <span class="provider-summary-meta">{{ number_format((int) $availabilitySummary['healthy_providers']) }} currently healthy</span>
                </div>
                <div class="provider-summary-card is-good">
                    <span class="provider-summary-label">Successful vs failed</span>
                    <span class="provider-summary-value">{{ number_format((int) $availabilitySummary['successful_transactions']) }} / {{ number_format((int) $availabilitySummary['failed_transactions']) }}</span>
                    <span class="provider-summary-meta">Across {{ number_format((int) $availabilitySummary['availability_check_transactions_count']) }} checked transactions</span>
                </div>
                <div class="provider-summary-card {{ $availabilitySummary['last_checked_at'] ? 'is-warning' : 'is-danger' }}">
                    <span class="provider-summary-label">Last monitor run</span>
                    <span class="provider-summary-value">{{ $availabilitySummary['last_checked_at'] ? $availabilitySummary['last_checked_at']->format('M j, Y') : 'Never' }}</span>
                    <span class="provider-summary-meta">{{ $availabilitySummary['last_checked_at'] ? $availabilitySummary['last_checked_at']->format('g:i A') : 'No availability data yet' }}</span>
                </div>
            </div>

            <div class="provider-grid">
                @forelse($apis as $api)
                    <div class="card provider-card mb-0">
                        <div class="card-body">
                            <div class="provider-head">
                                <div>
                                    <div class="provider-name">{{ $api->name }}</div>

                                    @if($api->slug)
                                        <div class="provider-slug">{{ $api->slug }}</div>
                                    @endif
                                </div>

                                <span class="provider-status {{ $api->status === 'active' ? 'active' : 'inactive' }}">
                                    <i class="bx {{ $api->status === 'active' ? 'bx-check-circle' : 'bx-x-circle' }}"></i>
                                    {{ ucfirst($api->status) }}
                                </span>
                            </div>

                            <div class="provider-availability">
                                <div class="provider-availability-head">
                                    <div class="provider-availability-title">Availability monitor</div>
                                    @if($api->availability_checked_at && $api->availability_status_class)
                                        <span class="provider-availability-badge {{ $api->availability_status_class }}">
                                            <i class="bx bx-pulse"></i>
                                            {{ $api->availability_status_label }}
                                        </span>
                                    @endif
                                </div>

                                <div class="provider-availability-grid">
                                    <div class="provider-availability-item">
                                        <span>Availability score</span>
                                        <strong>{{ $api->availability_score !== null ? $api->availability_score . '%' : 'N/A' }}</strong>
                                    </div>
                                    <div class="provider-availability-item">
                                        <span>Checked transactions</span>
                                        <strong>{{ number_format((int) ($api->availability_check_transactions_count ?? 0)) }}</strong>
                                    </div>
                                    <div class="provider-availability-item">
                                        <span>Successful</span>
                                        <strong>{{ number_format((int) ($api->successful_transactions ?? 0)) }}</strong>
                                    </div>
                                    <div class="provider-availability-item">
                                        <span>Failed</span>
                                        <strong>{{ number_format((int) ($api->failed_transactions ?? 0)) }}</strong>
                                    </div>
                                </div>

                                <div class="provider-availability-empty mt-75">
                                    Last checked:
                                    <strong>
                                        {{ $api->availability_checked_at ? $api->availability_checked_at->format('M j, Y g:i A') : 'Not checked yet' }}
                                    </strong>
                                </div>
                            </div>

                            <div class="provider-webhook">
                                <div class="provider-webhook-label">
                                    <i class="bx bx-link-alt"></i> Webhook URL
                                </div>

                                <div class="input-group input-group-sm">
                                    <input
                                        type="text"
                                        id="webhook-url-{{ $api->id }}"
                                        class="form-control"
                                        value="{{ route('log.provider.webhook', $api->id) }}"
                                        readonly
                                    >

                                    <div class="input-group-append">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            onclick="copyWebhookUrl('webhook-url-{{ $api->id }}', this)"
                                        >
                                            <i class="bx bx-copy"></i>
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <small class="text-muted d-block mt-50">
                                    Add this URL as the callback or webhook endpoint in the provider dashboard.
                                </small>
                            </div>

                            @if(hasAccess('api.edit') || hasAccess('api.balance'))
                                <div class="provider-actions">
                                    @if(hasAccess('api.edit'))
                                        <a href="{{ route('api.edit', $api->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bx bx-edit-alt mr-25"></i>
                                            View / Edit
                                        </a>
                                    @endif

                                    @if(hasAccess('api.balance'))
                                        <button
                                            type="button"
                                            id="api-{{ $api->id }}"
                                            class="btn btn-sm btn-outline-info"
                                            onclick="getBalance('{{ $api->id }}')"
                                        >
                                            <span id="icon-{{ $api->id }}">
                                                <i class="bx bx-refresh"></i>
                                            </span>
                                            Refresh Balance
                                        </button>
                                        <span
                                            id="balance-{{ $api->id }}"
                                            class="provider-balance muted"
                                            data-provider-slug="{{ $api->slug }}"
                                        >
                                            {{ $api->balance !== null ? (($api->slug === 'paystack' ? 'NGN' : getSettings()->currency) . ' ' . number_format((float) $api->balance, 2)) : 'No cached balance yet' }}
                                        </span>
                                    @endif

                                    @if(($api->is_bank_transfer || $api->is_bank_verification) && hasAccess('api.edit'))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="syncBanks('{{ $api->id }}', this)"
                                        >
                                            <i class="bx bx-network-chart"></i>
                                            Sync Banks
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center text-muted py-4">
                            <i class="bx bx-plug font-large-1 d-block mb-1"></i>
                            No API providers found.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function copyWebhookUrl(id, button) {
    const input = document.getElementById(id);
    const original = button.innerHTML;

    navigator.clipboard.writeText(input.value).then(() => {
        button.innerHTML = '<i class="bx bx-check"></i> Copied';
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-success');

        setTimeout(() => {
            button.innerHTML = original;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 1500);
    });
}

function getBalance(id) {
    const button = $('#api-' + id);
    const icon = $('#icon-' + id);
    const balance = $('#balance-' + id);
    const providerSlug = balance.data('providerSlug');

    function setBalanceFailure(message) {
        balance
            .removeClass('muted')
            .addClass('text-danger')
            .text(message)
            .show();
    }

    button.prop('disabled', true);
    icon.html("<i class='bx bx-loader-alt bx-spin'></i>");

    $.ajax({
        url: "{{ url('admin/api-balance') }}/" + id,
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                const currency = data.currency || (providerSlug === 'paystack' ? 'NGN' : '{{ getSettings()->currency }}');
                balance
                    .removeClass('muted text-danger')
                    .text(currency + ' ' + Number(data.balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                    .show();
                return;
            }

            setBalanceFailure(data.message || 'Balance check failed.');
        },
        error: function (xhr) {
            setBalanceFailure(xhr.responseJSON?.message || 'Balance check failed.');
        },
        complete: function () {
            button.prop('disabled', false);
            icon.html("<i class='bx bx-refresh'></i>");
        }
    });
}

function syncBanks(id, button) {
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Syncing";

    $.ajax({
        url: "{{ url('admin/api') }}/" + id + "/pull-banks",
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function (data) {
            alert(data.message || 'Banks synced successfully.');
        },
        error: function (xhr) {
            alert(xhr.responseJSON?.message || 'Unable to sync banks.');
        },
        complete: function () {
            button.disabled = false;
            button.innerHTML = original;
        }
    });
}
</script>
@endsection
