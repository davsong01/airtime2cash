@php
    $settings = getSettings();
    $currency = $settings->currency ?? 'NGN';
    $status = strtolower($transaction->status ?: 'pending');
    $statusClass = match ($status) {
        'approved' => 'success',
        'declined' => 'danger',
        'pending', 'processing' => 'warning',
        default => 'secondary',
    };
    $statusIcon = match ($statusClass) {
        'success' => 'bx-check-circle',
        'danger' => 'bx-x-circle',
        'warning' => 'bx-time-five',
        default => 'bx-info-circle',
    };
    $statusTitle = match ($status) {
        'approved' => 'Conversion approved',
        'declined' => 'Conversion declined',
        default => 'Conversion under review',
    };
    $statusMessage = match ($status) {
        'approved' => 'Your airtime conversion has been reviewed and the payout approved.',
        'declined' => 'This conversion could not be completed. Review the reason provided below.',
        default => 'Your request has been received and is awaiting review. You can return here for updates.',
    };
    $productName = $transaction->product?->display_name ?: $transaction->product?->name ?: 'Airtime conversion';
    $productInitial = str($productName)->substr(0, 2)->upper();
    $transferMode = $transaction->transfer_mode === 'auto_share' ? 'Auto Transfer' : 'Manual Transfer';
    $createdAt = $transaction->created_at;
    $completedAt = $transaction->completed_at ?? $transaction->updated_at ?? null;
    $completedLabel = $completedAt ? $completedAt->format('M j, Y · g:i A') : 'Awaiting completion';
    $isCompleted = filled($transaction->completed_at) || in_array($status, ['approved', 'declined', 'successful', 'failed'], true);
@endphp

@extends('sneat.layouts.app')
@section('title', 'Conversion ' . $transaction->transaction_id)

