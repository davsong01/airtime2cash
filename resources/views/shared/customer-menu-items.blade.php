@php
    $menu = $menu ?? customerMenuData();
    $variant = $variant ?? 'legacy';
    $stats = $menu['stats'] ?? [];
    $sections = $menu['sections'] ?? [];
@endphp

@if($variant === 'sneat')
    @foreach($sections as $section)
        <li class="menu-header small">
            <span class="menu-header-text">{{ $section['label'] }}</span>
        </li>

        @foreach($section['items'] as $item)
            @php
                $isActive = menuItemIsActive($item['active_paths'] ?? []);
                $isLogout = ($item['type'] ?? null) === 'logout';
                $target = $item['target'] ?? null;
            @endphp
            <li class="menu-item {{ $isActive ? 'active' : '' }}">
                <a
                    href="{{ $item['href'] }}"
                    class="menu-link"
                    @if($target) target="{{ $target }}" @endif
                    @if($isLogout) onclick="event.preventDefault(); document.getElementById('logout-form').submit();" @endif
                >
                    <i class="{{ menuIconClass($item['modern_icon_key'] ?? $item['icon_key'] ?? 'circle', 'sneat') }}"></i>
                    <div data-i18n="{{ $item['label'] }}">{{ $item['label'] }}</div>
                </a>
            </li>

            @if($isLogout)
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endif
        @endforeach
    @endforeach
@else
    @if(!empty($stats))
        @foreach($stats as $stat)
            <li class="navigation-header customer-details">
                <span>{{ $stat['label'] }}</span><br>
                <strong>{{ $stat['value'] }}</strong>
            </li>
        @endforeach
    @endif

    @foreach($sections as $section)
        <li class="block-header-color navigation-header">
            <span>{{ $section['label'] }}</span>
        </li>

        @foreach($section['items'] as $item)
            @if(!empty($item['modern_only']))
                @continue
            @endif
            @php
                $isActive = menuItemIsActive($item['active_paths'] ?? []);
                $isLogout = ($item['type'] ?? null) === 'logout';
                $target = $item['target'] ?? null;
            @endphp
            <li class="{{ $isActive ? 'active' : '' }} svg">
                <a
                    href="{{ $item['href'] }}"
                    @if($target) target="{{ $target }}" @endif
                    @if($isLogout) onclick="event.preventDefault(); document.getElementById('logout-form').submit();" @endif
                >
                    @if(!empty($item['icon_html']))
                        {!! $item['icon_html'] !!}
                    @else
                        <i class="{{ menuIconClass($item['icon_key'] ?? 'circle', 'legacy') }}"></i>
                    @endif
                    <span class="menu-title"> &nbsp;{{ $item['label'] }}</span>
                </a>
                @if($isLogout)
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endif
            </li>
        @endforeach
    @endforeach
@endif
