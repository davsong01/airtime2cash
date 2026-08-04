<!-- Menu -->

<aside id="layout-menu" class="layout-menu menu-vertical menu">
  @php
    $dashboardLogo = getSettings()->dashboard_logo ?? getSettings()->logo ?? null;
  @endphp
  <div class="app-brand demo">
    <a href="/" class="app-brand-link">
      <span class="app-brand-logo demo">
        <div class="brand-logo">
          @if($dashboardLogo)
            <img style="max-height: 70px;text-align: center;margin: auto;max-width: 150px;object-fit: contain;" src="{{ asset($dashboardLogo) }}" />
          @endif
          <h2 class="brand-text mb-0"></h2>
        </div>
      </span>
      <span class="app-brand-text demo menu-text fw-bold ms-2"></span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" aria-label="Collapse sidebar" title="Collapse sidebar">
      <i class="icon-base bx bx-chevron-left"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @include('shared.customer-menu-items', ['variant' => 'sneat'])
  </ul>
</aside>
