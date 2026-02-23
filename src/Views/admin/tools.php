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

$basePath     = '/admin/tools';
$filterParams = [];
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
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        tool<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tools)): ?>

    <table>
      <caption class="visually-hidden">Listed tools and their status</caption>
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

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

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
