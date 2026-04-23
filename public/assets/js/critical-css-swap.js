'use strict';

// ─── Critical CSS Swap ──────────────────────────────────────────────
//
// Promotes <link rel="preload" as="style" data-rel-swap> tags to
// rel="stylesheet" once their load event fires.
//
// Companion to the inline <style nonce> critical block rendered by
// layouts/main.php — see local_testing/critical-css/plan.md.

class CriticalCssSwap {
  static #selector = 'link[rel="preload"][as="style"][data-rel-swap]';

  /** @returns {CriticalCssSwap|null} */
  static init() {
    const links = document.querySelectorAll(CriticalCssSwap.#selector);
    if (links.length === 0) return null;
    return new CriticalCssSwap(links);
  }

  /** @param {NodeListOf<HTMLLinkElement>} links */
  constructor(links) {
    links.forEach(link => {
      if (link.sheet) {
        link.rel = 'stylesheet';
        return;
      }
      link.addEventListener('load', this.#promote, { once: true });
      link.addEventListener('error', this.#report, { once: true });
    });

    setTimeout(this.#backstop, 5000);
  }

  #promote = (event) => {
    event.currentTarget.rel = 'stylesheet';
  };

  #report = (event) => {
    console.error('Critical CSS swap: stylesheet failed to load', event.currentTarget.href);
  };

  #backstop = () => {
    document.querySelectorAll(CriticalCssSwap.#selector)
      .forEach(link => { link.rel = 'stylesheet'; });
  };
}

CriticalCssSwap.init();
