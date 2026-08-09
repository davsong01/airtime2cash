@extends('layouts.app')
@section('content')
@php
    $isEdit = isset($bank) && $bank && $bank->exists;
    $formAction = $isEdit ? route('banks.update', $bank) : route('banks.store');
    $title = $isEdit ? 'Edit Bank' : 'Add Bank';
    $providerCodes = old('provider_codes', $isEdit ? ($bank->provider_codes ?? []) : []);
    $providerCodes = is_array($providerCodes) ? $providerCodes : [];
    $providers = $providers ?? collect();
@endphp
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <section class="card">
                <div class="card-header">
                    <h4 class="card-title mb-25">{{ $title }}</h4>
                    <small class="text-muted">Banks are normalized by code, and provider codes are merged during sync.</small>
                </div>
                <div class="card-body">
                    @include('layouts.alerts')
                    <form action="{{ $formAction }}" method="POST">
                        @csrf
                        @if($isEdit)
                            @method('PATCH')
                        @endif
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $isEdit ? $bank->bank_name : '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CBN Code</label>
                                    <input type="text" name="cbn_code" class="form-control" value="{{ old('cbn_code', $isEdit ? $bank->cbn_code : '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="active" @selected(old('status', $isEdit ? $bank->status : 'active') === 'active')>Active</option>
                                        <option value="inactive" @selected(old('status', $isEdit ? $bank->status : 'active') === 'inactive')>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-header bg-transparent">
                                        <div>
                                            <h5 class="mb-25">Provider Codes</h5>
                                            <small class="text-muted">Enter the code each provider uses for this same bank. Leave blank if a provider does not use a different code.</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @forelse($providers as $provider)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="form-group">
                                                        <label>{{ $provider->name }} <small class="text-muted">({{ $provider->slug }})</small></label>
                                                        <input
                                                            type="text"
                                                            name="provider_codes[{{ $provider->slug }}]"
                                                            class="form-control"
                                                            value="{{ old('provider_codes.' . $provider->slug, $providerCodes[$provider->slug] ?? '') }}"
                                                            placeholder="Provider code"
                                                        >
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12">
                                                    <div class="alert alert-light mb-0">No providers available to map yet.</div>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-right">
                                <a href="{{ route('banks.index') }}" class="btn btn-light-secondary">Cancel</a>
                                <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Update' : 'Save' }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
