<?php
/**
 * Admin — Reports stub.
 *
 * Will query pending_deposit_v for deposit status and
 * neighborhood_summary_fast_v for neighborhood statistics
 * when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-reports-heading">

  <header>
    <h1 id="admin-reports-heading">
      <i class="fa-solid fa-chart-bar" aria-hidden="true"></i>
      Reports
    </h1>
    <p>Platform reports and statistics.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Reports are not yet available. This page will display deposit status summaries, neighborhood statistics, and platform activity metrics.</p>

  <a href="/admin" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Admin Dashboard
  </a>

</section>
