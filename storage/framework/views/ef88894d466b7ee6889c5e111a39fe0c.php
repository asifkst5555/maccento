<?php if($paginator->hasPages()): ?>
<?php
	$currentPage = (int) $paginator->currentPage();
	$hasLastPage = method_exists($paginator, 'lastPage');
	$lastPage = $hasLastPage ? (int) $paginator->lastPage() : null;
	$total = method_exists($paginator, 'total') ? (int) $paginator->total() : null;
	$from = method_exists($paginator, 'firstItem') ? (int) ($paginator->firstItem() ?? 0) : null;
	$to = method_exists($paginator, 'lastItem') ? (int) ($paginator->lastItem() ?? 0) : null;
?>

<div class="panel-pager" role="navigation" aria-label="Pagination Navigation">
	<?php if($total !== null && $total > 0 && $from !== null && $to !== null): ?>
	<p class="panel-pager-summary">Showing <?php echo e(number_format($from)); ?> to <?php echo e(number_format($to)); ?> of <?php echo e(number_format($total)); ?> results</p>
	<?php endif; ?>

	<div class="panel-pager-controls">
		<?php if($paginator->onFirstPage()): ?>
			<span class="panel-pager-btn is-disabled" aria-disabled="true">« Previous</span>
		<?php else: ?>
			<a class="panel-pager-btn" href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev">« Previous</a>
		<?php endif; ?>

		<?php if($hasLastPage && $lastPage !== null && $lastPage > 1): ?>
			<?php
				$startPage = max(1, $currentPage - 2);
				$endPage = min($lastPage, $currentPage + 2);
			?>

			<?php if($startPage > 1): ?>
				<a class="panel-pager-page" href="<?php echo e($paginator->url(1)); ?>">1</a>
				<?php if($startPage > 2): ?>
					<span class="panel-pager-ellipsis" aria-hidden="true">…</span>
				<?php endif; ?>
			<?php endif; ?>

			<?php for($page = $startPage; $page <= $endPage; $page++): ?>
				<?php if($page === $currentPage): ?>
					<span class="panel-pager-page is-active" aria-current="page"><?php echo e($page); ?></span>
				<?php else: ?>
					<a class="panel-pager-page" href="<?php echo e($paginator->url($page)); ?>"><?php echo e($page); ?></a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if($endPage < $lastPage): ?>
				<?php if($endPage < ($lastPage - 1)): ?>
					<span class="panel-pager-ellipsis" aria-hidden="true">…</span>
				<?php endif; ?>
				<a class="panel-pager-page" href="<?php echo e($paginator->url($lastPage)); ?>"><?php echo e($lastPage); ?></a>
			<?php endif; ?>
		<?php endif; ?>

		<?php if($paginator->hasMorePages()): ?>
			<a class="panel-pager-btn" href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next">Next »</a>
		<?php else: ?>
			<span class="panel-pager-btn is-disabled" aria-disabled="true">Next »</span>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>
<?php /**PATH /home/asifk/projects/maccento/resources/views/components/panel-pagination.blade.php ENDPATH**/ ?>