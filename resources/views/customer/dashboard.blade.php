@extends('layouts.app')
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            @include('admin.includes.popup')
            @include('admin.includes.scroller')
            <p class="" style="background: #3864dcc7;width: fit-content;padding: 10px;border-radius: 5px;color: white;">Referral Link: <span id="referral-link">{{ url('/register'). '?referral='.auth()->user()->username }}</span> <span style="cursor: pointer;"><i onclick="copyLink()" class="fa fa-copy" style="color: #00ff58;"></i></span> </p>
            <div class="content-header row">
            </div>
            <div class="content-body">
                <!-- Dashboard Ecommerce Starts -->
                <section id="dashboard-ecommerce">
                    <div class="row">
                        <div class="col-md-12">
                            @include('layouts.alerts')
                        </div>
                        @if(!empty($customer))
                        <div class="col-md-6 col-12 dashboard-greetings">
                            <div class="card" style="min-height: 310px;">
                                <div class="card-header">
                                    <h3 class="greeting-text">Customer of the Month</h3>
                                </div>
                                
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-end">
                                            <div class="dashboard-content-left">
                                                <h1 class="text-primary font-large-2 text-bold-500">{{$customer->customer->user->username ?? $customer->customer->user->firstname}}</h1>
                                                <div style="color:green" class="text-muted line-ellipsis">{{number_format($customer->count)}}+ Transactions</div>
                                            </div>
                                            <div class="dashboard-content-right">
                                                <img src="{{ asset('app-assets/images/icon/cup.png') }}" height="220"
                                                    width="220" class="img-fluid" alt="Dashboard Ecommerce" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <!-- Greetings Content Starts -->
                        <div class="col-md-6 col-12 dashboard-greetings">
                            <div class="card" style="min-height: 325px;">
                                <div class="card-header">
                                    <h3 class="greeting-text">Wallet Balance</h3>
                                    {{-- <p class="mb-0">Best seller of the month</p> --}}
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-end">
                                            <div class="dashboard-content-left">
                                                <?php
                                                $balance = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(walletBalance(auth()->user())) : 0;
                                                $ref = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(referralBalance(auth()->user())) : 0;
                                                ?>
                                                <h1 class="text-primary font-large-2 text-bold-500">
                                                    {!! $balance !!}</h1>

                                                <div class="text-muted line-ellipsis">Referral Earnings</div>
                                                <h3 class="mb-2">{!! $ref !!}</h3>
                                                <div class="d-flex align-items-center justify-content-start"
                                                    style="gap: 10px">
                                                    <a href="{{ route('customer.load.wallet') }}"
                                                        class="btn btn-sm btn-success glow">Fund Wallet</a>
                                                    <a href="{{ route('downlines') }}"
                                                        class="btn btn-sm btn-warning glow">Earning History</a>
                                                    <a href="/customer-transactions"
                                                        class="btn btn-primary btn-sm glow">Transaction History</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Multi Radial Chart Starts -->
                        <div class="col-md-6 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Refer and Earn</h4>
                                    <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p>
                                            Share your referral links with friends to earn handsome rewards
                                        <div class="text-primary">
                                            {{ url('/register'). '?referral='.auth()->user()->username }}
                                        </div>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
                <!-- Dashboard Ecommerce ends -->
            </div>
        </div>
    </div>
    </div>
@endsection
@section('page-script')
    <script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
    <script>
        function copyLink(){
            (async () => {
                try {
                    var copyText = document.getElementById('referral-link');
                    let text = copyText.innerHTML
                    await navigator.clipboard.writeText(text);
                    copyText.innerHTML = 'Link copied!';
                    setTimeout(() => {
                        copyText.innerHTML = text;
                    }, 3000);
                } catch (error) {
                    alert(error.message)
                }
            })();
        }
    </script>
@endsection
