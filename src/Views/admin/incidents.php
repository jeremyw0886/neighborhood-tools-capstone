<?php
/**
 * Admin — Open incident listing with sorting, type/deadline filters, and urgency badges.
 *
 * Variables from AdminController::incidents():
 *   $incidents    array   Rows from Incident::getOpen() via open_incident_v
 *   $totalCount   int     Total open incidents matching filters
 *   $page         int     Current page (1-based)
 *   $totalPages   int     Total pages
 *   $perPage      int     Results per page (12)
 *   $type         ?string Active incident type filter, or null
 *   $deadlineMet  ?bool   Deadline filter: true = met, false = missed, null = all
 *   $sort         string  Active sort column
 *   $dir          string  Active sort direction (ASC|DESC)
 *   $filterParams array   Non-null filter params for pagination URLs
 *
 * Each incident row contains:
 *   id_irt, subject_irt, description_irt, incident_type, incident_occurred_at_irt,
 *   created_at_irt, days_open, is_reported_within_deadline_irt,
 *   estimated_damage_amount_irt, reporter_id, reporter_name,
 *   id_bor_irt, tool_name_tol, borrower_id, borrower_name,
 *   lender_id, lender_name, related_disputes,
 *   deposit_amount, deposit_status
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/incidents';

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

$allTypes = [
    'damage'            => 'Damage',
    'theft'             => 'Theft',
    'loss'              => 'Loss',
    'injury'            => 'Injury',
    'late_return'       => 'Late Return',
    'condition_dispute' => 'Condition Dispute',
    'other'             => 'Other',
];

$sortLabels = [
    'created_at_irt'              => 'Date Filed',
    'days_open'                   => 'Days Open',
    'incident_type'               => 'Type',
    'estimated_damage_amount_irt' => 'Damage Estimate',
];

$deadlineValue = match ($deadlineMet) {
    true    => 'met',
    false   => 'missed',
    default => '',
};
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

  <form method="get" action="/admin/incidents" role="search" aria-label="Filter and sort incidents" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort incidents</legend>

      <div>
        <label for="incidents-type">Type</label>
        <select id="incidents-type" name="type">
          <option value="">All Types</option>
          <?php foreach ($allTypes as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $type === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="incidents-deadline">48h Deadline</label>
        <select id="incidents-deadline" name="deadline">
          <option value="">All</option>
          <option value="met"<?= $deadlineValue === 'met' ? ' selected' : '' ?>>Met</option>
          <option value="missed"<?= $deadlineValue === 'missed' ? ' selected' : '' ?>>Missed</option>
        </select>
      </div>

      <div>
        <label for="incidents-sort">Sort By</label>
        <select id="incidents-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="incidents-dir">Direction</label>
        <select id="incidents-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
    </fieldset>
  </form>

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
        $incType  = $incident['incident_type'];
        $icon     = $typeIcons[$incType] ?? 'fa-circle-question';
        $withinDeadline = (bool) $incident['is_reported_within_deadline_irt'];
      ?>
        <article data-urgency="<?= $urgency ?>">
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
                <span data-incident-type="<?= htmlspecialchars($incType) ?>">
                  <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
                  <?= htmlspecialchars(ucwords(str_replace('_', ' ', $incType))) ?>
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

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No incidents">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Open Incidents</h2>
      <p>All incidents have been resolved. The community is in good standing.</p>
      <a href="<?= htmlspecialchars($backUrl) ?>" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
      </a>
    </section>

  <?php endif; ?>

</div>
</section>
