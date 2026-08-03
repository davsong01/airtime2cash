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
                                    <li class="breadcrumb-item"><a href="{{ route('api.index') }}">API Provider</a>
                                    </li>
                                    <li class="breadcrumb-item active">Add API Provider
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
                                    <h4 class="card-title">Add Provider</h4>
                                    <p>Add new provider</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('api.store')}}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="slug">Slug</label>
                                                        <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" placeholder="Enter slug" id="slug" required>
                                                    </fieldset>
                                                   
                                                    <fieldset class="form-group">
                                                        <label for="warning_threshold_status">Warning Threshold Status</label>
                                                        <select class="form-control" name="warning_threshold_status" id="warning_threshold_status" required>
                                                            <option value="">Select</option>
                                                            <option value="active" {{ old('warning_threshold_status') == 'active' ? 'selected' : ''}}>Active</option>
                                                            <option value="inactive" {{ old('warning_threshold_status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="warning_threshold">Balance Warning Threshold</label>
                                                        <input type="number" class="form-control" name="warning_threshold" value="{{ old('warning_threshold') }}" placeholder="Enter warning threshold" id="warning_threshold">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="sandbox_base_url">Sandbox Base URL</label>
                                                        <input type="text" class="form-control" name="sandbox_base_url" value="{{ old('sandbox_base_url') }}" placeholder="Enter warning threshold" id="warning_threshold">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="live_base_url">Live Base URL</label>
                                                        <input type="text" class="form-control" name="live_base_url" value="{{ old('live_base_url') }}" placeholder="Enter warning threshold" id="warning_threshold">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-6">
                                                   
                                                    <fieldset class="form-group">
                                                        <label for="status">API Status</label>
                                                        <select class="form-control" name="status" id="status" required>
                                                            <option value="">Select</option>
                                                            <option value="active" {{ old('status') == 'active' ? 'selected' : ''}}>Active</option>
                                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="file_name">File Name</label>
                                                        <input type="text" class="form-control" name="file_name" value="{{ old('file_name') }}" placeholder="Enter file name" id="file_name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="api_key">API Key</label>
                                                        <input type="text" class="form-control" name="api_key" value="{{ old('api_key') }}" placeholder="Enter api key" id="api_key">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="secret_key">Secret Key</label>
                                                        <input type="text" class="form-control" name="secret_key" value="{{ old('secret_key') }}" placeholder="Enter secret key" id="secret_key">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="public_key">Public Key</label>
                                                        <input type="text" class="form-control" name="public_key" value="{{ old('public_key') }}"  placeholder="Enter public key" id="public_key">
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-12">
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