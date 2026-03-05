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

(function () {
  const fileInput = document.getElementById('tool-photos');
  const previewList = document.getElementById('photo-preview-list');
  if (!fileInput || !previewList) return;

  const MAX_FILES = 6;
  const MAX_SIZE = 5 * 1024 * 1024;
  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

  function validateFiles(files) {
    if (files.length > MAX_FILES) {
      NT.toast(`You can upload at most ${MAX_FILES} photos.`, 'error');
      return false;
    }

    for (const file of files) {
      if (!ALLOWED_TYPES.includes(file.type)) {
        NT.toast(`"${file.name}" is not a valid image type. Use JPEG, PNG, or WebP.`, 'error');
        return false;
      }
      if (file.size > MAX_SIZE) {
        NT.toast(`"${file.name}" exceeds the 5 MB limit.`, 'error');
        return false;
      }
    }

    return true;
  }

  function renderPreviews(files) {
    for (const img of previewList.querySelectorAll('img')) {
      URL.revokeObjectURL(img.src);
    }
    previewList.innerHTML = '';

    if (files.length === 0) {
      previewList.hidden = true;
      return;
    }

    previewList.hidden = false;

    for (let i = 0; i < files.length; i++) {
      const li = document.createElement('li');
      const img = document.createElement('img');
      img.src = URL.createObjectURL(files[i]);
      img.alt = `Preview of ${files[i].name}`;
      img.width = 120;
      img.height = 80;
      li.appendChild(img);

      const label = document.createElement('span');
      label.textContent = i === 0 ? 'Primary' : `Photo ${i + 1}`;
      li.appendChild(label);

      previewList.appendChild(li);
    }
  }

  fileInput.addEventListener('change', () => {
    const files = Array.from(fileInput.files);

    if (files.length === 0) {
      renderPreviews([]);
      return;
    }

    if (!validateFiles(files)) {
      fileInput.value = '';
      renderPreviews([]);
      return;
    }

    renderPreviews(files);
  });

  const dropZone = fileInput.closest('div');
  if (!dropZone) return;

  let dragCounter = 0;

  dropZone.addEventListener('dragenter', (e) => {
    e.preventDefault();
    dragCounter++;
    dropZone.dataset.dragover = '';
  });

  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
  });

  dropZone.addEventListener('dragleave', () => {
    dragCounter--;
    if (dragCounter <= 0) {
      dragCounter = 0;
      delete dropZone.dataset.dragover;
    }
  });

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dragCounter = 0;
    delete dropZone.dataset.dragover;

    const dt = new DataTransfer();
    for (const file of e.dataTransfer.files) {
      if (ALLOWED_TYPES.includes(file.type)) {
        dt.items.add(file);
      }
    }

    if (dt.files.length === 0) {
      NT.toast('No valid image files found in the drop.', 'error');
      return;
    }

    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
  });
})();

