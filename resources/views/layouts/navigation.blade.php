<div class="header-navbar-shadow"></div>
<nav class="header-navbar main-header-navbar navbar-expand-lg navbar navbar-with-menu fixed-top ">
    <div class="navbar-wrapper">
        <div class="navbar-container content">
            <div class="navbar-collapse" id="navbar-mobile">
                <div class="mr-auto float-left bookmark-wrapper d-flex align-items-center">
                    <ul class="nav navbar-nav">
                        <li class="nav-item mobile-menu d-xl-none mr-auto"><a
                                class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i
                                    class="ficon bx bx-menu"></i></a></li>
                    </ul>
                </div>
                <ul class="nav navbar-nav float-right">

                    <li class="nav-item d-none d-lg-block"><a class="nav-link nav-link-expand"><i
                                class="ficon bx bx-fullscreen"></i></a></li>
                    <?php
                    $balance = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(walletBalance(auth()->user()), 2) : 0;
                    $ref = auth()->user()->type == 'customer' ? getSettings()->currency . number_format(referralBalance(auth()->user()), 2) : 0;
                    ?>
                    @if (auth()->user()->type == 'customer')
                        <li class="nav-item d-lg-block">
                            <a class="nav-link">
                                <a href="{{ route('customer.load.wallet') }}" class="text-muted">
                                    <strong style="font-weight: lighter;">Balance: </strong>{!! $balance !!}
                                </a>
                                <br />
                                <a href="{{ route('downlines') }}" class="text-muted">
                                    <strong style="font-weight: lighter;">Referral Earning: </strong>{!! $ref !!}
                                </a>
                            </a>
                        </li>
                    @endif


                </ul>
            </div>
        </div>
    </div>
</nav>
