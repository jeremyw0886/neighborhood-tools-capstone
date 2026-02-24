(function () {
  'use strict';

  const form = document.querySelector('#deposit-detail form[action^="/payments/deposit/"]');
  if (!form) return;

  const radios = form.querySelectorAll('input[name="action"]');
  const forfeitFieldset = form.querySelectorAll('fieldset')[1];
  if (!radios.length || !forfeitFieldset) return;

  const inputs = forfeitFieldset.querySelectorAll('input, textarea');

  function sync() {
    const isForfeit = form.querySelector('input[name="action"]:checked')?.value === 'forfeit';
    forfeitFieldset.hidden = !isForfeit;
    inputs.forEach(function (el) { el.disabled = !isForfeit; });
  }

  radios.forEach(function (radio) { radio.addEventListener('change', sync); });
  sync();
})();
