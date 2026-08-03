@extends('layouts.app')
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <!-- END: Vendor CSS-->
@endsection
@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Unverified Customers</h5>
                        @include('layouts.alerts')
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                        <form id="actionForm" onsubmit="return confirm('This action is irreversible!');" method="POST" action="{{ route('verify-users-actions') }}" class="mb-3">
                            @csrf
                            <div class="form-row align-items-center">
                                <div class="col-auto">
                                    <select id="action-select" class="form-control" name="action" required>
                                        <option value="" disabled selected>Bulk actions</option>
                                        <option value="verify">Verify</option>
                                        <option value="delete">Delete</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-secondary" id="submit-action">Apply</button>
                                </div>
                            </div>
                        </form>

                        <table id="table-extended-success" class="table table-striped dataex-html5-selectors">
                            <thead>
                                <tr>
                                    @if(hasAccess('customers.verify'))
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    @endif
                                    <th>Details</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $customer)
                                    <tr>
                                        @if(hasAccess('customers.verify'))
                                        <td>
                                            <input type="checkbox" class="customer-checkbox" name="customer_ids[]" value="{{ $customer->id }}">
                                        </td>
                                        @endif
                                        <td>
                                            <p>
                                                Name: <a target="_blank" href="{{ route('customers.edit', $customer->id) }}">
                                                    {{ $customer->firstname . ' ' . $customer->lastname }}</a> <br>
                                                Email: {{ $customer->email }} <br>
                                                Phone Number: {{ $customer->phone }}
                                            </p>
                                        </td>
                                        <td>{{ $customer->username }}</td>
                                        <td><small><strong>{{ ucfirst($customer->status) }}</strong></small></td>
                                        <td>{{ $customer->created_at }}</td>
                                        @if(hasAccess('customers.verify'))
                                        <td>
                                            <a style="display:block" onclick="return confirm('Are you sure you want to verify this user?');" href="{{ route('customer.verify', $customer->id) }}">
                                                <button type="button" class="btn btn-primary btn-sm mr-1 mb-1"><i class="bx bx-check"></i><span class="align-middle ml-25">Verify Email</span></button>
                                            </a>
                                            <a style="display:block" onclick="return confirm('Are you sure you want to delete this user, this action is irreversible?');" href="{{ route('customer.delete', $customer->id) }}">
                                                <button type="button" class="btn btn-danger btn-sm mr-1 mb-1"><i class="fa fa-recycle"></i><span class="align-middle ml-25">Delete</span></button>
                                            </a>
                                        </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@section('page-script')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.print.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/buttons.bootstrap.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script src="{{ asset('app-assets/js/scripts/datatables/datatable.js') }}"></script>

    <script>
        $(document).ready(function () {
            if ($.fn.dataTable.isDataTable('#table-extended-success')) {
                $('#table-extended-success').DataTable().destroy();
            }
            
            $('#table-extended-success').DataTable({
                "pageLength": 50,
                "columnDefs": [
                    { orderable: false, targets: [0, 5] } // Disable sorting for the first and last columns
                ]
            });
            // Select all checkboxes
            $('#select-all').on('change', function () {
                // Check if "Select All" is checked or not and set the state of customer checkboxes accordingly
                $('.customer-checkbox').prop('checked', this.checked);
            });

            // Submit the form when the action is selected
            $('#submit-action').on('click', function (e) {
                // Prevent form submission if no action is selected
                if ($('#action-select').val() === null || $('#action-select').val() === '') {
                    e.preventDefault();
                    alert('Please select an action.');
                } else {
                    // Add selected customer IDs to the form before submitting
                    const selectedCustomerIds = $('.customer-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    
                    if (selectedCustomerIds.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one customer.');
                    } else {
                        // Append selected customer IDs to the form as hidden input
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'customer_ids',
                            value: selectedCustomerIds
                        }).appendTo('#actionForm');
                    }
                }
            });
        });
    </script>
@endsection
