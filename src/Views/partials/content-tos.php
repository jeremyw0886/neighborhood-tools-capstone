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
 * Content is stored as plain text with \n line breaks — rendered
 * via nl2br(htmlspecialchars()) for safety. The data-legal attribute
 * hooks into modal.css's legal document formatting rules.
 */
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
    <h3>Summary</h3>
    <p><?= nl2br(htmlspecialchars($tos['summary_tos'])) ?></p>
  </section>
  <?php endif; ?>

  <section>
    <h3>Full Terms</h3>
    <?= nl2br(htmlspecialchars($tos['content_tos'])) ?>
  </section>
</div>
