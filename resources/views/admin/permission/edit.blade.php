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
                                    <li class="breadcrumb-item"><a href="{{ route('permission.index') }}">Permission</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit {{ $permission->name }}
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
                                    <h4 class="card-title">Edit {{ $permission->name }}</h4>
                                    <h6 style="color:red">Be Careful with these settings</h6>

                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('permission.update', $permission->id) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row">
                                                <div class="form-group mb-50 col-sm-6 col-12">
                                                    <label class="text-bold-600" for="name">Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name"
                                                        name="name" value="{{ $permission->name ?? old('name') }}"
                                                        placeholder="Name" required>
                                                </div>
                                                <div class="form-group mb-50 col-sm-6 col-12">
                                                    <label class="text-bold-600" for="route">Route (route to be used in the system) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="route"
                                                        name="route" value="{{ $permission->route ?? old('route') }}"
                                                        placeholder="Slug" required>
                                                </div>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                    <label for="status">Status</label>
                                                    <select class="form-control" name="status" id="status" required>
                                                        <option value="active" {{ $permission->status == 'active' ? 'selected' : ''}}>Active</option>
                                                        <option value="inactive" {{ $permission->status == 'inactive' ? 'selected' : ''}}>Inactive</option>
                                                    </select>
                                                </fieldset>
                                                <fieldset class="form-group col-sm-6 col-12">
                                                    <label for="type">Type</label>
                                                    <select class="form-control" name="type" id="type" required>
                                                        <option value="menu" {{ $permission->type == 'menu' ? 'selected' : ''}}>Menu</option>
                                                        <option value="link" {{ $permission->type == 'link' ? 'selected' : ''}}>Link</option>
                                                    </select>
                                                </fieldset>
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
