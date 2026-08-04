@auth
    @if(auth()->user()->type === 'customer')
        @php
            $mobileNavItems = customerMobileNavItems();
            $mobileNavAccent = getSettings()?->active_color ?: '#0B7D4F';
        @endphp

        <nav
            class="customer-mobile-nav"
            style="--customer-mobile-accent: {{ $mobileNavAccent }};"
            aria-label="Customer quick navigation">
            <div class="customer-mobile-nav__inner">
                @foreach($mobileNavItems as $item)
                    @php($isActive = menuItemIsActive($item['active_paths']))
                    <a
                        href="{{ $item['href'] }}"
                        class="customer-mobile-nav__item {{ $isActive ? 'is-active' : '' }}"
                        @if($isActive) aria-current="page" @endif>
                        <span class="customer-mobile-nav__icon">
                            <i class="{{ menuIconClass($item['icon_key']) }}" aria-hidden="true"></i>
                        </span>
                        <span class="customer-mobile-nav__label">{{ $item['label'] }}</span>
                        @if($isActive)
                            <span class="customer-mobile-nav__dot" aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </nav>
    @endif
@endauth
