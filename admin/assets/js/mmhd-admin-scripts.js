jQuery(document).ready(function($) {
    // Any admin-specific JavaScript code goes here

    // Example: Show/Hide meta boxes based on a select field
    // $('#mmhd_ticket_status').change(function() {
    //     var selectedStatus = $(this).val();
    //     if (selectedStatus === 'closed') {
    //         $('#your-other-meta-box').hide(); // Example: Hide another meta box if status is closed
    //     } else {
    //         $('#your-other-meta-box').show();
    //     }
    // });

    // Example: AJAX call for admin-specific actions
    $('#mmhd-save-credential').click(function() {
        alert('admin js here   ');
        var url = $('#credential_url').val();
        var username = $('#credential_username').val();
        var password = $('#credential_password').val();
        var notes = $('#credential_notes').val();
        var nonce = $('#mmhd_credentials_nonce').val();
        $.ajax({
            url: mmhd_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'mmhd_save_credential',
                ticket_id: mmhd_ajax_object.ticket_id,
                url: url,
                username: username,
                password: password,
                notes: notes,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mmhd-credentials-modal').hide();
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    var modal = $('#mmhd-credentials-modal');
    var addBtn = $('#mmhd-add-credentials-btn');
    var showBtn = $('#mmhd-show-credentials-btn');
    var closeBtn = $('#mmhd-close-modal');
    var credentialsList = $('#mmhd-credentials-list');
    var credentialsForm = $('#mmhd-credentials-form');

    addBtn.click(function() {
        credentialsForm.show();
        credentialsList.hide();
        modal.show();
    });
    showBtn.click(function() {
        credentialsForm.hide();
        credentialsList.show();
        modal.show();
    });

    closeBtn.click(function() {
        modal.hide();
    });

    // FAQ category shortcode copy-to-clipboard
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.mmhd-copy-shortcode');
        if (!btn) return;
        e.preventDefault();
        var text = btn.getAttribute('data-clipboard-text');
        if (!text) return;

        copyTextToClipboard(text, btn);
    });

    function copyTextToClipboard(text, btn) {
        var ok = false;

        // Modern Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showCopyFeedback(btn, true);
            }, function (err) {
                console.warn('Clipboard API failed:', err);
                fallbackCopyToClipboard(text, btn);
            });
            return;
        }

        // Fallback for older browsers
        fallbackCopyToClipboard(text, btn);
    }

    function fallbackCopyToClipboard(text, btn) {
        try {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopyFeedback(btn, ok);
        } catch (err) {
            console.warn('Fallback copy failed:', err);
            showCopyFeedback(btn, false);
        }
    }

    function showCopyFeedback(btn, ok) {
        var original = btn.textContent;
        if (ok) {
            btn.textContent = 'Copied!';
            btn.classList.add('button-primary');
        } else {
            btn.textContent = 'Failed';
            btn.classList.add('button-outline');
        }
        setTimeout(function () {
            btn.textContent = original;
            btn.classList.remove('button-primary');
            btn.classList.remove('button-outline');
        }, 1500);
    }
});