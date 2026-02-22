<?php
/**
 * Admin — Tool management with analytics from tool_statistics_fast_v.
 *
 * Variables from AdminController::tools():
 *   $tools       array   Rows from Tool::getAdminList() via tool_statistics_fast_v
 *   $totalCount  int     Total tools in the system
 *   $page        int     Current page (1-based)
 *   $totalPages  int     Total pages
 *   $perPage     int     Results per page (12)
 *
 * Each tool row contains:
 *   id_tol, tool_name_tol, owner_id, owner_name, tool_condition,
 *   rental_fee_tol, estimated_value_tol, created_at_tol,
 *   avg_rating, rating_count, five_star_count,
 *   total_borrows, completed_borrows, cancelled_borrows, denied_borrows,
 *   total_hours_borrowed, last_borrowed_at,
 *   incident_count, refreshed_at
 *
 * Shared data:
 *   $currentPage  string
 *   $backUrl      string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static fn(int $pageNum): string =>
    '/admin/tools' . ($pageNum > 1 ? '?page=' . $pageNum : '');
?>

<section aria-labelledby="admin-tools-heading">

  <header>
    <h1 id="admin-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Manage Tools
    </h1>
    <p>Platform-wide tool listings with borrow statistics, ratings, and incident counts.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        tool<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tools)): ?>

    <table>
      <thead>
        <tr>
          <th scope="col">Tool</th>
          <th scope="col">Owner</th>
          <th scope="col">Condition</th>
          <th scope="col">Fee</th>
          <th scope="col">Rating</th>
          <th scope="col">Borrows</th>
          <th scope="col">Incidents</th>
          <th scope="col">Listed</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tools as $tool):
          $incidents = (int) $tool['incident_count'];
        ?>
          <tr<?= $incidents > 0 ? ' data-has-incidents' : '' ?>>
            <td>
              <a href="/tools/<?= (int) $tool['id_tol'] ?>">
                <?= htmlspecialchars($tool['tool_name_tol']) ?>
              </a>
              <small>$<?= number_format((float) $tool['rental_fee_tol'], 2) ?>/day</small>
            </td>
            <td>
              <a href="/profile/<?= (int) $tool['owner_id'] ?>">
                <?= htmlspecialchars($tool['owner_name']) ?>
              </a>
            </td>
            <td>
              <span data-condition="<?= htmlspecialchars($tool['tool_condition']) ?>">
                <?= htmlspecialchars(ucfirst($tool['tool_condition'])) ?>
              </span>
            </td>
            <td>$<?= number_format((float) $tool['rental_fee_tol'], 2) ?></td>
            <td>
              <?php if ((int) $tool['rating_count'] > 0): ?>
                <span><?= htmlspecialchars($tool['avg_rating']) ?></span>
                <small>(<?= number_format((int) $tool['rating_count']) ?>)</small>
              <?php else: ?>
                <span>—</span>
              <?php endif; ?>
            </td>
            <td>
              <span><?= number_format((int) $tool['completed_borrows']) ?></span>
              <small>of <?= number_format((int) $tool['total_borrows']) ?></small>
            </td>
            <td>
              <?php if ($incidents > 0): ?>
                <span data-warning><?= $incidents ?></span>
              <?php else: ?>
                <span>0</span>
              <?php endif; ?>
            </td>
            <td>
              <time datetime="<?= htmlspecialchars($tool['created_at_tol']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($tool['created_at_tol']))) ?>
              </time>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

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
              <span aria-disabled="true" aria-label="No previous page">
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
              <li><span aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li>
              <?php if ($i === $page): ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-current="page"
                   aria-label="Page <?= $i ?>, current page"><?= $i ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
            <li>
              <a href="<?= $paginationUrl($totalPages) ?>"
                 aria-label="Go to page <?= $totalPages ?>"><?= $totalPages ?></a>
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
              <span aria-disabled="true" aria-label="No next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>

    <section aria-label="No tools">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Tools Listed</h2>
      <p>No tools have been listed on the platform yet.</p>
      <a href="/admin" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php endif; ?>

</section>
