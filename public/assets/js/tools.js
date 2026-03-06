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
  let gallery = document.getElementById('gallery-manager');
  const fieldset = gallery?.closest('fieldset') ?? document.querySelector('section[aria-labelledby="edit-tool-heading"] > fieldset:last-of-type');
  if (!fieldset) return;

  const toolId = gallery?.dataset.toolId ?? document.getElementById('add-photo')?.dataset.toolId;
  if (!toolId) return;

  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  const MAX_SIZE = 5 * 1024 * 1024;

  let busy = false;

  function getGallery() {
    return document.getElementById('gallery-manager');
  }

  function setBusy(state) {
    busy = state;
    const g = getGallery();
    if (g) g.ariaBusy = state ? 'true' : 'false';
  }

  function getImageIds() {
    const g = getGallery();
    if (!g) return [];
    return Array.from(g.querySelectorAll('li[data-image-id]'))
      .map(li => parseInt(li.dataset.imageId, 10));
  }

  function escapeAttr(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML.replace(/"/g, '&quot;');
  }

  function clamp(val, min, max) {
    return Math.max(min, Math.min(max, val));
  }

  function ensureGallery() {
    let g = getGallery();
    if (g) return g;

    const emptyMsg = fieldset.querySelector('#gallery-empty');
    if (emptyMsg) emptyMsg.remove();

    g = document.createElement('ol');
    g.id = 'gallery-manager';
    g.setAttribute('aria-label', 'Tool photos');
    g.dataset.toolId = toolId;

    const hint = fieldset.querySelector('#gallery-crop-hint');
    if (hint) {
      hint.after(g);
    } else {
      fieldset.querySelector('legend').after(g);
    }

    attachGalleryListeners(g);
    gallery = g;
    return g;
  }

  function updateSlotHint() {
    const g = getGallery();
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

  function validateFile(file) {
    if (!ALLOWED_TYPES.includes(file.type)) {
      NT.toast('Invalid file type. Use JPEG, PNG, or WebP.', 'error');
      return false;
    }
    if (file.size > MAX_SIZE) {
      NT.toast('File exceeds the 5 MB limit.', 'error');
      return false;
    }
    return true;
  }

  function buildLiHtml(img) {
    const thumb = escapeAttr(img.filename.replace(/\.(\w+)$/, '-400w.$1'));
    const altSafe = escapeAttr(img.alt_text || '');
    const csrfToken = escapeAttr(document.querySelector('meta[name="csrf-token"]')?.content ?? '');
    const fx = img.focal_x ?? 50;
    const fy = img.focal_y ?? 50;
    const focalStyle = (fx !== 50 || fy !== 50) ? ` style="object-position:${fx}% ${fy}%"` : '';

    return `
      <img src="/uploads/tools/${thumb}"
           alt="${altSafe}"
           width="400" height="268"
           loading="lazy"
           decoding="async"${focalStyle}>
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
      <form method="post" action="/tools/${toolId}/images/${img.id}" data-delete-form>
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <button type="submit" data-intent="danger" aria-label="Delete this photo">
          <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
        </button>
      </form>
    `;
  }

  async function persistOrder() {
    try {
      const res = await NT.fetch(`/tools/${toolId}/images/order`, {
        method: 'PATCH',
        body: JSON.stringify({ order: getImageIds() }),
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

  const cropDialog  = document.getElementById('crop-dialog');
  const cropPreview = document.getElementById('crop-preview');
  const cropViewport = document.getElementById('crop-viewport');
  const confirmBtn  = cropDialog?.querySelector('[data-crop-confirm]');
  const cancelBtn   = cropDialog?.querySelector('[data-crop-cancel]');
  const cropLabelEl = cropDialog?.querySelector('[data-crop-label]');
  const confirmIcon = confirmBtn?.querySelector('i');

  let cropMode = null;
  let cropFile = null;
  let cropObjectUrl = null;
  let cropFocalX = 50;
  let cropFocalY = 50;
  let repositionImageId = null;

  function cleanupCropState() {
    if (cropObjectUrl) {
      URL.revokeObjectURL(cropObjectUrl);
      cropObjectUrl = null;
    }
    cropFile = null;
    cropMode = null;
    repositionImageId = null;
    const fi = document.getElementById('add-photo');
    if (fi) fi.value = '';
  }

  function closeCropDialog() {
    if (!cropDialog) return;
    cleanupCropState();
    cropDialog.close();
  }

  function openCropUpload(file) {
    if (!cropDialog || !cropPreview) return;

    cropMode = 'upload';
    cropFile = file;
    cropFocalX = 50;
    cropFocalY = 50;
    repositionImageId = null;

    cropObjectUrl = URL.createObjectURL(file);
    cropPreview.src = cropObjectUrl;
    cropPreview.style.objectPosition = '50% 50%';

    if (confirmIcon) confirmIcon.className = 'fa-solid fa-cloud-arrow-up';
    if (cropLabelEl) cropLabelEl.textContent = 'Upload';
    if (confirmBtn) confirmBtn.disabled = false;

    cropDialog.dataset.mode = 'upload';
    cropDialog.showModal();
  }

  function openCropReposition(imageId, imgSrc, focalX, focalY) {
    if (!cropDialog || !cropPreview) return;

    cropMode = 'reposition';
    cropFile = null;
    cropObjectUrl = null;
    cropFocalX = focalX;
    cropFocalY = focalY;
    repositionImageId = imageId;

    cropPreview.src = imgSrc;
    cropPreview.style.objectPosition = `${focalX}% ${focalY}%`;

    if (confirmIcon) confirmIcon.className = 'fa-solid fa-check';
    if (cropLabelEl) cropLabelEl.textContent = 'Save';
    if (confirmBtn) confirmBtn.disabled = false;

    cropDialog.dataset.mode = 'reposition';
    cropDialog.showModal();
  }

  if (cropViewport && cropPreview) {
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let startFX = 50;
    let startFY = 50;

    cropViewport.addEventListener('pointerdown', (e) => {
      e.preventDefault();
      cropViewport.setPointerCapture(e.pointerId);
      dragging = true;
      startX = e.clientX;
      startY = e.clientY;
      startFX = cropFocalX;
      startFY = cropFocalY;
    });

    cropViewport.addEventListener('pointermove', (e) => {
      if (!dragging) return;
      e.preventDefault();

      const vpW = cropViewport.clientWidth;
      const vpH = cropViewport.clientHeight;
      const natW = cropPreview.naturalWidth || 800;
      const natH = cropPreview.naturalHeight || 536;

      const dx = e.clientX - startX;
      const dy = e.clientY - startY;
      const pctDx = vpW > 0 ? (dx / vpW) * -100 : 0;
      const pctDy = vpH > 0 ? (dy / vpH) * -100 : 0;

      const vpAspect = vpW / vpH;
      const natAspect = natW / natH;
      let sensitivityX, sensitivityY;

      if (natAspect > vpAspect) {
        sensitivityX = (natW * (vpH / natH)) / vpW;
        sensitivityY = 1;
      } else {
        sensitivityX = 1;
        sensitivityY = (natH * (vpW / natW)) / vpH;
      }

      cropFocalX = clamp(Math.round(startFX + pctDx * sensitivityX), 0, 100);
      cropFocalY = clamp(Math.round(startFY + pctDy * sensitivityY), 0, 100);
      cropPreview.style.objectPosition = `${cropFocalX}% ${cropFocalY}%`;
    });

    cropViewport.addEventListener('pointerup', () => { dragging = false; });
    cropViewport.addEventListener('pointercancel', () => { dragging = false; });

    cropViewport.addEventListener('keydown', (e) => {
      let handled = false;

      if (e.key === 'ArrowLeft')  { cropFocalX = clamp(cropFocalX - 1, 0, 100); handled = true; }
      if (e.key === 'ArrowRight') { cropFocalX = clamp(cropFocalX + 1, 0, 100); handled = true; }
      if (e.key === 'ArrowUp')    { cropFocalY = clamp(cropFocalY - 1, 0, 100); handled = true; }
      if (e.key === 'ArrowDown')  { cropFocalY = clamp(cropFocalY + 1, 0, 100); handled = true; }

      if (handled) {
        e.preventDefault();
        cropPreview.style.objectPosition = `${cropFocalX}% ${cropFocalY}%`;
      }
    });
  }

  confirmBtn?.addEventListener('click', async () => {
    if (busy) return;

    if (cropMode === 'upload') {
      if (!cropFile) return;

      confirmBtn.disabled = true;
      confirmBtn.dataset.loading = '';
      setBusy(true);

      const fd = new FormData();
      fd.append('photo', cropFile);
      fd.append('focal_x', String(cropFocalX));
      fd.append('focal_y', String(cropFocalY));

      try {
        const res = await NT.fetch(`/tools/${toolId}/images`, {
          method: 'POST',
          body: fd,
        });

        if (!res.ok) {
          const err = await res.json().catch(() => null);
          NT.toast(err?.error ?? 'Failed to upload photo.', 'error');
          confirmBtn.disabled = false;
          delete confirmBtn.dataset.loading;
          setBusy(false);
          return;
        }

        const img = await res.json();
        const g = ensureGallery();

        const li = document.createElement('li');
        li.dataset.imageId = img.id;
        li.dataset.focalX = String(img.focal_x ?? cropFocalX);
        li.dataset.focalY = String(img.focal_y ?? cropFocalY);
        li.draggable = true;
        li.tabIndex = 0;
        li.innerHTML = buildLiHtml(img);

        g.appendChild(li);
        closeCropDialog();
        updateSlotHint();
        NT.toast('Photo uploaded.', 'success');
      } catch {
        NT.toast('Failed to upload photo.', 'error');
        confirmBtn.disabled = false;
        delete confirmBtn.dataset.loading;
      } finally {
        setBusy(false);
      }
    } else if (cropMode === 'reposition') {
      if (!repositionImageId) return;

      confirmBtn.disabled = true;
      confirmBtn.dataset.loading = '';
      setBusy(true);

      try {
        const res = await NT.fetch(`/tools/${toolId}/images/${repositionImageId}`, {
          method: 'PATCH',
          body: JSON.stringify({ focal_x: cropFocalX, focal_y: cropFocalY }),
        });

        if (!res.ok) {
          NT.toast('Failed to save position.', 'error');
          confirmBtn.disabled = false;
          delete confirmBtn.dataset.loading;
          setBusy(false);
          return;
        }

        const g = getGallery();
        const li = g?.querySelector(`li[data-image-id="${repositionImageId}"]`);
        if (li) {
          li.dataset.focalX = String(cropFocalX);
          li.dataset.focalY = String(cropFocalY);
          const img = li.querySelector('img');
          if (img) {
            img.style.objectPosition = (cropFocalX !== 50 || cropFocalY !== 50)
              ? `${cropFocalX}% ${cropFocalY}%`
              : '';
          }
        }

        closeCropDialog();
        NT.toast('Position saved.', 'success');
      } catch {
        NT.toast('Failed to save position.', 'error');
        confirmBtn.disabled = false;
        delete confirmBtn.dataset.loading;
      } finally {
        setBusy(false);
      }
    }
  });

  cancelBtn?.addEventListener('click', closeCropDialog);
  cropDialog?.addEventListener('close', cleanupCropState);

  let dragItem = null;

  function attachGalleryListeners(g) {

    g.addEventListener('dragstart', (e) => {
      if (e.target.closest('input, button, label, a')) {
        e.preventDefault();
        return;
      }

      const li = e.target.closest('li[data-image-id]');
      if (!li) return;
      dragItem = li;
      li.dataset.dragging = '';
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', li.dataset.imageId);
    });

    g.addEventListener('dragend', (e) => {
      const li = e.target.closest('li[data-image-id]');
      if (li) delete li.dataset.dragging;
      dragItem = null;
      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }
    });

    g.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';

      const target = e.target.closest('li[data-image-id]');
      if (!target || target === dragItem) return;

      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }
      target.dataset.dragOver = '';
    });

    g.addEventListener('drop', async (e) => {
      e.preventDefault();
      const target = e.target.closest('li[data-image-id]');
      if (!target || !dragItem || target === dragItem || busy) return;

      for (const el of g.querySelectorAll('[data-drag-over]')) {
        delete el.dataset.dragOver;
      }

      const items = Array.from(g.querySelectorAll('li[data-image-id]'));
      const dragIdx = items.indexOf(dragItem);
      const targetIdx = items.indexOf(target);
      const refNode = dragItem.nextElementSibling;

      if (dragIdx < targetIdx) {
        target.after(dragItem);
      } else {
        target.before(dragItem);
      }

      setBusy(true);
      const ok = await persistOrder();
      if (!ok) {
        if (refNode) refNode.before(dragItem);
        else g.appendChild(dragItem);
      }
      setBusy(false);
    });

    g.addEventListener('keydown', async (e) => {
      if (e.target !== e.target.closest('li[data-image-id]')) return;
      const li = e.target;
      if (!li || busy) return;

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
      setBusy(true);
      const ok = await persistOrder();
      if (!ok) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
          swapTarget.after(li);
        } else {
          swapTarget.before(li);
        }
        li.focus();
      }
      setBusy(false);
    });

    g.addEventListener('click', async (e) => {
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
          const promoted = g.querySelector(`li[data-image-id="${data.new_primary_id}"] [data-primary-radio]`);
          if (promoted) {
            promoted.checked = true;
            const label = promoted.nextElementSibling;
            if (label) label.innerHTML = '<i class="fa-solid fa-star" aria-hidden="true"></i> Primary';
          }
        }

        const remaining = g.querySelectorAll('li[data-image-id]').length;

        if (remaining === 0) {
          g.remove();
          const empty = document.createElement('p');
          empty.id = 'gallery-empty';
          empty.textContent = 'No photos uploaded yet.';
          const hint = fieldset.querySelector('#gallery-crop-hint');
          if (hint) {
            hint.after(empty);
          } else {
            fieldset.querySelector('legend').after(empty);
          }
        }

        updateSlotHint();
        NT.toast('Photo deleted.', 'success');
      } catch {
        NT.toast('Failed to delete photo.', 'error');
        deleteBtn.disabled = false;
      } finally {
        setBusy(false);
      }
    });

    g.addEventListener('change', async (e) => {
      const radio = e.target.closest('[data-primary-radio]');
      if (!radio || busy) return;

      const imageId = radio.value;
      setBusy(true);

      for (const r of g.querySelectorAll('[data-primary-radio]')) {
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

        for (const li of g.querySelectorAll('li[data-image-id]')) {
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
        for (const r of g.querySelectorAll('[data-primary-radio]')) {
          r.disabled = false;
        }
        setBusy(false);
      }
    });

    g.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-reposition]');
      if (!btn || busy) return;

      const li = btn.closest('li[data-image-id]');
      if (!li) return;

      const imageId = li.dataset.imageId;
      const img = li.querySelector('img');
      if (!img) return;

      const fx = parseInt(li.dataset.focalX ?? '50', 10);
      const fy = parseInt(li.dataset.focalY ?? '50', 10);

      openCropReposition(imageId, img.src, fx, fy);
    });

    g.addEventListener('blur', async (e) => {
      const input = e.target.closest('[data-alt-input]');
      if (!input || busy) return;

      const imageId = input.dataset.imageId;
      const altText = input.value.trim().slice(0, 255);

      try {
        const res = await NT.fetch(`/tools/${toolId}/images/${imageId}`, {
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
    }, true);
  }

  if (gallery) {
    attachGalleryListeners(gallery);
  }

  const photoSubmit = document.getElementById('photo-submit');
  const dropZone = document.getElementById('photo-drop-zone');
  const fileInput = document.getElementById('add-photo');

  if (photoSubmit) photoSubmit.hidden = true;

  if (dropZone && fileInput) {
    dropZone.setAttribute('role', 'button');
    dropZone.tabIndex = 0;
    dropZone.setAttribute('aria-label', 'Choose a photo to upload');

    dropZone.addEventListener('click', (e) => {
      if (e.target === fileInput) return;
      fileInput.click();
    });

    dropZone.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        fileInput.click();
      }
    });

    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      if (!file) return;
      if (!validateFile(file)) { fileInput.value = ''; return; }
      openCropUpload(file);
    });

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

      const file = Array.from(e.dataTransfer.files).find(f => ALLOWED_TYPES.includes(f.type));

      if (!file) {
        NT.toast('No valid image file found. Use JPEG, PNG, or WebP.', 'error');
        return;
      }

      if (!validateFile(file)) return;
      openCropUpload(file);
    });
  }
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

      const fx = btn.dataset.focalX ?? '50';
      const fy = btn.dataset.focalY ?? '50';
      mainImg.style.objectPosition = (fx !== '50' || fy !== '50') ? `${fx}% ${fy}%` : '';

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
