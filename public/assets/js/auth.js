'use strict';

(function () {
  const zipInput = document.getElementById('zip_code');
  const select   = document.getElementById('neighborhood_id');

  if (!zipInput || !select) return;

  const fullOptions = select.innerHTML;
  let controller    = null;

  function restoreFullList() {
    select.innerHTML = fullOptions;
  }

  function handleZipChange() {
    const zip = zipInput.value.trim();

    if (controller) {
      controller.abort();
      controller = null;
    }

    if (!/^\d{5}$/.test(zip)) {
      restoreFullList();
      return;
    }

    controller = new AbortController();

    fetch('/api/neighborhoods/' + encodeURIComponent(zip), {
      signal: controller.signal,
    })
      .then(function (res) {
        if (!res.ok) throw new Error(res.statusText);
        return res.json();
      })
      .then(function (neighborhoods) {
        if (!neighborhoods.length) {
          restoreFullList();
          return;
        }

        const current = select.value;

        const grouped = {};
        neighborhoods.forEach(function (n) {
          const city = n.city_name_nbh;
          if (!grouped[city]) grouped[city] = [];
          grouped[city].push(n);
        });

        let html = '<option value="">Select a neighborhood</option>';

        Object.keys(grouped).sort().forEach(function (city) {
          html += '<optgroup label="' + city.replace(/"/g, '&quot;') + '">';
          grouped[city].forEach(function (n) {
            const sel = String(n.id_nbh) === current ? ' selected' : '';
            const name = n.neighborhood_name_nbh
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;');
            html += '<option value="' + n.id_nbh + '"' + sel + '>' + name + '</option>';
          });
          html += '</optgroup>';
        });

        select.innerHTML = html;
      })
      .catch(function (err) {
        if (err.name === 'AbortError') return;
        restoreFullList();
      });
  }

  zipInput.addEventListener('input', handleZipChange);

  if (/^\d{5}$/.test(zipInput.value.trim())) {
    handleZipChange();
  }
})();