@section('page-css')
    <style>
        .a2c-status-shell { max-width: 1080px; margin-inline: auto; }
        .a2c-status-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .68);
            border-radius: 1rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, .94), rgba(255, 255, 255, .78));
            box-shadow: 0 1.5rem 3.5rem rgba(34, 48, 62, .1), 0 .35rem .9rem rgba(34, 48, 62, .05);
            -webkit-backdrop-filter: blur(20px) saturate(145%);
            backdrop-filter: blur(20px) saturate(145%);
        }
        .a2c-status-hero {
            position: relative;
            padding: 1.6rem;
            border-bottom: 1px solid var(--bs-border-color);
            background: radial-gradient(circle at 90% 0, rgba(var(--bs-primary-rgb), .14), transparent 38%);
        }
        .a2c-status-mark { display: inline-flex; width: 56px; height: 56px; flex: 0 0 56px; align-items: center; justify-content: center; border-radius: 1rem; box-shadow: inset 0 0 0 1px currentColor, 0 .6rem 1.3rem rgba(34, 48, 62, .08); }
        .a2c-status-pill { display: inline-flex; padding: .42rem .75rem; align-items: center; gap: .42rem; border-radius: 50rem; font-size: .74rem; font-weight: 700; }
        .a2c-status-pill::before { width: .42rem; height: .42rem; border-radius: 50%; background: currentColor; content: ""; opacity: .75; }
        .a2c-reference { display: flex; padding: .65rem .75rem; align-items: center; gap: .55rem; border: 1px solid var(--bs-border-color); border-radius: .55rem; background: rgba(255,255,255,.45); }
        .a2c-reference code { min-width: 0; overflow: hidden; color: var(--bs-heading-color); font-size: .8rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .a2c-section { padding: 1.5rem; }
        .a2c-section + .a2c-section { border-top: 1px solid var(--bs-border-color); }
        .a2c-network-mark { display: inline-flex; width: 48px; height: 48px; flex: 0 0 48px; align-items: center; justify-content: center; border-radius: .8rem; background: linear-gradient(145deg, rgba(var(--bs-primary-rgb), .16), rgba(var(--bs-primary-rgb), .07)); color: var(--bs-primary); font-weight: 800; }
        .a2c-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); overflow: hidden; border: 1px solid var(--bs-border-color); border-radius: .8rem; }
        .a2c-detail-item { min-width: 0; padding: .9rem 1rem; border-bottom: 1px solid var(--bs-border-color); }
        .a2c-detail-item:nth-child(odd) { border-right: 1px solid var(--bs-border-color); }
        .a2c-detail-item:nth-last-child(-n + 2) { border-bottom: 0; }
        .a2c-detail-label { display: block; margin-bottom: .3rem; color: var(--bs-secondary-color); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
        .a2c-detail-value { display: block; overflow-wrap: anywhere; color: var(--bs-heading-color); font-size: .88rem; font-weight: 500; }
        .a2c-financial-card { height: 100%; border: 1px solid var(--bs-border-color); border-radius: .9rem; background: rgba(255,255,255,.38); }
        .a2c-financial-row { display: flex; padding: .72rem 0; align-items: center; justify-content: space-between; gap: 1rem; }
        .a2c-financial-row + .a2c-financial-row { border-top: 1px dashed var(--bs-border-color); }
        .a2c-financial-row strong { color: var(--bs-heading-color); }
        .a2c-financial-total { margin-top: .25rem; padding-top: 1rem; border-top-style: solid !important; font-size: 1rem; }
        .a2c-timeline { position: relative; margin: 0; padding: 0; list-style: none; }
        .a2c-timeline::before { position: absolute; width: 2px; background: var(--bs-border-color); content: ""; inset: 20px auto 20px 17px; }
        .a2c-timeline-item { position: relative; display: flex; padding-bottom: 1.2rem; align-items: flex-start; gap: .85rem; }
        .a2c-timeline-item:last-child { padding-bottom: 0; }
        .a2c-timeline-dot { position: relative; z-index: 1; display: inline-flex; width: 36px; height: 36px; flex: 0 0 36px; align-items: center; justify-content: center; border: 2px solid var(--bs-border-color); border-radius: 50%; background: var(--bs-paper-bg); color: var(--bs-secondary-color); }
        .a2c-timeline-item.is-complete .a2c-timeline-dot { border-color: var(--bs-success); background: var(--bs-success); color: #fff; }
        .a2c-timeline-item.is-current .a2c-timeline-dot { border-color: var(--bs-warning); background: rgba(var(--bs-warning-rgb), .14); color: var(--bs-warning); box-shadow: 0 0 0 .25rem rgba(var(--bs-warning-rgb), .1); }
        .a2c-timeline-item.is-declined .a2c-timeline-dot { border-color: var(--bs-danger); background: rgba(var(--bs-danger-rgb), .14); color: var(--bs-danger); }
        .a2c-timeline-copy strong, .a2c-timeline-copy small { display: block; }
        .a2c-timeline-copy strong { margin-top: .1rem; color: var(--bs-heading-color); font-size: .85rem; }
        .a2c-timeline-copy small { margin-top: .18rem; color: var(--bs-secondary-color); line-height: 1.45; }
        [data-bs-theme="dark"] .a2c-status-card { border-color: rgba(255,255,255,.08); background: linear-gradient(145deg, rgba(47,51,73,.92), rgba(38,41,60,.8)); box-shadow: 0 1.5rem 3.5rem rgba(0,0,0,.28); }
        [data-bs-theme="dark"] .a2c-reference, [data-bs-theme="dark"] .a2c-financial-card { background: rgba(20,22,34,.24); }
        @media (min-width: 992px) { .a2c-border-end-lg { border-right: 1px solid var(--bs-border-color); } }
        @media (max-width: 575.98px) {
            .a2c-status-hero, .a2c-section { padding: 1.1rem; }
            .a2c-detail-grid { grid-template-columns: minmax(0, 1fr); }
            .a2c-detail-item, .a2c-detail-item:nth-child(odd), .a2c-detail-item:nth-last-child(-n + 2) { border-right: 0; border-bottom: 1px solid var(--bs-border-color); }
            .a2c-detail-item:last-child { border-bottom: 0; }
            .a2c-status-mark { width: 48px; height: 48px; flex-basis: 48px; }
        }
    </style>
@endsection

@section('content')
    <div class="a2c-status-shell">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <a href="{{ route('customer.airtime2cash.transaction.history') }}" class="btn btn-sm btn-label-secondary">
                <i class="bx bx-left-arrow-alt me-1"></i> Conversion history
            </a>
            @if($status === 'approved')
                <a href="{{ route('airtime2cash.transaction.receipt.download', $transaction->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                    <i class="bx bx-download me-1"></i> Download receipt
                </a>
            @endif
        </div>

        @include('sneat.layouts.alerts')

        <article class="card a2c-status-card">
            <header class="a2c-status-hero">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="a2c-status-mark bg-label-{{ $statusClass }}"><i class="bx {{ $statusIcon }} fs-3"></i></span>
                        <div class="min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="a2c-status-pill bg-label-{{ $statusClass }}">{{ str($status)->title() }}</span>
                                <small class="text-muted">{{ $createdAt->format('M j, Y · g:i A') }}</small>
                            </div>
                            <h3 class="mb-1">{{ $statusTitle }}</h3>
                            <p class="text-muted mb-0">{{ $statusMessage }}</p>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <small class="d-block text-muted mb-1">Amount to receive</small>
                        <div class="fs-2 fw-bold text-heading">{{ $currency }}{{ number_format($transaction->amount_paid, 2) }}</div>
                    </div>
                </div>

                <div class="a2c-reference mt-4">
                    <i class="bx bx-hash text-primary"></i>
                    <code id="conversion-reference">{{ $transaction->transaction_id }}</code>
                    <button type="button" class="btn btn-sm btn-icon btn-label-secondary ms-auto" id="copy-conversion-reference" title="Copy reference" aria-label="Copy transaction reference">
                        <i class="bx bx-copy"></i>
                    </button>
                </div>
            </header>

            @if($status === 'declined' && filled($transaction->decline_reason))
                <div class="px-3 px-md-4 pt-4">
                    <div class="alert alert-danger mb-0 d-flex gap-2" role="alert">
                        <i class="bx bx-error-circle fs-5"></i>
                        <div><strong class="d-block mb-1">Reason for decline</strong>{{ $transaction->decline_reason }}</div>
                    </div>
                </div>
            @endif

            <section class="a2c-section">
                <div class="row g-4">
                    <div class="col-lg-7 a2c-border-end-lg">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="a2c-network-mark">{{ $productInitial }}</span>
                            <div>
                                <small class="text-muted">Network</small>
                                <h5 class="mb-0">{{ $productName }}</h5>
                            </div>
                        </div>

                        <div class="a2c-detail-grid">
                            <div class="a2c-detail-item"><span class="a2c-detail-label">Transfer method</span><span class="a2c-detail-value">{{ $transferMode }}</span></div>
                            <div class="a2c-detail-item"><span class="a2c-detail-label">Sending number</span><span class="a2c-detail-value">{{ $transaction->phone_numbers ?: 'Not provided' }}</span></div>
                            <div class="a2c-detail-item"><span class="a2c-detail-label">Payout destination</span><span class="a2c-detail-value">{{ $transaction->payment_method ?: 'Not provided' }}</span></div>
                            <div class="a2c-detail-item"><span class="a2c-detail-label">Submitted</span><span class="a2c-detail-value">{{ $createdAt->format('M j, Y · g:i A') }}</span></div>
                            @if($transaction->payment_method === 'Transfer to Bank Account')
                                <div class="a2c-detail-item"><span class="a2c-detail-label">Bank</span><span class="a2c-detail-value">{{ $transaction->bank_name ?: 'Not provided' }}</span></div>
                                <div class="a2c-detail-item"><span class="a2c-detail-label">Account</span><span class="a2c-detail-value">{{ $transaction->account_name }} · {{ $transaction->account_number }}</span></div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="a2c-financial-card p-3 p-md-4">
                            <div class="d-flex align-items-center gap-2 mb-2"><span class="avatar-initial rounded bg-label-primary p-2"><i class="bx bx-wallet"></i></span><h5 class="mb-0">Conversion summary</h5></div>
                            <div class="a2c-financial-row"><span class="text-muted">Airtime amount</span><strong>{{ $currency }}{{ number_format($transaction->total_amount, 2) }}</strong></div>
                            <div class="a2c-financial-row"><span class="text-muted">Charge rate</span><strong>{{ number_format($transaction->charge_rate, 2) }}%</strong></div>
                            <div class="a2c-financial-row"><span class="text-muted">Conversion charge</span><strong class="text-danger">-{{ $currency }}{{ number_format($transaction->amount_charged, 2) }}</strong></div>
                            <div class="a2c-financial-row a2c-financial-total"><span>You receive</span><strong class="text-success">{{ $currency }}{{ number_format($transaction->amount_paid, 2) }}</strong></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="a2c-section">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <span class="text-uppercase text-muted fw-semibold small">Progress</span>
                        <h5 class="mt-2 mb-1">Conversion timeline</h5>
                        <p class="text-muted small mb-0">Follow this request from submission through payout.</p>
                    </div>
                    <div class="col-lg-7">
                        <ol class="a2c-timeline">
                            <li class="a2c-timeline-item is-complete">
                                <span class="a2c-timeline-dot"><i class="bx bx-check"></i></span>
                                <span class="a2c-timeline-copy"><strong>Request submitted</strong><small>We received your {{ strtolower($transferMode) }} request.</small></span>
                            </li>
                            <li class="a2c-timeline-item {{ $status === 'declined' ? 'is-declined' : ($status === 'approved' ? 'is-complete' : 'is-current') }}">
                                <span class="a2c-timeline-dot"><i class="bx {{ $status === 'declined' ? 'bx-x' : ($status === 'approved' ? 'bx-check' : 'bx-time-five') }}"></i></span>
                                <span class="a2c-timeline-copy"><strong>{{ $status === 'declined' ? 'Review declined' : 'Airtime review' }}</strong><small>{{ $status === 'pending' ? 'Your conversion is currently being reviewed.' : ($status === 'approved' ? 'Your airtime transfer was successfully reviewed.' : 'The review could not be completed.') }}</small></span>
                            </li>
                            <li class="a2c-timeline-item {{ $status === 'approved' ? 'is-complete' : '' }}">
                                <span class="a2c-timeline-dot"><i class="bx {{ $status === 'approved' ? 'bx-check' : 'bx-wallet' }}"></i></span>
                                <span class="a2c-timeline-copy"><strong>Payout approved</strong><small>{{ $status === 'approved' ? 'The payout for this conversion has been approved.' : 'This step follows a successful review.' }}</small></span>
                            </li>
                            <li class="a2c-timeline-item {{ $isCompleted ? 'is-complete' : 'is-current' }}">
                                <span class="a2c-timeline-dot"><i class="bx {{ $isCompleted ? 'bx-check' : 'bx-time-five' }}"></i></span>
                                <span class="a2c-timeline-copy"><strong>Completed</strong><small>{{ $completedAt ? 'Completed at ' . $completedLabel : 'The transaction has not been completed yet.' }}</small></span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>
        </article>
    </div>
@endsection

@section('page-script')
    <script>
        document.getElementById('copy-conversion-reference')?.addEventListener('click', async function () {
            const reference = document.getElementById('conversion-reference').textContent.trim();
            try {
                await navigator.clipboard.writeText(reference);
                this.innerHTML = '<i class="bx bx-check"></i>';
                setTimeout(() => this.innerHTML = '<i class="bx bx-copy"></i>', 1400);
            } catch (error) {
                window.prompt('Copy transaction reference:', reference);
            }
        });
    </script>
@endsection
