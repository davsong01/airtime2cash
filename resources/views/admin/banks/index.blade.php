@extends('layouts.app')

@section('title', 'Banks')

@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .banks-shell {
            max-width: 1360px;
            margin-inline: auto;
        }

        .banks-hero {
            border: 0;
            border-radius: 1rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            color: #fff;
            box-shadow: 0 18px 40px -24px rgba(15, 23, 42, 0.65);
        }

        .banks-hero .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        .banks-metric {
            border-radius: 0.9rem;
            padding: 1rem 1.1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
        }

        .banks-metric .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.62);
            margin-bottom: 0.25rem;
        }

        .banks-metric .value {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .banks-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.15);
            overflow: hidden;
        }

        .banks-card .card-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #edf2f7;
            padding: 1.1rem 1.25rem;
        }

        .banks-card .card-body {
            padding: 1.25rem;
        }

        .banks-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .banks-actions .btn {
            border-radius: 0.7rem;
        }

        .bank-provider-code {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.55rem;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
        }

        .bank-provider-code small {
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }

        .table thead th {
            border-top: 0;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .bank-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .bank-status.active {
            background: rgba(40, 199, 111, 0.12);
            color: #168f5b;
        }

        .bank-status.inactive {
            background: rgba(234, 84, 85, 0.12);
            color: #d64545;
        }

        .banks-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .banks-toolbar .btn {
            border-radius: 0.7rem;
        }

        @media (max-width: 767.98px) {
            .banks-hero .d-flex {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endsection

@section('content')
<div class="app-content content banks-shell">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 mt-1">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <div class="breadcrumb-wrapper col-12">
                            <ol class="breadcrumb p-0 mb-0">
                                <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active">Banks</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <div class="card banks-hero mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div class="pr-3 mb-2 mb-md-0">
                            <div class="badge badge-light mb-75">Bank directory</div>
                            <p class="mb-0 text-muted">Search every bank quickly, keep provider codes in one place, and toggle status when a bank should be hidden from transfers.</p>
                        </div>
                        <div class="banks-actions">
                            <a href="{{ route('banks.create') }}" class="btn btn-light">
                                <i class="bx bx-plus mr-25"></i> Add Bank
                            </a>
                            <form action="{{ route('admin.reserved.accounts.sync-provider-ids') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-light" onclick="return confirm('Sync all reserved accounts to the current Monnify API id?')">
                                    <i class="bx bx-refresh mr-25"></i> Refresh Reserved Accounts
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4 mb-1 mb-md-0">
                            <div class="banks-metric">
                                <div class="label">Total banks</div>
                                <div class="value">{{ number_format($stats['total'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-1 mb-md-0">
                            <div class="banks-metric">
                                <div class="label">Active banks</div>
                                <div class="value">{{ number_format($stats['active'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="banks-metric">
                                <div class="label">Inactive banks</div>
                                <div class="value">{{ number_format($stats['inactive'] ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card banks-card">
                <div class="card-header">
                    <div class="banks-toolbar">
                        <div>
                            <h4 class="card-title mb-25">Banks</h4>
                            <small class="text-muted">Use search to find a bank name, CBN code, provider code, or status.</small>
                        </div>
                        <div class="text-right">
                            <small class="text-muted d-block">Tip: click the search box on the right to filter instantly.</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="banks-table">
                            <thead>
                                <tr>
                                    <th>Bank Name</th>
                                    <th>CBN Code</th>
                                    <th>Status</th>
                                    <th>Provider Codes</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($banks as $bank)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $bank->bank_name }}</div>
                                            <small class="text-muted">Bank record ID: {{ $bank->id }}</small>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold">{{ $bank->cbn_code ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="bank-status {{ ($bank->status ?? 'active') === 'active' ? 'active' : 'inactive' }}">
                                                {{ ucfirst($bank->status ?? 'active') }}
                                            </span>
                                        </td>
                                        <td>
                                            @php($codes = is_array($bank->provider_codes ?? null) ? $bank->provider_codes : [])
                                            @forelse($codes as $slug => $code)
                                                <span class="bank-provider-code"><small>{{ $slug }}</small> {{ $code }}</span>
                                            @empty
                                                <span class="text-muted">No provider codes saved</span>
                                            @endforelse
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('banks.edit', $bank) }}" class="btn btn-sm btn-outline-primary mr-25 mb-25">
                                                <i class="bx bx-edit-alt mr-25"></i>Edit
                                            </a>
                                            <form action="{{ route('banks.destroy', $bank) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bank?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger mb-25" type="submit">
                                                    <i class="bx bx-trash mr-25"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No banks found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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
        $(function () {
            $('#banks-table').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                autoWidth: false,
                language: {
                    search: 'Search banks:',
                    lengthMenu: 'Show _MENU_ banks',
                    emptyTable: 'No banks found.',
                }
            });
        });
    </script>
@endsection
