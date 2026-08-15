@extends('layouts.app')
@section('title', 'Edit ' .$customerlevel->name)
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
                                    <li class="breadcrumb-item"><a href="{{ route('customerlevel.index') }}">Customer Levels</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit {{ $customerlevel->name }}
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
                                    <h4 class="card-title">Edit {{ $customerlevel->name }}</h4>
                                    {{-- <p>Add new category</p> --}}
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('customerlevel.update', $customerlevel->id)}}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ $customerlevel->name ?? old('name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                     <fieldset class="form-group">
                                                        <label for="upgrade_amount">Upgrade Amount</label>
                                                        <input type="text" class="form-control" id="upgrade_amount" name="upgrade_amount" value="{{ $customerlevel->upgrade_amount ??  old('upgrade_amount') }}" placeholder="Enter Upgrade amount" required>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-6">
                                                    {{-- <fieldset class="form-group">
                                                        <label for="make_api_level">Make API Level</label>
                                                        <select class="form-control" name="make_api_level" id="make_api_level" required>
                                                            <option value="">Select</option>
                                                            <option value="yes" {{ $customerlevel->make_api_level == 'yes' ? 'selected' : '' }}>Yes
                                                            </option>
                                                            <option value="no" {{ $customerlevel->make_api_level == 'make_api_level' ? 'selected' : '' }}>No
                                                            </option>
                                                        </select>
                                                    </fieldset> --}}
                                                    <fieldset class="form-group">
                                                        <label for="order">Order</label>
                                                        <input type="number" class="form-control" name="order" value="{{ $customerlevel->order ?? old('order') }}" placeholder="Enter order" id="order" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="status">Status</label>
                                                        <select class="form-control" name="status" id="status" required>
                                                            <option value="1" {{ old('status', (int) $customerlevel->status ? '1' : '0') == '1' ? 'selected' : '' }}>Enabled</option>
                                                            <option value="0" {{ old('status', (int) $customerlevel->status ? '1' : '0') == '0' ? 'selected' : '' }}>Disabled</option>
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                
                                                <div class="col-md-12">
                                                    <button class="btn btn-primary" type="submit">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                        @if($customerlevel->customers()->count() < 1)
                                            <form action="{{ route('customerlevel.destroy', $customerlevel->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to delete forever?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        @endif
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
