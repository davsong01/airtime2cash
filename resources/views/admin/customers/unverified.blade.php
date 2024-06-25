@extends('layouts.app')
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <!-- END: Vendor CSS-->
@endsection
@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Unverified Customers</h5>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-extended-success" class="table table-striped dataex-html5-selectors">
                                <thead>
                                    <tr>
                                        <th>Details</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        @if(hasAccess('customers.verify'))
                                        <th>Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>
                                                <P>
                                                    Name:<a target="_blank" href="{{ route('customers.edit', $customer->id) }}">
                                                        {{ $customer->firstname . ' ' . $customer->lastname }}</a> <br>
                                                    Email:  {{ $customer->email }} <br>
                                                    Phone Number: {{ $customer->phone }}
                                                </P>
                                            </td>
                                            
                                            <td>{{ $customer->username }}</td>
                                            <td> <small><strong>{{ ucfirst($customer->status) }}</strong></small></td>
                                            <td>{{ $customer->created_at }}</td>
                                            @if(hasAccess('customers.verify'))
                                            <td>
                                                <a onclick="return confirm('Are you sure you want to verify this user?');" href="{{ route('customer.verify', $customer->id) }}"><button type="button" class="btn btn-primary btn-sm mr-1 mb-1"><i class="bx bx-check"></i><span class="align-middle ml-25">Verify Email</span></button>
                                                </a>
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
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
