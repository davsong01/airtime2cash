<div class="customer-section-heading">
    <div>
        <h4>Referrals and downlines</h4>
        <p>Customers referred by this account and the earnings attributed to each relationship.</p>
    </div>
    <span class="badge badge-light-info px-1 py-50">{{ number_format($downlines->count()) }} referrals</span>
</div>

<div class="table-responsive">
    <table class="table customer-data-table mb-0">
        <thead><tr><th>S/N</th><th>Customer</th><th>Contact</th><th>Total earned</th><th>First recorded</th></tr></thead>
        <tbody>
            @forelse($downlines as $referral)
                @php $referredUser = $referral->referredCustomer->user ?? null; @endphp
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td><strong>{{ $referredUser?->name ?: 'Unknown customer' }}</strong></td>
                    <td><span>{{ $referredUser?->email ?: '-' }}</span><small class="d-block text-muted">{{ $referredUser?->phone ?: 'No phone number' }}</small></td>
                    <td>{!! getSettings()->currency !!}{{ number_format((float) $referral->total, 2) }}</td>
                    <td class="text-muted">{{ $referral->created_at->format('d M Y, h:i A') }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="customer-empty-state"><i class="bx bx-git-branch"></i>This customer has no recorded referrals.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
