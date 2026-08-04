@extends('sneat.layouts.app')
@section('title', 'API Settings')

@section('content')
    @php
        $customer = auth()->user()->customer;
        $referralLink = url('/register') . '?referral=' . auth()->user()->username;
    @endphp

    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Developer tools',
        'title' => 'API Settings',
        'subtitle' => 'Generate and copy your API keys when API access is active.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Referral link</div>
                    <div class="p-3 rounded bg-light text-break">{{ $referralLink }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">KYC status</div>
                    <h5 class="mb-0">{{ ucfirst(getFinalKycStatus(auth()->user()->customer->id)) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">API access</div>
                    <h5 class="mb-0">{{ $customer->api_access == 'active' ? 'Active' : 'Inactive' }}</h5>
                </div>
            </div>
        </div>

        @if ($customer->api_access == 'active')
            <div class="col-12">
                <div class="card customer-form-card">
                    <div class="card-body customer-modern-form">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                            <div>
                                <h5 class="mb-1">Generate keys</h5>
                                <p class="text-muted mb-0">Generate fresh keys and copy the values you need for integration.</p>
                            </div>
                            <button class="btn btn-primary customer-form-submit api-key-btn" type="button"><i class="bx bx-key me-1"></i> Generate New API Keys</button>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <div class="input-group">
                                        <input class="form-control" id="api-key-field" value="{{ auth()->user()->api_key }}" readonly>
                                        <button class="btn btn-outline-secondary copy" type="button" data-target="#api-key-field">Copy</button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Public Key</label>
                                    <div class="input-group">
                                        <input class="form-control" id="public-key-field" value="" readonly>
                                        <button class="btn btn-outline-secondary copy" type="button" data-target="#public-key-field">Copy</button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Secret Key</label>
                                    <div class="input-group">
                                        <input class="form-control" id="secret-key-field" value="" readonly>
                                        <button class="btn btn-outline-secondary copy" type="button" data-target="#secret-key-field">Copy</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('page-script')
    <script>
        $('.api-key-btn').click(function() {
            $.ajax({
                url: '{{ route('profile.keys') }}',
                success: res => {
                    let data = res.data;
                    $('#public-key-field').val(data.public);
                    $('#secret-key-field').val(data.secret);
                },
            });
        });

        $('.copy').click(function () {
            const target = $(this).data('target');
            const input = document.querySelector(target);
            if (!input) return;
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
        });
    </script>
@endsection
