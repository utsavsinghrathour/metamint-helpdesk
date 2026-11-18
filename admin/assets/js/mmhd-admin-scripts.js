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
});