<div class="customer-section-heading">
    <div>
        <h4>Transaction history</h4>
        <p>The customer's latest utility, wallet, and service transactions.</p>
    </div>
    <span class="badge badge-light-info px-1 py-50">10 per page</span>
</div>

<div class="table-responsive">
    <table class="table customer-data-table mb-0">
        <thead><tr><th>S/N</th><th>Product</th><th>Amount</th><th>Paid</th><th>Biller</th><th>Status</th><th>Reference</th></tr></thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td class="text-muted">{{ $transactions->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $transaction->product_name ?: 'Transaction' }}</strong><small class="d-block text-muted">{{ $transaction->created_at->format('d M Y, h:i A') }}</small></td>
                    <td>{!! getSettings()->currency !!}{{ number_format((float) $transaction->amount, 2) }}</td>
                    <td>{!! getSettings()->currency !!}{{ number_format((float) $transaction->total_amount, 2) }}</td>
                    <td>{{ $transaction->unique_element ?: '-' }}</td>
                    <td><span class="badge {{ $transaction->status === 'success' ? 'badge-light-success' : ($transaction->status === 'failed' ? 'badge-light-danger' : 'badge-light-warning') }}">{{ ucfirst($transaction->status) }}</span></td>
                    <td><a href="{{ route('admin.single.transaction.view', $transaction->id) }}" class="font-weight-bold">{{ $transaction->transaction_id }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="customer-empty-state"><i class="bx bx-receipt"></i>No transactions have been recorded for this customer.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($transactions->hasPages())
    <div class="d-flex justify-content-center mt-2">{{ $transactions->appends(['tab' => 'transactions'])->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
@endif
