'use strict';

(function () {
  const nav = document.querySelector('nav[aria-label="Browse mode"]');
  if (!nav) return;

  const links = nav.querySelectorAll(':scope > a');
  nav.dataset.active = nav.querySelector(':scope > a:nth-child(2)[aria-current="page"]') ? 'end' : 'start';

  for (const link of links) {
    link.addEventListener('click', () => {
      nav.dataset.active = link === links[links.length - 1] ? 'end' : 'start';
    });
  }
})();

(function () {
  const radius = document.getElementById('filter-radius');
  const zip = document.getElementById('filter-zip');
  if (!radius || !zip) return;

  function sync() {
    zip.required = radius.value !== '';
  }

  sync();
  radius.addEventListener('change', sync);
})();

(function () {
  const checkbox = document.getElementById('uses-fuel');
  const group = document.getElementById('fuel-type-group');
  if (!checkbox || !group) return;

  function toggle() {
    group.hidden = !checkbox.checked;
  }

  toggle();
  checkbox.addEventListener('change', toggle);
})();

(function () {
  const slider = document.getElementById('filter-max-fee');
  const display = document.getElementById('fee-display');
  if (!slider || !display) return;

  function syncSliderAria() {
    display.textContent = `$${slider.value}`;
    slider.setAttribute('aria-valuenow', slider.value);
    slider.setAttribute('aria-valuetext', `$${slider.value}`);
  }

  slider.addEventListener('input', syncSliderAria);
})();

