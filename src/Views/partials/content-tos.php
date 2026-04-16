<?php

use App\Core\ViewHelper;

/**
 * Terms of Service — shared content partial.
 *
 * Included by both:
 *   - partials/modal-tos.php  (inside <dialog>)
 *   - tos/show.php            (standalone page)
 *
 * Expects $tos to be a non-null array from current_tos_v.
 * Accepts optional $contentHeadingLevel (default 'h2'); modal wrappers pass 'h3'.
 *
 * Rendering helpers live on ViewHelper so this partial can be included
 * multiple times per request (e.g. standalone page + auto-included modal)
 * without redeclaring functions.
 */
$contentHeadingLevel ??= 'h2';

$rawContent = $tos['content_tos'] ?? '';
$lines = explode("\n", $rawContent);

$sections = [];
$preamble = [];
$currentSection = null;

foreach ($lines as $line) {
    if (preg_match('/^(\d+)\.\s+(.+)$/', $line, $m) && mb_strlen($line) < 120) {
        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }
        $currentSection = [
            'number' => (int) $m[1],
            'title'  => trim($m[2]),
            'lines'  => [],
        ];
    } elseif ($currentSection !== null) {
        $currentSection['lines'][] = $line;
    } else {
        $preamble[] = $line;
    }
}

if ($currentSection !== null) {
    $sections[] = $currentSection;
}
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
    <p><?= nl2br(htmlspecialchars($tos['summary_tos'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) ?></p>
  </section>
  <?php endif; ?>

  <?php if ($sections !== []): ?>
  <nav aria-label="Table of contents">
    <<?= $contentHeadingLevel ?>>Contents</<?= $contentHeadingLevel ?>>
    <ol>
      <?php foreach ($sections as $sec): ?>
        <li><a href="#<?= ViewHelper::tosSectionSlug($sec['number'], $sec['title']) ?>"><?= htmlspecialchars(ucwords(strtolower($sec['title']))) ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>

  <?php if (trim(implode('', $preamble)) !== ''): ?>
  <section>
    <p><?= ViewHelper::renderTosBody($preamble) ?></p>
  </section>
  <?php endif; ?>

  <?php foreach ($sections as $sec): ?>
  <section id="<?= ViewHelper::tosSectionSlug($sec['number'], $sec['title']) ?>">
    <<?= $contentHeadingLevel ?>><?= htmlspecialchars($sec['number'] . '. ' . ucwords(strtolower($sec['title']))) ?></<?= $contentHeadingLevel ?>>
    <p><?= ViewHelper::renderTosBody($sec['lines']) ?></p>
  </section>
  <?php endforeach; ?>

  <?php else: ?>
  <section>
    <<?= $contentHeadingLevel ?>>Full Terms</<?= $contentHeadingLevel ?>>
    <?= nl2br(htmlspecialchars($rawContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) ?>
  </section>
  <?php endif; ?>
</div>
