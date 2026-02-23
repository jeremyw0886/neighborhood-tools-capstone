<?php
/**
 * Admin — Event management with timing filters and attendee counts.
 *
 * Variables from AdminController::events():
 *   $events          array   Rows from Event::getUpcoming() via upcoming_event_v
 *   $totalCount      int     Total events matching current filter
 *   $page            int     Current page (1-based)
 *   $totalPages      int     Total pages
 *   $perPage         int     Results per page (12)
 *   $timing          ?string Active timing filter (uppercase) or null
 *   $timingCounts    array   Timing label => count for filter bar
 *   $attendeeCounts  array   id_evt => attendee count
 *   $filterParams    array   Non-null filter params for pagination URLs
 *
 * Each event row contains:
 *   id_evt, event_name_evt, event_description_evt, event_address_evt,
 *   start_at_evt, end_at_evt, days_until_event, event_timing,
 *   neighborhood_id, neighborhood_name_nbh, city_name_nbh, state_code_sta,
 *   creator_id, creator_name, created_at_evt, updated_at_evt, last_updated_by
 *
 * Shared data:
 *   $currentPage  string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/events';

$allTimings = ['HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'];
$totalAll   = array_sum($timingCounts);
?>

<section aria-labelledby="admin-events-heading">

  <header>
    <h1 id="admin-events-heading">
      <i class="fa-solid fa-calendar" aria-hidden="true"></i>
      Manage Events
    </h1>
    <p>View and manage upcoming community events.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <nav aria-label="Filter by timing" data-timing-filters>
    <ul>
      <li>
        <a href="/admin/events"<?= $timing === null ? ' aria-current="true"' : '' ?>>
          All <span>(<?= number_format($totalAll) ?>)</span>
        </a>
      </li>
      <?php foreach ($allTimings as $label):
        $count    = $timingCounts[$label] ?? 0;
        $slug     = urlencode($label);
        $isActive = $timing === $label;
      ?>
        <li>
          <a href="/admin/events?timing=<?= $slug ?>"<?= $isActive ? ' aria-current="true"' : '' ?>>
            <?= htmlspecialchars(ucwords(strtolower($label))) ?> <span>(<?= number_format($count) ?>)</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        event<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($events)): ?>

    <div data-actions>
      <a href="/events/create">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Create Event
      </a>
    </div>

    <table>
      <caption class="visually-hidden">Community events</caption>
      <thead>
        <tr>
          <th scope="col">Event</th>
          <th scope="col">Location</th>
          <th scope="col">Start</th>
          <th scope="col">Timing</th>
          <th scope="col">Attendees</th>
          <th scope="col">Creator</th>
          <th scope="col">Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $event):
          $eventId   = (int) $event['id_evt'];
          $attendees = $attendeeCounts[$eventId] ?? 0;
          $location  = $event['event_address_evt'] !== null
              ? ($event['neighborhood_name_nbh'] ?? '') .
                ($event['city_name_nbh'] ? ', ' . $event['city_name_nbh'] : '')
              : 'Virtual';
          $timingKey = strtolower(str_replace(' ', '-', $event['event_timing']));
        ?>
          <tr>
            <td>
              <a href="/events/<?= $eventId ?>">
                <?= htmlspecialchars($event['event_name_evt']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($location) ?></td>
            <td>
              <time datetime="<?= htmlspecialchars($event['start_at_evt']) ?>">
                <?= htmlspecialchars(date('M j, Y g:iA', strtotime($event['start_at_evt']))) ?>
              </time>
            </td>
            <td>
              <span data-timing="<?= $timingKey ?>">
                <?= htmlspecialchars(ucwords(strtolower($event['event_timing']))) ?>
              </span>
            </td>
            <td><?= number_format($attendees) ?></td>
            <td>
              <a href="/profile/<?= (int) $event['creator_id'] ?>">
                <?= htmlspecialchars($event['creator_name']) ?>
              </a>
            </td>
            <td>
              <time datetime="<?= htmlspecialchars($event['created_at_evt']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($event['created_at_evt']))) ?>
              </time>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No events">
      <i class="fa-regular fa-calendar" aria-hidden="true"></i>
      <h2>No Upcoming Events</h2>
      <p>No events match the current filter. Create one to get started.</p>
      <a href="/events/create" role="button">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Create Event
      </a>
    </section>

  <?php endif; ?>

</section>
