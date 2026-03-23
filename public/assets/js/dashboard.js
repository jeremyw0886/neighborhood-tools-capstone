'use strict';

// ─── Dashboard Router ────────────────────────────────────────────────

class DashboardRouter {
  static #instance = null;
  static #TRANSITION_MS = 250;
  static #PREFETCH_DELAY = 100;
  static #PREFETCH_TTL = 30_000;
  static #SWIPE_THRESHOLD = 80;
  static #CACHE_LIMIT = 8;
  static #DASHBOARD_RE = /^\/(?:dashboard(?:\/(?:lender|borrower|history|loan\/\d+))?|tools\/create|tools\/\d+\/edit|profile\/\d+|bookmarks|events)$/;

  /** @type {HTMLElement} */
  #mainEl;
  /** @type {Map<string, {data: Object, time: number}>} */
  #cache = new Map();
  /** @type {AbortController|null} */
  #currentAbort = null;
  #navigating = false;
  #navId = 0;
  #navIndex = 0;
  #hoverTimer = null;
  #swipeStartX = 0;
  #swipeStartY = 0;
  #abortController = new AbortController();

  /** @param {HTMLElement} mainEl */
  constructor(mainEl) {
    this.#mainEl = mainEl;

    if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
      NT.style.setRule('dash-reduced-motion', ':root', '--_dash-transition-ms:0ms');
    }

    history.replaceState({ dashNav: true, idx: this.#navIndex }, '');
    this.#bind();
  }

  /** @returns {DashboardRouter|null} */
  static init() {
    if (DashboardRouter.#instance) return DashboardRouter.#instance;
    if (!window.NT) return null;
    const mainEl = document.getElementById('main-content');
    if (!mainEl) return null;
    return (DashboardRouter.#instance = new DashboardRouter(mainEl));
  }

  destroy() {
    clearTimeout(this.#hoverTimer);
    this.#currentAbort?.abort();
    this.#abortController.abort();
    NT.style.removeRule('dash-reduced-motion');
    DashboardRouter.#instance = null;
  }

  #bind() {
    const { signal } = this.#abortController;
    this.#mainEl.addEventListener('click', this.#handleClick, { signal });
    window.addEventListener('popstate', this.#handlePopstate, { signal });
    this.#mainEl.addEventListener('pointerenter', this.#handlePointerEnter, { signal, capture: true });
    this.#mainEl.addEventListener('pointerleave', this.#handlePointerLeave, { signal, capture: true });
    this.#mainEl.addEventListener('focusin', this.#handleFocusIn, { signal });
    this.#mainEl.addEventListener('focusout', this.#handleFocusOut, { signal });
    document.addEventListener('keydown', this.#handleKeydown, { signal });
    this.#mainEl.addEventListener('touchstart', this.#handleTouchStart, { signal, passive: true });
    this.#mainEl.addEventListener('touchend', this.#handleTouchEnd, { signal, passive: true });
  }

  // ── Helpers ──

  static #isDashboardUrl(url) {
    try {
      const path = new URL(url, location.origin).pathname;
      return DashboardRouter.#DASHBOARD_RE.test(path);
    } catch { return false; }
  }

  static #shouldIntercept(link) {
    if (!link?.href) return false;
    if (link.target === '_blank' || link.hasAttribute('download')) return false;
    return DashboardRouter.#isDashboardUrl(link.href);
  }

  // ── Cache ──

  #cacheGet(url) {
    const entry = this.#cache.get(url);
    if (!entry) return null;
    if (Date.now() - entry.time >= DashboardRouter.#PREFETCH_TTL) {
      this.#cache.delete(url);
      return null;
    }
    return entry.data;
  }

  #cacheSet(url, data) {
    if (this.#cache.size >= DashboardRouter.#CACHE_LIMIT) {
      const oldest = this.#cache.keys().next().value;
      this.#cache.delete(oldest);
    }
    this.#cache.set(url, { data, time: Date.now() });
  }

  // ── Fetch & Sync ──

  /**
   * Fetch a page and return parsed content.
   *
   * @param {string} url
   * @param {AbortSignal} [signal]
   * @returns {Promise<{html: string, title: string, stylesheets: string[]}|null>}
   */
  async #fetchPage(url, signal) {
    const cached = this.#cacheGet(url);
    if (cached) return cached;

    const response = await NT.fetch(url, {
      signal,
      timeout: 8_000,
      headers: { Accept: 'text/html' },
    });

    if (!response.ok) return null;

    const text = await response.text();
    const doc = new DOMParser().parseFromString(text, 'text/html');
    const newMain = doc.getElementById('main-content');
    if (!newMain) return null;

    const stylesheets = [...doc.querySelectorAll('link[rel="stylesheet"]')]
      .map((link) => link.getAttribute('href'))
      .filter(Boolean);

    const data = { html: newMain.innerHTML, title: doc.title, stylesheets };
    this.#cacheSet(url, data);
    return data;
  }

