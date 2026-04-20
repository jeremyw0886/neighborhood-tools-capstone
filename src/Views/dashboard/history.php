<?php
/**
 * Dashboard — History sub-page: past lending and borrowing activity.
 *
 * Variables from DashboardController::history():
 *
 * @var array                            $lenderHistory   Past borrows where user was the lender
 * @var array                            $borrowerHistory Past borrows where user was the borrower
 * @var array{sort: string, dir: string} $lendSort        Lending history sort state
 * @var ?string                          $lendStatus      Lending history status filter (returned|denied|cancelled|null)
 * @var array{sort: string, dir: string} $borrowSort      Borrowing history sort state
 * @var ?string                          $borrowStatus    Borrowing history status filter (returned|denied|cancelled|null)
 *
 * Shared data:
 *
 * @var array{id, name, first_name, role, avatar} $authUser
 */

use App\Core\ViewHelper;
?>

<section aria-labelledby="lending-history-heading">
    <h2 id="lending-history-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      Lending History
    </h2>

    <?php if (!empty($lenderHistory)): ?>
      <?php
        $paramPrefix = 'lend_';
        $sortOptions = [
            'requested_at_bor' => 'Date',
            'tool_name_tol' => 'Tool Name',
            'borrower_name' => 'Borrower',
            'borrow_status' => 'Status',
        ];
        $currentSort = $lendSort['sort'];
        $currentDir = strtolower($lendSort['dir']);
        $filterOptions = ['' => 'All', 'returned' => 'Returned', 'denied' => 'Denied', 'cancelled' => 'Cancelled'];
        $currentFilter = $lendStatus;
        $preserveParams = [
            'borrow_sort' => $borrowSort['sort'],
            'borrow_dir' => strtolower($borrowSort['dir']),
            'borrow_status' => $borrowStatus ?? '',
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

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
            $otherName  = $row['borrower_name'] ?? $row['other_party_name'] ?? '—';
            $otherId    = (int) ($row['borrower_id'] ?? $row['id_acc_bor'] ?? $row['other_party_id'] ?? 0);
          ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php if ($otherId > 0): ?>
                  <a href="/profile/<?= htmlspecialchars((string) $otherId) ?>">
                    <?= htmlspecialchars($otherName) ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($otherName) ?>
                <?php endif; ?>
              </td>
              <td>
                <span data-borrow-status="<?= htmlspecialchars($statusText) ?>"><?= htmlspecialchars($statusText) ?></span>
                <?php if ($statusText === 'returned' && !in_array((int) $row['id_bor'], $ratedBorrowIds, true)): ?>
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
      <?php
        $paramPrefix = 'borrow_';
        $sortOptions = [
            'requested_at_bor' => 'Date',
            'tool_name_tol' => 'Tool Name',
            'lender_name' => 'Lender',
            'borrow_status' => 'Status',
        ];
        $currentSort = $borrowSort['sort'];
        $currentDir = strtolower($borrowSort['dir']);
        $filterOptions = ['' => 'All', 'returned' => 'Returned', 'denied' => 'Denied', 'cancelled' => 'Cancelled'];
        $currentFilter = $borrowStatus;
        $preserveParams = [
            'lend_sort' => $lendSort['sort'],
            'lend_dir' => strtolower($lendSort['dir']),
            'lend_status' => $lendStatus ?? '',
        ];
      ?>
      <?php require BASE_PATH . '/src/Views/partials/sort-filter.php'; ?>

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
            $otherName  = $row['lender_name'] ?? $row['owner_name'] ?? $row['other_party_name'] ?? '—';
            $otherId    = (int) ($row['lender_id'] ?? $row['owner_id'] ?? $row['other_party_id'] ?? 0);
          ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php if ($otherId > 0): ?>
                  <a href="/profile/<?= htmlspecialchars((string) $otherId) ?>">
                    <?= htmlspecialchars($otherName) ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($otherName) ?>
                <?php endif; ?>
              </td>
              <td>
                <span data-borrow-status="<?= htmlspecialchars($statusText) ?>"><?= htmlspecialchars($statusText) ?></span>
                <?php if ($statusText === 'returned' && !in_array((int) $row['id_bor'], $ratedBorrowIds, true)): ?>
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
