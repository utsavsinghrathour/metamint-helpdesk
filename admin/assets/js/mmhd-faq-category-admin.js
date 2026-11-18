/**
 * FAQ category screen: copy the generated shortcode to the clipboard.
 */
(function () {
	'use strict';

	function showCopyFeedback(button, copied) {
		var original = button.getAttribute('data-original-label') || button.textContent;
		button.setAttribute('data-original-label', original);
		button.textContent = copied ? 'Copied!' : 'Copy failed';

		window.setTimeout(function () {
			button.textContent = original;
		}, 1500);
	}

	function fallbackCopy(text, button) {
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.setAttribute('readonly', 'readonly');
		textarea.style.position = 'fixed';
		textarea.style.top = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();

		try {
			var copied = document.execCommand('copy');
			showCopyFeedback(button, copied);
		} catch (error) {
			showCopyFeedback(button, false);
		}

		document.body.removeChild(textarea);
	}

	function copyText(text, button) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(
				function () {
					showCopyFeedback(button, true);
				},
				function () {
					fallbackCopy(text, button);
				}
			);
			return;
		}

		fallbackCopy(text, button);
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.mmhd-copy-shortcode');
		if (!button) {
			return;
		}

		event.preventDefault();
		var text = button.getAttribute('data-clipboard-text');
		if (!text) {
			return;
		}

		copyText(text, button);
	});
})();
