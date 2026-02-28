<?php

declare(strict_types=1);

use App\Core\ViewHelper;

/**
 * @var string $basePath     URL path (e.g. '/admin/users')
 * @var array  $filterParams Current filter/sort state
 * @var int    $page         Current page number
 * @var int    $totalPages   Total number of pages
 * @var string $pageParam    Query-string key for page number (default 'page')
 */

if ($totalPages <= 1) {
    return;
}

$pageParam ??= 'page';

$url = static fn(int $pageNum): string =>
    ViewHelper::adminPaginationUrl($basePath, $pageNum, $filterParams, $pageParam);
?>

<nav aria-label="Pagination">
  <ul>

    <?php if ($page > 1): ?>
      <li>
        <a href="<?= $url($page - 1) ?>"
           aria-label="Go to previous page">
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
          <span>Previous</span>
        </a>
      </li>
    <?php else: ?>
      <li>
        <span aria-disabled="true">
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
          <span>Previous</span>
        </span>
      </li>
    <?php endif; ?>

    <?php
    $windowSize = 2;
    $startPage  = max(1, $page - $windowSize);
    $endPage    = min($totalPages, $page + $windowSize);

    if ($startPage > 1): ?>
      <li>
        <a href="<?= $url(1) ?>" aria-label="Go to page 1">1</a>
      </li>
      <?php if ($startPage > 2): ?>
        <li><span aria-hidden="true">&hellip;</span></li>
      <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
      <li>
        <?php if ($i === $page): ?>
          <a href="<?= $url($i) ?>"
             aria-current="page"
             aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
        <?php else: ?>
          <a href="<?= $url($i) ?>"
             aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
        <?php endif; ?>
      </li>
    <?php endfor; ?>

    <?php if ($endPage < $totalPages): ?>
      <?php if ($endPage < $totalPages - 1): ?>
        <li><span aria-hidden="true">&hellip;</span></li>
      <?php endif; ?>
      <li>
        <a href="<?= $url($totalPages) ?>"
           aria-label="Go to page <?= htmlspecialchars((string) $totalPages) ?>"><?= htmlspecialchars((string) $totalPages) ?></a>
      </li>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
      <li>
        <a href="<?= $url($page + 1) ?>"
           aria-label="Go to next page">
          <span>Next</span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </a>
      </li>
    <?php else: ?>
      <li>
        <span aria-disabled="true">
          <span>Next</span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </span>
      </li>
    <?php endif; ?>

  </ul>
</nav>
