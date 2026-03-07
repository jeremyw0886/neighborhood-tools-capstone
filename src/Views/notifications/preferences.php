<?php
/**
 * Notification Preferences — toggle optional notification types.
 *
 * Variables from NotificationController::preferences():
 *   $prefs  array<string, bool>  Keyed by type name (due, return, rating)
 *
 * Shared data:
 *   $csrfToken  string
 */

$prefNotice = $_SESSION['pref_notice'] ?? null;
unset($_SESSION['pref_notice']);
?>

<section aria-labelledby="preferences-heading" id="notification-preferences">

  <nav aria-label="Back">
    <a href="/notifications">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Notifications
    </a>
  </nav>

  <?php if ($prefNotice !== null): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($prefNotice) ?></p>
  <?php endif; ?>

  <form action="/notifications/preferences" method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>
        <i class="fa-solid fa-sliders" aria-hidden="true"></i> Notification Preferences
      </legend>
      <p>Choose which notifications you receive. Required notifications for the borrow workflow cannot be turned off.</p>

      <ul>
        <li>
          <label>
            <input type="checkbox" name="pref_due"<?= $prefs['due'] ? ' checked' : '' ?>>
            <strong>Due date reminders</strong>
            <small>Get notified when a borrowed tool is approaching its return date.</small>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" name="pref_return"<?= $prefs['return'] ? ' checked' : '' ?>>
            <strong>Return confirmations</strong>
            <small>Get notified when a borrower returns your tool.</small>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" name="pref_rating"<?= $prefs['rating'] ? ' checked' : '' ?>>
            <strong>Rating alerts</strong>
            <small>Get notified when someone rates you.</small>
          </label>
        </li>
      </ul>

      <aside>
        <h2>
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Always-on notifications
        </h2>
        <p>Borrow requests, approvals, and denials are required for the lending workflow and cannot be disabled.</p>
      </aside>
    </fieldset>

    <button type="submit" data-intent="primary">
      <i class="fa-solid fa-check" aria-hidden="true"></i> Save Preferences
    </button>
  </form>
</section>
