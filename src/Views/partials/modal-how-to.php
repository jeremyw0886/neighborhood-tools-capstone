<?php
/**
 * How To — <dialog> modal partial.
 *
 * Included on every page via layouts/main.php.
 * Opened by nav.js when user clicks [data-modal="how-to"].
 * Without JS, the link navigates to /how-to (standalone page).
 *
 * Content lives in partials/content-how-to.php for DRY reuse
 * between this modal and the standalone pages/how-to.php page.
 *
 * @see public/assets/js/nav.js       — modal open/close logic
 * @see public/assets/css/modal.css    — dialog styling
 * @see partials/content-how-to.php    — shared content
 */
?>
<dialog id="modal-how-to" aria-labelledby="modal-how-to-title">
  <header>
    <h2 id="modal-how-to-title">How It Works</h2>
    <button type="button" aria-label="Close dialog">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </header>
  <div>
    <?php
      $contentPath = BASE_PATH . '/src/Views/partials/content-how-to.php';
      if (file_exists($contentPath)) {
          require $contentPath;
      }
    ?>
  </div>
</dialog>
