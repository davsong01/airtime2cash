@php
    $canManageProducts = hasAccess('product.edit');
    $currentQuery = request()->except('page');
    $buildFilterUrl = function (array $overrides = []) use ($currentQuery) {
        $query = array_merge($currentQuery, $overrides);

        return url()->current() . (empty($query) ? '' : ('?' . http_build_query($query)));
    };
@endphp

@extends('layouts.app')
@section('title', 'All Products')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-operations.css') }}">
    <style>
        .product-thumb {
            width: 56px;
            height: 56px;
            border-radius: .9rem;
            object-fit: cover;
            border: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
            box-shadow: 0 8px 16px rgba(15, 23, 42, .06);
        }

        .product-name-block {
            min-width: 0;
        }

        .product-name-block .product-title {
            color: #0f172a;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
        }

        .product-name-block .product-slug,
        .product-name-block .product-count {
            color: #64748b;
            font-size: .82rem;
        }

        .product-dashboard-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .7rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
        }

        .product-dashboard-chip.is-active {
            color: #166534;
            background: rgba(34, 197, 94, .12);
        }

        .product-dashboard-chip.is-inactive {
            color: #9a3412;
            background: rgba(249, 115, 22, .12);
        }

        .product-filter-label {
            font-size: .84rem;
            font-weight: 700;
            color: #0f172a;
        }

        .product-bulk-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb p-0 mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')

                <section class="ops-hero mb-2">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="ops-kicker"><i class="bx bx-package"></i> Product operations</span>
                            <h2>Product catalog</h2>
                            <p>Search faster, filter by availability, and manage product records without loading the entire dataset at once.</p>
                        </div>
                        <div class="col-lg-4 mt-2 mt-lg-0 text-lg-right">
                            <a href="{{ route('product.create') }}" class="btn btn-light mr-50">
                                <i class="bx bx-plus mr-25"></i> Add product
                            </a>
                            @if($products->total() < 1)
                                <a href="{{ route('product.pull') }}" class="btn btn-primary">
                                    <i class="bx bx-download mr-25"></i> Pull products
                                </a>
                            @else
                                <a href="{{ route('product.repull') }}" class="btn btn-primary">
                                    <i class="bx bx-refresh mr-25"></i> Re-pull products
                                </a>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="row">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-primary"><i class="bx bx-box"></i></span>
                                <span class="ops-metric-label">Matching products</span>
                                <strong>{{ number_format((int) ($summary->total ?? $products->total())) }}</strong>
                                <small>Respecting the active filters</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-success"><i class="bx bx-check-circle"></i></span>
                                <span class="ops-metric-label">Active products</span>
                                <strong>{{ number_format((int) ($summary->active ?? 0)) }}</strong>
                                <small>Available on the storefront</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-info"><i class="bx bx-slider-alt"></i></span>
                                <span class="ops-metric-label">With variations</span>
                                <strong>{{ number_format((int) ($summary->with_variations ?? 0)) }}</strong>
                                <small>Products that expose variation choices</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card ops-metric-card">
                            <div class="card-body">
                                <span class="ops-metric-icon is-warning"><i class="bx bx-trash"></i></span>
                                <span class="ops-metric-label">Deletable</span>
                                <strong>{{ number_format((int) ($summary->deletable ?? 0)) }}</strong>
                                <small>No transactions attached yet</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card ops-panel ops-filter-panel mb-2">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="ops-filter-icon"><i class="bx bx-filter-alt"></i></span>
                            <div>
                                <h5 class="mb-25">Find products</h5>
                                <small class="text-muted">Use a combination of filters to narrow the catalog quickly.</small>
                            </div>
                        </div>
                        @if(request()->query())
                            <a href="{{ route('product.index') }}" class="btn btn-sm btn-light-secondary mt-1 mt-sm-0">
                                <i class="bx bx-reset mr-25"></i> Clear filters
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        <form action="{{ route('product.index') }}" method="GET">
                            <div class="row">
                                <div class="col-md-6 col-xl-3 form-group">
                                    <label class="product-filter-label" for="search">Search</label>
                                    <input class="form-control" type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name, display name or slug">
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label class="product-filter-label" for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All statuses</option>
                                        <option value="active" @selected(request('status') === 'active')>Active</option>
                                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label class="product-filter-label" for="category_id">Category</label>
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="">All categories</option>
                                        @foreach($categories ?? [] as $category)
                                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label class="product-filter-label" for="api_id">API</label>
                                    <select class="form-control" id="api_id" name="api_id">
                                        <option value="">All APIs</option>
                                        @foreach($apis ?? [] as $api)
                                            <option value="{{ $api->id }}" @selected((string) request('api_id') === (string) $api->id)>
                                                {{ $api->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-2 form-group">
                                    <label class="product-filter-label" for="variations">Variations</label>
                                    <select class="form-control" id="variations" name="variations">
                                        <option value="">All</option>
                                        <option value="yes" @selected(request('variations') === 'yes')>Yes</option>
                                        <option value="no" @selected(request('variations') === 'no')>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-1 d-flex align-items-end">
                                    <button class="btn btn-primary btn-block" type="submit">
                                        <i class="bx bx-search mr-25"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card ops-panel">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-1 mb-sm-0">
                            <span class="ops-section-kicker">Catalog directory</span>
                            <h5 class="mb-0">{{ number_format((int) ($products->total() ?? 0)) }} matching products</h5>
                        </div>

                        @if($canManageProducts)
                            <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                                <span class="badge badge-light-primary px-1 py-50">{{ request('status') ?: 'Any status' }}</span>
                                <select class="form-control form-control-sm" id="bulkActionSelect" style="min-width: 190px;">
                                    <option value="">Bulk action</option>
                                    <option value="delete">Delete selected</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-danger" id="bulkActionApplyBtn" disabled>
                                    Apply
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <form id="productBulkForm" method="POST">
                            @csrf
                        </form>

                        <table class="table table-hover mb-0 ops-table">
                            <thead>
                                <tr>
                                    @if($canManageProducts)
                                        <th style="width: 44px;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAllProducts">
                                                <label class="custom-control-label" for="selectAllProducts"></label>
                                            </div>
                                        </th>
                                    @endif
                                    <th>S/N</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>API</th>
                                    <th>Variations</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    @if($canManageProducts)
                                        <th class="text-right">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    @php
                                        $canDeleteProduct = ((int) ($product->transactions_count ?? 0) === 0)
                                            && ((int) ($product->variations_transactions_count ?? 0) === 0);
                                        $rowIndex = $products->firstItem() + $loop->index;
                                    @endphp
                                    <tr>
                                        @if($canManageProducts)
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
                                                    <span class="text-muted" title="This product has transactions and cannot be deleted">
                                                        <i class="bx bx-lock-alt"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="text-muted">{{ number_format($rowIndex) }}</td>

                                        <td>
                                            <div class="d-flex align-items-center" style="gap: .85rem;">
                                                <img src="{!! $product->image !!}" alt="{{ $product->name }}" class="product-thumb">
                                                <div class="product-name-block">
                                                    <div class="product-title">{{ $product->name }}</div>
                                                    <div class="product-slug">Slug: {{ $product->slug }}</div>
                                                    <div class="product-count">Transactions: {{ number_format((int) ($product->transactions_count ?? 0)) }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="font-weight-bold">{{ $product->category?->name ?? 'Unassigned' }}</div>
                                            @if($product->category?->status ?? false)
                                                <small class="text-muted d-block">Category attached</small>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="font-weight-bold">{{ $product->api?->name ?? 'No API' }}</div>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column" style="gap: .35rem;">
                                                <span class="badge badge-light-primary">All: {{ number_format((int) ($product->variations_count ?? 0)) }}</span>
                                                <span class="badge badge-light-success">Active: {{ number_format((int) ($product->active_variations_count ?? 0)) }}</span>
                                                <span class="badge {{ $product->has_variations === 'yes' ? 'badge-light-info' : 'badge-light-secondary' }}">
                                                    {{ $product->has_variations === 'yes' ? 'Has variations' : 'No variations' }}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            @if($product->status === 'active')
                                                <span class="product-dashboard-chip is-active"><i class="bx bx-check-circle"></i> Active</span>
                                            @else
                                                <span class="product-dashboard-chip is-inactive"><i class="bx bx-pause-circle"></i> Inactive</span>
                                            @endif
                                        </td>

                                        <td>
                                            <strong class="d-block">{{ $product->created_at?->format('M j, Y') }}</strong>
                                            <small class="text-muted">{{ $product->created_at?->format('g:i A') }}</small>
                                        </td>

                                        @if($canManageProducts)
                                            <td class="text-right text-nowrap">
                                                <a class="btn btn-sm btn-primary mb-25" href="{{ route('product.edit', $product->id) }}">
                                                    <i class="bx bxs-pencil mr-25"></i> Open
                                                </a>
                                                <a class="btn btn-sm btn-info mb-25" onclick="return confirm('{{ $product->name }} will be duplicated!')" href="{{ route('duplicate.product', $product->id) }}">
                                                    <i class="bx bxs-copy mr-25"></i> Duplicate
                                                </a>
                                                @if($product->has_variations == 'yes')
                                                    <a class="btn btn-sm btn-dark mb-25" href="{{ route('product.edit', $product->id) }}">
                                                        <i class="bx bx-sitemap mr-25"></i> Variations
                                                    </a>
                                                @endif
                                                @if($canDeleteProduct)
                                                    <form method="POST" action="{{ route('product.destroy', $product->id) }}" class="d-inline-block" onsubmit="return confirm('Delete {{ $product->name }} and all of its variations? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger mb-25">
                                                            <i class="bx bx-trash mr-25"></i> Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-secondary mb-25" disabled>
                                                        <i class="bx bx-lock-alt mr-25"></i> Locked
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageProducts ? 9 : 8 }}" class="text-center py-4">
                                            <i class="bx bx-package d-block font-large-1 text-muted mb-1"></i>
                                            <strong>No products found</strong>
                                            <p class="text-muted mb-0">Try adjusting the filters or pull products from the provider.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($products->hasPages())
                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
                            <small class="text-muted">
                                Showing {{ number_format((int) $products->firstItem()) }} to {{ number_format((int) $products->lastItem()) }} of {{ number_format((int) $products->total()) }}
                            </small>
                            <div>{{ $products->links() }}</div>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAllProducts');
            const bulkActionSelect = document.getElementById('bulkActionSelect');
            const bulkActionApplyBtn = document.getElementById('bulkActionApplyBtn');
            const bulkForm = document.getElementById('productBulkForm');
            const checkboxes = Array.from(document.querySelectorAll('.product-checkbox'));

            const updateBulkState = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                const count = selected.length;

                if (bulkActionApplyBtn) {
                    bulkActionApplyBtn.disabled = count === 0 || !bulkActionSelect?.value;
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

            bulkActionSelect?.addEventListener('change', updateBulkState);

            bulkActionApplyBtn?.addEventListener('click', function () {
                const action = bulkActionSelect?.value;
                const ids = Array.from(document.querySelectorAll('.product-checkbox:checked')).map((checkbox) => checkbox.value);

                if (!action || ids.length === 0) {
                    alert('Select a bulk action and at least one deletable product.');
                    return;
                }

                if (action !== 'delete') {
                    return;
                }

                if (!window.confirm('Delete the selected products and their variations permanently? This is only allowed for products without transactions.')) {
                    return;
                }

                const currentAction = bulkForm?.getAttribute('action');
                bulkForm?.setAttribute('action', '{{ route('product.bulk-delete') }}');
                bulkForm.querySelectorAll('input[name="product_ids[]"]').forEach((input) => input.remove());

                ids.forEach((id) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'product_ids[]';
                    hidden.value = id;
                    bulkForm.appendChild(hidden);
                });

                bulkForm?.submit();

                if (currentAction) {
                    bulkForm?.setAttribute('action', currentAction);
                }
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateBulkState);
            });

            updateBulkState();
        });
    </script>
@endsection
