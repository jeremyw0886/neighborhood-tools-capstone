<?php
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/disputes';

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

$sortLabels = [
    'created_at_dsp'  => 'Date Filed',
    'days_open'       => 'Days Open',
    'last_message_at' => 'Last Activity',
    'message_count'   => 'Messages',
];

$hasFilters = $urgency !== null;
?>

  <form method="get" action="/admin/disputes" aria-label="Filter and sort disputes" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort disputes</legend>

      <div>
        <label for="disputes-urgency">Urgency</label>
        <select id="disputes-urgency" name="urgency">
          <option value="">All Urgencies</option>
          <option value="critical"<?= $urgency === 'critical' ? ' selected' : '' ?>>Critical (14+ days)</option>
          <option value="high"<?= $urgency === 'high' ? ' selected' : '' ?>>High (7–13 days)</option>
          <option value="moderate"<?= $urgency === 'moderate' ? ' selected' : '' ?>>Moderate (3–6 days)</option>
          <option value="new"<?= $urgency === 'new' ? ' selected' : '' ?>>New (0–2 days)</option>
        </select>
      </div>

      <div>
        <label for="disputes-sort">Sort By</label>
        <select id="disputes-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="disputes-dir">Direction</label>
        <select id="disputes-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
      <?php if ($hasFilters): ?>
        <a href="<?= htmlspecialchars($basePath) ?>" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php endif; ?>
    </fieldset>
  </form>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>-<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        open dispute<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($disputes)): ?>

    <div role="list">
      <?php foreach ($disputes as $dispute):
        $daysOpen = (int) $dispute['days_open'];
        $urgencyLevel = $urgencyLabel($daysOpen);
      ?>
        <div role="listitem">
        <article data-urgency="<?= $urgencyLevel ?>">
          <header>
            <h2>
              <a href="/disputes/<?= (int) $dispute['id_dsp'] ?>">
                <?= htmlspecialchars($dispute['subject_text_dsp']) ?>
              </a>
            </h2>
            <span data-urgency="<?= $urgencyLevel ?>">
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
        </div>
      <?php endforeach; ?>
    </div>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No disputes">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Open Disputes</h2>
      <?php if ($urgency !== null): ?>
        <p>No disputes match the selected urgency level.</p>
        <a href="/admin/disputes" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php else: ?>
        <p>All disputes have been resolved. The community is in good standing.</p>
        <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary" data-back>
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>
