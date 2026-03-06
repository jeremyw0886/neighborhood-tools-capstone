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

use App\Core\ViewHelper;
?>

<section aria-labelledby="borrower-heading">

  <header>
    <h1 id="borrower-heading">
      <i class="fa-solid fa-hand" aria-hidden="true"></i>
      My Borrows
    </h1>
    <p>Track your active borrows, pending requests, and overdue items.</p>
    <?php require BASE_PATH . '/src/Views/partials/tool-search.php'; ?>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/dashboard-nav.php'; ?>

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
    <section aria-labelledby="overdue-heading">
      <h2 id="overdue-heading" data-urgent>
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
                <dt>Lender</dt>
                <dd>
                  <a href="/profile/<?= (int) $row['lender_id'] ?>">
                    <?= htmlspecialchars($row['lender_name']) ?>
                  </a>
                </dd>
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
  <?php endif; ?>

  <?php if (!empty($awaitingPickup)): ?>
    <section aria-labelledby="awaiting-pickup-heading">
      <h2 id="awaiting-pickup-heading">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
        Awaiting Pickup (<?= count($awaitingPickup) ?>)
      </h2>

      <ul data-card-list>
        <?php foreach ($awaitingPickup as $pickup): ?>
          <?php
            $pickupId      = (int) $pickup['id_bor'];
            $waiverSigned  = $waiversByBorrow[$pickupId] ?? false;
            $borrowDeposit = $depositsByBorrow[$pickupId] ?? null;
            $handover      = $handoversByBorrow[$pickupId] ?? null;
            $depositPaid   = $borrowDeposit === null || $borrowDeposit['deposit_status'] !== 'pending';
            $approvedHoursAgo = (int) ((time() - strtotime($pickup['approved_at_bor'])) / 3600);
            $approvedDaysAgo  = (int) floor($approvedHoursAgo / 24);
            $pickupUrgency    = $approvedDaysAgo >= 3 ? 'overdue' : ($approvedDaysAgo >= 2 ? 'due-soon' : null);
            $approvedAgoLabel = $approvedDaysAgo > 0
              ? $approvedDaysAgo . ' day' . ($approvedDaysAgo !== 1 ? 's' : '') . ' ago'
              : $approvedHoursAgo . 'h ago';
          ?>
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= $pickupId ?>">
                  <?= htmlspecialchars($pickup['tool_name_tol']) ?>
                </a>
                <span data-status="approved">Ready for Pickup</span>
              </header>
              <dl>
                <dt>Lender</dt>
                <dd>
                  <a href="/profile/<?= (int) $pickup['lender_id'] ?>">
                    <?= htmlspecialchars($pickup['lender_name']) ?>
                  </a>
                </dd>
                <dt>Approved</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($pickup['approved_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j, g:ia', strtotime($pickup['approved_at_bor']))) ?>
                  </time>
                  <small<?= $pickupUrgency !== null ? ' data-status="' . $pickupUrgency . '"' : '' ?>><?= htmlspecialchars($approvedAgoLabel) ?></small>
                </dd>
                <dt>Duration</dt>
                <dd><?= (int) $pickup['loan_duration_hours_bor'] ?> hrs</dd>
              </dl>
              <footer data-actions>
                <?php if (!$waiverSigned): ?>
                  <a href="/waiver/<?= $pickupId ?>" role="button" data-intent="warning">
                    <i class="fa-solid fa-file-signature" aria-hidden="true"></i> Sign Waiver
                  </a>
                <?php elseif (!$depositPaid): ?>
                  <a href="/payments/deposit/<?= (int) $borrowDeposit['id_sdp'] ?>" role="button" data-intent="warning">
                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i> Pay Deposit
                  </a>
                <?php elseif ($handover !== null): ?>
                  <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Code
                  </a>
                <?php else: ?>
                  <span role="button" aria-disabled="true" data-intent="ghost">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Code
                  </span>
                <?php endif; ?>
                <details>
                  <summary data-intent="danger">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
                  </summary>
                  <form method="post" action="/borrow/<?= $pickupId ?>/cancel">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="cancel-reason-<?= $pickupId ?>">Reason</label>
                    <textarea
                      id="cancel-reason-<?= $pickupId ?>"
                      name="reason"
                      required
                      maxlength="1000"
                      rows="2"
                      placeholder="Why are you cancelling?"
                    ></textarea>
                    <button type="submit" data-intent="danger">Cancel Request</button>
                  </form>
                </details>
              </footer>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <section aria-labelledby="active-borrows-heading">
    <h2 id="active-borrows-heading">
      <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
      Active Borrows (<?= count($borrows) ?>)
    </h2>

    <?php if (!empty($borrows)): ?>
      <form method="get" action="/dashboard/borrower" aria-label="Sort and filter active borrows">
        <fieldset>
          <legend class="visually-hidden">Sort and filter options</legend>
          <input type="hidden" name="req_sort" value="<?= htmlspecialchars($reqSort['sort']) ?>">
          <input type="hidden" name="req_dir" value="<?= htmlspecialchars(strtolower($reqSort['dir'])) ?>">
          <label>
            Sort by
            <select name="borrow_sort">
              <option value="due_at_bor"<?= ViewHelper::selected($borrowSort['sort'], 'due_at_bor') ?>>Due Date</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($borrowSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="lender_name"<?= ViewHelper::selected($borrowSort['sort'], 'lender_name') ?>>Lender</option>
              <option value="hours_until_due"<?= ViewHelper::selected($borrowSort['sort'], 'hours_until_due') ?>>Time Remaining</option>
            </select>
          </label>
          <label>
            Direction
            <select name="borrow_dir">
              <option value="asc"<?= ViewHelper::selected(strtolower($borrowSort['dir']), 'asc') ?>>Soonest First</option>
              <option value="desc"<?= ViewHelper::selected(strtolower($borrowSort['dir']), 'desc') ?>>Latest First</option>
            </select>
          </label>
          <label>
            Status
            <select name="borrow_status">
              <option value=""<?= $borrowStatus === null ? ' selected' : '' ?>>All</option>
              <option value="on-time"<?= ViewHelper::selected($borrowStatus ?? '', 'on-time') ?>>On Time</option>
              <option value="due-soon"<?= ViewHelper::selected($borrowStatus ?? '', 'due-soon') ?>>Due Soon</option>
              <option value="overdue"<?= ViewHelper::selected($borrowStatus ?? '', 'overdue') ?>>Overdue</option>
            </select>
          </label>
          <button type="submit" data-intent="ghost" data-size="sm">Sort</button>
        </fieldset>
      </form>

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
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Your Code
                  </a>
                <?php else: ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Code
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
      <form method="get" action="/dashboard/borrower" aria-label="Sort pending requests">
        <fieldset>
          <legend class="visually-hidden">Sort options</legend>
          <input type="hidden" name="borrow_sort" value="<?= htmlspecialchars($borrowSort['sort']) ?>">
          <input type="hidden" name="borrow_dir" value="<?= htmlspecialchars(strtolower($borrowSort['dir'])) ?>">
          <?php if ($borrowStatus !== null): ?>
            <input type="hidden" name="borrow_status" value="<?= htmlspecialchars($borrowStatus) ?>">
          <?php endif; ?>
          <label>
            Sort by
            <select name="req_sort">
              <option value="requested_at_bor"<?= ViewHelper::selected($reqSort['sort'], 'requested_at_bor') ?>>Date Requested</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($reqSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="lender_name"<?= ViewHelper::selected($reqSort['sort'], 'lender_name') ?>>Lender</option>
              <option value="loan_duration_hours_bor"<?= ViewHelper::selected($reqSort['sort'], 'loan_duration_hours_bor') ?>>Duration</option>
            </select>
          </label>
          <label>
            Direction
            <select name="req_dir">
              <option value="desc"<?= ViewHelper::selected(strtolower($reqSort['dir']), 'desc') ?>>Newest First</option>
              <option value="asc"<?= ViewHelper::selected(strtolower($reqSort['dir']), 'asc') ?>>Oldest First</option>
            </select>
          </label>
          <button type="submit" data-intent="ghost" data-size="sm">Sort</button>
        </fieldset>
      </form>

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
                <dd><?= (int) $req['loan_duration_hours_bor'] ?> hrs</dd>
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

</div>
</section>
