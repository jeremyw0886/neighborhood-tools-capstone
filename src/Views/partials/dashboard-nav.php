<?php
/**
 * Dashboard sub-navigation — shared across all dashboard pages.
 *
 * Uses $currentPage (from getSharedData()) to set aria-current="page"
 * on the active link. Included via require in each dashboard view.
 * Opens a data-dashboard-body wrapper that each view must close.
 */
?>
<div data-dashboard-body>
<nav aria-label="Dashboard navigation">
  <ul>
    <li><a href="/dashboard"<?= $currentPage === '/dashboard' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
    <li><a href="/dashboard/lender"<?= $currentPage === '/dashboard/lender' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
    <li><a href="/dashboard/borrower"<?= $currentPage === '/dashboard/borrower' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
    <li><a href="/dashboard/history"<?= $currentPage === '/dashboard/history' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    <li data-separator aria-hidden="true"></li>
    <li><a href="/profile/<?= htmlspecialchars((string) $authUser['id']) ?>"<?= str_starts_with($currentPage, '/profile') ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-id-card" aria-hidden="true"></i> My Profile</a></li>
    <li><a href="/bookmarks"<?= $currentPage === '/bookmarks' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-bookmark" aria-hidden="true"></i> Bookmarks</a></li>
    <li><a href="/events"<?= str_starts_with($currentPage, '/events') ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Events</a></li>
    <?php if (\App\Core\Role::tryFrom($authUser['role'])?->isAdmin()): ?>
      <li><a href="/admin"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Admin</a></li>
    <?php endif; ?>
  </ul>
</nav>
