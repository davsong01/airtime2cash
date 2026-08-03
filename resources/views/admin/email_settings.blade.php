@extends('layouts.app')
@section('title', 'Edit Email setting')
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
                                    </li>
                                    <li class="breadcrumb-item active">Edit Email Setting
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
                                    {{-- <p>Add new category</p> --}}
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('email.setup.update')}}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_DRIVER">MAIL_DRIVER</label>
                                                        <input type="text" class="form-control" id="MAIL_DRIVER" name="MAIL_DRIVER" value="{{ $mailsetting->MAIL_DRIVER ?? old('MAIL_DRIVER') }}" placeholder="Enter MAIL_DRIVER" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_HOST">MAIL_HOST</label>
                                                        <input type="text" class="form-control" id="MAIL_HOST" name="MAIL_HOST" value="{{ $mailsetting->MAIL_HOST ?? old('MAIL_HOST') }}" placeholder="Enter MAIL_HOST" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_PORT">MAIL_PORT</label>
                                                        <input type="text" class="form-control" id="MAIL_PORT" name="MAIL_PORT" value="{{ $mailsetting->MAIL_PORT ?? old('MAIL_PORT') }}" placeholder="Enter MAIL_PORT" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_USERNAME">MAIL_USERNAME</label>
                                                        <input type="text" class="form-control" id="MAIL_USERNAME" name="MAIL_USERNAME" value="{{ $mailsetting->MAIL_USERNAME ?? old('MAIL_USERNAME') }}" placeholder="Enter MAIL_USERNAME" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_PASSWORD">MAIL_PASSWORD</label>
                                                        <input type="text" class="form-control" id="MAIL_PASSWORD" name="MAIL_PASSWORD" value="{{ $mailsetting->MAIL_PASSWORD ?? old('MAIL_PASSWORD') }}" placeholder="Enter MAIL_PASSWORD" required>
                                                    </fieldset>
                                                    
                                                    
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_ENCRYPTION">MAIL_ENCRYPTION</label>
                                                        <input type="text" class="form-control" id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION" value="{{ $mailsetting->MAIL_ENCRYPTION ?? old('MAIL_ENCRYPTION') }}" placeholder="Enter MAIL_ENCRYPTION" required>
                                                    </fieldset>
                                                   <fieldset class="form-group">
                                                        <label for="MAIL_FROM_ADDRESS">MAIL_FROM_ADDRESS</label>
                                                        <input type="text" class="form-control" id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" value="{{ $mailsetting->MAIL_FROM_ADDRESS ?? old('MAIL_FROM_ADDRESS') }}" placeholder="Enter MAIL_FROM_ADDRESS" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_FROM_NAME">MAIL_FROM_NAME</label>
                                                        <input type="text" class="form-control" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" value="{{ $mailsetting->MAIL_FROM_NAME ?? old('MAIL_FROM_NAME') }}" placeholder="Enter MAIL_FROM_NAME" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_REPLY_TO_ADDRESS">MAIL_REPLY_TO_ADDRESS</label>
                                                        <input type="text" class="form-control" id="MAIL_REPLY_TO_ADDRESS" name="MAIL_REPLY_TO_ADDRESS" value="{{ $mailsetting->MAIL_REPLY_TO_ADDRESS ?? old('MAIL_REPLY_TO_ADDRESS') }}" placeholder="Enter MAIL_REPLY_TO_ADDRESS">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="MAIL_REPLY_TO_NAME">MAIL_REPLY_TO_NAME</label>
                                                        <input type="text" class="form-control" id="MAIL_REPLY_TO_NAME" name="MAIL_REPLY_TO_NAME" value="{{ $mailsetting->MAIL_REPLY_TO_NAME ?? old('MAIL_REPLY_TO_NAME') }}" placeholder="Enter MAIL_REPLY_TO_NAME">
                                                    </fieldset>
                                                    
                                                   
                                                </div>
                                                <div class="col-md-12">
                                                <button class="btn btn-primary" type="submit">Update</button>
    
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