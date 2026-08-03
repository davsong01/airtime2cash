@extends('layouts.app')
@section('title', 'Add Category')
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
                                    <li class="breadcrumb-item active">Add Customer Level
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
                                    <h4 class="card-title">Add Customer Level</h4>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('customerlevel.store')}}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="upgrade_amount">Upgrade Amount</label>
                                                        <input type="text" class="form-control" id="upgrade_amount" name="upgrade_amount" value="{{ old('upgrade_amount') }}" placeholder="Enter Upgrade amount" required>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-6">
                                                    {{-- <fieldset class="form-group">
                                                        <label for="make_api_level">Make API Level</label>
                                                        <select class="form-control" name="make_api_level" id="make_api_level" required>
                                                            <option value="">Select</option>
                                                            <option value="yes" {{ old('make_api_level') == 'yes' ? 'selected' : '' }}>Yes
                                                            </option>
                                                            <option value="no" {{ old('make_api_level') == 'make_api_level' ? 'selected' : '' }}>No
                                                            </option>
                                                        </select>
                                                    </fieldset> --}}
                                                    <fieldset class="form-group">
                                                        <label for="order">Order</label>
                                                        <input type="number" class="form-control" name="order" value="{{ old('order') }}" placeholder="Enter order" id="order" required>
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