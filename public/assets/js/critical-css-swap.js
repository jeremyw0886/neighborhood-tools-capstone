'use strict';

// ─── Critical CSS Swap ──────────────────────────────────────────────
//
// Promotes <link rel="preload" as="style" data-rel-swap> tags to
// rel="stylesheet" 
//
// Companion to the inline <style nonce> critical block rendered by
// layouts/main.php — see local_testing/critical-css/plan.md.

class CriticalCssSwap {
  /** @returns {null} */
  static init() {
    const links = document.querySelectorAll('link[rel="preload"][as="style"][data-rel-swap]');
    if (links.length === 0) return null;

    links.forEach(link => { link.rel = 'stylesheet'; });
    return null;
  }
}

CriticalCssSwap.init();
