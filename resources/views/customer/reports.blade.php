@extends('layouts.app')
@section('title', 'Customer Reports')

@section('page-css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
    .title{
        color:black;
    }
</style>
@endsection
@section('content')
<!-- Content wrapper -->
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="card-title">
            Customer Reports
        </div>
        <div class="content-body">
            <div class="row">
                <!-- Marketing Campaigns Starts -->
                <div class="col-xl-12 col-12 dashboard-marketing-campaign">
                    <div class="card marketing-campaigns">
                        <div class="card-content">
                            <div class="card-header">
                                Please select parameters below to download reports for specific date ranges
                            </div>
                            <div class="card-body pb-0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <form action="{{ route('customer.transaction.report') }}" method="GET">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="type">Transaction Type</label>
                                                        <select class="form-control" name="type" id="type">
                                                            <option value="">Select</option>
                                                            <option value="wallet" {{ old('type') == 'wallet' ? 'selected' : ''}}>Wallet History</option>
                                                            <option value="transaction" {{ old('type') == 'transaction' ? 'selected' : ''}}>Transactions History</option>
                                                            <option value="earning" {{ old('type') == 'earning' ? 'selected' : ''}}>Earning History</option>
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="service">Category</label>
                                                        <select class="form-control js-example-basic-single" name="category" id="category">
                                                            <option value="">Select</option>
                                                            @foreach ($categories as $category)
                                                                <option value="{{ $category->id }}" {{ \Request::get('service') == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="service">Service</label>
                                                        <select class="form-control js-example-basic-single" name="service" id="service">
                                                            <option value="">Select</option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}" {{ \Request::get('service') == $product->id ? 'selected' : ''}}>{{ $product->display_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="unique_element">Unique Element</label>
                                                        <input type="text" class="form-control" id="unique_element" name="unique_element" placeholder="Enter unique element" value="{{ \Request::get('unique_element') }}">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="status">Status</label>
                                                        <select class="form-control" name="status" id="status">
                                                            <option value="">Select</option>
                                                            <option value="delivered" {{ \Request::get('status') == 'delivered' ? 'selected' : ''}}>Delivered</option>
                                                            <option value="failed" {{ \Request::get('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="from">From</label>
                                                        <input type="date" class="form-control" value="{{ \Request::get('from')}}" name="from">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-3">
                                                    <fieldset class="form-group">
                                                        <label for="to">To</label>
                                                        <input type="date" class="form-control" value="{{ \Request::get('to')}}" name="to">
                                                    </fieldset>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="to"></label>
                                                    <input type="submit" class="form-control btn btn-primary" value="Download Report">
                                                </div>
                                            </div>
                                        </form>
                                        <hr>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
</script>
@endsection
