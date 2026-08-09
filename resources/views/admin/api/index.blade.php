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
                <div class="card-body">
                    <h3 class="mb-25">API Providers</h3>
                    <p class="text-muted mb-0">Manage provider configuration, webhook endpoints and balances.</p>
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
