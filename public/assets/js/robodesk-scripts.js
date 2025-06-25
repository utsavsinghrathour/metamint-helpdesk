jQuery(document).ready(function($) {

    $('#change-ticket-status').change(function() {
        var ticketId = $(this).data('ticket-id');
        var newStatus = $(this).val();
        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'robodesk_change_ticket_status',
                ticket_id: ticketId,
                new_status: newStatus,
                nonce: robodesk_ajax_object.nonce
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred: ' + textStatus + ' ' + errorThrown);
                console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
            }
        });
    });

    $('.robodesk-change-status').click(function(e) {
        e.preventDefault();

        var button = $(this);
        var ticketId = button.data('ticket-id');
        var newStatus = button.data('new-status');

        console.log("Ticket ID:", ticketId);
        console.log("New Status:", newStatus);
        console.log("Nonce:", robodesk_ajax_object.nonce); // Check nonce
        console.log("Ajax URL:", robodesk_ajax_object.ajax_url);

        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'robodesk_change_ticket_status',
                ticket_id: ticketId,
                new_status: newStatus,
                nonce: robodesk_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (newStatus === 'closed') {
                        button.text('Reopen').data('new-status', 'open');
                    } else {
                        button.text('Close').data('new-status', 'closed');
                    }
                    button.closest("tr").find("td:eq(1)").text(newStatus);
                } else {
                    alert('Error changing ticket status.');
                    console.log(response); // Log the full response for debugging
                }
            },
            error: function(error) {
                alert('Error changing ticket status.');
                console.log(error);
            }
        });
    });


    // FAQ Voting
    $('.robodesk-faq-vote-button').click(function() {
        var faqId = $(this).data('faq-id');
        var voteType = $(this).data('vote-type');
        var button = $(this);

        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'robodesk_faq_vote',
                faq_id: faqId,
                vote_type: voteType,
                nonce: robodesk_ajax_object.faq_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#robodesk-faq-upvotes-' + faqId).text(response.data.upvotes);
                    $('#robodesk-faq-downvotes-' + faqId).text(response.data.downvotes);
                    button.addClass('robodesk-faq-voted');
                } else {
                    alert('Error voting.');
                    console.log(response);
                }
            },
            error: function(error){
                alert('Error voting.');
                console.log(error);
            }
        });
    });

    // Saving Credentials (Modal)
    $('#robodesk-save-credential').click(function() {
        alert(('scripts here'));
        var url = $('#credential_url').val();
        var username = $('#credential_username').val();
        var password = $('#credential_password').val();
        var notes = $('#credential_notes').val();
        var nonce = robodesk_ajax_object.credentials_nonce;

        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'robodesk_save_credential',
                ticket_id: robodesk_ajax_object.ticket_id,
                url: url,
                username: username,
                password: password,
                notes: notes,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#robodesk-credentials-modal').hide();
                    location.reload(); // Reload the page after saving
                } else {
                    alert(response.data.message);
                    console.log(response);
                }
            },
            error: function(error){
                alert('Error saving credentials.');
                console.log(error);
            }
        });
    });

    // Credential Modal Management
    var modal = $('#robodesk-credentials-modal');
    var addBtn = $('#robodesk-add-credentials-btn');
    var showBtn = $('#robodesk-show-credentials-btn');
    var closeBtn = $('#robodesk-close-modal');
    var credentialsList = $('#robodesk-credentials-list');
    var credentialsForm = $('#robodesk-credentials-form');

    addBtn.click(function() {
        credentialsForm.show();
        credentialsList.hide();
        modal.show();
    });

    showBtn.click(function(){
        credentialsForm.hide();
        credentialsList.show();
        modal.show();
    });

    closeBtn.click(function() {
        modal.hide();
    });
    $(document).on('click', '.robodesk-delete-credentials', function(e){
        e.preventDefault();
        var credentialId = $(this).data('credential-id');
        var nonce = robodesk_ajax_object.delete_credentials_nonce;
        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'robodesk_delete_credential',
                credential_id: credentialId,
                nonce: nonce
            },
            success: function(response){
                if(response.success){
                    location.reload();
                }else{
                    alert(response.data.message);
                    console.log(response);
                }
            },
            error: function(error){
                alert('Error deleting credentials.');
                console.log(error);
            }
        });
    });
});

