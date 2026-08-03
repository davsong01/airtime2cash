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
                                    <li class="breadcrumb-item"><a href="{{ route('role.index') }}">Roles</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit {{ $role->name }}
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
                                    <h4 class="card-title">Edit {{ $role->name }}</h4>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('role.update', $role->id) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row">
                                                <div class="form-group mb-50 col-sm-6 col-12">
                                                    <label class="text-bold-600" for="name">Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name"
                                                        name="name" value="{{ $role->name ?? old('name') }}"
                                                        placeholder="Name" required>
                                                </div>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                    <label for="category">Status</label>
                                                    <select class="form-control" name="status" id="status" required>
                                                        <option value="active" {{ $role->status == 'active' ? 'selected' : ''}}>Active</option>
                                                        <option value="inactive" {{ $role->status == 'inactive' ? 'selected' : ''}}>Inactive</option>
                                                    </select>
                                                </fieldset>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5>Menus</h5>
                                                </div>
                                                @foreach ($menus as $value)
                                                <fieldset class="form-group col-md-3">
                                                    <div class="checkbox checkbox-shadow checkbox-sm selectAll mr-50">
                                                        <input type="checkbox" name="menus[]" id="menus-{{$value->id}}" value="{{$value->id}}" {{in_array($value->id, $rolePermissions) ? 'checked' : ''}}>
                                                        <label for="menus-{{$value->id}}">{{$value->name}}</label>
                                                    </div>
                                                </fieldset>
                                                @endforeach
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5>Permissions</h5>
                                                </div>
                                                @foreach ($permissions as $value)
                                                <fieldset class="form-group col-md-3">
                                                    <div class="checkbox checkbox-shadow checkbox-sm selectAll mr-50">
                                                        <input type="checkbox" name="permissions[]" id="permissions-{{$value->id}}" value="{{$value->id}}" {{in_array($value->id, $rolePermissions) ? 'checked' : ''}}>
                                                        <label for="permissions-{{$value->id}}">{{$value->name}}</label>
                                                    </div>
                                                </fieldset>
                                                @endforeach
                                            </div>
                                            <div class="">
                                                <button class="btn btn-primary" type="submit">Update</button>
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
