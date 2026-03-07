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
    'denial'   => 'fa-circle-xmark',
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

/**
 * Compute a time-based group label for a notification timestamp.
 *
 * Uses calendar-day boundaries (not 24-hour periods) to avoid midnight edge cases.
 */
$groupLabel = static function (string $timestamp): string {
    $now          = new DateTimeImmutable('now');
    $todayStr     = $now->format('Y-m-d');
    $yesterdayStr = $now->modify('-1 day')->format('Y-m-d');
    $weekStart    = $now->modify('Monday this week')->format('Y-m-d');
    $then         = new DateTimeImmutable($timestamp);
    $thenStr      = $then->format('Y-m-d');

    return match (true) {
        $thenStr === $todayStr                       => 'Today',
        $thenStr === $yesterdayStr                    => 'Yesterday',
        $thenStr >= $weekStart                       => 'This Week',
        $then->format('Y-m') === $now->format('Y-m') => 'This Month',
        default                                       => 'Older',
    };
};

// Pagination range display
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

/**
 * Build a pagination URL preserving the current filter and page.
 */
$paginationUrl = static function (int $pageNum) use ($filter): string {
    $params = array_filter([
        'filter' => $filter,
        'page'   => $pageNum > 1 ? $pageNum : null,
    ]);

    return '/notifications' . ($params !== [] ? '?' . http_build_query($params) : '');
};

/**
 * Build a filter tab URL.
 */
$filterUrl = static fn(?string $f): string =>
    '/notifications' . ($f !== null ? '?filter=' . urlencode($f) : '');
?>

<section aria-labelledby="notifications-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="notifications-heading">
      <i class="fa-solid fa-bell" aria-hidden="true"></i> Notifications
    </h1>

    <?php if ($unreadCount > 0): ?>
      <form action="/notifications/read" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="page" value="<?= htmlspecialchars((string) $page) ?>">
        <?php if ($filter !== null): ?>
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <?php endif; ?>
        <button type="submit" data-intent="primary">
          <i class="fa-solid fa-check-double" aria-hidden="true"></i> Mark all as read
        </button>
      </form>
    <?php endif; ?>

    <?php if ($totalCount > 0): ?>
      <form action="/notifications/clear-read" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit" data-intent="danger-outline">
          <i class="fa-solid fa-trash" aria-hidden="true"></i> Clear read
        </button>
      </form>
    <?php endif; ?>
  </header>

  <nav aria-label="Filter notifications">
    <ul>
      <?php
      $tabs = [
          ''           => 'All',
          'unread'     => 'Unread',
          'request'    => 'Requests',
          'decision'   => 'Decisions',
          'activity'   => 'Due & Returns',
      ];
      foreach ($tabs as $value => $label):
          $tabFilter = $value === '' ? null : $value;
          $isCurrent = $filter === $tabFilter;
      ?>
        <li>
          <a href="<?= htmlspecialchars($filterUrl($tabFilter)) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($label) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        notification<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($notifications)): ?>

    <?php
    $currentGroup = '';
    $itemIndex    = $rangeStart;
    foreach ($notifications as $ntf):
      $isRead = !empty($ntf['is_read_ntf']);
      $type   = $ntf['notification_type'] ?? 'request';
      $icon   = $typeIcon($type);
      $link   = '/notifications/' . (int) $ntf['id_ntf'] . '/go';
      $group  = $groupLabel($ntf['created_at_ntf']);

      if ($group !== $currentGroup):
        if ($currentGroup !== ''):
          echo '    </ol>' . "\n";
        endif;
        $currentGroup = $group;
    ?>
    <h3><?= htmlspecialchars($group) ?></h3>
    <ol start="<?= $itemIndex ?>">
    <?php endif; ?>
        <li data-type="<?= htmlspecialchars($type) ?>"<?= $isRead ? '' : ' data-unread' ?>>
          <article>
            <span aria-hidden="true">
              <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
            </span>

            <div>
              <a href="<?= htmlspecialchars($link) ?>">
                <h2><?= htmlspecialchars($ntf['title_ntf']) ?></h2>
              </a>

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
                <?php
                $borrowStatus = $ntf['related_borrow_status'] ?? '';
                $actionLabel  = match (true) {
                    $type === 'request' && $borrowStatus === 'requested' => 'View Request',
                    $type === 'return' && $borrowStatus === 'returned'   => 'Rate Borrower',
                    $type === 'rating'                                   => 'View History',
                    default                                              => null,
                };
                if ($actionLabel !== null): ?>
                  <a href="<?= htmlspecialchars($link) ?>" data-intent="secondary-sm"><?= $actionLabel ?></a>
                <?php endif; ?>
                <?php if (!$isRead): ?>
                  <span class="visually-hidden">Unread</span>
                  <form action="/notifications/read" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="notification_ids" value="<?= (int) $ntf['id_ntf'] ?>">
                    <input type="hidden" name="page" value="<?= (int) $page ?>">
                    <?php if ($filter !== null): ?>
                      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <?php endif; ?>
                    <button type="submit" aria-label="Mark as read">
                      <i class="fa-solid fa-check" aria-hidden="true"></i>
                    </button>
                  </form>
                <?php endif; ?>
                <form action="/notifications/<?= (int) $ntf['id_ntf'] ?>/delete" method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="page" value="<?= (int) $page ?>">
                  <?php if ($filter !== null): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                  <?php endif; ?>
                  <button type="submit" aria-label="Delete notification">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                  </button>
                </form>
              </footer>
            </div>
          </article>
        </li>
    <?php
      $itemIndex++;
    endforeach;
    ?>
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
              <span aria-disabled="true">
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
                   aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
            <li>
              <a href="<?= $paginationUrl($totalPages) ?>"
                 aria-label="Go to page <?= htmlspecialchars((string) $totalPages) ?>"><?= htmlspecialchars((string) $totalPages) ?></a>
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
              <span aria-disabled="true">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>

    <?php
    $emptyState = match ($filter) {
        'unread'   => [
            'icon'    => 'fa-solid fa-circle-check',
            'heading' => 'All Caught Up',
            'body'    => 'You&rsquo;ve read all your notifications. Nice work!',
        ],
        'request'  => [
            'icon'    => 'fa-solid fa-hand',
            'heading' => 'No Borrow Requests',
            'body'    => 'When someone requests one of your tools, you&rsquo;ll see it here.',
        ],
        'decision' => [
            'icon'    => 'fa-solid fa-circle-check',
            'heading' => 'No Decisions Yet',
            'body'    => 'Updates on your borrow requests will appear here.',
        ],
        'activity' => [
            'icon'    => 'fa-solid fa-clock',
            'heading' => 'No Due Dates or Returns',
            'body'    => 'When a borrow is due or returned, we&rsquo;ll notify you here.',
        ],
        default    => [
            'icon'    => 'fa-regular fa-bell-slash',
            'heading' => 'No Notifications',
            'body'    => 'You&rsquo;re all caught up! We&rsquo;ll let you know when something needs your attention.',
        ],
    };
    ?>
    <section aria-label="<?= htmlspecialchars($emptyState['heading']) ?>">
      <i class="<?= htmlspecialchars($emptyState['icon']) ?>" aria-hidden="true"></i>
      <h2><?= htmlspecialchars($emptyState['heading']) ?></h2>
      <p><?= $emptyState['body'] ?></p>
      <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
      </a>
    </section>

  <?php endif; ?>

</section>
