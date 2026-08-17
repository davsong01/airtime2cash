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
                                <div class="px-2 px-md-4 pt-0 pb-2">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <strong>Bulk actions</strong>
                                            <small class="d-block text-muted" id="productSelectionLabel">Select products without transactions to delete them and their variations.</small>
                                        </div>
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            form="productBulkForm"
                                            formaction="{{ route('product.bulk-delete') }}"
                                            onclick="return confirm('Delete the selected products and their variations permanently? This is only allowed for products without transactions.')"
                                            id="bulkDeleteProductsButton"
                                            disabled
                                        >
                                            <i class="bx bx-trash mr-25"></i>
                                            Delete Selected
                                        </button>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body card-dashboard">
                                        <div class="table-responsive">
                                            <form id="productBulkForm" method="POST">
                                                @csrf
                                            </form>

                                            <table class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 44px;">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="selectAllProducts">
                                                                <label class="custom-control-label" for="selectAllProducts"></label>
                                                            </div>
                                                        </th>
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
                                                    @php
                                                        $canDeleteProduct = ((int) ($product->transactions_count ?? 0) === 0)
                                                            && ((int) ($product->variations_transactions_count ?? 0) === 0);
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            @if($canDeleteProduct)
                                                                <div class="custom-control custom-checkbox">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="custom-control-input product-checkbox"
                                                                        id="product-{{ $product->id }}"
                                                                        name="product_ids[]"
                                                                        value="{{ $product->id }}"
                                                                        form="productBulkForm"
                                                                    >
                                                                    <label class="custom-control-label" for="product-{{ $product->id }}"></label>
                                                                </div>
                                                            @else
                                                                <span class="text-muted" title="This product has transactions and cannot be bulk deleted">
                                                                    <i class="bx bx-lock-alt"></i>
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <img src="{!! $product->image !!}" alt="{{$product->name}}" style="width: 50px;float:left">
                                                            <div class="ml-1">
                                                                <div class="font-weight-bold">{{ $product->name }}</div>
                                                                <small class="text-muted d-block">Slug: {{ $product->slug }}</small>
                                                                <small class="text-muted d-block">Transactions: {{ number_format((int) ($product->transactions_count ?? 0)) }}</small>
                                                            </div>
                                                        </td>
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
                                                            @if($canDeleteProduct)
                                                                <form method="POST" action="{{ route('product.destroy', $product->id) }}" class="d-inline-block" onsubmit="return confirm('Delete {{$product->name}} and all of its variations? This cannot be undone.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger btn-sm mr-1 mb-1">
                                                                        <i class="bx bx-trash"></i>
                                                                        <span class="align-middle ml-25">Delete</span>
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <button type="button" class="btn btn-secondary btn-sm mr-1 mb-1" disabled>
                                                                    <i class="bx bx-lock-alt"></i>
                                                                    <span class="align-middle ml-25">Locked</span>
                                                                </button>
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
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAllProducts');
            const bulkDeleteButton = document.getElementById('bulkDeleteProductsButton');
            const checkboxes = Array.from(document.querySelectorAll('.product-checkbox'));

            const updateBulkState = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                const count = selected.length;

                if (bulkDeleteButton) {
                    bulkDeleteButton.disabled = count === 0;
                }

                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }
            };

            selectAll?.addEventListener('change', function () {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
                updateBulkState();
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateBulkState);
            });

            updateBulkState();
        });
    </script>
@endsection
