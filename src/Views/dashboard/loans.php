<?php
/**
 * Dashboard — Loans sub-page: all active, pending, and recently completed loans.
 *
 * Variables from DashboardController::loans():
 *   $activeLoans     array  Non-terminal loans (requested/approved/borrowed) with user_role and due_status_key
 *   $recentCompleted array  Terminal loans (returned/denied/cancelled) from the last 30 days
 *   $ratedBorrowIds  int[]  Borrow IDs the current user has already rated
 *   $currentRole     string Current role filter (all|lender|borrower)
 *   $currentStatus   string Current status filter (all|active|pending|completed)
 *   $currentSort     string Current sort field for completed section
 *   $currentDir      string Current sort direction for completed section
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

use App\Core\ViewHelper;
?>

<form method="get" action="/dashboard/loans" data-filter-form>
  <fieldset>
    <legend class="visually-hidden">Filter loans</legend>

    <label>
      Role
      <select name="role">
        <option value="all"<?= ViewHelper::selected($currentRole, 'all') ?>>All Roles</option>
        <option value="lender"<?= ViewHelper::selected($currentRole, 'lender') ?>>As Lender</option>
        <option value="borrower"<?= ViewHelper::selected($currentRole, 'borrower') ?>>As Borrower</option>
      </select>
    </label>

    <label>
      Status
      <select name="status">
        <option value="all"<?= ViewHelper::selected($currentStatus, 'all') ?>>All Statuses</option>
        <option value="active"<?= ViewHelper::selected($currentStatus, 'active') ?>>Active</option>
        <option value="pending"<?= ViewHelper::selected($currentStatus, 'pending') ?>>Pending</option>
        <option value="completed"<?= ViewHelper::selected($currentStatus, 'completed') ?>>Completed</option>
      </select>
    </label>

    <button type="submit">
      <i class="fa-solid fa-filter" aria-hidden="true"></i> Filter
    </button>
  </fieldset>
</form>

<?php if ($activeLoans): ?>
  <section aria-labelledby="active-loans-heading">
    <h2 id="active-loans-heading">
      <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
      Active Loans (<?= count($activeLoans) ?>)
    </h2>

    <ul data-card-list>
      <?php foreach ($activeLoans as $loan): ?>
        <li>
          <article data-activity-card>
            <header>
              <a href="/dashboard/loan/<?= (int) $loan['id_bor'] ?>">
                <?= htmlspecialchars($loan['tool_name_tol']) ?>
              </a>
              <span data-status="<?= htmlspecialchars($loan['due_status_key']) ?>"><?= htmlspecialchars($loan['status_name']) ?></span>
            </header>
            <dl>
              <dt class="visually-hidden">Role</dt>
              <dd><span data-role-badge="<?= htmlspecialchars($loan['user_role']) ?>"><?= $loan['user_role'] === 'lender' ? 'Lending' : 'Borrowing' ?></span></dd>

              <dt>Counterparty</dt>
              <dd>
                <a href="/profile/<?= (int) $loan['counterparty_id'] ?>">
                  <?= htmlspecialchars($loan['counterparty_name']) ?>
                </a>
              </dd>

              <?php if ($loan['due_at_bor']): ?>
                <dt>Due</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($loan['due_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j \a\t g:ia', strtotime($loan['due_at_bor']))) ?>
                  </time>
                </dd>
              <?php endif; ?>
            </dl>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if ($recentCompleted): ?>
  <section aria-labelledby="completed-loans-heading">
    <h2 id="completed-loans-heading">
      <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
      Recently Completed (<?= count($recentCompleted) ?>)
    </h2>

    <ul data-card-list>
      <?php foreach ($recentCompleted as $loan): ?>
        <li>
          <article data-activity-card>
            <header>
              <a href="/dashboard/loan/<?= (int) $loan['id_bor'] ?>">
                <?= htmlspecialchars($loan['tool_name_tol']) ?>
              </a>
              <span data-status="<?= htmlspecialchars($loan['borrow_status']) ?>"><?= htmlspecialchars(ucfirst($loan['borrow_status'])) ?></span>
            </header>
            <dl>
              <dt class="visually-hidden">Role</dt>
              <dd><span data-role-badge="<?= htmlspecialchars($loan['user_role']) ?>"><?= $loan['user_role'] === 'lender' ? 'Lent' : 'Borrowed' ?></span></dd>

              <dt>Counterparty</dt>
              <dd>
                <a href="/profile/<?= (int) $loan['counterparty_id'] ?>">
                  <?= htmlspecialchars($loan['counterparty_name']) ?>
                </a>
              </dd>

              <?php if ($loan['borrow_status'] === 'returned'): ?>
                <dt>Returned</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($loan['returned_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j', strtotime($loan['returned_at_bor']))) ?>
                  </time>
                </dd>

                <?php if (!in_array((int) $loan['id_bor'], $ratedBorrowIds, true)): ?>
                  <dt class="visually-hidden">Action</dt>
                  <dd>
                    <a href="/rate/<?= (int) $loan['id_bor'] ?>" data-rate-link>
                      <i class="fa-solid fa-star" aria-hidden="true"></i> Rate
                    </a>
                  </dd>
                <?php endif; ?>
              <?php endif; ?>
            </dl>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if (!$activeLoans && !$recentCompleted): ?>
  <section aria-label="No loans">
    <p>You have no active or recent loans. <a href="/tools">Browse tools</a> to get started.</p>
  </section>
<?php endif; ?>
