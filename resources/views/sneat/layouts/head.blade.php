<!doctype html>
@php
  $settings = getSettings();
  $appName = config('app.name');
  $favicon = $settings->favicon ?? 'modern-assets/img/favicon/favicon.ico';
  $seoDescription = $settings->seo_description ?? $appName;
  $seoTitle = $settings->seo_title ?? 'Dashboard';
  $seoKeywords = $settings->seo_keywords ?? $appName;
  $themeColor = $settings->primary_color ?? '#ffffff';
@endphp
<html
  lang="en"
  class="layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr"
  data-skin="bordered"
  data-assets-path="{{ rtrim(asset('modern-assets'), '/') }}/"
  data-template="vertical-menu-template-bordered"
  data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="description" content="{{ $seoDescription }}" />
  <meta name="keywords" content="@yield('keywords', $seoKeywords)" />
  <meta name="author" content="{{ $appName }}" />
  <meta name="theme-color" content="{{ $themeColor }}" />
  <meta name="msapplication-TileColor" content="{{ $themeColor }}" />
  <meta name="msapplication-TileImage" content="{{ asset($favicon) }}" />
  <meta property="og:title" content="@yield('title', config('app.name'))" />
  <meta property="og:description" content="{{ $seoDescription }}" />
  <meta property="og:site_name" content="{{ $appName }}" />
  <title>@yield('title', $appName . ' - ' . $seoTitle)</title>

  <link rel="apple-touch-icon" href="{{ asset($favicon) }}" />
  <link rel="shortcut icon" type="image/x-icon" href="{{ asset($favicon) }}" />
  <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}" />

  <link rel="stylesheet" href="{{ asset('modern-assets/vendor/fonts/iconify-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('modern-assets/vendor/css/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('modern-assets/css/demo.css') }}" />
  <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
  <link rel="stylesheet" href="{{ asset('modern-assets/vendor/libs/apex-charts/apex-charts.css') }}" />
  <link rel="stylesheet" href="{{ asset('modern-assets/css/customer-purchase.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/customer-mobile-nav.css') }}" />

  @yield('page-css')

  <script src="{{ asset('modern-assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('modern-assets/js/config.js') }}"></script>
</head>
<body>
