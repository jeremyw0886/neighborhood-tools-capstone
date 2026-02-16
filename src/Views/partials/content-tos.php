<?php

/**
 * Terms of Service — shared content partial.
 *
 * Included by both:
 *   - partials/modal-tos.php  (inside <dialog>)
 *   - tos/show.php            (standalone page)
 *
 * Expects $tos to be a non-null array from current_tos_v with keys:
 *   id_tos, version_tos, title_tos, content_tos, summary_tos,
 *   effective_at_tos, created_at_tos, created_by_name, total_acceptances
 *
 * Accepts optional $contentHeadingLevel (default 'h2').
 * Modal wrappers pass 'h3' so sections nest under the dialog's <h2> title.
 * Standalone pages use the default 'h2' to sit directly under the page <h1>.
 *
 * Content is stored as plain text with \n line breaks — rendered
 * via nl2br(htmlspecialchars()) for safety. The data-legal attribute
 * hooks into modal.css's legal document formatting rules.
 */
$contentHeadingLevel ??= 'h2';
?>
<div data-legal>
  <p>
    <strong>Effective:</strong>
    <time datetime="<?= htmlspecialchars($tos['effective_at_tos']) ?>">
      <?= htmlspecialchars(date('F j, Y', strtotime($tos['effective_at_tos']))) ?>
    </time>
  </p>

  <?php if (!empty($tos['summary_tos'])): ?>
  <section>
    <<?= $contentHeadingLevel ?>>Summary</<?= $contentHeadingLevel ?>>
    <p><?= nl2br(htmlspecialchars($tos['summary_tos'])) ?></p>
  </section>
  <?php endif; ?>

  <section>
    <<?= $contentHeadingLevel ?>>Full Terms</<?= $contentHeadingLevel ?>>
    <?= nl2br(htmlspecialchars($tos['content_tos'])) ?>
  </section>
</div>
