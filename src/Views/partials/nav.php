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
    <?php include BASE_PATH . '/public/assets/images/logo.svg'; ?>
    <?php if (!$isHero): ?>
      <span>NeighborhoodTools</span>
    <?php endif; ?>
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
      <a href="/how-to"
         data-modal="how-to"<?= $currentPage === '/how-to' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-book" aria-hidden="true"></i> How To
      </a>
    </li>
    <li>
      <a href="/tos"
         data-modal="tos"<?= $currentPage === '/tos' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Service
      </a>
    </li>
    <li>
      <a href="/faq"
         data-modal="faq"<?= $currentPage === '/faq' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQs
      </a>
    </li>
    <li>
      <a href="/tools"<?= $currentPage === '/tools' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools
      </a>
    </li>
  </ul>

  <div id="hero-dropdown">
    <?php if ($isLoggedIn): ?>

      <span>Hello, <?= htmlspecialchars($authUser['first_name']) ?></span>

      <a href="/notifications"
         aria-label="Notifications<?= $unreadCount > 0 ? " ({$unreadCount} unread)" : '' ?>">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <?php if ($unreadCount > 0): ?>
          <span><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>

      <button id="hero-dropdown-toggle"
              type="button"
              aria-haspopup="true"
              aria-expanded="false"
              aria-label="More options">
        <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
      </button>

      <ul id="hero-dropdown-menu" role="menu">
        <li role="menuitem">
          <a href="/dashboard">
            <i class="fa-solid fa-gauge" aria-hidden="true"></i> Dashboard
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

      <button id="hero-dropdown-toggle"
              type="button"
              aria-haspopup="true"
              aria-expanded="false"
              aria-label="More options">
        <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
      </button>

      <ul id="hero-dropdown-menu" role="menu">
        <li role="menuitem">
          <a href="/register">
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Sign Up
          </a>
        </li>
        <li role="menuitem">
          <a href="/tools">
            <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools
          </a>
        </li>
      </ul>

    <?php endif; ?>
  </div>

</nav>
