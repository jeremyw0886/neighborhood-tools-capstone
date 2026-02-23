'use strict';

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
