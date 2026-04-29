<?php
/**
 * Dashboard shell — renders nav, header, and includes the active content partial.
 *
 * @var string  $dashboardSection   Section key (overview|lender|borrower|history|loan-status|list-tool|edit-tool|bookmarks|events|profile|profile-edit)
 * @var string  $dashboardPartial   Absolute path to the content partial
 * @var string  $backUrl            URL for the back link
 * @var ?string $loanStatusHeading  Tool name heading for loan-status view
 * @var ?array{relationLabel: string, counterpartyId: int, counterpartyName: string, statusLabel: string, statusSlug: string} $loanSubtitle Subtitle fields for loan-status view
 *
 * Shared data:
 *
 * @var array{id, name, first_name, role, avatar} $authUser
 */

$isOverview = ($dashboardSection === 'overview');
$isLoanStatus = ($dashboardSection === 'loan-status');

$sectionId = match($dashboardSection) {
    'overview' => 'dashboard-heading',
    'lender' => 'lender-heading',
    'borrower' => 'borrower-heading',
    'history' => 'history-heading',
    'loans' => 'loans-heading',
    'loan-status' => 'loan-status-heading',
    'list-tool' => 'create-tool-heading',
    'edit-tool' => 'edit-tool-heading',
    'bookmarks' => 'bookmarks-heading',
    'events' => 'events-heading',
    'profile' => 'profile-heading',
    'profile-edit' => 'edit-profile-heading',
    default => 'dashboard-heading',
};

$sectionHtmlId = match($dashboardSection) {
    'loans' => 'my-loans',
    'loan-status' => 'loan-status',
    'list-tool' => 'list-tool',
    'edit-tool' => 'edit-tool',
    'bookmarks' => 'bookmarks-page',
    'events' => 'events-page',
    'profile-edit' => 'profile-edit',
    default => null,
};

$sectionIcon = match($dashboardSection) {
    'overview' => 'fa-gauge',
    'lender' => 'fa-hand-holding',
    'borrower' => 'fa-hand',
    'history' => 'fa-clock-rotate-left',
    'loans' => 'fa-handshake',
    'loan-status' => 'fa-timeline',
    'list-tool' => 'fa-plus',
    'edit-tool' => 'fa-pen-to-square',
    'bookmarks' => 'fa-bookmark',
    'events' => 'fa-calendar-days',
    'profile' => 'fa-id-card',
    'profile-edit' => 'fa-user-pen',
    default => 'fa-gauge',
};

$sectionLabel = match($dashboardSection) {
    'overview' => 'Welcome, ' . htmlspecialchars($authUser['first_name']),
    'lender' => 'My Tools',
    'borrower' => 'My Borrows',
    'history' => 'Borrow History',
    'loans' => 'My Loans',
    'loan-status' => htmlspecialchars($loanStatusHeading ?? 'Loan Status'),
    'list-tool' => 'List a Tool',
    'edit-tool' => 'Edit Tool',
    'bookmarks' => 'My Bookmarks',
    'events' => 'Community Events',
    'profile' => 'My Profile',
    'profile-edit' => 'Edit Profile',
    default => 'Dashboard',
};

$sectionSubtitle = match($dashboardSection) {
    'overview' => 'Here&rsquo;s a snapshot of your NeighborhoodTools activity.',
    'lender' => 'Manage your listed tools and respond to incoming borrow requests.',
    'borrower' => 'Track your active borrows, pending requests, and overdue items.',
    'history' => 'Review your past lending and borrowing activity.',
    'loans' => 'Track all your active and recent loans in one place.',
    'list-tool' => 'Share your tools with your neighbors. Fill out the details below to get started.',
    'edit-tool' => isset($tool) ? 'Update the details for <strong>' . htmlspecialchars($tool['tool_name_tol']) . '</strong>.' : null,
    'bookmarks' => 'Tools you&rsquo;ve saved for later.',
    'events' => 'Discover upcoming events in the Asheville and Hendersonville neighborhoods.',
    'profile' => 'Your public profile, ratings, and listed tools.',
    'profile-edit' => 'Update your personal information and profile details.',
    default => null,
};
?>

<section<?= $sectionHtmlId !== null ? ' id="' . $sectionHtmlId . '"' : '' ?> aria-labelledby="<?= $sectionId ?>">

  <nav aria-label="Back"<?= $isOverview ? ' aria-hidden="true" data-hidden' : '' ?>>
    <a href="<?= htmlspecialchars($backUrl) ?>"<?= $isOverview ? ' tabindex="-1"' : '' ?>>
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="<?= $sectionId ?>">
      <i class="fa-solid <?= $sectionIcon ?>" aria-hidden="true"></i>
      <?= $sectionLabel ?>
    </h1>
    <?php if ($isLoanStatus && isset($loanSubtitle)): ?>
      <?php require BASE_PATH . '/src/Views/partials/loan-subtitle.php'; ?>
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
