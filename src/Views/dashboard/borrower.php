<?php
/**
 * Dashboard — Borrower sub-page: active borrows, outgoing requests, overdue items.
 *
 * Variables from DashboardController::borrower():
 *   $borrows            array  Active borrows where user is the borrower
 *   $requests           array  Pending requests where user is the borrower
 *   $overdue            array  Overdue borrows where user is the borrower
 *   $awaitingPickup     array  Approved borrows awaiting pickup (borrower side)
 *   $depositsByBorrow   array<int, array>  Keyed by borrow ID — deposit rows for awaiting-pickup borrows
 *   $handoversByBorrow  array<int, array>  Keyed by borrow ID — pending handover rows
 *   $waiversByBorrow    array<int, bool>   Keyed by borrow ID — true if waiver signed
 *   $borrowSort         array{sort: string, dir: string}  Active borrows sort state
 *   $borrowStatus       ?string  Active borrows status filter (on-time|due-soon|overdue|null)
 *   $reqSort            array{sort: string, dir: string}  Pending requests sort state
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

?>

<?php if (!empty($borrowSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($borrowSuccess) ?></p>
  <?php endif; ?>

  <?php
    $flashError = $borrowErrors['general'] ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
  <?php endif; ?>

  <?php if (!empty($overdue)): ?>
    <?php $overdueRole = 'borrower'; ?>
    <?php require BASE_PATH . '/src/Views/partials/overdue-list.php'; ?>
  <?php endif; ?>

  <?php if (!empty($awaitingPickup)): ?>
    <?php $pickupRole = 'borrower'; ?>
    <?php require BASE_PATH . '/src/Views/partials/awaiting-pickup.php'; ?>
  <?php endif; ?>

  <section aria-labelledby="active-borrows-heading">
    <h2 id="active-borrows-heading">
      <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
      Active Borrows (<?= count($borrows) ?>)
    </h2>

    <?php if (!empty($borrows)): ?>
      <?php
        $paramPrefix = 'borrow_';
        $sortOptions = [
            'due_at_bor' => 'Due Date',
            'tool_name_tol' => 'Tool Name',
            'lender_name' => 'Lender',
            'hours_until_due' => 'Time Remaining',
        ];
        $currentSort = $borrowSort['sort'];
        $currentDir = strtolower($borrowSort['dir']);
        $filterOptions = ['' => 'All', 'on-time' => 'On Time', 'due-soon' => 'Due Soon', 'overdue' => 'Overdue'];
        $currentFilter = $borrowStatus;
        $preserveParams = [
            'req_sort' => $reqSort['sort'],
            'req_dir' => strtolower($reqSort['dir']),
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

      <ul data-card-list>
        <?php foreach ($borrows as $row): ?>
          <?php
            $dueStatus  = $row['due_status'] ?? 'ON TIME';
            $statusSlug = match ($dueStatus) {
                'OVERDUE'  => 'overdue',
                'DUE SOON' => 'due-soon',
                default    => 'on-time',
            };
          ?>
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
                  <?= htmlspecialchars($row['tool_name_tol']) ?>
                </a>
                <span data-status="<?= $statusSlug ?>"><?= htmlspecialchars($dueStatus) ?></span>
              </header>
              <dl>
                <dt>Lender</dt>
                <dd>
                  <a href="/profile/<?= (int) $row['lender_id'] ?>">
                    <?= htmlspecialchars($row['lender_name']) ?>
                  </a>
                </dd>
                <dt>Due</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
                  </time>
                </dd>
              </dl>
              <footer data-actions>
                <?php $handover = $handoversByBorrow[(int) $row['id_bor']] ?? null; ?>
                <?php if ($handover !== null): ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Your Return Code
                  </a>
                <?php else: ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Return Code
                  </a>
                <?php endif; ?>
              </footer>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>You don&rsquo;t have any active borrows.</p>
      <a href="/tools" role="button" data-intent="primary">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Browse Tools
      </a>
    <?php endif; ?>
  </section>

  <section aria-labelledby="my-requests-heading">
    <h2 id="my-requests-heading">
      <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
      My Pending Requests (<?= count($requests) ?>)
    </h2>

    <?php if (!empty($requests)): ?>
      <?php
        $paramPrefix = 'req_';
        $sortOptions = [
            'requested_at_bor' => 'Date Requested',
            'tool_name_tol' => 'Tool Name',
            'lender_name' => 'Lender',
            'loan_duration_hours_bor' => 'Duration',
        ];
        $currentSort = $reqSort['sort'];
        $currentDir = strtolower($reqSort['dir']);
        $filterOptions = null;
        $currentFilter = null;
        $preserveParams = [
            'borrow_sort' => $borrowSort['sort'],
            'borrow_dir' => strtolower($borrowSort['dir']),
            'borrow_status' => $borrowStatus ?? '',
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

      <ul data-card-list>
        <?php foreach ($requests as $req): ?>
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= (int) $req['id_bor'] ?>">
                  <?= htmlspecialchars($req['tool_name_tol']) ?>
                </a>
                <span data-status="requested">Pending</span>
              </header>
              <dl>
                <dt>Lender</dt>
                <dd>
                  <a href="/profile/<?= (int) $req['lender_id'] ?>">
                    <?= htmlspecialchars($req['lender_name']) ?>
                  </a>
                </dd>
                <dt>Requested</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($req['requested_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j, g:ia', strtotime($req['requested_at_bor']))) ?>
                  </time>
                </dd>
                <dt>Duration</dt>
                <dd><?= htmlspecialchars(\App\Core\ViewHelper::formatDuration((int) $req['loan_duration_hours_bor'])) ?></dd>
              </dl>
              <footer data-actions>
                <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/cancel">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit" data-intent="danger">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
                  </button>
                </form>
              </footer>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No pending requests.</p>
    <?php endif; ?>
  </section>
