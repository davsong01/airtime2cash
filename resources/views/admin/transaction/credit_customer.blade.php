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
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item active">Credit Customer
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
                                    <h4 class="card-title">Credit Customer</h4>
                                    <p>Credit customer wallet</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('admin.process.credit.debit')}}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <fieldset class="form-group">
                                                        <label for="email">Email</label>
                                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter customer email..." required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="amount">Amount</label>
                                                        <input type="number" class="form-control" name="amount" value="{{ old('amount') }}" placeholder="Enter amount" id="amount" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="reason" class="text-danger bold">Reason</label>
                                                        <textarea class="form-control" name="reason" value="{{ old('reason') }}" placeholder="Enter reason for this transaction" id="reason"></textarea>
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
