@extends('sneat.layouts.app')
@section('title', 'Customer Reports')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Exports',
        'title' => 'Customer Reports',
        'subtitle' => 'Download filtered report data for the date range you need.',
    ])

    <div class="card customer-form-card">
        <div class="card-header d-flex align-items-center gap-3">
            <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-filter-alt fs-4"></i></span>
            <div><h5 class="mb-1">Report filters</h5><small class="text-muted">Select the records and date range to export.</small></div>
        </div>
        <div class="card-body">
            <form action="{{ route('customer.transaction.report') }}" method="GET" class="customer-modern-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">Select</option>
                            <option value="wallet" {{ request('type') == 'wallet' ? 'selected' : '' }}>Wallet History</option>
                            <option value="transaction" {{ request('type') == 'transaction' ? 'selected' : '' }}>Transactions History</option>
                            <option value="earning" {{ request('type') == 'earning' ? 'selected' : '' }}>Earning History</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select modern-select2" name="category" data-placeholder="Search categories">
                            <option value="">Select</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Service</label>
                        <select class="form-select modern-select2" name="service" data-placeholder="Search services">
                            <option value="">Select</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('service') == $product->id ? 'selected' : '' }}>{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unique element</label>
                        <input type="text" class="form-control" name="unique_element" value="{{ request('unique_element') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Select</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary customer-filter-submit w-100" type="submit"><i class="bx bx-download me-1"></i> Download Report</button>
                    </div>
                </div>
            </form>
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
