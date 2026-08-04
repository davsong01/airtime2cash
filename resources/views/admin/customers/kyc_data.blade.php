@extends('layouts.app')

@section('page-css')
    <style>
        .kyc-command-hero {
            position: relative;
            overflow: hidden;
            padding: 2rem;
            border-radius: .75rem;
            background: linear-gradient(125deg, #123f43 0%, #1f6667 72%, #278078 100%);
            color: #fff;
            box-shadow: 0 1rem 2.5rem rgba(18, 63, 67, .2);
        }
        .kyc-command-hero::after {
            position: absolute;
            width: 280px;
            height: 280px;
            border: 50px solid rgba(244, 232, 90, .12);
            border-radius: 50%;
            content: '';
            right: -100px;
            top: -125px;
        }
        .kyc-command-hero h2,
        .kyc-command-hero p { color: inherit; }
        .kyc-hero-kicker {
            display: inline-flex;
            margin-bottom: .8rem;
            padding: .35rem .7rem;
            align-items: center;
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 50rem;
            background: rgba(255,255,255,.1);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .kyc-hero-queue {
            position: relative;
            z-index: 1;
            min-width: 220px;
            padding: 1.1rem 1.25rem;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: .65rem;
            background: rgba(255,255,255,.11);
            backdrop-filter: blur(8px);
        }
        .kyc-hero-queue strong {
            display: block;
            color: #f4e85a;
            font-size: 2.2rem;
            line-height: 1;
        }
        .kyc-stat-card {
            height: 100%;
            border: 0;
            box-shadow: 0 .5rem 1.5rem rgba(34, 48, 62, .08);
        }
        .kyc-stat-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: .6rem;
            font-size: 1.2rem;
        }
        .kyc-stat-value {
            color: #22303e;
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.15;
        }
        .kyc-search-shell { position: relative; }
        .kyc-search-shell .form-control { padding-left: 2.35rem; }
        .kyc-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: .8rem;
            color: #7d8994;
            transform: translateY(-50%);
        }
        .kyc-suggestions {
            position: absolute;
            z-index: 1060;
            top: calc(100% + .35rem);
            right: 0;
            left: 0;
            overflow: hidden;
            border: 1px solid #dfe3e7;
            border-radius: .55rem;
            background: #fff;
            box-shadow: 0 .8rem 2rem rgba(34, 48, 62, .16);
        }
        .kyc-suggestion {
            display: flex;
            width: 100%;
            padding: .75rem .85rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            border-bottom: 1px solid #edf0f2;
            background: #fff;
            color: #475f7b;
            text-align: left;
        }
        .kyc-suggestion:last-child { border-bottom: 0; }
        .kyc-suggestion:hover,
        .kyc-suggestion:focus { background: #eef8f4; outline: 0; }
        .kyc-suggestion-name { display: block; color: #22303e; font-weight: 600; }
        .kyc-suggestion-meta { display: block; color: #828d99; font-size: .74rem; }
        .kyc-queue-card { overflow: visible; }
        .kyc-filter-label { color: #5f6f7a; font-size: .76rem; font-weight: 600; }
        .kyc-filter-actions { padding-top: 1.45rem; }
        .kyc-table-customer { min-width: 250px; }
        .kyc-pagination .pagination { margin-bottom: 0; }
        @media (max-width: 767.98px) {
            .kyc-command-hero { padding: 1.35rem; }
            .kyc-hero-queue { width: 100%; margin-top: 1rem; }
            .kyc-filter-actions { padding-top: 0; }
        }
    </style>
@endsection

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <section id="admin-kyc-dashboard">
                <div class="kyc-command-hero mb-2">
                    <div class="d-md-flex align-items-center justify-content-between">
                        <div class="position-relative" style="z-index:1; max-width: 620px;">
                            <span class="kyc-hero-kicker"><i class="fa fa-shield mr-50"></i> Verification operations</span>
                            <h2 class="mb-50">KYC Review Centre</h2>
                            <p class="mb-0 text-white-50">Find customers quickly, monitor verification coverage, and work through the oldest submissions first.</p>
                        </div>
                        <div class="kyc-hero-queue">
                            <strong>{{ number_format((int) $summary->review_queue) }}</strong>
                            <span>Submissions awaiting review</span>
                            <a href="{{ route('admin.kyc', ['status' => 'review']) }}#kyc-queue" class="d-inline-block mt-75 text-white font-weight-bold">Open review queue <i class="fa fa-arrow-right ml-25"></i></a>
                        </div>
                    </div>
                </div>

                <div class="row match-height mb-1">
                    <div class="col-xl-3 col-sm-6">
                        <div class="card kyc-stat-card">
                            <div class="card-body d-flex align-items-center">
                                <span class="kyc-stat-icon badge-light-warning mr-1"><i class="fa fa-clock-o"></i></span>
                                <div><div class="kyc-stat-value">{{ number_format((int) $summary->review_queue) }}</div><small class="text-muted">Awaiting review</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="card kyc-stat-card">
                            <div class="card-body d-flex align-items-center">
                                <span class="kyc-stat-icon badge-light-success mr-1"><i class="fa fa-check"></i></span>
                                <div><div class="kyc-stat-value">{{ number_format((int) $summary->verified) }}</div><small class="text-muted">Verified customers</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="card kyc-stat-card">
                            <div class="card-body d-flex align-items-center">
                                <span class="kyc-stat-icon badge-light-secondary mr-1"><i class="fa fa-user-times"></i></span>
                                <div><div class="kyc-stat-value">{{ number_format((int) $summary->unverified) }}</div><small class="text-muted">Unverified customers</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="card kyc-stat-card">
                            <div class="card-body d-flex align-items-center">
                                <span class="kyc-stat-icon badge-light-info mr-1"><i class="fa fa-users"></i></span>
                                <div><div class="kyc-stat-value">{{ number_format((int) $summary->total) }}</div><small class="text-muted">Total customers</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card kyc-queue-card" id="kyc-queue">
                    <div class="card-header border-bottom">
                        <div>
                            <h5 class="card-title mb-25">Customer verification queue</h5>
                            <small class="text-muted">Review submissions or search the full customer base.</small>
                        </div>
                    </div>

                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.kyc') }}" id="kyc-filter-form" class="row align-items-start" autocomplete="off">
                            <div class="col-lg-6 col-md-5 form-group">
                                <label class="kyc-filter-label" for="kyc-search">Search customers</label>
                                <div class="kyc-search-shell">
                                    <i class="fa fa-search kyc-search-icon"></i>
                                    <input type="search" id="kyc-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Type a name, username, email, or phone" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="kyc-suggestions">
                                    <div id="kyc-suggestions" class="kyc-suggestions" role="listbox" hidden></div>
                                </div>
                                <small class="text-muted">Start typing for suggestions, or enter any term and press Enter.</small>
                            </div>
                            <div class="col-lg-3 col-md-4 form-group">
                                <label class="kyc-filter-label" for="kyc-status">KYC status</label>
                                <select id="kyc-status" name="status" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="review" @selected(request('status') === 'review')>Review queue</option>
                                    <option value="awaiting-approval" @selected(request('status') === 'awaiting-approval')>Awaiting approval</option>
                                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                    <option value="unverified" @selected(request('status') === 'unverified')>Unverified</option>
                                    <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 form-group d-flex kyc-filter-actions">
                                <button type="submit" class="btn btn-primary mr-1"><i class="fa fa-filter mr-25"></i> Filter</button>
                                <a href="{{ route('admin.kyc') }}" class="btn btn-light"><i class="fa fa-times mr-25"></i> Clear</a>
                            </div>
                        </form>

                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-1">
                            <small class="text-muted">
                                @if($customers->total())
                                    Showing {{ number_format($customers->firstItem()) }}-{{ number_format($customers->lastItem()) }} of {{ number_format($customers->total()) }} customers
                                @else
                                    No matching customers
                                @endif
                            </small>
                            <small class="text-muted">Pending submissions are ordered oldest first</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 64px;">S/N</th>
                                        <th>Customer</th>
                                        <th>KYC status</th>
                                        <th>Last updated</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customers as $customer)
                                        @php
                                            $statusClass = match ($customer->kyc_status) {
                                                'verified' => 'badge-light-success',
                                                'awaiting-approval' => 'badge-light-warning',
                                                'pending' => 'badge-light-info',
                                                default => 'badge-light-secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $customers->firstItem() + $loop->index }}</td>
                                            <td class="kyc-table-customer">
                                                <a class="font-weight-bold" href="{{ route('customers.edit', ['id' => $customer->user_id, 'tab' => 'kyc']) }}">
                                                    {{ trim($customer->firstname . ' ' . $customer->lastname) ?: 'Unnamed customer' }}
                                                </a>
                                                <div class="text-muted small">{{ '@' . $customer->username }} &middot; {{ $customer->email }}</div>
                                                <div class="text-muted small">{{ $customer->phone ?: 'No phone number' }}</div>
                                            </td>
                                            <td><span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('-', ' ', $customer->kyc_status)) }}</span></td>
                                            <td class="text-muted small">{{ optional($customer->updated_at)->format('d M Y, h:i A') }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('customers.edit', ['id' => $customer->user_id, 'tab' => 'kyc']) }}" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i><span class="align-middle ml-25">Review</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No customers match the selected filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($customers->hasPages())
                            <div class="kyc-pagination d-flex justify-content-center mt-2">
                                {{ $customers->onEachSide(1)->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('kyc-filter-form');
            const input = document.getElementById('kyc-search');
            const suggestions = document.getElementById('kyc-suggestions');
            const endpoint = @json(route('admin.kyc.customer-suggestions'));
            let timer;
            let requestController;

            function closeSuggestions() {
                suggestions.hidden = true;
                suggestions.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
            }

            function showMessage(message) {
                suggestions.innerHTML = '';
                const item = document.createElement('div');
                item.className = 'px-1 py-75 text-muted small';
                item.textContent = message;
                suggestions.appendChild(item);
                suggestions.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function renderSuggestions(items) {
                suggestions.innerHTML = '';

                if (!items.length) {
                    showMessage('No matching customers. Press Enter to search the full list.');
                    return;
                }

                items.forEach(function (customer) {
                    const button = document.createElement('button');
                    const details = document.createElement('span');
                    const name = document.createElement('span');
                    const meta = document.createElement('span');
                    const status = document.createElement('span');

                    button.type = 'button';
                    button.className = 'kyc-suggestion';
                    button.setAttribute('role', 'option');
                    name.className = 'kyc-suggestion-name';
                    meta.className = 'kyc-suggestion-meta';
                    status.className = 'badge badge-light-secondary';
                    name.textContent = customer.name;
                    meta.textContent = '@' + customer.username + ' · ' + customer.email + (customer.phone ? ' · ' + customer.phone : '');
                    status.textContent = customer.status.replace(/-/g, ' ');
                    details.appendChild(name);
                    details.appendChild(meta);
                    button.appendChild(details);
                    button.appendChild(status);
                    button.addEventListener('click', function () {
                        input.value = customer.email;
                        closeSuggestions();
                        form.submit();
                    });
                    suggestions.appendChild(button);
                });

                suggestions.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            input.addEventListener('input', function () {
                window.clearTimeout(timer);
                const term = input.value.trim();

                if (term.length < 2) {
                    closeSuggestions();
                    return;
                }

                timer = window.setTimeout(function () {
                    if (requestController) {
                        requestController.abort();
                    }

                    requestController = new AbortController();
                    showMessage('Searching customers...');

                    fetch(endpoint + '?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json' },
                        signal: requestController.signal
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Suggestion request failed');
                            }
                            return response.json();
                        })
                        .then(renderSuggestions)
                        .catch(function (error) {
                            if (error.name !== 'AbortError') {
                                closeSuggestions();
                            }
                        });
                }, 250);
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeSuggestions();
                }
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.kyc-search-shell')) {
                    closeSuggestions();
                }
            });
        });
    </script>
@endsection
