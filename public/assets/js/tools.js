'use strict';

// ─── Browse Mode Toggle ──────────────────────────────────────────────

class BrowseModeToggle {
  static #instance = null;

  #abortController = new AbortController();

  /** @param {HTMLElement} nav */
  constructor(nav) {
    const links = nav.querySelectorAll(':scope > a');
    nav.dataset.active = nav.querySelector(':scope > a:nth-child(2)[aria-current="page"]') ? 'end' : 'start';

    const { signal } = this.#abortController;
    for (const link of links) {
      link.addEventListener('click', () => {
        nav.dataset.active = link === links[links.length - 1] ? 'end' : 'start';
      }, { signal });
    }
  }

  /** @returns {BrowseModeToggle|null} */
  static init() {
    if (BrowseModeToggle.#instance) return BrowseModeToggle.#instance;
    const nav = document.querySelector('nav[aria-label="Browse mode"]');
    if (!nav) return null;
    return (BrowseModeToggle.#instance = new BrowseModeToggle(nav));
  }

  destroy() {
    this.#abortController.abort();
    BrowseModeToggle.#instance = null;
  }
}

// ─── Fuel Type Toggle ────────────────────────────────────────────────

class FuelTypeToggle {
  static #instance = null;

  #abortController = new AbortController();

  /**
   * @param {HTMLInputElement} checkbox
   * @param {HTMLElement} group
   */
  constructor(checkbox, group) {
    group.hidden = !checkbox.checked;
    checkbox.addEventListener('change', () => { group.hidden = !checkbox.checked; }, {
      signal: this.#abortController.signal,
    });
  }

  /** @returns {FuelTypeToggle|null} */
  static init() {
    if (FuelTypeToggle.#instance) return FuelTypeToggle.#instance;
    const checkbox = document.getElementById('uses-fuel');
    const group = document.getElementById('fuel-type-group');
    if (!checkbox || !group) return null;
    return (FuelTypeToggle.#instance = new FuelTypeToggle(checkbox, group));
  }

  destroy() {
    this.#abortController.abort();
    FuelTypeToggle.#instance = null;
  }
}

// ─── Fee Slider ──────────────────────────────────────────────────────

class FeeSlider {
  static #instance = null;

  #abortController = new AbortController();

  /**
   * @param {HTMLInputElement} slider
   * @param {HTMLElement} display
   */
  constructor(slider, display) {
    slider.addEventListener('input', () => {
      display.textContent = `$${slider.value}`;
      slider.setAttribute('aria-valuenow', slider.value);
      slider.setAttribute('aria-valuetext', `$${slider.value}`);
    }, { signal: this.#abortController.signal });
  }

  /** @returns {FeeSlider|null} */
  static init() {
    if (FeeSlider.#instance) return FeeSlider.#instance;
    const slider = document.getElementById('filter-max-fee');
    const display = document.getElementById('fee-display');
    if (!slider || !display) return null;
    return (FeeSlider.#instance = new FeeSlider(slider, display));
  }

  destroy() {
    this.#abortController.abort();
    FeeSlider.#instance = null;
  }
}

// ─── Browse Filter ───────────────────────────────────────────────────

class BrowseFilter {
  static #instance = null;
  static #DEBOUNCE_MS = 300;
  static #SKELETON_COUNT = 6;

  /** @type {HTMLElement} */
  #page;
  /** @type {HTMLFormElement} */
  #form;
  /** @type {HTMLElement|null} */
  #grid;
  /** @type {HTMLElement|null} */
  #countArea;
  /** @type {HTMLElement|null} */
  #emptyState;
  /** @type {HTMLInputElement|null} */
  #zipField;
  /** @type {HTMLSelectElement|null} */
  #radiusField;
  /** @type {HTMLSelectElement|null} */
  #categorySelect;
  /** @type {Map<string, string>} */
  #defaultCounts = new Map();
  #debounceTimer = null;
  /** @type {AbortController|null} */
  #fetchController = null;
  #currentPage = 1;
  #isPaginating = false;
  #abortController = new AbortController();

  /** @param {HTMLElement} page */
  constructor(page) {
    this.#page = page;
    this.#form = page.querySelector('form[role="search"]');
    this.#grid = page.querySelector('[role="list"]');
    this.#countArea = page.querySelector('[aria-live="polite"]');
    this.#emptyState = page.querySelector('section[aria-label="No results"]');
    this.#zipField = document.getElementById('filter-zip');
    this.#radiusField = document.getElementById('filter-radius');
    this.#categorySelect = document.getElementById('filter-category');

    this.#form.noValidate = true;

    const applyBtn = this.#form.querySelector('fieldset:last-of-type button[type="submit"]');
    if (applyBtn) applyBtn.hidden = true;

    const serverClearLink = this.#form.querySelector('fieldset a[data-intent="ghost"]');
    if (serverClearLink) serverClearLink.remove();

    if (this.#categorySelect) {
      for (const opt of this.#categorySelect.options) {
        if (opt.value !== '') this.#defaultCounts.set(opt.value, opt.textContent);
      }
    }

    this.#syncZipRequired();
    this.#updateClearButton();
    this.#bind();
  }

  /** @returns {BrowseFilter|null} */
  static init() {
    if (BrowseFilter.#instance) return BrowseFilter.#instance;
    const page = document.getElementById('browse-page');
    if (!page) return null;
    const form = page.querySelector('form[role="search"]');
    if (!form) return null;
    return (BrowseFilter.#instance = new BrowseFilter(page));
  }

  destroy() {
    clearTimeout(this.#debounceTimer);
    this.#fetchController?.abort();
    this.#abortController.abort();
    BrowseFilter.#instance = null;
  }

  #bind() {
    const { signal } = this.#abortController;
    this.#form.addEventListener('submit', this.#handleSubmit, { signal });
    this.#radiusField?.addEventListener('change', this.#handleImmediateChange, { signal });
    this.#radiusField?.addEventListener('change', this.#syncZipRequired, { signal });
    this.#zipField?.addEventListener('input', this.#syncZipRequired, { signal });

    const category = document.getElementById('filter-category');
    category?.addEventListener('change', this.#handleImmediateChange, { signal });

    const search = document.getElementById('browse-search');
    const maxFee = document.getElementById('filter-max-fee');
    search?.addEventListener('input', this.#handleDebouncedInput, { signal });
    this.#zipField?.addEventListener('input', this.#handleDebouncedInput, { signal });
    maxFee?.addEventListener('input', this.#handleDebouncedInput, { signal });

    this.#page.addEventListener('click', this.#handlePageClick, { signal });
    window.addEventListener('popstate', this.#handlePopstate, { signal });
    window.addEventListener('beforeunload', this.#handleBeforeUnload, { signal });
    window.addEventListener('pageshow', this.#handlePageshow, { signal });
  }

  #syncZipRequired = () => {
    if (!this.#zipField || !this.#radiusField) return;
    this.#zipField.required = this.#radiusField.value !== '' && !(/^\d{5}$/).test(this.#zipField.value);
  };

  #getBasePath() {
    return this.#form.action ? new URL(this.#form.action).pathname : '/tools';
  }

  #buildQueryString() {
    const data = new FormData(this.#form);
    const params = new URLSearchParams();

    for (const [key, value] of data) {
      if (key === 'page') continue;
      const trimmed = value.toString().trim();
      if (trimmed !== '') params.set(key, trimmed);
    }

    const slider = document.getElementById('filter-max-fee');
    if (slider && params.get('max_fee') === slider.max) {
      params.delete('max_fee');
    }

    if (this.#currentPage > 1) {
      params.set('page', String(this.#currentPage));
    }

    return params;
  }

  #hasActiveFilters() {
    const params = this.#buildQueryString();
    params.delete('page');

    const defaultZip = this.#form.dataset.defaultZip || '';
    const defaultRadius = this.#form.dataset.defaultRadius || '';

    if (defaultZip && defaultRadius
        && params.get('zip') === defaultZip
        && params.get('radius') === defaultRadius) {
      params.delete('zip');
      params.delete('radius');
    }

    return params.size > 0;
  }

  #showSkeletons() {
    if (!this.#grid) return;

    this.#grid.hidden = false;
    this.#grid.innerHTML = '';
    for (let i = 0; i < BrowseFilter.#SKELETON_COUNT; i++) {
      const card = document.createElement('div');
      card.setAttribute('role', 'listitem');
      card.setAttribute('aria-hidden', 'true');
      card.className = 'skeleton-card';
      this.#grid.appendChild(card);
    }

    if (this.#emptyState) this.#emptyState.hidden = true;
  }

  #updateResultCount(data) {
    if (!this.#countArea) return;

    const existing = this.#countArea.querySelector(':scope > p');
    const p = document.createElement('p');

    if (data.totalCount > 0) {
      const rangeStrong = document.createElement('strong');
      rangeStrong.textContent = `${data.rangeStart}\u2013${data.rangeEnd}`;
      const totalStrong = document.createElement('strong');
      totalStrong.textContent = data.totalCount.toLocaleString();
      const suffix = data.totalCount !== 1 ? 'tools' : 'tool';

      if (data.zip && data.radius) {
        const radiusStrong = document.createElement('strong');
        radiusStrong.textContent = data.radius;
        const zipStrong = document.createElement('strong');
        zipStrong.textContent = data.zip;
        p.append('Showing ', rangeStrong, ' of ', totalStrong, ` ${suffix} within `, radiusStrong, ' miles of ', zipStrong);
      } else {
        p.append('Showing ', rangeStrong, ' of ', totalStrong, ` ${suffix}`);
      }
    } else {
      p.textContent = 'No tools match your filters.';
    }

    if (existing) {
      existing.replaceWith(p);
    } else {
      this.#countArea.appendChild(p);
    }
  }

  #updateClearButton() {
    const fieldset = this.#form.querySelector('fieldset[aria-label="Filters"]');
    if (!fieldset) return;

    const existing = fieldset.querySelector('a[data-intent="ghost"]');
    const active = this.#hasActiveFilters();

    if (active && !existing) {
      const link = document.createElement('a');
      link.href = this.#getBasePath();
      link.setAttribute('role', 'button');
      link.dataset.intent = 'ghost';
      const icon = document.createElement('i');
      icon.className = 'fa-solid fa-xmark';
      icon.setAttribute('aria-hidden', 'true');
      link.append(icon, ' Clear Filters');
      fieldset.appendChild(link);
    } else if (!active && existing) {
      existing.remove();
    }
  }

  #updateCategoryCounts(counts) {
    if (!this.#categorySelect) return;

    for (const opt of this.#categorySelect.options) {
      if (opt.value === '') continue;

      if (counts) {
        const name = (this.#defaultCounts.get(opt.value) ?? opt.textContent).replace(/\s*\(\d+\)\s*$/, '');
        const n = counts[opt.value] ?? 0;
        opt.textContent = `${name} (${n})`;
      } else {
        opt.textContent = this.#defaultCounts.get(opt.value) ?? opt.textContent;
      }
    }
  }

  #updateEmptyState() {
    if (!this.#emptyState) return;

    const params = this.#buildQueryString();
    const term = params.get('q') || '';
    const radius = params.get('radius');
    const maxFee = params.get('max_fee');
    const hasFilters = params.size > 0;
    const basePath = this.#getBasePath();

    const icon = this.#emptyState.querySelector(':scope > i, :scope > img');
    const h2 = this.#emptyState.querySelector(':scope > h2');

    while (this.#emptyState.lastChild) this.#emptyState.lastChild.remove();

    if (icon) this.#emptyState.appendChild(icon);
    if (h2) this.#emptyState.appendChild(h2);

    if (hasFilters) {
      const ul = document.createElement('ul');

      if (term) {
        const li = document.createElement('li');
        li.append('No tools match \u201c', term, '\u201d \u2014 try a broader search term');
        ul.appendChild(li);
      }

      if (radius !== null && Number(radius) < 50) {
        const li = document.createElement('li');
        const widerParams = new URLSearchParams(params);
        widerParams.set('radius', '50');
        const a = document.createElement('a');
        a.href = `${basePath}?${widerParams.toString()}`;
        a.textContent = 'increasing your search distance';
        li.append('Try ', a);
        ul.appendChild(li);
      }

      if (maxFee !== null) {
        const li = document.createElement('li');
        li.textContent = 'Try raising or removing the max fee filter';
        ul.appendChild(li);
      }

      this.#emptyState.appendChild(ul);

      const clearLink = document.createElement('a');
      clearLink.href = basePath;
      clearLink.setAttribute('role', 'button');
      clearLink.dataset.intent = 'ghost';
      const clearIcon = document.createElement('i');
      clearIcon.className = 'fa-solid fa-arrow-rotate-left';
      clearIcon.setAttribute('aria-hidden', 'true');
      clearLink.append(clearIcon, ' Clear All Filters');
      this.#emptyState.appendChild(clearLink);
    } else {
      const p = document.createElement('p');
      p.textContent = 'No tools are currently available \u2014 check back soon.';
      this.#emptyState.appendChild(p);
    }
  }

  async #fetchFiltered({ replaceHistory = false, showLoading = true } = {}) {
    clearTimeout(this.#debounceTimer);

    this.#fetchController?.abort();
    this.#fetchController = new AbortController();

    const params = this.#buildQueryString();
    const basePath = this.#getBasePath();
    const qs = params.toString();
    const url = qs ? `${basePath}?${qs}` : basePath;

    if (showLoading) {
      this.#showSkeletons();
    } else if (this.#grid) {
      NT.style.setRule('grid-lock', '#browse-page [role="list"]', `min-height:${this.#grid.offsetHeight}px`);
    }

    try {
      const res = await NT.fetch(url, { signal: this.#fetchController.signal });

      if (!res.ok) {
        this.#form.submit();
        return;
      }

      const data = await res.json();

      if (this.#grid) {
        this.#grid.innerHTML = data.html;
      }

      NT.style.removeRule('grid-lock');

      const currentPagination = this.#page.querySelector('nav[aria-label="Pagination"]');
      if (currentPagination) {
        if (data.paginationHtml.trim()) {
          currentPagination.outerHTML = data.paginationHtml;
        } else {
          currentPagination.remove();
        }
      } else if (data.paginationHtml.trim() && this.#grid) {
        this.#grid.insertAdjacentHTML('afterend', data.paginationHtml);
      }

      this.#updateResultCount(data);
      this.#updateClearButton();
      this.#updateCategoryCounts(data.categoryCounts ?? null);

      if (data.totalCount === 0) {
        if (this.#grid) {
          this.#grid.innerHTML = '';
          this.#grid.hidden = true;
        }
        if (this.#emptyState) {
          this.#updateEmptyState();
          this.#emptyState.hidden = false;
        }
      } else {
        if (this.#grid) this.#grid.hidden = false;
        if (this.#emptyState) this.#emptyState.hidden = true;
      }

      if (replaceHistory) {
        history.replaceState(null, '', url);
      } else {
        history.pushState(null, '', url);
      }

      if (this.#isPaginating) {
        this.#isPaginating = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    } catch (err) {
      NT.style.removeRule('grid-lock');
      if (err.name === 'AbortError') return;
      NT.toast('Something went wrong. Reloading\u2026', 'error');
      this.#form.submit();
    }
  }

  #handleSubmit = (e) => {
    e.preventDefault();
    clearTimeout(this.#debounceTimer);
    this.#currentPage = 1;
    this.#fetchFiltered({ showLoading: false });
  };

  #handleImmediateChange = () => {
    clearTimeout(this.#debounceTimer);
    this.#currentPage = 1;
    this.#fetchFiltered();
  };

  #handleDebouncedInput = () => {
    clearTimeout(this.#debounceTimer);
    this.#currentPage = 1;
    this.#debounceTimer = setTimeout(() => this.#fetchFiltered({ showLoading: false }), BrowseFilter.#DEBOUNCE_MS);
  };

  #handlePageClick = (e) => {
    const paginationLink = e.target.closest('nav[aria-label="Pagination"] a');
    if (paginationLink) {
      e.preventDefault();
      const linkUrl = new URL(paginationLink.href);
      this.#currentPage = parseInt(linkUrl.searchParams.get('page') ?? '1', 10);
      this.#isPaginating = true;
      this.#fetchFiltered();
      return;
    }

    const clearLink = e.target.closest('fieldset a[data-intent="ghost"]');
    if (clearLink) {
      e.preventDefault();

      for (const el of this.#form.elements) {
        if (!el.name || el.name === 'page' || el.type === 'hidden') continue;

        if (el.tagName === 'SELECT') {
          el.selectedIndex = 0;
        } else if (el.type === 'range') {
          el.value = el.max;
        } else {
          el.value = '';
        }
      }

      const defaultZip = this.#form.dataset.defaultZip || '';
      const defaultRadius = this.#form.dataset.defaultRadius || '';

      if (defaultZip && defaultRadius) {
        const zf = document.getElementById('filter-zip');
        const rf = document.getElementById('filter-radius');
        if (zf) zf.value = defaultZip;
        if (rf) rf.value = defaultRadius;
      }

      document.getElementById('filter-max-fee')?.dispatchEvent(new Event('input', { bubbles: true }));
      this.#syncZipRequired();
      this.#currentPage = 1;
      this.#fetchFiltered();
    }
  };

  #handlePopstate = () => {
    const params = new URLSearchParams(window.location.search);

    for (const el of this.#form.elements) {
      if (!el.name || el.name === 'page') continue;

      const urlValue = params.get(el.name);

      if (el.tagName === 'SELECT') {
        const option = urlValue !== null
          ? el.querySelector(`option[value="${CSS.escape(urlValue)}"]`)
          : el.querySelector('option:first-child');
        if (option) el.value = option.value;
      } else if (el.type !== 'hidden') {
        el.value = urlValue ?? el.defaultValue;
      }
    }

    this.#syncZipRequired();
    this.#currentPage = parseInt(params.get('page') ?? '1', 10);
    this.#fetchFiltered({ replaceHistory: true });
  };

  #handleBeforeUnload = () => {
    clearTimeout(this.#debounceTimer);
    this.#fetchController?.abort();
  };

  #handlePageshow = (e) => {
    if (!e.persisted) return;
    this.#fetchController = new AbortController();
    this.#currentPage = parseInt(new URLSearchParams(window.location.search).get('page') ?? '1', 10);
    this.#fetchFiltered({ replaceHistory: true });
  };
}

// ─── View Toggle ─────────────────────────────────────────────────────

class ViewToggle {
  static #instance = null;
  static #STORAGE_KEY = 'nt-view-preference';

  /** @type {HTMLElement} */
  #grid;
  /** @type {HTMLButtonElement} */
  #gridBtn;
  /** @type {HTMLButtonElement} */
  #listBtn;
  /** @type {MediaQueryList} */
  #mql;
  #abortController = new AbortController();

  /**
   * @param {HTMLElement} grid
   * @param {HTMLElement} summary
   */
  constructor(grid, summary) {
    this.#grid = grid;
    this.#mql = window.matchMedia('(max-width: 600px)');

    const toolbar = document.createElement('div');
    toolbar.setAttribute('data-view-toggle', '');
    toolbar.setAttribute('role', 'group');
    toolbar.setAttribute('aria-label', 'View mode');

    this.#gridBtn = document.createElement('button');
    this.#gridBtn.type = 'button';
    this.#gridBtn.innerHTML = '<i class="fa-solid fa-grip" aria-hidden="true"></i>';
    this.#gridBtn.setAttribute('aria-label', 'Grid view');
    this.#gridBtn.title = 'Grid view';
    this.#gridBtn.dataset.view = 'grid';

    this.#listBtn = document.createElement('button');
    this.#listBtn.type = 'button';
    this.#listBtn.innerHTML = '<i class="fa-solid fa-list" aria-hidden="true"></i>';
    this.#listBtn.setAttribute('aria-label', 'List view');
    this.#listBtn.title = 'List view';
    this.#listBtn.dataset.view = 'list';

    toolbar.append(this.#gridBtn, this.#listBtn);
    summary.appendChild(toolbar);

    this.#setView(this.#getPreferred());

    const { signal } = this.#abortController;
    this.#mql.addEventListener('change', () => this.#setView(this.#getPreferred()), { signal });
    toolbar.addEventListener('click', this.#handleClick, { signal });
  }

  /** @returns {ViewToggle|null} */
  static init() {
    if (ViewToggle.#instance) return ViewToggle.#instance;
    const page = document.getElementById('browse-page');
    if (!page) return null;
    const grid = page.querySelector('[role="list"]');
    const summary = page.querySelector('[aria-live="polite"]');
    if (!grid || !summary) return null;
    return (ViewToggle.#instance = new ViewToggle(grid, summary));
  }

  destroy() {
    this.#abortController.abort();
    ViewToggle.#instance = null;
  }

  #getPreferred() {
    return localStorage.getItem(ViewToggle.#STORAGE_KEY) === 'list' ? 'list' : 'grid';
  }

  #setView(mode) {
    const effective = this.#mql.matches ? 'grid' : mode;
    this.#grid.dataset.view = effective;
    this.#gridBtn.setAttribute('aria-pressed', effective === 'grid');
    this.#listBtn.setAttribute('aria-pressed', effective === 'list');
    if (!this.#mql.matches) localStorage.setItem(ViewToggle.#STORAGE_KEY, mode);
  }

  #handleClick = (e) => {
    const btn = e.target.closest('button[data-view]');
    if (btn) this.#setView(btn.dataset.view);
  };
}

// ─── Photo Queue ─────────────────────────────────────────────────────

class PhotoQueue {
  static #instance = null;
  static #MAX_FILES = 6;
  static #MAX_SIZE = 5 * 1024 * 1024;
  static #ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

  /** @type {HTMLOListElement} */
  #queue;
  /** @type {HTMLElement} */
  #dropZone;
  /** @type {HTMLInputElement} */
  #fileInput;
  /** @type {HTMLElement} */
  #dataContainer;
  #queuedPhotos = [];
  #primaryIndex = 0;
  #repositionIndex = -1;
  #dragItem = null;
  #dragCounter = 0;
  #abortController = new AbortController();

  /**
   * @param {HTMLOListElement} queue
   * @param {HTMLElement} dropZone
   * @param {HTMLInputElement} fileInput
   * @param {HTMLElement} dataContainer
   */
  constructor(queue, dropZone, fileInput, dataContainer) {
    this.#queue = queue;
    this.#dropZone = dropZone;
    this.#fileInput = fileInput;
    this.#dataContainer = dataContainer;

    if (NT.crop) {
      NT.crop.onConfirm((mode, data) => {
        if (mode === 'upload') {
          this.#queuedPhotos.push({ file: data.file, focalX: data.focalX, focalY: data.focalY, altText: '' });
          NT.crop.close();
          this.#renderQueue();
          const lastLi = this.#queue.querySelector('li:last-child');
          lastLi?.focus({ preventScroll: true });
          requestAnimationFrame(() => {
            lastLi?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          });
        } else if (mode === 'reposition' && this.#repositionIndex >= 0) {
          this.#queuedPhotos[this.#repositionIndex].focalX = data.focalX;
          this.#queuedPhotos[this.#repositionIndex].focalY = data.focalY;
          NT.crop.close();
          this.#renderQueue();
          this.#queue.querySelectorAll('li[data-queue-index]')[this.#repositionIndex]?.focus();
          this.#repositionIndex = -1;
        }
      });
    }

    this.#bind();
    this.#updateHint();
  }

  /** @returns {PhotoQueue|null} */
  static init() {
    if (PhotoQueue.#instance) return PhotoQueue.#instance;
    const queue = document.getElementById('photo-queue');
    const dropZone = document.getElementById('photo-drop-zone');
    const fileInput = document.getElementById('tool-photos');
    const dataContainer = document.getElementById('photo-queue-data');
    if (!queue || !dropZone || !fileInput || !dataContainer) return null;
    if (document.getElementById('gallery-manager')) return null;
    return (PhotoQueue.#instance = new PhotoQueue(queue, dropZone, fileInput, dataContainer));
  }

  destroy() {
    this.#abortController.abort();
    PhotoQueue.#instance = null;
  }

  #bind() {
    const { signal } = this.#abortController;

    this.#queue.addEventListener('click', this.#handleQueueClick, { signal });
    this.#queue.addEventListener('input', this.#handleQueueInput, { signal });
    this.#queue.addEventListener('dragstart', this.#handleDragStart, { signal });
    this.#queue.addEventListener('dragend', this.#handleDragEnd, { signal });
    this.#queue.addEventListener('dragover', this.#handleQueueDragOver, { signal });
    this.#queue.addEventListener('drop', this.#handleQueueDrop, { signal });
    this.#queue.addEventListener('keydown', this.#handleQueueKeydown, { signal });

    this.#dropZone.setAttribute('role', 'button');
    this.#dropZone.tabIndex = 0;
    this.#dropZone.setAttribute('aria-label', 'Choose a photo to upload');
    this.#dropZone.addEventListener('click', this.#handleDropZoneClick, { signal });
    this.#dropZone.addEventListener('keydown', this.#handleDropZoneKeydown, { signal });
    this.#dropZone.addEventListener('dragenter', this.#handleDragEnter, { signal });
    this.#dropZone.addEventListener('dragover', this.#handleDragOver, { signal });
    this.#dropZone.addEventListener('dragleave', this.#handleDragLeave, { signal });
    this.#dropZone.addEventListener('drop', this.#handleDrop, { signal });

    this.#fileInput.addEventListener('change', this.#handleFileChange, { signal });

    const createForm = this.#fileInput.closest('form');
    createForm?.addEventListener('submit', () => this.#syncFormInputs(), { signal });
  }

  static #validateFile(file) {
    if (!PhotoQueue.#ALLOWED_TYPES.includes(file.type)) {
      NT.toast('Invalid file type. Use JPEG, PNG, or WebP.', 'error');
      return false;
    }
    if (file.size > PhotoQueue.#MAX_SIZE) {
      NT.toast('File exceeds the 5 MB limit.', 'error');
      return false;
    }
    return true;
  }

  #syncFormInputs() {
    const dt = new DataTransfer();
    for (const entry of this.#queuedPhotos) dt.items.add(entry.file);
    this.#fileInput.files = dt.files;

    this.#dataContainer.innerHTML = '';

    const primaryInput = document.createElement('input');
    primaryInput.type = 'hidden';
    primaryInput.name = 'primary_index';
    primaryInput.value = String(this.#primaryIndex);
    this.#dataContainer.appendChild(primaryInput);

    for (let i = 0; i < this.#queuedPhotos.length; i++) {
      const xInput = document.createElement('input');
      xInput.type = 'hidden';
      xInput.name = 'focal_x[]';
      xInput.value = String(this.#queuedPhotos[i].focalX);
      this.#dataContainer.appendChild(xInput);

      const yInput = document.createElement('input');
      yInput.type = 'hidden';
      yInput.name = 'focal_y[]';
      yInput.value = String(this.#queuedPhotos[i].focalY);
      this.#dataContainer.appendChild(yInput);

      const altInput = document.createElement('input');
      altInput.type = 'hidden';
      altInput.name = 'alt_text[]';
      altInput.value = this.#queuedPhotos[i].altText;
      this.#dataContainer.appendChild(altInput);
    }
  }

  #updateHint() {
    const remaining = PhotoQueue.#MAX_FILES - this.#queuedPhotos.length;
    const hint = document.getElementById('photo-queue-hint');
    if (hint) {
      hint.textContent = `JPEG, PNG, or WebP \u2014 max 5 MB each. ${remaining} slot${remaining !== 1 ? 's' : ''} remaining.`;
    }
    this.#dropZone.closest('div')?.toggleAttribute('hidden', remaining <= 0);
    this.#dropZone.hidden = remaining <= 0;
  }

  #renderQueue() {
    for (const img of this.#queue.querySelectorAll('img')) {
      if (img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
    }
    this.#queue.innerHTML = '';

    if (this.#queuedPhotos.length === 0) {
      this.#queue.hidden = true;
      this.#syncFormInputs();
      this.#updateHint();
      return;
    }

    this.#queue.hidden = false;

    if (this.#primaryIndex >= this.#queuedPhotos.length) {
      this.#primaryIndex = 0;
    }

    for (let i = 0; i < this.#queuedPhotos.length; i++) {
      const entry = this.#queuedPhotos[i];
      const isPrimary = i === this.#primaryIndex;
      const li = document.createElement('li');
      li.dataset.queueIndex = String(i);
      li.draggable = true;
      li.tabIndex = 0;

      const blobUrl = URL.createObjectURL(entry.file);
      const focalX = entry.focalX;
      const focalY = entry.focalY;

      const img = document.createElement('img');
      img.src = blobUrl;
      img.alt = entry.altText || `Preview of ${entry.file.name}`;
      img.width = 400;
      img.height = 268;
      img.decoding = 'async';
      if (focalX !== 50 || focalY !== 50) {
        img.dataset.focalX = String(focalX);
        img.dataset.focalY = String(focalY);
      }

      const altDiv = document.createElement('div');
      const altLabel = document.createElement('label');
      altLabel.htmlFor = `queue-alt-${i}`;
      const altSpan = document.createElement('span');
      altSpan.className = 'visually-hidden';
      altSpan.textContent = `Alt text for photo ${i + 1}`;
      altLabel.appendChild(altSpan);
      const altInput = document.createElement('input');
      altInput.type = 'text';
      altInput.id = `queue-alt-${i}`;
      altInput.maxLength = 255;
      altInput.placeholder = 'Describe this photo\u2026';
      altInput.value = entry.altText;
      altInput.dataset.altQueue = String(i);
      altDiv.append(altLabel, altInput);

      const primaryDiv = document.createElement('div');
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'queue_primary';
      radio.id = `queue-primary-${i}`;
      radio.value = String(i);
      radio.checked = isPrimary;
      radio.dataset.primaryQueue = '';
      const primaryLabel = document.createElement('label');
      primaryLabel.htmlFor = `queue-primary-${i}`;
      if (isPrimary) {
        const starIcon = document.createElement('i');
        starIcon.className = 'fa-solid fa-star';
        starIcon.setAttribute('aria-hidden', 'true');
        primaryLabel.append(starIcon, ' Primary');
      } else {
        primaryLabel.textContent = 'Primary';
      }
      primaryDiv.append(radio, primaryLabel);

      const repoDiv = document.createElement('div');
      const repoBtn = document.createElement('button');
      repoBtn.type = 'button';
      repoBtn.dataset.repositionQueue = String(i);
      repoBtn.setAttribute('aria-label', 'Reposition this photo');
      const repoIcon = document.createElement('i');
      repoIcon.className = 'fa-solid fa-crop-simple';
      repoIcon.setAttribute('aria-hidden', 'true');
      repoBtn.append(repoIcon, ' Reposition');
      repoDiv.appendChild(repoBtn);

      const removeDiv = document.createElement('div');
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.dataset.removeQueue = String(i);
      removeBtn.dataset.intent = 'danger';
      removeBtn.setAttribute('aria-label', 'Remove this photo');
      const removeIcon = document.createElement('i');
      removeIcon.className = 'fa-solid fa-trash-can';
      removeIcon.setAttribute('aria-hidden', 'true');
      removeBtn.append(removeIcon, ' Remove');
      removeDiv.appendChild(removeBtn);

      li.append(img, altDiv, primaryDiv, repoDiv, removeDiv);

      this.#queue.appendChild(li);
      NT.applyFocalPoints(li);
    }

    this.#syncFormInputs();
    this.#updateHint();

    const cropHint = document.getElementById('gallery-crop-hint');
    if (cropHint) cropHint.hidden = this.#queuedPhotos.length === 0;
  }

  #handleQueueClick = (e) => {
    const removeBtn = e.target.closest('[data-remove-queue]');
    if (removeBtn) {
      const idx = parseInt(removeBtn.dataset.removeQueue, 10);
      if (this.#primaryIndex === idx) {
        this.#primaryIndex = 0;
      } else if (this.#primaryIndex > idx) {
        this.#primaryIndex--;
      }
      this.#queuedPhotos.splice(idx, 1);
      this.#renderQueue();
      return;
    }

    const primaryRadio = e.target.closest('[data-primary-queue]');
    if (primaryRadio) {
      this.#primaryIndex = parseInt(primaryRadio.value, 10);
      this.#renderQueue();
      return;
    }

    const repoBtn = e.target.closest('[data-reposition-queue]');
    if (repoBtn && NT.crop) {
      this.#repositionIndex = parseInt(repoBtn.dataset.repositionQueue, 10);
      const entry = this.#queuedPhotos[this.#repositionIndex];
      const blobUrl = URL.createObjectURL(entry.file);
      NT.crop.openReposition(blobUrl, entry.focalX, entry.focalY, `queue-${this.#repositionIndex}`);
    }
  };

  #handleQueueInput = (e) => {
    const altInput = e.target.closest('[data-alt-queue]');
    if (!altInput) return;
    const idx = parseInt(altInput.dataset.altQueue, 10);
    if (this.#queuedPhotos[idx]) {
      this.#queuedPhotos[idx].altText = altInput.value;
    }
  };

  #handleDragStart = (e) => {
    if (e.target.closest('button')) { e.preventDefault(); return; }
    const li = e.target.closest('li[data-queue-index]');
    if (!li) return;
    this.#dragItem = li;
    li.dataset.dragging = '';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', li.dataset.queueIndex);
  };

  #handleDragEnd = (e) => {
    const li = e.target.closest('li[data-queue-index]');
    if (li) delete li.dataset.dragging;
    this.#dragItem = null;
    for (const el of this.#queue.querySelectorAll('[data-drag-over]')) {
      delete el.dataset.dragOver;
    }
  };

  #handleQueueDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const target = e.target.closest('li[data-queue-index]');
    if (!target || target === this.#dragItem) return;
    for (const el of this.#queue.querySelectorAll('[data-drag-over]')) {
      delete el.dataset.dragOver;
    }
    target.dataset.dragOver = '';
  };

  #handleQueueDrop = (e) => {
    e.preventDefault();
    const target = e.target.closest('li[data-queue-index]');
    if (!target || !this.#dragItem || target === this.#dragItem) return;

    for (const el of this.#queue.querySelectorAll('[data-drag-over]')) {
      delete el.dataset.dragOver;
    }

    const fromIdx = parseInt(this.#dragItem.dataset.queueIndex, 10);
    const toIdx = parseInt(target.dataset.queueIndex, 10);

    this.#primaryIndex = PhotoQueue.#reindexPrimary(this.#primaryIndex, fromIdx, toIdx);
    const [moved] = this.#queuedPhotos.splice(fromIdx, 1);
    this.#queuedPhotos.splice(toIdx, 0, moved);
    this.#renderQueue();
  };

  static #reindexPrimary(primary, from, to) {
    if (primary === from) return to;
    if (from < to && primary > from && primary <= to) return primary - 1;
    if (from > to && primary >= to && primary < from) return primary + 1;
    return primary;
  }

  /** @param {KeyboardEvent} e */
  #handleQueueKeydown = (e) => {
    const li = e.target.closest('li[data-queue-index]');
    if (!li || e.target !== li) return;

    const items = Array.from(this.#queue.querySelectorAll('li[data-queue-index]'));
    const idx = items.indexOf(li);
    if (idx < 0) return;

    if ((e.key === 'ArrowUp' || e.key === 'ArrowLeft') && idx > 0) {
      e.preventDefault();
      this.#primaryIndex = PhotoQueue.#reindexPrimary(this.#primaryIndex, idx, idx - 1);
      const [moved] = this.#queuedPhotos.splice(idx, 1);
      this.#queuedPhotos.splice(idx - 1, 0, moved);
      this.#renderQueue();
      this.#queue.querySelectorAll('li[data-queue-index]')[idx - 1]?.focus();
    } else if ((e.key === 'ArrowDown' || e.key === 'ArrowRight') && idx < items.length - 1) {
      e.preventDefault();
      this.#primaryIndex = PhotoQueue.#reindexPrimary(this.#primaryIndex, idx, idx + 1);
      const [moved] = this.#queuedPhotos.splice(idx, 1);
      this.#queuedPhotos.splice(idx + 1, 0, moved);
      this.#renderQueue();
      this.#queue.querySelectorAll('li[data-queue-index]')[idx + 1]?.focus();
    }
  };

  #handleDropZoneClick = (e) => {
    if (e.target === this.#fileInput) return;
    this.#fileInput.click();
  };

  #handleDropZoneKeydown = (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      this.#fileInput.click();
    }
  };

  #handleFileChange = () => {
    const file = this.#fileInput.files?.[0];
    if (!file) return;
    if (this.#queuedPhotos.length >= PhotoQueue.#MAX_FILES) {
      NT.toast(`You can upload at most ${PhotoQueue.#MAX_FILES} photos.`, 'error');
      this.#fileInput.value = '';
      return;
    }
    if (!PhotoQueue.#validateFile(file)) { this.#fileInput.value = ''; return; }
    if (NT.crop) {
      NT.crop.openUpload(file, { icon: 'fa-solid fa-plus', label: 'Add' });
    }
  };

  #handleDragEnter = (e) => {
    e.preventDefault();
    this.#dragCounter++;
    this.#dropZone.dataset.dragover = '';
  };

  #handleDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
  };

  #handleDragLeave = () => {
    this.#dragCounter--;
    if (this.#dragCounter <= 0) {
      this.#dragCounter = 0;
      delete this.#dropZone.dataset.dragover;
    }
  };

  #handleDrop = (e) => {
    e.preventDefault();
    this.#dragCounter = 0;
    delete this.#dropZone.dataset.dragover;

    const file = Array.from(e.dataTransfer.files).find((f) => PhotoQueue.#ALLOWED_TYPES.includes(f.type));
    if (!file) {
      NT.toast('No valid image file found. Use JPEG, PNG, or WebP.', 'error');
      return;
    }
    if (this.#queuedPhotos.length >= PhotoQueue.#MAX_FILES) {
      NT.toast(`You can upload at most ${PhotoQueue.#MAX_FILES} photos.`, 'error');
      return;
    }
    if (!PhotoQueue.#validateFile(file)) return;
    if (NT.crop) {
      NT.crop.openUpload(file, { icon: 'fa-solid fa-plus', label: 'Add' });
    }
  };
}

// ─── Gallery Manager ─────────────────────────────────────────────────

class GalleryManager {
  static #instance = null;
  static #ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  static #MAX_SIZE = 5 * 1024 * 1024;

  static #setPrimaryLabel(label, isPrimary) {
    label.textContent = '';
    if (isPrimary) {
      const starIcon = document.createElement('i');
      starIcon.className = 'fa-solid fa-star';
      starIcon.setAttribute('aria-hidden', 'true');
      label.append(starIcon, ' Primary');
    } else {
      label.textContent = 'Primary';
    }
  }

  /** @type {HTMLElement} */
  #fieldset;
  #toolId;
  #busy = false;
  #dragItem = null;
  #dragCounter = 0;
  #abortController = new AbortController();

  /**
   * @param {HTMLElement} fieldset
   * @param {string} toolId
   */
  constructor(fieldset, toolId) {
    this.#fieldset = fieldset;
    this.#toolId = toolId;

    const gallery = document.getElementById('gallery-manager');
    if (gallery) this.#attachGalleryListeners(gallery);

    if (NT.crop) {
      NT.crop.onConfirm(async (mode, data) => {
        if (this.#busy) return;

        if (mode === 'upload') {
          await this.#handleUpload(data);
        } else if (mode === 'reposition') {
          await this.#handleReposition(data);
        }
      });
    }

    this.#bindDropZone();
  }

  /** @returns {GalleryManager|null} */
  static init() {
    if (GalleryManager.#instance) return GalleryManager.#instance;
    const gallery = document.getElementById('gallery-manager');
    const fieldset = gallery?.closest('fieldset')
      ?? document.querySelector('section[aria-labelledby="edit-tool-heading"] fieldset:last-of-type');
    if (!fieldset) return null;
    const toolId = gallery?.dataset.toolId ?? document.getElementById('add-photo')?.dataset.toolId;
    if (!toolId) return null;
    return (GalleryManager.#instance = new GalleryManager(fieldset, toolId));
  }

  destroy() {
    this.#abortController.abort();
    GalleryManager.#instance = null;
  }

  #setBusy(state) {
    this.#busy = state;
    const g = document.getElementById('gallery-manager');
    if (g) g.ariaBusy = state ? 'true' : 'false';
  }

  #getImageIds() {
    const g = document.getElementById('gallery-manager');
    if (!g) return [];
    return Array.from(g.querySelectorAll('li[data-image-id]'))
      .map((li) => parseInt(li.dataset.imageId, 10));
  }

  static #escapeAttr(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML.replace(/"/g, '&quot;');
  }

  #ensureGallery() {
    let g = document.getElementById('gallery-manager');
    if (g) return g;

    const emptyMsg = this.#fieldset.querySelector('#gallery-empty');
    if (emptyMsg) emptyMsg.remove();

    g = document.createElement('ol');
    g.id = 'gallery-manager';
    g.setAttribute('aria-label', 'Tool photos');
    g.dataset.toolId = this.#toolId;

    const hint = this.#fieldset.querySelector('#gallery-crop-hint');
    if (hint) {
      hint.after(g);
    } else {
      this.#fieldset.querySelector('legend').after(g);
    }

    this.#attachGalleryListeners(g);
    return g;
  }

  #updateSlotHint() {
    const g = document.getElementById('gallery-manager');
    const remaining = 6 - (g ? g.querySelectorAll('li[data-image-id]').length : 0);
    const uploadForm = document.getElementById('photo-upload-form');

    if (uploadForm) {
      uploadForm.hidden = remaining <= 0;
      const hint = document.getElementById('add-photo-hint');
      if (hint && remaining > 0) {
        hint.textContent = `JPEG, PNG, or WebP \u2014 max 5 MB. ${remaining} slot${remaining !== 1 ? 's' : ''} remaining.`;
      }
    }
  }

  static #validateFile(file) {
    if (!GalleryManager.#ALLOWED_TYPES.includes(file.type)) {
      NT.toast('Invalid file type. Use JPEG, PNG, or WebP.', 'error');
      return false;
    }
    if (file.size > GalleryManager.#MAX_SIZE) {
      NT.toast('File exceeds the 5 MB limit.', 'error');
      return false;
    }
    return true;
  }

  #buildLiHtml(img) {
    const thumb = GalleryManager.#escapeAttr(img.filename.replace(/\.(\w+)$/, '-360w.$1'));
    const altSafe = GalleryManager.#escapeAttr(img.alt_text || '');
    const csrfToken = GalleryManager.#escapeAttr(document.querySelector('meta[name="csrf-token"]')?.content ?? '');
    const fx = img.focal_x ?? 50;
    const fy = img.focal_y ?? 50;
    const focalAttrs = (fx !== 50 || fy !== 50) ? ` data-focal-x="${fx}" data-focal-y="${fy}"` : '';

    return `
      <img src="/uploads/tools/${thumb}"
           alt="${altSafe}"
           width="400" height="268"
           loading="lazy"
           decoding="async"${focalAttrs}>
      <div>
        <label for="alt-text-${img.id}">
          <span class="visually-hidden">Alt text for image ${img.id}</span>
        </label>
        <input type="text"
               id="alt-text-${img.id}"
               value=""
               maxlength="255"
               placeholder="Describe this photo\u2026"
               data-alt-input
               data-image-id="${img.id}">
      </div>
      <div>
        <input type="radio"
               name="primary_image"
               id="primary-${img.id}"
               value="${img.id}"
               ${img.is_primary ? 'checked' : ''}
               data-primary-radio>
        <label for="primary-${img.id}">
          ${img.is_primary ? '<i class="fa-solid fa-star" aria-hidden="true"></i> ' : ''}Primary
        </label>
      </div>
      <div>
        <button type="button"
                data-reposition
                data-image-id="${img.id}"
                aria-label="Reposition this photo">
          <i class="fa-solid fa-crop-simple" aria-hidden="true"></i> Reposition
        </button>
      </div>
      <form method="post" action="/tools/${this.#toolId}/images/${img.id}" data-delete-form>
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <button type="submit" data-intent="danger" aria-label="Delete this photo">
          <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
        </button>
      </form>
    `;
  }

  async #persistOrder() {
    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images/order`, {
        method: 'PATCH',
        body: JSON.stringify({ order: this.#getImageIds() }),
      });
      if (!res.ok) {
        NT.toast('Failed to save new order.', 'error');
        return false;
      }
      return true;
    } catch {
      NT.toast('Failed to save new order.', 'error');
      return false;
    }
  }

  async #handleUpload(data) {
    if (!data.file) return;

    NT.crop.setConfirmState(true, true);
    this.#setBusy(true);

    const fd = new FormData();
    fd.append('photo', data.file);
    fd.append('focal_x', String(data.focalX));
    fd.append('focal_y', String(data.focalY));

    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images`, {
        method: 'POST',
        body: fd,
      });

      if (!res.ok) {
        const err = await res.json().catch(() => null);
        NT.toast(err?.error ?? 'Failed to upload photo.', 'error');
        NT.crop.setConfirmState(false, false);
        this.#setBusy(false);
        return;
      }

      const img = await res.json();
      const g = this.#ensureGallery();

      const li = document.createElement('li');
      li.dataset.imageId = img.id;
      li.dataset.focalX = String(img.focal_x ?? data.focalX);
      li.dataset.focalY = String(img.focal_y ?? data.focalY);
      li.draggable = true;
      li.tabIndex = 0;
      li.innerHTML = this.#buildLiHtml(img);

      g.appendChild(li);
      NT.applyFocalPoints(li);
      NT.crop.close();
      this.#updateSlotHint();
      li.focus({ preventScroll: true });
      requestAnimationFrame(() => {
        li.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
      NT.toast('Photo uploaded.', 'success');
    } catch {
      NT.toast('Failed to upload photo.', 'error');
      NT.crop.setConfirmState(false, false);
    } finally {
      this.#setBusy(false);
    }
  }

  async #handleReposition(data) {
    if (!data.imageId) return;

    NT.crop.setConfirmState(true, true);
    this.#setBusy(true);

    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images/${data.imageId}`, {
        method: 'PATCH',
        body: JSON.stringify({ focal_x: data.focalX, focal_y: data.focalY }),
      });

      if (!res.ok) {
        NT.toast('Failed to save position.', 'error');
        NT.crop.setConfirmState(false, false);
        this.#setBusy(false);
        return;
      }

      const g = document.getElementById('gallery-manager');
      const li = g?.querySelector(`li[data-image-id="${data.imageId}"]`);
      if (li) {
        li.dataset.focalX = String(data.focalX);
        li.dataset.focalY = String(data.focalY);
        const img = li.querySelector('img');
        if (img) {
          if (data.focalX !== 50 || data.focalY !== 50) {
            img.dataset.focalX = String(data.focalX);
            img.dataset.focalY = String(data.focalY);
          } else {
            delete img.dataset.focalX;
            delete img.dataset.focalY;
          }
          NT.applyFocalPoints(li);
        }
      }

      NT.crop.close();
      NT.toast('Position saved.', 'success');
    } catch {
      NT.toast('Failed to save position.', 'error');
      NT.crop.setConfirmState(false, false);
    } finally {
      this.#setBusy(false);
    }
  }

  /** @param {HTMLOListElement} g */
  #attachGalleryListeners(g) {
    g.addEventListener('dragstart', (e) => {
      if (e.target.closest('input, button, label, a')) {
        e.preventDefault();
        return;
      }
      const li = e.target.closest('li[data-image-id]');
      if (!li) return;
      this.#dragItem = li;
      li.dataset.dragging = '';
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', li.dataset.imageId);
    });

    g.addEventListener('dragend', (e) => {
      const li = e.target.closest('li[data-image-id]');
      if (li) delete li.dataset.dragging;
      this.#dragItem = null;
      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }
    });

    g.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      const target = e.target.closest('li[data-image-id]');
      if (!target || target === this.#dragItem) return;
      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }
      target.dataset.dragOver = '';
    });

    g.addEventListener('drop', async (e) => {
      e.preventDefault();
      const target = e.target.closest('li[data-image-id]');
      if (!target || !this.#dragItem || target === this.#dragItem || this.#busy) return;

      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }

      const items = Array.from(g.querySelectorAll('li[data-image-id]'));
      const dragIdx = items.indexOf(this.#dragItem);
      const targetIdx = items.indexOf(target);
      const refNode = this.#dragItem.nextElementSibling;

      if (dragIdx < targetIdx) {
        target.after(this.#dragItem);
      } else {
        target.before(this.#dragItem);
      }

      this.#setBusy(true);
      const ok = await this.#persistOrder();
      if (!ok) {
        if (refNode) refNode.before(this.#dragItem);
        else g.appendChild(this.#dragItem);
      }
      this.#setBusy(false);
    });

    g.addEventListener('keydown', async (e) => {
      if (e.target !== e.target.closest('li[data-image-id]')) return;
      const li = e.target;
      if (!li || this.#busy) return;

      const items = Array.from(g.querySelectorAll('li[data-image-id]'));
      const idx = items.indexOf(li);
      if (idx < 0) return;

      let swapTarget = null;

      if ((e.key === 'ArrowUp' || e.key === 'ArrowLeft') && idx > 0) {
        swapTarget = items[idx - 1];
        e.preventDefault();
        swapTarget.before(li);
      } else if ((e.key === 'ArrowDown' || e.key === 'ArrowRight') && idx < items.length - 1) {
        swapTarget = items[idx + 1];
        e.preventDefault();
        swapTarget.after(li);
      }

      if (!swapTarget) return;

      li.focus();
      this.#setBusy(true);
      const ok = await this.#persistOrder();
      if (!ok) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
          swapTarget.after(li);
        } else {
          swapTarget.before(li);
        }
        li.focus();
      }
      this.#setBusy(false);
    });

    g.addEventListener('click', async (e) => {
      const deleteBtn = e.target.closest('[data-delete-form] button[type="submit"]');
      if (deleteBtn) {
        e.preventDefault();
        if (this.#busy) return;
        await this.#handleDelete(g, deleteBtn);
        return;
      }

      const repoBtn = e.target.closest('[data-reposition]');
      if (repoBtn && !this.#busy) {
        const li = repoBtn.closest('li[data-image-id]');
        if (!li) return;
        const imageId = li.dataset.imageId;
        const img = li.querySelector('img');
        if (!img) return;
        const fx = parseInt(li.dataset.focalX ?? '50', 10);
        const fy = parseInt(li.dataset.focalY ?? '50', 10);
        NT.crop?.openReposition(img.src, fx, fy, imageId);
      }
    });

    g.addEventListener('change', async (e) => {
      const radio = e.target.closest('[data-primary-radio]');
      if (!radio || this.#busy) return;
      await this.#handlePrimaryChange(g, radio);
    });

    g.addEventListener('blur', async (e) => {
      const input = e.target.closest('[data-alt-input]');
      if (!input || this.#busy) return;
      await this.#handleAltTextBlur(input);
    }, true);
  }

  async #handleDelete(g, deleteBtn) {
    const li = deleteBtn.closest('li[data-image-id]');
    if (!li) return;

    const imageId = li.dataset.imageId;

    if (!await NT.confirm('Delete this photo?')) return;

    this.#setBusy(true);
    deleteBtn.disabled = true;

    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images/${imageId}`, {
        method: 'DELETE',
      });

      if (!res.ok) {
        NT.toast('Failed to delete photo.', 'error');
        deleteBtn.disabled = false;
        this.#setBusy(false);
        return;
      }

      const data = await res.json();
      li.remove();

      if (data.new_primary_id) {
        const promoted = g.querySelector(`li[data-image-id="${data.new_primary_id}"] [data-primary-radio]`);
        if (promoted) {
          promoted.checked = true;
          const label = promoted.nextElementSibling;
          if (label) GalleryManager.#setPrimaryLabel(label, true);
        }
      }

      const remaining = g.querySelectorAll('li[data-image-id]').length;

      if (remaining === 0) {
        g.remove();
        const empty = document.createElement('p');
        empty.id = 'gallery-empty';
        empty.textContent = 'No photos uploaded yet.';
        const hint = this.#fieldset.querySelector('#gallery-crop-hint');
        if (hint) {
          hint.after(empty);
        } else {
          this.#fieldset.querySelector('legend').after(empty);
        }
      }

      this.#updateSlotHint();
      NT.toast('Photo deleted.', 'success');
    } catch {
      NT.toast('Failed to delete photo.', 'error');
      deleteBtn.disabled = false;
    } finally {
      this.#setBusy(false);
    }
  }

  async #handlePrimaryChange(g, radio) {
    const imageId = radio.value;
    this.#setBusy(true);

    for (const r of g.querySelectorAll('[data-primary-radio]')) {
      r.disabled = true;
    }

    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images/${imageId}/primary`, {
        method: 'PATCH',
      });

      if (!res.ok) {
        NT.toast('Failed to set primary photo.', 'error');
        this.#setBusy(false);
        return;
      }

      for (const li of g.querySelectorAll('li[data-image-id]')) {
        const r = li.querySelector('[data-primary-radio]');
        const label = r?.nextElementSibling;
        if (!label) continue;

        GalleryManager.#setPrimaryLabel(label, r.value === imageId);
      }

      NT.toast('Primary photo updated.', 'success');
    } catch {
      NT.toast('Failed to set primary photo.', 'error');
    } finally {
      for (const r of g.querySelectorAll('[data-primary-radio]')) {
        r.disabled = false;
      }
      this.#setBusy(false);
    }
  }

  async #handleAltTextBlur(input) {
    const imageId = input.dataset.imageId;
    const altText = input.value.trim().slice(0, 255);

    try {
      const res = await NT.fetch(`/tools/${this.#toolId}/images/${imageId}`, {
        method: 'PATCH',
        body: JSON.stringify({ alt_text: altText }),
      });

      if (res.ok) {
        NT.toast('Alt text saved.', 'success');
      } else {
        NT.toast('Failed to save alt text.', 'error');
      }
    } catch {
      NT.toast('Failed to save alt text.', 'error');
    }
  }

  #bindDropZone() {
    const dropZone = document.getElementById('photo-drop-zone');
    const fileInput = document.getElementById('add-photo');
    if (!dropZone || !fileInput) return;

    const { signal } = this.#abortController;

    dropZone.setAttribute('role', 'button');
    dropZone.tabIndex = 0;
    dropZone.setAttribute('aria-label', 'Choose a photo to upload');

    dropZone.addEventListener('click', (e) => {
      if (e.target === fileInput) return;
      fileInput.click();
    }, { signal });

    dropZone.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        fileInput.click();
      }
    }, { signal });

    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      if (!file) return;
      if (!GalleryManager.#validateFile(file)) { fileInput.value = ''; return; }
      NT.crop?.openUpload(file);
    }, { signal });

    dropZone.addEventListener('dragenter', (e) => {
      e.preventDefault();
      this.#dragCounter++;
      dropZone.dataset.dragover = '';
    }, { signal });

    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
    }, { signal });

