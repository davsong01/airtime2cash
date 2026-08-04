@php
  $navbarUser = auth()->user();
  $navbarFullName = trim(collect([
    $navbarUser?->firstname,
    $navbarUser?->middlename,
    $navbarUser?->lastname,
  ])->filter()->implode(' '));
  $navbarFullName = $navbarFullName ?: ($navbarUser?->username ?: 'Customer');
  $navbarInitialParts = collect([$navbarUser?->firstname, $navbarUser?->lastname])->filter()->values();

  if ($navbarInitialParts->isEmpty()) {
    $navbarInitialParts = collect(preg_split('/\s+/', $navbarFullName))->filter()->values();
  }

  $navbarInitials = $navbarInitialParts
    ->take(2)
    ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
    ->implode('');
  $navbarInitials = $navbarInitials ?: 'CU';
  $navbarAvatarColors = ['#2563eb', '#0891b2', '#059669', '#d97706', '#dc2626', '#4f46e5', '#0f766e'];
  $navbarAvatarKey = $navbarUser?->id ?: $navbarFullName;
  $navbarAvatarColor = $navbarAvatarColors[abs(crc32((string) $navbarAvatarKey)) % count($navbarAvatarColors)];
@endphp

<style>
  .navbar-user-initials {
    display: inline-flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, .88);
    border-radius: 50%;
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .02em;
    box-shadow: 0 .3rem .8rem rgba(34, 48, 62, .18);
  }

  .navbar-user-initials--large {
    font-size: .9rem;
  }

  .navbar-user-menu {
    width: 280px;
  }

  .navbar-user-menu .dropdown-item {
    padding-block: .62rem;
  }

  .navbar-user-email {
    display: block;
    max-width: 170px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>

<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base bx bx-menu icon-md"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

      <!-- Search -->
      <div class="navbar-nav align-items-center">
        <div class="nav-item navbar-search-wrapper mb-0">
          <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
            <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
          </a>
        </div>
      </div>

      <!-- /Search -->





    <ul class="navbar-nav flex-row align-items-center ms-md-auto">



        <!-- Language -->
        <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class="icon-base bx bx-globe icon-md"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-language="en" data-text-direction="ltr">
                <span>English</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-language="fr" data-text-direction="ltr">
                <span>French</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-language="ar" data-text-direction="rtl">
                <span>Arabic</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-language="de" data-text-direction="ltr">
                <span>German</span>
              </a>
            </li>
          </ul>
        </li>
        <!--/ Language -->

          <!-- Style Switcher -->
          <li class="nav-item dropdown me-2 me-xl-0">
            <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class="icon-base bx bx-sun icon-md theme-icon-active"></i>
              <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
              <li>
                <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="false">
                  <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i>Light</span>
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
                  <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i>Dark</span>
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">
                  <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i>System</span>
                </button>
              </li>
            </ul>
          </li>
          <!-- / Style Switcher-->

        <!-- Semi Dark Toggle -->
        <li class="nav-item me-2 me-xl-0">
          <button
            type="button"
            class="nav-link btn btn-link p-0 border-0 shadow-none"
            id="nav-semi-dark-toggle"
            aria-label="Toggle semi dark menu">
            <i class="icon-base bx bx-brightness-half icon-md" id="nav-semi-dark-icon"></i>
          </button>
        </li>
        <!-- / Semi Dark Toggle -->


        <!-- Quick links  -->
        <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="icon-base bx bx-grid-alt icon-md"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0">
            <div class="dropdown-menu-header border-bottom">
              <div class="dropdown-header d-flex align-items-center py-3">
                <h6 class="mb-0 me-auto">Shortcuts</h6>
                <a href="javascript:void(0)" class="dropdown-shortcuts-add py-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Add shortcuts"><i class="icon-base bx bx-plus-circle text-heading"></i></a>
              </div>
            </div>
            <div class="dropdown-shortcuts-list scrollable-container">
              <div class="row row-bordered overflow-visible g-0">
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-calendar icon-26px text-heading"></i>
                  </span>
                  <a href="app-calendar.html" class="stretched-link">Calendar</a>
                  <small>Appointments</small>
                </div>
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-food-menu icon-26px text-heading"></i>
                  </span>
                  <a href="app-invoice-list.html" class="stretched-link">Invoice App</a>
                  <small>Manage Accounts</small>
                </div>
              </div>
              <div class="row row-bordered overflow-visible g-0">
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-user icon-26px text-heading"></i>
                  </span>
                  <a href="app-user-list.html" class="stretched-link">User App</a>
                  <small>Manage Users</small>
                </div>
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-check-shield icon-26px text-heading"></i>
                  </span>
                  <a href="app-access-roles.html" class="stretched-link">Role Management</a>
                  <small>Permission</small>
                </div>
              </div>
              <div class="row row-bordered overflow-visible g-0">
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-pie-chart-alt-2 icon-26px text-heading"></i>
                  </span>
                  <a href="index-2.html" class="stretched-link">Dashboard</a>
                  <small>User Dashboard</small>
                </div>
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-cog icon-26px text-heading"></i>
                  </span>
                  <a href="pages-account-settings-account.html" class="stretched-link">Setting</a>
                  <small>Account Settings</small>
                </div>
              </div>
              <div class="row row-bordered overflow-visible g-0">
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-help-circle icon-26px text-heading"></i>
                  </span>
                  <a href="pages-faq.html" class="stretched-link">FAQs</a>
                  <small>FAQs & Articles</small>
                </div>
                <div class="dropdown-shortcuts-item col">
                  <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                    <i class="icon-base bx bx-window-open icon-26px text-heading"></i>
                  </span>
                  <a href="modal-examples.html" class="stretched-link">Modals</a>
                  <small>Useful Popups</small>
                </div>
              </div>
            </div>
          </div>
        </li>
        <!-- Quick links -->

        <!-- Notification -->
        @include('sneat.layouts.notifications')
        <!--/ Notification -->
        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
            <div class="avatar">
              <span class="navbar-user-initials" style="background-color: {{ $navbarAvatarColor }};">{{ $navbarInitials }}</span>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end navbar-user-menu">
            <li>
              <a class="dropdown-item py-3" href="{{ route('profile.edit') }}">
                <div class="d-flex">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar">
                      <span class="navbar-user-initials navbar-user-initials--large" style="background-color: {{ $navbarAvatarColor }};">{{ $navbarInitials }}</span>
                    </div>
                  </div>
                  <div class="flex-grow-1 min-w-0">
                    <h6 class="mb-1 text-truncate">{{ $navbarFullName }}</h6>
                    <small class="navbar-user-email text-body-secondary">{{ $navbarUser?->email }}</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider my-1"></div>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('customer.load.wallet') }}">
                <i class="icon-base bx bx-wallet icon-md me-3"></i><span>Fund Wallet</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('customer.transaction.history') }}">
                <i class="icon-base bx bx-receipt icon-md me-3"></i><span>Transaction History</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('update.kyc.details') }}">
                <i class="icon-base bx bx-id-card icon-md me-3"></i><span>KYC Verification</span>
              </a>
            </li>
            <li>
              <div class="dropdown-divider my-1"></div>
            </li>
            <li>
              <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="dropdown-item text-danger" type="submit">
                  <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                </button>
              </form>
            </li>
          </ul>
        </li>
        <!--/ User -->
      
    </ul>
  </div>
</nav>
