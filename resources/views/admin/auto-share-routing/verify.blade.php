@extends('layouts.app')

@section('title', 'Routing Check')

@section('page-css')
<style>
    .routing-check-page { background: #f5f7fb; min-height: calc(100vh - 85px); }
    .routing-hero { background: linear-gradient(135deg, #123f43 0%, #1f6d6d 100%); border-radius: 20px; color: #fff; overflow: hidden; position: relative; }
    .routing-hero:after { content: ''; position: absolute; width: 280px; height: 280px; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; right: -80px; top: -115px; box-shadow: 0 0 0 28px rgba(255,255,255,.04), 0 0 0 58px rgba(255,255,255,.025); }
    .routing-kicker { color: #a9e6d5; font-size: .7rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .routing-hero h1 { color: #fff; font-size: 2rem; font-weight: 700; letter-spacing: -.03em; }
    .routing-hero p { color: rgba(255,255,255,.75); max-width: 560px; }
    .routing-live-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 12px; padding: 12px 14px; position: relative; z-index: 1; }
    .routing-live-pill .label { color: rgba(255,255,255,.62); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; }
    .routing-live-pill .value { color: #fff; font-size: 1.05rem; font-weight: 700; }
    .routing-panel { background: #fff; border: 1px solid #e8edf3; border-radius: 16px; box-shadow: 0 8px 28px rgba(29, 52, 75, .06); }
    .routing-panel-title { color: #23384d; font-size: 1rem; font-weight: 700; }
    .routing-panel-subtitle { color: #8493a3; font-size: .82rem; }
    .routing-field label { color: #536678; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .routing-field .form-control, .routing-field .input-group-text { border-color: #dfe7ef; height: 48px; }
    .routing-field .form-control:focus { border-color: #1f8b83; box-shadow: 0 0 0 .15rem rgba(31,139,131,.12); }
    .routing-check-btn { background: #1f8b83; border: 0; border-radius: 10px; height: 48px; font-weight: 700; }
    .routing-check-btn:hover { background: #176f69; }
    .routing-note { background: #f0faf8; border: 1px solid #d7eee9; border-radius: 10px; color: #52706e; font-size: .8rem; }
    .winner-card { background: linear-gradient(145deg, #f0fbf8, #fff); border: 1px solid #cfe9e2; border-radius: 16px; }
    .winner-label, .section-label { color: #6d8292; font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .winner-name { color: #123f43; font-size: 1.35rem; font-weight: 800; }
    .winner-reason { background: #fff; border-left: 3px solid #27ad92; border-radius: 0 8px 8px 0; color: #536678; font-size: .82rem; line-height: 1.55; padding: 10px 12px; }
    .stat-tile { background: #fff; border: 1px solid #e5edf0; border-radius: 10px; padding: 11px 12px; }
    .stat-tile small { color: #8293a0; display: block; font-size: .68rem; text-transform: uppercase; }
    .stat-tile strong { color: #263f51; font-size: .95rem; }
    .candidate-table th { border-top: 0; color: #8493a3; font-size: .67rem; letter-spacing: .06em; text-transform: uppercase; }
    .candidate-table td { color: #43596a; font-size: .82rem; vertical-align: middle; }
    .candidate-table .winner-row { background: #f1fbf8; }
    .health-dot { border-radius: 50%; display: inline-block; height: 7px; margin-right: 5px; width: 7px; }
    .health-good { background: #25b88a; } .health-muted { background: #9aa8b3; }
    .routing-json { background: #17252f; border-radius: 12px; color: #d9f5ee; font-family: SFMono-Regular, Consolas, monospace; font-size: .72rem; line-height: 1.55; max-height: 390px; overflow: auto; padding: 16px; }
    .routing-json::-webkit-scrollbar { height: 8px; width: 8px; } .routing-json::-webkit-scrollbar-thumb { background: #41606a; border-radius: 10px; }
    @media (max-width: 767px) { .routing-hero h1 { font-size: 1.55rem; } .routing-live-pill { margin-top: 16px; } }
</style>
@endsection

@section('content')
<div class="app-content content routing-check-page">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 mt-1">
                <ol class="breadcrumb p-0 mb-0">
                    <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('airtime2cash.index') }}">Catalogue</a></li>
                    <li class="breadcrumb-item active">Routing Check</li>
                </ol>
            </div>
        </div>

        <div class="content-body">
            @include('layouts.alerts')

            <div class="routing-hero p-2 p-md-3 mb-2">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="routing-kicker mb-50"><i class="bx bx-git-compare mr-25"></i>Provider diagnostics</div>
                        <h1 class="mb-50">Auto Share Routing Check</h1>
                        <p class="mb-0">Run a safe simulation to see which provider would handle an Airtime to Cash request and exactly why it would win.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="routing-live-pill">
                            <div class="label">Live routing mode</div>
                            <div class="value mb-1">{{ ucfirst(getSettings()->auto_share_routing_mode ?? 'manual') }}</div>
                            <span class="text-white small"><i class="bx bx-check-circle mr-25"></i>{{ (getSettings()->auto_share_routing_mode ?? 'manual') === 'auto' ? 'Mapped providers are compared by matching band and charge' : 'The configured provider is used when mapped to the product' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="routing-panel p-2 p-md-2 mb-2">

                <div class="routing-note p-1 mb-2"><i class="bx bx-info-circle mr-25"></i><strong>Auto mode is always enforced for this check.</strong> Network is recorded as context; generic price bands currently route by amount, effective fee, and availability health.</div>
                <form method="POST" action="{{ route('admin.auto-share.routing.verify.check') }}">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-5 routing-field mb-1 mb-md-0">
                            <label for="network">Network</label>
                            <select id="network" name="network" class="form-control @error('network') is-invalid @enderror" required>
                                <option value="">Choose a network</option>
                                @foreach($networks as $network)
                                    <option value="{{ $network->id }}" @selected((string) old('network', request('network')) === (string) $network->id)>{{ $network->name }}{{ $network->auto_share_product_code ? ' · '.$network->auto_share_product_code : '' }}</option>
                                @endforeach
                            </select>
                            @error('network')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 routing-field mb-1 mb-md-0">
                            <label for="amount">Transaction amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">{{ getSettings()->currency }}</span></div>
                                <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', request('amount')) }}" class="form-control @error('amount') is-invalid @enderror" placeholder="Enter amount" required>
                            </div>
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary routing-check-btn btn-block"><i class="bx bx-search-alt mr-25"></i>Run routing check</button>
                        </div>
                    </div>
                </form>
            </div>

            @if($result)
                <div class="row">
                    <div class="col-xl-5 mb-2">
                        <div class="winner-card p-2 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><div class="winner-label">Recommended provider</div><div class="winner-name mt-50">{{ $result['selected_provider']['name'] }}</div></div>
                                <span class="badge badge-success">Selected</span>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6 mb-1"><div class="stat-tile"><small>Network</small><strong>{{ $result['request']['network'] }}</strong></div></div>
                                <div class="col-6 mb-1"><div class="stat-tile"><small>Amount</small><strong>{{ getSettings()->currency }}{{ number_format($result['request']['amount'], 2) }}</strong></div></div>
                                <div class="col-6"><div class="stat-tile"><small>Mode tested</small><strong>{{ ucfirst($result['routing_mode']) }}</strong></div></div>
                                <div class="col-6"><div class="stat-tile"><small>Provider status</small><strong>{{ ucfirst($result['selected_provider']['status']) }}</strong></div></div>
                            </div>
                            <div class="winner-label mt-2 mb-50">Selection reason</div>
                            <div class="winner-reason">{{ $result['reason'] }}</div>
                        </div>
                    </div>
                    <div class="col-xl-7 mb-2">
                        <div class="routing-panel p-2 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-1"><div><div class="routing-panel-title">Candidate comparison</div><div class="routing-panel-subtitle">Every eligible provider considered for this amount.</div></div><span class="badge badge-light-primary">{{ count($result['routing']['candidates'] ?? []) }} candidates</span></div>
                            <div class="table-responsive">
                                <table class="table candidate-table mb-0">
                                    <thead><tr><th>Provider</th><th>Total customer charge</th><th>Health</th><th>Band</th></tr></thead>
                                    <tbody>
                                    @forelse(($result['routing']['candidates'] ?? []) as $candidate)
                                        <tr class="{{ (int) ($candidate['provider_id'] ?? 0) === (int) $result['selected_provider']['id'] ? 'winner-row' : '' }}">
                                            <td><strong>{{ $candidate['provider'] }}</strong>@if((int) ($candidate['provider_id'] ?? 0) === (int) $result['selected_provider']['id']) <span class="badge badge-light-success ml-25">Winner</span>@endif</td>
                                            <td>{{ getSettings()->currency }}{{ number_format((float) ($candidate['effective_total_charge'] ?? $candidate['fee'] ?? 0), 2) }}</td>
                                            <td><span class="health-dot {{ (int) ($candidate['availability_score'] ?? 0) >= 70 ? 'health-good' : 'health-muted' }}"></span>{{ $candidate['availability_score'] ?? 0 }}%</td>
                                            <td>{{ $candidate['band'] ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted text-center py-2">No eligible candidates were found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="routing-panel p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div><div class="routing-panel-title">Complete charge view</div><div class="routing-panel-subtitle">Product conversion charges and Auto Share provider pricing are shown separately from bank API charges.</div></div>
                        <i class="bx bx-receipt text-primary font-medium-5"></i>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-1 mb-md-0">
                            <div class="stat-tile h-100">
                                <small>Product conversion charge · {{ number_format((float) ($result['charges']['product']['rate'] ?? 0), 2) }}%</small>
                                <strong>{{ getSettings()->currency }}{{ number_format((float) ($result['charges']['product']['amount'] ?? 0), 2) }}</strong>
                                <div class="text-muted small mt-25">Attached to the selected network product</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-1 mb-md-0">
                            <div class="stat-tile h-100">
                                <small>Auto Share provider pricing</small>
                                <strong>{{ getSettings()->currency }}{{ number_format((float) ($result['charges']['provider_routing_fee'] ?? 0), 2) }}</strong>
                                <div class="text-muted small mt-25">Auto Share fee, band and global extras</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-tile h-100">
                                <small>Combined visible charges</small>
                                <strong>{{ getSettings()->currency }}{{ number_format((float) ($result['charges']['visible_charge_total'] ?? 0), 2) }}</strong>
                                <div class="text-muted small mt-25">Diagnostic total; not a new transaction debit</div>
                            </div>
                        </div>
                    </div>
                    @if(!empty($result['charges']['provider']))
                        <div class="table-responsive mt-1">
                            <table class="table candidate-table mb-0">
                                <thead><tr><th>Auto Share provider charge</th><th>Type</th><th>Amount</th></tr></thead>
                                <tbody>
                                @foreach($result['charges']['provider'] as $charge)
                                    <tr><td>{{ $charge['label'] ?? 'Charge' }}</td><td>{{ ucfirst(str_replace('_', ' ', $charge['type'] ?? 'provider')) }}</td><td>{{ getSettings()->currency }}{{ number_format((float) ($charge['amount'] ?? 0), 2) }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                {{-- <div class="routing-panel p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1"><div><div class="routing-panel-title">Decision payload</div><div class="routing-panel-subtitle">Sanitized JSON returned by the routing engine.</div></div><i class="bx bx-code-alt text-primary font-medium-5"></i></div>
                    <pre class="routing-json mb-0">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div> --}}
            @endif
        </div>
    </div>
</div>
@endsection
