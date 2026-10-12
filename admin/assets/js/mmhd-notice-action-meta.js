(function () {
    function init() {
        var radios = document.querySelectorAll('input[name="_mmhd_notice_action"]');
        var wrap = document.getElementById('mmhd-notice-initial-message-wrap');
        if (!radios.length || !wrap) return;
        var toggle = function () {
            var checked = document.querySelector('input[name="_mmhd_notice_action"]:checked');
            wrap.style.display = checked && checked.value === 'start_conversation' ? '' : 'none';
        };
        radios.forEach(function (r) { r.addEventListener('change', toggle); });
        toggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
