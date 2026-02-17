'use strict';

(function () {
  var checkbox = document.getElementById('uses-fuel');
  var group = document.getElementById('fuel-type-group');
  if (!checkbox || !group) return;

  function toggle() {
    group.hidden = !checkbox.checked;
  }

  toggle();
  checkbox.addEventListener('change', toggle);
})();
