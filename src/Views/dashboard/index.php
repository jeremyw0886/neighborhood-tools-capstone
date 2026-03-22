<?php
/**
 * Dashboard shell — renders nav, header, and includes the active content partial.
 *
 * @var string  $dashboardSection   Section key (overview|lender|borrower|history|loan-status)
 * @var string  $dashboardPartial   Absolute path to the content partial
 * @var string  $backUrl            URL for the back link
 * @var ?string $loanStatusHeading  Tool name heading for loan-status view
 * @var ?string $loanStatusSubtitle Pre-built HTML subtitle for loan-status view
 */

$isOverview = ($dashboardSection === 'overview');
$isLoanStatus = ($dashboardSection === 'loan-status');

$sectionId = match($dashboardSection) {
    'overview' => 'dashboard-heading',
    'lender' => 'lender-heading',
    'borrower' => 'borrower-heading',
    'history' => 'history-heading',
    'loan-status' => 'loan-status-heading',
    default => 'dashboard-heading',
};

$sectionIcon = match($dashboardSection) {
    'overview' => 'fa-gauge',
    'lender' => 'fa-hand-holding',
    'borrower' => 'fa-hand',
    'history' => 'fa-clock-rotate-left',
    'loan-status' => 'fa-timeline',
    default => 'fa-gauge',
};

$sectionLabel = match($dashboardSection) {
    'overview' => 'Welcome, ' . htmlspecialchars($authUser['first_name']),
    'lender' => 'My Tools',
    'borrower' => 'My Borrows',
    'history' => 'Borrow History',
    'loan-status' => htmlspecialchars($loanStatusHeading ?? 'Loan Status'),
    default => 'Dashboard',
};

$sectionSubtitle = match($dashboardSection) {
    'overview' => 'Here&rsquo;s a snapshot of your NeighborhoodTools activity.',
    'lender' => 'Manage your listed tools and respond to incoming borrow requests.',
    'borrower' => 'Track your active borrows, pending requests, and overdue items.',
    'history' => 'Review your past lending and borrowing activity.',
    'loan-status' => null,
    default => null,
};
?>

<section<?= $isLoanStatus ? ' id="loan-status"' : '' ?> aria-labelledby="<?= $sectionId ?>">

  <?php if (!$isOverview): ?>
    <nav aria-label="Back">
      <a href="<?= htmlspecialchars($backUrl) ?>">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
      </a>
    </nav>
  <?php endif; ?>

  <header>
    <h1 id="<?= $sectionId ?>">
      <i class="fa-solid <?= $sectionIcon ?>" aria-hidden="true"></i>
      <?= $sectionLabel ?>
    </h1>
    <?php if ($isLoanStatus && isset($loanStatusSubtitle)): ?>
      <?= $loanStatusSubtitle ?>
    <?php elseif ($sectionSubtitle !== null): ?>
      <p><?= $sectionSubtitle ?></p>
    <?php endif; ?>
    <?php require BASE_PATH . '/src/Views/partials/tool-search.php'; ?>
  </header>

  <div data-dashboard-body>
    <?php require BASE_PATH . '/src/Views/partials/dashboard-nav.php'; ?>

    <div data-dashboard-content>
      <?php require $dashboardPartial; ?>
    </div>
  </div>

</section>
