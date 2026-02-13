<?php
/**
 * Admin — TOS management stub.
 *
 * Will query current_tos_v for the active TOS version and
 * tos_acceptance_required_v for users who haven't accepted
 * when fully implemented.
 *
 * Shared data:
 *   $currentPage  string
 *   $currentTos   ?array  Current TOS version from getSharedData()
 */
?>

<section aria-labelledby="admin-tos-heading">

  <header>
    <h1 id="admin-tos-heading">
      <i class="fa-solid fa-file-contract" aria-hidden="true"></i>
      Manage Terms of Service
    </h1>
    <p>View and manage Terms of Service versions.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <p>TOS management is not yet available. This page will display the current TOS version, acceptance statistics, and a list of users who have not yet accepted the latest terms.</p>

  <a href="<?= htmlspecialchars($backUrl) ?>" role="button">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
  </a>

</section>
