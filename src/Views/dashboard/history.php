<?php
/**
 * Dashboard — History sub-page: past lending and borrowing activity.
 *
 * Variables from DashboardController::history():
 *   $lenderHistory   array  Past borrows where user was the lender
 *   $borrowerHistory array  Past borrows where user was the borrower
 *   $lendSort        array{sort: string, dir: string}  Lending history sort state
 *   $lendStatus      ?string  Lending history status filter (returned|denied|cancelled|null)
 *   $borrowSort      array{sort: string, dir: string}  Borrowing history sort state
 *   $borrowStatus    ?string  Borrowing history status filter (returned|denied|cancelled|null)
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

use App\Core\ViewHelper;
?>

<section aria-labelledby="history-heading">

  <header>
    <h1 id="history-heading">
      <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
      Borrow History
    </h1>
    <p>Review your past lending and borrowing activity.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/dashboard-nav.php'; ?>

  <section aria-labelledby="lending-history-heading">
    <h2 id="lending-history-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      Lending History
    </h2>

    <?php if (!empty($lenderHistory)): ?>
      <form method="get" action="/dashboard/history" aria-label="Sort and filter lending history">
        <fieldset>
          <legend class="visually-hidden">Sort and filter options</legend>
          <input type="hidden" name="borrow_sort" value="<?= htmlspecialchars($borrowSort['sort']) ?>">
          <input type="hidden" name="borrow_dir" value="<?= htmlspecialchars(strtolower($borrowSort['dir'])) ?>">
          <?php if ($borrowStatus !== null): ?>
            <input type="hidden" name="borrow_status" value="<?= htmlspecialchars($borrowStatus) ?>">
          <?php endif; ?>
          <label>
            Sort by
            <select name="lend_sort">
              <option value="requested_at_bor"<?= ViewHelper::selected($lendSort['sort'], 'requested_at_bor') ?>>Date</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($lendSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="borrower_name"<?= ViewHelper::selected($lendSort['sort'], 'borrower_name') ?>>Borrower</option>
              <option value="borrow_status"<?= ViewHelper::selected($lendSort['sort'], 'borrow_status') ?>>Status</option>
            </select>
          </label>
          <label>
            Direction
            <select name="lend_dir">
              <option value="desc"<?= ViewHelper::selected(strtolower($lendSort['dir']), 'desc') ?>>Newest First</option>
              <option value="asc"<?= ViewHelper::selected(strtolower($lendSort['dir']), 'asc') ?>>Oldest First</option>
            </select>
          </label>
          <label>
            Status
            <select name="lend_status">
              <option value=""<?= $lendStatus === null ? ' selected' : '' ?>>All</option>
              <option value="returned"<?= ViewHelper::selected($lendStatus ?? '', 'returned') ?>>Returned</option>
              <option value="denied"<?= ViewHelper::selected($lendStatus ?? '', 'denied') ?>>Denied</option>
              <option value="cancelled"<?= ViewHelper::selected($lendStatus ?? '', 'cancelled') ?>>Cancelled</option>
            </select>
          </label>
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Your past activity as a lender</caption>
        <thead>
          <tr>
            <th scope="col"<?= ViewHelper::ariaSort($lendSort['sort'], $lendSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= ViewHelper::ariaSort($lendSort['sort'], $lendSort['dir'], 'borrower_name') ?>>Borrower</th>
            <th scope="col"<?= ViewHelper::ariaSort($lendSort['sort'], $lendSort['dir'], 'borrow_status') ?>>Status</th>
            <th scope="col"<?= ViewHelper::ariaSort($lendSort['sort'], $lendSort['dir'], 'requested_at_bor') ?>>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lenderHistory as $row):
            $statusText = $row['borrow_status'] ?? $row['status_name_bst'] ?? $row['status'] ?? '—';
          ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php $borrowerId = (int) ($row['borrower_id'] ?? $row['id_acc_bor'] ?? 0); ?>
                <?php if ($borrowerId > 0): ?>
                  <a href="/profile/<?= htmlspecialchars((string) $borrowerId) ?>">
                    <?= htmlspecialchars($row['borrower_name'] ?? '—') ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($row['borrower_name'] ?? '—') ?>
                <?php endif; ?>
              </td>
              <td>
                <?= htmlspecialchars($statusText) ?>
                <?php if ($statusText === 'returned'): ?>
                  <a href="/rate/<?= (int) $row['id_bor'] ?>" data-rate-link>
                    <i class="fa-solid fa-star" aria-hidden="true"></i> Rate
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <?php $date = $row['returned_at_bor'] ?? $row['requested_at_bor'] ?? $row['created_at_bor'] ?? null; ?>
                <?php if ($date): ?>
                  <time datetime="<?= htmlspecialchars($date) ?>">
                    <?= htmlspecialchars(date('M j, Y', strtotime($date))) ?>
                  </time>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No lending history yet. <a href="/tools/create">List a tool</a> to get started.</p>
    <?php endif; ?>
  </section>

  <section aria-labelledby="borrowing-history-heading">
    <h2 id="borrowing-history-heading">
      <i class="fa-solid fa-hand" aria-hidden="true"></i>
      Borrowing History
    </h2>

    <?php if (!empty($borrowerHistory)): ?>
      <form method="get" action="/dashboard/history" aria-label="Sort and filter borrowing history">
        <fieldset>
          <legend class="visually-hidden">Sort and filter options</legend>
          <input type="hidden" name="lend_sort" value="<?= htmlspecialchars($lendSort['sort']) ?>">
          <input type="hidden" name="lend_dir" value="<?= htmlspecialchars(strtolower($lendSort['dir'])) ?>">
          <?php if ($lendStatus !== null): ?>
            <input type="hidden" name="lend_status" value="<?= htmlspecialchars($lendStatus) ?>">
          <?php endif; ?>
          <label>
            Sort by
            <select name="borrow_sort">
              <option value="requested_at_bor"<?= ViewHelper::selected($borrowSort['sort'], 'requested_at_bor') ?>>Date</option>
              <option value="tool_name_tol"<?= ViewHelper::selected($borrowSort['sort'], 'tool_name_tol') ?>>Tool Name</option>
              <option value="lender_name"<?= ViewHelper::selected($borrowSort['sort'], 'lender_name') ?>>Lender</option>
              <option value="borrow_status"<?= ViewHelper::selected($borrowSort['sort'], 'borrow_status') ?>>Status</option>
            </select>
          </label>
          <label>
            Direction
            <select name="borrow_dir">
              <option value="desc"<?= ViewHelper::selected(strtolower($borrowSort['dir']), 'desc') ?>>Newest First</option>
              <option value="asc"<?= ViewHelper::selected(strtolower($borrowSort['dir']), 'asc') ?>>Oldest First</option>
            </select>
          </label>
          <label>
            Status
            <select name="borrow_status">
              <option value=""<?= $borrowStatus === null ? ' selected' : '' ?>>All</option>
              <option value="returned"<?= ViewHelper::selected($borrowStatus ?? '', 'returned') ?>>Returned</option>
              <option value="denied"<?= ViewHelper::selected($borrowStatus ?? '', 'denied') ?>>Denied</option>
              <option value="cancelled"<?= ViewHelper::selected($borrowStatus ?? '', 'cancelled') ?>>Cancelled</option>
            </select>
          </label>
          <button type="submit">Sort</button>
        </fieldset>
      </form>

      <table>
        <caption class="visually-hidden">Your past activity as a borrower</caption>
        <thead>
          <tr>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'tool_name_tol') ?>>Tool</th>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'lender_name') ?>>Lender</th>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'borrow_status') ?>>Status</th>
            <th scope="col"<?= ViewHelper::ariaSort($borrowSort['sort'], $borrowSort['dir'], 'requested_at_bor') ?>>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrowerHistory as $row):
            $statusText = $row['borrow_status'] ?? $row['status_name_bst'] ?? $row['status'] ?? '—';
          ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php $lenderId = (int) ($row['lender_id'] ?? $row['owner_id'] ?? 0); ?>
                <?php if ($lenderId > 0): ?>
                  <a href="/profile/<?= htmlspecialchars((string) $lenderId) ?>">
                    <?= htmlspecialchars($row['lender_name'] ?? $row['owner_name'] ?? '—') ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($row['lender_name'] ?? $row['owner_name'] ?? '—') ?>
                <?php endif; ?>
              </td>
              <td>
                <?= htmlspecialchars($statusText) ?>
                <?php if ($statusText === 'returned'): ?>
                  <a href="/rate/<?= (int) $row['id_bor'] ?>" data-rate-link>
                    <i class="fa-solid fa-star" aria-hidden="true"></i> Rate
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <?php $date = $row['returned_at_bor'] ?? $row['requested_at_bor'] ?? $row['created_at_bor'] ?? null; ?>
                <?php if ($date): ?>
                  <time datetime="<?= htmlspecialchars($date) ?>">
                    <?= htmlspecialchars(date('M j, Y', strtotime($date))) ?>
                  </time>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No borrowing history yet. <a href="/tools">Browse tools</a> to find something to borrow.</p>
    <?php endif; ?>
  </section>

</div>
</section>
