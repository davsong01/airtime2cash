@php
    $statusClass = $transaction->status === 'declined' ? 'danger' : ($transaction->status === 'approved' ? 'success' : 'warning');
@endphp
@extends('sneat.layouts.app')
@section('title', 'Airtime to Cash Status')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Status',
        'title' => 'Airtime to Cash Status',
        'subtitle' => 'Check transfer status, charges, and payout details.',
    ])

    <div class="card mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small text-uppercase fw-semibold mb-1">Status</div>
                <span class="badge bg-label-{{ $statusClass }} fs-6">{{ strtoupper($transaction->status) }}</span>
            </div>
            @if($transaction->status === 'approved')
                <a href="{{ route('airtime2cash.transaction.receipt.download', $transaction->id) }}" target="_blank" class="btn btn-primary">Download receipt</a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <img src="{{ asset($transaction->product->image) }}" alt="" class="rounded mb-3" style="width:72px;height:72px;object-fit:cover;">
                    <h5 class="mb-1">{{ $transaction->product->name }}</h5>
                    <div class="text-muted mb-2">{{ $transaction->phone_numbers }}</div>
                    <div class="mb-2"><strong>Amount transferred:</strong> {{ getSettings()->currency }}{{ number_format($transaction->total_amount, 2) }}</div>
                    <div class="mb-2"><strong>Amount received:</strong> {{ getSettings()->currency }}{{ number_format($transaction->amount_paid, 2) }}</div>
                    <div class="mb-2"><strong>Date:</strong> {{ date('M jS, Y g:iA', strtotime($transaction->created_at)) }}</div>
                    <div class="mb-0"><strong>Transaction ID:</strong> {{ $transaction->transaction_id }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Transaction details</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr><th>Where to receive payment</th><td>{{ ucfirst($transaction->payment_method) }}</td></tr>
                                <tr><th>Product</th><td>{{ $transaction->product->name }}@if($transaction->status == 'declined')<span class="text-danger ms-2">{{ $transaction->decline_reason }}</span>@endif</td></tr>
                                <tr><th>Phone</th><td>{{ $transaction->phone_numbers }}</td></tr>
                                <tr><th>Amount to transfer</th><td>{{ getSettings()->currency }}{{ number_format($transaction->total_amount) }}</td></tr>
                                <tr><th>Amount charged</th><td>{{ getSettings()->currency }}{{ number_format($transaction->amount_charged) }}</td></tr>
                                <tr><th>Charge rate</th><td>{{ number_format($transaction->charge_rate) }}%</td></tr>
                                <tr><th>Amount to receive</th><td>{{ getSettings()->currency }}{{ number_format($transaction->amount_paid) }}</td></tr>
                                <tr><th>Status</th><td>{{ ucfirst($transaction->status) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
