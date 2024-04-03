@extends('layouts.app')
@section('title', 'All APIs')

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
                                    <li class="breadcrumb-item"><a href="{{ route('api.index') }}">API Provider</a>
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
                                
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                        @if(hasAccess('api.edit') || hasAccess('api.balance'))
                                                        <th>Actions</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $apis as $api )
                                                    <tr>
                                                        <td>{{ $api->name }}</td>
                                                        <td style="color:{{ $api->status == 'active' ? 'green' : 'red'}}">{{ ucfirst($api->status) }}</td>
                                                        @if(hasAccess('api.edit') || hasAccess('api.balance'))
                                                        <td>
                                                            @if(hasAccess('api.edit'))
                                                            <a href="{{ route('api.edit', $api->id) }}"><button type="button" class="btn btn-primary btn-sm mr-1 mb-1"><i class="fa fa-edit"></i><span class="align-middle ml-25">View/Edit</span></button></a>
                                                            @endif
                                                            @if(hasAccess('api.balance'))
                                                            <a id="api-{{$api->id}}" onclick="getBalance('{{$api->id}}')" style="color:white" class="btn btn-info btn-sm mr-1 mb-1"><span id="icon-{{ $api->id }}"><i class="fa fa-refresh"></i></span><span class="align-middle ml-25">Check balance</span></a>
                                                            <span id="balance-{{$api->id}}" style="font-weight: bold;color: black;"></span>
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

<script>
     function getBalance(id){
        var url = "{{ url('admin/api-balance') }}/"+id;
        
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            
            beforeSend: function () {
                $("#icon-"+id).html("<i class='fa fa-spinner fa-spin'></i>");
            },
            success: function (data) {
                if(data.status == 'success'){
                    $("#api-"+id).hide(); 
                    $("#balance-"+id).html('<span>'+data.balance+'</span>'); 
                }
            },
            error: function (data) {
                $("#api-"+id).hide(); 
                $("#balance-"+id).html('<span>An error occured</>'); 
            }
        });
    }
        // function getBalance(id){
        //     alert('sd');
        //     let url = "{{url('/')}}"+"api-balance/"+id;
        //     $.ajax({
        //         type: "GET",
        //         url: url,
        //         dataType: 'json',
        //         beforeSend: function () {
        //             $("#icon-"+id).html("<i class='fa fa-spinner fa-spin'></i>");
        //         },
        //         success: function (data) {
        //             $("#api-"+id).html(data.message);
        //         }
        //     });
        // }
        
    </script>
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
