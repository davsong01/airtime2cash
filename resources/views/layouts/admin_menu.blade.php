@php
    $allowedMenu = auth()->user()->admin->rolepermissions();
    $allowedRoutes = auth()->user()->admin->rolepermissions();
@endphp

<div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto"><a class="navbar-brand" href="/">
                    <div class="brand-logo"><img style="max-height: 70px;text-align: center;margin: auto;max-width: 200px;padding-top: 56.25%;" src="{{ asset(getSettings()->dashboard_logo) }}" />
                    </div>
                    {{-- <div class="brand-logo"><img style="height: auto;" class="logo" src="{{ asset('site/dashboard_logo.jpeg')}}" /></div> --}}

                    <h2 class="brand-text mb-0"></h2>
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"><i
                        class="bx bx-x d-block d-xl-none font-medium-4 primary"></i><i
                        class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block primary"
                        data-ticon="bx-disc"></i></a></li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content" style="margin-top: 20px;">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation"
            data-icon-style="lines">
            {{-- Menu start --}}
            @if (in_array('Dashboard', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="{{ Route::is('dashboard') ? 'active' : '' }} nav-item svg"><a
                        href="{{ route('dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" height="24"
                            viewBox="0 -960 960 960"  width="24">
                            <path
                                d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                        </svg><span class="menu-title" data-i18n="Form Layout">&nbsp;Dashboard</span></a>
                </li>
            @endif
            @if (in_array('Announcement', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="{{ Request::path() == 'profile' ? 'active' : '' }} svg"><a href="{{ route('announcement.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24">
                        <path d="M720-440v-80h160v80H720Zm48 280-128-96 48-64 128 96-48 64Zm-80-480-48-64 128-96 48 64-128 96ZM200-200v-160h-40q-33 0-56.5-23.5T80-440v-80q0-33 23.5-56.5T160-600h160l200-120v480L320-360h-40v160h-80Zm240-182v-196l-98 58H160v80h182l98 58Zm120 36v-268q27 24 43.5 58.5T620-480q0 41-16.5 75.5T560-346ZM300-480Z" />
                        </svg>
                    <span class="menu-title">&nbsp;Announcement</span></a>
                </li>
            @endif
            @if (in_array('Catalogue', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="nav-item svg"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" height="24"
                            viewBox="0 -960 960 960" width="24">
                            <path d="M280-600v-80h560v80H280Zm0 160v-80h560v80H280Zm0 160v-80h560v80H280ZM160-600q-17 0-28.5-11.5T120-640q0-17 11.5-28.5T160-680q17 0 28.5 11.5T200-640q0 17-11.5 28.5T160-600Zm0 160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520q17 0 28.5 11.5T200-480q0 17-11.5 28.5T160-440Zm0 160q-17 0-28.5-11.5T120-320q0-17 11.5-28.5T160-360q17 0 28.5 11.5T200-320q0 17-11.5 28.5T160-280Z"/>
                        </svg><span class="menu-title" data-i18n="Form Elements">&nbsp;Catalogue</span></a>
                    <ul class="menu-content">
                        @if (in_array('api.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('api.*') ? 'active' : '' }} svg"><a href="{{ route('api.index') }}"><i class="bx bx-right-arrow-alt"></i><span class="menu-item">API
                                Providers</span></a>
                            </li>
                        @endif
                        @if (in_array('category.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('category.*') ? 'active' : '' }} svg"><a
                                    href="{{ route('category.index') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item" data-i18n="Input Groups">Categories</span></a>
                            </li>
                        @endif
                        @if (in_array('product.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('product.*') ? 'active' : '' }} svg"><a
                                    href="{{ route('product.index') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item" data-i18n="Input">Products</span></a>
                            </li>
                            <li class="{{ Route::is('airtime2cash.*') ? 'active' : '' }} svg"><a
                                    href="{{ route('airtime2cash.index') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item" data-i18n="Input">Airtime to Cash</span></a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (in_array('Email Management', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="nav-item svg"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" height="24"
                            viewBox="0 -960 960 960" width="24">
                            <path
                                d="M280-600v-80h560v80H280Zm0 160v-80h560v80H280Zm0 160v-80h560v80H280ZM160-600q-17 0-28.5-11.5T120-640q0-17 11.5-28.5T160-680q17 0 28.5 11.5T200-640q0 17-11.5 28.5T160-600Zm0 160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520q17 0 28.5 11.5T200-480q0 17-11.5 28.5T160-440Zm0 160q-17 0-28.5-11.5T120-320q0-17 11.5-28.5T160-360q17 0 28.5 11.5T200-320q0 17-11.5 28.5T160-280Z"/>
                        </svg><span class="menu-title" data-i18n="Form Elements">&nbsp;Email Management</span></a>
                    <ul class="menu-content">
                        @if (in_array('emails.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('emails.index') ? 'active' : '' }} svg"><a href="{{ route('emails.index') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item">Emails</span></a>
                            </li>
                        @endif
                        @if (in_array('emails.pending', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('emails.pending') ? 'active' : '' }} svg"><a
                                    href="{{ route('emails.pending') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item" data-i18n="Input">Pending Emails</span></a>
                            </li>
                        @endif
                        @if (in_array('email.setup', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="{{ Route::is('email.setup') ? 'active' : '' }} svg"><a
                                    href="{{ route('email.setup') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item" data-i18n="Input">Email Setup</span></a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (in_array('Customers', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg nav-item"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" 
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="M660-570q-25 0-42.5-17.5T600-630q0-25 17.5-42.5T660-690q25 0 42.5 17.5T720-630q0 25-17.5 42.5T660-570Zm-360 0q-25 0-42.5-17.5T240-630q0-25 17.5-42.5T300-690q25 0 42.5 17.5T360-630q0 25-17.5 42.5T300-570Zm180 110q-25 0-42.5-17.5T420-520q0-25 17.5-42.5T480-580q25 0 42.5 17.5T540-520q0 25-17.5 42.5T480-460Zm0-220q-25 0-42.5-17.5T420-740q0-25 17.5-42.5T480-800q25 0 42.5 17.5T540-740q0 25-17.5 42.5T480-680Zm0 520q-20 0-40.5-3t-39.5-8v-143q0-35 23.5-60.5T480-400q33 0 56.5 25.5T560-314v143q-19 5-39.5 8t-40.5 3Zm-140-32q-20-8-38.5-18T266-232q-28-20-44.5-52T205-352q0-26-5.5-48.5T180-443q-10-13-37.5-39.5T92-532q-11-11-11-28t11-28q11-11 28-11t28 11l153 145q20 18 29.5 42.5T340-350v158Zm280 0v-158q0-26 10-51t29-42l153-145q12-11 28.5-11t27.5 11q11 11 11 28t-11 28q-23 23-50.5 49T780-443q-14 20-19.5 42.5T755-352q0 36-16.5 68.5T693-231q-16 11-34.5 21T620-192Z" />
                        </svg><span class="menu-title" data-i18n="Form Elements">&nbsp;Customers</span></a>
                    <ul class="menu-content">
                        @if (in_array('customers', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('customers') ? 'active' : '' }}"><a
                                    href="{{ route('customers') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item">All Customers</span></a>
                            </li>
                        @endif
                        @if (in_array('customers.active', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('customers.active.*') ? 'active' : '' }}"><a
                                    href="{{ route('customers.active', 'active') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item"
                                        data-i18n="Input">Active
                                        Customers</span></a>
                            </li>
                        @endif
                        @if (in_array('customers.suspended', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Request::is('customers-suspended/suspended') ? 'active' : '' }}"><a
                                    href="{{ route('customers.suspended', 'suspended') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item"
                                        data-i18n="Input Groups">Suspended Customers</span></a>
                            </li>
                        @endif

                        @if (in_array('customer-blacklist.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg"><a href="{{ request()->route()->getPrefix() }}/customer-blacklist"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item"
                                        data-i18n="Input Groups">Blacklisted Customers</span></a>
                            </li>
                        @endif
                        @if (in_array('customerlevel.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('customerlevel.*') ? 'active' : '' }}"><a
                                    href="{{ route('customerlevel.index') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item"
                                        data-i18n="Input Groups">Customer Levels</span></a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (in_array('User Management', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg nav-item"><a href="#"><svg  xmlns="http://www.w3.org/2000/svg"
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="M400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-8 18-13.5 37.5T404-360h-4q-71 0-127.5 18T180-306q-9 5-14.5 14t-5.5 20v32h252q6 21 16 41.5t22 38.5H80Zm560 40-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 11t21.5 15l58-20 40 70-46 40q2 12 2 25t-2 25l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80Zm40-120q33 0 56.5-23.5T760-320q0-33-23.5-56.5T680-400q-33 0-56.5 23.5T600-320q0 33 23.5 56.5T680-240ZM400-560q33 0 56.5-23.5T480-640q0-33-23.5-56.5T400-720q-33 0-56.5 23.5T320-640q0 33 23.5 56.5T400-560Zm0-80Zm12 400Z" />
                        </svg><span class="menu-title" data-i18n="Form Elements">&nbsp;User Management</span></a>
                    <ul class="menu-content">
                        @if (in_array('admins', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admins') ? 'active' : '' }}"><a href="{{ route('admins') }}"><i
                            class="bx bx-right-arrow-alt"></i><span class="menu-item">All
                                        Admins</span></a>
                            </li>
                        @endif
                        {{-- @if (in_array('role.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds())) --}}
                            <li class="svg {{ Route::is('role.index') ? 'active' : '' }}"><a href="{{ route('role.index') }}"><i
                            class="bx bx-right-arrow-alt"></i><span class="menu-item">All
                                        Roles</span></a>
                            </li>
                            <li class="svg {{ Route::is('permission.index') ? 'active' : '' }}"><a href="{{ route('permission.index') }}"><i
                            class="bx bx-right-arrow-alt"></i><span class="menu-item">All
                                        Permissions</span></a>
                            </li>
                        {{-- @endif --}}
                    </ul>
                </li>
            @endif
            @if (in_array('A2Cash Management', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg {{ Request::path() == 'admin.airtime.2.cash.log' ? 'active' : '' }}"><a href="{{ route('admin.airtime.2.cash.log') }}"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path  d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/></svg><span class="menu-title">&nbsp;A2Cash Management</span></a>
                </li>
            @endif
            @if (in_array('Financials', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg nav-item"><a href="#"><svg  xmlns="http://www.w3.org/2000/svg"
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="M80-200v-80h400v80H80Zm0-200v-80h200v80H80Zm0-200v-80h200v80H80Zm744 400L670-354q-24 17-52.5 25.5T560-320q-83 0-141.5-58.5T360-520q0-83 58.5-141.5T560-720q83 0 141.5 58.5T760-520q0 29-8.5 57.5T726-410l154 154-56 56ZM560-400q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z" />
                        </svg><span class="menu-title" data-i18n="Form Elements">&nbsp;Financials</span></a>
                    <ul class="menu-content">
                        @if (in_array('admin.trans', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.trans') ? 'active' : '' }}"><a
                                    href="{{ route('admin.trans') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item">Product Purchase Log</span></a>
                            </li>
                        @endif
                        @if (in_array('admin.walletfundinglog', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.walletfundinglog') ? 'active' : '' }}"><a
                                    href="{{ route('admin.walletfundinglog') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item">Wallet Funding
                                        Log</span></a>
                            </li>
                        @endif
                        
                        @if (in_array('admin.walletlog', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.walletlog') ? 'active' : '' }}"><a
                                    href="{{ route('admin.walletlog') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item">Wallet
                                        Log</span></a>
                            </li>
                        @endif
                        @if (in_array('admin.earninglog', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.earninglog') ? 'active' : '' }}"><a
                                    href="{{ route('admin.earninglog') }}"><i class="bx bx-right-arrow-alt"></i><span
                                        class="menu-item">Earnings
                                        Log</span></a>
                            </li>
                        @endif
                        @if (in_array('admin.credit.customer', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.credit.customer') ? 'active' : '' }}"><a
                                    href="{{ route('admin.credit.customer') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item">Credit
                                        Customer</span></a>
                            </li>
                        @endif
                        @if (in_array('admin.debit.customer', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.debit.customer') ? 'active' : '' }}"><a
                                    href="{{ route('admin.debit.customer') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item">Debit
                                        Customer</span></a>
                            </li>
                        @endif
                        @if (in_array('admin.verifybiller', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                        <li class="svg {{ Route::is('admin.verifybiller') ? 'active' : '' }}"><a
                             href="{{ route('admin.verifybiller') }}"><i
                                 class="bx bx-right-arrow-alt"></i><span class="menu-item">Verify Biller</span></a>
                        </li>
                        @endif
                        @if (in_array('billerlog.index', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                        <li class="svg {{ Route::is('billerlog.index') ? 'active' : '' }}"><a
                             href="{{ route('billerlog.index') }}"><i
                                 class="bx bx-right-arrow-alt"></i><span class="menu-item">Biller Logs</span></a>
                        </li>
                        @endif
                        @if (in_array('admin.reserved.accounts', $allowedRoutes) || in_array(1, auth()->user()->admin->roleIds()))
                            <li class="svg {{ Route::is('admin.reserved.accounts') ? 'active' : '' }}"><a
                                    href="{{ route('admin.reserved.accounts') }}"><i
                                        class="bx bx-right-arrow-alt"></i><span class="menu-item">Reserved Account
                                        Numbers</span></a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            {{-- @if (in_array('Profile', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds())) --}}
                <li class="svg {{ Request::path() == 'profile' ? 'active' : '' }}"><a
                        href="{{ route('profile.edit') }}"><svg xmlns="http://www.w3.org/2000/svg"
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="M200-246q54-53 125.5-83.5T480-360q83 0 154.5 30.5T760-246v-514H200v514Zm280-194q58 0 99-41t41-99q0-58-41-99t-99-41q-58 0-99 41t-41 99q0 58 41 99t99 41ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm80-80h400v-10q-42-35-93-52.5T480-280q-56 0-107 17.5T280-210v10Zm200-320q-25 0-42.5-17.5T420-580q0-25 17.5-42.5T480-640q25 0 42.5 17.5T540-580q0 25-17.5 42.5T480-520Zm0 17Z" />
                        </svg><span class="menu-title">&nbsp;My Profile</span></a>
                </li>
            {{-- @endif --}}
            @if (in_array('Callback Analysis', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg {{ Route::is('callback.analysis') ? 'active' : '' }}"><a
                        href="{{ route('callback.analysis') }}"><svg xmlns="http://www.w3.org/2000/svg"
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="M440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80ZM320-440v-80h320v80H320Zm200 160v-80h160q50 0 85-35t35-85q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480q0 83-58.5 141.5T680-280H520Z"
                                 />
                        </svg><span class="menu-title">&nbsp;Callback Analysis</span></a>
                </li>
            @endif
            @if (in_array('KYC Management', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg {{ Route::is('admin.*') ? 'active' : '' }}"><a
                        href="{{ route('admin.kyc') }}"><svg xmlns="http://www.w3.org/2000/svg" height="24"
                            viewBox="0 -960 960 960"  width="24">
                            <path
                                d="M480-260q68 0 123.5-38.5T684-400H276q25 63 80.5 101.5T480-260ZM312-520l44-42 42 42 42-42-84-86-86 86 42 42Zm250 0 42-42 44 42 42-42-86-86-84 86 42 42ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z" />
                        </svg><span class="menu-title">&nbsp;KYC Management</span></a>
                </li>
            @endif
            @if (in_array('Payment Gateway Settings', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg {{ Route::is('paymentgateway.*') ? 'active' : '' }}"><a
                        href="{{ route('paymentgateway.index') }}"><svg xmlns="http://www.w3.org/2000/svg"
                            height="24" viewBox="0 -960 960 960" width="24">
                            <path
                                d="m480-560-56-56 63-64H320v-80h167l-64-64 57-56 160 160-160 160ZM280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM40-800v-80h131l170 360h280l156-280h91L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68.5-39t-1.5-79l54-98-144-304H40Z"
                                 />
                        </svg><span class="menu-title">&nbsp;Payment Gateway Settings</span></a></li>
            @endif
            @if (in_array('General Settings', $allowedMenu) || in_array(1, auth()->user()->admin->roleIds()))
                <li class="svg {{  Route::is('settings.edit') ? 'active' : '' }}"><a href="{{ route('settings.edit') }}"><svg  xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z" />
            </svg><span class="menu-title">&nbsp;General Settings</span></a></li>
            @endif
            <li class="svg"><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><svg
                        xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24">
                        <path
                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z" />
                    </svg><span class="menu-title">&nbsp;Logout</span></a>
            </li>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </ul>
    </div>
</div>
