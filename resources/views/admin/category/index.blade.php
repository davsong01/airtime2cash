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
                                    <li class="breadcrumb-item"><a href="{{ route('category.index') }}">Categories</a>
                                    </li>
                                    <li class="breadcrumb-item active">All Categories
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
                                    <h4 class="card-title">All Categories</h4> <br>
                                   
                                    @if($categories->count() < 1)
                                        <a href="{{ route('category.pull') }}"><button id="addRow" class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Pull Categories</button></a>
                                    @else    
                                        <a href="{{ route('category.repull') }}"><button id="addRow" class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Repull Categories</button></a>
                                    @endif
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="dtable">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Order</th>
                                                        <th>Products</th>
                                                        <th>Status</th>
                                                        <th>Date Added</th>
                                                        @if(hasAccess('category.edit') || hasAccess('category.destroy'))
                                                        <th>Actions</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $categories as $category )
                                                    <tr>
                                                        <td>{{ $category->name }}</td>
                                                        <td>{{ $category->order }}</td>
                                                        <td>{{ $category->products_count }}</td>
                                                        <td style="color:{{ $category->status == 'active' ? 'green' : 'red'}}">{{ ucfirst($category->status) }}</td>
                                                        <td>{{ $category->created_at }}</td>
                                                        @if(hasAccess('category.edit') || hasAccess('category.destroy'))
                                                        <td>
                                                            @if(hasAccess('category.edit'))
                                                            <a href="{{ route('category.edit', $category->id) }}"><button type="button" class="btn btn-primary btn-sm mr-1 mb-1"><i class="bx bxs-pencil"></i><span class="align-middle ml-25">View/Edit</span></button></a>
                                                            @endif
                                                            @if($category->products_count < 1)
                                                            @if(hasAccess('category.destroy'))
                                                            <form action="{{ route('category.destroy', $category->id) }}"
                                                                class="btn btn-custon-four btn-bg-cl-social" method="POST"
                                                                onsubmit="return confirm('Are you sure you want to delete forever?');">
                                                                {{ csrf_field() }}
                                                                {{method_field('DELETE')}}
            
                                                                <button type="submit" class="btn btn-danger btn-sm mr-1 mb-1"
                                                                    data-toggle="tooltip" title="Delete Customer Level"><i class="fa fa-trash"></i> Delete Category                                                                </button>
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

    <script>
        $('#dtable').DataTable({
             "ordering": false,
        });

    </script>
    
@endsection