(function () {
  const gallery = document.getElementById('gallery-manager');
  if (!gallery) return;

  const toolId = gallery.dataset.toolId;
  let busy = false;

  function setBusy(state) {
    busy = state;
    gallery.ariaBusy = state ? 'true' : 'false';
  }

  function getImageIds() {
    return Array.from(gallery.querySelectorAll('li[data-image-id]'))
      .map(li => parseInt(li.dataset.imageId, 10));
  }

  let dragItem = null;

  gallery.addEventListener('dragstart', (e) => {
    const li = e.target.closest('li[data-image-id]');
    if (!li) return;
    dragItem = li;
    li.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', li.dataset.imageId);
  });

  gallery.addEventListener('dragend', (e) => {
    const li = e.target.closest('li[data-image-id]');
    if (li) li.classList.remove('dragging');
    dragItem = null;
    for (const el of gallery.querySelectorAll('.drag-over')) {
      el.classList.remove('drag-over');
    }
  });

  gallery.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    const target = e.target.closest('li[data-image-id]');
    if (!target || target === dragItem) return;

    for (const el of gallery.querySelectorAll('.drag-over')) {
      el.classList.remove('drag-over');
    }
    target.classList.add('drag-over');
  });

  gallery.addEventListener('drop', async (e) => {
    e.preventDefault();
    const target = e.target.closest('li[data-image-id]');
    if (!target || !dragItem || target === dragItem || busy) return;

    for (const el of gallery.querySelectorAll('.drag-over')) {
      el.classList.remove('drag-over');
    }

    const items = Array.from(gallery.children);
    const dragIdx = items.indexOf(dragItem);
    const targetIdx = items.indexOf(target);

    if (dragIdx < targetIdx) {
      target.after(dragItem);
    } else {
      target.before(dragItem);
    }

    setBusy(true);

    try {
      const res = await NT.fetch(`/tools/${toolId}/images/order`, {
        method: 'PATCH',
        body: JSON.stringify({ order: getImageIds() }),
      });

      if (!res.ok) {
        NT.toast('Failed to save new order.', 'error');
        if (dragIdx < targetIdx) {
          items[dragIdx].before(dragItem);
        } else {
          items[dragIdx].after(dragItem);
        }
      }
    } catch {
      NT.toast('Failed to save new order.', 'error');
    } finally {
      setBusy(false);
    }
  });

  gallery.addEventListener('click', async (e) => {
    const deleteBtn = e.target.closest('[data-delete-form] button[type="submit"]');
    if (!deleteBtn) return;

    e.preventDefault();
    if (busy) return;

    const li = deleteBtn.closest('li[data-image-id]');
    if (!li) return;

    const imageId = li.dataset.imageId;

    if (!confirm('Delete this photo?')) return;

    setBusy(true);
    deleteBtn.disabled = true;

    try {
      const res = await NT.fetch(`/tools/${toolId}/images/${imageId}`, {
        method: 'DELETE',
      });

      if (!res.ok) {
        NT.toast('Failed to delete photo.', 'error');
        deleteBtn.disabled = false;
        setBusy(false);
        return;
      }

      const data = await res.json();
      li.remove();

      if (data.new_primary_id) {
        const promoted = gallery.querySelector(`li[data-image-id="${data.new_primary_id}"] [data-primary-radio]`);
        if (promoted) {
          promoted.checked = true;
          const label = promoted.nextElementSibling;
          if (label) label.innerHTML = '<i class="fa-solid fa-star" aria-hidden="true"></i> Primary';
        }
      }

      const remaining = gallery.querySelectorAll('li[data-image-id]').length;

      if (remaining === 0) {
        gallery.outerHTML = '<p>No photos uploaded yet.</p>';
      }

      const addSection = document.querySelector('[data-add-photo]')?.closest('div');
      if (addSection) {
        addSection.hidden = false;
        const hint = addSection.querySelector('p');
        if (hint) {
          const slots = 6 - remaining;
          hint.textContent = `JPEG, PNG, or WebP — max 5 MB. ${slots} slot${slots !== 1 ? 's' : ''} remaining.`;
        }
      }

      NT.toast('Photo deleted.', 'success');
    } catch {
      NT.toast('Failed to delete photo.', 'error');
      deleteBtn.disabled = false;
    } finally {
      setBusy(false);
    }
  });

  gallery.addEventListener('change', async (e) => {
    const radio = e.target.closest('[data-primary-radio]');
    if (!radio || busy) return;

    const imageId = radio.value;
    setBusy(true);

    for (const r of gallery.querySelectorAll('[data-primary-radio]')) {
      r.disabled = true;
    }

    try {
      const res = await NT.fetch(`/tools/${toolId}/images/${imageId}/primary`, {
        method: 'PATCH',
      });

      if (!res.ok) {
        NT.toast('Failed to set primary photo.', 'error');
        setBusy(false);
        return;
      }

      for (const li of gallery.querySelectorAll('li[data-image-id]')) {
        const r = li.querySelector('[data-primary-radio]');
        const label = r?.nextElementSibling;
        if (!label) continue;

        if (r.value === imageId) {
          label.innerHTML = '<i class="fa-solid fa-star" aria-hidden="true"></i> Primary';
        } else {
          label.textContent = 'Primary';
        }
      }

      NT.toast('Primary photo updated.', 'success');
    } catch {
      NT.toast('Failed to set primary photo.', 'error');
    } finally {
      for (const r of gallery.querySelectorAll('[data-primary-radio]')) {
        r.disabled = false;
      }
      setBusy(false);
    }
  });

  const addInput = document.querySelector('[data-add-photo]');

  if (addInput) {
    addInput.addEventListener('change', async () => {
      const file = addInput.files?.[0];
      if (!file || busy) return;

      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowed.includes(file.type)) {
        NT.toast('Invalid file type. Use JPEG, PNG, or WebP.', 'error');
        addInput.value = '';
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        NT.toast('File exceeds the 5 MB limit.', 'error');
        addInput.value = '';
        return;
      }

      setBusy(true);
      addInput.disabled = true;

      const fd = new FormData();
      fd.append('photo', file);

      try {
        const res = await NT.fetch(`/tools/${toolId}/images`, {
          method: 'POST',
          body: fd,
        });

        if (!res.ok) {
          const err = await res.json().catch(() => null);
          NT.toast(err?.error ?? 'Failed to upload photo.', 'error');
          addInput.value = '';
          addInput.disabled = false;
          setBusy(false);
          return;
        }

        const img = await res.json();

        const li = document.createElement('li');
        li.dataset.imageId = img.id;
        li.draggable = true;

        const thumb = img.filename.replace(/\.(\w+)$/, '-400w.$1');

        li.innerHTML = `
          <img src="/uploads/tools/${thumb}"
               alt="${img.alt_text || ''}"
               width="400" height="268"
               loading="lazy"
               decoding="async">
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
          <form method="post" action="/tools/${toolId}/images/${img.id}" data-delete-form>
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="csrf_token" value="${document.querySelector('meta[name="csrf-token"]')?.content ?? ''}">
            <button type="submit" data-intent="danger" aria-label="Delete this photo">
              <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
            </button>
          </form>
          <span aria-hidden="true" data-drag-handle><i class="fa-solid fa-grip-vertical"></i></span>
        `;

        const activeGallery = document.getElementById('gallery-manager');
        if (activeGallery) {
          activeGallery.appendChild(li);
        }

        const remaining = 6 - activeGallery.querySelectorAll('li[data-image-id]').length;
        const addSection = addInput.closest('div');

        if (remaining <= 0) {
          addSection.hidden = true;
        } else {
          const hint = addSection.querySelector('p');
          if (hint) {
            hint.textContent = `JPEG, PNG, or WebP — max 5 MB. ${remaining} slot${remaining !== 1 ? 's' : ''} remaining.`;
          }
        }

        addInput.value = '';
        NT.toast('Photo uploaded.', 'success');
      } catch {
        NT.toast('Failed to upload photo.', 'error');
      } finally {
        addInput.disabled = false;
        setBusy(false);
      }
    });

    const dropZone = addInput.closest('div');
    if (dropZone) {
      let dragCounter = 0;

      dropZone.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dragCounter++;
        dropZone.dataset.dragover = '';
      });

      dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
      });

      dropZone.addEventListener('dragleave', () => {
        dragCounter--;
        if (dragCounter <= 0) {
          dragCounter = 0;
          delete dropZone.dataset.dragover;
        }
      });

      dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        delete dropZone.dataset.dragover;

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        const file = Array.from(e.dataTransfer.files).find(f => allowed.includes(f.type));

        if (!file) {
          NT.toast('No valid image file found. Use JPEG, PNG, or WebP.', 'error');
          return;
        }

        if (file.size > 5 * 1024 * 1024) {
          NT.toast('File exceeds the 5 MB limit.', 'error');
          return;
        }

        const dt = new DataTransfer();
        dt.items.add(file);
        addInput.files = dt.files;
        addInput.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
  }

  let altDebounceTimers = {};

  gallery.addEventListener('blur', async (e) => {
    const input = e.target.closest('[data-alt-input]');
    if (!input) return;

    const imageId = input.dataset.imageId;
    clearTimeout(altDebounceTimers[imageId]);

    altDebounceTimers[imageId] = setTimeout(async () => {
      const altText = input.value.trim().slice(0, 255);

      try {
        const res = await NT.fetch(`/tools/${toolId}/images/${imageId}`, {
          method: 'PATCH',
          body: JSON.stringify({ alt_text: altText }),
        });

        if (!res.ok) {
          NT.toast('Failed to save alt text.', 'error');
        }
      } catch {
        NT.toast('Failed to save alt text.', 'error');
      }
    }, 300);
  }, true);
})();

