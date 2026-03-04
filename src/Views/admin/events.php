<?php
/**
 * Admin — Event management with timing filter and sortable columns.
 *
 * Variables from AdminController::events():
 *   $events         array   Rows from Event::getUpcoming() with attendee_count
 *   $totalCount     int     Total events matching current filter
 *   $page           int     Current page (1-based)
 *   $totalPages     int     Total pages
 *   $perPage        int     Results per page (12)
 *   $timing         ?string Active timing filter (uppercase) or null
 *   $timingCounts   array   Timing label => count for filter options
 *   $sort           string  Active sort column
 *   $dir            string  Active sort direction (ASC|DESC)
 *   $filterParams   array   Non-null filter params for pagination URLs
 *
 * Each event row contains:
 *   id_evt, event_name_evt, event_description_evt, event_address_evt,
 *   start_at_evt, end_at_evt, days_until_event, event_timing,
 *   neighborhood_id, neighborhood_name_nbh, city_name_nbh, state_code_sta,
 *   creator_id, creator_name, created_at_evt, updated_at_evt, last_updated_by,
 *   attendee_count
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/events';

$allTimings = ['HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'];
$totalAll   = array_sum($timingCounts);

$sortLabels = [
    'start_at_evt'   => 'Start Date',
    'attendee_count' => 'Attendees',
    'created_at_evt' => 'Date Created',
    'event_name_evt' => 'Event Name',
];

$ariaSortFor = static function (string $col) use ($sort, $dir): string {
    if ($sort !== $col) {
        return '';
    }
    return ' aria-sort="' . ($dir === 'ASC' ? 'ascending' : 'descending') . '"';
};
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

  <form method="get" action="/admin/events" role="search" aria-label="Filter and sort events" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort events</legend>

      <div>
        <label for="events-timing">Timing</label>
        <select id="events-timing" name="timing">
          <option value="">All (<?= number_format($totalAll) ?>)</option>
          <?php foreach ($allTimings as $label):
            $count = $timingCounts[$label] ?? 0;
          ?>
            <option value="<?= htmlspecialchars($label) ?>"<?= $timing === $label ? ' selected' : '' ?>>
              <?= htmlspecialchars(ucwords(strtolower($label))) ?> (<?= number_format($count) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="events-sort">Sort By</label>
        <select id="events-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="events-dir">Direction</label>
        <select id="events-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
    </fieldset>
  </form>

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
          <th scope="col"<?= $ariaSortFor('event_name_evt') ?>>Event</th>
          <th scope="col">Location</th>
          <th scope="col"<?= $ariaSortFor('start_at_evt') ?>>Start</th>
          <th scope="col">Timing</th>
          <th scope="col"<?= $ariaSortFor('attendee_count') ?>>Attendees</th>
          <th scope="col">Creator</th>
          <th scope="col"<?= $ariaSortFor('created_at_evt') ?>>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $event):
          $eventId   = (int) $event['id_evt'];
          $attendees = (int) $event['attendee_count'];
          $location  = $event['event_address_evt'] !== null
              ? ($event['neighborhood_name_nbh'] ?? '') .
                ($event['city_name_nbh'] ? ', ' . $event['city_name_nbh'] : '')
              : 'Virtual';
          $timingKey = strtolower(str_replace(' ', '-', $event['event_timing']));
        ?>
          <tr>
            <td data-label="Event">
              <a href="/events/<?= $eventId ?>">
                <?= htmlspecialchars($event['event_name_evt']) ?>
              </a>
            </td>
            <td data-label="Location"><?= htmlspecialchars($location) ?></td>
            <td data-label="Start">
              <time datetime="<?= htmlspecialchars($event['start_at_evt']) ?>">
                <?= htmlspecialchars(date('M j, Y g:iA', strtotime($event['start_at_evt']))) ?>
              </time>
            </td>
            <td data-label="Timing">
              <span data-timing="<?= $timingKey ?>">
                <?= htmlspecialchars(ucwords(strtolower($event['event_timing']))) ?>
              </span>
            </td>
            <td data-label="Attendees"><?= number_format($attendees) ?></td>
            <td data-label="Creator">
              <a href="/profile/<?= (int) $event['creator_id'] ?>">
                <?= htmlspecialchars($event['creator_name']) ?>
              </a>
            </td>
            <td data-label="Created">
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
      <a href="/events/create" role="button" data-intent="primary">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Create Event
      </a>
    </section>

  <?php endif; ?>

</div>
</section>
