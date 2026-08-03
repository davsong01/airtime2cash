@extends('layouts.app')
@section('title', 'API Settings')

@section('page-css')
    <style>
        .reset-pin {
            font-size: 10px;
            float: right;
        }

        .key-field {
            padding: 10px;
            margin-bottom: 20px;
            width: 100%;
            border: #5A8DEE 2px solid;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .key-field i {
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
    </style>
@endsection
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <!-- Basic Inputs start -->
                <section id="basic-input">
                    <div class="row">

                        <div class="col-md-6 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Refer and Earn</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <p>Share your referral links with friends to earn handsome rewards
                                            <div class="text-primary">{{ url('/register'). '?referral='.auth()->user()->username }}</div>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">KYC Status</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        @if (getFinalKycStatus(auth()->user()->customer->id) == 'verified')
                                            <button class="btn btn-success">Verified</button>
                                        @else
                                            <button class="btn btn-danger">Unverified</button>
                                        @endif
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-12 dashboard-visit">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">API Access</h4>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        @if (auth()->user()->customer->api_access == 'active')
                                            <button class="btn btn-success">Active</button>
                                        @else
                                            <button class="btn btn-danger">Inactive</button>
                                        @endif
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if (auth()->user()->customer->api_access == 'active')
                        <div class="col-md-12">
                            <div class="card">
                                <div class="content-body">
                                    <!-- Nav Filled Starts -->
                                    <section id="nav-filled">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <div class="col-md-12">
                                                        <div class="card-header d-flex justify-content-between align-items-center" style="padding:1.4rem 0.7rem">
                                                            
                                                            <div>
                                                                <h4 class="card-title">Generate Keys</h4>
                                                                <p>
                                                                    Click the generate new API keys button above to generate API public and secret keys.
                                                                    <br>NOTE: You can only view these keys once, however you can generate new public and secret keys as many times as you want.                                                                </p>
                                                                    <button class="btn btn-danger api-key-btn">Generate New API
                                                                        Keys</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-8">
                                                                    <label class="label" for="">API Key</label>
                                                                    <div class="key-field" id="api">
                                                                        <span>{{ auth()->user()->api_key }}</span>
                                                                        <i class="fa fa-copy text-danger copy"></i>
                                                                    </div>
                                                                    <label class="label" for="">Public Key</label>

                                                                    <div class="key-field" id="public">
                                                                        <span></span>
                                                                        <i class="fa fa-copy text-primary copy"></i>
                                                                    </div>
                                                                    <label class="label" for="">Secret Key</label>

                                                                    <div class="key-field" id="secret">
                                                                        <span></span>
                                                                        <i class="fa fa-copy text-primary copy"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                    <!-- Nav Filled Ends -->
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    <script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>

    <script>
        $('.api-key-btn').click(function() {
            $.ajax({
                url: '{{ route('profile.keys') }}',
                beforeSend: () => {},
                success: res => {
                    let data = res.data;
                    
                    $('#public span').html(data.public);
                    $('#secret span').html(data.secret);
                },
            });
        });

        $('.copy').click(function () {
            (async () => {
                try {
                    var copyText = $(this).prev('span');
                    let text = copyText.html();
                    await navigator.clipboard.writeText(text);
                    copyText.html('Key copied to clipboard!').css({color: 'green'});
                    setTimeout(() => {
                        copyText.html(text).css({color: '#555'});
                    }, 3000);
                } catch (error) {
                    alert(error.message)
                }
            })();
        })
    </script>
@endsection
