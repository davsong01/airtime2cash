@php
    $settings = getSettings();
    $currency = $settings->currency;
    $status = strtolower($transaction->status ?: 'pending');
    $statusClass = match ($status) {
        'failed', 'declined' => 'danger',
        'delivered', 'successful', 'success', 'completed', 'approved' => 'success',
        'pending', 'initiated', 'processing' => 'warning',
        'refunded', 'reversed' => 'info',
        default => 'secondary',
    };
    $statusIcon = match ($statusClass) {
        'success' => 'bx-check-circle',
        'danger' => 'bx-x-circle',
        'warning' => 'bx-time-five',
        'info' => 'bx-refresh',
        default => 'bx-info-circle',
    };
    $systemReasons = ['LEVEL-UPGRADE', 'WALLET-FUNDING', 'ADMIN-DEBIT', 'ADMIN-CREDIT'];
    $serviceName = in_array($transaction->reason, $systemReasons, true)
        ? str($transaction->reason)->replace('-', ' ')->title()
        : ($transaction->product->display_name ?? $transaction->product->name ?? 'Transaction');
    $variationName = $transaction?->variation?->system_name;
    $canDownloadReceipt = !in_array($transaction->reason, ['LEVEL-UPGRADE', 'WALLET-FUNDING'], true)
        && $status !== 'failed';
    $extraInfo = [];
    $isWalletToBank = strtolower((string) ($transaction->reason ?? '')) === 'wallet to bank transfer';
    $chargeBreakdown = collect(normalizeChargeBreakdown($transaction->charge_breakdown ?? []))->filter(fn ($charge) => is_array($charge));
    $pendingNote = $status === 'pending'
        ? trim((string) ($transaction->api?->pending_note ?? ''))
        : '';

    if (filled($transaction->extra_info)) {
        $decodedExtraInfo = json_decode($transaction->extra_info, true);
        $extraInfo = is_array($decodedExtraInfo)
            ? array_filter($decodedExtraInfo, fn ($value, $key) => ! str_starts_with((string) $key, 'resolution_'), ARRAY_FILTER_USE_BOTH)
            : [];
    }
@endphp

@extends('sneat.layouts.app')
@section('title', 'Transaction ' . $transaction->transaction_id)

