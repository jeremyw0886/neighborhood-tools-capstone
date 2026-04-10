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
 * Content is stored as plain text with \n line breaks. This partial parses
 * numbered section headers (e.g. "1. AGREEMENT TO TERMS") into semantic
 * HTML sections with proper headings and a table of contents.
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

/**
 * @param list<string> $lines
 */
function renderTosBody(array $lines): string
{
    $text = implode("\n", $lines);
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $html = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = (string) preg_replace(
        '/\b([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/',
        '<a href="mailto:$1">$1</a>',
        $html,
    );
    $html = (string) preg_replace_callback(
        '/(?<!["\/])(\b)(https?:\/\/[^\s<]+|neighborhoodtools\.org\/\S+)/',
        static fn(array $m): string => $m[1] . '<a href="'
            . (str_starts_with($m[2], 'http') ? $m[2] : 'https://' . $m[2])
            . '" rel="noopener">' . $m[2] . '</a>',
        $html,
    );
    return nl2br($html, false);
}

/**
 * @return string Slug for anchor links
 */
function tosSectionSlug(int $number, string $title): string
{
    $slug = strtolower(trim($title));
    $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
    return 'tos-' . $number . '-' . trim($slug, '-');
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
        <li><a href="#<?= tosSectionSlug($sec['number'], $sec['title']) ?>"><?= htmlspecialchars(ucwords(strtolower($sec['title']))) ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>

  <?php if (trim(implode('', $preamble)) !== ''): ?>
  <section>
    <p><?= renderTosBody($preamble) ?></p>
  </section>
  <?php endif; ?>

  <?php foreach ($sections as $sec): ?>
  <section id="<?= tosSectionSlug($sec['number'], $sec['title']) ?>">
    <<?= $contentHeadingLevel ?>><?= htmlspecialchars($sec['number'] . '. ' . ucwords(strtolower($sec['title']))) ?></<?= $contentHeadingLevel ?>>
    <p><?= renderTosBody($sec['lines']) ?></p>
  </section>
  <?php endforeach; ?>

  <?php else: ?>
  <section>
    <<?= $contentHeadingLevel ?>>Full Terms</<?= $contentHeadingLevel ?>>
    <?= nl2br(htmlspecialchars($rawContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) ?>
  </section>
  <?php endif; ?>
</div>
