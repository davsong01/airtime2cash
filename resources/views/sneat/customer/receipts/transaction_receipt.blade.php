@php
    $settings = getSettings();
    $currency = $settings->currency;
    $status = strtolower($transaction['status'] ?? 'pending');
    $statusLabel = str($status)->title();
    $statusStyles = match ($status) {
        'failed', 'declined' => ['color' => '#b42318', 'background' => '#fef3f2', 'border' => '#fecdca'],
        'delivered', 'successful', 'success', 'completed', 'approved' => ['color' => '#067647', 'background' => '#ecfdf3', 'border' => '#abefc6'],
        'pending', 'initiated', 'processing' => ['color' => '#b54708', 'background' => '#fffaeb', 'border' => '#fedf89'],
        default => ['color' => '#344054', 'background' => '#f2f4f7', 'border' => '#d0d5dd'],
    };
    $systemReasons = ['LEVEL-UPGRADE', 'WALLET-FUNDING', 'ADMIN-DEBIT', 'ADMIN-CREDIT'];
    $reason = $transaction['reason'] ?? null;
    $product = $transaction['product'] ?? null;
    $variation = $transaction['variation'] ?? null;
    $serviceName = in_array($reason, $systemReasons, true)
        ? str($reason)->replace('-', ' ')->title()
        : ($product['display_name'] ?? $product['name'] ?? $transaction['product_name'] ?? 'Transaction');
    $variationName = $variation['system_name'] ?? $transaction['variation_name'] ?? null;
    $extraInfo = [];
    $isWalletToBank = strtolower((string) ($transaction['reason'] ?? '')) === 'wallet to bank transfer';
    $chargeBreakdown = collect(normalizeChargeBreakdown($transaction['charge_breakdown'] ?? []))->filter(fn ($charge) => is_array($charge));

    if (filled($transaction['extra_info'] ?? null)) {
        $decodedExtraInfo = json_decode($transaction['extra_info'], true);
        $extraInfo = is_array($decodedExtraInfo)
            ? array_filter($decodedExtraInfo, fn ($value, $key) => ! str_starts_with((string) $key, 'resolution_'), ARRAY_FILTER_USE_BOTH)
            : [];
    }

    $embedPublicImage = static function (?string $relativePath, int $maxWidth, int $maxHeight): ?string {
        if (!$relativePath) {
            return null;
        }

        $absolutePath = public_path(ltrim($relativePath, '/'));

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        try {
            $image = Image::make($absolutePath);
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            return 'data:image/png;base64,' . base64_encode((string) $image->encode('png'));
        } catch (Throwable $exception) {
            return null;
        }
    };

    $appName = (string) config('app.name', '2Cash');
    $logoDataUri = $embedPublicImage($settings->logo ?? null, 900, 180);
    $serviceLogoDataUri = $embedPublicImage($product['image'] ?? (in_array($reason, $systemReasons, true) ? 'site/upgrade.jpg' : null), 180, 180);

    $issuedAt = \Carbon\Carbon::parse($transaction['created_at']);
    $generatedAt = now();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $transaction['transaction_id'] }}</title>
    <style>
        @page {
            margin: 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f6;
            color: #344054;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }

        .watermark {
            position: fixed;
            z-index: 2;
            top: 43%;
            left: 5%;
            width: 90%;
            color: #101828;
            font-size: 68px;
            font-weight: bold;
            letter-spacing: 5px;
            opacity: .042;
            text-align: center;
            text-transform: uppercase;
            transform: rotate(-28deg);
        }

        .receipt {
            position: relative;
            z-index: 1;
            width: 100%;
            overflow: hidden;
            border: 1px solid #dfe5ec;
            border-radius: 12px;
            background: #ffffff;
        }

        .accent {
            height: 7px;
            background: #16a36a;
        }

        .header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid #eaecf0;
        }

        .header-table,
        .summary-table,
        .section-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-cell {
            width: 58%;
            vertical-align: middle;
        }

        .receipt-meta-cell {
            width: 42%;
            vertical-align: middle;
            text-align: right;
        }

        .logo {
            max-width: 150px;
            max-height: 48px;
        }

        .brand-fallback {
            color: #101828;
            font-size: 22px;
            font-weight: bold;
        }

        .receipt-title {
            margin: 0 0 4px;
            color: #101828;
            font-size: 20px;
            font-weight: bold;
        }

        .receipt-reference {
            margin-top: 6px;
            color: #344054;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
            font-weight: bold;
        }

        .muted {
            color: #667085;
        }

        .summary {
            padding: 22px 28px;
            background: #f8fafc;
            border-bottom: 1px solid #eaecf0;
        }

        .service-cell {
            width: 58%;
            vertical-align: middle;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;
        }

        .service-logo-cell {
            width: 58px;
            padding-right: 12px;
            vertical-align: middle;
        }

        .service-copy-cell {
            vertical-align: middle;
        }

        .service-logo {
            display: block;
            width: 48px;
            height: 48px;
            border: 1px solid #e4e7ec;
            border-radius: 8px;
            object-fit: contain;
        }

        .service-logo-fallback {
            width: 48px;
            height: 48px;
            border: 1px solid #c7d7fe;
            border-radius: 8px;
            background: #eef4ff;
            color: #3538cd;
            font-size: 16px;
            font-weight: bold;
            line-height: 48px;
            text-align: center;
        }

        .amount-cell {
            width: 42%;
            vertical-align: middle;
            text-align: right;
        }

        .status {
            display: inline-block;
            margin-bottom: 9px;
            padding: 4px 9px;
            border: 1px solid {{ $statusStyles['border'] }};
            border-radius: 12px;
            background: {{ $statusStyles['background'] }};
            color: {{ $statusStyles['color'] }};
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .service-name {
            margin: 0 0 3px;
            color: #101828;
            font-size: 16px;
            font-weight: bold;
        }

        .amount-label {
            margin-bottom: 4px;
            color: #667085;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .amount {
            color: #101828;
            font-size: 25px;
            font-weight: bold;
            letter-spacing: -.5px;
        }

        .content {
            padding: 22px 28px 26px;
        }

        .section {
            margin-bottom: 22px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            margin: 0 0 10px;
            color: #101828;
            font-size: 12px;
            font-weight: bold;
        }

        .detail-box {
            overflow: hidden;
            border: 1px solid #e4e7ec;
            border-radius: 8px;
        }

        .detail-cell {
            width: 50%;
            padding: 10px 12px;
            border-bottom: 1px solid #eaecf0;
            vertical-align: top;
        }

        .detail-cell-left {
            border-right: 1px solid #eaecf0;
        }

        .detail-row-last .detail-cell {
            border-bottom: 0;
        }

        .detail-label {
            display: block;
            margin-bottom: 3px;
            color: #667085;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .45px;
            text-transform: uppercase;
        }

        .detail-value {
            color: #101828;
            font-size: 10px;
            font-weight: bold;
            word-break: break-word;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            padding: 7px 0;
            border-bottom: 1px dashed #d0d5dd;
        }

        .payment-table td:last-child {
            text-align: right;
            color: #101828;
            font-weight: bold;
        }

        .payment-table .total-row td {
            padding-top: 11px;
            border-bottom: 0;
            border-top: 2px solid #101828;
            color: #101828;
            font-size: 13px;
            font-weight: bold;
        }

        .notice {
            margin-bottom: 18px;
            padding: 10px 12px;
            border-left: 3px solid {{ $statusStyles['color'] }};
            background: {{ $statusStyles['background'] }};
            color: #344054;
        }

        .balance-box {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 7px;
            background: #f8fafc;
        }

        .balance-box td:last-child {
            text-align: right;
            color: #101828;
            font-weight: bold;
        }

        .footer {
            padding: 14px 28px;
            border-top: 1px solid #eaecf0;
            background: #f8fafc;
            color: #667085;
            font-size: 8px;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="watermark">{{ $appName }}</div>
    <div class="receipt">
        <div class="accent"></div>

        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="brand-cell">
                        @if($logoDataUri)
                            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                        @else
                            <div class="brand-fallback">{{ config('app.name', '2Cash') }}</div>
                        @endif
                    </td>
                    <td class="receipt-meta-cell">
                        <div class="receipt-title">Transaction Receipt</div>
                        <div class="muted">Issued {{ $issuedAt->format('M j, Y · g:i A') }}</div>
                        <div class="receipt-reference">REF: {{ $transaction['reference_id'] ?? $transaction['transaction_id'] }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="summary">
            <table class="summary-table">
                <tr>
                    <td class="service-cell">
                        <table class="service-table">
                            <tr>
                                <td class="service-logo-cell">
                                    @if($serviceLogoDataUri)
                                        <img class="service-logo" src="{{ $serviceLogoDataUri }}" alt="Service logo">
                                    @else
                                        <div class="service-logo-fallback">{{ str($serviceName)->substr(0, 2)->upper() }}</div>
                                    @endif
                                </td>
                                <td class="service-copy-cell">
                                    <span class="status">{{ $statusLabel }}</span>
                                    <div class="service-name">{{ $serviceName }}</div>
                                    @if($variationName)
                                        <div class="muted">{{ $variationName }}</div>
                                    @elseif(filled($transaction['unique_element'] ?? null))
                                        <div class="muted">{{ $transaction['unique_element'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="amount-cell">
                        <div class="amount-label">Total paid</div>
                        <div class="amount">{!! $currency !!}{{ number_format($transaction['total_amount'] ?? 0, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            @if(filled($transaction['descr'] ?? null) || filled($transaction['extras'] ?? null))
                <div class="notice">
                    @if(filled($transaction['descr'] ?? null))
                        <strong>{{ ucfirst($transaction['descr']) }}</strong>
                    @endif
                    @if(filled($transaction['extras'] ?? null))
                        <div>{{ $transaction['extras'] }}</div>
                    @endif
                </div>
            @endif

            <div class="section">
                <div class="section-title">Transaction details</div>
                <div class="detail-box">
                    <table class="section-table">
                        <tr>
                            <td class="detail-cell detail-cell-left">
                                <span class="detail-label">Transaction ID</span>
                                <span class="detail-value">{{ $transaction['transaction_id'] }}</span>
                            </td>
                            <td class="detail-cell">
                                <span class="detail-label">Reference</span>
                                <span class="detail-value">{{ $transaction['reference_id'] ?? 'Not available' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-cell detail-cell-left">
                                <span class="detail-label">Payment method</span>
                                <span class="detail-value">{{ filled($transaction['payment_method'] ?? null) ? str($transaction['payment_method'])->replace('-', ' ')->title() : 'Not available' }}</span>
                            </td>
                            <td class="detail-cell">
                                <span class="detail-label">Recipient / biller</span>
                                <span class="detail-value">{{ $transaction['unique_element'] ?? 'Not provided' }}</span>
                            </td>
                        </tr>
                    <tr class="detail-row-last">
                        <td class="detail-cell detail-cell-left">
                            <span class="detail-label">Customer phone</span>
                            <span class="detail-value">{{ $transaction['customer_phone'] ?? 'Not provided' }}</span>
                        </td>
                            <td class="detail-cell">
                                <span class="detail-label">Customer email</span>
                                <span class="detail-value">{{ $transaction['customer_email'] ?? 'Not provided' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if(count($extraInfo))
                <div class="section">
                    <div class="section-title">Additional information</div>
                    <div class="detail-box">
                        <table class="section-table">
                            @foreach(array_chunk($extraInfo, 2, true) as $rowIndex => $row)
                                <tr class="{{ $loop->last ? 'detail-row-last' : '' }}">
                                    @foreach($row as $key => $value)
                                        <td class="detail-cell {{ $loop->first ? 'detail-cell-left' : '' }}">
                                            <span class="detail-label">{{ str($key)->replace(['_', '-'], ' ')->title() }}</span>
                                            <span class="detail-value">{{ is_scalar($value) ? ucfirst((string) $value) : json_encode($value) }}</span>
                                        </td>
                                    @endforeach
                                    @if(count($row) === 1)
                                        <td class="detail-cell"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif

            <div class="section">
                <div class="section-title">Payment summary</div>
                <table class="payment-table">
                    <tr>
                        <td>Unit price</td>
                        <td>{!! $currency !!}{{ number_format($transaction['unit_price'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Quantity</td>
                        <td>{{ number_format($transaction['quantity'] ?? 1) }}</td>
                    </tr>
                    @if($isWalletToBank)
                        @php
                            $baseTransferCharge = $chargeBreakdown->whereIn('type', ['provider_fee', 'our_charge'])->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                            $bandExtraCharges = $chargeBreakdown->where('type', 'band_extra_charge')->values();
                            $additionalCharges = $chargeBreakdown->where('type', 'global_extra_charge')->values();
                            $extraChargesTotal = $bandExtraCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)) + $additionalCharges->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));
                            $totalFee = $baseTransferCharge + $extraChargesTotal;

                            if ($baseTransferCharge <= 0 && (float) ($transaction['provider_charge'] ?? 0) > 0) {
                                $baseTransferCharge = (float) $transaction['provider_charge'];
                                $totalFee = $baseTransferCharge + $extraChargesTotal;
                            }
                        @endphp
                        <tr>
                            <td>Base Transfer Charge</td>
                            <td>{!! $currency !!}{{ number_format($baseTransferCharge, 2) }}</td>
                        </tr>
                        @foreach($bandExtraCharges as $charge)
                            <tr>
                                <td>{{ $charge['label'] ?? 'Band charge' }}</td>
                                <td>{!! $currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                        @foreach($additionalCharges as $charge)
                            <tr>
                                <td>{{ $charge['label'] ?? 'Additional charge' }}</td>
                                <td>{!! $currency !!}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                        @if(!empty($transaction['pricing_band_name']))
                            <tr>
                                <td>Matched Band</td>
                                <td>{{ $transaction['pricing_band_name'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Total Fee</td>
                            <td>{!! $currency !!}{{ number_format($totalFee, 2) }}</td>
                        </tr>
                    @elseif((float) ($transaction['provider_charge'] ?? 0) > 0)
                        <tr>
                            <td>Convenience fee</td>
                            <td>{!! $currency !!}{{ number_format($transaction['provider_charge'], 2) }}</td>
                        </tr>
                    @endif
                    @if((float) ($transaction['discount'] ?? 0) > 0)
                        <tr>
                            <td>Discount</td>
                            <td>-{!! $currency !!}{{ number_format($transaction['discount'], 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total paid</td>
                        <td>{!! $currency !!}{{ number_format($transaction['total_amount'] ?? 0, 2) }}</td>
                    </tr>
                </table>

                <div class="balance-box">
                    <table class="section-table">
                        <tr>
                            <td>Wallet balance before</td>
                            <td>{!! $currency !!}{{ number_format($transaction['balance_before'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Wallet balance after</td>
                            <td>{!! $currency !!}{{ number_format($transaction['balance_after'] ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>This receipt was generated electronically and requires no signature.</td>
                    <td class="footer-right">
                        {{ $settings->official_email ?: config('app.name', '2Cash') }}<br>
                        Generated {{ $generatedAt->format('M j, Y · g:i A') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
