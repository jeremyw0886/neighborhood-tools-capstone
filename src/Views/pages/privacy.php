<?php
/**
 * Standalone Privacy Policy page.
 *
 * Unlike How It Works and FAQs, this page does not have a modal variant —
 * it is always rendered as a full standalone page.
 *
 * Content lives in partials/content-privacy.php for consistency with
 * other informational pages.
 *
 * @see src/Controllers/PageController::privacy()
 * @see src/Views/partials/content-privacy.php
 */
?>
<article aria-labelledby="privacy-heading">
  <header>
    <h1 id="privacy-heading"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy</h1>
    <p>How NeighborhoodTools collects, uses, and protects your information.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/content-privacy.php'; ?>
</article>
