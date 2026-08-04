@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $eyebrow = $eyebrow ?? null;
    $actions = $actions ?? [];
@endphp

<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-body p-4 p-lg-5 position-relative">
        <div class="row align-items-end g-4 position-relative">
            <div class="col-lg-8">
                @if($eyebrow)
                    <span class="badge bg-label-primary text-uppercase fw-semibold mb-3">{{ $eyebrow }}</span>
                @endif
                <h3 class="mb-2">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-muted mb-0" style="max-width: 720px">{{ $subtitle }}</p>
                @endif
            </div>

            @if(!empty($actions))
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-2 align-items-stretch align-items-lg-end">
                        @foreach($actions as $action)
                            <a href="{{ $action['href'] }}" class="btn {{ $action['class'] ?? 'btn-primary' }} btn-lg" @if(!empty($action['target'])) target="{{ $action['target'] }}" @endif>
                                @if(!empty($action['icon']))
                                    <i class="{{ $action['icon'] }} me-2"></i>
                                @endif
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
