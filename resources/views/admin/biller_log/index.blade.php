@extends('layouts.app')
@section('title', 'Biller Logs')
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
                                    <li class="breadcrumb-item"><a href="{{ route('billerlog.index') }}">Biller Logs</a>
                                    </li>
                                    <li class="breadcrumb-item active">All Biller's Information
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
                                <div class="card-header">
                                    <h4 class="card-title">All Biller's code</h4> <br>
                                    <p>List of all Biller's verified on {{config('app.name')}}</p>
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Biller</th>
                                                        <th>Refined Data</th>
                                                        <th>Raw Data</th>
                                                        <th>ServiceID</th>
                                                        <th>Date Added</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $billers as $biller )
                                                    <tr>
                                                        <td>{{ $biller->billers_code }}</td>
                                                        <td>
                                                            @foreach(json_decode($biller->refined_data, true) as $key => $value)
                                                            <strong>{{ $key }}:</strong> {{ $value }} <br>
                                                            @endforeach
                                                        </td>
                                                        <td>
                                                            <pre>
                                                                <code>

                                                                    {{ json_encode(json_decode($biller->raw_data,true),JSON_PRETTY_PRINT) }}
                                                                </code>
                                                                </pre>
                                                        </td>
                                                        <td>
                                                            {{ $biller->service_id}}
                                                        </td>
                                                        <td>
                                                            {{ $biller->created_at}}
                                                        </td>
                                                        <td>
                                                            <form action="{{ route('billerlog.destroy', $biller->id) }}"
                                                                class="btn btn-custon-four btn-bg-cl-social" method="POST"
                                                                onsubmit="return confirm('Are you sure you want to delete forever?');">
                                                                {{ csrf_field() }}
                                                                {{method_field('DELETE')}}
            
                                                                <button type="submit" class="btn btn-danger btn-sm mr-1 mb-1"
                                                                    data-toggle="tooltip" title="Delete Customer Level"><i class="fa fa-trash"></i> Delete Log</button>
                                                            </form>
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
