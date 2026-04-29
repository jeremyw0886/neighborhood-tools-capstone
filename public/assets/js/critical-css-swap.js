'use strict';

// ─── Critical CSS Swap ──────────────────────────────────────────────
//
// Promotes <link rel="preload" as="style" data-rel-swap> tags to
// rel="stylesheet" once the document is parsing past the head, so the
// stylesheet applies without blocking initial render.
//
// Companion to the inline <style nonce> critical block rendered by
// layouts/main.php — see local_testing/critical-css-plan.md.

class CriticalCssSwap {
  /**
   * Promote any preloaded critical-CSS links to active stylesheets.
   *
   * @returns {void}
   */
  static init() {
    const links = document.querySelectorAll('link[rel="preload"][as="style"][data-rel-swap]');
    for (const link of links) link.rel = 'stylesheet';
  }
}

CriticalCssSwap.init();
