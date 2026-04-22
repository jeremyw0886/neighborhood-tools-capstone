<?php

declare(strict_types=1);

/**
 * build-assets.php — Minify and version all CSS and JS assets.
 *
 * 1. Base CSS bundle: concatenates config/css.php manifest → style.min.css
 * 2. Page-specific CSS: minifies each non-bundled .css → .min.css
 * 3. All JS: minifies each .js → .min.js
 * 4. Generates a content-hash version file (config/asset-version.php)
 *
 * Usage:
 *   php build-assets.php              Build + minify all assets
 *   php build-assets.php --no-minify  Concatenate/copy without minifying
 *   php build-assets.php --css-only   CSS only (bundle + page-specific)
 *   php build-assets.php --js-only    JS only
 *
 * Run from project root before deploying to production.
 */

// ───────────────────────────────────────────────────────────────────────────
// Configuration
// ───────────────────────────────────────────────────────────────────────────

$basePath    = __DIR__;
$cssDir      = $basePath . '/public/assets/css';
$jsDir       = $basePath . '/public/assets/js';
$bundleOut   = $cssDir . '/style.min.css';
$versionFile = $basePath . '/config/asset-version.php';
$manifest    = $basePath . '/config/css.php';

$minify  = !in_array('--no-minify', $argv, true);
$cssOnly = in_array('--css-only', $argv, true);
$jsOnly  = in_array('--js-only', $argv, true);
$doAll   = !$cssOnly && !$jsOnly;

$allMinified = [];

echo "\n  build-assets.php\n";
echo "  ════════════════════════════════════════\n\n";

// ───────────────────────────────────────────────────────────────────────────
// 1. Base CSS bundle
// ───────────────────────────────────────────────────────────────────────────

if ($doAll || $cssOnly) {
    echo "  [1/3] Base CSS bundle\n";

    if (!file_exists($manifest)) {
        fwrite(STDERR, "  ERROR: Missing manifest at $manifest\n");
        exit(1);
    }

    /** @var string[] $cssFiles */
    $cssFiles = require $manifest;

    if (!is_array($cssFiles) || $cssFiles === []) {
        fwrite(STDERR, "  ERROR: Manifest returned empty or non-array value\n");
        exit(1);
    }

    $sourceTotal = 0;

    foreach ($cssFiles as $file) {
        $path = $cssDir . '/' . $file;
        if (!is_file($path)) {
            fwrite(STDERR, "  ERROR: Missing source file — $path\n");
            exit(1);
        }
        $sourceTotal += filesize($path);
    }

    $timestamp = date('Y-m-d H:i:s');
    $bundle    = "/* NeighborhoodTools — built $timestamp */\n";

    foreach ($cssFiles as $file) {
        $path     = $cssDir . '/' . $file;
        $label    = pathinfo($file, PATHINFO_FILENAME);
        $contents = file_get_contents($path);

        $bundle .= "\n/* --- $label --- */\n";
        $bundle .= $contents . "\n";
    }

    if ($minify) {
        $bundle = minifyCss($bundle);
    }

    file_put_contents($bundleOut, $bundle);
    $allMinified[] = $bundleOut;

    $outputSize = strlen($bundle);
    $saved      = $sourceTotal - $outputSize;
    $percent    = $sourceTotal > 0 ? round($saved / $sourceTotal * 100) : 0;

    echo sprintf("        Concatenated %d files (%s)\n", count($cssFiles), formatBytes($sourceTotal));
    echo sprintf("        Output: %s", formatBytes($outputSize));

    if ($minify) {
        echo sprintf(" (saved %d%%)", $percent);
    }

    echo "\n\n";

    // ───────────────────────────────────────────────────────────────────────
    // 2. Page-specific CSS
    // ───────────────────────────────────────────────────────────────────────

    echo "  [2/3] Page-specific CSS\n";

    $bundledSet = array_flip($cssFiles);
    $pageCount  = 0;
    $pageSaved  = 0;

    foreach (glob($cssDir . '/*.css') as $file) {
        $basename = basename($file);

        if (str_ends_with($basename, '.min.css') || isset($bundledSet[$basename])) {
            continue;
        }

        $source  = file_get_contents($file);
        $output  = $minify ? minifyCss($source) : $source;
        $outPath = $cssDir . '/' . str_replace('.css', '.min.css', $basename);

        file_put_contents($outPath, $output);
        $allMinified[] = $outPath;
        $pageCount++;
        $pageSaved += strlen($source) - strlen($output);

        $pct = strlen($source) > 0 ? round((strlen($source) - strlen($output)) / strlen($source) * 100) : 0;
        echo sprintf("        %-30s → .min.css (%d%%)\n", $basename, $pct);
    }

    echo $pageCount === 0
        ? "        (none found)\n"
        : sprintf("        %d files, saved %s total\n", $pageCount, formatBytes($pageSaved));

    echo "\n";
}

