<?php
/**
 * Notifications — paginated list with read/unread styling and mark-as-read.
 *
 * Variables from NotificationController::index():
 *   $notifications  array   Rows from Notification::getForUser()
 *   $totalCount     int     Total notifications (for pagination)
 *   $page           int     Current page (1-based)
 *   $totalPages     int     Total pages
 *   $perPage        int     Results per page (12)
 *
 * Shared data:
 *   $authUser    array{id, name, first_name, role, avatar}
 *   $csrfToken   string
 *   $unreadCount int
 */

/**
 * Map notification type to Font Awesome icon class.
 */
$typeIcon = static fn(string $type): string => match ($type) {
    'request'  => 'fa-hand',
    'approval' => 'fa-circle-check',
    'due'      => 'fa-clock',
    'return'   => 'fa-rotate-left',
    'rating'   => 'fa-star',
    default    => 'fa-bell',
};

/**
 * Format a timestamp into human-readable relative time.
 *
 * Timezone is set to America/New_York in index.php.
 */
$relativeTime = static function (string $timestamp): string {
    $now  = new DateTimeImmutable('now');
    $then = new DateTimeImmutable($timestamp);
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60) {
        return 'Just now';
    }

    $minutes = (int) floor($diff / 60);
    if ($minutes < 60) {
        return $minutes === 1 ? '1 minute ago' : "{$minutes} minutes ago";
    }

    $hours = (int) floor($diff / 3600);
    if ($hours < 24) {
        return $hours === 1 ? '1 hour ago' : "{$hours} hours ago";
    }

    $days = (int) floor($diff / 86400);
    if ($days === 1) {
        return 'Yesterday';
    }
    if ($days < 7) {
        return "{$days} days ago";
    }

    // Beyond a week, show the date (e.g. "Jan 15" or "Jan 15, 2025" if different year)
    return $then->format('Y') === $now->format('Y')
        ? $then->format('M j')
        : $then->format('M j, Y');
};

// Pagination range display
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

/**
 * Build a pagination URL preserving the current page.
 */
$paginationUrl = static fn(int $pageNum): string =>
    '/notifications' . ($pageNum > 1 ? '?page=' . $pageNum : '');
?>

<section aria-labelledby="notifications-heading">

  <header>
    <h1 id="notifications-heading">
      <i class="fa-solid fa-bell" aria-hidden="true"></i> Notifications
    </h1>

    <?php if ($unreadCount > 0): ?>
      <form action="/notifications/read" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="page" value="<?= $page ?>">
        <button type="submit">
          <i class="fa-solid fa-check-double" aria-hidden="true"></i> Mark all as read
        </button>
      </form>
    <?php endif; ?>
  </header>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        notification<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($notifications)): ?>

    <ol>
      <?php foreach ($notifications as $ntf):
        $isRead = !empty($ntf['is_read_ntf']);
        $type   = $ntf['notification_type'] ?? 'request';
        $icon   = $typeIcon($type);
        $link   = ($ntf['id_bor_ntf'] ?? null) !== null ? '/dashboard' : null;
      ?>
        <li data-type="<?= htmlspecialchars($type) ?>"<?= $isRead ? '' : ' data-unread' ?>>
          <article>
            <span aria-hidden="true">
              <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
            </span>

            <div>
              <?php if ($link !== null): ?>
                <a href="<?= $link ?>">
                  <h2><?= htmlspecialchars($ntf['title_ntf']) ?></h2>
                </a>
              <?php else: ?>
                <h2><?= htmlspecialchars($ntf['title_ntf']) ?></h2>
              <?php endif; ?>

              <?php if (!empty($ntf['body_ntf'])): ?>
                <p><?= htmlspecialchars($ntf['body_ntf']) ?></p>
              <?php endif; ?>

              <footer>
                <time datetime="<?= htmlspecialchars($ntf['created_at_ntf']) ?>">
                  <?= htmlspecialchars($relativeTime($ntf['created_at_ntf'])) ?>
                </time>
                <?php if (!empty($ntf['related_tool_name'])): ?>
                  <span>
                    <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                    <?= htmlspecialchars($ntf['related_tool_name']) ?>
                  </span>
                <?php endif; ?>
                <?php if (!$isRead): ?>
                  <span class="visually-hidden">Unread</span>
                <?php endif; ?>
              </footer>
            </div>
          </article>
        </li>
      <?php endforeach; ?>
    </ol>

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
              <li><span aria-hidden="true">&hellip;</span></li>
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
              <li><span aria-hidden="true">&hellip;</span></li>
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

    <section aria-label="No notifications">
      <i class="fa-regular fa-bell-slash" aria-hidden="true"></i>
      <h2>No Notifications</h2>
      <p>You&rsquo;re all caught up! We&rsquo;ll let you know when something needs your attention.</p>
      <a href="/dashboard" role="button">
        <i class="fa-solid fa-gauge" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php endif; ?>

</section>
