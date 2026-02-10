<?php
/**
 * Dashboard — main overview with summary cards and quick actions.
 *
 * Variables from DashboardController::index():
 *   $activeBorrowCount   int     Active borrows (as borrower or lender)
 *   $pendingRequestCount int     Pending borrow requests
 *   $overdueCount        int     Overdue borrows
 *   $listedToolCount     int     Tools this user has listed
 *   $reputation          ?array  Row from user_reputation_fast_v
 *   $adminStats          ?array  {openDisputes, pendingDeposits, openIncidents} — admin only
 *
 * Shared data:
 *   $authUser    array{id, name, first_name, role, avatar}
 *   $csrfToken   string
 */

$overallRating = $reputation !== null ? round((float) $reputation['overall_avg_rating'], 1) : 0;
$totalRatings  = $reputation !== null ? (int) $reputation['total_rating_count'] : 0;
$starsFull     = (int) floor($overallRating);
$starsHalf     = ($overallRating - $starsFull) >= 0.5 ? 1 : 0;
$starsEmpty    = 5 - $starsFull - $starsHalf;
?>

<section aria-labelledby="dashboard-heading">

  <header>
    <h1 id="dashboard-heading">
      <i class="fa-solid fa-gauge" aria-hidden="true"></i>
      Welcome back, <?= htmlspecialchars($authUser['first_name']) ?>
    </h1>
    <p>Here&rsquo;s a snapshot of your NeighborhoodTools activity.</p>
  </header>

  <nav aria-label="Dashboard sections">
    <ul>
      <li><a href="/dashboard"         aria-current="page"><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
      <li><a href="/dashboard/lender"  ><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
      <li><a href="/dashboard/borrower"><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
      <li><a href="/dashboard/history" ><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    </ul>
  </nav>

  <section aria-labelledby="summary-heading">
    <h2 id="summary-heading" class="visually-hidden">Activity Summary</h2>

    <div role="list">

      <article role="listitem">
        <a href="/dashboard/borrower">
          <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
          <h3>Active Borrows</h3>
          <p><?= $activeBorrowCount ?></p>
          <span>View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

      <article role="listitem">
        <a href="/dashboard/lender">
          <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
          <h3>Pending Requests</h3>
          <p><?= $pendingRequestCount ?></p>
          <span>View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

      <?php if ($overdueCount > 0): ?>
        <article role="listitem" data-urgent>
          <a href="/dashboard/borrower">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <h3>Overdue</h3>
            <p><?= $overdueCount ?></p>
            <span>View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
          </a>
        </article>
      <?php endif; ?>

      <article role="listitem">
        <a href="/dashboard/lender">
          <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
          <h3>My Listed Tools</h3>
          <p><?= $listedToolCount ?></p>
          <span>View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

      <article role="listitem">
        <a href="/profile/<?= (int) $authUser['id'] ?>">
          <i class="fa-solid fa-star" aria-hidden="true"></i>
          <h3>My Rating</h3>
          <p>
            <?= $overallRating ?><span>/5</span>
          </p>
          <span>
            <?php for ($i = 0; $i < $starsFull; $i++): ?>
              <i class="fa-solid fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <?php if ($starsHalf): ?>
              <i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>
            <?php endif; ?>
            <?php for ($i = 0; $i < $starsEmpty; $i++): ?>
              <i class="fa-regular fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= $overallRating ?> out of 5 stars</span>
            (<?= $totalRatings ?> review<?= $totalRatings !== 1 ? 's' : '' ?>)
          </span>
        </a>
      </article>

    </div>
  </section>

  <?php if ($adminStats !== null): ?>
    <section aria-labelledby="admin-summary-heading">
      <h2 id="admin-summary-heading">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Admin Overview
      </h2>

      <div role="list">
        <article role="listitem">
          <a href="/admin/disputes">
            <i class="fa-solid fa-gavel" aria-hidden="true"></i>
            <h3>Open Disputes</h3>
            <p><?= $adminStats['openDisputes'] ?></p>
          </a>
        </article>

        <article role="listitem">
          <a href="/admin">
            <i class="fa-solid fa-vault" aria-hidden="true"></i>
            <h3>Pending Deposits</h3>
            <p><?= $adminStats['pendingDeposits'] ?></p>
          </a>
        </article>

        <article role="listitem">
          <a href="/admin/incidents">
            <i class="fa-solid fa-flag" aria-hidden="true"></i>
            <h3>Open Incidents</h3>
            <p><?= $adminStats['openIncidents'] ?></p>
          </a>
        </article>
      </div>
    </section>
  <?php endif; ?>

  <section aria-labelledby="quick-actions-heading">
    <h2 id="quick-actions-heading">Quick Actions</h2>
    <ul>
      <li>
        <a href="/tools/create">
          <i class="fa-solid fa-plus" aria-hidden="true"></i> List a Tool
        </a>
      </li>
      <li>
        <a href="/tools">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Browse Tools
        </a>
      </li>
      <li>
        <a href="/notifications">
          <i class="fa-solid fa-bell" aria-hidden="true"></i> Notifications
          <?php if (($unreadCount ?? 0) > 0): ?>
            <span>(<?= $unreadCount ?>)</span>
          <?php endif; ?>
        </a>
      </li>
    </ul>
  </section>

</section>