// ───────────────────────────────────────────────────────────────────────────
// 3. JavaScript
// ───────────────────────────────────────────────────────────────────────────

if ($doAll || $jsOnly) {
    echo "  [3/3] JavaScript\n";

    $jsCount = 0;
    $jsSaved = 0;

    foreach (glob($jsDir . '/*.js') as $file) {
        $basename = basename($file);

        if (str_ends_with($basename, '.min.js')) {
            continue;
        }

        $source  = file_get_contents($file);
        $output  = $minify ? (new JsMinifier($source))->minify() : $source;
        $outPath = $jsDir . '/' . str_replace('.js', '.min.js', $basename);

        file_put_contents($outPath, $output);
        $allMinified[] = $outPath;
        $jsCount++;
        $jsSaved += strlen($source) - strlen($output);

        $pct = strlen($source) > 0 ? round((strlen($source) - strlen($output)) / strlen($source) * 100) : 0;
        echo sprintf("        %-30s → .min.js  (%d%%)\n", $basename, $pct);
    }

    echo $jsCount === 0
        ? "        (none found)\n"
        : sprintf("        %d files, saved %s total\n", $jsCount, formatBytes($jsSaved));

    echo "\n";
}

// ───────────────────────────────────────────────────────────────────────────
// 4. Generate content-hash version file
// ───────────────────────────────────────────────────────────────────────────

if ($allMinified !== []) {
    sort($allMinified);

    $combined = '';
    foreach ($allMinified as $path) {
        $combined .= md5_file($path);
    }

    $hash = substr(md5($combined), 0, 12);

    file_put_contents($versionFile, "<?php\n\n// Auto-generated by build-assets.php — do not edit\nreturn '$hash';\n");

    echo "  ────────────────────────────────────────\n";
    echo "  Version hash: $hash\n";
    echo "  Wrote:        $versionFile\n";
    echo "\n  Done. Deploy minified assets to production.\n\n";
}

// ===========================================================================
// CSS Minifier
// ===========================================================================

/**
 * Minify CSS — strip comments, collapse whitespace, remove unnecessary chars.
 */
function minifyCss(string $css): string
{
    $licenses = [];
    $css = preg_replace_callback(
        '#/\*!.*?\*/#s',
        static function (array $m) use (&$licenses): string {
            $key = '/*___LICENSE_' . count($licenses) . '___*/';
            $licenses[$key] = $m[0];
            return $key;
        },
        $css
    );

    $css = preg_replace('#/\*.*?\*/#s', '', $css);
    $css = preg_replace('#\s+#', ' ', $css);
    $css = preg_replace('#\s*([{};,>~])\s*#', '$1', $css);
    $css = preg_replace('#:\s+#', ':', $css);
    $css = str_replace(';}', '}', $css);
    $css = preg_replace('#\s*!\s*important#', '!important', $css);
    // Strip trailing-length units from zero values (e.g. `0px` → `0`).
    // Deliberately excludes `%`: `0%` is a valid keyframe SELECTOR
    // (`@keyframes foo { 0% { ... } }`) and stripping it to `0` is a parse
    // error per CSS spec.
    $css = preg_replace('#(?<![a-zA-Z0-9.\-])0(?:px|em|rem|pt|ex|ch|vw|vh|vmin|vmax)#', '0', $css);
    $css = trim($css);

    foreach ($licenses as $key => $comment) {
        $css = str_replace($key, $comment, $css);
    }

    return $css;
}

// ===========================================================================
// JS Minifier — token-aware, safe, pure PHP
// ===========================================================================

/**
 * Token-aware JavaScript minifier.
 *
 * Walks the source character-by-character using small focused methods for
 * each token type (strings, template literals, regex, comments). Strips
 * comments, collapses whitespace, and removes unnecessary spaces while
 * preserving spaces between identifier characters to avoid fusing keywords.
 */
