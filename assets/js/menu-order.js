(function () {
  var searchInput = document.getElementById('menuSearch');
  var tagsWrap = document.getElementById('menuTags');

  if (tagsWrap) {
    var activeTag = 'semua';

    var applyFilter = function () {
      var query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
      var sections = document.querySelectorAll('.menu-category');

      sections.forEach(function (section) {
        var category = section.getAttribute('data-category');
        var categoryMatches = activeTag === 'semua' || category === activeTag;
        var visibleCount = 0;

        section.querySelectorAll('.menu-item').forEach(function (item) {
          var name = item.getAttribute('data-name') || '';
          var nameMatches = query === '' || name.indexOf(query) !== -1;
          var show = categoryMatches && nameMatches;
          item.classList.toggle('is-hidden', !show);
          if (show) visibleCount++;
        });

        section.classList.toggle('is-hidden', visibleCount === 0);
      });
    };

    tagsWrap.querySelectorAll('.menu-tag').forEach(function (btn) {
      btn.addEventListener('click', function () {
        tagsWrap.querySelectorAll('.menu-tag').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        activeTag = btn.getAttribute('data-filter');
        applyFilter();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', applyFilter);
    }
  }

  /* ---------- Item detail bottom sheet ---------- */
  var sheet = document.getElementById('itemSheet');
  if (!sheet) return;

  var sheetImage = document.getElementById('sheetImage');
  var sheetName = document.getElementById('sheetName');
  var sheetDesc = document.getElementById('sheetDesc');
  var sheetPrice = document.getElementById('sheetPrice');
  var sheetItemId = document.getElementById('sheetItemId');
  var sheetQty = document.getElementById('sheetQty');
  var sheetQtyFooter = document.getElementById('sheetQtyFooter');
  var sheetNotes = document.getElementById('sheetNotes');
  var sheetHint = document.getElementById('sheetHint');
  var sheetForm = document.getElementById('sheetForm');
  var spiceSection = document.getElementById('sheetSpiceSection');
  var tempSection = document.getElementById('sheetTempSection');

  function resetRowQtys(section) {
    section.querySelectorAll('.option-qty-row__input').forEach(function (input) {
      input.value = '0';
    });
  }

  function openSheet(card) {
    sheetImage.src = card.getAttribute('data-image');
    sheetImage.alt = card.getAttribute('data-item-name');
    sheetName.textContent = card.getAttribute('data-item-name');
    sheetDesc.textContent = card.getAttribute('data-desc');
    sheetPrice.textContent = card.getAttribute('data-price-label');
    sheetItemId.value = card.getAttribute('data-id');
    sheetQty.value = '1';
    sheetNotes.value = '';
    sheetHint.classList.remove('show');
    resetRowQtys(spiceSection);
    resetRowQtys(tempSection);

    var category = card.getAttribute('data-category');
    var serveTemp = card.getAttribute('data-serve-temp');
    var spiceOption = card.getAttribute('data-spice-option');
    // Only show the Tidak Pedas/.../Sangat Pedas picker when this dish
    // actually has adjustable spice — sebagian hidangan disajikan dengan
    // level tetap sesuai resep, tidak perlu ditanya.
    var isFood = category === 'Makanan' && spiceOption === 'ada';
    // Only show the Dingin/Panas picker when the item can genuinely be
    // served either way — items fixed to one temp (mis. Es Jeruk selalu
    // dingin) don't need to ask.
    var isDrink = category === 'Minuman' && serveTemp === 'keduanya';
    spiceSection.style.display = isFood ? '' : 'none';
    tempSection.style.display = isDrink ? '' : 'none';

    // Kalau item punya picker (list qty per level), qty total dihitung dari
    // jumlah tiap baris — stepper qty global di footer jadi tidak relevan
    // lagi dan disembunyikan.
    var hasPicker = isFood || isDrink;
    sheetForm.setAttribute('data-has-picker', hasPicker ? '1' : '0');
    sheetQtyFooter.style.display = hasPicker ? 'none' : '';

    sheet.classList.add('active');
    sheet.setAttribute('aria-hidden', 'false');
    document.body.classList.add('item-sheet-open');
  }

  function closeSheet() {
    sheet.classList.remove('active');
    sheet.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('item-sheet-open');
  }

  document.querySelectorAll('.menu-item--pick').forEach(function (card) {
    card.addEventListener('click', function () { openSheet(card); });
  });

  sheet.querySelectorAll('[data-sheet-close]').forEach(function (el) {
    el.addEventListener('click', closeSheet);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSheet();
  });

  sheet.querySelector('[data-qty-minus]').addEventListener('click', function () {
    var v = Math.max(1, parseInt(sheetQty.value, 10) - 1);
    sheetQty.value = v;
  });
  sheet.querySelector('[data-qty-plus]').addEventListener('click', function () {
    var v = Math.min(20, parseInt(sheetQty.value, 10) + 1);
    sheetQty.value = v;
  });

  // Stepper +/- per baris tingkat pedas/suhu (event delegation karena
  // barisnya sudah dicetak statis di HTML, cukup satu listener).
  sheet.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-row-minus], [data-row-plus]');
    if (!btn) return;
    var input = btn.parentElement.querySelector('.option-qty-row__input');
    var current = parseInt(input.value, 10) || 0;
    if (btn.hasAttribute('data-row-minus')) {
      input.value = Math.max(0, current - 1);
    } else {
      input.value = Math.min(20, current + 1);
    }
  });

  sheetForm.addEventListener('submit', function (e) {
    if (sheetForm.getAttribute('data-has-picker') !== '1') return;
    var visibleSection = spiceSection.style.display !== 'none' ? spiceSection : tempSection;
    var total = 0;
    visibleSection.querySelectorAll('.option-qty-row__input').forEach(function (input) {
      total += parseInt(input.value, 10) || 0;
    });
    if (total === 0) {
      e.preventDefault();
      sheetHint.classList.add('show');
    }
  });
})();