  /**
   * Inject stylesheets the target page needs that aren't already loaded.
   *
   * @param {string[]} needed
   * @returns {Promise<void>}
   */
  static #syncStylesheets(needed) {
    const current = new Set(
      [...document.querySelectorAll('head link[rel="stylesheet"]')]
        .map((l) => l.getAttribute('href'))
        .filter(Boolean),
    );

    const loads = [];

    for (const href of needed) {
      if (current.has(href)) continue;
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      loads.push(new Promise((resolve) => {
        link.onload = resolve;
        link.onerror = resolve;
      }));
      document.head.appendChild(link);
    }

    return loads.length > 0 ? Promise.all(loads) : Promise.resolve();
  }

  #reinit() {
    NT.applyFocalPoints();

    for (const flash of this.#mainEl.querySelectorAll('[data-flash]')) {
      const type = flash.getAttribute('data-flash');
      NT.toast(flash.textContent.trim(), type);
      flash.remove();
    }

    for (const input of this.#mainEl.querySelectorAll('input[data-suggest="tools"]')) {
      if (!input.id) continue;
      const availableOnly = input.hasAttribute('data-available-only');
      NT.autosuggest({
        inputId: input.id,
        fetchUrl: (q) => `/tools/suggest?q=${encodeURIComponent(q)}${availableOnly ? '&available=1' : ''}`,
        labelKey: 'name',
        onSelect: (item) => { window.location.href = `/tools/${item.id}`; },
      });
    }

    for (const img of this.#mainEl.querySelectorAll('img[loading="lazy"]')) {
      if (img.complete && img.naturalWidth > 0) {
        img.setAttribute('data-loaded', '');
      } else {
        img.addEventListener('load', () => img.setAttribute('data-loaded', ''), { once: true });
      }
    }

    document.dispatchEvent(new CustomEvent('dashboard:content-swapped'));
  }

  // ── Navigation ──

  /**
   * Navigate to a dashboard URL with a slide transition.
   *
   * @param {string} url
   * @param {'forward'|'back'} direction
   * @param {boolean} pushHistory
   */
  async #navigate(url, direction = 'forward', pushHistory = true) {
    if (this.#navigating) return;
    this.#navigating = true;

    const id = ++this.#navId;
    const abort = new AbortController();
    this.#currentAbort = abort;

    const slideOut = direction === 'back' ? 'slide-out-right' : 'slide-out-left';
    const slideIn = direction === 'back' ? 'slide-in-left' : 'slide-in-right';

    try {
      this.#cacheSet(location.href, {
        html: this.#mainEl.innerHTML,
        title: document.title,
        stylesheets: [...document.querySelectorAll('head link[rel="stylesheet"]')]
          .map((l) => l.getAttribute('href'))
          .filter(Boolean),
      });

      const [data] = await Promise.all([
        this.#fetchPage(url, abort.signal),
        new Promise((resolve) => {
          this.#mainEl.setAttribute('data-transition', slideOut);
          this.#mainEl.addEventListener('transitionend', resolve, { once: true });
          setTimeout(resolve, DashboardRouter.#TRANSITION_MS + 50);
        }),
      ]);

      if (!data) {
        this.#mainEl.removeAttribute('data-transition');
        location.assign(url);
        return;
      }

      if (pushHistory) {
        this.#navIndex++;
        history.pushState({ dashNav: true, idx: this.#navIndex }, '', url);
      }

      window.scrollTo({ top: 0, behavior: 'instant' });

      await DashboardRouter.#syncStylesheets(data.stylesheets);

      this.#mainEl.innerHTML = data.html;
      document.title = data.title;

      this.#navigating = false;
      this.#currentAbort = null;

      this.#mainEl.setAttribute('data-transition', slideIn);
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          if (id === this.#navId) {
            this.#mainEl.removeAttribute('data-transition');
          }
        });
      });

      this.#reinit();
    } catch (err) {
      if (err.name !== 'AbortError') {
        this.#mainEl.removeAttribute('data-transition');
        location.assign(url);
      }
    } finally {
      if (id === this.#navId) {
        this.#navigating = false;
        this.#currentAbort = null;
      }
    }
  }

  // ── Event Handlers ──

  #handleClick = (e) => {
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    const link = e.target.closest('a[href]');
    if (!link) return;
    if (link.closest('form')) return;

    const isBack = !!link.closest('nav[aria-label="Back"]');

    if (isBack) {
      e.preventDefault();
      const dest = DashboardRouter.#isDashboardUrl(link.href) ? link.href : '/dashboard';
      this.#navigate(dest, 'back');
      return;
    }

    if (!DashboardRouter.#shouldIntercept(link)) return;

    e.preventDefault();
    this.#navigate(link.href, 'forward');
  };

  #handlePopstate = (e) => {
    if (!e.state?.dashNav) return;
    if (!DashboardRouter.#isDashboardUrl(location.href)) {
      location.reload();
      return;
    }

    const direction = (e.state.idx ?? 0) < this.#navIndex ? 'back' : 'forward';
    this.#navIndex = e.state.idx ?? 0;
    this.#navigate(location.href, direction, false);
  };

  #startPrefetch(link) {
    if (!DashboardRouter.#shouldIntercept(link)) return;
    if (this.#cache.has(link.href)) return;

    this.#hoverTimer = setTimeout(() => {
      this.#fetchPage(link.href).catch(() => {});
    }, DashboardRouter.#PREFETCH_DELAY);
  }

  #cancelPrefetch() {
    clearTimeout(this.#hoverTimer);
    this.#hoverTimer = null;
  }

  #handlePointerEnter = (e) => {
    const link = e.target.closest('a[href]');
    if (link) this.#startPrefetch(link);
  };

  #handlePointerLeave = (e) => {
    if (e.target.closest('a[href]')) this.#cancelPrefetch();
  };

  #handleFocusIn = (e) => {
    const link = e.target.closest('a[href]');
    if (link) this.#startPrefetch(link);
  };

  #handleFocusOut = (e) => {
    if (e.target.closest('a[href]')) this.#cancelPrefetch();
  };

  /** @param {KeyboardEvent} e */
  #handleKeydown = (e) => {
    if (!e.altKey || e.key !== 'ArrowLeft') return;
    if (e.ctrlKey || e.metaKey || e.shiftKey) return;

    const backLink = this.#mainEl.querySelector('nav[aria-label="Back"] a[href]');
    if (!backLink || !DashboardRouter.#shouldIntercept(backLink)) return;

    e.preventDefault();
    this.#navigate(backLink.href, 'back');
  };

  /** @param {TouchEvent} e */
  #handleTouchStart = (e) => {
    if (e.touches.length !== 1) return;
    this.#swipeStartX = e.touches[0].clientX;
    this.#swipeStartY = e.touches[0].clientY;
  };

  /** @param {TouchEvent} e */
  #handleTouchEnd = (e) => {
    if (e.changedTouches.length !== 1) return;

    const dx = e.changedTouches[0].clientX - this.#swipeStartX;
    const dy = Math.abs(e.changedTouches[0].clientY - this.#swipeStartY);

    if (dx < DashboardRouter.#SWIPE_THRESHOLD || dx < dy * 2) return;

    const backLink = this.#mainEl.querySelector('nav[aria-label="Back"] a[href]');
    if (!backLink || !DashboardRouter.#shouldIntercept(backLink)) return;

    this.#navigate(backLink.href, 'back');
  };
}

// ─── Init ────────────────────────────────────────────────────────────

DashboardRouter.init();
