<?php
/**
 * Standalone Terms of Service page.
 *
 * Progressive-enhancement fallback for the <dialog> modal: without JS,
 * the nav link /tos navigates here. With JS, the same content is
 * shown in a modal instead.
 *
 * The TOS body lives in partials/content-tos.php (shared with the modal).
 * The accept form/badge is specific to this page layout — the modal
 * has its own version in its <footer>.
 *
 * Variables from getSharedData():
 *   $currentTos  — array from current_tos_v or null
 *   $tosAccepted — bool (true if accepted or no TOS exists)
 *   $isLoggedIn  — bool
 *   $authUser    — array or null
 *   $csrfToken   — string
 *
 * @see src/Controllers/TosController::show()
 * @see src/Views/partials/content-tos.php
 */

$tos = $currentTos ?? null;
?>
<article aria-labelledby="tos-heading">
  <header>
    <h1 id="tos-heading">
      <i class="fa-solid fa-file-contract" aria-hidden="true"></i>
      Terms of Service<?php if ($tos): ?> <small>v<?= htmlspecialchars($tos['version_tos']) ?></small><?php endif; ?>
    </h1>
    <p>Please review the terms that govern your use of NeighborhoodTools.</p>
  </header>

  <?php if ($tos): ?>
    <?php require BASE_PATH . '/src/Views/partials/content-tos.php'; ?>

    <?php if ($isLoggedIn): ?>
      <footer>
        <?php if ($tosAccepted): ?>
          <p>
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            You have accepted these terms.
          </p>
        <?php else: ?>
          <form method="post" action="/tos/accept">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="tos_id" value="<?= (int) $tos['id_tos'] ?>">
            <button type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> I Accept the Terms of Service</button>
          </form>
        <?php endif; ?>
      </footer>
    <?php endif; ?>
  <?php else: ?>
    <p>No terms of service are currently available. Please check back later.</p>
  <?php endif; ?>
</article>
