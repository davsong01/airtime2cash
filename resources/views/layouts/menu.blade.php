@if(auth()->user()->email_verified_at)
<div class="main-menu menu-fixed menu-dark menu-accordion" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto">
                <a class="navbar-brand" href="/">
                    <div class="brand-logo">
                        <img style="max-height: 70px;text-align: center;margin: auto;max-width: 150px;object-fit: contain;" src="{{ asset(getSettings()->dashboard_logo) }}" />
                        <h2 class="brand-text mb-0"></h2>
                    </div>
                </a>
            </li>
            <li class="nav-item nav-toggle">
                <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
                    <i class="bx bx-x d-block d-xl-none font-medium-4 primary"></i>
                    <i class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block primary" data-ticon="bx-disc"></i>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-menu-content" style="margin-top: 20px;">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="lines">
            @include('shared.customer-menu-items', ['variant' => 'legacy'])
        </ul>
    </div>
</div>
@endif
