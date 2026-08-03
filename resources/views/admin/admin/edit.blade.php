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
                                    <li class="breadcrumb-item active">Edit {{ $admin->firstname }}
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
                                    <h4 class="card-title">Edit Account</h4>
                                    <p>Update admin account information</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('updateAdmin') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="form-group mb-50 col-sm-6 col-12">
                                                    <label class="text-bold-600" for="firstName">First Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="firstName"
                                                        name="first_name" value="{{ $admin->firstname }}"
                                                        placeholder="First name" required>
                                                </div>
                                                <div class="form-group col-sm-6 col-12">
                                                    <label class="text-bold-600" for="lastName">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="lastName"
                                                        name="last_name" value="{{ $admin->lastname }}"
                                                        placeholder="Last name" required>
                                                </div>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                <label for="file_name">Email</label>
                                                <input type="email" class="form-control" name="email"
                                                    value="{{ $admin->email }}" placeholder="Enter email address"
                                                    id="file_name" required>
                                                </fieldset>
                                                <div class="form-group col-sm-6 col-12">
                                                    <label for="text-bold-600">Phone</label>
                                                    <input type="text" class="form-control" name="phone"
                                                        value="{{ $admin->phone }}" placeholder="Enter phone number"
                                                        id="phone" required>
                                                </div>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                    <label for="category">Status</label>
                                                    <select class="form-control" name="status" id="status" required>
                                                        <option value="active" {{ $admin->status == 'active' ? 'selected' :'' }}>Active</option>
                                                        <option value="inactive"  {{ $admin->status == 'inactive' ? 'selected' :''}}>Suspended</option>
                                                    </select>
                                                </fieldset>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                <label for="file_name">Roles</label>
                                                <fieldset class="form-group">
                                                    @foreach ($roles as $key => $value)
                                                    <div class="checkbox checkbox-success">
                                                            <input type="checkbox" name="roles[]"
                                                                value="{{ $value->id }}" id="colorCheckbox{{ $value->id }}" @checked(in_array($value->id, $permissions))>
                                                            <label for="colorCheckbox{{ $value->id }}" class="mr-1">{{ $value->name }}</label>
                                                            </div>
                                                        @endforeach
                                                </fieldset>
                                            </fieldset>
                                            <div class="form-group col-sm-6 col-12">
                                                <label for="text-bold-600">New Password <small>(Leave empty to use current password)</small></label>
                                                <input type="text" class="form-control" name="password"
                                                    value="" placeholder="Enter new password"
                                                    id="password" required>
                                            </div>
                                            </div>
                                            <div class="">
                                                <input type="hidden" value="{{ $admin->id }}" name="id" />
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
