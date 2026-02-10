<?php
/**
 * Admin — Dispute management stub.
 *
 * Will query open_dispute_v for open disputes with reporter,
 * borrower, lender details, message counts, and deposit info
 * when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-disputes-heading">

  <header>
    <h1 id="admin-disputes-heading">
      <i class="fa-solid fa-gavel" aria-hidden="true"></i>
      Manage Disputes
    </h1>
    <p>Review and resolve open disputes.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Dispute management is not yet available. This page will display open disputes with participant details, message threads, related incidents, and deposit information.</p>

  <a href="/admin" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Admin Dashboard
  </a>

</section>
