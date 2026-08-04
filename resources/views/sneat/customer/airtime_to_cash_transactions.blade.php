@php use App\Models\Airtime2CashTransactions; @endphp
@extends('sneat.layouts.app')
@section('title', 'Airtime to Cash History')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'History',
        'title' => 'Airtime to Cash Transactions',
        'subtitle' => 'Track pending, approved, and declined airtime-to-cash requests.',
    ])

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Pending</div>
                    <h3 class="mb-0">{{ Airtime2CashTransactions::where(['customer_id' => auth()->user()->customer->id, 'type' => 'credit', 'status' => 'pending'])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-1">Declined</div>
                    <h3 class="mb-0">{{ Airtime2CashTransactions::where(['customer_id' => auth()->user()->customer->id, 'type' => 'credit', 'status' => 'declined'])->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card customer-form-card mb-4">
        <div class="card-header d-flex align-items-center gap-3">
            <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-filter-alt fs-4"></i></span>
            <div><h5 class="mb-1">Filter requests</h5><small class="text-muted">Find a conversion by product, transfer mode, status, ID, or date.</small></div>
        </div>
        <div class="card-body">
            <form action="{{ route('customer.airtime2cash.transaction.history') }}" method="GET" class="customer-modern-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Product</label>
                        <select class="form-select modern-select2" name="product_id" data-placeholder="Search products">
                            <option value="">Select</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="transfer_mode">Transfer mode</label>
                        <select class="form-select" name="transfer_mode" id="transfer_mode">
                            <option value="">All modes</option>
                            <option value="manual" @selected(request('transfer_mode') === 'manual')>Manual Transfer</option>
                            <option value="auto_share" @selected(request('transfer_mode') === 'auto_share')>Auto Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" class="form-control" name="transaction_id" value="{{ request('transaction_id') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Select</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="declined" {{ request('status') == 'declined' ? 'selected' : '' }}>Declined</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary customer-filter-submit w-100" type="submit"><i class="bx bx-search me-1"></i> Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Charge</th>
                        <th>Transfer mode</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <span class="d-block fw-semibold text-heading">{{ $transaction->product->name ?? 'Not set' }}</span>
                                <small class="d-block text-muted mt-1">{{ $transaction->transaction_id }}</small>
                            </td>
                            <td>{{ getSettings()['currency'] }}{{ number_format($transaction->total_amount, 2) }}</td>
                            <td>{{ getSettings()['currency'] }}{{ number_format($transaction->amount_charged, 2) }}</td>
                            <td><span class="badge bg-label-info">{{ $transaction->transfer_mode === 'auto_share' ? 'Auto Transfer' : 'Manual Transfer' }}</span></td>
                            <td>
                                <span class="badge {{ $transaction->status == 'declined' ? 'bg-label-danger' : ($transaction->status == 'pending' ? 'bg-label-warning' : 'bg-label-success') }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                            <td>{{ date('M jS, Y g:iA', strtotime($transaction->created_at)) }}</td>
                            <td class="text-end">
                                <a target="_blank" href="{{ route('airtime2cash.transaction.status', $transaction->transaction_id) }}" class="btn btn-sm btn-primary">View</a>
                                @if(in_array($transaction->status, ['approved']))
                                    <a target="_blank" href="{{ route('airtime2cash.transaction.receipt.download', $transaction->id) }}" class="btn btn-sm btn-outline-secondary">Receipt</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No airtime-to-cash transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>
<script>
    $('.modern-select2').select2({
        width: '100%',
        placeholder: function () { return $(this).data('placeholder'); },
        allowClear: true
    });
</script>
@endsection
