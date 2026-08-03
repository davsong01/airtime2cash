@extends('layouts.app')
@section('title', 'Edit ' .$paymentgateway->name)
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
                                    <li class="breadcrumb-item"><a href="{{ route('paymentgateway.index') }}">Payment Gateways</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit {{ $paymentgateway->name }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Basic Inputs start -->
                <section id="basic-input">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Edit {{ $paymentgateway->name }}</h4>
                                    {{-- <p>Add new category</p> --}}
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('paymentgateway.update', $paymentgateway->id)}}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ $paymentgateway->name ?? old('name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="slug">Slug</label>
                                                        <input type="text" class="form-control" id="slug" name="slug" value="{{ $paymentgateway->slug ?? old('slug') }}" placeholder="Enter slug" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="password">Gateway Password</label>
                                                        <input type="text" class="form-control" id="password" name="password" value="{{ $paymentgateway->password ?? old('password') }}" placeholder="Enter Password">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="api_key">API key</label>
                                                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ $paymentgateway->api_key ?? old('api_key') }}" placeholder="Enter API Key">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="secret_key">Secret key</label>
                                                        <input type="text" class="form-control" id="secret_key" name="secret_key" value="{{ $paymentgateway->secret_key ?? old('secret_key') }}" placeholder="Enter Secret Key">
                                                    </fieldset>
                                                    {{-- <fieldset class="form-group">
                                                        <label for="public_key">Public key</label>
                                                        <input type="text" class="form-control" id="public_key" name="public_key" value="{{ $paymentgateway->public_key ?? old('public_key') }}" placeholder="Enter Public Key">
                                                    </fieldset> --}}
                                                    <fieldset class="form-group">
                                                        <label for="contract_id">Contract ID</label>
                                                        <input type="text" class="form-control" id="contract_id" name="contract_id" value="{{ $paymentgateway->contract_id ?? old('contract_id') }}" placeholder="Enter Contract Id">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-6">
                                                    
                                                    <fieldset class="form-group">
                                                        <label for="merchant_email">Merchant Email</label>
                                                        <input type="text" class="form-control" id="merchant_email" name="merchant_email" value="{{ $paymentgateway->merchant_email ?? old('merchant_email') }}" placeholder="Enter Merchant email">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="base_url">Base URL</label>
                                                        <input type="text" class="form-control" id="base_url" name="base_url" value="{{ $paymentgateway->base_url ?? old('base_url') }}" placeholder="Enter base URL" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="charge">Gateway Charge for card funding <span style="color:red">(%)</span></label>
                                                        <input type="number" class="form-control" id="charge" name="charge" step=".10" value="{{ $paymentgateway->charge ?? old('charge') }}" placeholder="Enter gatway charge in percentage" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="reserved_account_payment_charge_type">Gateway Reserved Account Payment Charge Type</label>
                                                        <select class="form-control" name="reserved_account_payment_charge_type" id="reserved_account_payment_charge_type" required>
                                                            <option value="">Select</option>
                                                            <option value="flat" {{ $paymentgateway->reserved_account_payment_charge_type == 'flat' ? 'selected' : ''}}>Flat</option>
                                                            <option value="percentage" {{ $paymentgateway->reserved_account_payment_charge_type == 'percentage' ? 'selected' : ''}}>Percentage</option>
                                                        </select>
                                                    </fieldset>
                                                    
                                                    <fieldset class="form-group">
                                                        <label for="reserved_account_payment_charge">Gateway Reserved Account Payment Charge <span style="color:red">({{ getPaymentGatewayReservedAccountCharge($paymentgateway->id)['type'] }}) </span></label>
                                                        <input type="number" class="form-control" id="reserved_account_payment_charge" name="reserved_account_payment_charge" step=".10" value="{{ $paymentgateway->reserved_account_payment_charge ?? old('reserved_account_payment_charge') }}" placeholder="Enter flat reserved account payment charge" required>
                                                    </fieldset>
                                                    
                                                    {{-- <fieldset class="form-group">
                                                        <label for="status">Gateway Status</label>
                                                        <select class="form-control" name="status" id="status" required>
                                                            <option value="">Select</option>
                                                            <option value="active" {{ $paymentgateway->status == 'active' ? 'selected' : ''}}>Active</option>
                                                            <option value="inactive" {{ $paymentgateway->status == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                        </select>
                                                    </fieldset> --}}
                                                </div>
                                                <div class="col-md-12">
                                                <button class="btn btn-primary" type="submit">Update Settings</button>
    
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>

@endsection