<style>
    .kips-pagination {
        display: inline-flex;
        gap: 6px;
        align-items: center;
        user-select: none;
    }
    .kips-pagination .page-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 4px;
        border: 1px solid var(--border, #334155);
        border-radius: 10px;
        background: rgba(30, 41, 59, 0.6);
        color: var(--text, #e2e8f0);
        text-decoration: none;
        font-size: 0.92rem;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .kips-pagination .page-item:hover:not(.disabled) {
        border-color: var(--accent, #38bdf8);
        color: var(--accent, #38bdf8);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.15);
        background: rgba(30, 41, 59, 0.8);
    }
    .kips-pagination .page-item.active {
        background: linear-gradient(135deg, var(--primary, #2563eb), #1d4ed8);
        border-color: var(--primary, #2563eb);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .kips-pagination .page-item.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: rgba(30, 41, 59, 0.3);
    }
    .kips-pagination .page-item svg {
        width: 20px;
        height: 20px;
    }
</style>

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="kips-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                <span class="page-link" aria-hidden="true">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-item" rel="prev" aria-label="@lang('pagination.previous')">
                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}" class="page-item" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-item" rel="next" aria-label="@lang('pagination.next')">
                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
            </a>
        @else
            <span class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <span class="page-link" aria-hidden="true">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                </span>
            </span>
        @endif
    </nav>
@endif
