@extends('layouts.app')
@section('page-css')
    <style>
        .card-container {
            display: flex;
            width: 100%;
            justify-content: space-around;
            padding: 20px;
        }
        .card2 {
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
            padding: 20px;
            margin: 10px;
            background-color: #f9f9f9;
        }
        .card2 h2 {
            color: #333;
            font-size: 20px;
        }
        .card2 .amount {
            font-size: 24px;
            color: green;
            margin: 10px 0;
        }
        .card2 a {
            display: block;
            margin: 5px 0;
            color: #007bff;
            text-decoration: none;
        }
        .card2 a:hover {
            text-decoration: underline;
        }

        .button-container {
            font-family: Arial, sans-serif;
        }

        .fund-wallet-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
            width: 100%;
        }

        /* .fund-wallet-btn:hover {
            background-color: #e5533d;
        } */

        .icon {
            /* background: white; */
            margin-right: 10px;
            border-radius: 50%;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .display {
            font-size: 12px;
        }

        .svg{
            fill: white !important;
        }
    </style>
@endsection
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
                            @if(getSettings()->google_dashboard_ad_enabled ?? true)
                                {!! getSettings()->google_dashboard_ad_code !!}
                            @endif
                        <div class="col-md-12">
                            @include('layouts.alerts')
                        </div>
                        @if(!empty($customer))
                        <div class="col-md-6 dashboard-greetings">
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
                        <!-- Wallet balance -->
                        <div class="col-md-6 dashboard-greetings">
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
                    </div>
                    <div class="row">
                        <!-- Refer and earn -->
                        <div class="col-md-6 dashboard-visit">
                            <div class="card" style="width: 100%;">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Refer and Earn</h4>
                                    <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p>
                                            Share your referral links with friends to earn handsome rewards
                                            <div class="text-primary">
                                                {{ url('/register') . '?referral=' . auth()->user()->username }}
                                            </div>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            
                            <div class="row">
                                <div class="col-md-4" style="padding-bottom: 5px;">
                                    <div class="button-container">
                                        <a target="_blank" href="{{ route('airtime-to-cash')}}" class="fund-wallet-btn" style="background-color: #DC143C">
                                            <span style="font-size: 11px;" class="icon"><svg xmlns="http://www.w3.org/2000/svg" height="24" fill="white" viewBox="0 -960 960 960" width="24"><path d="M760-120q-39 0-70-22.5T647-200H440q-66 0-113-47t-47-113q0-66 47-113t113-47h80q33 0 56.5-23.5T600-600q0-33-23.5-56.5T520-680H313q-13 35-43.5 57.5T200-600q-50 0-85-35t-35-85q0-50 35-85t85-35q39 0 69.5 22.5T313-760h207q66 0 113 47t47 113q0 66-47 113t-113 47h-80q-33 0-56.5 23.5T360-360q0 33 23.5 56.5T440-280h207q13-35 43.5-57.5T760-360q50 0 85 35t35 85q0 50-35 85t-85 35ZM200-680q17 0 28.5-11.5T240-720q0-17-11.5-28.5T200-760q-17 0-28.5 11.5T160-720q0 17 11.5 28.5T200-680Z"></path>
                                            </svg> Airtime to cash</span>
                                        </a>
                                    </div>
                                </div>
                                @foreach (getCategories() as $category)
                                <?php
                                    $colors = [
                                                '#FF6347', // Tomato
                                                '#FFD700', // Gold
                                                '#FF8C00', // Dark Orange
                                                '#4682B4', // Steel Blue
                                                '#008080', // Teal
                                                '#708090', // Slate Gray
                                                '#20B2AA', // Light Sea Green
                                                '#FF4500', // Orange Red
                                                '#6B8E23', // Olive Drab
                                                '#800080', // Purple
                                                '#2E8B57', // Sea Green
                                                '#8A2BE2', // Blue Violet
                                                '#DC143C', // Crimson
                                                '#008B8B', // Dark Cyan
                                                '#1E90FF', // Dodger Blue
                                                '#C71585', // Medium Violet Red
                                                '#483D8B', // Dark Slate Blue
                                                '#FF1493', // Deep Pink
                                                '#2F4F4F', // Dark Slate Gray
                                                '#FF8C00'  // Dark Orange];
                                    ];
                                    $randomColor = $colors[array_rand($colors)];
                                ?>
                                <div class="col-md-4" style="padding-bottom: 5px;">
                                    <div class="button-container">
                                        <a target="_blank" href="{{ route('open.transaction.page', $category->slug)}}" class="fund-wallet-btn" style="background-color: {{ $randomColor }}">
                                            <span class="icon">@if($category->icon){!! $category->icon !!}@else <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M880-720v480q0 33-23.5 56.5T800-160H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720Zm-720 80h640v-80H160v80Zm0 160v240h640v-240H160Zm0 240v-480 480Z" fill="white"/></svg>@endif</span> <span class="display">&nbsp;{{ $category->display_name }}</span>
                                        </a>
                                    </div>
                                </div>
                                @endforeach
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
