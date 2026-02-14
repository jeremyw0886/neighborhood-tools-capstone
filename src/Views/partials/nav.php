<?php
/**
 * Navigation partial — shared across all pages.
 *
 * Two visual modes controlled by $heroPage:
 *   Hero mode  ($heroPage = true):  Transparent over gradient, logo only, gold accents
 *   Standard   ($heroPage = false): Solid --mountain-pine background, logo + site name
 *
 * Variables from BaseController::getSharedData():
 *   $isLoggedIn  bool
 *   $authUser    ?array{id, name, first_name, role, avatar}
 *   $csrfToken   string
 *   $currentPage string  (current URI path)
 *   $unreadCount int     (unread notification count for badge)
 *   $heroPage    bool    (set by controller, absent/false for standard pages)
 */

$isHero = !empty($heroPage);
?>
<nav aria-label="Main navigation"<?= $isHero ? ' data-hero' : '' ?>>

  <a href="/" aria-label="NeighborhoodTools home">
    <img src="/assets/images/logo.svg" alt="NeighborhoodTools logo" width="80" height="80" fetchpriority="high">
  </a>

  <button id="mobile-menu-toggle"
          type="button"
          aria-label="Toggle navigation menu"
          aria-expanded="false"
          aria-controls="top-links">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
  </button>

  <ul id="top-links" role="list">
    <li>
      <a href="/"<?= $currentPage === '/' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-house" aria-hidden="true"></i> Home
      </a>
    </li>
    <li>
      <a href="/tools"<?= $currentPage === '/tools' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Tools
      </a>
    </li>
    <li role="separator" aria-hidden="true"></li>
    <li>
      <a href="/how-to"
         data-modal="how-to"<?= $currentPage === '/how-to' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-book" aria-hidden="true"></i> How To
      </a>
    </li>
    <li>
      <a href="/tos"
         data-modal="tos"<?= $currentPage === '/tos' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Legal
      </a>
    </li>
    <li>
      <a href="/faq"
         data-modal="faq"<?= $currentPage === '/faq' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQs
      </a>
    </li>
  </ul>

  <div id="user-actions">
    <?php if ($isLoggedIn): ?>

      <button id="user-actions-toggle"
              type="button"
              aria-haspopup="true"
              aria-expanded="false">
        <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
        Hello, <?= htmlspecialchars($authUser['first_name']) ?>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>

      <a href="/notifications"
         aria-label="Notifications<?= $unreadCount > 0 ? " ({$unreadCount} unread)" : '' ?>">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <?php if ($unreadCount > 0): ?>
          <span><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>

      <ul id="user-actions-menu" role="menu" hidden>
        <li role="menuitem">
          <a href="/dashboard">
            <i class="fa-solid fa-gauge" aria-hidden="true"></i> Dashboard
          </a>
        </li>
        <li role="menuitem">
          <a href="/bookmarks"<?= $currentPage === '/bookmarks' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-bookmark" aria-hidden="true"></i> Bookmarks
          </a>
        </li>
        <?php if (\App\Core\Role::tryFrom($authUser['role'])?->isAdmin()): ?>
          <li role="menuitem">
            <a href="/admin">
              <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Admin
            </a>
          </li>
        <?php endif; ?>
        <li role="menuitem">
          <form action="/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit">
              <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout
            </button>
          </form>
        </li>
      </ul>

    <?php else: ?>

      <a href="/login" role="button">
        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log in
      </a>

      <?php if ($currentPage === '/tools'): ?>
        <a href="/register">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Sign Up
        </a>
      <?php endif; ?>

    <?php endif; ?>
  </div>

</nav>
