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
                                   
                                    <li class="breadcrumb-item active">All Reserved Accounts
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
                                                        <th>Account Details</th>
                                                        <th>Transactions</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $numbers as $number )
                                                    <tr>
                                                        <td><p>
                                                            {{$number->customer->user->name}} <br>
                                                            <a title="View Customer" target="_blank" href="{{route('customers.edit', $number->customer->user->id)}}">{{$number->customer->user->email}}</a> <br>
                                                            
                                                                <small style="color:black"><strong>Created on: {{$number->created_at}} </strong>
                                                                    @if(!empty($number->admin_id)) <br>
                                                                    By: <strong>{{ $number->admin->user->firstname . ' '. $number->admin->user->lastname}}</strong>
                                                                    @else   <br>
                                                                    By: <strong>SYSTEM</strong>
                                                                    @endif
                                                                </small>
                                                            </p>
                                                        </td>
                                                        <td><p>
                                                            {{$number->account_name}}<br>
                                                            {{$number->account_number}}<br>
                                                            {{$number->bank_name}}<br>
                                                            <small><strong>Attached BVN: </strong>{{mask($number->bvn)}}</small> <br>
                                                            <span style="color:black">Provider:</span> {{$number->payment_gateway->name}}
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <a title ="View Transactions" href="{{route('account.transactions', $number->id)}}">
                                                            {!! getSettings()->currency !!}{{ number_format($number->transactions->sum('total_amount'), 2) }} <small><strong>({{number_format($number->transactions->count())}})</strong></small></a>
                                                        </td>
                                                        <td>
                                                            @if($number->transactions->count() < 1)
                                                            <a onclick="return confirm('You are about to delete a reserved account!')"class="btn btn-danger btn-sm mr-1 mb-1" href="{{ route('reserved_account.delete', $number->id) }}"><i class="bx bxs-trash"></i><span class="align-middle ml-25">Delete</span></button></a>
                                                            @endif
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
