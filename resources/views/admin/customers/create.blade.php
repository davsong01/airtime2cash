@extends('layouts.app')
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
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('customer-blacklist.index') }}">Blacklists</a>
                                    </li>
                                    <li class="breadcrumb-item active">New Blacklist
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
                                    <h4 class="card-title">Add New Blacklist</h4>
                                    <p>Add new item to the black list</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('customer-blacklist.store') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="type">Type</label>
                                                        <select class="form-control" name="type" id="type" required>
                                                            <option value="">Select</option>
                                                            <option value="email"
                                                                {{ old('email') == 'email' ? 'selected' : '' }}>Email
                                                            </option>
                                                            <option value="biller"
                                                                {{ old('biller') == 'biller' ? 'selected' : '' }}>Biller
                                                            </option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="value">Blacklist Item</label>
                                                        <input type="text" class="form-control" name="value"
                                                            value="{{ old('value') }}"
                                                            placeholder="Enter item to blacklist" id="value" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="type">Status</label>
                                                        <select class="form-control" name="status" id="type" required>
                                                            <option value="">Select Status</option>
                                                            <option value="active"
                                                                {{ old('active') == 'active' ? 'selected' : '' }}>Active
                                                            </option>
                                                            <option value="in-active"
                                                                {{ old('in-active') == 'in-active' ? 'selected' : '' }}>In Active
                                                            </option>
                                                        </select>
                                                    </fieldset>
                                                    <div class="mt-2">
                                                        <button class="btn btn-primary" type="submit">Submit</button>
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
