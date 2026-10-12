(function () {
    if (window.__mmhdNoticePickerInit) {
        return;
    }
    window.__mmhdNoticePickerInit = true;

    function initPicker(root) {
        var select = root.querySelector('select[name="mmhd_related_notices[]"]');
        var search = root.querySelector('.mmhd-notice-search');
        var selectedBox = root.querySelector('.mmhd-notice-selected');
        if (!select || !selectedBox) return;

        function renderChips() {
            selectedBox.innerHTML = '';
            var options = Array.prototype.slice.call(select.selectedOptions || []);
            options.forEach(function (option) {
                var chip = document.createElement('span');
                chip.className = 'mmhd-notice-chip';
                chip.textContent = option.textContent;

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'mmhd-notice-chip-remove';
                remove.setAttribute('aria-label', 'Remove');
                remove.textContent = '\u00d7';
                remove.addEventListener('click', function () {
                    option.selected = false;
                    renderChips();
                    select.dispatchEvent(new Event('change'));
                });

                chip.appendChild(remove);
                selectedBox.appendChild(chip);
            });
        }

        select.addEventListener('change', renderChips);
        renderChips();

        if (search) {
            search.addEventListener('input', function () {
                var query = search.value.toLowerCase();
                var options = select.options;
                for (var i = 0; i < options.length; i++) {
                    options[i].style.display =
                        options[i].textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
                }
            });
        }
    }

    function initAll() {
        var roots = document.querySelectorAll('.mmhd-notice-picker');
        for (var i = 0; i < roots.length; i++) {
            initPicker(roots[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
