<div class="customer-section-heading">
    <div>
        <h4>Reserved bank accounts</h4>
        <p>Manage virtual accounts assigned to this customer and inspect their funding activity.</p>
    </div>
    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#reserved"><i class="bx bx-plus mr-25"></i> Create reserved account</button>
</div>

<div class="table-responsive">
    <table class="table customer-data-table mb-0">
        <thead><tr><th>S/N</th><th>Account name</th><th>Bank and provider</th><th>Account number</th><th>Transactions</th><th class="text-right">Action</th></tr></thead>
        <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td><strong>{{ $account->account_name }}</strong><small class="d-block text-muted">Created {{ $account->created_at->format('d M Y') }}</small></td>
                    <td>{{ $account->bank_name }}<small class="d-block text-muted">{{ $account->gateway->name ?? 'Unknown provider' }}</small></td>
                    <td><strong>{{ $account->account_number }}</strong><small class="d-block text-muted">By {{ $account->admin?->user?->name ?: 'System' }}</small></td>
                    <td><a href="{{ route('account.transactions', $account->id) }}">{!! getSettings()->currency !!}{{ number_format((float) ($account->transaction_total ?? 0), 2) }} <small>({{ number_format($account->transactions_count) }})</small></a></td>
                    <td class="text-right"><a href="{{ route('reserved_account.delete', $account->id) }}" onclick="return confirm('Delete this reserved account?')" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="customer-empty-state"><i class="bx bx-building-house"></i>No reserved bank account has been created for this customer.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