    dropZone.addEventListener('dragleave', () => {
      this.#dragCounter--;
      if (this.#dragCounter <= 0) {
        this.#dragCounter = 0;
        delete dropZone.dataset.dragover;
      }
    }, { signal });

    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      this.#dragCounter = 0;
      delete dropZone.dataset.dragover;

      const file = Array.from(e.dataTransfer.files).find((f) => GalleryManager.#ALLOWED_TYPES.includes(f.type));
      if (!file) {
        NT.toast('No valid image file found. Use JPEG, PNG, or WebP.', 'error');
        return;
      }
      if (!GalleryManager.#validateFile(file)) return;
      NT.crop?.openUpload(file);
    }, { signal });
  }
}

// ─── Gallery Viewer ──────────────────────────────────────────────────

class GalleryViewer {
  static #instance = null;

  /** @type {HTMLElement} */
  #galleryEl;
  /** @type {HTMLElement|null} */
  #thumbList;
  /** @type {NodeList|null} */
  #thumbButtons;
  /** @type {HTMLImageElement|null} */
  #mainImg;
  /** @type {HTMLElement|null} */
  #mainFigure;
  /** @type {HTMLSourceElement|null} */
  #mainSource;
  /** @type {HTMLDialogElement|null} */
  #lightbox;
  /** @type {HTMLImageElement|null} */
  #lightboxImg;
  #allImages = [];
  #currentIndex = 0;
  #lightboxIndex = 0;
  #triggerElement = null;
  #abortController = new AbortController();

