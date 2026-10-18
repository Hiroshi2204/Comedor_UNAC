@if ($paginator->total() > 0)
<div class="paginacion">
    <span>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ number_format($paginator->total()) }}</span>
    @if ($paginator->hasPages())
    <nav aria-label="Paginación">
        @if ($paginator->onFirstPage())<span class="pg off">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="pg off">…</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="pg act-pg" aria-current="page">{{ $page }}</span>
                    @else<a href="{{ $url }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>@else<span class="pg off">›</span>@endif
    </nav>
    @endif
</div>
@endif
