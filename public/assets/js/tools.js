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

  slider.addEventListener('input', () => {
    display.textContent = `$${slider.value}`;
    slider.setAttribute('aria-valuenow', slider.value);
    slider.setAttribute('aria-valuetext', `$${slider.value}`);
  });
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

  const DEBOUNCE_MS  = 300;
  const SKELETON_COUNT = 6;

  let debounceTimer = null;
  let generation    = 0;

  function getBasePath() {
    return form.action ? new URL(form.action).pathname : '/tools';
  }

  function buildQueryString() {
    const data = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of data) {
      const trimmed = value.toString().trim();
      if (trimmed !== '') params.set(key, trimmed);
    }

    const slider = document.getElementById('filter-max-fee');
    if (slider && params.get('max_fee') === slider.max) {
      params.delete('max_fee');
    }

    return params;
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

    if (data.totalCount > 0) {
      const p = document.createElement('p');
      const strong1 = document.createElement('strong');
      strong1.textContent = `${data.rangeStart}–${data.rangeEnd}`;
      const strong2 = document.createElement('strong');
      strong2.textContent = data.totalCount.toLocaleString();
      const suffix = data.totalCount !== 1 ? 'tools' : 'tool';

      p.append('Showing ', strong1, ' of ', strong2, ` ${suffix}`);
      countArea.replaceChildren(p);
    } else {
      const p = document.createElement('p');
      p.textContent = 'No tools match your filters.';
      countArea.replaceChildren(p);
    }
  }

  async function fetchFiltered() {
    const token = ++generation;
    const params = buildQueryString();
    const basePath = getBasePath();
    const url = `${basePath}?${params}`;

    showSkeletons();

    try {
      const res = await NT.fetch(url);
      if (token !== generation) return;

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

      if (data.totalCount === 0 && grid) {
        grid.innerHTML = '';
      }

      if (emptyState) {
        emptyState.hidden = data.totalCount > 0;
      }

      history.replaceState(null, '', url);
    } catch {
      if (token !== generation) return;
      form.submit();
    }
  }

  function handleImmediateChange() {
    clearTimeout(debounceTimer);
    fetchFiltered();
  }

  function handleDebouncedInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchFiltered, DEBOUNCE_MS);
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(debounceTimer);
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
  maxFee?.addEventListener('change', handleImmediateChange);

  page.addEventListener('click', (e) => {
    const link = e.target.closest('nav[aria-label="Pagination"] a');
    if (!link) return;

    e.preventDefault();
    const url = new URL(link.href);
    const pageNum = url.searchParams.get('page');

    if (pageNum) {
      const pageInput = form.querySelector('input[name="page"]')
        ?? Object.assign(document.createElement('input'), { type: 'hidden', name: 'page' });
      pageInput.value = pageNum;
      if (!pageInput.parentNode) form.appendChild(pageInput);
    }

    fetchFiltered();
  });

  window.addEventListener('popstate', () => {
    const params = new URLSearchParams(window.location.search);

    for (const input of form.elements) {
      if (input.name && params.has(input.name)) {
        input.value = params.get(input.name);
      } else if (input.name && input.type !== 'hidden') {
        input.value = input.defaultValue;
      }
    }

    fetchFiltered();
  });
})();
