@extends('sneat.layouts.app')
@section('title', 'Downlines')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Network',
        'title' => $check ? 'Downline Transactions' : 'My Downlines',
        'subtitle' => 'Review your referral network and earnings.',
        'actions' => [
            ['label' => 'Withdraw Earning', 'href' => route('downlines.withdraw'), 'class' => 'btn-primary', 'icon' => 'bx bx-wallet'],
        ],
    ])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        @if(!$check)
                            <th>Total earning</th>
                        @else
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Earning</th>
                        @endif
                        <th>Date</th>
                        @if(!$check)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($refs as $ref)
                        <tr>
                            <td>{{ $ref->referredCustomer?->user?->username }}</td>
                            @if(!$check)
                                <td>{{ getSettings()->currency . number_format($ref->total_earnings()) }}</td>
                            @else
                                <td>{{ $ref->transaction->product_name }}</td>
                                <td>{{ getSettings()->currency . number_format($ref->transaction->amount) }}</td>
                                <td>{{ getSettings()->currency . number_format($ref->amount, 2) }}</td>
                            @endif
                            <td>{{ $ref->created_at->toDateTimeString() }}</td>
                            @if(!$check)
                                <td class="text-end"><a href="downlines/{{ $ref->referred_customer_id }}" class="btn btn-sm btn-primary">View Transactions</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $check ? 4 : 4 }}" class="text-center text-muted py-4">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