  /** @param {HTMLElement} galleryEl */
  constructor(galleryEl) {
    this.#galleryEl = galleryEl;
    this.#mainFigure = document.getElementById('gallery-main');
    this.#mainImg = document.getElementById('gallery-main-img');
    this.#mainSource = document.getElementById('gallery-main-source');
    this.#thumbList = document.getElementById('gallery-thumbs');
    this.#lightbox = document.getElementById('gallery-lightbox');
    this.#lightboxImg = document.getElementById('lightbox-img');

    if (this.#thumbList && this.#mainImg) {
      this.#thumbButtons = this.#thumbList.querySelectorAll('button');
      this.#bindThumbs();
    }

    if (this.#lightbox && this.#lightboxImg) {
      this.#buildImageList();
      this.#bindLightbox();
    }
  }

  /** @returns {GalleryViewer|null} */
  static init() {
    if (GalleryViewer.#instance) return GalleryViewer.#instance;
    const galleryEl = document.getElementById('tool-gallery');
    if (!galleryEl) return null;
    return (GalleryViewer.#instance = new GalleryViewer(galleryEl));
  }

  destroy() {
    this.#abortController.abort();
    GalleryViewer.#instance = null;
  }

  #setActiveThumb(index) {
    this.#currentIndex = index;

    for (let i = 0; i < this.#thumbButtons.length; i++) {
      this.#thumbButtons[i].setAttribute('aria-current', i === index ? 'true' : 'false');
    }

    const btn = this.#thumbButtons[index];
    if (!btn) return;

    this.#mainImg.src = btn.dataset.full;
    this.#mainImg.srcset = btn.dataset.srcset || '';
    this.#mainImg.alt = btn.dataset.alt || '';

    if (this.#mainSource) {
      const webpSrcset = btn.dataset.srcsetWebp;
      if (webpSrcset) {
        this.#mainSource.srcset = webpSrcset;
        this.#mainSource.hidden = false;
      } else {
        this.#mainSource.srcset = '';
        this.#mainSource.hidden = true;
      }
    }

    const fx = btn.dataset.focalX ?? '50';
    const fy = btn.dataset.focalY ?? '50';
    if (fx !== '50' || fy !== '50') {
      this.#mainImg.dataset.focalX = fx;
      this.#mainImg.dataset.focalY = fy;
    } else {
      delete this.#mainImg.dataset.focalX;
      delete this.#mainImg.dataset.focalY;
    }
    NT.applyFocalPoints(this.#mainImg);

    const link = this.#mainImg.closest('a[data-lightbox-trigger]');
    if (link) link.href = btn.dataset.full;

    const caption = this.#mainFigure?.querySelector('figcaption');
    const altText = btn.dataset.alt || '';
    if (caption) {
      caption.textContent = altText;
      caption.hidden = altText === '';
    }
  }

  #bindThumbs() {
    const { signal } = this.#abortController;

    this.#thumbList.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      const index = Array.from(this.#thumbButtons).indexOf(btn);
      if (index >= 0) this.#setActiveThumb(index);
    }, { signal });

