<?php
/**
 * Admin — Event management stub.
 *
 * Will query upcoming_event_v for community events with
 * computed timing labels (HAPPENING NOW / THIS WEEK / etc.)
 * when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-events-heading">

  <header>
    <h1 id="admin-events-heading">
      <i class="fa-solid fa-calendar" aria-hidden="true"></i>
      Manage Events
    </h1>
    <p>View and manage community events.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Event management is not yet available. This page will display upcoming community events with scheduling details and attendance information.</p>

  <a href="<?= htmlspecialchars($backUrl) ?>" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
  </a>

</section>
