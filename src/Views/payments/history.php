<?php

$typeLabels = [
    'deposit_release' => 'Release',
    'deposit_forfeit' => 'Forfeit',
    'deposit_hold'    => 'Hold',
    'rental_fee'      => 'Fee',
];

$paginationUrl = static fn(int $p): string => '/payments/history?page=' . $p;
?>

<section id="payment-history" aria-labelledby="history-heading">

  <nav aria-label="Back">
    <?php if ($isAdmin): ?>
      <a href="/admin">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Admin Dashboard
      </a>
    <?php else: ?>
      <a href="/dashboard">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
      </a>
    <?php endif; ?>
  </nav>

  <h1 id="history-heading">
    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
    Payment History
  </h1>

  <?php if ($transactions): ?>

    <ul aria-label="Transactions">
      <?php foreach ($transactions as $tx):
          $type      = $tx['transaction_type_ptx'];
          $label     = $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
          $amount    = number_format((float) $tx['amount_ptx'], 2);
          $provider  = htmlspecialchars($tx['payment_provider']);
          $tool      = htmlspecialchars($tx['tool_name_tol']);
          $from      = $tx['from_name'] ? htmlspecialchars($tx['from_name']) : '—';
          $to        = $tx['to_name'] ? htmlspecialchars($tx['to_name']) : '—';
          $date      = date('M j, Y \a\t g:i A', strtotime($tx['processed_at_ptx']));
          $datetime  = htmlspecialchars($tx['processed_at_ptx']);
      ?>
        <li data-type="<?= htmlspecialchars($type) ?>">
          <header>
            <span data-badge="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($label) ?></span>
            <strong>$<?= $amount ?></strong>
          </header>
          <dl>
            <div>
              <dt>Tool</dt>
              <dd><?= $tool ?></dd>
            </div>
            <div>
              <dt>From</dt>
              <dd><?= $from ?></dd>
            </div>
            <div>
              <dt>To</dt>
              <dd><?= $to ?></dd>
            </div>
            <div>
              <dt>Provider</dt>
              <dd><?= $provider ?></dd>
            </div>
          </dl>
          <footer>
            <time datetime="<?= $datetime ?>"><?= $date ?></time>
          </footer>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Pagination">
        <ul>

          <?php if ($page > 1): ?>
            <li>
              <a href="<?= $paginationUrl($page - 1) ?>"
                 aria-label="Go to previous page">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true" aria-label="No previous page">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </span>
            </li>
          <?php endif; ?>

          <?php
          $windowSize = 2;
          $startPage  = max(1, $page - $windowSize);
          $endPage    = min($totalPages, $page + $windowSize);

          if ($startPage > 1): ?>
            <li>
              <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
            </li>
            <?php if ($startPage > 2): ?>
              <li><span aria-hidden="true">…</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li>
              <?php if ($i === $page): ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-current="page"
                   aria-label="Page <?= $i ?>, current page"><?= $i ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">…</span></li>
            <?php endif; ?>
            <li>
              <a href="<?= $paginationUrl($totalPages) ?>"
                 aria-label="Go to page <?= $totalPages ?>"><?= $totalPages ?></a>
            </li>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <li>
              <a href="<?= $paginationUrl($page + 1) ?>"
                 aria-label="Go to next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true" aria-label="No next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>

    <p>No payment transactions found.</p>

  <?php endif; ?>

</section>
