<?php
/**
 * Dashboard partial — bookmarked tools grid with pagination.
 *
 * Variables from ToolController::bookmarks():
 *
 * @var array   $bookmarks     Rows from Bookmark::getForUser() (tool-card compatible)
 * @var int     $totalCount    Total bookmarks for this user
 * @var int     $page          Current page number (1-based)
 * @var int     $totalPages    Total pages
 * @var int     $perPage       Results per page (12)
 * @var array   $bookmarkedIds Tool IDs on this page (all bookmarked)
 * @var ?string $bookmarkFlash Flash message
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static function (int $pageNum): string {
    return '/bookmarks?page=' . $pageNum;
};
?>

<?php if (!empty($bookmarkFlash)): ?>
  <p role="status" data-flash="success"><?= htmlspecialchars($bookmarkFlash) ?></p>
<?php endif; ?>

<div aria-live="polite" aria-atomic="true">
  <?php if ($totalCount > 0): ?>
    <p>
      Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
      <strong><?= number_format($totalCount) ?></strong>
      bookmark<?= $totalCount !== 1 ? 's' : '' ?>
    </p>
  <?php endif; ?>
</div>

<?php if (!empty($bookmarks)): ?>

  <div role="list">
    <?php $cardSizes = '(max-width: 600px) calc(100vw - 3rem), (max-width: 900px) calc(50vw - 2.25rem), 270px'; ?>
    <?php foreach ($bookmarks as $tool): ?>
      <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
    <?php endforeach; ?>
    <?php unset($cardSizes); ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav aria-label="Pagination">
      <ul>

        <?php if ($page > 1): ?>
          <li>
            <a href="<?= $paginationUrl($page - 1) ?>"
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
            <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li><span aria-hidden="true">…</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li>
            <?php if ($i === $page): ?>
              <a href="<?= $paginationUrl($i) ?>"
                 aria-current="page"
                 aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
            <?php else: ?>
              <a href="<?= $paginationUrl($i) ?>"
                 aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
            <?php endif; ?>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?>
            <li><span aria-hidden="true">…</span></li>
          <?php endif; ?>
          <li>
            <a href="<?= $paginationUrl($totalPages) ?>"
               aria-label="Go to page <?= htmlspecialchars((string) $totalPages) ?>"><?= htmlspecialchars((string) $totalPages) ?></a>
          </li>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <li>
            <a href="<?= $paginationUrl($page + 1) ?>"
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
  <?php endif; ?>

<?php else: ?>

  <section aria-label="No bookmarks">
    <i class="fa-solid fa-bookmark" aria-hidden="true"></i>
    <h2>No Bookmarks Yet</h2>
    <p>Browse tools and tap the bookmark icon to save them here.</p>
    <a href="/tools" role="button" data-intent="primary">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools
    </a>
  </section>

<?php endif; ?>
