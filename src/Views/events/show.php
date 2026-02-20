<?php
/**
 * Event Detail — full information for a single community event.
 *
 * Variables from EventController::show():
 *   $event    array               Row from Event::findById()
 *   $meta     array<string,string> Key/value pairs from Event::getMeta()
 *   $isAdmin  bool                Whether the viewer is admin/super_admin
 *
 * Shared data:
 *   $csrfToken  string
 *   $backUrl    string
 */

$startDt  = new DateTimeImmutable($event['start_at_evt']);
$hasEnd   = $event['end_at_evt'] !== null;
$endDt    = $hasEnd ? new DateTimeImmutable($event['end_at_evt']) : null;
$sameDay  = $hasEnd && $startDt->format('Y-m-d') === $endDt->format('Y-m-d');
$timing   = $event['event_timing'];
$isPast   = $timing === 'PAST';

$timingSlug = strtolower(str_replace(' ', '-', $timing));
$timingIcon = match ($timing) {
    'HAPPENING NOW' => 'fa-circle-play',
    'THIS WEEK'     => 'fa-calendar-day',
    'THIS MONTH'    => 'fa-calendar-week',
    'PAST'          => 'fa-calendar-xmark',
    default         => 'fa-calendar',
};

$days = (int) $event['days_until_event'];
?>

<article id="event-detail" aria-labelledby="event-detail-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <span data-timing="<?= htmlspecialchars($timingSlug) ?>">
      <i class="fa-solid <?= $timingIcon ?>" aria-hidden="true"></i>
      <?= htmlspecialchars($timing) ?>
    </span>
    <h1 id="event-detail-heading"><?= htmlspecialchars($event['event_name_evt']) ?></h1>
  </header>

  <dl>
    <div>
      <dt>
        <i class="fa-regular fa-clock" aria-hidden="true"></i>
        Date &amp; Time
      </dt>
      <dd>
        <time datetime="<?= htmlspecialchars($event['start_at_evt']) ?>">
          <?= htmlspecialchars($startDt->format('l, F j, Y \a\t g:i A')) ?>
        </time>
        <?php if ($hasEnd): ?>
          <br>
          <span>to</span>
          <time datetime="<?= htmlspecialchars($event['end_at_evt']) ?>">
            <?php if ($sameDay): ?>
              <?= htmlspecialchars($endDt->format('g:i A')) ?>
            <?php else: ?>
              <?= htmlspecialchars($endDt->format('l, F j, Y \a\t g:i A')) ?>
            <?php endif; ?>
          </time>
        <?php endif; ?>
      </dd>
    </div>

    <?php if ($event['neighborhood_name_nbh'] !== null): ?>
      <div>
        <dt>
          <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
          Location
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
        Organized by
      </dt>
      <dd>
        <a href="/profile/<?= (int) $event['creator_id'] ?>">
          <?= htmlspecialchars($event['creator_name']) ?>
        </a>
      </dd>
    </div>

    <?php if (!$isPast): ?>
      <div>
        <dt>
          <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
          Countdown
        </dt>
        <dd data-timing="<?= htmlspecialchars($timingSlug) ?>">
          <?php if ($timing === 'HAPPENING NOW'): ?>
            Happening now
          <?php elseif ($days === 0): ?>
            Starts today
          <?php elseif ($days === 1): ?>
            Tomorrow
          <?php else: ?>
            In <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
          <?php endif; ?>
        </dd>
      </div>
    <?php endif; ?>
  </dl>

  <?php if ($event['event_description_evt'] !== null && $event['event_description_evt'] !== ''): ?>
    <section aria-label="Description">
      <h2>About This Event</h2>
      <p><?= nl2br(htmlspecialchars($event['event_description_evt'])) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($meta !== []): ?>
    <section aria-label="Additional details">
      <h2>Additional Details</h2>
      <dl>
        <?php foreach ($meta as $key => $value): ?>
          <div>
            <dt><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></dt>
            <dd><?= htmlspecialchars($value) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endif; ?>

  <footer>
    <p>
      <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i>
      Posted <?= htmlspecialchars((new DateTimeImmutable($event['created_at_evt']))->format('M j, Y')) ?>
      <?php if ($event['last_updated_by'] !== null): ?>
        · Updated by <?= htmlspecialchars($event['last_updated_by']) ?>
      <?php endif; ?>
    </p>
  </footer>

</article>
