@extends('layouts.app')
@section('title', 'All Reserved Account Details')
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}"> 
    
    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css')}}">
    <!-- END: Vendor CSS-->
    
@endsection
@section('content')
<!-- Content wrapper -->
 <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb p-0 mb-0">
                                    <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a>
                                    </li>
                                    <li class="breadcrumb-item active">All Transactions for {{$account->account_name}}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Column selectors with Export Options and print table -->
                <section id="column-selectors">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                @include('layouts.alerts')
                                
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Customer Details</th>
                                                        <th>Transaction Details</th>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $transactions as $transaction )
                                                    <tr>
                                                        <td><p>
                                                            {{$transaction->customer_name}} <br>
                                                            <a title="View Customer" target="_blank" href="{{route('customers.edit', $transaction->customer->id)}}">{{$transaction->customer_email}}</a> <br>
                                                            @if($transaction->status == 'success')
                                                            <button class="btn btn-success btn-sm">Success</button>
                                                            @else  
                                                            <button class="btn btn-danger btn-sm">Failed</button>
                                                            @endif
                                                            </p>
                                                        </td>
                                                        <td><p>
                                                            {{$account->account_name}}<br>
                                                            {{$account->account_number}}<br>
                                                            {{$account->bank_name}}<br>
                                                            <span style="color:black">Provider:</span> {{$account->payment_gateway->name}} <br>
                                                            <span style="color:black">Reference:</span> {{$transaction->transaction_id}} <br>
                                                            <span style="color:black">Method:</span> {{$transaction->payment_method}}
                                                            </p>
                                                        </td>
                                                        <td>{{$transaction->created_at}}</td>
                                                        <td>{!! getSettings()->currency !!}{{ number_format($transaction->amount, 2)}}</td>
                                                        <td>
                                                            <a class="btn btn-info btn-sm" title="View Transaction" href="{{ route('transaction.status', $transaction->transaction_id) }}">View</a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- Column selectors with Export Options and print table -->
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.print.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.bootstrap.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
     <script src="{{ asset('app-assets/js/scripts/datatables/datatable.js') }}"></script>
@endsection
