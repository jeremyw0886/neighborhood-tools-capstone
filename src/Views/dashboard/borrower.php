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

      <table>
        <caption class="visually-hidden">Overdue borrowed items</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Lender</th>
            <th scope="col">Due Date</th>
            <th scope="col">Overdue By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($overdue as $row): ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
                  <?= htmlspecialchars($row['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $row['lender_id'] ?>">
                  <?= htmlspecialchars($row['lender_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
                </time>
              </td>
              <td>
                <?php
                  $daysOverdue = (int) $row['days_overdue'];
                  $hoursOverdue = (int) $row['hours_overdue'];
                  echo $daysOverdue > 0
                    ? $daysOverdue . ' day' . ($daysOverdue !== 1 ? 's' : '')
                    : $hoursOverdue . ' hour' . ($hoursOverdue !== 1 ? 's' : '');
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <?php if (!empty($awaitingPickup)): ?>
    <section aria-labelledby="awaiting-pickup-heading">
      <h2 id="awaiting-pickup-heading">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
        Awaiting Pickup (<?= count($awaitingPickup) ?>)
      </h2>

      <table>
        <caption class="visually-hidden">Approved borrows ready for pickup</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Lender</th>
            <th scope="col">Approved</th>
            <th scope="col">Duration</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($awaitingPickup as $pickup): ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $pickup['id_bor'] ?>">
                  <?= htmlspecialchars($pickup['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $pickup['lender_id'] ?>">
                  <?= htmlspecialchars($pickup['lender_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($pickup['approved_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($pickup['approved_at_bor']))) ?>
                </time>
              </td>
              <td><?= (int) $pickup['loan_duration_hours_bor'] ?> hrs</td>
              <td><span data-status="approved">Approved &mdash; ready for pickup</span></td>
              <td data-actions>
                <?php
                  $pickupId      = (int) $pickup['id_bor'];
                  $borrowDeposit = $depositsByBorrow[$pickupId] ?? null;
                  $handover      = $handoversByBorrow[$pickupId] ?? null;
                  $depositPaid   = $borrowDeposit === null || $borrowDeposit['deposit_status'] !== 'pending';
                ?>
                <?php if (!$depositPaid): ?>
                  <a href="/payments/deposit/<?= (int) $borrowDeposit['id_sdp'] ?>" role="button">
                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i> Pay Deposit
                  </a>
                <?php elseif ($handover !== null): ?>
                  <a href="/handover/<?= $pickupId ?>" role="button">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Code
                  </a>
                <?php else: ?>
                  <span role="button" aria-disabled="true">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Code
                  </span>
                <?php endif; ?>
                <details>
                  <summary>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
                  </summary>
                  <form method="post" action="/borrow/<?= (int) $pickup['id_bor'] ?>/cancel">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="cancel-reason-<?= (int) $pickup['id_bor'] ?>">Reason</label>
                    <textarea
                      id="cancel-reason-<?= (int) $pickup['id_bor'] ?>"
                      name="reason"
                      required
                      maxlength="1000"
                      rows="2"
                      placeholder="Why are you cancelling?"
                    ></textarea>
                    <button type="submit">Cancel Request</button>
                  </form>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Currently borrowed items</caption>
        <thead>
          <tr>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'lender_name') ?>>Lender</th>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'due_at_bor', 'hours_until_due') ?>>Due Date</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrows as $row): ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
                  <?= htmlspecialchars($row['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $row['lender_id'] ?>">
                  <?= htmlspecialchars($row['lender_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
                </time>
              </td>
              <td>
                <?php
                  $status = $row['due_status'] ?? 'ON TIME';
                  $statusAttr = match ($status) {
                      'OVERDUE'  => ' data-urgent',
                      'DUE SOON' => ' data-warning',
                      default    => '',
                  };
                ?>
                <span<?= $statusAttr ?>><?= htmlspecialchars($status) ?></span>
              </td>
              <td data-actions>
                <?php $handover = $handoversByBorrow[(int) $row['id_bor']] ?? null; ?>
                <?php if ($handover !== null): ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Your Code
                  </a>
                <?php else: ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Code
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You don&rsquo;t have any active borrows.</p>
      <a href="/tools" role="button">
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
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Your pending borrow requests</caption>
        <thead>
          <tr>
            <th scope="col"<?= ViewHelper::ariaSort($reqSort['sort'], $reqSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= ViewHelper::ariaSort($reqSort['sort'], $reqSort['dir'], 'lender_name') ?>>Lender</th>
            <th scope="col"<?= ViewHelper::ariaSort($reqSort['sort'], $reqSort['dir'], 'requested_at_bor') ?>>Requested</th>
            <th scope="col"<?= ViewHelper::ariaSort($reqSort['sort'], $reqSort['dir'], 'loan_duration_hours_bor') ?>>Duration</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $req): ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $req['id_bor'] ?>">
                  <?= htmlspecialchars($req['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $req['lender_id'] ?>">
                  <?= htmlspecialchars($req['lender_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($req['requested_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($req['requested_at_bor']))) ?>
                </time>
              </td>
              <td><?= (int) $req['loan_duration_hours_bor'] ?> hrs</td>
              <td data-actions>
                <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/cancel">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No pending requests.</p>
    <?php endif; ?>
  </section>

</div>
</section>
