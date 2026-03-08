'use strict';

/**
 * Global utility module — shared infrastructure for all page-specific JS.
 *
 * Exposes a frozen `window.NT` namespace with fetch wrapper, toast system,
 * and form utilities. Loaded on every page after nav.js via defer.
 *
 * @see src/Views/layouts/main.php — script loading order
 * @see public/assets/css/components.css — [data-flash] and #toast-container styles
 */

(() => {

  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const DEFAULT_TIMEOUT = 10_000;
  const TOAST_DURATION = 5_000;

  // ─── Fetch Wrapper ──────────────────────────────────────────────────

  /**
   * Thin fetch() wrapper with CSRF, timeout, and session-expiry detection.
   *
   * @param {string} url
   * @param {RequestInit & { timeout?: number }} opts
   * @returns {Promise<Response>}
   */
  async function ntFetch(url, opts = {}) {
    const controller = new AbortController();
    const timeout = opts.timeout ?? DEFAULT_TIMEOUT;
    const timer = setTimeout(() => controller.abort(), timeout);

    if (opts.signal) {
      opts.signal.addEventListener('abort', () => controller.abort(), { once: true });
    }

    const headers = new Headers(opts.headers);
    headers.set('X-CSRF-Token', CSRF_TOKEN);
    headers.set('X-Requested-With', 'XMLHttpRequest');
    if (!headers.has('Accept')) headers.set('Accept', 'application/json');

    if (opts.body && !(opts.body instanceof FormData)) {
      headers.set('Content-Type', 'application/json');
    }

    try {
      const response = await fetch(url, {
        ...opts,
        headers,
        credentials: 'same-origin',
        signal: controller.signal,
      });

      if (response.redirected && new URL(response.url).pathname === '/login') {
        toast('Your session has expired. Redirecting to login\u2026', 'error');
        setTimeout(() => { window.location.href = '/login'; }, 1500);
        return Promise.reject(new Error('Session expired'));
      }

      if (!response.ok) {
        switch (response.status) {
          case 401:
            window.location.href = '/login';
            return Promise.reject(new Error('Unauthorized'));
          case 403:
            toast('Access denied.', 'error');
            break;
          case 422:
            return response;
          case 429:
            toast('Too many requests, try again shortly.', 'error');
            break;
          default:
            if (response.status >= 500) {
              toast('Something went wrong.', 'error');
            }
        }
      }

      return response;
    } finally {
      clearTimeout(timer);
    }
  }

  // ─── Toast System ───────────────────────────────────────────────────

  let toastContainer = null;

  function getToastContainer() {
    if (toastContainer) return toastContainer;

    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastContainer);

    return toastContainer;
  }

  /**
   * Show a toast notification.
   *
   * @param {string} message
   * @param {'success'|'error'|'info'} type
   * @param {number} duration — auto-dismiss in ms (0 = manual only)
   */
  function toast(message, type = 'info', duration = TOAST_DURATION) {
    const container = getToastContainer();
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.setAttribute('data-flash', type);

    const iconMap = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
    const icon = document.createElement('i');
    icon.className = `fa-solid ${iconMap[type] ?? iconMap.info}`;
    icon.setAttribute('aria-hidden', 'true');

    const text = document.createElement('span');
    text.textContent = message;

    const dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'toast-dismiss';
    dismiss.setAttribute('aria-label', 'Dismiss notification');
    const dismissIcon = document.createElement('i');
    dismissIcon.className = 'fa-solid fa-xmark';
    dismissIcon.setAttribute('aria-hidden', 'true');
    dismiss.appendChild(dismissIcon);
    dismiss.addEventListener('click', () => removeToast(el));

    el.append(icon, text, dismiss);
    container.appendChild(el);

    if (duration > 0) {
      setTimeout(() => removeToast(el), duration);
    }
  }

  function removeToast(el) {
    if (!el.parentNode) return;

    const prefersReduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReduced) {
      el.remove();
      return;
    }

    el.classList.add('toast-exit');
    el.addEventListener('animationend', () => el.remove(), { once: true });
  }

  // ─── Flash Auto-Dismiss ─────────────────────────────────────────────

  function migrateFlashMessages() {
    const flashes = document.querySelectorAll('[data-flash]');

    if (flashes.length === 0) return;

    const container = getToastContainer();

    for (const flash of flashes) {
      if (flash.closest('#toast-container')) continue;

      flash.remove();
      container.appendChild(flash);

      const dismiss = document.createElement('button');
      dismiss.type = 'button';
      dismiss.className = 'toast-dismiss';
      dismiss.setAttribute('aria-label', 'Dismiss notification');
      const dismissIcon = document.createElement('i');
      dismissIcon.className = 'fa-solid fa-xmark';
      dismissIcon.setAttribute('aria-hidden', 'true');
      dismiss.appendChild(dismissIcon);
      dismiss.addEventListener('click', () => removeToast(flash));
      flash.appendChild(dismiss);

      setTimeout(() => removeToast(flash), TOAST_DURATION);
    }
  }

  // ─── Form: Dirty Tracking ──────────────────────────────────────────

  /**
   * Warn on unsaved changes via beforeunload.
   *
   * @param {HTMLFormElement} form
   */
  function trackDirty(form) {
    let dirty = false;

    const warn = (e) => {
      if (!dirty) return;
      e.preventDefault();
    };

    const markDirty = (e) => {
      if (!e.isTrusted) return;

      if (e.target.type === 'file') {
        dirty = e.target.files.length > 0;
      } else {
        dirty = true;
      }
    };

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    form.addEventListener('reset', () => { dirty = false; });
    form.addEventListener('submit', () => {
      dirty = false;
      window.removeEventListener('beforeunload', warn);
    });

    window.addEventListener('beforeunload', warn);
  }

  // ─── Form: Character Counter ───────────────────────────────────────

  /**
   * Append a live character counter below a textarea.
   *
   * @param {HTMLTextAreaElement} textarea
   * @param {number} max
   */
  function charCounter(textarea, max) {
    const counter = document.createElement('span');
    counter.className = 'char-counter';
    const counterId = `${textarea.id || 'textarea'}-counter`;
    counter.id = counterId;
    counter.setAttribute('aria-live', 'polite');
    textarea.setAttribute('aria-describedby', counterId);

    const update = () => {
      const len = textarea.value.length;
      counter.textContent = `${len} / ${max}`;
    };

    let debounceTimer;
    textarea.addEventListener('input', () => {
      update();
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        counter.setAttribute('aria-live', 'polite');
      }, 1_000);
      counter.setAttribute('aria-live', 'off');
    });

    update();
    textarea.parentNode.insertBefore(counter, textarea.nextSibling);
  }

  // ─── Form: Image Preview ──────────────────────────────────────────

  /**
   * Show an image preview when a file input changes.
   *
   * @param {HTMLInputElement} input
   * @param {HTMLElement} container — element to render the preview <img> into
   */
  function imagePreview(input, container) {
    let objectUrl = null;

    const allowed = (input.accept || 'image/jpeg,image/png,image/webp')
      .split(',')
      .map(t => t.trim());

    const cleanup = () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
      container.textContent = '';
    };

    input.addEventListener('change', () => {
      cleanup();

      const file = input.files?.[0];
      if (!file) return;

      if (!allowed.includes(file.type)) {
        toast('Invalid file type. Please choose a JPEG, PNG, or WebP image.', 'error');
        input.value = '';
        return;
      }

      objectUrl = URL.createObjectURL(file);
      const img = document.createElement('img');
      img.src = objectUrl;
      img.alt = 'Image preview';
      container.appendChild(img);
    });

    input.form?.addEventListener('reset', cleanup);
  }

  // ─── CSP-Safe Dynamic Styles ───────────────────────────────────────

  const dynamicSheet = new CSSStyleSheet();
  document.adoptedStyleSheets = [...document.adoptedStyleSheets, dynamicSheet];
  const ruleMap = new Map();

  /**
   * Set a CSS rule keyed by a unique ID (replaces any previous rule for that key).
   *
   * @param {string} key
   * @param {string} selector
   * @param {string} declarations
   */
  function setRule(key, selector, declarations) {
    removeRule(key);
    const index = dynamicSheet.insertRule(`${selector}{${declarations}}`, dynamicSheet.cssRules.length);
    ruleMap.set(key, index);
  }

  /**
   * Remove a previously set rule by key.
   *
   * @param {string} key
   */
  function removeRule(key) {
    if (!ruleMap.has(key)) return;
    const staleIndex = ruleMap.get(key);
    dynamicSheet.deleteRule(staleIndex);
    ruleMap.delete(key);
    for (const [k, v] of ruleMap) {
      if (v > staleIndex) ruleMap.set(k, v - 1);
    }
  }

  /**
   * Apply object-position from data-focal-x / data-focal-y on images within root.
   *
   * @param {Element} root
   */
  function applyFocalPoints(root = document) {
    const imgs = (root.matches?.('img') ? [root] : root.querySelectorAll('img'));
    for (const img of imgs) {
      if (!img.id) img.id = `fp-${crypto.randomUUID().slice(0, 8)}`;
      const key = `fp-${img.id}`;
      const fx = img.dataset.focalX;
      const fy = img.dataset.focalY;
      if (fx && fy) {
        setRule(key, `#${CSS.escape(img.id)}`, `object-position:${fx}% ${fy}%`);
      } else {
        removeRule(key);
      }
    }
  }

  // ─── Focus Management ──────────────────────────────────────────────

  const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  /**
   * Trap keyboard focus within a container.
   *
   * @param {HTMLElement} container
   * @returns {() => void} release function
   */
  function trapFocus(container) {
    const handler = (e) => {
      if (e.key !== 'Tab') return;

      const focusable = [...container.querySelectorAll(FOCUSABLE)]
        .filter(el => el.offsetParent !== null);
      if (focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };

    container.addEventListener('keydown', handler);
    return () => container.removeEventListener('keydown', handler);
  }

  /**
   * Announce a message to screen readers via a shared live region.
   *
   * @param {string} message
   * @param {'polite'|'assertive'} priority
   */
  let announceRegion = null;

  function announce(message, priority = 'polite') {
    if (!announceRegion) {
      announceRegion = document.createElement('div');
      announceRegion.className = 'visually-hidden';
      announceRegion.setAttribute('aria-live', 'polite');
      announceRegion.setAttribute('aria-atomic', 'true');
      document.body.appendChild(announceRegion);
    }

    announceRegion.setAttribute('aria-live', priority);
    announceRegion.textContent = '';

    requestAnimationFrame(() => {
      announceRegion.textContent = message;
    });
  }

  // ─── Non-Blocking Confirm Dialog ───────────────────────────────────

  /**
   * Promise-based replacement for window.confirm().
   *
   * @param {string} message
   * @param {string} confirmLabel
   * @returns {Promise<boolean>}
   */
  function ntConfirm(message, confirmLabel = 'Delete') {
    return new Promise((resolve) => {
      const dialog = document.createElement('dialog');
      dialog.setAttribute('aria-label', 'Confirmation');
      dialog.dataset.confirm = '';

      const p = document.createElement('p');
      p.textContent = message;

      const footer = document.createElement('footer');

      const cancelBtn = document.createElement('button');
      cancelBtn.type = 'button';
      cancelBtn.textContent = 'Cancel';

      const confirmBtn = document.createElement('button');
      confirmBtn.type = 'button';
      confirmBtn.textContent = confirmLabel;
      confirmBtn.setAttribute('data-intent', 'danger');
      confirmBtn.setAttribute('autofocus', '');

      footer.append(cancelBtn, confirmBtn);
      dialog.append(p, footer);
      document.body.appendChild(dialog);

      const cleanup = (result) => {
        dialog.close();
        dialog.remove();
        resolve(result);
      };

      cancelBtn.addEventListener('click', () => cleanup(false));
      confirmBtn.addEventListener('click', () => cleanup(true));
      dialog.addEventListener('cancel', () => cleanup(false));

      dialog.showModal();
    });
  }

  // ─── Form: Client-Side Validation ─────────────────────────────────

  /**
   * Derive an error-element ID from a field's id or name.
   *
   * @param {HTMLElement} field
   * @returns {string}
   */
  function errorIdFor(field) {
    return `${(field.id || field.name).replace(/_/g, '-')}-error`;
  }

  /**
   * Build a human-readable message from the Constraint Validation API.
   *
   * @param {HTMLElement} field
   * @returns {string}
   */
  function validationMessage(field) {
    const v = field.validity;

    if (v.valueMissing) {
      return field.tagName === 'SELECT'
        ? 'Please select an option.'
        : 'This field is required.';
    }
    if (v.typeMismatch && field.type === 'email') return 'Please enter a valid email address.';
    if (v.typeMismatch) return 'Please enter a valid value.';
    if (v.tooShort) return `Please enter at least ${field.minLength} characters.`;
    if (v.tooLong) return `Please enter no more than ${field.maxLength} characters.`;
    if (v.patternMismatch) return field.title || 'Please match the requested format.';
    if (v.rangeUnderflow) return `Value must be at least ${field.min}.`;
    if (v.rangeOverflow) return `Value must be no more than ${field.max}.`;
    if (v.stepMismatch) return 'Please enter a valid value.';
    if (v.badInput) return 'Please enter a valid value.';
    return 'This field is invalid.';
  }

  /**
   * Show an inline error for a field, matching server-rendered markup.
   *
   * @param {HTMLElement} field
   */
  function showFieldError(field) {
    const id = errorIdFor(field);
    if (document.getElementById(id)) return;

    const tag = field.closest('.auth-card .form-group') ? 'span' : 'p';
    const el = document.createElement(tag);
    el.id = id;
    el.setAttribute('role', 'alert');
    el.textContent = validationMessage(field);

    field.setAttribute('aria-invalid', 'true');

    const describedBy = field.getAttribute('aria-describedby') ?? '';
    if (!describedBy.includes(id)) {
      if (!field.hasAttribute('data-orig-describedby')) {
        field.dataset.origDescribedby = describedBy;
      }
      field.setAttribute('aria-describedby', describedBy ? `${describedBy} ${id}` : id);
    }

    const ref = field.parentNode.querySelector('.char-counter')
      ?? field.parentNode.querySelector('.form-hint')
      ?? field;
    ref.after(el);

    if (!field.hasAttribute('data-live-validate')) {
      field.dataset.liveValidate = '';
      const clear = () => { if (field.checkValidity()) clearFieldError(field); };
      field.addEventListener('input', clear);
      field.addEventListener('change', clear);
    }
  }

  /**
   * Remove a JS-rendered inline error for a field.
   *
   * @param {HTMLElement} field
   */
  function clearFieldError(field) {
    const id = errorIdFor(field);
    document.getElementById(id)?.remove();

    field.removeAttribute('aria-invalid');

    if (field.hasAttribute('data-orig-describedby')) {
      const original = field.dataset.origDescribedby;
      if (original) {
        field.setAttribute('aria-describedby', original);
      } else {
        field.removeAttribute('aria-describedby');
      }
      delete field.dataset.origDescribedby;
    }
  }

  /**
   * Validate all fields in a form. Returns true when valid.
   *
   * @param {HTMLFormElement} form
   * @returns {boolean}
   */
  function validateForm(form) {
    const fields = form.querySelectorAll('input, select, textarea');
    let firstInvalid = null;

    for (const field of fields) {
      if (field.type === 'hidden' || field.disabled || field.name === 'website') continue;

      clearFieldError(field);

      if (!field.checkValidity()) {
        showFieldError(field);
        firstInvalid ??= field;
      }
    }

    firstInvalid?.focus();
    return !firstInvalid;
  }

  /**
   * Auto-validate novalidate POST forms on submit.
   */
  function initFormValidation() {
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!form.hasAttribute('novalidate')) return;
      if (form.method?.toLowerCase() !== 'post') return;

      if (!validateForm(form)) e.preventDefault();
    });
  }

  // ─── Form: Double-Submit Prevention ────────────────────────────────

  /**
   * Disable the submit button on first click for all POST forms.
   */
  function initDoubleSubmitGuard() {
    const pendingButtons = new Set();

    document.addEventListener('submit', (e) => {
      if (e.defaultPrevented) return;

      const form = e.target;
      if (form.method?.toLowerCase() !== 'post') return;

      const btn = e.submitter
        ?? form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
      if (!btn || btn.disabled) return;

      btn.disabled = true;
      pendingButtons.add(btn);

      if (btn.tagName === 'BUTTON') {
        btn.dataset.originalLabel = btn.textContent;
        btn.textContent = 'Submitting\u2026';
      }
    });

    window.addEventListener('pageshow', (e) => {
      if (e.persisted && document.querySelector('form[method="post"] input[name="csrf_token"]')) {
        location.reload();
        return;
      }

      for (const btn of pendingButtons) {
        btn.disabled = false;
        if (btn.dataset.originalLabel) {
          btn.textContent = btn.dataset.originalLabel;
          delete btn.dataset.originalLabel;
        }
      }
      pendingButtons.clear();
    });
  }

  // ─── Pagination Smooth Scroll ──────────────────────────────────────

  function initPaginationScroll() {
    if (sessionStorage.getItem('nt-paginated')) {
      sessionStorage.removeItem('nt-paginated');
      const main = document.getElementById('main-content');
      main?.scrollIntoView({ behavior: 'smooth' });
    }

    document.addEventListener('click', (e) => {
      const link = e.target.closest('.pagination a');
      if (link) sessionStorage.setItem('nt-paginated', '1');
    });
  }

  // ─── Scroll-to-Top Button ───────────────────────────────────────────

  function initScrollToTop() {
    const main = document.getElementById('main-content');
    if (!main) return;

    const sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    setRule('scroll-sentinel', '#scroll-sentinel', 'height:1px;margin:0;padding:0;border:0');
    sentinel.id = 'scroll-sentinel';
    main.prepend(sentinel);

    const btn = document.createElement('button');
    btn.id = 'scroll-top';
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(btn);

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          btn.removeAttribute('data-visible');
        } else {
          btn.setAttribute('data-visible', '');
        }
      },
      { rootMargin: '-300px 0px 0px 0px' }
    );
    observer.observe(sentinel);

    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ─── Lazy Image Fade-In ─────────────────────────────────────────────

  function observeLazyImage(img) {
    if (img.dataset.loaded !== undefined) return;

    if (img.complete) {
      img.dataset.loaded = '';
    } else {
      img.addEventListener('load', () => { img.dataset.loaded = ''; }, { once: true });
      img.addEventListener('error', () => { img.dataset.loaded = ''; }, { once: true });
    }
  }

  function initLazyFadeIn() {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    for (const img of document.querySelectorAll('img[loading="lazy"]')) {
      observeLazyImage(img);
    }

    new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (node.nodeType !== Node.ELEMENT_NODE) continue;

          if (node.matches('img[loading="lazy"]')) {
            observeLazyImage(node);
          } else {
            for (const img of node.querySelectorAll('img[loading="lazy"]')) {
              observeLazyImage(img);
            }
          }
        }
      }
    }).observe(document.body, { childList: true, subtree: true });
  }

  // ─── Namespace ─────────────────────────────────────────────────────

  window.NT = Object.freeze({
    fetch: ntFetch,
    toast,
    confirm: ntConfirm,
    style: Object.freeze({ setRule, removeRule }),
    applyFocalPoints,
    focus: Object.freeze({ trap: trapFocus, announce }),
    form: Object.freeze({
      trackDirty,
      charCounter,
      imagePreview,
      validate: validateForm,
    }),
  });

  // ─── Init ──────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', () => {
    migrateFlashMessages();
    initPaginationScroll();
    initFormValidation();
    initDoubleSubmitGuard();
    applyFocalPoints();
    initScrollToTop();
    initLazyFadeIn();
  });

})();
