@extends('layouts.app')
@section('title', 'Change Transaction Pin')

@section('page-css')
<style>
    .reset-pin {
        font-size: 10px;
        float: right;
    }
</style>
@endsection
@section('content')
<!-- Content wrapper -->
 <div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <!-- Basic Inputs start -->
            <section id="basic-input">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                                <div class="content-body">
                                <!-- Nav Filled Starts -->
                                <section id="nav-filled">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="col-md-12"> 
                                                    <div class="card-header" style="padding:1.4rem 0.7rem">
                                                        <h4 class="card-title">Change Transaction Pin</h4>
                                                        <p>Please enter your new Transaction PIN</p>
                                                        @include('layouts.alerts')
                                                    </div>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-body">
                                                        <form action="{{route('final.pin.reset')}}" method="POST" autocomplete="off">
                                                            @csrf
                                                            <div class="row">
                                                                <div class="col-md-12">   
                                                                    <fieldset class="form-group">
                                                                        <label for="new_transaction_pin">New Transaction Pin</label>
                                                                        <input autocomplete="false" type="password" class="form-control" id="new_transaction_pin" name="new_transaction_pin" required>
                                                                    </fieldset>
                                                                    
                                                                    <button class="btn btn-primary" type="submit">Update Transaction PIN</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <!-- Nav Filled Ends -->
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
