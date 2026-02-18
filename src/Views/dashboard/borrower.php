<?php
/**
 * Dashboard — Borrower sub-page: active borrows, outgoing requests, overdue items.
 *
 * Variables from DashboardController::borrower():
 *   $borrows   array  Active borrows where user is the borrower
 *   $requests  array  Pending requests where user is the borrower
 *   $overdue   array  Overdue borrows where user is the borrower
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */
?>

<section aria-labelledby="borrower-heading">

  <header>
    <h1 id="borrower-heading">
      <i class="fa-solid fa-hand" aria-hidden="true"></i>
      My Borrows
    </h1>
    <p>Track your active borrows, pending requests, and overdue items.</p>
  </header>

  <nav aria-label="Dashboard sections">
    <ul>
      <li><a href="/dashboard"         ><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
      <li><a href="/dashboard/lender"  ><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
      <li><a href="/dashboard/borrower" aria-current="page"><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
      <li><a href="/dashboard/history" ><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    </ul>
  </nav>

  <?php if (!empty($_SESSION['borrow_success'])): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($_SESSION['borrow_success']) ?></p>
    <?php unset($_SESSION['borrow_success']); ?>
  <?php endif; ?>

  <?php
    $flashError = $_SESSION['borrow_errors']['general'] ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
    <?php unset($_SESSION['borrow_errors']); ?>
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
              <td><?= htmlspecialchars($row['tool_name_tol']) ?></td>
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

  <section aria-labelledby="active-borrows-heading">
    <h2 id="active-borrows-heading">
      <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
      Active Borrows (<?= count($borrows) ?>)
    </h2>

    <?php if (!empty($borrows)): ?>
      <table>
        <caption class="visually-hidden">Currently borrowed items</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Lender</th>
            <th scope="col">Due Date</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrows as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol']) ?></td>
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
      <table>
        <caption class="visually-hidden">Your pending borrow requests</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Owner</th>
            <th scope="col">Requested</th>
            <th scope="col">Duration</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $req): ?>
            <tr>
              <td><?= htmlspecialchars($req['tool_name_tol']) ?></td>
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
              <td>
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

</section>
