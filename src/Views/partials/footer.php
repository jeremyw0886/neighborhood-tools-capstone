<?php
/**
 * Footer partial — shared across all pages.
 *
 * Variables from BaseController::getSharedData():
 *   $isLoggedIn  bool
 *   $authUser    ?array{id, name, first_name, role, avatar}
 *   $currentPage string  (current URI path)
 */
?>
<footer id="site-footer">

  <div>
    <div>
      <img src="/assets/images/logo-mark.svg" alt="NeighborhoodTools" width="40" height="40" loading="lazy">
      <p>Share tools, build community.</p>
      <p>Neighborhood tool sharing for local borrowing and lending.</p>
    </div>

    <nav aria-label="Explore">
      <h2>Explore</h2>
      <ul>
        <li><a href="/"<?= $currentPage === '/' ? ' aria-current="page"' : '' ?>>Home</a></li>
        <li><a href="/available"<?= $currentPage === '/available' ? ' aria-current="page"' : '' ?>>Browse Tools</a></li>
        <li><a href="/events"<?= str_starts_with($currentPage, '/events') ? ' aria-current="page"' : '' ?>>Events</a></li>
      </ul>
    </nav>

    <nav aria-label="About">
      <h2>About</h2>
      <ul>
        <li><a href="/how-to" data-modal="how-to"<?= $currentPage === '/how-to' ? ' aria-current="page"' : '' ?>>How It Works</a></li>
        <li><a href="/faq" data-modal="faq"<?= $currentPage === '/faq' ? ' aria-current="page"' : '' ?>>FAQs</a></li>
        <li><a href="/tos" data-modal="tos"<?= $currentPage === '/tos' ? ' aria-current="page"' : '' ?>>Terms of Service</a></li>
      </ul>
    </nav>

    <nav aria-label="Account">
      <h2>Account</h2>
      <ul>
        <?php if ($isLoggedIn): ?>
          <li><a href="/dashboard"<?= str_starts_with($currentPage, '/dashboard') ? ' aria-current="page"' : '' ?>>Dashboard</a></li>
          <li><a href="/profile/<?= htmlspecialchars((string) $authUser['id']) ?>"<?= str_starts_with($currentPage, '/profile') ? ' aria-current="page"' : '' ?>>My Profile</a></li>
          <li><a href="/bookmarks"<?= $currentPage === '/bookmarks' ? ' aria-current="page"' : '' ?>>Bookmarks</a></li>
        <?php else: ?>
          <li><a href="/login"<?= $currentPage === '/login' ? ' aria-current="page"' : '' ?>>Log In</a></li>
          <li><a href="/register"<?= $currentPage === '/register' ? ' aria-current="page"' : '' ?>>Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>

  <div>
    <p>&copy; <?= date('Y') ?> NeighborhoodTools</p>
  </div>

</footer>
