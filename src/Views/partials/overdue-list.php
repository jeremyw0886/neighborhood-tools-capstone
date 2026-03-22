<?php
/**
 * Shared overdue items section for lender and borrower dashboards.
 *
 * @var array  $overdue     Overdue borrow rows
 * @var string $overdueRole 'lender' | 'borrower'
 */
?>
<section aria-label="Overdue items">
  <h2 data-urgent>
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    Overdue (<?= count($overdue) ?>)
  </h2>

  <ul data-card-list>
    <?php foreach ($overdue as $row): ?>
      <?php
        $daysOverdue  = (int) $row['days_overdue'];
        $hoursOverdue = (int) $row['hours_overdue'];
        $overdueLabel = $daysOverdue > 0
          ? $daysOverdue . ' day' . ($daysOverdue !== 1 ? 's' : '')
          : $hoursOverdue . ' hour' . ($hoursOverdue !== 1 ? 's' : '');
      ?>
      <li>
        <article data-activity-card>
          <header>
            <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
              <?= htmlspecialchars($row['tool_name_tol']) ?>
            </a>
            <span data-status="overdue">Overdue</span>
          </header>
          <dl>
            <?php if ($overdueRole === 'lender'): ?>
              <dt>Borrower</dt>
              <dd>
                <a href="/profile/<?= (int) $row['borrower_id'] ?>">
                  <?= htmlspecialchars($row['borrower_name']) ?>
                </a>
              </dd>
            <?php else: ?>
              <dt>Lender</dt>
              <dd>
                <a href="/profile/<?= (int) $row['lender_id'] ?>">
                  <?= htmlspecialchars($row['lender_name']) ?>
                </a>
              </dd>
            <?php endif; ?>
            <dt>Due Date</dt>
            <dd>
              <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
              </time>
            </dd>
            <dt>Overdue By</dt>
            <dd><?= htmlspecialchars($overdueLabel) ?></dd>
          </dl>
        </article>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
