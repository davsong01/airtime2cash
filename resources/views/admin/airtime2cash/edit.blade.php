@extends('layouts.app')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/editors/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/admin-airtime2cash-form.css') }}">
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb p-0 mb-0">
                            <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('airtime2cash.index') }}">Airtime to cash products</a></li>
                            <li class="breadcrumb-item active">Edit {{ $product->name }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @include('layouts.alerts')
                @include('admin.airtime2cash.form', ['product' => $product])
            </div>
        </div>
    </div>
@endsection

@include('admin.airtime2cash.form-scripts')
