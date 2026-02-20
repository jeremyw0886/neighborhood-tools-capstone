<?php
/**
 * Create Event — admin form for scheduling a community event.
 *
 * Variables from EventController::create():
 *   $neighborhoods  array   Rows from Neighborhood::allGroupedByCity()
 *   $errors         array   Field-keyed validation errors (empty on first load)
 *   $old            array   Previous input values for sticky fields (empty on first load)
 *
 * Shared data:
 *   $csrfToken  string
 *   $backUrl    string
 */

$currentCity = '';
?>

<section id="event-create" aria-labelledby="event-create-heading">

  <header>
    <h1 id="event-create-heading">
      <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>
      Create Event
    </h1>
    <p>Schedule a new community event for the neighborhood.</p>
  </header>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form action="/events" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Event Details</legend>

      <div>
        <label for="event-name">Event Name <span aria-hidden="true">*</span></label>
        <input type="text"
               id="event-name"
               name="event_name"
               required
               maxlength="255"
               autocomplete="off"
               placeholder="e.g. Spring Tool Swap Meet"
               value="<?= htmlspecialchars($old['event_name'] ?? '') ?>"
               <?php if (isset($errors['event_name'])): ?>aria-invalid="true" aria-describedby="event-name-error"<?php endif; ?>>
        <?php if (isset($errors['event_name'])): ?>
          <p id="event-name-error" role="alert"><?= htmlspecialchars($errors['event_name']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="event-description">Description</label>
        <textarea id="event-description"
                  name="event_description"
                  rows="4"
                  maxlength="5000"
                  placeholder="What will happen at this event? Include any details attendees should know."><?= htmlspecialchars($old['event_description'] ?? '') ?></textarea>
      </div>
    </fieldset>

    <fieldset>
      <legend>Date &amp; Time</legend>

      <div>
        <label for="event-start-date">Start Date <span aria-hidden="true">*</span></label>
        <input type="date"
               id="event-start-date"
               name="start_date"
               required
               min="<?= htmlspecialchars(date('Y-m-d')) ?>"
               value="<?= htmlspecialchars($old['start_date'] ?? '') ?>"
               <?php if (isset($errors['start_date'])): ?>aria-invalid="true" aria-describedby="start-date-error"<?php endif; ?>>
        <?php if (isset($errors['start_date'])): ?>
          <p id="start-date-error" role="alert"><?= htmlspecialchars($errors['start_date']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="event-start-time">Start Time <span aria-hidden="true">*</span></label>
        <input type="time"
               id="event-start-time"
               name="start_time"
               required
               value="<?= htmlspecialchars($old['start_time'] ?? '') ?>"
               <?php if (isset($errors['start_time'])): ?>aria-invalid="true" aria-describedby="start-time-error"<?php endif; ?>>
        <?php if (isset($errors['start_time'])): ?>
          <p id="start-time-error" role="alert"><?= htmlspecialchars($errors['start_time']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="event-end-date">End Date</label>
        <input type="date"
               id="event-end-date"
               name="end_date"
               min="<?= htmlspecialchars(date('Y-m-d')) ?>"
               value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
               aria-describedby="end-date-hint<?= isset($errors['end_date']) ? ' end-date-error' : '' ?>"
               <?php if (isset($errors['end_date'])): ?>aria-invalid="true"<?php endif; ?>>
        <p id="end-date-hint">Leave blank for single-day events.</p>
        <?php if (isset($errors['end_date'])): ?>
          <p id="end-date-error" role="alert"><?= htmlspecialchars($errors['end_date']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="event-end-time">End Time</label>
        <input type="time"
               id="event-end-time"
               name="end_time"
               value="<?= htmlspecialchars($old['end_time'] ?? '') ?>"
               <?php if (isset($errors['end_time'])): ?>aria-invalid="true" aria-describedby="end-time-error"<?php endif; ?>>
        <?php if (isset($errors['end_time'])): ?>
          <p id="end-time-error" role="alert"><?= htmlspecialchars($errors['end_time']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Location</legend>

      <div>
        <label>
          <input type="checkbox"
                 id="event-virtual"
                 name="is_virtual"
                 <?= !empty($old['is_virtual']) ? 'checked' : '' ?>>
          This is a virtual event (no physical location)
        </label>
      </div>

      <div>
        <label for="event-address">Address <span aria-hidden="true">*</span></label>
        <input type="text"
               id="event-address"
               name="event_address"
               maxlength="255"
               autocomplete="street-address"
               placeholder="e.g. 123 Main St, Asheville, NC 28801"
               value="<?= htmlspecialchars($old['event_address'] ?? '') ?>"
               aria-describedby="event-address-hint<?= isset($errors['event_address']) ? ' event-address-error' : '' ?>"
               <?php if (isset($errors['event_address'])): ?>aria-invalid="true"<?php endif; ?>>
        <p id="event-address-hint">Required for in-person events.</p>
        <?php if (isset($errors['event_address'])): ?>
          <p id="event-address-error" role="alert"><?= htmlspecialchars($errors['event_address']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="event-neighborhood">Neighborhood</label>
        <select id="event-neighborhood"
                name="neighborhood_id"
                aria-describedby="neighborhood-hint<?= isset($errors['neighborhood_id']) ? ' neighborhood-error' : '' ?>"
                <?php if (isset($errors['neighborhood_id'])): ?>aria-invalid="true"<?php endif; ?>>
          <option value="">No specific neighborhood</option>
          <?php foreach ($neighborhoods as $nbh):
            $city = $nbh['city_name_nbh'];
            if ($city !== $currentCity):
              if ($currentCity !== '') { echo '</optgroup>'; }
              $currentCity = $city;
          ?>
            <optgroup label="<?= htmlspecialchars($city) ?>">
          <?php endif; ?>
            <option value="<?= (int) $nbh['id_nbh'] ?>"
                    <?= ((int) ($old['neighborhood_id'] ?? 0)) === (int) $nbh['id_nbh'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($nbh['neighborhood_name_nbh']) ?>
            </option>
          <?php endforeach; ?>
          <?php if ($currentCity !== ''): ?></optgroup><?php endif; ?>
        </select>
        <p id="neighborhood-hint">Select the neighborhood where this event will take place.</p>
        <?php if (isset($errors['neighborhood_id'])): ?>
          <p id="neighborhood-error" role="alert"><?= htmlspecialchars($errors['neighborhood_id']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <footer>
      <button type="submit">
        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Create Event
      </button>
      <a href="/events">Cancel</a>
    </footer>
  </form>

</section>
