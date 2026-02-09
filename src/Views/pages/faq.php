<?php
/**
 * Standalone FAQs page.
 *
 * Progressive-enhancement fallback for the <dialog> modal: without JS,
 * the nav link /faq navigates here. With JS, the same content is
 * shown in a modal instead.
 *
 * Content lives in partials/content-faq.php (shared with the modal).
 *
 * @see src/Controllers/PageController::faq()
 * @see src/Views/partials/content-faq.php
 */
?>
<article aria-labelledby="faq-heading">
  <header>
    <h1 id="faq-heading"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> Frequently Asked Questions</h1>
    <p>Quick answers to common questions about NeighborhoodTools.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/content-faq.php'; ?>
</article>
