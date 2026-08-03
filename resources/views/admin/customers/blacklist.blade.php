@extends('layouts.app')
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <!-- head -->
                        <h5 class="card-title mb-2">Blacklists</h5>
                        <a href="{{ route('customer-blacklist.create') }}"><button id="addRow"
                                class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Add
                                To Blacklist</button></a>
                        @include('layouts.alerts')
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-extended-success" class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Blacklist Item</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            {{-- <td>
                                                <img class="rounded-circle mr-1" src="{{ $customer->avatar }}"
                                                    alt="image">
                                                Name:<a target="_blank" href="{{ request()->route()->getPrefix() }}/customer/edit/{{ $customer->id }}">
                                                    {{ $customer->firstname . ' ' . $customer->lastname }}</a> <br>
                                                   Email:  {{ $customer->email }} <br>
                                                   Phone Number: {{ $customer->phone }}
                                            </td> --}}

                                            <td>{{ $customer->value }}</td>
                                            <td>{{ ucfirst($customer->status) }}</td>
                                            <td>{{ $customer->created_at->toDateString('en-GB') }}</td>
                                            <td>
                                                <div class="custom-control custom-switch custom-switch-success custom-switch-glow custom-control-inline mb-1">
                                                    <input type="checkbox" class="custom-control-input" id="customSwitchGlow2-{{ $customer->id }}" @checked($customer->status == 'active') data-id="{{ $customer->id }}" data-value="{{ $customer->status }}">
                                                    <label class="custom-control-label" for="customSwitchGlow2-{{ $customer->id }}">
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $customers->render() }}
                        </div>
                    </div>
                    <!-- datatable ends -->
                </div>
            </section>
        </div>
    </div>

    @section('page-script')
        <script>
            $('.custom-control-input').on('change', function toggleStatus () {
                let check = confirm('Are you sure you want to perform this action?');
                if (check) {
                    let status = $(this).attr('data-value');
                    let id = $(this).attr('data-id');
                    $.ajax({
                        url: 'black-list-status',
                        data: {status, id},
                        success: e => {
                            alert(e.message)
                            if (e.code == 1) {
                                let status = $(this).attr('data-value', e.status);
                            }
                        },
                        error: () => alert('Request could not be completed!'),
                    });
                }
            })

        </script>
    @endsection
