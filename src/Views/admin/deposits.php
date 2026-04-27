<?php

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/deposits';

$sortLabels = [
    'amount_sdp'     => 'Amount',
    'deposit_status' => 'Status',
    'tool_name_tol'  => 'Tool',
    'borrower_name'  => 'Borrower',
    'lender_name'    => 'Lender',
    'days_held'      => 'Days Held',
    'created_at_sdp' => 'Created',
];

$sortToColumn = [
    'amount_sdp'     => 0,
    'deposit_status' => 1,
    'tool_name_tol'  => 2,
    'borrower_name'  => 3,
    'lender_name'    => 4,
    'days_held'      => 5,
    'created_at_sdp' => 6,
];

$ariaSortDir = $dir === 'ASC' ? 'ascending' : 'descending';
$hasFilters  = $search !== null || $status !== null || $action !== null || $incidentsOnly;

$allStatuses = ['pending', 'held', 'released', 'forfeited', 'partial_release'];
$allActions  = [
    'READY FOR RELEASE', 'OVERDUE - REVIEW NEEDED', 'ACTIVE BORROW',
    'PAYMENT PENDING', 'RELEASED', 'FORFEITED', 'PARTIAL RELEASE', 'REVIEW NEEDED',
];
?>

  <form method="get" action="/admin/deposits" role="search" aria-label="Filter and sort deposits" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort deposits</legend>

      <div>
        <label for="deposits-search">Search</label>
        <input type="search" id="deposits-search" name="q"
          value="<?= htmlspecialchars($search ?? '') ?>"
          placeholder="Tool, borrower, or lender…"
          autocomplete="off"
          data-suggest="admin" data-suggest-type="deposits">
      </div>

      <div>
        <label for="deposits-status">Status</label>
        <select id="deposits-status" name="status">
          <option value="">All Statuses</option>
          <?php foreach ($allStatuses as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>"<?= $status === $s ? ' selected' : '' ?>>
              <?= htmlspecialchars(ucwords(str_replace('_', ' ', $s))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="deposits-action">Action Required</label>
        <select id="deposits-action" name="action">
          <option value="">All Actions</option>
          <?php foreach ($allActions as $a): ?>
            <option value="<?= htmlspecialchars($a) ?>"<?= $action === $a ? ' selected' : '' ?>>
              <?= htmlspecialchars($a) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="deposits-sort">Sort By</label>
        <select id="deposits-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="deposits-dir">Direction</label>
        <select id="deposits-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <div>
        <input type="checkbox" id="deposits-incidents" name="incidents" value="1"<?= $incidentsOnly ? ' checked' : '' ?>>
        <label for="deposits-incidents">Incidents only</label>
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
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        deposit<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($deposits)): ?>

    <table>
      <caption class="visually-hidden">Security deposits and their status</caption>
      <thead>
        <tr>
          <?php
          $columns = ['Amount', 'Status', 'Tool', 'Borrower', 'Lender', 'Days Held', 'Created', 'Action'];
          foreach ($columns as $i => $label):
            $isSorted = isset($sortToColumn[$sort]) && $sortToColumn[$sort] === $i;
          ?>
            <th scope="col"<?= $isSorted ? ' aria-sort="' . $ariaSortDir . '"' : '' ?>><?= $label ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deposits as $deposit):
          $incidents = (int) $deposit['incident_count'];
        ?>
          <tr<?= $incidents > 0 ? ' data-has-incidents' : '' ?>>
            <td data-label="Amount">
              <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">
                $<?= number_format((float) $deposit['amount_sdp'], 2) ?>
              </a>
              <?php if ($deposit['forfeited_amount_sdp'] !== null): ?>
                <small>Forfeited: $<?= number_format((float) $deposit['forfeited_amount_sdp'], 2) ?></small>
              <?php endif; ?>
            </td>
            <td data-label="Status">
              <span data-deposit-status="<?= htmlspecialchars($deposit['deposit_status']) ?>">
                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $deposit['deposit_status']))) ?>
              </span>
            </td>
            <td data-label="Tool">
              <a href="/tools/<?= (int) $deposit['id_tol'] ?>">
                <?= htmlspecialchars($deposit['tool_name_tol']) ?>
              </a>
            </td>
            <td data-label="Borrower">
              <a href="/profile/<?= (int) $deposit['borrower_id'] ?>">
                <?= htmlspecialchars($deposit['borrower_name']) ?>
              </a>
            </td>
            <td data-label="Lender">
              <a href="/profile/<?= (int) $deposit['lender_id'] ?>">
                <?= htmlspecialchars($deposit['lender_name']) ?>
              </a>
            </td>
            <td data-label="Days Held">
              <?php if ($deposit['days_held'] !== null): ?>
                <?= (int) $deposit['days_held'] ?>
              <?php else: ?>
                <span title="Not yet held">&mdash;</span>
              <?php endif; ?>
            </td>
            <td data-label="Created">
              <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($deposit['created_at_sdp']))) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($deposit['created_at_sdp']))) ?>
              </time>
            </td>
            <td data-label="Action">
              <span data-action-required="<?= htmlspecialchars($deposit['action_required']) ?>">
                <?= htmlspecialchars($deposit['action_required']) ?>
              </span>
              <?php if ($incidents > 0): ?>
                <small data-warning>
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                  <?= $incidents ?> incident<?= $incidents !== 1 ? 's' : '' ?>
                </small>
              <?php endif; ?>
              <?php if (in_array($deposit['action_required'], ['READY FOR RELEASE', 'OVERDUE - REVIEW NEEDED'], true)): ?>
                <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>"
                   data-intent="primary" data-shape="pill">
                  Process
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <div role="status">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Deposits Found</h2>
      <p>No deposits match the current criteria.</p>
      <?php if ($hasFilters): ?>
        <a href="/admin/deposits" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary" data-back>
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
        </a>
      <?php endif; ?>
    </div>

  <?php endif; ?>
