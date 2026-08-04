<!doctype html>
<html lang="en" class=" layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-skin="default" data-assets-path="{{ asset('modern-assets') }}/" data-template="vertical-menu-template" data-bs-theme="light">
    @include('sneat.layouts.head')
    <body>
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                @include('sneat.layouts.menu')

                <div class="menu-mobile-toggler d-xl-none rounded-1">
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                        <i class="bx bx-menu icon-base"></i>
                        <i class="bx bx-chevron-right icon-base"></i>
                    </a>
                </div>

                <div class="layout-page">
                    @include('sneat.layouts.navbar')

                    <div class="content-wrapper">
                        <div class="container-xxl flex-grow-1 container-p-y">
                            @yield('content')
                        </div>
                        <div class="content-backdrop fade"></div>
                    </div>
                </div>
            </div>

            <div class="layout-overlay layout-menu-toggle"></div>
            <div class="drag-target"></div>
        </div>

        @include('sneat.layouts.footer')
    </body>
</html>
