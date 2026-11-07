@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
        <p>Showing <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span> of <span class="font-medium text-slate-700">{{ $paginator->total() }}</span></p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-secondary opacity-50" aria-disabled="true"><x-icon name="chevron-left" class="size-3.5" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-secondary" aria-label="Previous page"><x-icon name="chevron-left" class="size-3.5" /></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm btn-primary hidden sm:inline-flex" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm btn-ghost hidden sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-secondary" aria-label="Next page"><x-icon name="chevron-right" class="size-3.5" /></a>
            @else
                <span class="btn btn-sm btn-secondary opacity-50" aria-disabled="true"><x-icon name="chevron-right" class="size-3.5" /></span>
            @endif
        </div>
    </nav>
@endif
