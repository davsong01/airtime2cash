@extends('layouts.app')
@section('title', 'All Products')
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
                                    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">Products</a>
                                    </li>
                                    <li class="breadcrumb-item active">All Products
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
                                    <h4 class="card-title">All products</h4> <br>
                                    @if($products->count() < 1)
                                    <a href="{{ route('product.pull') }}"><button id="addRow" class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Pull Products</button></a>
                                    @else      
                                    <a href="{{ route('product.repull') }}"><button id="addRow" class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; RePull Products</button></a>
                                    @endif
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Variations</th>
                                                        <th>Status</th>
                                                        <th>Date Added</th>
                                                        @if(hasAccess('product.edit'))
                                                        <th>Actions</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ( $products as $product )
                                                    <tr>
                                                        <td><img src="{!! $product->image !!}" alt="{{$product->name}}" style="width: 50px;float:left">{{ $product->name }}</td>
                                                        <td>{{ $product->category->name }} <br>
                                                        </td>
                                                    
                                                        <td>
                                                            All: {{ $product->variations->count() }} <br>
                                                            <span style="color:green">Active: {{ $product->variations->where('status','active')->count() }}</span>
                                                        </td>
                                                        <td>{{ $product->status }}</td>
                                                        <td>{{ $product->created_at }}</td>
                                                        @if(hasAccess('product.edit'))
                                                        <td>
                                                            <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('product.edit', $product->id) }}"><i class="bx bxs-pencil"></i><span class="align-middle ml-25">View</span></button></a>
                                                            <a class="btn btn-info btn-sm mr-1 mb-1" onclick="return confirm('{{$product->name}} will be duplicated!')" href="{{ route('duplicate.product', $product->id) }}"><i class="bx bxs-copy"></i><span class="align-middle ml-25">Duplicate</span></button></a>
                                                            @if($product->has_variations == 'yes')
                                                            <a class="btn btn-dark btn-sm mr-1 mb-1" href="{{ route('product.edit', $product->id) }}"><i class="bx bxs-copy"></i><span class="align-middle ml-25">Edit Variations</span></button></a>
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
