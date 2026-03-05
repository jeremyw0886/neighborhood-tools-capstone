'use strict';

(function () {
  const zipInput = document.getElementById('zip_code');
  const select   = document.getElementById('neighborhood_id');

  if (!zipInput || !select) return;

  const savedOptions = [...select.children].map(child => child.cloneNode(true));
  let generation = 0;

  function restoreFullList() {
    select.replaceChildren(...savedOptions.map(node => node.cloneNode(true)));
  }

  async function handleZipChange() {
    const zip = zipInput.value.trim();
    const token = ++generation;

    if (!/^\d{5}$/.test(zip)) {
      restoreFullList();
      return;
    }

    try {
      const res = await NT.fetch(`/api/neighborhoods/${encodeURIComponent(zip)}`);
      if (token !== generation) return;

      if (!res.ok) {
        restoreFullList();
        return;
      }

      const neighborhoods = await res.json();

      if (!neighborhoods.length) {
        restoreFullList();
        return;
      }

      const current = select.value;
      const grouped = {};

      for (const n of neighborhoods) {
        (grouped[n.city_name_nbh] ??= []).push(n);
      }

      const fragment = document.createDocumentFragment();
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'Select a neighborhood';
      fragment.appendChild(placeholder);

      for (const city of Object.keys(grouped).sort()) {
        const group = document.createElement('optgroup');
        group.label = city;

        for (const n of grouped[city]) {
          const opt = document.createElement('option');
          opt.value = n.id_nbh;
          opt.textContent = n.neighborhood_name_nbh;
          opt.selected = String(n.id_nbh) === current;
          group.appendChild(opt);
        }

        fragment.appendChild(group);
      }

      select.replaceChildren(fragment);
    } catch {
      if (token !== generation) return;
      NT.toast('Could not load neighborhoods for that ZIP code.', 'error');
      restoreFullList();
    }
  }

  zipInput.addEventListener('input', handleZipChange);

  if (/^\d{5}$/.test(zipInput.value.trim())) {
    handleZipChange();
  }
})();

(function () {
  const fields = document.querySelectorAll('input[type="password"]');
  if (!fields.length) return;

  for (const input of fields) {
    const group = input.closest('.form-group');
    if (!group) continue;

    group.setAttribute('data-password-toggle', '');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-toggle-visibility', '');
    btn.setAttribute('aria-label', 'Show password');

    const icon = document.createElement('i');
    icon.className = 'fa-regular fa-eye';
    icon.setAttribute('aria-hidden', 'true');
    btn.appendChild(icon);

    input.after(btn);

    btn.addEventListener('click', () => {
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      icon.className = showing ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  }

  const form = fields[0].closest('form');
  if (form) {
    form.addEventListener('submit', () => {
      for (const input of fields) {
        input.type = 'password';
      }
    });
  }
})();
