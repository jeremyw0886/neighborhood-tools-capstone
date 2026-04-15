<?php
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/events';

$allTimings  = ['HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'];
$totalAll    = array_sum($timingCounts);
$hasFilters  = $timing !== null;

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
      <?php if ($hasFilters): ?>
        <a href="/admin/events" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php endif; ?>
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
          $location  = !empty($event['event_address_evt'])
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
