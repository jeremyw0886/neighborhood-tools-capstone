<?php
/**
 * Admin — Reports with neighborhood statistics from neighborhood_summary_fast_v.
 *
 * Variables from AdminController::reports():
 *   $neighborhoods  array   Rows from Neighborhood::getSummaryList()
 *   $totalCount     int     Total neighborhoods
 *   $page           int     Current page (1-based)
 *   $totalPages     int     Total pages
 *   $perPage        int     Results per page (12)
 *
 * Each neighborhood row contains:
 *   id_nbh, neighborhood_name_nbh, city_name_nbh, state_code_sta,
 *   total_members, active_members, verified_members,
 *   total_tools, available_tools, active_borrows,
 *   completed_borrows_30d, upcoming_events, zip_codes, refreshed_at
 *
 * Shared data:
 *   $currentPage  string
 *   $backUrl      string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static fn(int $pageNum): string =>
    '/admin/reports' . ($pageNum > 1 ? '?page=' . $pageNum : '');
?>

<section aria-labelledby="admin-reports-heading">

  <header>
    <h1 id="admin-reports-heading">
      <i class="fa-solid fa-chart-bar" aria-hidden="true"></i>
      Reports
    </h1>
    <p>Neighborhood statistics and community health metrics.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <section aria-labelledby="neighborhood-stats-heading">
    <h2 id="neighborhood-stats-heading">
      <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
      Neighborhood Summary
    </h2>

    <div aria-live="polite" aria-atomic="true">
      <?php if ($totalCount > 0): ?>
        <p>
          Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
          <strong><?= number_format($totalCount) ?></strong>
          neighborhood<?= $totalCount !== 1 ? 's' : '' ?>
        </p>
      <?php endif; ?>
    </div>

    <?php if (!empty($neighborhoods)): ?>

      <table>
        <thead>
          <tr>
            <th scope="col">Neighborhood</th>
            <th scope="col">Members</th>
            <th scope="col">Tools</th>
            <th scope="col">Borrows</th>
            <th scope="col">Events</th>
            <th scope="col">ZIP Codes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($neighborhoods as $nbh):
            $active  = (int) $nbh['active_members'];
            $total   = (int) $nbh['total_members'];
            $avail   = (int) $nbh['available_tools'];
            $tools   = (int) $nbh['total_tools'];
            $borrows = (int) $nbh['active_borrows'];
          ?>
            <tr<?= $borrows > 0 ? ' data-has-activity' : '' ?>>
              <td>
                <strong><?= htmlspecialchars($nbh['neighborhood_name_nbh']) ?></strong>
                <small><?= htmlspecialchars($nbh['city_name_nbh']) ?>, <?= htmlspecialchars($nbh['state_code_sta']) ?></small>
              </td>
              <td>
                <span><?= number_format($active) ?></span>
                <small>of <?= number_format($total) ?> total</small>
              </td>
              <td>
                <span><?= number_format($avail) ?></span>
                <small>of <?= number_format($tools) ?> listed</small>
              </td>
              <td>
                <span><?= number_format($borrows) ?></span>
                <small><?= number_format((int) $nbh['completed_borrows_30d']) ?> completed (30d)</small>
              </td>
              <td>
                <?php if ((int) $nbh['upcoming_events'] > 0): ?>
                  <span><?= number_format((int) $nbh['upcoming_events']) ?></span>
                <?php else: ?>
                  <span>0</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($nbh['zip_codes'] !== null): ?>
                  <span><?= htmlspecialchars($nbh['zip_codes']) ?></span>
                <?php else: ?>
                  <span>—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if (!empty($neighborhoods[0]['refreshed_at'])): ?>
        <p data-cache-note>
          Data refreshed
          <time datetime="<?= htmlspecialchars($neighborhoods[0]['refreshed_at']) ?>">
            <?= htmlspecialchars(date('M j, Y g:i A', strtotime($neighborhoods[0]['refreshed_at']))) ?>
          </time>
        </p>
      <?php endif; ?>

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

      <section aria-label="No neighborhoods">
        <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
        <h3>No Neighborhood Data</h3>
        <p>No neighborhood statistics are available yet.</p>
        <a href="/admin" role="button">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
        </a>
      </section>

    <?php endif; ?>
  </section>

</section>
