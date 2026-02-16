<?php
/**
 * Terms of Service — <dialog> modal partial.
 *
 * Included on every page via layouts/main.php.
 * Opened by nav.js when user clicks [data-modal="tos"].
 * Without JS, the link navigates to /tos (standalone page).
 *
 * Unlike How To and FAQ, TOS content is dynamic — pulled from the
 * database via current_tos_v. 
 *
 * Footer is conditional:
 *   - Not logged in → no footer (read-only)
 *   - Logged in + already accepted → "Accepted on [date]" badge
 *   - Logged in + not yet accepted → "I Accept" button (POST form)
 *
 * Content body lives in partials/content-tos.php for DRY reuse
 * between this modal and the standalone tos/show.php page.
 *
 * @see public/assets/js/nav.js       — modal open/close logic
 * @see public/assets/css/modal.css    — dialog styling (legal formatting)
 * @see partials/content-tos.php       — shared content
 * @see src/Models/Tos.php             — getCurrent(), hasUserAccepted()
 */

$tos = $currentTos ?? null;
?>
<dialog id="modal-tos" aria-labelledby="modal-tos-title">
  <header>
    <h2 id="modal-tos-title">Terms of Service<?php if ($tos): ?> <small>v<?= htmlspecialchars($tos['version_tos']) ?></small><?php endif; ?></h2>
    <button type="button" aria-label="Close dialog">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </header>
  <div>
    <?php if ($tos): ?>
      <?php
        $contentHeadingLevel = 'h3';
        $contentPath = BASE_PATH . '/src/Views/partials/content-tos.php';
        if (file_exists($contentPath)) {
            require $contentPath;
        }
        unset($contentHeadingLevel);
      ?>
    <?php else: ?>
      <p>No terms of service are currently available.</p>
    <?php endif; ?>
  </div>
  <?php if ($tos && ($isLoggedIn ?? false)): ?>
  <footer>
    <?php if ($tosAccepted ?? false): ?>
      <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Accepted</span>
    <?php else: ?>
      <form method="post" action="/tos/accept">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <input type="hidden" name="tos_id" value="<?= (int) ($tos['id_tos'] ?? 0) ?>">
        <button type="submit">I Accept the Terms of Service</button>
      </form>
    <?php endif; ?>
  </footer>
  <?php endif; ?>
</dialog>