class JsMinifier
{
    private readonly int $len;
    private int $pos = 0;
    private string $out = '';

    /** @var array<string, string> */
    private array $licenses = [];

    public function __construct(
        private readonly string $src,
    ) {
        $this->len = strlen($src);
    }

    /**
     * Run the minifier and return minified source.
     */
    public function minify(): string
    {
        while ($this->pos < $this->len) {
            $ch   = $this->src[$this->pos];
            $next = $this->peek(1);

            match (true) {
                $ch === '/' && $next === '/'  => $this->skipSingleLineComment(),
                $ch === '/' && $next === '*'  => $this->skipMultiLineComment(),
                $ch === "'"                   => $this->emitString("'"),
                $ch === '"'                   => $this->emitString('"'),
                $ch === '`'                   => $this->emitTemplateLiteral(),
                $ch === '/' && $this->expectsExpression() => $this->emitRegex(),
                $this->isWhitespace($ch)      => $this->collapseWhitespace(),
                default                       => $this->emit($ch),
            };
        }

        $result = $this->out;

        foreach ($this->licenses as $key => $comment) {
            $result = str_replace($key, $comment, $result);
        }

        $result = preg_replace("#\n{2,}#", "\n", $result);

        return trim($result);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function peek(int $offset = 0): string
    {
        $target = $this->pos + $offset;
        return $target < $this->len ? $this->src[$target] : '';
    }

    private function advance(): string
    {
        return $this->src[$this->pos++];
    }

    private function emit(string $text): void
    {
        $this->out .= $text;
        $this->pos++;
    }

    private function lastEmitted(): string
    {
        return $this->out !== '' ? $this->out[strlen($this->out) - 1] : '';
    }

    private function isWhitespace(string $ch): bool
    {
        return $ch === ' ' || $ch === "\t" || $ch === "\n" || $ch === "\r";
    }

    private function isIdentChar(string $ch): bool
    {
        return $ch !== '' && (ctype_alnum($ch) || $ch === '_' || $ch === '$');
    }

    // ── Token consumers ─────────────────────────────────────────────────

    /**
     * Skip // comment, preserving the trailing newline for ASI safety.
     */
    private function skipSingleLineComment(): void
    {
        $end = strpos($this->src, "\n", $this->pos);

        if ($end === false) {
            $this->pos = $this->len;
            return;
        }

        $this->pos = $end + 1;

        if ($this->lastEmitted() !== "\n") {
            $this->out .= "\n";
        }
    }

    /**
     * Skip a block comment. Preserves /*! license comments via placeholders.
     */
    private function skipMultiLineComment(): void
    {
        $end = strpos($this->src, '*/', $this->pos + 2);

        if ($end === false) {
            $this->pos = $this->len;
            return;
        }

        $isLicense = ($this->pos + 2 < $this->len && $this->src[$this->pos + 2] === '!');

        if ($isLicense) {
            $comment = substr($this->src, $this->pos, $end - $this->pos + 2);
            $key = '/*___JSLICENSE_' . count($this->licenses) . '___*/';
            $this->licenses[$key] = $comment;
            $this->out .= $key;
        }

        $this->pos = $end + 2;
    }

    /**
     * Emit a quoted string verbatim (single or double quotes).
     */
    private function emitString(string $quote): void
    {
        $this->out .= $this->advance();

        while ($this->pos < $this->len) {
            $ch = $this->advance();
            $this->out .= $ch;

            if ($ch === '\\' && $this->pos < $this->len) {
                $this->out .= $this->advance();
                continue;
            }

            if ($ch === $quote) {
                return;
            }
        }
    }

    /**
     * Emit a template literal verbatim, recursing into ${…} expressions.
     */
    private function emitTemplateLiteral(): void
    {
        $this->out .= $this->advance(); // opening `

        while ($this->pos < $this->len) {
            $ch = $this->src[$this->pos];

            if ($ch === '\\' && $this->pos + 1 < $this->len) {
                $this->out .= $this->advance() . $this->advance();
                continue;
            }

            if ($ch === '`') {
                $this->out .= $this->advance();
                return;
            }

            if ($ch === '$' && $this->peek(1) === '{') {
                $this->out .= $this->advance() . $this->advance(); // ${
                $this->emitTemplateExpression();
                continue;
            }

            $this->out .= $this->advance();
        }
    }

    /**
     * Emit the body of a ${…} expression inside a template literal.
     * Tracks brace depth and recurses for nested template literals/strings.
     */
    private function emitTemplateExpression(): void
    {
        $depth = 1;

        while ($this->pos < $this->len && $depth > 0) {
            $ch = $this->src[$this->pos];

            match (true) {
                $ch === '{'           => $this->handleBrace($depth, '{'),
                $ch === '}'           => $this->handleBrace($depth, '}'),
                $ch === "'" || $ch === '"' => $this->emitString($ch),
                $ch === '`'           => $this->emitTemplateLiteral(),
                default               => $this->out .= $this->advance(),
            };
        }
    }

    /**
     * Track brace depth inside template expressions.
     */
    private function handleBrace(int &$depth, string $brace): void
    {
        $depth += ($brace === '{') ? 1 : -1;
        $this->out .= $this->advance();
    }

    /**
     * Emit a regex literal verbatim, including character classes and flags.
     */
    private function emitRegex(): void
    {
        $this->out .= $this->advance(); // opening /

        while ($this->pos < $this->len) {
            $ch = $this->advance();
            $this->out .= $ch;

            if ($ch === '\\' && $this->pos < $this->len) {
                $this->out .= $this->advance();
                continue;
            }

            if ($ch === '[') {
                $this->emitCharacterClass();
                continue;
            }

            if ($ch === '/') {
                $this->emitRegexFlags();
                return;
            }
        }
    }

    /**
     * Emit the contents of a regex character class ([…]).
     */
    private function emitCharacterClass(): void
    {
        while ($this->pos < $this->len) {
            $ch = $this->advance();
            $this->out .= $ch;

            if ($ch === '\\' && $this->pos < $this->len) {
                $this->out .= $this->advance();
                continue;
            }

            if ($ch === ']') {
                return;
            }
        }
    }

    /**
     * Emit regex flags (e.g. /gi).
     */
    private function emitRegexFlags(): void
    {
        while ($this->pos < $this->len && ctype_alpha($this->src[$this->pos])) {
            $this->out .= $this->advance();
        }
    }

    /**
     * Collapse a whitespace run. Preserves a single space between identifier
     * characters and newlines where ASI could apply.
     */
    private function collapseWhitespace(): void
    {
        $hasNewline = false;

        while ($this->pos < $this->len && $this->isWhitespace($this->src[$this->pos])) {
            if ($this->src[$this->pos] === "\n" || $this->src[$this->pos] === "\r") {
                $hasNewline = true;
            }
            $this->pos++;
        }

        if ($this->out === '' || $this->pos >= $this->len) {
            return;
        }

        $last = $this->lastEmitted();
        $next = $this->src[$this->pos];

        if ($this->isIdentChar($last) && $this->isIdentChar($next)) {
            $this->out .= $hasNewline ? "\n" : ' ';
            return;
        }

        if ($hasNewline && $this->needsNewlineAfter($last)) {
            $this->out .= "\n";
            return;
        }

        if ($hasNewline && $this->needsNewlineBefore($next)) {
            $this->out .= "\n";
        }
    }

    /**
     * Whether a / at the current position starts a regex (not division).
     */
    private function expectsExpression(): bool
    {
        $trimmed = rtrim($this->out);

        if ($trimmed === '') {
            return true;
        }

        $last = $trimmed[strlen($trimmed) - 1];

        return in_array($last, [
            '=', '(', '[', '!', '&', '|', '?', ':', ';', ',',
            '{', '}', '^', '~', "\n", '+', '-', '*', '%', '<', '>',
        ], true);
    }

    /**
     * Whether a newline should be preserved after this character (ASI safety).
     */
    private function needsNewlineAfter(string $ch): bool
    {
        return $this->isIdentChar($ch) || in_array($ch, [')', ']', '+', '-'], true);
    }

    /**
     * Whether a newline should be preserved before this character (ASI safety).
     */
    private function needsNewlineBefore(string $ch): bool
    {
        return $this->isIdentChar($ch) || in_array($ch, ['(', '[', '+', '-', '/', '`', '!', '~'], true);
    }
}

// ===========================================================================
// Utility
// ===========================================================================

/**
 * Format byte count as human-readable string.
 */
function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    return sprintf('%.1f KB', $bytes / 1024);
}
