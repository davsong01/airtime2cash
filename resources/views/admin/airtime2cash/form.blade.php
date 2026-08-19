@php
    $editing = $product !== null;
    $currency = getSettings()?->currency ?? 'NGN';
    $selectedCategory = old('category', $product?->category_id);
    $discountType = $product?->category?->discount_type ?? 'percentage';
    $rateUnit = $discountType === 'flat' ? $currency : '%';
@endphp

<form action="{{ $editing ? route('airtime2cash.update', $product->id) : route('airtime2cash.store') }}" method="POST" enctype="multipart/form-data" class="a2c-admin-form" id="airtime2cash-product-form">
    @csrf
    @if($editing)
        @method('PATCH')
    @endif

    <section class="a2c-form-hero">
        <div>
            <span class="a2c-form-kicker"><i class="bx bx-transfer-alt"></i> Airtime conversion</span>
            <h2>{{ $editing ? 'Edit ' . $product->name : 'Create Airtime to Cash product' }}</h2>
            <p>Configure how this network appears to customers, the amount limits, and the conversion rates applied.</p>
        </div>
        <div class="a2c-form-hero-status">
            <span>Product status</span>
            <strong>{{ ucfirst(old('status', $product?->status ?? 'inactive')) }}</strong>
        </div>
    </section>

    <div class="row">
        <div class="col-xl-8">
            <section class="card a2c-form-section">
                <div class="card-header a2c-section-heading">
                    <span class="a2c-section-icon"><i class="bx bx-cube"></i></span>
                    <div>
                        <h4>Product identity</h4>
                        <p>The customer-facing name, network category, image, and availability.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name">Product name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="e.g. MTN Airtime to Cash" value="{{ old('name', $product?->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="category">Network category <span class="text-danger">*</span></label>
                            <select class="form-control @error('category') is-invalid @enderror" name="category" id="category" required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="status">Availability <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" name="status" id="status" required>
                                <option value="active" @selected(old('status', $product?->status) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $product?->status ?? 'inactive') === 'inactive')>Inactive</option>
                            </select>
                            <small class="form-text text-muted">Inactive products are hidden from customer purchase flows.</small>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="image">Display image @unless($editing)<span class="text-danger">*</span>@endunless</label>
                            <div class="custom-file">
                                <input type="file" accept="image/jpeg,image/png" class="custom-file-input @error('image') is-invalid @enderror" id="image" name="image" @required(!$editing)>
                                <label class="custom-file-label" for="image">{{ $editing ? 'Replace current image' : 'Choose product image' }}</label>
                            </div>
                            <small class="form-text text-muted">JPEG or PNG, maximum 1 MB.</small>
                            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="card a2c-form-section">
                <div class="card-header a2c-section-heading">
                    <span class="a2c-section-icon is-gold"><i class="bx bx-slider-alt"></i></span>
                    <div>
                        <h4>Conversion rules</h4>
                        <p>Set the default charge and permitted transaction range.</p>
                    </div>
                </div>
                <div class="card-body">
                    <input type="hidden" name="fixed_price" value="no">
                    <ul class="nav nav-pills a2c-rate-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="manual-rate-tab" data-toggle="pill" href="#manual-rate-panel" role="tab" aria-controls="manual-rate-panel" aria-selected="true">
                                <i class="bx bx-hand mr-50"></i>Manual Transfer
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="auto-share-rate-tab" data-toggle="pill" href="#auto-share-rate-panel" role="tab" aria-controls="auto-share-rate-panel" aria-selected="false">
                                <i class="bx bx-bolt-circle mr-50"></i>Auto Share
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content a2c-rate-tab-content">
                        <div class="tab-pane fade show active" id="manual-rate-panel" role="tabpanel" aria-labelledby="manual-rate-tab">
                            <div class="a2c-rate-panel-header">
                                <div class="a2c-rate-panel-copy">
                                    <strong>Manual transfer rate</strong>
                                    <span>Applied when customers manually send airtime to the destination number.</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="manual_status" value="inactive">
                                    <input type="checkbox" class="custom-control-input" id="manual_status" name="manual_status" value="active" @checked(old('manual_status', $product?->manual_status ?? 'active') === 'active')>
                                    <label class="custom-control-label" for="manual_status"><span class="sr-only">Toggle manual transfers</span></label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-0">
                                    <label for="rate">Charge rate ({{ $rateUnit }}) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('rate') is-invalid @enderror" id="rate" name="rate" value="{{ old('rate', $product?->rate) }}" required>
                                        <div class="input-group-append"><span class="input-group-text">{{ $rateUnit }}</span></div>
                                        @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label for="manual_profit_percentage">Profit Percentage (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('manual_profit_percentage') is-invalid @enderror" id="manual_profit_percentage" name="manual_profit_percentage" value="{{ old('manual_profit_percentage', $product?->manual_profit_percentage) }}" placeholder="0.00">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                        @error('manual_profit_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <small class="form-text text-muted">Manual income recorded in admin logs uses this percentage.</small>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6 form-group mb-0">
                                    <label for="manual_min">Minimum amount ({{ $currency }})</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                        <input type="number" step="0.01" min="0" class="form-control @error('manual_min') is-invalid @enderror" id="manual_min" name="manual_min" value="{{ old('manual_min', $product?->manual_min) }}" placeholder="Use shared range if blank or 0">
                                        @error('manual_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label for="manual_max">Maximum amount ({{ $currency }})</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                        <input type="number" step="0.01" min="0" class="form-control @error('manual_max') is-invalid @enderror" id="manual_max" name="manual_max" value="{{ old('manual_max', $product?->manual_max) }}" placeholder="Use shared range if blank or 0">
                                        @error('manual_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="a2c-level-rates">
                                <div class="a2c-level-rates-heading mt-2">
                                    <strong>Customer level overrides</strong>
                                    <span>Leave blank or set 0 to use the main manual charge rate.</span>
                                </div>
                                <div class="row">
                                    @forelse($customerlevel as $level)
                                        <div class="col-md-6 form-group mb-3">
                                            <label for="manual-level-rate-{{ $level->id }}">{{ $level->name }} override ({{ $rateUnit }})</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" class="form-control @error('manual_level_rate.' . $level->id) is-invalid @enderror" id="manual-level-rate-{{ $level->id }}" name="manual_level_rate[{{ $level->id }}]" value="{{ old('manual_level_rate.' . $level->id, $editing ? $product?->customer_level_transfer_price($level->id, 'manual') : '') }}" placeholder="Use main manual rate">
                                                <div class="input-group-append"><span class="input-group-text">{{ $rateUnit }}</span></div>
                                                @error('manual_level_rate.' . $level->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12"><div class="alert alert-light mb-0">No customer levels are configured yet.</div></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="auto-share-rate-panel" role="tabpanel" aria-labelledby="auto-share-rate-tab">
                            <div class="a2c-rate-panel-header">
                                <div class="a2c-rate-panel-copy">
                                    <strong>Auto Share rate</strong>
                                    <span>Applied when airtime is transferred automatically through the Auto Share integration.</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="auto_share_status" value="inactive">
                                    <input type="checkbox" class="custom-control-input" id="auto_share_status" name="auto_share_status" value="active" @checked(old('auto_share_status', $product?->auto_share_status ?? 'inactive') === 'active')>
                                    <label class="custom-control-label" for="auto_share_status"><span class="sr-only">Toggle Auto Share transfers</span></label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group mb-0">
                                    <label for="auto_share_rate">Charge rate ({{ $rateUnit }}) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('auto_share_rate') is-invalid @enderror" id="auto_share_rate" name="auto_share_rate" value="{{ old('auto_share_rate', $product?->auto_share_rate ?? $product?->rate) }}" required>
                                        <div class="input-group-append"><span class="input-group-text">{{ $rateUnit }}</span></div>
                                        @error('auto_share_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label for="auto_share_profit_percentage">Profit Percentage (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('auto_share_profit_percentage') is-invalid @enderror" id="auto_share_profit_percentage" name="auto_share_profit_percentage" value="{{ old('auto_share_profit_percentage', $product?->auto_share_profit_percentage) }}" placeholder="0.00">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                        @error('auto_share_profit_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <small class="form-text text-muted">Auto share income recorded in admin logs uses this percentage.</small>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6 form-group mb-0">
                                    <label for="auto_share_min">Minimum amount ({{ $currency }})</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                        <input type="number" step="0.01" min="0" class="form-control @error('auto_share_min') is-invalid @enderror" id="auto_share_min" name="auto_share_min" value="{{ old('auto_share_min', $product?->auto_share_min) }}" placeholder="Use shared range if blank or 0">
                                        @error('auto_share_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label for="auto_share_max">Maximum amount ({{ $currency }})</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                        <input type="number" step="0.01" min="0" class="form-control @error('auto_share_max') is-invalid @enderror" id="auto_share_max" name="auto_share_max" value="{{ old('auto_share_max', $product?->auto_share_max) }}" placeholder="Use shared range if blank or 0">
                                        @error('auto_share_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-2 mb-0">
                                <label for="auto_share_product_code">AutoSync product code</label>
                                <input type="text" class="form-control @error('auto_share_product_code') is-invalid @enderror" id="auto_share_product_code" name="auto_share_product_code" value="{{ old('auto_share_product_code', $product?->auto_share_product_code) }}" placeholder="e.g. mtn">
                                @error('auto_share_product_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="form-text text-muted">Required when Auto Share is active. Use AutoSync's product ID/code for this network.</small>
                            </div>

                            <div class="a2c-level-rates">
                                <div class="a2c-level-rates-heading mt-2">
                                    <strong>Customer level overrides</strong>
                                    <span>Leave blank or set 0 to use the main auto charge rate.</span>
                                </div>
                                <div class="row">
                                    @forelse($customerlevel as $level)
                                        <div class="col-md-6 form-group mb-3">
                                            <label for="auto-share-level-rate-{{ $level->id }}">{{ $level->name }} override ({{ $rateUnit }})</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" class="form-control @error('auto_share_level_rate.' . $level->id) is-invalid @enderror" id="auto-share-level-rate-{{ $level->id }}" name="auto_share_level_rate[{{ $level->id }}]" value="{{ old('auto_share_level_rate.' . $level->id, $editing ? $product?->customer_level_transfer_price($level->id, 'auto_share') : '') }}" placeholder="Use main auto rate">
                                                <div class="input-group-append"><span class="input-group-text">{{ $rateUnit }}</span></div>
                                                @error('auto_share_level_rate.' . $level->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12"><div class="alert alert-light mb-0">No customer levels are configured yet.</div></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="a2c-shared-limits">
                        <div class="a2c-shared-limits-heading">
                            <strong>Shared transaction range</strong>
                            <span>These limits currently apply to both transfer methods.</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                            <label for="min">Minimum amount ({{ $currency }}) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                <input type="number" step="0.01" min="0" class="form-control" id="min" name="min" value="{{ old('min', $product?->min) }}" required>
                            </div>
                        </div>
                            <div class="col-md-6 form-group">
                            <label for="max">Maximum amount ({{ $currency }}) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                <input type="number" step="0.01" min="0" class="form-control" id="max" name="max" value="{{ old('max', $product?->max) }}" required>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="a2c-rule-note"><i class="bx bx-info-circle"></i> The maximum amount should be greater than the minimum amount.</div>
                </div>
            </section>

            <section class="card a2c-form-section">
                <div class="card-header a2c-section-heading">
                    <span class="a2c-section-icon is-purple"><i class="bx bx-message-square-detail"></i></span>
                    <div>
                        <h4>Customer content</h4>
                        <p>Explain the service and give customers clear transfer instructions.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="description">Short description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Briefly explain this conversion product">{{ old('description', $product?->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="instruction-editor">Manual Transfer instructions</label>
                        <small class="form-text text-muted mb-1">Shown after the customer selects a network for a manual transfer.</small>
                        <div id="manual-instruction-toolbar">
                            <span class="ql-formats"><select class="ql-header"><option selected></option><option value="2"></option><option value="3"></option></select></span>
                            <span class="ql-formats"><button type="button" class="ql-bold"></button><button type="button" class="ql-italic"></button><button type="button" class="ql-underline"></button></span>
                            <span class="ql-formats"><button type="button" class="ql-list" value="ordered"></button><button type="button" class="ql-list" value="bullet"></button></span>
                            <span class="ql-formats"><button type="button" class="ql-link"></button><button type="button" class="ql-clean"></button></span>
                        </div>
                        <div class="editor" id="instruction-editor">{!! old('instruction', $product?->instruction) !!}</div>
                        <input name="instruction" type="hidden" id="instruction-content">
                    </div>
                    <div class="form-group mb-0">
                        <label for="auto-share-instruction-editor">Auto Transfer instructions</label>
                        <small class="form-text text-muted mb-1">Shown immediately when the customer chooses Auto Transfer.</small>
                        <div id="auto-share-instruction-toolbar">
                            <span class="ql-formats"><select class="ql-header"><option selected></option><option value="2"></option><option value="3"></option></select></span>
                            <span class="ql-formats"><button type="button" class="ql-bold"></button><button type="button" class="ql-italic"></button><button type="button" class="ql-underline"></button></span>
                            <span class="ql-formats"><button type="button" class="ql-list" value="ordered"></button><button type="button" class="ql-list" value="bullet"></button></span>
                            <span class="ql-formats"><button type="button" class="ql-link"></button><button type="button" class="ql-clean"></button></span>
                        </div>
                        <div class="editor" id="auto-share-instruction-editor">{!! old('auto_share_instruction', $product?->auto_share_instruction) !!}</div>
                        <input name="auto_share_instruction" type="hidden" id="auto-share-instruction-content">
                    </div>
                </div>
            </section>

            <section class="card a2c-form-section">
                <div class="card-header a2c-section-heading">
                    <span class="a2c-section-icon is-slate"><i class="bx bx-search-alt"></i></span>
                    <div>
                        <h4>Search information</h4>
                        <p>Optional metadata used when this product is indexed or shared.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="seo_title">SEO title</label>
                            <input type="text" class="form-control" id="seo_title" name="seo_title" placeholder="Search-friendly page title" value="{{ old('seo_title', $product?->seo_title) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="seo_keywords">SEO keywords</label>
                            <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" placeholder="airtime, cash, network" value="{{ old('seo_keywords', $product?->seo_keywords) }}">
                        </div>
                        <div class="col-12 form-group mb-0">
                            <label for="seo_description">SEO description</label>
                            <textarea class="form-control" id="seo_description" rows="3" name="seo_description" placeholder="Short description for search results">{{ old('seo_description', $product?->seo_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <aside class="a2c-form-aside">
                <div class="card a2c-preview-card">
                    <div class="card-body">
                        <span class="a2c-aside-label">Product preview</span>
                        <div class="a2c-product-image-wrap">
                            <img id="product-image-preview" src="{{ $editing && $product->image ? asset($product->image) : asset('app-assets/images/placeholder/product-placeholder.png') }}" alt="Product image preview" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="a2c-image-fallback" style="display:none"><i class="bx bx-image"></i></span>
                        </div>
                        <h4 id="product-name-preview">{{ old('name', $product?->name ?? 'New conversion product') }}</h4>
                        <p>Review the key setup before saving. Changes become available according to the selected status.</p>
                    </div>
                </div>

                <div class="card a2c-save-card">
                    <div class="card-body">
                        <h5>{{ $editing ? 'Ready to update?' : 'Ready to create?' }}</h5>
                        <p>{{ $editing ? 'Save the configuration changes for this product.' : 'Create this product and continue managing it.' }}</p>
                        <button class="btn btn-primary btn-block" type="submit">
                            <i class="bx bx-save mr-50"></i>{{ $editing ? 'Save product changes' : 'Create product' }}
                        </button>
                        <a href="{{ route('airtime2cash.index') }}" class="btn btn-outline-secondary btn-block">Cancel</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</form>
