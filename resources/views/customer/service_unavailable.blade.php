@php
    $settings = getSettings();
@endphp
@extends('layouts.app')

@section('title', $serviceLabel . ' unavailable')

@section('page-css')
<style>
    .service-locked-shell {
        min-height: 70vh;
        display: flex;
        align-items: center;
        padding: 2rem 0;
    }

    .service-locked-card {
        border: 1px solid rgba(37, 99, 235, 0.14);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .service-locked-badge {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1rem;
        padding: .45rem .85rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, .1);
        color: #1d4ed8;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .service-locked-title {
        color: #0f172a;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
        font-weight: 800;
        line-height: 1.1;
    }

    .service-locked-copy {
        color: #475569;
        font-size: 1rem;
        line-height: 1.7;
    }

    .service-locked-panel {
        padding: 1.5rem;
        border-radius: 1rem;
        background: rgba(37, 99, 235, .05);
    }

    .service-locked-action {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }
</style>
@endsection

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <section class="service-locked-shell">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-xl-7">
                            <div class="card service-locked-card">
                                <div class="card-body p-2 p-md-3">
                                    <div class="service-locked-badge">
                                        <i class="bx bx-shield-x"></i>
                                        Service Restricted
                                    </div>
                                    <h1 class="service-locked-title">{{ $serviceLabel }}</h1>
                                    <p class="service-locked-copy mb-0">{{ $message }}</p>
                                    <div class="service-locked-panel mt-2">
                                        <p class="mb-2 text-muted">If you believe this should be enabled, contact admin and request access for your account.</p>
                                        @if(!empty($adminWhatsappLink))
                                            <a class="btn btn-success service-locked-action" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">
                                                <i class="bx bxl-whatsapp"></i>
                                                Click to contact admin on WhatsApp
                                            </a>
                                            {{-- <small class="d-block text-muted mt-1">Admin WhatsApp: {{ $adminWhatsappNumber }}</small> --}}
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                Admin WhatsApp is not configured yet. Please contact support.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-arrow-back"></i>
                                            Back to dashboard
                                        </a>
                                    </div>
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
