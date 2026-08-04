@extends('sneat.layouts.app')

@section('title', $category->seo_title)
@section('keywords', $category->seo_keywords)
@section('description', $category->seo_description)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/select2/select2.css') }}" />
@endsection

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
                                            data-discounted_rate="{{ $item->discounted_rate }}"
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
                                    <span class="input-group-text"><i class="bx bx-percentage"></i></span>
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
                        <div class="form-check p-3 ps-5 rounded bg-label-primary">
                            <input type="checkbox" class="form-check-input" id="agreement" required>
                            <label class="form-check-label" for="agreement">I have read and agree to these instructions</label>
                        </div>
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

            $('#amount').val('');
            $('#product').val('').trigger('change.select2');
            $('#payment_method').val('').trigger('change.select2');

            $('#product').on('change', function () {
                const selected = $('#product').find(':selected');
                const product = selected.val();
                const discountedRate = parseFloat(selected.data('discounted_rate'));
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
