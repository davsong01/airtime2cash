<div class="customer-section-heading">
    <div>
        <h4>Airtime to Cash history</h4>
        <p>The customer's airtime to cash transactions, including manual and auto-share flows.</p>
    </div>
    <span class="badge badge-light-info px-1 py-50">10 per page</span>
</div>

<div class="table-responsive">
    <table class="table customer-data-table mb-0">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Product</th>
                <th>Amount Charged</th>
                <th>Amount Paid</th>
                <th>Method</th>
                <th>Status</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse($airtimeTransactions as $transaction)
                @php
                    $status = strtolower((string) ($transaction->status ?? 'pending'));
                    $statusClass = match ($status) {
                        'approved', 'successful', 'success' => 'badge-light-success',
                        'pending' => 'badge-light-warning',
                        'declined', 'failed' => 'badge-light-danger',
                        default => 'badge-light-secondary',
                    };
                    $statusLabel = str_replace('-', ' ', $status);
                    $methodLabel = $transaction->transfer_mode === 'auto_share' ? 'Auto Share' : 'Manual';
                @endphp
                <tr>
                    <td class="text-muted">{{ $airtimeTransactions->firstItem() + $loop->index }}</td>
                    <td>
                        <strong>{{ $transaction->product->name ?? 'Airtime to Cash' }}</strong>
                        <small class="d-block text-muted">{{ $transaction->created_at->format('d M Y, h:i A') }}</small>
                    </td>
                    <td>{!! getSettings()->currency !!}{{ number_format((float) $transaction->amount_charged, 2) }}</td>
                    <td>{!! getSettings()->currency !!}{{ number_format((float) $transaction->amount_paid, 2) }}</td>
                    <td>{{ $methodLabel }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ ucfirst($statusLabel) }}</span></td>
                    <td><a href="{{ route('admin.single.airtime2cash.transaction.view', $transaction->id) }}" class="font-weight-bold">{{ $transaction->transaction_id }}</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="customer-empty-state">
                            <i class="bx bx-phone-call"></i>
                            No airtime to cash transactions have been recorded for this customer.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($airtimeTransactions && $airtimeTransactions->hasPages())
    <div class="d-flex justify-content-center mt-2">
        {{ $airtimeTransactions->appends(['tab' => 'airtime2cash-transactions'])->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
@endif
