    <div class="row">
        <!-- Referral Chart Starts-->
        <div class="col-xl-12 col-12">
            <div class="card" style="margin-bottom:0px">
                <div class="card-content">
                    <div class="card-body text-center pb-0">
                        <span class="text-muted">Wallet Balance</span>
                        <h2 style="color: black;">{{$balance = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(walletBalance(auth()->user())) : 0}}</h2>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
