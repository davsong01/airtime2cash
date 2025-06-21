<?php 
    use Illuminate\Support\Facades\Auth;
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="{{ getSettings()->seo_description }}">
    <meta name="keywords" content="@yield('keywords')">
    <meta name="author" content="{{ config('app.name')}}">
    <title>{{config('app.name')}} - {{ getSettings()->seo_title }}</title>
    <link rel="apple-touch-icon" href="{{ asset(getSettings()->favicon) }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(getSettings()->favicon) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/fontawesome.min.css" integrity="" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/charts/apexcharts.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/extensions/dragula.min.css') }}">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/colors.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/components.css') }}">
    <script src="https://www.google.com/recaptcha/api.js?render=@yield('CAPTCHA_SITEKEY')"></script>
    @include('layouts/css/main_menu_css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/themes/dark-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/themes/semi-dark-layout.css') }}">
    <!-- END: Theme CSS-->

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css/pages/dashboard-analytics.css') }}">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/style.css') }}">
    <!-- END: Custom CSS-->
    <style>
        .btn-group{
            display: none !important;
        }

        .table th, .table td {
            padding: 0.9rem 1rem !important;
        }

        .mb-1, .my-1 {
            margin-bottom: 2px !important;
        }
        .bx {
            font-size: 15px !important;
        }
        label {
            text-transform: none !important;
            font-weight: 400  !important;
        }
        .btn i {
            top: 0px !important;
        }

        textarea.form-control {
            height: 105px !important;
            padding: 10px !important;
        }

        .red{
            color:red
        }

        .green{
            color:green
        }

        .svg svg{
            fill: {{ getSettings()->menu_text_color }};
        }

        .captcha {
            width: 100%;
            display: block;
            margin-bottom: 10px;
        }

        .captcha input {
            width: 100%;
            display: block;
            margin-top: 10px;
            padding: 10px;
            font-size: 16px;
        }

        .whatsapp_float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 80px;
            right: 40px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 3px #999;
            z-index: 100;
        }

        .whatsapp-icon {
            margin-top: 16px;
        }

      /* for mobile */
        @media screen and (max-width: 767px) {
            .whatsapp-icon {
                margin-top: 10px;
            }

            .whatsapp_float {
                width: 40px;
                height: 40px;
                bottom: 100px;
                right: 31px;
                font-size: 22px;
            }
        }

    </style>
    @yield('page-css')
    @if(Auth::check() && auth()->user()->type == 'customer')
    {{-- Google ads --}}
    {!! getSettings()->google_ad_code !!}
    @endif
</head>