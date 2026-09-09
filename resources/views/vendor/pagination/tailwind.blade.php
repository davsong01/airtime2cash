<style>
.custom-pagination .page-link {
    width: 38px;
    height: 38px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease-in-out;
}

.custom-pagination .page-link:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.custom-pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(13, 110, 253, 0.3);
}

.custom-pagination .page-item.disabled .page-link {
    opacity: 0.4;
    background-color: transparent;
}
</style>
@if ($paginator->hasPages())
<div class="d-flex justify-content-center my-4">
    <nav aria-label="Pagination Navigation">
        <ul class="pagination custom-pagination gap-1 mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-chevron-left fs-5"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link rounded-circle d-flex align-items-center justify-content-center"
                       href="{{ $paginator->previousPageUrl() }}"
                       rel="prev"
                       aria-label="Previous">
                        <i class="bx bx-chevron-left fs-5"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link rounded-circle border-0 bg-transparent">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link rounded-circle fw-semibold">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link rounded-circle" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link rounded-circle d-flex align-items-center justify-content-center"
                       href="{{ $paginator->nextPageUrl() }}"
                       rel="next"
                       aria-label="Next">
                        <i class="bx bx-chevron-right fs-5"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-chevron-right fs-5"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
</div>
@endif
