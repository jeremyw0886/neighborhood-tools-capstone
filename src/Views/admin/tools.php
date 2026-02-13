<?php
/**
 * Admin — Tool management stub.
 *
 * Will query tool_statistics_fast_v for a paginated tool list
 * with borrow stats, ratings, and incident counts when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-tools-heading">

  <header>
    <h1 id="admin-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Manage Tools
    </h1>
    <p>View and manage all listed tools.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>Tool management is not yet available. This page will display a searchable, paginated list of all tools with borrow statistics and condition reports.</p>

  <a href="<?= htmlspecialchars($backUrl) ?>" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
  </a>

</section>