(function () {
  const galleryEl = document.getElementById('tool-gallery');
  if (!galleryEl) return;

  const mainFigure = document.getElementById('gallery-main');
  const mainImg = document.getElementById('gallery-main-img');
  const thumbList = document.getElementById('gallery-thumbs');
  const lightbox = document.getElementById('gallery-lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  const prevBtn = document.getElementById('lightbox-prev');
  const nextBtn = document.getElementById('lightbox-next');
  const closeBtn = document.getElementById('lightbox-close');

  if (thumbList && mainImg) {
    const thumbButtons = thumbList.querySelectorAll('button');
    let currentIndex = 0;

    function setActiveThumb(index) {
      currentIndex = index;

      for (let i = 0; i < thumbButtons.length; i++) {
        thumbButtons[i].setAttribute('aria-current', i === index ? 'true' : 'false');
      }

      const btn = thumbButtons[index];
      if (!btn) return;

      mainImg.src = btn.dataset.full;
      mainImg.srcset = btn.dataset.srcset || '';
      mainImg.alt = btn.dataset.alt || '';

      const link = mainImg.closest('a[data-lightbox-trigger]');
      if (link) link.href = btn.dataset.full;

      const caption = mainFigure?.querySelector('figcaption');
      const altText = btn.dataset.alt || '';
      if (caption) {
        caption.textContent = altText;
        caption.hidden = altText === '';
      }
    }

    thumbList.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;

      const index = Array.from(thumbButtons).indexOf(btn);
      if (index >= 0) setActiveThumb(index);
    });

    galleryEl.addEventListener('keydown', (e) => {
      if (lightbox?.open) return;

      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        setActiveThumb((currentIndex + 1) % thumbButtons.length);
      } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        setActiveThumb((currentIndex - 1 + thumbButtons.length) % thumbButtons.length);
      }
    });
  }

  if (!lightbox || !lightboxImg) return;

  const allImages = [];
  let lightboxIndex = 0;
  let triggerElement = null;

  if (thumbList) {
    for (const btn of thumbList.querySelectorAll('button')) {
      allImages.push({
        src: btn.dataset.full,
        alt: btn.dataset.alt || '',
      });
    }
  } else if (mainImg) {
    const link = mainImg.closest('a[data-lightbox-trigger]');
    allImages.push({
      src: link?.href || mainImg.src,
      alt: mainImg.alt,
    });
  }

  function showLightboxImage(index) {
    lightboxIndex = index;
    const img = allImages[index];
    if (!img) return;

    lightboxImg.src = img.src;
    lightboxImg.alt = img.alt;

    if (prevBtn) prevBtn.hidden = allImages.length <= 1;
    if (nextBtn) nextBtn.hidden = allImages.length <= 1;
  }

  function openLightbox(index) {
    triggerElement = document.activeElement;
    showLightboxImage(index);
    lightbox.showModal();
  }

  function closeLightbox() {
    lightbox.close();
    triggerElement?.focus();
    triggerElement = null;
  }

  galleryEl.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-lightbox-trigger]');
    if (!trigger) return;

    e.preventDefault();

    let index = 0;
    if (thumbList) {
      const activeBtn = thumbList.querySelector('button[aria-current="true"]');
      if (activeBtn) {
        index = Array.from(thumbList.querySelectorAll('button')).indexOf(activeBtn);
      }
    }

    openLightbox(Math.max(0, index));
  });

  closeBtn?.addEventListener('click', closeLightbox);

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  prevBtn?.addEventListener('click', () => {
    showLightboxImage((lightboxIndex - 1 + allImages.length) % allImages.length);
  });

  nextBtn?.addEventListener('click', () => {
    showLightboxImage((lightboxIndex + 1) % allImages.length);
  });

  lightbox.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      e.preventDefault();
      closeLightbox();
    } else if (e.key === 'ArrowLeft' && allImages.length > 1) {
      e.preventDefault();
      showLightboxImage((lightboxIndex - 1 + allImages.length) % allImages.length);
    } else if (e.key === 'ArrowRight' && allImages.length > 1) {
      e.preventDefault();
      showLightboxImage((lightboxIndex + 1) % allImages.length);
    }
  });
})();
