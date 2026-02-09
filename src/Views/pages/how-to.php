<?php
/**
 * Standalone How It Works page.
 *
 * Progressive-enhancement fallback for the <dialog> modal: without JS,
 * the nav link /how-to navigates here. With JS, the same content is
 * shown in a modal instead.
 *
 * Content lives in partials/content-how-to.php (shared with the modal).
 *
 * @see src/Controllers/PageController::howTo()
 * @see src/Views/partials/content-how-to.php
 */
?>
<article aria-labelledby="how-to-heading">
  <header>
    <h1 id="how-to-heading"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> How It Works</h1>
    <p>Everything you need to know about borrowing and lending tools with NeighborhoodTools.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/content-how-to.php'; ?>
</article>
