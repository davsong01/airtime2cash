@php $canVerify = hasAccess('customers.verify'); @endphp

@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row"><div class="content-header-left col-12 mb-2 mt-1"><div class="breadcrumb-wrapper col-12"><ol class="breadcrumb p-0 mb-0"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li><li class="breadcrumb-item"><a href="{{ route('customers') }}">Customers</a></li><li class="breadcrumb-item active">Unverified email accounts</li></ol></div></div></div>
            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero ops-hero-customers mb-2">
                    <div class="row align-items-center"><div class="col-lg-8"><span class="ops-kicker"><i class="bx bx-envelope"></i> Account verification</span><h2>Unverified email accounts</h2><p>Search and process accounts without loading the entire customer database into the browser.</p></div><div class="col-lg-4 mt-2 mt-lg-0 text-lg-right"><a href="{{ route('customers') }}" class="btn btn-light"><i class="bx bx-group mr-50"></i> Customer portfolio</a></div></div>
                </section>

                <section class="row">
                    <div class="col-md-4"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-warning"><i class="bx bx-time-five"></i></span><span class="ops-metric-label">Awaiting verification</span><strong>{{ number_format((int) $summary->unverified) }}</strong><small>All unverified customer emails</small></div></div></div>
                    <div class="col-md-4"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-primary"><i class="bx bx-user-plus"></i></span><span class="ops-metric-label">New this month</span><strong>{{ number_format((int) $summary->new_this_month) }}</strong><small>Recently registered and unverified</small></div></div></div>
                    <div class="col-md-4"><div class="card ops-metric-card"><div class="card-body"><span class="ops-metric-icon is-success"><i class="bx bx-envelope-open"></i></span><span class="ops-metric-label">Verified accounts</span><strong>{{ number_format((int) $summary->verified) }}</strong><small>Email verification completed</small></div></div></div>
                </section>

                <section class="card ops-panel ops-filter-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap"><div class="d-flex align-items-center"><span class="ops-filter-icon"><i class="bx bx-filter-alt"></i></span><div><h5 class="mb-25">Find accounts</h5><small class="text-muted">Search names, email, username, phone, or registration date.</small></div></div>@if(request()->query())<a href="{{ route('customers.unverified') }}" class="btn btn-sm btn-light-secondary"><i class="bx bx-reset mr-25"></i> Clear</a>@endif</div>
                    <div class="card-body"><form method="GET" action="{{ route('customers.unverified') }}"><div class="row"><div class="col-md-6 form-group"><label for="search">Customer details</label><input class="form-control" type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email, username or phone"></div><div class="col-md-2 form-group"><label for="from">Joined from</label><input class="form-control" type="date" id="from" name="from" value="{{ request('from') }}"></div><div class="col-md-2 form-group"><label for="to">Joined to</label><input class="form-control" type="date" id="to" name="to" value="{{ request('to') }}"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit"><i class="bx bx-search mr-25"></i> Search</button></div></div></form></div>
                </section>

                <form id="actionForm" method="POST" action="{{ route('verify-users-actions') }}" onsubmit="return prepareBulkAction();">
                    @csrf
                    <section class="card ops-panel">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap"><div><span class="ops-section-kicker">Verification queue</span><h5 class="mb-0">{{ number_format($customers->total()) }} matching accounts</h5></div>@if($canVerify)<div class="d-flex align-items-center"><select id="action-select" class="form-control form-control-sm mr-50" name="action" required><option value="">Bulk action</option><option value="verify">Verify selected</option><option value="delete">Delete selected</option></select><button class="btn btn-sm btn-secondary" type="submit">Apply</button></div>@endif</div>
                        <div class="table-responsive"><table class="table table-hover mb-0 ops-table ops-compact-table"><thead><tr>@if($canVerify)<th><input type="checkbox" id="select-all"></th>@endif<th>S/N</th><th>Customer</th><th>Account</th><th>Joined</th>@if($canVerify)<th class="text-right">Actions</th>@endif</tr></thead><tbody>
                            @forelse($customers as $customer)
                                @php $name = trim($customer->firstname . ' ' . $customer->lastname) ?: 'Unnamed customer'; @endphp
                                <tr>@if($canVerify)<td><input type="checkbox" class="customer-checkbox" value="{{ $customer->id }}"></td>@endif<td class="text-muted">{{ $customers->firstItem() + $loop->index }}</td><td><div class="d-flex align-items-center"><span class="ops-customer-mark">{{ str($name)->substr(0, 1)->upper() }}</span><div class="min-width-0"><a class="d-block font-weight-bold text-truncate" href="{{ route('customers.edit', $customer->id) }}">{{ $name }}</a><small class="d-block text-muted text-truncate" title="{{ $customer->email }} · {{ $customer->phone ?: 'No phone' }}">{{ $customer->email }} · {{ $customer->phone ?: 'No phone' }}</small></div></div></td><td><strong class="d-inline-block mr-50">{{ '@' . ($customer->username ?: 'not-set') }}</strong><span class="badge badge-light-warning">Unverified</span><small class="d-block text-muted">{{ ucfirst($customer->status ?: 'unknown') }}</small></td><td><strong>{{ $customer->created_at->format('M j, Y') }}</strong><small class="d-block text-muted">{{ $customer->created_at->format('g:i A') }}</small></td>@if($canVerify)<td class="text-right text-nowrap"><a onclick="return confirm('Verify this customer email?')" href="{{ route('customer.verify', $customer->id) }}" class="btn btn-sm btn-icon btn-primary" title="Verify email"><i class="bx bx-check"></i></a><a onclick="return confirm('Delete this unverified customer permanently?')" href="{{ route('customer.delete', $customer->id) }}" class="btn btn-sm btn-icon btn-outline-danger" title="Delete customer"><i class="bx bx-trash"></i></a></td>@endif</tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">No unverified accounts match these filters.</td></tr>
                            @endforelse
                        </tbody></table></div>
                        @if($customers->hasPages())<div class="card-footer d-flex justify-content-between align-items-center"><small class="text-muted">Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ number_format($customers->total()) }}</small>{{ $customers->links() }}</div>@endif
                    </section>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.getElementById('select-all')?.addEventListener('change', function () {
            document.querySelectorAll('.customer-checkbox').forEach(checkbox => checkbox.checked = this.checked);
        });

        function prepareBulkAction() {
            const ids = Array.from(document.querySelectorAll('.customer-checkbox:checked')).map(checkbox => checkbox.value);
            if (!document.getElementById('action-select')?.value || ids.length === 0) {
                alert('Select an action and at least one customer.');
                return false;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'customer_ids';
            input.value = ids.join(',');
            document.getElementById('actionForm').appendChild(input);
            return confirm('Apply this action to ' + ids.length + ' selected account(s)?');
        }
    </script>
@endsection
