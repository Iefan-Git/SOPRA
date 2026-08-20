// SOPRA — searchable dropdown ("combobox") enhancement.
//
// Turns any <select class="combo-select"> into a text field you can type
// into to filter the list, while a hidden native <select> keeps carrying
// the same name/value it always did — so PHP form handling, "required",
// and onchange="...submit()" auto-submit filters all keep working exactly
// as before with zero backend changes.
(function () {
  function enhance(select) {
    if (select.classList.contains('combo-native')) {
      return;
    }

    var options = Array.prototype.map.call(select.options, function (opt) {
      return { value: opt.value, text: opt.text };
    });

    // Wrap the select in a container, then hide it (still submits fine).
    var wrap = document.createElement('div');
    wrap.className = 'combo';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('combo-native');

    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'combo-input search-input';
    input.autocomplete = 'off';
    input.placeholder = select.getAttribute('data-placeholder') || (options[0] ? options[0].text : 'Search...');
    // Move the "required" constraint to the visible input so the browser's
    // validation bubble anchors to something the user can actually see,
    // instead of to the now visually-hidden native select.
    if (select.hasAttribute('required')) {
      input.setAttribute('required', 'required');
      select.removeAttribute('required');
    }
    wrap.appendChild(input);

    var panel = document.createElement('div');
    panel.className = 'combo-panel';
    wrap.appendChild(panel);

    var activeIdx = -1;
    var filtered = options;

    function selectedOption() {
      return options[select.selectedIndex] || null;
    }

    function syncInputFromSelect() {
      var cur = selectedOption();
      input.value = cur && cur.value !== '' ? cur.text : '';
    }

    function renderPanel() {
      panel.innerHTML = '';
      if (!filtered.length) {
        var empty = document.createElement('div');
        empty.className = 'combo-empty';
        empty.textContent = 'No matches';
        panel.appendChild(empty);
        return;
      }
      filtered.forEach(function (opt, i) {
        var row = document.createElement('div');
        row.className = 'combo-option' + (i === activeIdx ? ' active' : '') + (opt.value === select.value ? ' selected' : '');
        row.textContent = opt.value === '' ? opt.text : opt.text;
        row.dataset.value = opt.value;
        row.addEventListener('mousedown', function (ev) {
          ev.preventDefault(); // keep focus, fire before blur
          choose(opt);
        });
        panel.appendChild(row);
      });
    }

    function choose(opt) {
      select.value = opt.value;
      input.value = opt.value === '' ? '' : opt.text;
      closePanel();
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function openPanel() {
      var q = input.value.trim().toLowerCase();
      filtered = q === '' ? options.filter(function (o) { return o.value !== ''; })
                           : options.filter(function (o) { return o.value !== '' && o.text.toLowerCase().indexOf(q) !== -1; });
      activeIdx = -1;
      renderPanel();
      panel.classList.add('open');
    }

    function closePanel() {
      panel.classList.remove('open');
      activeIdx = -1;
    }

    input.addEventListener('focus', openPanel);
    input.addEventListener('input', openPanel);

    input.addEventListener('keydown', function (ev) {
      if (!panel.classList.contains('open') && (ev.key === 'ArrowDown' || ev.key === 'ArrowUp')) {
        openPanel();
        return;
      }
      if (ev.key === 'ArrowDown') {
        ev.preventDefault();
        activeIdx = Math.min(activeIdx + 1, filtered.length - 1);
        renderPanel();
        var el = panel.children[activeIdx];
        if (el) el.scrollIntoView({ block: 'nearest' });
      } else if (ev.key === 'ArrowUp') {
        ev.preventDefault();
        activeIdx = Math.max(activeIdx - 1, 0);
        renderPanel();
        var el2 = panel.children[activeIdx];
        if (el2) el2.scrollIntoView({ block: 'nearest' });
      } else if (ev.key === 'Enter') {
        if (panel.classList.contains('open') && activeIdx >= 0 && filtered[activeIdx]) {
          ev.preventDefault();
          choose(filtered[activeIdx]);
        }
      } else if (ev.key === 'Escape') {
        closePanel();
        syncInputFromSelect();
      } else if (ev.key === 'Backspace' && input.value === '') {
        // Let an empty field clear the selection on blur.
      }
    });

    input.addEventListener('blur', function () {
      setTimeout(function () {
        closePanel();
        // Typed text with no matching option (and not a valid re-selection): revert.
        var typed = input.value.trim();
        if (typed === '') {
          if (select.value !== '') {
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
          return;
        }
        var exact = options.find(function (o) { return o.text === typed; });
        if (exact) {
          if (select.value !== exact.value) {
            select.value = exact.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
        } else {
          syncInputFromSelect();
        }
      }, 120);
    });

    document.addEventListener('click', function (ev) {
      if (!wrap.contains(ev.target)) closePanel();
    });

    syncInputFromSelect();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.combo-select').forEach(enhance);
  });

  // Exposed so pages can enhance a <select class="combo-select"> that is
  // created or repopulated after DOMContentLoaded (e.g. the cascading
  // State -> District picker on the Record Duty form).
  window.comboEnhance = enhance;
})();
