@extends('sneat.layouts.app')
@section('title', 'Referrals')

@section('content')
    @include('sneat.customer.partials.page-header', [
        'eyebrow' => 'Network',
        'title' => 'My Referrals',
        'subtitle' => 'A simple list of customers you referred.',
    ])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Date registered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refs as $ref)
                        <tr>
                            <td>{{ $ref->customer->user->username }}</td>
                            <td>{{ $ref->created_at->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No referrals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
