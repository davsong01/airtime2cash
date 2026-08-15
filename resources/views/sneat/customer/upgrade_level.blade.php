@extends('sneat.layouts.app')
@section('title', 'Upgrade Level')

@section('content')
    @php
        $currentLevel = auth()->user()->customer?->level?->name ?? 'N/A';
        $currentLevelId = auth()->user()->customer?->level?->id ?? 0;
    @endphp

    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Account tier',
        'title' => 'Upgrade Level',
        'subtitle' => 'Move to a higher customer level when you are ready.',
    ])

    @include('sneat.layouts.alerts')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card customer-form-card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="purchase-heading-icon bg-label-primary"><i class="bx bx-up-arrow-circle fs-4"></i></span>
                    <div><h5 class="mb-1">Choose a level</h5><small class="text-muted">Review the upgrade amount before continuing.</small></div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                        <span class="badge bg-label-primary">Current level: {{ $currentLevel }}</span>
                        <span class="badge bg-label-success">Balance: {{ getSettings()['currency'] }}{{ number_format(walletBalance(auth()->user()), 2) }}</span>
                    </div>
                    <form action="{{ route('customer.level.upgrade.process') }}" method="POST" autocomplete="off" class="customer-modern-form">
                        @csrf
                        <div class="mb-4">
                            <label for="level" class="form-label">Select level</label>
                            <select class="form-select" name="level" id="level" required>
                                <option value="">Select</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" {{ $currentLevelId == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }} ({{ getSettings()['currency'] }}{{ $level->upgrade_amount }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary customer-form-submit" type="submit"><i class="bx bx-up-arrow-circle me-1"></i> Upgrade</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
