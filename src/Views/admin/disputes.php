<?php
/**
 * Admin — Open dispute listing with pagination.
 *
 * Variables from DisputeController::index():
 *   $disputes    array   Rows from Dispute::getAll() via open_dispute_v
 *   $totalCount  int     Total open disputes
 *   $page        int     Current page (1-based)
 *   $totalPages  int     Total pages
 *   $perPage     int     Results per page (12)
 *
 * Each dispute row contains:
 *   id_dsp, subject_text_dsp, created_at_dsp, days_open,
 *   reporter_id, reporter_name, reporter_email,
 *   id_bor_dsp (borrow ID), tool_name_tol,
 *   borrower_id, borrower_name, lender_id, lender_name,
 *   message_count, last_message_at, related_incidents,
 *   deposit_amount, deposit_status
 *
 * Shared data:
 *   $currentPage  string
 *   $backUrl      string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath     = '/admin/disputes';
$filterParams = [];

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
?>

<section aria-labelledby="admin-disputes-heading">

  <header>
    <h1 id="admin-disputes-heading">
      <i class="fa-solid fa-gavel" aria-hidden="true"></i>
      Manage Disputes
    </h1>
    <p>Review and resolve open disputes between members.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        open dispute<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($disputes)): ?>

    <div role="list">
      <?php foreach ($disputes as $dispute):
        $daysOpen = (int) $dispute['days_open'];
        $urgency  = $urgencyLabel($daysOpen);
      ?>
        <article role="listitem" data-urgency="<?= $urgency ?>">
          <header>
            <h2>
              <a href="/disputes/<?= (int) $dispute['id_dsp'] ?>">
                <?= htmlspecialchars($dispute['subject_text_dsp']) ?>
              </a>
            </h2>
            <span data-urgency="<?= $urgency ?>">
              <?= $daysOpen ?> day<?= $daysOpen !== 1 ? 's' : '' ?> open
            </span>
          </header>

          <dl>
            <div>
              <dt>Reporter</dt>
              <dd>
                <a href="/profile/<?= (int) $dispute['reporter_id'] ?>">
                  <?= htmlspecialchars($dispute['reporter_name']) ?>
                </a>
              </dd>
            </div>
            <div>
              <dt>Tool</dt>
              <dd><?= htmlspecialchars($dispute['tool_name_tol']) ?></dd>
            </div>
            <div>
              <dt>Borrower</dt>
              <dd>
                <a href="/profile/<?= (int) $dispute['borrower_id'] ?>">
                  <?= htmlspecialchars($dispute['borrower_name']) ?>
                </a>
              </dd>
            </div>
            <div>
              <dt>Lender</dt>
              <dd>
                <a href="/profile/<?= (int) $dispute['lender_id'] ?>">
                  <?= htmlspecialchars($dispute['lender_name']) ?>
                </a>
              </dd>
            </div>
            <div>
              <dt>Messages</dt>
              <dd><?= (int) $dispute['message_count'] ?></dd>
            </div>
            <?php if ((int) $dispute['related_incidents'] > 0): ?>
              <div>
                <dt>Incidents</dt>
                <dd data-warning><?= (int) $dispute['related_incidents'] ?></dd>
              </div>
            <?php endif; ?>
            <?php if ($dispute['deposit_amount'] !== null): ?>
              <div>
                <dt>Deposit</dt>
                <dd>$<?= number_format((float) $dispute['deposit_amount'], 2) ?> (<?= htmlspecialchars($dispute['deposit_status']) ?>)</dd>
              </div>
            <?php endif; ?>
          </dl>

          <footer>
            <div>
              <time datetime="<?= htmlspecialchars($dispute['created_at_dsp']) ?>">
                Filed <?= htmlspecialchars(date('M j, Y', strtotime($dispute['created_at_dsp']))) ?>
              </time>
              <?php if ($dispute['last_message_at'] !== null): ?>
                <span>
                  Last activity <?= htmlspecialchars(date('M j, Y', strtotime($dispute['last_message_at']))) ?>
                </span>
              <?php endif; ?>
            </div>
            <a href="/disputes/<?= (int) $dispute['id_dsp'] ?>">
              View Details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No disputes">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Open Disputes</h2>
      <p>All disputes have been resolved. The community is in good standing.</p>
      <a href="/admin" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php endif; ?>

</section>
