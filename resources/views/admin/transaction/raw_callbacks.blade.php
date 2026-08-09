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
                        <h5 class="card-title mb-2">Raw Callbacks</h5>
                        @include('layouts.alerts')
                        <div class="d-inline-block">
                            <!-- chart-1   -->
                            <div class="d-flex market-statistics-1">
                                <!-- chart-statistics-1 -->
                                <div id="donut-success-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-extended-success" class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Details</th>
                                        <th>Raw</th>
                                        <th>Analysis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($calls as $call)
                                        <tr>
                                            <td>
                                                <small style="color:black">
                                                    <strong>Account Number:</strong><br> {{$call->account_number}} <br>
                                                    <strong>Session ID:</strong> <br>{{$call->session_id }} <br>
                                                    <strong>Reference ID:</strong><br> {{$call->transaction_reference }} <br>
                                                    <strong>Date Created:</strong><br> {{ date("M jS, Y g:iA", strtotime($call->created_at)) }} 
                                                    @if($call->status == 'analyzed') <br>
                                                    <strong style="color:green">Date Analyzed:</strong><br><span style="color:green"> {{ date("M jS, Y g:iA", strtotime($call->updated_at)) }}</span>
                                                    @endif  <br>
                                                    <strong>Status:</strong><span style="color:{{ $call->status == 'analyzed' ? 'green' : 'red' }}">{{ ucfirst($call->status) }} </span><br>
                                                </small>
                                                @if($call->status == 'analyzed')
                                                <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('admin.single.transaction.view', $call->transaction->id) }}">
                                                    <i class="fa fa-eye"></i><span class="align-middle ml-25">View Transaction</span>
                                                </a>
                                                @else
                                                <a class="btn btn-primary btn-sm mr-1 mb-1" href="{{ route('callback.reset', $call->id) }}">
                                                    <i class="fa fa-eye"></i><span class="align-middle ml-25">Reset</span>
                                                </a>

                                                <button
                                                    type="button"
                                                    class="btn btn-info btn-sm mr-1 mb-1 js-query-callback"
                                                    data-url="{{ route('admin.requery.callback', $call->transaction_reference) }}"
                                                    data-reference="{{ $call->transaction_reference }}"
                                                    data-provider="{{ $call->gateway?->name ?? 'Provider' }}"
                                                >
                                                    <i class="bx bx-refresh"></i><span class="align-middle ml-25">Query</span>
                                                </button>
                                                @endif
                                            </td>
                                            
                                            <td style="width:250px;font-size: 11px;">
                                                <?php
                                                    $formatted_string = json_encode(json_decode($call->raw,true),JSON_PRETTY_PRINT);    
                                                ?>
                                                <pre style="max-width:350px;max-height:200px;font-size: 11px;">
                                                    {{$formatted_string}}
                                                </pre>
                                            </td>
                                            
                                            
                                            <td style="width:350px;font-size: 11px;">
                                                <?php
                                                    $requery = json_encode(json_decode($call->raw_requery,true),JSON_PRETTY_PRINT);    
                                                ?>
                                                <pre style="max-width:350px;max-height:200px;font-size: 11px;">
                                                    {{$requery}}
                                                </pre>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                           
                            {{ $calls->links()}}
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="callbackQueryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-25">Callback Query Result</h5>
                        <small class="text-muted js-query-meta">Run a query to inspect the response.</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none js-query-alert"></div>

                    <div class="row mb-2">
                        <div class="col-md-4 mb-1">
                            <div class="border rounded p-1 h-100 bg-light">
                                <small class="text-muted d-block">Reference</small>
                                <strong class="js-query-reference">-</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-1">
                            <div class="border rounded p-1 h-100 bg-light">
                                <small class="text-muted d-block">Provider</small>
                                <strong class="js-query-provider">-</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-1">
                            <div class="border rounded p-1 h-100 bg-light">
                                <small class="text-muted d-block">Status</small>
                                <span class="badge js-query-status badge-light-secondary">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="border rounded p-1 bg-white">
                                <small class="text-muted d-block">Message</small>
                                <strong class="js-query-message">No query has been run yet.</strong>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-50">
                            <h6 class="mb-0">Formatted Response</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary js-copy-response">
                                <i class="bx bx-copy mr-25"></i>Copy
                            </button>
                        </div>
                        <pre class="bg-light rounded p-2 text-wrap mb-0 js-query-response" style="max-height: 420px; overflow: auto;">No query has been run yet.</pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    {{-- <script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const $queryModal = $('#callbackQueryModal');
            $('.js-example-basic-single').select2();

            $('.js-query-callback').on('click', function () {
                const button = $(this);
                const url = button.data('url');
                const reference = button.data('reference');
                const provider = button.data('provider');
                const $modal = $queryModal;
                const $alert = $modal.find('.js-query-alert');
                const $status = $modal.find('.js-query-status');
                const $response = $modal.find('.js-query-response');

                $modal.find('.js-query-reference').text(reference || '-');
                $modal.find('.js-query-provider').text(provider || '-');
                $modal.find('.js-query-meta').text('Querying ' + (provider || 'provider') + ' for ' + (reference || 'this callback') + '...');
                $modal.find('.js-query-message').text('Loading...');
                $response.text('Loading...');
                $alert.addClass('d-none').removeClass('alert-success alert-danger').text('');
                $status.removeClass('badge-light-success badge-light-danger badge-light-warning badge-light-secondary')
                    .addClass('badge-light-secondary')
                    .text('Loading');

                $modal.modal('show');

                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        const status = (response.status || 'failed').toString().toLowerCase();
                        const badgeClass = status === 'success'
                            ? 'badge-light-success'
                            : (status === 'pending' ? 'badge-light-warning' : 'badge-light-danger');
                        const pretty = response.response || response;
                        const message = response.message || 'Query completed.';

                        $status.removeClass('badge-light-success badge-light-danger badge-light-warning badge-light-secondary')
                            .addClass(badgeClass)
                            .text(status);
                        $modal.find('.js-query-meta').text(message);
                        $modal.find('.js-query-message').text(message);
                        $response.text(JSON.stringify(pretty, null, 2));
                        $alert.removeClass('d-none').removeClass('alert-danger').addClass('alert-success').text(message);
                    },
                    error: function (xhr) {
                        const payload = xhr.responseJSON || {};
                        const message = payload.message || 'Unable to query this callback right now.';
                        const pretty = payload.response || payload;

                        $status.removeClass('badge-light-success badge-light-danger badge-light-warning badge-light-secondary')
                            .addClass('badge-light-danger')
                            .text('failed');
                        $modal.find('.js-query-meta').text('Query failed.');
                        $modal.find('.js-query-message').text(message);
                        $response.text(JSON.stringify(pretty, null, 2) || message);
                        $alert.removeClass('d-none').removeClass('alert-success').addClass('alert-danger').text(message);
                    }
                });
            });

            $queryModal.on('click', '.js-copy-response', function () {
                const target = $queryModal.find('.js-query-response').text();

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(target);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = target;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            });
        });
    </script>
@endsection
