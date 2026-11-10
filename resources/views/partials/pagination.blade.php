@if ($paginator->hasPages())
<nav class="pager" aria-label="الصفحات">
    @if ($paginator->onFirstPage())
        <span class="off">السابق</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
    @endif
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="off">…</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="on num" aria-current="page">{{ $page }}</span>
                @else
                    <a class="num" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
    @else
        <span class="off">التالي</span>
    @endif
</nav>
@endif
