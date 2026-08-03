@extends('layouts.app')
@section('title', 'All Customer Levels')

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
                                    <li class="breadcrumb-item"><a href="{{ route('customerlevel.index') }}">Customer
                                            Levels</a>
                                    </li>
                                    <li class="breadcrumb-item active">All Customer Levels
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
                                <div class="card-header">
                                    <h4 class="card-title">All Customer Levels</h4> <br>
                                    <a href="{{ route('customerlevel.create') }}"><button id="addRow"
                                            class="btn btn-primary mb-2 d-flex align-items-center"><i
                                                class="bx bx-plus"></i>&nbsp; Add Customer Level</button></a>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Order</th>
                                                        <th>Customers Count</th>
                                                        <th>Upgrade Amount</th>
                                                        @if(hasAccess('customerlevel.edit') || hasAccess('customerlevel.destroy'))
                                                        <th>Actions</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($levels as $level)
                                                        <tr>
                                                            <td>{{ $level->name }}
                                                                @if($level->make_api_level == 'yes')
                                                                <button class="btn btn-primary">API</button>
                                                                @endif
                                                            </td>
                                                            <td>{{ $level->order }}</td>
                                                            <td>{{ $level->customers_count }}</td>
                                                            <td>{!! getSettings()['currency'] !!}{{ number_format($level->upgrade_amount) }}
                                                            </td>
                                                            @if(hasAccess('customerlevel.edit') || hasAccess('customerlevel.destroy'))
                                                            <td>
                                                                @if(hasAccess('customerlevel.edit'))
                                                                    <a href="{{ route('customerlevel.edit', $level->id) }}"><button
                                                                    type="button" class="btn btn-primary btn-sm mr-1 mb-1"><span class="align-middle ml-25">View/Edit</span></button></a>
                                                                @endif
                                                                @if(hasAccess('customerlevel.destroy'))
                                                                    @if ($level->customers_count < 1)
                                                                        <form
                                                                            action="{{ route('customerlevel.destroy', $level->id) }}"
                                                                            class="btn btn-custon-four btn-bg-cl-social"
                                                                            method="POST"
                                                                            onsubmit="return confirm('Are you sure you want to delete forever?');">
                                                                            {{ csrf_field() }}
                                                                            {{ method_field('DELETE') }}

                                                                            <button type="submit"
                                                                                class="btn btn-danger btn-sm mr-1 mb-1"
                                                                                data-toggle="tooltip"
                                                                                title="Delete Customer Level">Delete Customer
                                                                                Level
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                @endif
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
