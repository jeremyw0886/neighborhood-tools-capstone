<?php
/**
 * Dashboard — History sub-page: past lending and borrowing activity.
 *
 * Variables from DashboardController::history():
 *   $lenderHistory   array  Past borrows where user was the lender
 *   $borrowerHistory array  Past borrows where user was the borrower
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */
?>

<section aria-labelledby="history-heading">

  <header>
    <h1 id="history-heading">
      <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
      Borrow History
    </h1>
    <p>Review your past lending and borrowing activity.</p>
  </header>

  <nav aria-label="Dashboard sections">
    <ul>
      <li><a href="/dashboard"         ><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
      <li><a href="/dashboard/lender"  ><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
      <li><a href="/dashboard/borrower"><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
      <li><a href="/dashboard/history"  aria-current="page"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    </ul>
  </nav>

  <section aria-labelledby="lending-history-heading">
    <h2 id="lending-history-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      Lending History
    </h2>

    <?php if (!empty($lenderHistory)): ?>
      <table>
        <caption class="visually-hidden">Your past activity as a lender</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Borrower</th>
            <th scope="col">Status</th>
            <th scope="col">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lenderHistory as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php $borrowerId = (int) ($row['borrower_id'] ?? $row['id_acc_bor'] ?? 0); ?>
                <?php if ($borrowerId > 0): ?>
                  <a href="/profile/<?= $borrowerId ?>">
                    <?= htmlspecialchars($row['borrower_name'] ?? '—') ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($row['borrower_name'] ?? '—') ?>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['borrow_status'] ?? $row['status_name_bst'] ?? '—') ?></td>
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
      <table>
        <caption class="visually-hidden">Your past activity as a borrower</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Lender</th>
            <th scope="col">Status</th>
            <th scope="col">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrowerHistory as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['tool_name_tol'] ?? $row['tool_name'] ?? '—') ?></td>
              <td>
                <?php $lenderId = (int) ($row['lender_id'] ?? $row['owner_id'] ?? 0); ?>
                <?php if ($lenderId > 0): ?>
                  <a href="/profile/<?= $lenderId ?>">
                    <?= htmlspecialchars($row['lender_name'] ?? $row['owner_name'] ?? '—') ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($row['lender_name'] ?? $row['owner_name'] ?? '—') ?>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['borrow_status'] ?? $row['status_name_bst'] ?? '—') ?></td>
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

</section>
