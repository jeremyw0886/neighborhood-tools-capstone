<?php
/**
 * FAQs — <dialog> modal partial.
 *
 * Included on every page via layouts/main.php.
 * Opened by nav.js when user clicks [data-modal="faq"].
 * Without JS, the link navigates to /faq (standalone page).
 *
 * Content lives in partials/content-faq.php for DRY reuse
 * between this modal and the standalone pages/faq.php page.
 *
 * @see public/assets/js/nav.js       — modal open/close logic
 * @see public/assets/css/modal.css    — dialog styling (details/summary accordion)
 * @see partials/content-faq.php       — shared content
 */
?>
<dialog id="modal-faq" aria-labelledby="modal-faq-title">
  <header>
    <h2 id="modal-faq-title">Frequently Asked Questions</h2>
    <button type="button" aria-label="Close dialog">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </header>
  <div>
    <?php
      $contentPath = BASE_PATH . '/src/Views/partials/content-faq.php';
      if (file_exists($contentPath)) {
          require $contentPath;
      }
    ?>
  </div>
</dialog>
