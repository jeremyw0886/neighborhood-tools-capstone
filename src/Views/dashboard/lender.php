<?php
/**
 * Dashboard — Lender sub-page: listed tools, incoming requests, lent-out items.
 *
 * Variables from DashboardController::lender():
 *   $tools              array  Rows from tool_detail_v for this owner (paginated)
 *   $toolsPage          int    Current page number
 *   $toolsCount         int    Total tools owned
 *   $toolsPages         int    Total pages
 *   $perPage            int    Items per page
 *   $incomingRequests   array  Pending requests where user is lender
 *   $awaitingPickup     array  Approved borrows awaiting pickup (lender side)
 *   $lentOut            array  Active borrows where user is lender
 *   $depositsByBorrow   array<int, array>  Keyed by borrow ID — deposit rows for awaiting-pickup borrows
 *   $handoversByBorrow  array<int, array>  Keyed by borrow ID — pending handover rows
 *   $reqSort            array{sort: string, dir: string}  Incoming requests sort state
 *   $lentSort           array{sort: string, dir: string}  Lent-out table sort state
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

use App\Core\ViewHelper;
?>

<section aria-labelledby="lender-heading">

  <header>
    <h1 id="lender-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      My Tools
    </h1>
    <p>Manage your listed tools and respond to incoming borrow requests.</p>
    <?php require BASE_PATH . '/src/Views/partials/tool-search.php'; ?>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/dashboard-nav.php'; ?>

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
              <option value="requested_at_bor"<?= ViewHelper::selected($reqSort['sort'], 'requested_at_bor') ?>>Date Requested</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($reqSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="borrower_name"<?= ViewHelper::selected($reqSort['sort'], 'borrower_name') ?>>Borrower</option>
              <option value="hours_pending"<?= ViewHelper::selected($reqSort['sort'], 'hours_pending') ?>>Wait Time</option>
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
        <?php foreach ($incomingRequests as $req): ?>
          <?php
            $hoursPending = (int) $req['hours_pending'];
            $waitLabel = $hoursPending < 24
              ? $hoursPending . 'h ago'
              : (int) floor($hoursPending / 24) . 'd ago';
            $bAvg   = round((float) ($req['borrower_avg_rating'] ?? 0), 1);
            $bCount = (int) ($req['borrower_rating_count'] ?? 0);
          ?>
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= (int) $req['id_bor'] ?>">
                  <?= htmlspecialchars($req['tool_name_tol']) ?>
                </a>
                <span data-status="requested">Pending</span>
              </header>
              <dl>
                <dt>Borrower</dt>
                <dd>
                  <a href="/profile/<?= (int) $req['borrower_id'] ?>">
                    <?= htmlspecialchars($req['borrower_name']) ?>
                  </a>
                </dd>
                <dt>Duration</dt>
                <dd><?= (int) $req['loan_duration_hours_bor'] ?> hrs</dd>
                <dt>Waiting</dt>
                <dd><?= htmlspecialchars($waitLabel) ?></dd>
                <dt>Rating</dt>
                <dd><?= $bCount > 0 ? $bAvg . '/5 (' . $bCount . ')' : 'No ratings' ?></dd>
              </dl>
              <footer data-actions>
                <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/approve">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit" data-intent="success">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                  </button>
                </form>
                <details>
                  <summary data-intent="danger">
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
                    <button type="submit" data-intent="danger">Deny Request</button>
                  </form>
                </details>
              </footer>
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
            $pickupId    = (int) $pickup['id_bor'];
            $deposit     = $depositsByBorrow[$pickupId] ?? null;
            $handover    = $handoversByBorrow[$pickupId] ?? null;
            $depositPaid = $deposit === null || $deposit['deposit_status'] !== 'pending';
          ?>
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= $pickupId ?>">
                  <?= htmlspecialchars($pickup['tool_name_tol']) ?>
                </a>
                <span data-status="approved">Awaiting Pickup</span>
              </header>
              <dl>
                <dt>Borrower</dt>
                <dd>
                  <a href="/profile/<?= (int) $pickup['borrower_id'] ?>">
                    <?= htmlspecialchars($pickup['borrower_name']) ?>
                  </a>
                </dd>
                <dt>Approved</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($pickup['approved_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j, g:ia', strtotime($pickup['approved_at_bor']))) ?>
                  </time>
                </dd>
                <dt>Duration</dt>
                <dd><?= (int) $pickup['loan_duration_hours_bor'] ?> hrs</dd>
              </dl>
              <footer data-actions>
                <?php if (!$depositPaid): ?>
                  <span role="button" aria-disabled="true" data-intent="ghost">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Deposit
                  </span>
                <?php elseif ($handover !== null): ?>
                  <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Your Code
                  </a>
                <?php else: ?>
                  <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Code
                  </a>
                <?php endif; ?>
              </footer>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
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
              <option value="due_at_bor"<?= ViewHelper::selected($lentSort['sort'], 'due_at_bor') ?>>Due Date</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($lentSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="borrower_name"<?= ViewHelper::selected($lentSort['sort'], 'borrower_name') ?>>Borrower</option>
              <option value="hours_until_due"<?= ViewHelper::selected($lentSort['sort'], 'hours_until_due') ?>>Time Remaining</option>
            </select>
          </label>
          <label>
            Direction
            <select name="lent_dir">
              <option value="asc"<?= ViewHelper::selected(strtolower($lentSort['dir']), 'asc') ?>>Soonest First</option>
              <option value="desc"<?= ViewHelper::selected(strtolower($lentSort['dir']), 'desc') ?>>Latest First</option>
            </select>
          </label>
          <button type="submit" data-intent="ghost" data-size="sm">Sort</button>
        </fieldset>
      </form>

      <ul data-card-list>
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
          <li>
            <article data-activity-card>
              <header>
                <a href="/dashboard/loan/<?= (int) $row['id_bor'] ?>">
                  <?= htmlspecialchars($row['tool_name_tol']) ?>
                </a>
                <span data-status="<?= $statusSlug ?>"><?= htmlspecialchars($dueStatus) ?></span>
              </header>
              <dl>
                <dt>Borrower</dt>
                <dd>
                  <a href="/profile/<?= (int) $row['borrower_id'] ?>">
                    <?= htmlspecialchars($row['borrower_name']) ?>
                  </a>
                </dd>
                <dt>Due</dt>
                <dd>
                  <time datetime="<?= htmlspecialchars($row['due_at_bor']) ?>">
                    <?= htmlspecialchars(date('M j, g:ia', strtotime($row['due_at_bor']))) ?>
                  </time>
                  <small><?= htmlspecialchars($dueLabel) ?></small>
                </dd>
              </dl>
              <footer data-actions>
                <?php $handover = $handoversByBorrow[(int) $row['id_bor']] ?? null; ?>
                <?php if ($handover !== null): ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Code
                  </a>
                <?php else: ?>
                  <span role="button" aria-disabled="true" data-intent="ghost">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Code
                  </span>
                <?php endif; ?>
                <details>
                  <summary data-intent="warning">
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
                    <button type="submit" data-intent="warning">Extend Loan</button>
                  </form>
                </details>
              </footer>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No tools currently lent out.</p>
    <?php endif; ?>
  </section>

  <?php
    $rangeStart = $toolsCount > 0 ? (($toolsPage - 1) * $perPage) + 1 : 0;
    $rangeEnd   = min($toolsPage * $perPage, $toolsCount);

    $paginationUrl = static function (int $pageNum) use ($reqSort, $lentSort): string {
        $params = array_filter([
            'page'      => $pageNum > 1 ? $pageNum : null,
            'req_sort'  => $reqSort['sort'],
            'req_dir'   => strtolower($reqSort['dir']),
            'lent_sort' => $lentSort['sort'],
            'lent_dir'  => strtolower($lentSort['dir']),
        ]);

        return '/dashboard/lender?' . http_build_query($params);
    };
  ?>

  <section aria-labelledby="listed-tools-heading">
    <h2 id="listed-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Listed Tools (<?= htmlspecialchars((string) $toolsCount) ?>)
    </h2>

    <?php if (!empty($tools)): ?>

      <div aria-live="polite" aria-atomic="true">
        <?php if ($toolsCount > $perPage): ?>
          <p>
            Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>&ndash;<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
            <strong><?= number_format($toolsCount) ?></strong>
            tool<?= $toolsCount !== 1 ? 's' : '' ?>
          </p>
        <?php endif; ?>
      </div>

      <div role="list">
        <?php $cardHeadingLevel = 'h3'; ?>
        <?php foreach ($tools as $tool): ?>
          <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
        <?php endforeach; ?>
        <?php unset($cardHeadingLevel); ?>
      </div>

      <?php if ($toolsPages > 1): ?>
        <nav aria-label="Pagination">
          <ul>

            <?php if ($toolsPage > 1): ?>
              <li>
                <a href="<?= $paginationUrl($toolsPage - 1) ?>"
                   aria-label="Go to previous page">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </a>
              </li>
            <?php else: ?>
              <li>
                <span aria-disabled="true">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </span>
              </li>
            <?php endif; ?>

            <?php
            $windowSize = 2;
            $startPage  = max(1, $toolsPage - $windowSize);
            $endPage    = min($toolsPages, $toolsPage + $windowSize);

            if ($startPage > 1): ?>
              <li>
                <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
              </li>
              <?php if ($startPage > 2): ?>
                <li><span aria-hidden="true">&hellip;</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <li>
                <?php if ($i === $toolsPage): ?>
                  <a href="<?= $paginationUrl($i) ?>"
                     aria-current="page"
                     aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
                <?php else: ?>
                  <a href="<?= $paginationUrl($i) ?>"
                     aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
                <?php endif; ?>
              </li>
            <?php endfor; ?>

            <?php if ($endPage < $toolsPages): ?>
              <?php if ($endPage < $toolsPages - 1): ?>
                <li><span aria-hidden="true">&hellip;</span></li>
              <?php endif; ?>
              <li>
                <a href="<?= $paginationUrl($toolsPages) ?>"
                   aria-label="Go to page <?= htmlspecialchars((string) $toolsPages) ?>"><?= htmlspecialchars((string) $toolsPages) ?></a>
              </li>
            <?php endif; ?>

            <?php if ($toolsPage < $toolsPages): ?>
              <li>
                <a href="<?= $paginationUrl($toolsPage + 1) ?>"
                   aria-label="Go to next page">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            <?php else: ?>
              <li>
                <span aria-disabled="true">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
              </li>
            <?php endif; ?>

          </ul>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <p>You haven&rsquo;t listed any tools yet.</p>
      <a href="/tools/create" role="button" data-intent="primary">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> List Your First Tool
      </a>
    <?php endif; ?>
  </section>

</div>
</section>
