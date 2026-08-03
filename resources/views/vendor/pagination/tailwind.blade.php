@if ($paginator->hasPages())
<div class="row">
    <div class="mt-5 col-12">
        <nav>
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item previous disabled"><a class="page-link" href="#">
                            <i class="bx bx-chevron-left"></i>
                        </a></li>
                @else
                    <li class="page-item previous"><a class="page-link" href="{{ $paginator->previousPageUrl() }}">
                            <i class="bx bx-chevron-left"></i>
                        </a></li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-current="page"><a class="page-link"
                                href="#">{{ $element }}</a></li>
                        {{-- <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li> --}}
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><a class="page-link"
                                        href="{{ $url }}">{{ $page }}</a></li>
                            @else
                                <li class="page-item" aria-current="page"><a class="page-link"
                                        href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item active" aria-current="page"><a class="page-link"
                            href="{{ $paginator->nextPageUrl() }}">1</a></li>
                    {{-- <li>
                                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                                    aria-label="@lang('pagination.next')">&rsaquo;</a>
                            </li> --}}
                @else
                    <li class="page-item disabled"><a class="page-link" href="#">
                            <i class="bx bx-chevron-right"></i>
                        </a></li>
                @endif
            </ul>
        </nav>
    </div>
</div>
@endif
