<?php
/**
 * Admin — Open incident listing with urgency badges and pagination.
 *
 * Variables from AdminController::incidents():
 *   $incidents   array   Rows from Incident::getOpen() via open_incident_v
 *   $totalCount  int     Total open incidents
 *   $page        int     Current page (1-based)
 *   $totalPages  int     Total pages
 *   $perPage     int     Results per page (12)
 *
 * Each incident row contains:
 *   id_irt, subject_irt, description_irt, incident_type, incident_occurred_at_irt,
 *   created_at_irt, days_open, is_reported_within_deadline_irt,
 *   estimated_damage_amount_irt, reporter_id, reporter_name,
 *   id_bor_irt, tool_name_tol, borrower_id, borrower_name,
 *   lender_id, lender_name, related_disputes,
 *   deposit_amount, deposit_status
 *
 * Shared data:
 *   $currentPage  string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static fn(int $pageNum): string =>
    '/admin/incidents' . ($pageNum > 1 ? '?page=' . $pageNum : '');

$urgencyLabel = static function (int $daysOpen): string {
    if ($daysOpen >= 14) {
        return 'critical';
    }
    if ($daysOpen >= 7) {
        return 'high';
    }
    if ($daysOpen >= 3) {
        return 'moderate';
    }
    return 'new';
};

$typeIcons = [
    'damage'             => 'fa-hammer',
    'theft'              => 'fa-mask',
    'loss'               => 'fa-box-open',
    'injury'             => 'fa-kit-medical',
    'late_return'        => 'fa-clock',
    'condition_dispute'  => 'fa-scale-unbalanced',
    'other'              => 'fa-circle-question',
];
?>

<section aria-labelledby="admin-incidents-heading">

  <header>
    <h1 id="admin-incidents-heading">
      <i class="fa-solid fa-flag" aria-hidden="true"></i>
      Manage Incidents
    </h1>
    <p>Review and resolve open incident reports.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        open incident<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($incidents)): ?>

    <div role="list">
      <?php foreach ($incidents as $incident):
        $daysOpen = (int) $incident['days_open'];
        $urgency  = $urgencyLabel($daysOpen);
        $type     = $incident['incident_type'];
        $icon     = $typeIcons[$type] ?? 'fa-circle-question';
        $withinDeadline = (bool) $incident['is_reported_within_deadline_irt'];
      ?>
        <article role="listitem" data-urgency="<?= $urgency ?>">
          <header>
            <h2>
              <a href="/incidents/<?= (int) $incident['id_irt'] ?>">
                <?= htmlspecialchars($incident['subject_irt']) ?>
              </a>
            </h2>
            <span data-urgency="<?= $urgency ?>">
              <?= $daysOpen ?> day<?= $daysOpen !== 1 ? 's' : '' ?> open
            </span>
          </header>

          <dl>
            <div>
              <dt>Type</dt>
              <dd>
                <span data-incident-type="<?= htmlspecialchars($type) ?>">
                  <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
                  <?= htmlspecialchars(ucwords(str_replace('_', ' ', $type))) ?>
                </span>
              </dd>
            </div>
            <div>
              <dt>Reporter</dt>
              <dd>
                <a href="/profile/<?= (int) $incident['reporter_id'] ?>">
                  <?= htmlspecialchars($incident['reporter_name']) ?>
                </a>
              </dd>
            </div>
            <div>
              <dt>Tool</dt>
              <dd><?= htmlspecialchars($incident['tool_name_tol']) ?></dd>
            </div>
            <div>
              <dt>Borrower</dt>
              <dd>
                <a href="/profile/<?= (int) $incident['borrower_id'] ?>">
                  <?= htmlspecialchars($incident['borrower_name']) ?>
                </a>
              </dd>
            </div>
            <div>
              <dt>Lender</dt>
              <dd>
                <a href="/profile/<?= (int) $incident['lender_id'] ?>">
                  <?= htmlspecialchars($incident['lender_name']) ?>
                </a>
              </dd>
            </div>
            <?php if ($incident['estimated_damage_amount_irt'] !== null): ?>
              <div>
                <dt>Damage Est.</dt>
                <dd>$<?= number_format((float) $incident['estimated_damage_amount_irt'], 2) ?></dd>
              </div>
            <?php endif; ?>
            <?php if ((int) $incident['related_disputes'] > 0): ?>
              <div>
                <dt>Disputes</dt>
                <dd data-warning><?= (int) $incident['related_disputes'] ?></dd>
              </div>
            <?php endif; ?>
            <?php if ($incident['deposit_amount'] !== null): ?>
              <div>
                <dt>Deposit</dt>
                <dd>$<?= number_format((float) $incident['deposit_amount'], 2) ?> (<?= htmlspecialchars($incident['deposit_status']) ?>)</dd>
              </div>
            <?php endif; ?>
            <div>
              <dt>48h Deadline</dt>
              <dd>
                <?php if ($withinDeadline): ?>
                  <span data-deadline="met">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Met
                  </span>
                <?php else: ?>
                  <span data-deadline="missed">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Missed
                  </span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>

          <footer>
            <div>
              <time datetime="<?= htmlspecialchars($incident['created_at_irt']) ?>">
                Filed <?= htmlspecialchars(date('M j, Y', strtotime($incident['created_at_irt']))) ?>
              </time>
              <time datetime="<?= htmlspecialchars($incident['incident_occurred_at_irt']) ?>">
                Occurred <?= htmlspecialchars(date('M j, Y', strtotime($incident['incident_occurred_at_irt']))) ?>
              </time>
            </div>
            <a href="/incidents/<?= (int) $incident['id_irt'] ?>">
              View Details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </footer>
        </article>
      <?php endforeach; ?>
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
                   aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">&hellip;</span></li>
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

    <section aria-label="No incidents">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Open Incidents</h2>
      <p>All incidents have been resolved. The community is in good standing.</p>
      <a href="/admin" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php endif; ?>

</section>
