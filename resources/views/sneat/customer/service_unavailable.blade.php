@php
    $settings = getSettings();
@endphp
@extends('sneat.layouts.app')

@section('title', $serviceLabel . ' unavailable')

@section('page-css')
<style>
    .locked-service-wrap {
        min-height: 72vh;
        display: flex;
        align-items: center;
        padding: 2rem 0;
    }

    .locked-service-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 24px 55px rgba(15, 23, 42, 0.08);
    }

    .locked-service-card::before {
        position: absolute;
        top: -60px;
        right: -40px;
        width: 180px;
        height: 180px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.12), transparent 70%);
        content: "";
        pointer-events: none;
    }

    .locked-service-tag {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .4rem .75rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, .1);
        color: #1d4ed8;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .locked-service-title {
        margin-top: .9rem;
        color: #0f172a;
        font-size: clamp(1.7rem, 3vw, 2.6rem);
        font-weight: 800;
        line-height: 1.1;
    }

    .locked-service-copy {
        color: #475569;
        font-size: 1rem;
        line-height: 1.8;
    }

    .locked-service-panel {
        padding: 1.25rem;
        border-radius: 18px;
        background: rgba(37, 99, 235, .05);
    }

    .locked-service-cta {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
</style>
@endsection

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Service access',
        'title' => $serviceLabel,
        'subtitle' => 'Your account does not currently have permission to use this service.',
    ])

    <section class="locked-service-wrap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8 col-xl-7">
                    <div class="card locked-service-card">
                        <div class="card-body p-2 p-md-3 p-lg-4">
                            <span class="locked-service-tag">
                                <i class="bx bx-shield-x"></i>
                                Service Restricted
                            </span>
                            <h1 class="locked-service-title">{{ $serviceLabel }}</h1>
                            <p class="locked-service-copy mb-0">{{ $message }}</p>

                            <div class="locked-service-panel mt-2">
                                <p class="mb-2 text-muted">Reach out to the admin team and ask them to enable this service for your profile.</p>
                                @if(!empty($adminWhatsappLink))
                                    <a class="btn btn-success locked-service-cta" href="{{ $adminWhatsappLink }}" target="_blank" rel="noopener">
                                        <i class="bx bxl-whatsapp"></i>
                                        Contact Admin on WhatsApp
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
@endsection
