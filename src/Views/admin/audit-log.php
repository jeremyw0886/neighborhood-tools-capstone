<?php
/**
 * Admin — Audit log stub.
 *
 * No audit log table exists in the schema yet. This page is a
 * placeholder for future platform activity tracking.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-audit-heading">

  <header>
    <h1 id="admin-audit-heading">
      <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
      Audit Log
    </h1>
    <p>Platform activity and audit trail.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Audit logging is not yet available. This page will display a chronological record of administrative actions and platform events once the audit infrastructure is in place.</p>

  <a href="/admin" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Admin Dashboard
  </a>

</section>
