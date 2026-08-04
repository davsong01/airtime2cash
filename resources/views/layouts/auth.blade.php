<!DOCTYPE html>
<html>
<!-- BEGIN: Head-->

@include('layouts.head')
<!-- END: Head-->

<!-- BEGIN: Body-->
<body class="vertical-layout vertical-menu-modern semi-dark-layout 1-column  navbar-sticky footer-static bg-full-screen-image  blank-page blank-page" data-open="click" data-menu="vertical-menu-modern" data-col="1-column" data-layout="semi-dark-layout">
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <!-- login page start -->
                <section id="auth-login" class="row flexbox-container">
                    <div class="col-xl-8 col-11">
                        <div class="card bg-authentication mb-0">
                            <div class="row m-1">
                                <a href="/" class="app-brand-link gap-2" style="margin: auto !important;">
                                    <span class="app-brand-logo demo">
                                        <img style="object-fit: contain;max-width: 300px;" src="{{ asset(getSettings()?->logo ?: 'app-assets/images/logo/logo.png') }}" height="100px">
                                    </span>
                                </a>
                                @yield('body')
                            </div>
                        </div>
                    </div>
                </section>
                <!-- login page ends -->

            </div>
        </div>
    </div>
</body>
@include('layouts.chat')
@include('layouts.footer')

</body>
<!-- END: Body-->

</html>
