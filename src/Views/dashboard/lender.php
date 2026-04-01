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
 *   $overdue            array  Overdue borrows where user is lender
 *   $incomingRequests   array  Pending requests where user is lender
 *   $awaitingPickup     array  Approved borrows awaiting pickup (lender side)
 *   $lentOut            array  Active borrows where user is lender
 *   $depositsByBorrow        array<int, array>  Keyed by borrow ID — deposit rows for awaiting-pickup borrows
 *   $lentDepositsByBorrow    array<int, array>  Keyed by borrow ID — deposit rows for lent-out borrows
 *   $handoversByBorrow       array<int, array>  Keyed by borrow ID — pending handover rows
 *   $waiversByBorrow         array<int, bool>   Keyed by borrow ID — true if waiver signed
 *   $reqSort            array{sort: string, dir: string}  Incoming requests sort state
 *   $lentSort           array{sort: string, dir: string}  Lent-out table sort state
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

?>

<?php if (!empty($lenderNotice)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($lenderNotice) ?></p>
  <?php endif; ?>

  <?php if (!empty($borrowSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($borrowSuccess) ?></p>
  <?php endif; ?>
  <?php if (!empty($decisionData)): ?>
    <script id="decision-data" type="application/json"><?= json_encode($decisionData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?></script>
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

  <?php if (!empty($overdue)): ?>
    <?php $overdueRole = 'lender'; ?>
    <?php require BASE_PATH . '/src/Views/partials/overdue-list.php'; ?>
  <?php endif; ?>

  <?php if (!empty($incomingRequests)): ?>
    <section aria-labelledby="incoming-heading">
      <h2 id="incoming-heading">
        <i class="fa-solid fa-inbox" aria-hidden="true"></i>
        Incoming Requests (<?= count($incomingRequests) ?>)
      </h2>

      <?php
        $paramPrefix = 'req_';
        $sortOptions = [
            'requested_at_bor' => 'Date Requested',
            'tool_name_tol' => 'Tool Name',
            'borrower_name' => 'Borrower',
            'hours_pending' => 'Wait Time',
            'loan_duration_hours_bor' => 'Duration',
        ];
        $currentSort = $reqSort['sort'];
        $currentDir = strtolower($reqSort['dir']);
        $filterOptions = null;
        $currentFilter = null;
        $preserveParams = [
            'lent_sort' => $lentSort['sort'],
            'lent_dir' => strtolower($lentSort['dir']),
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

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
                <dd><?= htmlspecialchars(\App\Core\ViewHelper::formatDuration((int) $req['loan_duration_hours_bor'])) ?></dd>
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
                    <button type="submit" data-intent="danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny Request</button>
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
    <?php $pickupRole = 'lender'; ?>
    <?php require BASE_PATH . '/src/Views/partials/awaiting-pickup.php'; ?>
  <?php endif; ?>

  <section aria-labelledby="lent-out-heading">
    <h2 id="lent-out-heading">
      <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
      Currently Lent Out (<?= count($lentOut) ?>)
    </h2>

    <?php if (!empty($lentOut)): ?>
      <?php
        $paramPrefix = 'lent_';
        $sortOptions = [
            'due_at_bor' => 'Due Date',
            'tool_name_tol' => 'Tool Name',
            'borrower_name' => 'Borrower',
            'hours_until_due' => 'Time Remaining',
        ];
        $currentSort = $lentSort['sort'];
        $currentDir = strtolower($lentSort['dir']);
        $filterOptions = null;
        $currentFilter = null;
        $preserveParams = [
            'req_sort' => $reqSort['sort'],
            'req_dir' => strtolower($reqSort['dir']),
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

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
                <?php $lentDeposit = $lentDepositsByBorrow[(int) $row['id_bor']] ?? null; ?>
                <?php if ($lentDeposit !== null): ?>
                <dt>Deposit</dt>
                <dd>
                  <a href="/payments/deposit/<?= (int) $lentDeposit['id_sdp'] ?>">
                    $<?= number_format((float) $lentDeposit['amount_sdp'], 2) ?>
                  </a>
                  <small>(<?= htmlspecialchars(str_replace('_', ' ', $lentDeposit['deposit_status'])) ?>)</small>
                </dd>
                <?php endif; ?>
              </dl>
              <footer data-actions>
                <?php $handover = $handoversByBorrow[(int) $row['id_bor']] ?? null; ?>
                <?php if ($handover !== null): ?>
                  <a href="/handover/<?= (int) $row['id_bor'] ?>" role="button" data-intent="info">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Return Code
                  </a>
                <?php else: ?>
                  <span role="button" aria-disabled="true" data-intent="ghost">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Return Code
                  </span>
                <?php endif; ?>
                <?php if ($dueStatus === 'OVERDUE' || $dueStatus === 'DUE SOON'): ?>
                  <form method="post" action="/borrow/<?= (int) $row['id_bor'] ?>/remind">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" data-intent="warning">
                      <i class="fa-solid fa-bell" aria-hidden="true"></i> Send Reminder
                    </button>
                  </form>
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
