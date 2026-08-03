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
                                    <li class="breadcrumb-item"><a href="{{ route('admins') }}">Admins</a>
                                    </li>
                                    <li class="breadcrumb-item active">New Account
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
                                    <h4 class="card-title">Admin Account</h4>
                                    <p>Create new Admin account</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('adminSave') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="form-group mb-50 col-sm-6 col-12">
                                                    <label class="text-bold-600" for="firstName">First Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="firstName"
                                                        name="first_name" value="{{ old('first_name') }}"
                                                        placeholder="First name" required>
                                                </div>
                                                <div class="form-group col-sm-6 col-12">
                                                    <label class="text-bold-600" for="lastName">Last Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="lastName"
                                                        name="last_name" value="{{ old('last_name') }}"
                                                        placeholder="Last name" required>
                                                </div>
                                                <div class="form-group col-sm-6 col-12">
                                                    <label for="text-bold-600">Email</label>
                                                    <input type="email" class="form-control" name="email"
                                                        value="{{ old('email') }}" placeholder="Enter email address"
                                                        id="file_name" required>
                                                </div>
                                                <div class="form-group col-sm-6 col-12">
                                                    <label for="text-bold-600">Phone</label>
                                                    <input type="text" class="form-control" name="phone"
                                                        value="{{ old('phone') }}" placeholder="Enter phone number"
                                                        id="phone" required>
                                                </div>
                                                <fieldset class="form-group col-sm-6 col-12"">
                                                    <label for="category">Status</label>
                                                    <select class="form-control" name="status" id="status" required>
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Suspended</option>
                                                        
                                                    </select>
                                                </fieldset>
                                                <fieldset class="form-group col-md-6">
                                                    <label class="text-bold-600" for="password">Password <span style="color:red">Leave empty to use the default password of: {{staffDefaultPassword()}}</span></label>
                                                    <input type="password" class="form-control" name="password"
                                                        value="{{ old('password') }}" placeholder="Enter password"
                                                        id="password">
                                                </fieldset>

                                                <fieldset class="form-group col-md-6">
                                                <label class="text-bold-600" for="file_name">Select Roles</label>
                                                <fieldset class="form-group">
                                                    @foreach ($roles as $key => $value)
                                                    <div class="checkbox checkbox-success">
                                                            <input type="checkbox" name="permissions[]" value="{{ $value->id }}" id="colorCheckbox{{ $value->id }}">
                                                            <label for="colorCheckbox{{ $value->id }}" class="mr-1">{{ $value->name }}</label>
                                                            </div>
                                                        @endforeach
                                                </fieldset>
                                            </fieldset>
                                            </div>
                                            
                                            <div class="">
                                                <button class="btn btn-primary" type="submit">Submit</button>
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
