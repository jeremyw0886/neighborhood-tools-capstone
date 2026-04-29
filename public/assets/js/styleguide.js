'use strict';

// ─── Sticky TOC highlighter ──────────────────────────────────────────
//
// Watches every `<section[id]>` inside the style guide and marks the
// matching `<nav>` link with `aria-current="true"` when it crosses the
// top third of the viewport. Degrades cleanly: without JS the TOC is
// still a plain anchor list.

class TocHighlighter {
  static #instance = null;

  /** @type {IntersectionObserver} */
  #observer;
  /** @type {Map<string, HTMLAnchorElement>} */
  #links;
  /** @type {?HTMLAnchorElement} */
  #activeLink = null;

  /**
   * Observe each style guide section and remember the TOC link map for highlighting.
   *
   * @param {NodeListOf<HTMLElement>} sections - All `<section[id]>` elements within the style guide
   * @param {Map<string, HTMLAnchorElement>} links - Map from section id to its TOC anchor
   */
  constructor(sections, links) {
    this.#links = links;

    this.#observer = new IntersectionObserver(this.#onIntersect, {
      rootMargin: '-10% 0px -70% 0px',
      threshold: 0,
    });

    for (const section of sections) this.#observer.observe(section);
  }

  /**
   * Initialize the singleton TocHighlighter when the style guide TOC and sections are present.
   *
   * @returns {TocHighlighter|null}
   */
  static init() {
    if (TocHighlighter.#instance) return TocHighlighter.#instance;

    const toc = document.querySelector('.styleguide-page nav[aria-label="Style guide contents"]');
    if (!toc) return null;

    const sections = document.querySelectorAll('.styleguide-page section[id]');
    if (sections.length === 0) return null;

    const links = new Map();
    for (const anchor of toc.querySelectorAll('a[href^="#"]')) {
      const id = anchor.getAttribute('href').slice(1);
      if (id) links.set(id, anchor);
    }

    if (links.size === 0) return null;

    return (TocHighlighter.#instance = new TocHighlighter(sections, links));
  }

  #onIntersect = (entries) => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue;

      const link = this.#links.get(entry.target.id);
      if (!link) continue;

      if (this.#activeLink && this.#activeLink !== link) {
        this.#activeLink.removeAttribute('aria-current');
      }

      link.setAttribute('aria-current', 'true');
      this.#activeLink = link;
    }
  };

  /**
   * Disconnect the section observer, clear the active TOC link, and reset the singleton.
   */
  destroy() {
    this.#observer.disconnect();
    if (this.#activeLink) this.#activeLink.removeAttribute('aria-current');
    TocHighlighter.#instance = null;
  }
}

// ─── Swatch copy-to-clipboard ────────────────────────────────────────
//
// Clicking any `.swatch-grid > li` copies the CSS variable reference
// (`var(--mountain-pine)`) to the clipboard and announces it via an
// accessible live region. No-JS fallback: the text is selectable.

class SwatchCopier {
  static #instance = null;
  static #RESET_MS = 1800;

  /** @type {HTMLElement} */
  #root;
  /** @type {HTMLElement} */
  #liveRegion;
  /** @type {?number} */
  #resetTimer = null;

  /**
   * Wire click and keyboard listeners on the style guide root for swatch copying.
   *
   * @param {HTMLElement} root - The style guide page root element
   * @param {HTMLElement} liveRegion - The injected aria-live region used for copy announcements
   */
  constructor(root, liveRegion) {
    this.#root = root;
    this.#liveRegion = liveRegion;
    this.#root.addEventListener('click', this.#onClick);
    this.#root.addEventListener('keydown', this.#onKeydown);
  }

  /**
   * Initialize the singleton SwatchCopier when clipboard support and swatches are available.
   *
   * @returns {SwatchCopier|null}
   */
  static init() {
    if (SwatchCopier.#instance) return SwatchCopier.#instance;

    if (!navigator.clipboard?.writeText) return null;

    const root = document.querySelector('.styleguide-page');
    if (!root) return null;

    const swatches = root.querySelectorAll('.swatch-grid > li');
    if (swatches.length === 0) return null;

    for (const swatch of swatches) {
      swatch.tabIndex = 0;
      swatch.setAttribute('role', 'button');
      const varText = swatch.querySelector('.swatch-var')?.textContent?.trim() ?? '';
      if (varText) swatch.setAttribute('aria-label', `Copy ${varText} to clipboard`);
    }

    const liveRegion = document.createElement('p');
    liveRegion.setAttribute('role', 'status');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.className = 'visually-hidden';
    root.append(liveRegion);

    return (SwatchCopier.#instance = new SwatchCopier(root, liveRegion));
  }

  #onClick = (event) => {
    const swatch = event.target.closest('.swatch-grid > li');
    if (!swatch) return;
    this.#copy(swatch);
  };

  #onKeydown = (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    const swatch = event.target.closest('.swatch-grid > li');
    if (!swatch) return;
    event.preventDefault();
    this.#copy(swatch);
  };

  #copy(swatch) {
    const text = swatch.querySelector('.swatch-var')?.textContent?.trim();
    if (!text) return;

    navigator.clipboard.writeText(text).then(
      () => this.#announce(`Copied ${text}`),
      () => this.#announce('Copy failed — select the text manually')
    );
  }

  #announce(message) {
    this.#liveRegion.textContent = message;
    if (this.#resetTimer) clearTimeout(this.#resetTimer);
    this.#resetTimer = window.setTimeout(() => {
      this.#liveRegion.textContent = '';
      this.#resetTimer = null;
    }, SwatchCopier.#RESET_MS);
  }

  /**
   * Detach listeners, cancel the announcement reset timer, remove the live region, and reset the singleton.
   */
  destroy() {
    this.#root.removeEventListener('click', this.#onClick);
    this.#root.removeEventListener('keydown', this.#onKeydown);
    if (this.#resetTimer) clearTimeout(this.#resetTimer);
    this.#liveRegion.remove();
    SwatchCopier.#instance = null;
  }
}

// ─── Bootstrap ───────────────────────────────────────────────────────

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    TocHighlighter.init();
    SwatchCopier.init();
  }, { once: true });
} else {
  TocHighlighter.init();
  SwatchCopier.init();
}
