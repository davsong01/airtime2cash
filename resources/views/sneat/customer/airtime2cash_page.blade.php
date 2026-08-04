@extends('sneat.layouts.app')

@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
@endsection

@php
    $manualProducts = $category->products->where('manual_status', 'active')->count();
    $autoShareProducts = $category->products->where('auto_share_status', 'active')->count();
    $defaultTransferMode = old('transfer_mode', $manualProducts ? 'manual' : 'auto_share');
@endphp

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Conversion',
        'title' => 'Airtime to Cash',
        'subtitle' => 'Choose your network, enter the amount, and complete the conversion.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card purchase-card">
                <form action="{{ route('initialize.airtime2cashtransaction') }}" method="POST" id="initialize" class="purchase-form">
                    @csrf
                    <div class="card-header d-flex align-items-center gap-3 border-bottom">
                        <span class="purchase-heading-icon bg-label-success">
                            <i class="bx bx-transfer-alt fs-4"></i>
                        </span>
                        <div>
                            <h5 class="mb-1">Conversion details</h5>
                            <small class="text-muted">Enter the airtime and payout information.</small>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Transfer method</label>
                                <div class="row g-3 conversion-mode-options">
                                    <div class="col-md-6">
                                        <input class="btn-check" type="radio" name="transfer_mode" id="transfer-mode-manual" value="manual" autocomplete="off" @checked($defaultTransferMode === 'manual') @disabled(!$manualProducts)>
                                        <label class="conversion-mode-option" for="transfer-mode-manual">
                                            <span class="conversion-mode-icon bg-label-success"><i class="bx bx-hand"></i></span>
                                            <span><strong>Manual Transfer</strong><small>Send airtime manually to our number</small></span>
                                            <i class="bx bx-check-circle conversion-mode-check"></i>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input class="btn-check" type="radio" name="transfer_mode" id="transfer-mode-auto" value="auto_share" autocomplete="off" @checked($defaultTransferMode === 'auto_share') @disabled(!$autoShareProducts)>
                                        <label class="conversion-mode-option" for="transfer-mode-auto">
                                            <span class="conversion-mode-icon bg-label-warning"><i class="bx bx-bolt-circle"></i></span>
                                            <span><strong>Auto Transfer</strong><small>Airtime moved through Auto Share</small></span>
                                            <i class="bx bx-check-circle conversion-mode-check"></i>
                                        </label>
                                    </div>
                                </div>
                                @if(!$manualProducts || !$autoShareProducts)
                                    <div class="form-text">Unavailable methods are disabled until a network is enabled by the administrator.</div>
                                @endif
                            </div>

                            <div class="col-12" id="product-image-div" style="display:none">
                                <div class="purchase-product-preview d-flex align-items-center gap-3 p-3 rounded">
                                    <img id="product-image" src="" alt="" class="rounded">
                                    <div class="min-w-0">
                                        <h6 id="product-title" class="mb-1"></h6>
                                        <p id="product-description" class="text-muted small mb-0"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="product" class="form-label">Network</label>
                                <select class="form-select modern-select2" name="product" id="product" data-placeholder="Search networks" required>
                                    <option value="">Select a network</option>
                                    @foreach ($category->products as $item)
                                        <option
                                            value="{{ $item->id }}"
                                            data-manual_status="{{ $item->manual_status }}"
                                            data-auto_share_status="{{ $item->auto_share_status }}"
                                            data-manual_rate="{{ $item->manual_discounted_rate }}"
                                            data-auto_share_rate="{{ $item->auto_share_discounted_rate }}"
                                            data-min="{{ $item->min }}"
                                            data-max="{{ $item->max }}"
                                            data-image="{{ asset($item->image) }}"
                                            data-name="{{ $item->name }}"
                                            data-instruction="{{ $item->instruction }}"
                                            data-description="{{ $item->description }}"
                                        >
                                            {{ $item->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6" id="rate-div" style="display:none">
                                <label for="rate" class="form-label">Charge rate (%)</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-semibold" aria-hidden="true">%</span>
                                    <input class="form-control" id="rate" name="rate" type="number" disabled>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email ?? old('email') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone number sending airtime</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" required>
                            </div>

                            <div class="col-md-6" id="amount-div" style="display:none">
                                <label for="amount" class="form-label">Airtime amount ({{ getSettings()['currency'] }})</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ getSettings()['currency'] }}</span>
                                    <input class="form-control" id="amount" name="amount" placeholder="Enter amount" type="number" required>
                                </div>
                                <div class="form-text" id="airtime-range"></div>
                            </div>

                            <div class="col-md-6" id="receive-div" style="display:none">
                                <label for="receive" class="form-label">Amount to receive ({{ getSettings()['currency'] }})</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ getSettings()['currency'] }}</span>
                                    <input class="form-control fw-semibold" id="receive" name="receive" type="number" disabled>
                                </div>
                            </div>

                            <div class="col-12" id="payment-div" style="display:none">
                                <label for="payment_method" class="form-label">Payout destination</label>
                                <select class="form-select modern-select2" name="payment_method" id="payment_method" data-placeholder="Search payout options" required>
                                    <option value="">Select payout destination</option>
                                    <option value="Transfer to Bank Account">Bank account</option>
                                    <option value="Transfer to Wallet">Airtime2Cash wallet</option>
                                </select>
                            </div>

                            <div class="col-12" id="bank-details-div" style="display:none">
                                <div class="rounded border bg-body-tertiary p-3 p-md-4">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="bx bx-building-house text-primary fs-5"></i>
                                        <h6 class="mb-0">Bank account</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="bank" class="form-label">Bank</label>
                                            <select class="form-select modern-select2" name="bank" id="bank" data-placeholder="Search banks">
                                                <option value="">Select a bank</option>
                                                @foreach($banks as $bank)
                                                    <option value="{{ $bank->cbn_code }}">{{ $bank->bank_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="account_number" class="form-label">Account number</label>
                                            <input class="form-control" id="account_number" name="account_number" type="text" inputmode="numeric">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="account_name" class="form-label">Account name</label>
                                            <input class="form-control" id="account_name" name="account_name" type="text">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end border-top bg-transparent p-4">
                        <button id="buy-button" class="purchase-submit btn btn-primary" type="submit" onclick="return submitForm()">
                            <i class="bx bx-transfer me-1"></i>
                            <span>Submit conversion</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3 border-bottom">
                    <span class="purchase-heading-icon bg-label-info">
                        <i class="bx bx-info-circle fs-4"></i>
                    </span>
                    <div>
                        <h5 class="mb-1">Transfer instructions</h5>
                        <small class="text-muted">Instructions depend on the selected network.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div id="instruction-empty" class="text-center py-3">
                        <i class="bx bx-mobile-alt fs-1 text-muted mb-2"></i>
                        <p class="text-muted mb-0">Select a network to view its transfer instructions.</p>
                    </div>
                    <div id="instruction-div" style="display:none">
                        <div id="instruction" class="text-body mb-4"></div>
                        <label class="conversion-agreement" for="agreement" id="agreement-panel">
                            <input type="checkbox" class="form-check-input conversion-agreement-check" id="agreement" required>
                            <span class="conversion-agreement-icon"><i class="bx bx-check-shield"></i></span>
                            <span class="conversion-agreement-copy">
                                <strong>I have read and agree to these instructions</strong>
                                <small>Confirm this before submitting your airtime conversion.</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('modern-assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        function submitForm() {
            const form = document.getElementById('initialize');
            const requiredFields = form.querySelectorAll('[required]');

            for (let i = 0; i < requiredFields.length; i++) {
                const field = requiredFields[i];
                const fieldColumn = field.closest('.col-md-4, .col-md-6, .col-12');
                const isVisible = !fieldColumn || fieldColumn.offsetParent !== null;

                if (isVisible && !field.value.trim()) {
                    if (field.tagName === 'SELECT' && $(field).hasClass('select2-hidden-accessible')) {
                        $(field).select2('open');
                    } else {
                        field.focus();
                    }

                    alert('Please complete all required fields');
                    return false;
                }
            }

            if ($('#instruction-div').is(':visible') && !$('#agreement').prop('checked')) {
                $('#agreement-panel').addClass('is-required');
                $('#agreement').trigger('focus');
                alert('You must agree to the transfer instructions');
                return false;
            }

            const button = document.getElementById('buy-button');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span><span>Processing...</span>';
            form.submit();

            return false;
        }

        $(document).ready(function () {
            $('.modern-select2').each(function () {
                const select = $(this);
                select.select2({
                    width: '100%',
                    placeholder: select.data('placeholder'),
                    minimumResultsForSearch: 0
                });
            });

            const productSelect = $('#product');
            const allProductOptions = productSelect.find('option').clone();

            function refreshNetworks() {
                const transferMode = $('input[name="transfer_mode"]:checked').val();
                const statusKey = transferMode === 'auto_share' ? 'auto_share_status' : 'manual_status';

                productSelect.empty();
                allProductOptions.each(function (index) {
                    const option = $(this);
                    if (index === 0 || option.data(statusKey) === 'active') {
                        productSelect.append(option.clone());
                    }
                });

                productSelect.val('').trigger('change');
            }

            $('input[name="transfer_mode"]').on('change', refreshNetworks);
            $('#agreement').on('change', function () {
                $('#agreement-panel').removeClass('is-required');
            });
            refreshNetworks();
            $('#amount').val('');
            $('#payment_method').val('').trigger('change.select2');

            $('#product').on('change', function () {
                const selected = $('#product').find(':selected');
                const product = selected.val();
                const transferMode = $('input[name="transfer_mode"]:checked').val();
                const discountedRate = parseFloat(transferMode === 'auto_share' ? selected.data('auto_share_rate') : selected.data('manual_rate'));
                const max = parseFloat(selected.data('max'));
                const min = parseFloat(selected.data('min'));

                $('#agreement').prop('checked', false);
                $('#amount').val('');
                $('#receive').val('');
                $('#payment_method').val('').trigger('change');
                $('#receive-div, #payment-div, #bank-details-div').hide();

                if (!product) {
                    $('#rate').val('');
                    $('#rate-div, #instruction-div, #amount-div, #product-image-div').hide();
                    $('#instruction-empty').show();
                    return;
                }

                const image = selected.data('image');
                const title = selected.data('name');
                const description = selected.data('description');
                const instruction = selected.data('instruction');

                $('#product-image').attr('src', image);
                $('#product-title').text(title);
                $('#product-description').text(description);
                $('#instruction').html(instruction);
                $('#rate').val(discountedRate);

                if (Number.isFinite(min)) {
                    $('#amount').attr('min', min);
                } else {
                    $('#amount').removeAttr('min');
                }

                if (Number.isFinite(max)) {
                    $('#amount').attr('max', max);
                } else {
                    $('#amount').removeAttr('max');
                }

                if (Number.isFinite(min) && Number.isFinite(max)) {
                    $('#airtime-range').text(`Allowed range: {{ getSettings()['currency'] }}${min.toLocaleString()} - {{ getSettings()['currency'] }}${max.toLocaleString()}`).show();
                } else {
                    $('#airtime-range').hide();
                }

                $('#product-image-div, #rate-div, #instruction-div, #amount-div').show();
                $('#instruction-empty').hide();
            });

            $('#payment_method').on('change', function () {
                const useBank = this.value === 'Transfer to Bank Account';

                $('#bank-details-div').toggle(useBank);
                $('#bank, #account_number, #account_name').prop('required', useBank);

                if (!useBank) {
                    $('#bank').val('').trigger('change.select2');
                    $('#account_number, #account_name').val('');
                }
            });

            $('#amount').on('input', function () {
                const rate = parseFloat($('#rate').val());
                const amount = parseFloat(this.value);
                const min = parseFloat(this.min);
                const max = parseFloat(this.max);
                const isValidAmount = Number.isFinite(amount)
                    && Number.isFinite(rate)
                    && (!Number.isFinite(min) || amount >= min)
                    && (!Number.isFinite(max) || amount <= max);

                if (isValidAmount) {
                    const receive = amount - ((rate / 100) * amount);
                    $('#receive').val(receive.toFixed(2));
                    $('#receive-div, #payment-div').show();
                } else {
                    $('#receive').val('');
                    $('#payment_method').val('').trigger('change');
                    $('#receive-div, #payment-div, #bank-details-div').hide();
                }
            });
        });
    </script>
@endsection
