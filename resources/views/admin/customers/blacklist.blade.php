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
                                            <td>{{ $customer->created_at->toDateString('en-GB') }}</td>
                                            <td>
                                                <form action="{{ route('customer-blacklist.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Remove this item from the blacklist?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash mr-25"></i> Remove</button>
                                                </form>
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
    @endsection
