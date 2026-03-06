@if ($paginator->hasPages())
@php
	$currentPage = (int) $paginator->currentPage();
	$hasLastPage = method_exists($paginator, 'lastPage');
	$lastPage = $hasLastPage ? (int) $paginator->lastPage() : null;
	$total = method_exists($paginator, 'total') ? (int) $paginator->total() : null;
	$from = method_exists($paginator, 'firstItem') ? (int) ($paginator->firstItem() ?? 0) : null;
	$to = method_exists($paginator, 'lastItem') ? (int) ($paginator->lastItem() ?? 0) : null;
@endphp

<div class="panel-pager" role="navigation" aria-label="Pagination Navigation">
	@if($total !== null && $total > 0 && $from !== null && $to !== null)
	<p class="panel-pager-summary">Showing {{ number_format($from) }} to {{ number_format($to) }} of {{ number_format($total) }} results</p>
	@endif

	<div class="panel-pager-controls">
		@if($paginator->onFirstPage())
			<span class="panel-pager-btn is-disabled" aria-disabled="true">« Previous</span>
		@else
			<a class="panel-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">« Previous</a>
		@endif

		@if($hasLastPage && $lastPage !== null && $lastPage > 1)
			@php
				$startPage = max(1, $currentPage - 2);
				$endPage = min($lastPage, $currentPage + 2);
			@endphp

			@if($startPage > 1)
				<a class="panel-pager-page" href="{{ $paginator->url(1) }}">1</a>
				@if($startPage > 2)
					<span class="panel-pager-ellipsis" aria-hidden="true">…</span>
				@endif
			@endif

			@for($page = $startPage; $page <= $endPage; $page++)
				@if($page === $currentPage)
					<span class="panel-pager-page is-active" aria-current="page">{{ $page }}</span>
				@else
					<a class="panel-pager-page" href="{{ $paginator->url($page) }}">{{ $page }}</a>
				@endif
			@endfor

			@if($endPage < $lastPage)
				@if($endPage < ($lastPage - 1))
					<span class="panel-pager-ellipsis" aria-hidden="true">…</span>
				@endif
				<a class="panel-pager-page" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
			@endif
		@endif

		@if($paginator->hasMorePages())
			<a class="panel-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next »</a>
		@else
			<span class="panel-pager-btn is-disabled" aria-disabled="true">Next »</span>
		@endif
	</div>
</div>
@endif
