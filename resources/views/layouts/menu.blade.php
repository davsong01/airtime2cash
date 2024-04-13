@if (auth()->user()->email_verified_at)
<div class="main-menu menu-fixed menu-dark menu-accordion" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto"><a class="navbar-brand" href="/">
                    {{-- <div class="brand-logo"><img class="logo" src="{{ asset(getSettings()->logo ) }}" /></div> --}}
                    <div class="brand-logo"><img style="max-height: 70px;text-align: center;margin: auto;max-width: 150px;object-fit: contain;" src="{{ asset(getSettings()->dashboard_logo) }}" />
                    <h2 class="brand-text mb-0"></h2>
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"><i class="bx bx-x d-block d-xl-none font-medium-4 primary"></i><i class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block primary" data-ticon="bx-disc"></i></a></li>
        </ul>
    </div>
    {{-- <div class="shadow-bottom"></div> --}}
    <div class="main-menu-content" style="margin-top: 20px;" >
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="lines">
            {{-- start --}}
            <?php
                $balance = auth()->user()->type == 'customer' ? getSettings()->currency .number_format(walletBalance(auth()->user()), 2) : 0;
            ?>
            <li style="" class="navigation-header customer-details"><span>Wallet Balance</span><br>{!! $balance !!}</li>
            <li style="" class="navigation-header customer-details"><span>Customer Level</span><br><strong>{{ auth()->user()->customer?->level?->name }}</strong></li>
            <li class="block-header-color navigation-header"><span>Make Payment</span></li>
            <?php $categories = getCategories() ?>
            @foreach($categories as $category)
                <li class="{{ Request::path() == 'customer/'.$category->slug ? 'active' : '' }} svg"><a href="{{ route('open.transaction.page', $category->slug)}}">@if($category->icon){!! $category->icon !!}@else <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M880-720v480q0 33-23.5 56.5T800-160H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720Zm-720 80h640v-80H160v80Zm0 160v240h640v-240H160Zm0 240v-480 480Z" fill="white"/></svg>@endif<span class="menu-title"> &nbsp;{{ $category->display_name }}</span></a>
                </li>
            @endforeach
            <li class="{{ Route::is('airtime-to-cash') ? 'active' : '' }} svg"><a target="_blank" href="{{ route('airtime-to-cash')}}">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M760-120q-39 0-70-22.5T647-200H440q-66 0-113-47t-47-113q0-66 47-113t113-47h80q33 0 56.5-23.5T600-600q0-33-23.5-56.5T520-680H313q-13 35-43.5 57.5T200-600q-50 0-85-35t-35-85q0-50 35-85t85-35q39 0 69.5 22.5T313-760h207q66 0 113 47t47 113q0 66-47 113t-113 47h-80q-33 0-56.5 23.5T360-360q0 33 23.5 56.5T440-280h207q13-35 43.5-57.5T760-360q50 0 85 35t35 85q0 50-35 85t-85 35ZM200-680q17 0 28.5-11.5T240-720q0-17-11.5-28.5T200-760q-17 0-28.5 11.5T160-720q0 17 11.5 28.5T200-680Z"/>
                </svg>
                <span class="menu-title"> &nbsp;Airtime To Cash</span></a>
            </li>
            <li class="block-header-color navigation-header"><span>Self Service</span></li>
            <li class="{{ Route::is('dashboard') ? 'active' : '' }} nav-item svg"><a href="{{ route('dashboard') }}"><svg
            xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" fill="white"
            width="24">
            <path
                d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                </svg><span class="menu-title" data-i18n="Form Layout">&nbsp;Dashboard</span></a>
            </li>
            <li class="{{ Request::path() == 'profile' ? 'active' : '' }} svg"><a href="{{ route('profile.edit')}}"><svg xmlns="http://www.w3.org/2000/svg" height="24" fill="white" viewBox="0 -960 960 960" width="24"><path d="M200-246q54-53 125.5-83.5T480-360q83 0 154.5 30.5T760-246v-514H200v514Zm280-194q58 0 99-41t41-99q0-58-41-99t-99-41q-58 0-99 41t-41 99q0 58 41 99t99 41ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm80-80h400v-10q-42-35-93-52.5T480-280q-56 0-107 17.5T280-210v10Zm200-320q-25 0-42.5-17.5T420-580q0-25 17.5-42.5T480-640q25 0 42.5 17.5T540-580q0 25-17.5 42.5T480-520Zm0 17Z"/></svg><span class="menu-title">&nbsp;My Profile</span></a></li>

            <li class="{{ Request::path() == 'alldownlines' ? 'active' : '' }} svg"><a href="{{ route('alldownlines')}}"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M500-482q29-32 44.5-73t15.5-85q0-44-15.5-85T500-798q60 8 100 53t40 105q0 60-40 105t-100 53Zm220 322v-120q0-36-16-68.5T662-406q51 18 94.5 46.5T800-280v120h-80Zm80-280v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80Zm-480-40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM0-160v-112q0-34 17.5-62.5T64-378q62-31 126-46.5T320-440q66 0 130 15.5T576-378q29 15 46.5 43.5T640-272v112H0Zm320-400q33 0 56.5-23.5T400-640q0-33-23.5-56.5T320-720q-33 0-56.5 23.5T240-640q0 33 23.5 56.5T320-560ZM80-240h480v-32q0-11-5.5-20T540-306q-54-27-109-40.5T320-360q-56 0-111 13.5T100-306q-9 5-14.5 14T80-272v32Zm240-400Zm0 400Z"/></svg><span class="menu-title">&nbsp;Downlines</span></a></li>

            <li class="svg {{ Route::is('downlines.*') ? 'active' : '' }} svg"><a href="{{ route('downlines')}}"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M560-440q-50 0-85-35t-35-85q0-50 35-85t85-35q50 0 85 35t35 85q0 50-35 85t-85 35ZM280-320q-33 0-56.5-23.5T200-400v-320q0-33 23.5-56.5T280-800h560q33 0 56.5 23.5T920-720v320q0 33-23.5 56.5T840-320H280Zm80-80h400q0-33 23.5-56.5T840-480v-160q-33 0-56.5-23.5T760-720H360q0 33-23.5 56.5T280-640v160q33 0 56.5 23.5T360-400Zm440 240H120q-33 0-56.5-23.5T40-240v-440h80v440h680v80ZM280-400v-320 320Z"/></svg><span class="menu-title">&nbsp;Referral Earnings</span></a></li>

            <li class="svg {{ Request::path() == 'customer-level-upgrade' ? 'active' : '' }}"><a href="{{ route('customer.level.upgrade')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="white" height="24" viewBox="0 -960 960 960" width="24"><path d="M280-160v-80h400v80H280Zm160-160v-327L336-544l-56-56 200-200 200 200-56 56-104-103v327h-80Z"/></svg><span class="menu-title">&nbsp;Upgrade Account</span></a></li>

            <li class="svg {{ Route::is('customer.load.wallet') ? 'active' : '' }}"><a href="{{ route('customer.load.wallet')}}"><svg xmlns="http://www.w3.org/2000/svg" height="24" fill="white" viewBox="0 -960 960 960" width="24"><path d="M200-200v-560 560Zm0 80q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v100h-80v-100H200v560h560v-100h80v100q0 33-23.5 56.5T760-120H200Zm320-160q-33 0-56.5-23.5T440-360v-240q0-33 23.5-56.5T520-680h280q33 0 56.5 23.5T880-600v240q0 33-23.5 56.5T800-280H520Zm280-80v-240H520v240h280Zm-160-60q25 0 42.5-17.5T700-480q0-25-17.5-42.5T640-540q-25 0-42.5 17.5T580-480q0 25 17.5 42.5T640-420Z"/></svg><span class="menu-title">&nbsp;Fund Wallet</span></a></li>
            
            <li class="svg {{ Route::is('customer.transaction.history') ? 'active' : '' }}"><a href="{{ route('customer.transaction.history')}}"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M80-200v-80h400v80H80Zm0-200v-80h200v80H80Zm0-200v-80h200v80H80Zm744 400L670-354q-24 17-52.5 25.5T560-320q-83 0-141.5-58.5T360-520q0-83 58.5-141.5T560-720q83 0 141.5 58.5T760-520q0 29-8.5 57.5T726-410l154 154-56 56ZM560-400q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z"/></svg><span class="menu-title">&nbsp;Transactions History</span></a></li>

            <li class="svg {{ Route::is('customer.airtime2cash.transaction.history') ? 'active' : '' }}"><a href="{{ route('customer.airtime2cash.transaction.history')}}"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/></svg><span class="menu-title">&nbsp;A2Cash History</span></a></li>

            <li class="svg {{ Request::path() == 'customer-transaction-report' ? 'active' : '' }}"><a href="{{ route('customer.transaction.report')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="white" height="24" viewBox="0 -960 960 960" width="24"><path d="M240-80q-50 0-85-35t-35-85v-120h120v-560l60 60 60-60 60 60 60-60 60 60 60-60 60 60 60-60 60 60 60-60v680q0 50-35 85t-85 35H240Zm480-80q17 0 28.5-11.5T760-200v-560H320v440h360v120q0 17 11.5 28.5T720-160ZM360-600v-80h240v80H360Zm0 120v-80h240v80H360Zm320-120q-17 0-28.5-11.5T640-640q0-17 11.5-28.5T680-680q17 0 28.5 11.5T720-640q0 17-11.5 28.5T680-600Zm0 120q-17 0-28.5-11.5T640-520q0-17 11.5-28.5T680-560q17 0 28.5 11.5T720-520q0 17-11.5 28.5T680-480ZM240-160h360v-80H200v40q0 17 11.5 28.5T240-160Zm-40 0v-80 80Z"/></svg><span class="menu-title">&nbsp;Reports</span></a></li>
            <li class="svg {{ Request::path() == 'customer-update-kyc-info' ? 'active' : '' }}"><a href="{{ route('update.kyc.details')}}"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M120-120v-80l80-80v160h-80Zm160 0v-240l80-80v320h-80Zm160 0v-320l80 81v239h-80Zm160 0v-239l80-80v319h-80Zm160 0v-400l80-80v480h-80ZM120-327v-113l280-280 160 160 280-280v113L560-447 400-607 120-327Z"/></svg><span class="menu-title">&nbsp;KYC Info</span></a></li>
            
            @if(!empty(getSettings()->support_link))
            <li class="svg"><a target="_blank" href="{{ getSettings()->support_link}}"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-480q-17 0-28.5-11.5T440-520q0-17 11.5-28.5T480-560q17 0 28.5 11.5T520-520q0 17-11.5 28.5T480-480Zm0-240q-17 0-28.5-11.5T440-760q0-17 11.5-28.5T480-800q17 0 28.5 11.5T520-760q0 17-11.5 28.5T480-720Zm80 120q-17 0-28.5-11.5T520-640q0-17 11.5-28.5T560-680q17 0 28.5 11.5T600-640q0 17-11.5 28.5T560-600Zm40 120q-17 0-28.5-11.5T560-520q0-17 11.5-28.5T600-560q17 0 28.5 11.5T640-520q0 17-11.5 28.5T600-480Zm0-240q-17 0-28.5-11.5T560-760q0-17 11.5-28.5T600-800q17 0 28.5 11.5T640-760q0 17-11.5 28.5T600-720Zm80 120q-17 0-28.5-11.5T640-640q0-17 11.5-28.5T680-680q17 0 28.5 11.5T720-640q0 17-11.5 28.5T680-600Zm40 120q-17 0-28.5-11.5T680-520q0-17 11.5-28.5T720-560q17 0 28.5 11.5T760-520q0 17-11.5 28.5T720-480Zm0-240q-17 0-28.5-11.5T680-760q0-17 11.5-28.5T720-800q17 0 28.5 11.5T760-760q0 17-11.5 28.5T720-720Zm80 120q-17 0-28.5-11.5T760-640q0-17 11.5-28.5T800-680q17 0 28.5 11.5T840-640q0 17-11.5 28.5T800-600Zm40 120q-17 0-28.5-11.5T800-520q0-17 11.5-28.5T840-560q17 0 28.5 11.5T880-520q0 17-11.5 28.5T840-480Zm0-240q-17 0-28.5-11.5T800-760q0-17 11.5-28.5T840-800q17 0 28.5 11.5T880-760q0 17-11.5 28.5T840-720Zm-42 600q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z"/></svg><span class="menu-title">&nbsp;Contact Us</span></a></li>
            @endif
            
            <li class="svg"><a href="{{ route('logout')}}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><svg fill="white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg><span class="menu-title">&nbsp;Logout</span></a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>

        </ul>
    </div>
</div>
@endif