jQuery(document).ready(function($) {
    // ... other code

    $('#robodesk-add-note').submit(function(e) { // Select the form by ID
        e.preventDefault(); 

        var formData = $(this).serialize();

        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#robodesk-add-note')[0].reset(); // Clear form on success
                    location.reload(); // Reload the page to show the new note.
                } else {
                    alert(response.data.message || 'Error adding note.'); // Display error message or default message
                    console.error(response); // Log the full response for debugging
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred: ' + textStatus + ' ' + errorThrown);
                console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
            }
        });
    });

    $('#note_attachment').on('change', function() {
        var files = $(this)[0].files;
        var fileList = $('#file-list');
        fileList.empty(); // Clear previous file list
        for (var i = 0; i < files.length; i++) {
            fileList.append('<div>' + files[i].name + '</div>');
        }
    });

});

jQuery(document).ready(function($) {
    // ... other JavaScript code

    // Show/Hide Add Credential Form
    $('#add-credential-button').click(function() {
       
        $('#credential-form').toggle();
    });

    // Add Credential AJAX
    $('#robodesk-add-credential').submit(function(e) {
        e.preventDefault();
       
        var formData = $(this).serialize();
        $.ajax({
            url: robodesk_ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log(response);
                if (response.success) {
                    $('#robodesk-add-credential')[0].reset();
                    location.reload();
                } else {
                    alert(response.data);
                    console.error(response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred: ' + textStatus + ' ' + errorThrown);
                console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
            }
        });
    });

    // Delete Credential AJAX
    $('.delete-credential').click(function() {
        var credentialId = $(this).data('credential-id');
        var confirmDelete = confirm("Are you sure you want to delete this credential?");
        if (confirmDelete) {
            $.ajax({
                url: robodesk_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'robodesk_delete_credential',
                    credential_id: credentialId,
                    nonce: robodesk_ajax_object.delete_credential_nonce // Use the correct nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                        console.error(response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' ' + errorThrown);
                    console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
                }
            });
        }
    });

    // Show Password Functionality
    $('.show-password').click(function() {
        var passwordSpan = $(this).prev('.password-hidden');
        var password = $(this).data('password');
        if (passwordSpan.text() === '********') {
            passwordSpan.text(password);
            $(this).text('Hide');
        } else {
            passwordSpan.text('********');
            $(this).text('Show');
        }
    });
  
        $('body').append('<div class="spinner"></div>');
        $('#robodesk-create-ticket').submit(function (e) {
            e.preventDefault();
         
            $('#robodesk-response-message').html('');
            var formData = {
                action: 'robodesk_create_new_ticket', 
                ticket_title: $('#ticket_title').val(),
                ticket_content: $('#ticket_content').val(),
                ticket_priority: $('#ticket_priority').val(),
                robodesk_nonce: $('#_wpnonce').val(), 
            };
            $.ajax({
                url: robodesk_ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                beforeSend: function () {  
                    $('#robodesk-create-ticket').addClass('blurred');
                    $('.spinner').css('display', 'block');
                },
                success: function (response) {
                    if (response.success) {
                        
                        $('#robodesk-response-message').html('<p style="color: green;">Ticket created successfully!</p>');
                        $('#robodesk-create-ticket')[0].reset();
                        $('#robodesk-response-message').delay(5000).fadeOut(2000);
                    } else {
                  
                        $('#robodesk-response-message').html('<p style="color: red;">Error: ' + response.data.message + '</p>').fadeOut(1000);
                        $('#robodesk-response-message').delay(5000).fadeOut(2000);
                    }
                },
                error: function () {
               
                    $('#robodesk-response-message').html('<p style="color: red;">An error occurred. Please try again.</p>');
                    $('#robodesk-response-message').delay(5000).fadeOut(2000);
                },
                
                complete: function () {
                    $('#robodesk-create-ticket').removeClass('blurred');
                    $('.spinner').css('display', 'none');
                  
                }
            });
            
            
        });
        

});