    this.#galleryEl.addEventListener('keydown', (e) => {
      if (this.#lightbox?.open) return;

      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        this.#setActiveThumb((this.#currentIndex + 1) % this.#thumbButtons.length);
      } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        this.#setActiveThumb((this.#currentIndex - 1 + this.#thumbButtons.length) % this.#thumbButtons.length);
      }
    }, { signal });
  }

  #buildImageList() {
    if (this.#thumbList) {
      for (const btn of this.#thumbList.querySelectorAll('button')) {
        this.#allImages.push({
          src: btn.dataset.full,
          alt: btn.dataset.alt || '',
        });
      }
    } else if (this.#mainImg) {
      const link = this.#mainImg.closest('a[data-lightbox-trigger]');
      this.#allImages.push({
        src: link?.href || this.#mainImg.src,
        alt: this.#mainImg.alt,
      });
    }
  }

  #showLightboxImage(index) {
    this.#lightboxIndex = index;
    const img = this.#allImages[index];
    if (!img) return;

    this.#lightboxImg.src = img.src;
    this.#lightboxImg.alt = img.alt;

    const prevBtn = document.getElementById('lightbox-prev');
    const nextBtn = document.getElementById('lightbox-next');
    if (prevBtn) prevBtn.hidden = this.#allImages.length <= 1;
    if (nextBtn) nextBtn.hidden = this.#allImages.length <= 1;
  }

  #bindLightbox() {
    const { signal } = this.#abortController;
    const prevBtn = document.getElementById('lightbox-prev');
    const nextBtn = document.getElementById('lightbox-next');
    const closeBtn = document.getElementById('lightbox-close');

    this.#galleryEl.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-lightbox-trigger]');
      if (!trigger) return;

      e.preventDefault();

      let index = 0;
      if (this.#thumbList) {
        const activeBtn = this.#thumbList.querySelector('button[aria-current="true"]');
        if (activeBtn) {
          index = Array.from(this.#thumbList.querySelectorAll('button')).indexOf(activeBtn);
        }
      }

      this.#triggerElement = document.activeElement;
      this.#showLightboxImage(Math.max(0, index));
      this.#lightbox.showModal();
    }, { signal });

    closeBtn?.addEventListener('click', () => this.#closeLightbox(), { signal });

    this.#lightbox.addEventListener('click', (e) => {
      if (e.target === this.#lightbox) this.#closeLightbox();
    }, { signal });

    prevBtn?.addEventListener('click', () => {
      this.#showLightboxImage((this.#lightboxIndex - 1 + this.#allImages.length) % this.#allImages.length);
    }, { signal });

    nextBtn?.addEventListener('click', () => {
      this.#showLightboxImage((this.#lightboxIndex + 1) % this.#allImages.length);
    }, { signal });

    this.#lightbox.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        this.#closeLightbox();
      } else if (e.key === 'ArrowLeft' && this.#allImages.length > 1) {
        e.preventDefault();
        this.#showLightboxImage((this.#lightboxIndex - 1 + this.#allImages.length) % this.#allImages.length);
      } else if (e.key === 'ArrowRight' && this.#allImages.length > 1) {
        e.preventDefault();
        this.#showLightboxImage((this.#lightboxIndex + 1) % this.#allImages.length);
      }
    }, { signal });
  }

  #closeLightbox() {
    this.#lightbox.close();
    this.#triggerElement?.focus();
    this.#triggerElement = null;
  }
}

// ─── Delete Tool Confirmation ────────────────────────────────────────

class DeleteToolDialog {
  static #instance = null;

  /** @type {HTMLDialogElement} */
  #dialog;
  #abortController = new AbortController();

  /** @param {HTMLDialogElement} dialog */
  constructor(dialog) {
    this.#dialog = dialog;
    const { signal } = this.#abortController;

    document.querySelector('[data-open-delete-dialog]')
      ?.addEventListener('click', () => this.#dialog.showModal(), { signal });

    dialog.querySelector('[data-close-delete-dialog]')
      ?.addEventListener('click', () => this.#dialog.close(), { signal });
  }

  static init() {
    if (DeleteToolDialog.#instance) return DeleteToolDialog.#instance;
    const dialog = document.getElementById('delete-tool-dialog');
    if (!dialog) return null;
    return (DeleteToolDialog.#instance = new DeleteToolDialog(dialog));
  }
}

// ─── Init ────────────────────────────────────────────────────────────

BrowseModeToggle.init();
FuelTypeToggle.init();
FeeSlider.init();
BrowseFilter.init();
ViewToggle.init();
PhotoQueue.init();
GalleryManager.init();
GalleryViewer.init();
DeleteToolDialog.init();