@section('page-css')
    <style>
        .transaction-detail-shell {
            max-width: 1040px;
            margin-inline: auto;
        }

        .transaction-detail-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 1rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, .93), rgba(255, 255, 255, .75));
            box-shadow: 0 1.5rem 3.5rem rgba(34, 48, 62, .1), 0 .35rem .9rem rgba(34, 48, 62, .05);
            -webkit-backdrop-filter: blur(20px) saturate(145%);
            backdrop-filter: blur(20px) saturate(145%);
        }

        .transaction-detail-hero {
            position: relative;
            padding: 1.5rem;
            border-bottom: 1px solid var(--bs-border-color);
            background:
                radial-gradient(circle at 90% 0, rgba(var(--bs-primary-rgb), .13), transparent 35%),
                rgba(255, 255, 255, .22);
        }

        .transaction-detail-hero::before {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .95), transparent);
            content: "";
            inset: 0 1.5rem auto;
        }

        .transaction-status-mark {
            display: inline-flex;
            width: 52px;
            height: 52px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: .9rem;
            box-shadow: inset 0 0 0 1px currentColor, 0 .5rem 1.2rem rgba(34, 48, 62, .08);
        }

        .transaction-status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .72rem;
            border-radius: 100rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .transaction-status-pill::before {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: currentColor;
            content: "";
            opacity: .72;
        }

        .transaction-total {
            color: var(--bs-heading-color);
            font-size: clamp(1.75rem, 4vw, 2.35rem);
            font-weight: 700;
            letter-spacing: -.035em;
            line-height: 1;
        }

        .transaction-detail-section {
            padding: 1.5rem;
        }

        .transaction-detail-section + .transaction-detail-section {
            border-top: 1px solid var(--bs-border-color);
        }

        .transaction-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
            border: 1px solid var(--bs-border-color);
            border-radius: .85rem;
            overflow: hidden;
            background: rgba(255, 255, 255, .34);
        }

        .transaction-detail-item {
            min-width: 0;
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .transaction-detail-item:nth-child(odd) {
            border-right: 1px solid var(--bs-border-color);
        }

        .transaction-detail-item:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .transaction-detail-label {
            display: block;
            margin-bottom: .3rem;
            color: var(--bs-secondary-color);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .transaction-detail-value {
            display: block;
            overflow-wrap: anywhere;
            color: var(--bs-heading-color);
            font-size: .9rem;
            font-weight: 500;
        }

        .transaction-financial-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .65rem 0;
        }

        .transaction-financial-row + .transaction-financial-row {
            border-top: 1px dashed var(--bs-border-color);
        }

        .transaction-financial-row--total {
            margin-top: .35rem;
            padding-top: 1rem;
            border-top-style: solid !important;
            color: var(--bs-heading-color);
            font-size: 1rem;
            font-weight: 700;
        }

        .transaction-copy-button {
            flex: 0 0 auto;
        }

        .wallet-bank-breakdown-card {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid rgba(37, 99, 235, .16);
            border-radius: .95rem;
            background: linear-gradient(180deg, rgba(248, 251, 255, .95), rgba(238, 245, 255, .95));
            box-shadow: 0 .9rem 2rem rgba(37, 99, 235, .08);
        }

        .wallet-bank-breakdown-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .8rem;
        }

        .wallet-bank-breakdown-title {
            margin: 0;
            color: var(--bs-heading-color);
            font-size: 1rem;
            font-weight: 700;
        }

        .wallet-bank-breakdown-subtitle {
            color: var(--bs-secondary-color);
            font-size: .78rem;
        }

        .wallet-bank-breakdown-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .6rem;
            border-radius: 100rem;
            background: rgba(var(--bs-primary-rgb), .12);
            color: var(--bs-primary);
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .wallet-bank-breakdown-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .46rem 0;
        }

        .wallet-bank-breakdown-row + .wallet-bank-breakdown-row {
            border-top: 1px dashed var(--bs-border-color);
        }

        .wallet-bank-breakdown-row span {
            color: var(--bs-secondary-color);
            font-size: .88rem;
        }

        .wallet-bank-breakdown-row strong {
            color: var(--bs-heading-color);
            font-size: .9rem;
        }

        .wallet-bank-breakdown-section {
            margin-top: .8rem;
            padding-top: .8rem;
            border-top: 1px solid rgba(var(--bs-border-color-rgb), .55);
        }

        .wallet-bank-breakdown-section-label {
            margin-bottom: .45rem;
            color: var(--bs-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .wallet-bank-breakdown-total {
            margin-top: .7rem;
            padding-top: .7rem;
            border-top: 1px solid rgba(var(--bs-border-color-rgb), .75);
            font-weight: 700;
        }

        [data-bs-theme="dark"] .transaction-detail-card {
            border-color: rgba(255, 255, 255, .08);
            background: linear-gradient(145deg, rgba(47, 51, 73, .9), rgba(38, 41, 60, .78));
            box-shadow: 0 1.5rem 3.5rem rgba(0, 0, 0, .28), 0 .35rem .9rem rgba(0, 0, 0, .15);
        }

        [data-bs-theme="dark"] .transaction-detail-hero,
        [data-bs-theme="dark"] .transaction-detail-grid {
            background-color: rgba(20, 22, 34, .24);
        }

        @media (min-width: 992px) {
            .border-end-lg {
                border-right: 1px solid var(--bs-border-color);
            }
        }

        @media (max-width: 575.98px) {
            .transaction-detail-hero,
            .transaction-detail-section {
                padding: 1.15rem;
            }

            .transaction-detail-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .transaction-detail-item,
            .transaction-detail-item:nth-child(odd),
            .transaction-detail-item:nth-last-child(-n + 2) {
                border-right: 0;
                border-bottom: 1px solid var(--bs-border-color);
            }

            .transaction-detail-item:last-child {
                border-bottom: 0;
            }
        }
    </style>
@endsection

@section('content')
    <div class="transaction-detail-shell">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <a href="{{ route('customer.transaction.history') }}" class="btn btn-sm btn-label-secondary">
                <i class="bx bx-left-arrow-alt me-1"></i> Transaction history
            </a>
            @if($canDownloadReceipt)
                <a href="{{ route('transaction.receipt.download', $transaction->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                    <i class="bx bx-download me-1"></i> Download receipt
                </a>
            @endif
        </div>

        @include('sneat.layouts.alerts')

        <div class="card transaction-detail-card">
            <div class="transaction-detail-hero">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="transaction-status-mark bg-label-{{ $statusClass }}">
                            <i class="bx {{ $statusIcon }} fs-3"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="transaction-status-pill bg-label-{{ $statusClass }}">{{ str($status)->title() }}</span>
                                <small class="text-muted">{{ $transaction->created_at->format('M j, Y · g:i A') }}</small>
                            </div>
                            <h4 class="mb-1">{{ $serviceName }}</h4>
                            @if($variationName)
                                <p class="text-muted mb-0">{{ $variationName }}</p>
                            @elseif($transaction->unique_element)
                                <p class="text-muted mb-0">{{ $transaction->unique_element }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="text-md-end">
                        <small class="d-block text-muted mb-2">Total amount</small>
                        <div class="transaction-total">{!! $currency !!}{{ number_format($transaction->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>
            @if(filled($pendingNote))
                <div class="px-4 pt-3">
                    <div class="alert alert-info mb-0" role="alert" style="border-radius: .95rem;">
                        <span>{{ $pendingNote }}</span>
                    </div>
                </div>
            @endif

            @if(filled($transaction->descr) || filled($transaction->extras) || filled($transaction->instruction))
                <div class="transaction-detail-section py-3">
                    @if(filled($transaction->descr))
                        <div class="d-flex gap-2 mb-{{ filled($transaction->extras) || filled($transaction->instruction) ? '2' : '0' }}">
                            <i class="bx bx-info-circle text-{{ $statusClass }} mt-25"></i>
                            <span>{{ ucfirst($transaction->descr) }}</span>
                        </div>
                    @endif
                    @if(filled($transaction->extras))
                        <div class="alert alert-{{ $statusClass }} mb-{{ filled($transaction->instruction) ? '2' : '0' }} py-2 px-3">{{ $transaction->extras }}</div>
                    @endif
                    @if(filled($transaction->instruction))
                        <small class="text-muted">{{ $transaction->instruction }}</small>
                    @endif
                </div>
            @endif

            <div class="row g-0">
                <div class="col-lg-7 border-end-lg">
                    <section class="transaction-detail-section h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-primary p-2"><i class="bx bx-receipt"></i></span>
                            <h5 class="mb-0">Transaction details</h5>
                        </div>

                        <div class="transaction-detail-grid">
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Transaction ID</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="transaction-id" class="transaction-detail-value flex-grow-1">{{ $transaction->transaction_id }}</span>
                                    <button id="copy-transaction-id" class="transaction-copy-button btn btn-xs btn-icon btn-label-primary" type="button" data-value="{{ $transaction->transaction_id }}" title="Copy transaction ID" aria-label="Copy transaction ID">
                                        <i class="bx bx-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Reference</span>
                                <span class="transaction-detail-value">{{ $transaction->reference_id ?: 'Not available' }}</span>
                            </div>
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Payment method</span>
                                <span class="transaction-detail-value">{{ filled($transaction->payment_method) ? str($transaction->payment_method)->replace('-', ' ')->title() : 'Not available' }}</span>
                            </div>
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Recipient / biller</span>
                                <span class="transaction-detail-value">{{ $transaction->unique_element ?: 'Not provided' }}</span>
                            </div>
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Phone</span>
                                <span class="transaction-detail-value">{{ $transaction->customer_phone ?: 'Not provided' }}</span>
                            </div>
                            <div class="transaction-detail-item">
                                <span class="transaction-detail-label">Email</span>
                                <span class="transaction-detail-value">{{ $transaction->customer_email ?: 'Not provided' }}</span>
                            </div>
                            @if($isWalletToBank)
                                <div class="transaction-detail-item">
                                    <span class="transaction-detail-label">Bank name</span>
                                    <span class="transaction-detail-value">{{ $transaction->bank?->bank_name ?: $transaction->bank_name ?: 'Not provided' }}</span>
                                </div>
                                <div class="transaction-detail-item">
                                    <span class="transaction-detail-label">Account name</span>
                                    <span class="transaction-detail-value">{{ $transaction->account_name ?: 'Not provided' }}</span>
                                </div>
                                <div class="transaction-detail-item">
                                    <span class="transaction-detail-label">Account number</span>
                                    <span class="transaction-detail-value">{{ $transaction->account_number ?: 'Not provided' }}</span>
                                </div>
                            @endif
                            @foreach($extraInfo as $key => $value)
                                <div class="transaction-detail-item">
                                    <span class="transaction-detail-label">{{ str($key)->replace(['_', '-'], ' ')->title() }}</span>
                                    <span class="transaction-detail-value">{{ is_scalar($value) ? ucfirst((string) $value) : json_encode($value) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="transaction-detail-section h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-success p-2"><i class="bx bx-wallet"></i></span>
                            <h5 class="mb-0">Payment summary</h5>
                        </div>

                        <div class="transaction-financial-row">
                            <span class="text-muted">Unit price</span>
                            <span class="fw-medium">{!! $currency !!}{{ number_format($transaction->unit_price, 2) }}</span>
                        </div>
                        <div class="transaction-financial-row">
                            <span class="text-muted">Quantity</span>
                            <span class="fw-medium">{{ number_format($transaction->quantity ?: 1) }}</span>
                        </div>
                        @if($isWalletToBank)
                            @php
                                $baseTransferCharge = $chargeBreakdown->whereIn('type', ['provider_fee', 'our_charge'])->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                                $bandExtraCharges = $chargeBreakdown->where('type', 'band_extra_charge')->values();
                                $additionalCharges = $chargeBreakdown->where('type', 'global_extra_charge')->values();
                                $extraChargesTotal = $bandExtraCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)) + $additionalCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                                $totalFee = $baseTransferCharge + $extraChargesTotal;

                                if ($baseTransferCharge <= 0 && (float) $transaction->provider_charge > 0) {
                                    $baseTransferCharge = (float) $transaction->provider_charge;
                                    $totalFee = $baseTransferCharge + $extraChargesTotal;
                                }
                            @endphp
                            <div class="wallet-bank-breakdown-card">
                                <div class="wallet-bank-breakdown-header">
                                    <div>
                                        <h6 class="wallet-bank-breakdown-title">Charge Breakdown</h6>
                                        <div class="wallet-bank-breakdown-subtitle">Wallet to bank transfer charges</div>
                                    </div>
                                    <span class="wallet-bank-breakdown-pill"><i class="bx bx-receipt"></i> Wallet to bank</span>
                                </div>
                                <div class="wallet-bank-breakdown-row">
                                    <span>Transfer Amount</span>
                                    <strong>{!! $currency !!}{{ number_format((float) $transaction->amount, 2) }}</strong>
                                </div>
                                <div class="wallet-bank-breakdown-row">
                                    <span>Base Transfer Charge</span>
                                    <strong>{!! $currency !!}{{ number_format($baseTransferCharge, 2) }}</strong>
                                </div>
                                @if($bandExtraCharges->count())
                                    <div class="wallet-bank-breakdown-section">
                                        <div class="wallet-bank-breakdown-section-label">Band Extra Charges</div>
                                        @foreach ($bandExtraCharges as $charge)
                                            <div class="wallet-bank-breakdown-row">
                                                <span>{{ $charge['label'] ?? 'Band charge' }}</span>
                                                <strong>{!! $currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($additionalCharges->count())
                                    <div class="wallet-bank-breakdown-section">
                                        <div class="wallet-bank-breakdown-section-label">Additional Charges</div>
                                        @foreach ($additionalCharges as $charge)
                                            <div class="wallet-bank-breakdown-row">
                                                <span>{{ $charge['label'] ?? 'Additional charge' }}</span>
                                                <strong>{!! $currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if(!empty($transaction->pricing_band_name))
                                    <div class="wallet-bank-breakdown-section">
                                        <div class="wallet-bank-breakdown-row mb-0">
                                            <span>Matched Band</span>
                                            <strong>{{ $transaction->pricing_band_name }}</strong>
                                        </div>
                                    </div>
                                @endif
                                <div class="wallet-bank-breakdown-row wallet-bank-breakdown-total">
                                    <span>Total Fee</span>
                                    <strong>{!! $currency !!}{{ number_format($totalFee, 2) }}</strong>
                                </div>
                                <div class="wallet-bank-breakdown-row wallet-bank-breakdown-total">
                                    <span>Total Debit</span>
                                    <strong>{!! $currency !!}{{ number_format($transaction->total_amount, 2) }}</strong>
                                </div>
                            </div>
                        @elseif($isWalletToBank && (float) $transaction->provider_charge > 0)
                            <div class="transaction-financial-row">
                                <span class="text-muted">Convenience fee</span>
                                <span class="fw-medium">{!! $currency !!}{{ number_format($transaction->provider_charge, 2) }}</span>
                            </div>
                        @endif
                        @if((float) $transaction->discount > 0)
                            <div class="transaction-financial-row">
                                <span class="text-muted">Discount</span>
                                <span class="fw-medium text-success">-{!! $currency !!}{{ number_format($transaction->discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="transaction-financial-row transaction-financial-row--total">
                            <span>Total</span>
                            <span>{!! $currency !!}{{ number_format($transaction->total_amount, 2) }}</span>
                        </div>

                        <div class="rounded bg-body-tertiary p-3 mt-4">
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <small class="text-muted">Balance before</small>
                                <small class="fw-medium">{!! $currency !!}{{ number_format($transaction->balance_before, 2) }}</small>
                            </div>
                            <div class="d-flex justify-content-between gap-3">
                                <small class="text-muted">Balance after</small>
                                <small class="fw-medium">{!! $currency !!}{{ number_format($transaction->balance_after, 2) }}</small>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.getElementById('copy-transaction-id')?.addEventListener('click', async function () {
            const button = this;
            const originalHtml = button.innerHTML;

            try {
                await navigator.clipboard.writeText(button.dataset.value);
                button.innerHTML = '<i class="bx bx-check"></i>';
                setTimeout(() => button.innerHTML = originalHtml, 1600);
            } catch (error) {
                window.prompt('Copy transaction ID:', button.dataset.value);
            }
        });
    </script>
@endsection
