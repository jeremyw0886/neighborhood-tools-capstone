<?php
/**
 * Admin — Incident management stub.
 *
 * Will query open_incident_v for open incidents with type,
 * estimated damage, reporter/borrower/lender details, related
 * disputes, and deposit info when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-incidents-heading">

  <header>
    <h1 id="admin-incidents-heading">
      <i class="fa-solid fa-flag" aria-hidden="true"></i>
      Manage Incidents
    </h1>
    <p>Review open incident reports.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Incident management is not yet available. This page will display open incident reports with type classification, damage estimates, participant details, and linked disputes.</p>

  <a href="/admin" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Admin Dashboard
  </a>

</section>
