'use strict';

class AdminDeleteConfirm {
  static #instance = null;

  #abortController = new AbortController();
  #pending = false;

  constructor() {
    document.addEventListener('submit', this.#handleSubmit, { signal: this.#abortController.signal });
  }

  /** @returns {AdminDeleteConfirm|null} */
  static init() {
    if (AdminDeleteConfirm.#instance) return AdminDeleteConfirm.#instance;
    return (AdminDeleteConfirm.#instance = new AdminDeleteConfirm());
  }

  destroy() {
    this.#abortController.abort();
  }

  static reinit() {
    if (AdminDeleteConfirm.#instance) {
      AdminDeleteConfirm.#instance.destroy();
      AdminDeleteConfirm.#instance = null;
    }
    return AdminDeleteConfirm.init();
  }

  /** @param {SubmitEvent} e */
  #handleSubmit = async (e) => {
    const form = e.target;
    if (!form.matches('[data-category-delete], [data-delete-form]')) return;

    const button = form.querySelector('button[data-confirm]');
    if (!button) return;

    e.preventDefault();

    if (this.#pending) return;
    this.#pending = true;

    try {
      const message = button.getAttribute('data-confirm');
      const confirmed = await NT.confirm(`${message}\n\nProceed with deletion?`);
      if (!confirmed) return;

      if (!form.querySelector('input[name="force"]')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'force';
        input.value = '1';
        form.appendChild(input);
      }

      form.submit();
    } finally {
      this.#pending = false;
    }
  };
}

AdminDeleteConfirm.init();

class RoleChangeConfirm {
  static #instance = null;

  #dialog = document.querySelector('[data-role-confirm]');
  #message = this.#dialog?.querySelector('[data-role-confirm-message]');
  #pendingForm = null;
  #pendingSelect = null;
  #abortController = new AbortController();

  constructor() {
    const opts = { signal: this.#abortController.signal };
    document.addEventListener('submit', this.#handleSubmit, opts);
    this.#dialog.addEventListener('close', this.#handleClose, opts);
  }

  /** @returns {RoleChangeConfirm|null} */
  static init() {
    if (RoleChangeConfirm.#instance) return RoleChangeConfirm.#instance;
    if (!document.querySelector('[data-role-confirm]')) return null;
    return (RoleChangeConfirm.#instance = new RoleChangeConfirm());
  }

  destroy() {
    this.#abortController.abort();
  }

  static reinit() {
    if (RoleChangeConfirm.#instance) {
      RoleChangeConfirm.#instance.destroy();
      RoleChangeConfirm.#instance = null;
    }
    return RoleChangeConfirm.init();
  }

  /** @param {SubmitEvent} e */
  #handleSubmit = (e) => {
    const form = e.target;
    if (!form.matches('[data-role-form]')) return;

    const select = form.querySelector('[data-role-select]');
    if (!select) return;

    const original = select.dataset.original;
    if (select.value === original) return;

    e.preventDefault();

    const label = select.getAttribute('aria-label') ?? '';
    const name = label.replace(/^Role for /, '');
    const fromLabel = original === 'admin' ? 'Admin' : 'Member';
    const toLabel = select.value === 'admin' ? 'Admin' : 'Member';

    this.#message.textContent = `Change ${name} from ${fromLabel} to ${toLabel}?`;
    this.#pendingForm = form;
    this.#pendingSelect = select;
    this.#dialog.showModal();
  };

  #handleClose = () => {
    if (this.#dialog.returnValue === 'confirm' && this.#pendingForm) {
      this.#pendingForm.submit();
    } else if (this.#pendingSelect) {
      this.#pendingSelect.value = this.#pendingSelect.dataset.original;
    }

    this.#pendingForm = null;
    this.#pendingSelect = null;
  };
}

RoleChangeConfirm.init();

class DeleteUserConfirm {
  static #instance = null;

  #abortController = new AbortController();
  #pending = false;

  constructor() {
    document.addEventListener('submit', this.#handleSubmit, { signal: this.#abortController.signal });
  }

  /** @returns {DeleteUserConfirm|null} */
  static init() {
    if (DeleteUserConfirm.#instance) return DeleteUserConfirm.#instance;
    if (!document.querySelector('[data-delete-user-form]')) return null;
    return (DeleteUserConfirm.#instance = new DeleteUserConfirm());
  }

  destroy() {
    this.#abortController.abort();
  }

  static reinit() {
    if (DeleteUserConfirm.#instance) {
      DeleteUserConfirm.#instance.destroy();
      DeleteUserConfirm.#instance = null;
    }
    return DeleteUserConfirm.init();
  }

  /** @param {SubmitEvent} e */
  #handleSubmit = async (e) => {
    const form = e.target;
    if (!form.matches('[data-delete-user-form]')) return;

    e.preventDefault();

    if (this.#pending) return;
    this.#pending = true;

    try {
      const button = form.querySelector('[data-delete-user-name]');
      const name = button?.dataset.deleteUserName ?? 'this user';
      const confirmed = await NT.confirm(
        `Delete ${name}?\n\nThis soft-deletes the account. You can purge it permanently afterward.`
      );
      if (!confirmed) return;

      form.submit();
    } finally {
      this.#pending = false;
    }
  };
}

DeleteUserConfirm.init();

class PurgeConfirm {
  static #instance = null;

  #dialog = document.querySelector('[data-purge-confirm]');
  #form = this.#dialog?.querySelector('[data-purge-form]');
  #nameDisplay = this.#dialog?.querySelector('[data-purge-expected-name]');
  #nameInput = this.#dialog?.querySelector('[data-purge-name-input]');
  #submitBtn = this.#dialog?.querySelector('[data-purge-submit]');
  #cancelBtn = this.#dialog?.querySelector('[data-purge-cancel]');
  #expectedName = '';
  #abortController = new AbortController();

  constructor() {
    const opts = { signal: this.#abortController.signal };
    document.addEventListener('click', this.#handleTrigger, opts);
    this.#nameInput.addEventListener('input', this.#handleInput, opts);
    this.#cancelBtn.addEventListener('click', this.#handleCancel, opts);
    this.#dialog.addEventListener('cancel', this.#handleCancel, opts);
  }

  /** @returns {PurgeConfirm|null} */
  static init() {
    if (PurgeConfirm.#instance) return PurgeConfirm.#instance;
    if (!document.querySelector('[data-purge-confirm]')) return null;
    return (PurgeConfirm.#instance = new PurgeConfirm());
  }

  destroy() {
    this.#abortController.abort();
  }

  static reinit() {
    if (PurgeConfirm.#instance) {
      PurgeConfirm.#instance.destroy();
      PurgeConfirm.#instance = null;
    }
    return PurgeConfirm.init();
  }

  /** @param {MouseEvent} e */
  #handleTrigger = (e) => {
    const btn = e.target.closest('[data-purge-trigger]');
    if (!btn) return;

    const id = btn.dataset.purgeId;
    this.#expectedName = btn.dataset.purgeName;

    this.#form.action = `/admin/users/${encodeURIComponent(id)}/purge`;
    this.#nameDisplay.textContent = this.#expectedName;
    this.#nameInput.value = '';
    this.#submitBtn.disabled = true;
    this.#dialog.showModal();
  };

  #handleInput = () => {
    this.#submitBtn.disabled =
      this.#nameInput.value.trim().toLowerCase() !== this.#expectedName.toLowerCase();
  };

  #handleCancel = () => {
    this.#dialog.close();
    this.#nameInput.value = '';
    this.#submitBtn.disabled = true;
  };
}

PurgeConfirm.init();

class AdminRouter {
  static #instance = null;

  static #ADMIN_PATTERN = /^\/admin(?:\/|$)/;
  static #PREFETCH_TTL = 30_000;
  static #MAX_CACHE = 8;
  static #REDUCED_MOTION = matchMedia('(prefers-reduced-motion: reduce)');

  #content = document.querySelector('[data-admin-content]');
  #header = document.querySelector('[data-admin-header]');
  #section = document.querySelector('main > section[aria-labelledby]');
  #nav = document.querySelector('[data-admin-body] > nav');
  #abortController = null;
  #prefetchCache = new Map();
  #ac = new AbortController();
  #navIndex = 0;

  #loadedCss = new Set(
    [...document.querySelectorAll('link[rel="stylesheet"]')].map(l => l.href)
  );

  constructor() {
    const opts = { signal: this.#ac.signal };
    document.addEventListener('click', this.#handleClick, opts);
    document.addEventListener('submit', this.#handleSubmit, opts);
    window.addEventListener('popstate', this.#handlePopstate, opts);

    this.#nav?.addEventListener('pointerenter', this.#handleHover, { ...opts, capture: true });

    history.replaceState({ adminNav: true, idx: this.#navIndex }, '');
  }

  /** @returns {AdminRouter|null} */
  static init() {
    if (AdminRouter.#instance) return AdminRouter.#instance;
    if (!document.querySelector('[data-admin-content]')) return null;
    return (AdminRouter.#instance = new AdminRouter());
  }

  /** @param {MouseEvent} e */
  #handleClick = (e) => {
    const link = e.target.closest('a[href]');
    if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    if (link.matches('[data-back]') && this.#navIndex > 0) {
      e.preventDefault();
      history.back();
      return;
    }

    const url = new URL(link.href, location.origin);
    if (url.origin !== location.origin) return;
    if (!AdminRouter.#ADMIN_PATTERN.test(url.pathname)) return;

    e.preventDefault();
    this.#navigateTo(url.href);
  };

  /** @param {SubmitEvent} e */
  #handleSubmit = (e) => {
    const form = e.target;
    if (form.method !== 'get' || !form.matches('[data-admin-filters]')) return;

    e.preventDefault();
    const url = new URL(form.getAttribute('action'), location.origin);
    new FormData(form).forEach((v, k) => {
      if (v !== '') url.searchParams.set(k, v);
    });
    this.#navigateTo(url.href);
  };

  /** @param {PopStateEvent} e */
  #handlePopstate = (e) => {
    if (AdminRouter.#ADMIN_PATTERN.test(location.pathname)) {
      this.#navIndex = e.state?.idx ?? 0;
      this.#navigateTo(location.href, false);
    }
  };

  /** @param {PointerEvent} e */
  #handleHover = (e) => {
    const link = e.target.closest?.('a[href]');
    if (!link) return;

    const url = new URL(link.href, location.origin);
    if (url.origin !== location.origin) return;
    if (!AdminRouter.#ADMIN_PATTERN.test(url.pathname)) return;

    this.#prefetch(url.href);
  };

  /**
   * @param {string} url
   * @param {boolean} pushState
   */
  async #navigateTo(url, pushState = true) {
    this.#abortController?.abort();
    this.#abortController = new AbortController();

    if (!this.#content) {
      location.href = url;
      return;
    }

    this.#content.setAttribute('aria-busy', 'true');
    this.#content.setAttribute('data-transition', 'fade-out');

    try {
      let html, heading, icon, description, sectionId, title, css, js;
      const cached = this.#prefetchCache.get(url);
      if (cached && Date.now() - cached.time < AdminRouter.#PREFETCH_TTL && cached.isPartial) {
        html = cached.html;
        ({ heading, icon, description, sectionId, title, css, js } = cached);
      } else {
        const res = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: this.#abortController.signal,
        });

        if (!res.ok) {
          location.href = url;
          return;
        }

        html = await res.text();

        if (res.headers.get('X-Partial') !== '1') {
          const doc = NT.parseHtmlDocument(html);
          const newContent = doc.querySelector('[data-admin-content]');
          if (newContent) {
            html = newContent.innerHTML;
          } else {
            location.href = url;
            return;
          }
        }

        heading = decodeURIComponent(res.headers.get('X-Admin-Heading') ?? '');
        icon = res.headers.get('X-Admin-Icon') ?? '';
        description = decodeURIComponent(res.headers.get('X-Admin-Description') ?? '');
        sectionId = res.headers.get('X-Admin-Section-Id') ?? '';
        title = decodeURIComponent(res.headers.get('X-Page-Title') ?? '');
        css = res.headers.get('X-Page-Css');
        js = res.headers.get('X-Page-Js');
      }

      await this.#waitForAnimation();

      this.#saveDetailsState();
      this.#updateHeader(heading, icon, description, sectionId);
      this.#updatePageTitle(title);
      this.#ensureCss(css);
      this.#content.innerHTML = NT.sanitizeHtml(html);
      this.#content.setAttribute('data-transition', 'fade-in');
      this.#content.removeAttribute('aria-busy');

      if (pushState) {
        this.#navIndex++;
        history.pushState({ adminNav: true, idx: this.#navIndex }, '', url);
      }

      this.#updateNavActiveState(url);
      await this.#syncScripts(js);
      AdminRouter.#reinit();
      this.#restoreDetailsState();
      window.scrollTo({ top: 0, behavior: 'instant' });
      this.#content.focus({ preventScroll: true });

      this.#content.addEventListener('animationend', () => {
        this.#content.removeAttribute('data-transition');
      }, { once: true });

    } catch (err) {
      if (err.name !== 'AbortError') {
        this.#content.removeAttribute('aria-busy');
        this.#content.removeAttribute('data-transition');
        location.href = url;
      }
    }
  }

  /**
   * @param {string} heading
   * @param {string} icon
   * @param {string} description
   * @param {string} sectionId
   */
  #updateHeader(heading, icon, description, sectionId) {
    if (!this.#header) return;

    const h1 = this.#header.querySelector('h1');
    const p = this.#header.querySelector('p');
    const i = h1?.querySelector('i');

    if (sectionId && this.#section) {
      this.#section.setAttribute('aria-labelledby', sectionId);
      if (h1) h1.id = sectionId;
    }

    if (h1 && heading) {
      if (i) {
        i.className = icon;
        h1.childNodes[h1.childNodes.length - 1].textContent = ' ' + heading;
      } else {
        h1.textContent = heading;
      }
    }

    if (description) {
      if (p) {
        p.textContent = description;
        p.hidden = false;
      } else {
        const newP = document.createElement('p');
        newP.textContent = description;
        this.#header.appendChild(newP);
      }
    } else if (p) {
      p.hidden = true;
    }
  }

  /** @param {string} title */
  #updatePageTitle(title) {
    if (title) document.title = title;
  }

  /** @param {string} cssHeader */
  #ensureCss(cssHeader) {
    if (!cssHeader) return;
    cssHeader.split(',').map(s => s.trim()).filter(Boolean).forEach(href => {
      const absolute = new URL(href, location.origin).href;
      if (this.#loadedCss.has(absolute)) return;
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      document.head.appendChild(link);
      this.#loadedCss.add(absolute);
    });
  }

  /**
   * Inject page-specific scripts that aren't already loaded.
   *
   * @param {string} jsHeader Comma-separated list from X-Page-Js
   * @returns {Promise<void>}
   */
  async #syncScripts(jsHeader) {
    if (!jsHeader) return;
    const stripVersion = (s) => s.replace(/\?.*$/, '');
    const current = new Set(
      [...document.querySelectorAll('script[src]')]
        .map((s) => stripVersion(s.getAttribute('src')))
        .filter(Boolean),
    );
    const needed = jsHeader.split(',').map((s) => s.trim()).filter(Boolean);
    for (const src of needed) {
      if (current.has(stripVersion(src))) continue;
      await new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = NT.trustedScript(src);
        script.onload = resolve;
        script.onerror = resolve;
        document.head.appendChild(script);
      });
    }
  }

  /** @param {string} url */
  #updateNavActiveState(url) {
    if (!this.#nav) return;
    const path = new URL(url, location.origin).pathname;

    this.#nav.querySelectorAll('a[aria-current]').forEach(a => a.removeAttribute('aria-current'));

    let match = this.#nav.querySelector(`a[href="${CSS.escape(path)}"]`);
    if (!match && path.startsWith('/admin/tos')) {
      match = this.#nav.querySelector('a[href="/admin/tos"]');
    }
    match?.setAttribute('aria-current', 'page');
  }

  /** @returns {Promise<void>} */
  #waitForAnimation() {
    if (AdminRouter.#REDUCED_MOTION.matches) return Promise.resolve();

    return new Promise(resolve => {
      this.#content.addEventListener('animationend', resolve, { once: true });
      setTimeout(resolve, 200);
    });
  }

  /** @param {string} url */
  #prefetch(url) {
    if (this.#prefetchCache.has(url)) return;

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => {
        const isPartial = res.headers.get('X-Partial') === '1';
        const entry = {
          html: null,
          time: Date.now(),
          isPartial,
          heading: decodeURIComponent(res.headers.get('X-Admin-Heading') ?? ''),
          icon: res.headers.get('X-Admin-Icon') ?? '',
          description: decodeURIComponent(res.headers.get('X-Admin-Description') ?? ''),
          sectionId: res.headers.get('X-Admin-Section-Id') ?? '',
          title: decodeURIComponent(res.headers.get('X-Page-Title') ?? ''),
          css: res.headers.get('X-Page-Css') ?? '',
          js: res.headers.get('X-Page-Js') ?? '',
        };
        return res.text().then(html => { entry.html = html; return entry; });
      })
      .then(entry => {
        if (this.#prefetchCache.size >= AdminRouter.#MAX_CACHE) {
          const oldest = this.#prefetchCache.keys().next().value;
          this.#prefetchCache.delete(oldest);
        }
        this.#prefetchCache.set(url, entry);
        setTimeout(() => this.#prefetchCache.delete(url), AdminRouter.#PREFETCH_TTL);
      })
      .catch(() => {});
  }

  #saveDetailsState() {
    if (!this.#content) return;
    const state = {};
    for (const d of this.#content.querySelectorAll('details[aria-labelledby]')) {
      state[d.getAttribute('aria-labelledby')] = d.open;
    }
    sessionStorage.setItem('admin-details', JSON.stringify(state));
  }

  #restoreDetailsState() {
    if (!this.#content) return;
    try {
      const state = JSON.parse(sessionStorage.getItem('admin-details') ?? '{}');
      for (const d of this.#content.querySelectorAll('details[aria-labelledby]')) {
        const key = d.getAttribute('aria-labelledby');
        if (key in state) d.open = state[key];
      }
    } catch { /* ignore */ }
  }

  static #reinit() {
    AdminDeleteConfirm.reinit();
    RoleChangeConfirm.reinit();
    DeleteUserConfirm.reinit();
    PurgeConfirm.reinit();

    const content = document.querySelector('[data-admin-content]');
    if (content) {
      for (const flash of content.querySelectorAll('[data-flash]')) {
        NT.toast(flash.textContent.trim(), flash.getAttribute('data-flash'));
        flash.remove();
      }
    }
  }
}

AdminRouter.init();