(function () {
  const page = document.getElementById('browse-page');
  if (!page) return;

  const form       = page.querySelector('form[role="search"]');
  const grid       = page.querySelector('[role="list"]');
  const countArea  = page.querySelector('[aria-live="polite"]');
  const emptyState = page.querySelector('section[aria-label="No results"]');
  if (!form) return;

  function getPaginationNav() {
    return page.querySelector('nav[aria-label="Pagination"]');
  }

  function getFilterFieldset() {
    return form.querySelector('fieldset[aria-label="Filters"]');
  }

  const DEBOUNCE_MS    = 300;
  const SKELETON_COUNT = 6;

  let debounceTimer  = null;
  let abortCtrl      = null;
  let currentPage    = 1;
  let isPaginating   = false;

  function getBasePath() {
    return form.action ? new URL(form.action).pathname : '/tools';
  }

  function buildQueryString() {
    const data = new FormData(form);
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

    if (currentPage > 1) {
      params.set('page', String(currentPage));
    }

    return params;
  }

  function hasActiveFilters() {
    const params = buildQueryString();
    params.delete('page');
    return params.size > 0;
  }

  function showSkeletons() {
    if (!grid) return;

    grid.innerHTML = '';
    for (let i = 0; i < SKELETON_COUNT; i++) {
      const card = document.createElement('div');
      card.setAttribute('role', 'listitem');
      card.setAttribute('aria-hidden', 'true');
      card.className = 'skeleton-card';
      grid.appendChild(card);
    }

    if (emptyState) emptyState.hidden = true;
  }

  function updateResultCount(data) {
    if (!countArea) return;

    const existing = countArea.querySelector(':scope > p');
    const p = document.createElement('p');

    if (data.totalCount > 0) {
      const strong1 = document.createElement('strong');
      strong1.textContent = `${data.rangeStart}–${data.rangeEnd}`;
      const strong2 = document.createElement('strong');
      strong2.textContent = data.totalCount.toLocaleString();
      const suffix = data.totalCount !== 1 ? 'tools' : 'tool';
      p.append('Showing ', strong1, ' of ', strong2, ` ${suffix}`);
    } else {
      p.textContent = 'No tools match your filters.';
    }

    if (existing) {
      existing.replaceWith(p);
    } else {
      countArea.prepend(p);
    }
  }

  function updateClearButton() {
    const fieldset = getFilterFieldset();
    if (!fieldset) return;

    const existing = fieldset.querySelector('a[data-intent="ghost"]');
    const active   = hasActiveFilters();

    if (active && !existing) {
      const basePath = getBasePath();
      const link = document.createElement('a');
      link.href = basePath;
      link.setAttribute('role', 'button');
      link.dataset.intent = 'ghost';
      link.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear Filters';
      fieldset.appendChild(link);
    } else if (!active && existing) {
      existing.remove();
    }
  }

  async function fetchFiltered(replaceHistory = false) {
    clearTimeout(debounceTimer);

    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    const params   = buildQueryString();
    const basePath = getBasePath();
    const qs       = params.toString();
    const url      = qs ? `${basePath}?${qs}` : basePath;

    showSkeletons();

    try {
      const res = await NT.fetch(url, { signal: abortCtrl.signal });

      if (!res.ok) {
        form.submit();
        return;
      }

      const data = await res.json();

      if (grid) {
        grid.innerHTML = data.html;
      }

      const currentPagination = getPaginationNav();
      if (currentPagination) {
        if (data.paginationHtml.trim()) {
          currentPagination.outerHTML = data.paginationHtml;
        } else {
          currentPagination.remove();
        }
      } else if (data.paginationHtml.trim() && grid) {
        grid.insertAdjacentHTML('afterend', data.paginationHtml);
      }

      updateResultCount(data);
      updateClearButton();

      if (data.totalCount === 0 && grid) {
        grid.innerHTML = '';
      }

      if (emptyState) {
        emptyState.hidden = data.totalCount > 0;
      }

      if (replaceHistory) {
        history.replaceState(null, '', url);
      } else {
        history.pushState(null, '', url);
      }

      if (isPaginating) {
        isPaginating = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    } catch (err) {
      if (err.name === 'AbortError') return;
      NT.toast('Something went wrong. Reloading\u2026', 'error');
      form.submit();
    }
  }

  function handleImmediateChange() {
    clearTimeout(debounceTimer);
    currentPage = 1;
    fetchFiltered();
  }

  function handleDebouncedInput() {
    clearTimeout(debounceTimer);
    currentPage = 1;
    debounceTimer = setTimeout(fetchFiltered, DEBOUNCE_MS);
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(debounceTimer);
    currentPage = 1;
    fetchFiltered();
  });

  const category = document.getElementById('filter-category');
  const radius   = document.getElementById('filter-radius');
  category?.addEventListener('change', handleImmediateChange);
  radius?.addEventListener('change', handleImmediateChange);

  const search = document.getElementById('browse-search');
  const zip    = document.getElementById('filter-zip');
  const maxFee = document.getElementById('filter-max-fee');
  search?.addEventListener('input', handleDebouncedInput);
  zip?.addEventListener('input', handleDebouncedInput);
  maxFee?.addEventListener('input', handleDebouncedInput);

  page.addEventListener('click', (e) => {
    const link = e.target.closest('nav[aria-label="Pagination"] a');
    if (!link) return;

    e.preventDefault();
    const linkUrl = new URL(link.href);
    currentPage = parseInt(linkUrl.searchParams.get('page') ?? '1', 10);
    isPaginating = true;
    fetchFiltered();
  });

  page.addEventListener('click', (e) => {
    const clearLink = e.target.closest('fieldset a[data-intent="ghost"]');
    if (!clearLink) return;

    e.preventDefault();
    form.reset();
    currentPage = 1;
    fetchFiltered();
  });

  window.addEventListener('popstate', () => {
    const params = new URLSearchParams(window.location.search);

    for (const el of form.elements) {
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

    currentPage = parseInt(params.get('page') ?? '1', 10);
    fetchFiltered(true);
  });

  window.addEventListener('beforeunload', () => {
    clearTimeout(debounceTimer);
    if (abortCtrl) abortCtrl.abort();
  });
})();

(function () {
  const page = document.getElementById('browse-page');
  if (!page) return;

  const grid = page.querySelector('[role="list"]');
  const summary = page.querySelector('[aria-live="polite"]');
  if (!grid || !summary) return;

  const STORAGE_KEY = 'nt-view-preference';

  const toolbar = document.createElement('div');
  toolbar.setAttribute('data-view-toggle', '');
  toolbar.setAttribute('role', 'group');
  toolbar.setAttribute('aria-label', 'View mode');

  const gridBtn = document.createElement('button');
  gridBtn.type = 'button';
  gridBtn.innerHTML = '<i class="fa-solid fa-grip" aria-hidden="true"></i>';
  gridBtn.setAttribute('aria-label', 'Grid view');
  gridBtn.title = 'Grid view';
  gridBtn.dataset.view = 'grid';

  const listBtn = document.createElement('button');
  listBtn.type = 'button';
  listBtn.innerHTML = '<i class="fa-solid fa-list" aria-hidden="true"></i>';
  listBtn.setAttribute('aria-label', 'List view');
  listBtn.title = 'List view';
  listBtn.dataset.view = 'list';

  toolbar.append(gridBtn, listBtn);
  summary.appendChild(toolbar);

  const mql = window.matchMedia('(max-width: 600px)');

  function setView(mode) {
    const effective = mql.matches ? 'grid' : mode;
    grid.dataset.view = effective;
    gridBtn.setAttribute('aria-pressed', effective === 'grid');
    listBtn.setAttribute('aria-pressed', effective === 'list');
    if (!mql.matches) localStorage.setItem(STORAGE_KEY, mode);
  }

  function getPreferred() {
    return localStorage.getItem(STORAGE_KEY) === 'list' ? 'list' : 'grid';
  }

  setView(getPreferred());
  mql.addEventListener('change', () => setView(getPreferred()));

  toolbar.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-view]');
    if (!btn) return;
    setView(btn.dataset.view);
  });
})();
