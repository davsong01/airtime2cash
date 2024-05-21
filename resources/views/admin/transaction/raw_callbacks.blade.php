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

                                                <a class="btn btn-info btn-sm mr-1 mb-1" href="{{ route('admin.requery.callback', $call->transaction_reference) }}">
                                                    <i class="fa fa-eye"></i><span class="align-middle ml-25">Query</span>
                                                </a>
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
@endsection
@section('page-script')
    {{-- <script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
        });
    </script>
@endsection
