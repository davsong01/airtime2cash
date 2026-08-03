@extends('layouts.app')
@section('title', 'All APIs')

@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
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
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item active">Downlines</li>
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
                                <div class="card-header">
                                    <h4 class="card-title">
                                        @if ($check)
                                            Downline Transactions
                                        @else
                                            My downlines
                                        @endif
                                    </h4>
                                    <a href="{{ route('downlines.withdraw') }}"><button id="addRow"
                                            class="btn btn-primary mt-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Withdraw Earning</button></a>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                           <table able class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Username</th>
                                                        @if (!$check)
                                                            <th>Total Earning</th>
                                                        @else
                                                            <th>Service</th>
                                                            <th>Amount</th>
                                                            <th>Earning</th>
                                                        @endif
                                                        <th>Date</th>
                                                        @if (!$check)
                                                            <th>Action</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($refs as $ref)
                                                        <tr>
                                                            <td>{{ $ref->referredCustomer->user->username }}</td>
                                                            @if (!$check)
                                                                <td>{!! getSettings()->currency . number_format($ref->total_earnings()) !!}</td>
                                                                @else
                                                                <td>{{ $ref->transaction->product_name }}</td>
                                                                <td>{!! getSettings()->currency . number_format($ref->transaction->amount) !!}</td>
                                                                <td>{!! getSettings()->currency . number_format($ref->amount,2) !!}</td>
                                                            @endif
                                                            <td>{{ $ref->created_at->toDateTimeString() }}</td>
                                                            @if (!$check)
                                                                <td>
                                                                    <a href="downlines/{{ $ref->referred_customer_id }}"
                                                                        class="btn btn-sm btn-primary"><i class="fa fa-eye"></i>View Transactions</a>
                                                                </td>
                                                            @endif
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
