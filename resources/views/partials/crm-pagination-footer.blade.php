@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    $pageName = $pageName ?? 'page';
@endphp
<div class="crm-table-footer">
    <div class="crm-table-footer-meta">
        @if($paginator->total() > 0)
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong>
        @else
            No results
        @endif
    </div>
    @if($paginator->hasPages())
        <div class="crm-table-footer-pages">
            <span class="crm-page-badge">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            <ul class="crm-pagination">
                @if($paginator->onFirstPage())
                    <li class="is-disabled"><span aria-hidden="true">&laquo;</span></li>
                @else
                    <li><a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous page">&laquo;</a></li>
                @endif

                @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if($page == $paginator->currentPage())
                        <li class="is-active"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <li><a href="{{ $paginator->nextPageUrl() }}" aria-label="Next page">&raquo;</a></li>
                @else
                    <li class="is-disabled"><span aria-hidden="true">&raquo;</span></li>
                @endif
            </ul>
        </div>
    @endif
</div>
