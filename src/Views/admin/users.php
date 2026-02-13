<?php
/**
 * Admin — User management stub.
 *
 * Will query user_reputation_fast_v for a paginated member list
 * with ratings, tool counts, and borrow counts when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-users-heading">

  <header>
    <h1 id="admin-users-heading">
      <i class="fa-solid fa-users" aria-hidden="true"></i>
      Manage Users
    </h1>
    <p>View and manage platform members.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>User management is not yet available. This page will display a searchable, paginated list of all platform members with rating and activity summaries.</p>

  <a href="<?= htmlspecialchars($backUrl) ?>" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
  </a>

</section>
