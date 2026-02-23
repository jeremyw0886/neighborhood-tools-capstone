<?php
/**
 * Dashboard — Lender sub-page: listed tools, incoming requests, lent-out items.
 *
 * Variables from DashboardController::lender():
 *   $tools            array  Rows from tool_detail_v for this owner
 *   $incomingRequests array  Pending requests where user is lender
 *   $awaitingPickup   array  Approved borrows awaiting pickup (lender side)
 *   $lentOut          array  Active borrows where user is lender
 *   $reqSort          array{sort: string, dir: string}  Incoming requests sort state
 *   $lentSort         array{sort: string, dir: string}  Lent-out table sort state
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

$sel = fn(string $a, string $b): string => $a === $b ? ' selected' : '';

$ariaSortAttr = fn(string $sort, string $dir, string ...$fields): string =>
    in_array($sort, $fields, true)
        ? ' aria-sort="' . ($dir === 'ASC' ? 'ascending' : 'descending') . '"'
        : '';
?>

<section aria-labelledby="lender-heading">

  <header>
    <h1 id="lender-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      My Tools
    </h1>
    <p>Manage your listed tools and respond to incoming borrow requests.</p>
  </header>

  <nav aria-label="Dashboard sections">
    <ul>
      <li><a href="/dashboard"         ><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
      <li><a href="/dashboard/lender"   aria-current="page"><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
      <li><a href="/dashboard/borrower"><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
      <li><a href="/dashboard/history" ><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    </ul>
  </nav>

  <?php if (!empty($borrowSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($borrowSuccess) ?></p>
  <?php endif; ?>

  <?php
    $flashError = $borrowErrors['general']
      ?? $borrowErrors['reason']
      ?? $borrowErrors['extra_hours']
      ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
  <?php endif; ?>

  <?php if (!empty($incomingRequests)): ?>
    <section aria-labelledby="incoming-heading">
      <h2 id="incoming-heading">
        <i class="fa-solid fa-inbox" aria-hidden="true"></i>
        Incoming Requests (<?= count($incomingRequests) ?>)
      </h2>

      <form method="get" action="/dashboard/lender" aria-label="Sort incoming requests">
        <fieldset>
          <legend class="visually-hidden">Sort options</legend>
          <input type="hidden" name="lent_sort" value="<?= htmlspecialchars($lentSort['sort']) ?>">
          <input type="hidden" name="lent_dir" value="<?= htmlspecialchars(strtolower($lentSort['dir'])) ?>">
          <label>
            Sort by
            <select name="req_sort">
              <option value="requested_at_bor"<?= $sel($reqSort['sort'], 'requested_at_bor') ?>>Date Requested</option>
              <option value="tool_name_tol"<?= $sel($reqSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="borrower_name"<?= $sel($reqSort['sort'], 'borrower_name') ?>>Borrower</option>
              <option value="hours_pending"<?= $sel($reqSort['sort'], 'hours_pending') ?>>Wait Time</option>
              <option value="loan_duration_hours_bor"<?= $sel($reqSort['sort'], 'loan_duration_hours_bor') ?>>Duration</option>
            </select>
          </label>
          <label>
            Direction
            <select name="req_dir">
              <option value="desc"<?= $sel(strtolower($reqSort['dir']), 'desc') ?>>Newest First</option>
              <option value="asc"<?= $sel(strtolower($reqSort['dir']), 'asc') ?>>Oldest First</option>
            </select>
          </label>
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Pending borrow requests for your tools</caption>
        <thead>
          <tr>
            <th scope="col"<?= $ariaSortAttr($reqSort['sort'], $reqSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= $ariaSortAttr($reqSort['sort'], $reqSort['dir'], 'borrower_name') ?>>Borrower</th>
            <th scope="col"<?= $ariaSortAttr($reqSort['sort'], $reqSort['dir'], 'loan_duration_hours_bor') ?>>Duration</th>
            <th scope="col"<?= $ariaSortAttr($reqSort['sort'], $reqSort['dir'], 'hours_pending', 'requested_at_bor') ?>>Waiting</th>
            <th scope="col">Borrower Rating</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($incomingRequests as $req): ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $req['id_bor'] ?>">
                  <?= htmlspecialchars($req['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $req['borrower_id'] ?>">
                  <?= htmlspecialchars($req['borrower_name']) ?>
                </a>
              </td>
              <td><?= (int) $req['loan_duration_hours_bor'] ?> hrs</td>
              <td>
                <?php
                  $hoursPending = (int) $req['hours_pending'];
                  echo $hoursPending < 24
                    ? $hoursPending . 'h ago'
                    : (int) floor($hoursPending / 24) . 'd ago';
                ?>
              </td>
              <td>
                <?php
                  $bAvg = round((float) ($req['borrower_avg_rating'] ?? 0), 1);
                  $bCount = (int) ($req['borrower_rating_count'] ?? 0);
                ?>
                <?= $bCount > 0 ? $bAvg . '/5 (' . $bCount . ')' : 'No ratings' ?>
              </td>
              <td data-actions>
                <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/approve">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                  </button>
                </form>
                <details>
                  <summary>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny
                  </summary>
                  <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/deny">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="deny-reason-<?= (int) $req['id_bor'] ?>">Reason</label>
                    <textarea
                      id="deny-reason-<?= (int) $req['id_bor'] ?>"
                      name="reason"
                      required
                      maxlength="1000"
                      rows="2"
                      placeholder="Why are you denying this request?"
                    ></textarea>
                    <button type="submit">Deny Request</button>
                  </form>
                </details>
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
        <caption class="visually-hidden">Approved borrows awaiting pickup</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Borrower</th>
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
                <a href="/profile/<?= (int) $pickup['borrower_id'] ?>">
                  <?= htmlspecialchars($pickup['borrower_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($pickup['approved_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($pickup['approved_at_bor']))) ?>
                </time>
              </td>
              <td><?= (int) $pickup['loan_duration_hours_bor'] ?> hrs</td>
              <td><span data-status="approved">Approved &mdash; awaiting pickup</span></td>
              <td data-actions>
                <a href="/handover/<?= (int) $pickup['id_bor'] ?>" role="button">
                  <i class="fa-solid fa-qrcode" aria-hidden="true"></i> Handover
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <section aria-labelledby="lent-out-heading">
    <h2 id="lent-out-heading">
      <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
      Currently Lent Out (<?= count($lentOut) ?>)
    </h2>

    <?php if (!empty($lentOut)): ?>
      <form method="get" action="/dashboard/lender" aria-label="Sort lent-out tools">
        <fieldset>
          <legend class="visually-hidden">Sort options</legend>
          <input type="hidden" name="req_sort" value="<?= htmlspecialchars($reqSort['sort']) ?>">
          <input type="hidden" name="req_dir" value="<?= htmlspecialchars(strtolower($reqSort['dir'])) ?>">
          <label>
            Sort by
            <select name="lent_sort">
              <option value="due_at_bor"<?= $sel($lentSort['sort'], 'due_at_bor') ?>>Due Date</option>
              <option value="tool_name_tol"<?= $sel($lentSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="borrower_name"<?= $sel($lentSort['sort'], 'borrower_name') ?>>Borrower</option>
              <option value="hours_until_due"<?= $sel($lentSort['sort'], 'hours_until_due') ?>>Time Remaining</option>
            </select>
          </label>
          <label>
            Direction
            <select name="lent_dir">
              <option value="asc"<?= $sel(strtolower($lentSort['dir']), 'asc') ?>>Soonest First</option>
              <option value="desc"<?= $sel(strtolower($lentSort['dir']), 'desc') ?>>Latest First</option>
            </select>
          </label>
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Tools currently lent to other members</caption>
        <thead>
          <tr>
            <th scope="col"<?= $ariaSortAttr($lentSort['sort'], $lentSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= $ariaSortAttr($lentSort['sort'], $lentSort['dir'], 'borrower_name') ?>>Borrower</th>
            <th scope="col"<?= $ariaSortAttr($lentSort['sort'], $lentSort['dir'], 'due_at_bor', 'hours_until_due') ?>>Due Date</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lentOut as $row): ?>
            <?php
              $dueStatus    = $row['due_status'] ?? 'ON TIME';
              $statusSlug   = match ($dueStatus) {
                  'OVERDUE'  => 'overdue',
                  'DUE SOON' => 'due-soon',
                  default    => 'on-time',
              };
              $hoursUntilDue = (int) ($row['hours_until_due'] ?? 0);
              $dueLabel      = match (true) {
                  $dueStatus === 'OVERDUE'   => abs($hoursUntilDue) . 'h overdue',
                  $hoursUntilDue >= 24        => (int) floor($hoursUntilDue / 24) . 'd ' . ($hoursUntilDue % 24) . 'h left',
                  $hoursUntilDue > 0          => $hoursUntilDue . 'h left',
                  default                     => 'Due now',
              };
            ?>
            <tr>
              <td>
                <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
                  <?= htmlspecialchars($row['tool_name_tol']) ?>
                </a>
              </td>
              <td>
                <a href="/profile/<?= (int) $row['borrower_id'] ?>">
                  <?= htmlspecialchars($row['borrower_name']) ?>
                </a>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
                </time>
                <small><?= htmlspecialchars($dueLabel) ?></small>
              </td>
              <td>
                <span data-status="<?= $statusSlug ?>"><?= htmlspecialchars($dueStatus) ?></span>
              </td>
              <td data-actions>
                <form method="post" action="/borrow/<?= (int) $row['id_bor'] ?>/return">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Confirm Return
                  </button>
                </form>
                <details>
                  <summary>
                    <i class="fa-solid fa-clock" aria-hidden="true"></i> Extend
                  </summary>
                  <form method="post" action="/borrow/<?= (int) $row['id_bor'] ?>/extend">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="extra-hours-<?= (int) $row['id_bor'] ?>">Additional hours</label>
                    <input
                      type="number"
                      id="extra-hours-<?= (int) $row['id_bor'] ?>"
                      name="extra_hours"
                      required
                      min="1"
                      max="720"
                      placeholder="e.g. 24"
                    >
                    <label for="extend-reason-<?= (int) $row['id_bor'] ?>">Reason</label>
                    <textarea
                      id="extend-reason-<?= (int) $row['id_bor'] ?>"
                      name="reason"
                      required
                      maxlength="1000"
                      rows="2"
                      placeholder="Why are you extending this loan?"
                    ></textarea>
                    <button type="submit">Extend Loan</button>
                  </form>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No tools currently lent out.</p>
    <?php endif; ?>
  </section>

  <section aria-labelledby="listed-tools-heading">
    <h2 id="listed-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Listed Tools (<?= count($tools) ?>)
    </h2>

    <?php if (!empty($tools)): ?>
      <div role="list">
        <?php $cardHeadingLevel = 'h3'; ?>
        <?php foreach ($tools as $tool): ?>
          <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
        <?php endforeach; ?>
        <?php unset($cardHeadingLevel); ?>
      </div>
    <?php else: ?>
      <p>You haven&rsquo;t listed any tools yet.</p>
      <a href="/tools/create" role="button">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> List Your First Tool
      </a>
    <?php endif; ?>
  </section>

</section>
