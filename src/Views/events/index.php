<?php
/**
 * Community Events — paginated listing with timing filter.
 *
 * Variables from EventController::index():
 *   $events        array   Rows from Event::getUpcoming() via upcoming_event_v
 *   $totalCount    int     Total matching events
 *   $page          int     Current page (1-based)
 *   $totalPages    int     Total pages
 *   $perPage       int     Results per page (12)
 *   $filterParams  array   Active filter params (timing)
 *   $timing        ?string Active timing filter or null
 *   $timingCounts  array   Keyed by timing label => count
 *
 * Each event row contains:
 *   id_evt, event_name_evt, event_description_evt,
 *   start_at_evt, end_at_evt, days_until_event, event_timing,
 *   neighborhood_id, neighborhood_name_nbh, city_name_nbh, state_code_sta,
 *   creator_id, creator_name, created_at_evt, updated_at_evt, last_updated_by
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static function (int $pageNum) use ($filterParams): string {
    $params = array_merge($filterParams, $pageNum > 1 ? ['page' => $pageNum] : []);
    return '/events' . ($params !== [] ? '?' . htmlspecialchars(http_build_query($params)) : '');
};

$timingSlug = static fn(string $label): string =>
    strtolower(str_replace(' ', '-', $label));

$timingIcon = static fn(string $label): string => match ($label) {
    'HAPPENING NOW' => 'fa-circle-play',
    'THIS WEEK'     => 'fa-calendar-day',
    'THIS MONTH'    => 'fa-calendar-week',
    default         => 'fa-calendar',
};

$formatEventDate = static function (string $start, ?string $end): string {
    $startDt  = new DateTimeImmutable($start);
    $startFmt = $startDt->format('D, M j, Y \a\t g:i A');

    if ($end === null) {
        return $startFmt;
    }

    $endDt = new DateTimeImmutable($end);

    if ($startDt->format('Y-m-d') === $endDt->format('Y-m-d')) {
        return $startDt->format('D, M j, Y \a\t g:i A') . ' – ' . $endDt->format('g:i A');
    }

    return $startFmt . ' – ' . $endDt->format('D, M j, Y \a\t g:i A');
};

$allTimings = ['HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'];
$totalAll   = array_sum($timingCounts);
?>

<section id="events-page" aria-labelledby="events-heading">

  <header>
    <h1 id="events-heading">
      <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
      Community Events
    </h1>
    <p>Discover upcoming events in the Asheville and Hendersonville neighborhoods.</p>
  </header>

  <?php if ($eventSuccess !== ''): ?>
    <p role="alert"><?= htmlspecialchars($eventSuccess) ?></p>
  <?php endif; ?>

  <nav aria-label="Filter by timing">
    <ul>
      <li>
        <a href="/events"<?= $timing === null ? ' aria-current="true"' : '' ?>>
          All (<?= number_format($totalAll) ?>)
        </a>
      </li>
      <?php foreach ($allTimings as $t):
        $count = $timingCounts[$t] ?? 0;
        $slug  = $timingSlug($t);
        $icon  = $timingIcon($t);
      ?>
        <li>
          <a href="/events?timing=<?= htmlspecialchars(urlencode($t)) ?>"
             <?= $timing === $t ? ' aria-current="true"' : '' ?>
             data-timing="<?= htmlspecialchars($slug) ?>">
            <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
            <?= htmlspecialchars(ucwords(strtolower($t))) ?> (<?= number_format($count) ?>)
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        event<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($events)): ?>

    <div role="list">
      <?php foreach ($events as $event):
        $slug    = $timingSlug($event['event_timing']);
        $icon    = $timingIcon($event['event_timing']);
        $days    = (int) $event['days_until_event'];
        $hasEnd  = $event['end_at_evt'] !== null;
        $hasLocation = $event['neighborhood_name_nbh'] !== null;
      ?>
        <article role="listitem" data-timing="<?= htmlspecialchars($slug) ?>">
          <header>
            <span data-timing="<?= htmlspecialchars($slug) ?>">
              <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
              <?= htmlspecialchars($event['event_timing']) ?>
            </span>
            <h2>
              <a href="/events/<?= (int) $event['id_evt'] ?>">
                <?= htmlspecialchars($event['event_name_evt']) ?>
              </a>
            </h2>
          </header>

          <?php if ($event['event_description_evt'] !== null && $event['event_description_evt'] !== ''): ?>
            <p><?= htmlspecialchars($event['event_description_evt']) ?></p>
          <?php endif; ?>

          <dl>
            <div>
              <dt>
                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                <span class="visually-hidden">Date &amp; time</span>
              </dt>
              <dd>
                <time datetime="<?= htmlspecialchars($event['start_at_evt']) ?>">
                  <?= htmlspecialchars($formatEventDate($event['start_at_evt'], $event['end_at_evt'])) ?>
                </time>
              </dd>
            </div>
            <?php if ($hasLocation): ?>
              <div>
                <dt>
                  <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                  <span class="visually-hidden">Location</span>
                </dt>
                <dd>
                  <?= htmlspecialchars($event['neighborhood_name_nbh']) ?>,
                  <?= htmlspecialchars($event['city_name_nbh']) ?>,
                  <?= htmlspecialchars($event['state_code_sta']) ?>
                </dd>
              </div>
            <?php endif; ?>
            <div>
              <dt>
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span class="visually-hidden">Organized by</span>
              </dt>
              <dd><?= htmlspecialchars($event['creator_name']) ?></dd>
            </div>
          </dl>

          <footer>
            <?php if ($event['event_timing'] === 'HAPPENING NOW'): ?>
              <span data-timing="happening-now">Happening now</span>
            <?php elseif ($days === 0): ?>
              <span data-timing="this-week">Starts today</span>
            <?php elseif ($days === 1): ?>
              <span data-timing="this-week">Tomorrow</span>
            <?php else: ?>
              <span>In <?= $days ?> day<?= $days !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>

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

    <section aria-label="No events">
      <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
      <h2>No Upcoming Events</h2>
      <p>There are no community events scheduled right now. Check back soon!</p>
      <?php if ($timing !== null): ?>
        <a href="/events" role="button">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> View All Events
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>

</section>
