<?php

/**
 * Navigation partial — shared across all pages.
 *
 * Two visual modes controlled by $heroPage:
 *   Hero mode  ($heroPage = true):  Glass over gradient, shared grid geometry, gold accents
 *   Standard   ($heroPage = false): Solid --mountain-pine background
 *
 * Variables from BaseController::getSharedData():
 *
 * @var bool                                       $isLoggedIn
 * @var ?array{id, name, first_name, role, avatar} $authUser
 * @var string                                     $csrfToken
 * @var string                                     $currentPage (current URI path)
 * @var int                                        $unreadCount (unread notification count for badge)
 * @var bool                                       $heroPage    (set by controller, absent/false for standard pages)
 */

$isHero = !empty($heroPage);
$navLogoSrc = '/assets/images/logo.svg';
$navLogoWidth = 120;
$navLogoHeight = 120;

$userMenuItems = $isLoggedIn ? array_values(array_filter([
  [
    'href'   => '/dashboard',
    'label'  => 'Dashboard',
    'icon'   => 'fa-gauge',
    'active' => str_starts_with($currentPage, '/dashboard'),
  ],
  [
    'href'   => '/profile/' . $authUser['id'],
    'label'  => 'My Profile',
    'icon'   => 'fa-id-card',
    'active' => str_starts_with($currentPage, '/profile'),
  ],
  [
    'href'   => '/bookmarks',
    'label'  => 'Bookmarks',
    'icon'   => 'fa-bookmark',
    'active' => $currentPage === '/bookmarks',
  ],
  [
    'href'   => '/events',
    'label'  => 'Events',
    'icon'   => 'fa-calendar-days',
    'active' => str_starts_with($currentPage, '/events'),
  ],
  \App\Core\Role::tryFrom($authUser['role'])?->isAdmin() ? [
    'href'   => '/admin',
    'label'  => 'Admin',
    'icon'   => 'fa-shield-halved',
    'active' => str_starts_with($currentPage, '/admin'),
  ] : null,
])) : [];
?>
<nav aria-label="Main navigation" <?= $isHero ? ' data-hero' : '' ?>>
  <a href="/" aria-label="NeighborhoodTools home">
    <img src="<?= htmlspecialchars($navLogoSrc) ?>" alt="" width="<?= $navLogoWidth ?>" height="<?= $navLogoHeight ?>" fetchpriority="high">
  </a>

  <button id="mobile-menu-toggle"
    type="button"
    aria-label="Toggle navigation menu"
    aria-expanded="false"
    aria-controls="top-links">
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
  </button>

  <ul id="top-links">
    <li>
      <a href="/" <?= $currentPage === '/' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-house" aria-hidden="true"></i> Home
      </a>
    </li>
    <li>
      <a href="/available" <?= $currentPage === '/available' || $currentPage === '/tools' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools
      </a>
    </li>
    <li data-separator aria-hidden="true"></li>
    <li>
      <a href="/how-to"
        data-modal="how-to" <?= $currentPage === '/how-to' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-book" aria-hidden="true"></i> How To
      </a>
    </li>
    <li>
      <a href="/tos"
        data-modal="tos" <?= $currentPage === '/tos' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Legal
      </a>
    </li>
    <li>
      <a href="/faq"
        data-modal="faq" <?= $currentPage === '/faq' ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQs
      </a>
    </li>

    <?php if ($isLoggedIn): ?>
      <li data-mobile-auth data-separator aria-hidden="true"></li>
      <li data-mobile-auth>
        <span>
          <?php if ($authUser['nav_avatar'] !== null): ?>
            <img src="<?= htmlspecialchars($authUser['nav_avatar']) ?>" alt="" width="28" height="28" decoding="async">
          <?php else: ?>
            <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
          <?php endif; ?>
          Hello, <?= htmlspecialchars($authUser['first_name']) ?>
        </span>
      </li>

      <?php foreach ($userMenuItems as $item): ?>
        <li data-mobile-auth>
          <a href="<?= htmlspecialchars($item['href']) ?>" <?= $item['active'] ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($item['label']) ?>
          </a>
        </li>
      <?php endforeach; ?>

      <li data-mobile-auth>
        <a href="/notifications" <?= $currentPage === '/notifications' ? ' aria-current="page"' : '' ?>>
          <i class="fa-solid fa-bell" aria-hidden="true"></i>
          Notifications<?= $unreadCount > 0 ? ' (' . htmlspecialchars((string) $unreadCount) . ')' : '' ?>
        </a>
      </li>
      <li data-mobile-auth>
        <form action="/logout" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="submit" data-intent="ghost">
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout
          </button>
        </form>
      </li>
    <?php else: ?>
      <li data-mobile-auth data-separator aria-hidden="true"></li>
      <li data-mobile-auth>
        <a href="/login" <?= $currentPage === '/login' ? ' aria-current="page"' : '' ?>>
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log In
        </a>
      </li>
      <li data-mobile-auth>
        <a href="/register" <?= $currentPage === '/register' ? ' aria-current="page"' : '' ?>>
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Sign Up
        </a>
      </li>
    <?php endif; ?>
  </ul>

  <div id="user-actions">
    <?php if ($isLoggedIn): ?>

      <button id="user-actions-toggle"
        type="button"
        aria-haspopup="true"
        aria-controls="user-actions-menu"
        aria-expanded="false">
        <?php if ($authUser['nav_avatar'] !== null): ?>
          <img src="<?= htmlspecialchars($authUser['nav_avatar']) ?>"
            alt=""
            width="28" height="28"
            decoding="async">
        <?php else: ?>
          <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
        <?php endif; ?>
        Hello, <?= htmlspecialchars($authUser['first_name']) ?>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>

      <div id="bell-wrapper">
        <a href="/notifications"
          id="bell-toggle"
          aria-controls="bell-dropdown"
          aria-haspopup="menu"
          aria-expanded="false"
          aria-label="Notifications<?= $unreadCount > 0 ? ' (' . htmlspecialchars((string) $unreadCount) . ' unread)' : '' ?>">
          <i class="fa-solid fa-bell" aria-hidden="true"></i>
          <?php if ($unreadCount > 0): ?>
            <span><?= htmlspecialchars((string) $unreadCount) ?></span>
          <?php endif; ?>
        </a>

        <div id="bell-dropdown" role="menu" aria-label="Recent notifications" hidden>
          <ul>
          </ul>
          <p hidden>You&rsquo;re all caught up!</p>
          <a href="/notifications" role="menuitem">
            View all notifications
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <ul id="user-actions-menu" role="menu" hidden>
        <?php foreach ($userMenuItems as $item): ?>
          <li role="menuitem">
            <a href="<?= htmlspecialchars($item['href']) ?>" <?= $item['active'] ? ' aria-current="page"' : '' ?>>
              <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
              <?= htmlspecialchars($item['label']) ?>
            </a>
          </li>
        <?php endforeach; ?>
        <li role="menuitem">
          <form action="/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit" data-intent="ghost">
              <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout
            </button>
          </form>
        </li>
      </ul>

    <?php else: ?>

      <a href="/login" role="button" data-intent="primary">
        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log In
      </a>

      <?php if ($currentPage === '/tools' || $currentPage === '/available'): ?>
        <a href="/register">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Sign Up
        </a>
      <?php endif; ?>

    <?php endif; ?>
  </div>

</nav>