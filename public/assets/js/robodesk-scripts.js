jQuery(document).ready(function ($) {
  $("#change-ticket-status").change(function () {
    var ticketId = $(this).data("ticket-id");
    var newStatus = $(this).val();
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_change_ticket_status",
        ticket_id: ticketId,
        new_status: newStatus,
        nonce: robodesk_ajax_object.nonce,
      },
      success: function (response) {
        if (!response.success) {
          alert(response.data);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        alert("An error occurred: " + textStatus + " " + errorThrown);
        console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
      },
    });
  });

  $(".robodesk-change-status").click(function (e) {
    e.preventDefault();

    var button = $(this);
    var ticketId = button.data("ticket-id");
    var newStatus = button.data("new-status");

    console.log("Ticket ID:", ticketId);
    console.log("New Status:", newStatus);
    console.log("Nonce:", robodesk_ajax_object.nonce); // Check nonce
    console.log("Ajax URL:", robodesk_ajax_object.ajax_url);

    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_change_ticket_status",
        ticket_id: ticketId,
        new_status: newStatus,
        nonce: robodesk_ajax_object.nonce,
      },
      success: function (response) {
        if (response.success) {
          if (newStatus === "closed") {
            button.text("Reopen").data("new-status", "open");
          } else {
            button.text("Close").data("new-status", "closed");
          }
          button.closest("tr").find("td:eq(1)").text(newStatus);
        } else {
          alert("Error changing ticket status.");
          console.log(response); // Log the full response for debugging
        }
      },
      error: function (error) {
        alert("Error changing ticket status.");
        console.log(error);
      },
    });
  });

  // FAQ Voting
  $(".robodesk-faq-vote-button").click(function () {
    var faqId = $(this).data("faq-id");
    var voteType = $(this).data("vote-type");
    var button = $(this);

    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_faq_vote",
        faq_id: faqId,
        vote_type: voteType,
        nonce: robodesk_ajax_object.faq_nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#robodesk-faq-upvotes-" + faqId).text(response.data.upvotes);
          $("#robodesk-faq-downvotes-" + faqId).text(response.data.downvotes);
          button.addClass("robodesk-faq-voted");
        } else {
          alert("Error voting.");
          console.log(response);
        }
      },
      error: function (error) {
        alert("Error voting.");
        console.log(error);
      },
    });
  });

  // Saving Credentials (Modal)
  $("#robodesk-save-credential").click(function () {
    alert("scripts here");
    var url = $("#credential_url").val();
    var username = $("#credential_username").val();
    var password = $("#credential_password").val();
    var notes = $("#credential_notes").val();
    var nonce = robodesk_ajax_object.credentials_nonce;

    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_save_credential",
        ticket_id: robodesk_ajax_object.ticket_id,
        url: url,
        username: username,
        password: password,
        notes: notes,
        nonce: nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#robodesk-credentials-modal").hide();
          location.reload(); // Reload the page after saving
        } else {
          alert(response.data.message);
          console.log(response);
        }
      },
      error: function (error) {
        alert("Error saving credentials.");
        console.log(error);
      },
    });
  });

  // Credential Modal Management
  var modal = $("#robodesk-credentials-modal");
  var addBtn = $("#robodesk-add-credentials-btn");
  var showBtn = $("#robodesk-show-credentials-btn");
  var closeBtn = $("#robodesk-close-modal");
  var credentialsList = $("#robodesk-credentials-list");
  var credentialsForm = $("#robodesk-credentials-form");

  addBtn.click(function () {
    credentialsForm.show();
    credentialsList.hide();
    modal.show();
  });

  showBtn.click(function () {
    credentialsForm.hide();
    credentialsList.show();
    modal.show();
  });

  closeBtn.click(function () {
    modal.hide();
  });
  $(document).on("click", ".robodesk-delete-credentials", function (e) {
    e.preventDefault();
    var credentialId = $(this).data("credential-id");
    var nonce = robodesk_ajax_object.delete_credential_nonce;
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_delete_credential",
        credential_id: credentialId,
        nonce: nonce,
      },
      success: function (response) {
        if (response.success) {
          var row = $(".robodesk-delete-credentials[data-credential-id='" + credentialId + "']").closest("tr");
          row.remove();
        } else {
          alert(response.data.message);
          console.log(response);
        }
      },
      error: function (error) {
        alert("Error deleting credentials.");
        console.log(error);
      },
    });
  });
});

jQuery(document).ready(function ($) {
  $(document).on("click", ".robodesk-notification-ticket-link", function (e) {
    var link = $(this);
    var notificationId = parseInt(link.data("notification-id"), 10) || 0;
    var item = link.closest(".robodesk-notification-item");
    var href = link.attr("href");

    if (!notificationId || !item.hasClass("is-unread")) {
      return;
    }

    e.preventDefault();

    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_mark_user_notification_read",
        notification_id: notificationId,
        nonce: robodesk_ajax_object.notifications_nonce,
      },
      complete: function () {
        item.removeClass("is-unread").addClass("is-read");
        window.location.href = href;
      },
    });
  });

  $(document).on("click", "#robodesk-mark-all-notifications-read", function () {
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_mark_all_user_notifications_read",
        nonce: robodesk_ajax_object.notifications_nonce,
      },
      success: function (response) {
        if (response.success) {
          window.location.reload();
        } else {
          alert(response.data || "Could not mark notifications as read.");
        }
      },
      error: function () {
        alert("Could not mark notifications as read.");
      },
    });
  });
});

jQuery(document).ready(function ($) {
  // ... other code

  $("#robodesk-add-note").submit(function (e) {
    // Select the form by ID
    e.preventDefault();

    var formData = $(this).serialize();

    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.success) {
          $("#robodesk-add-note")[0].reset(); // Clear form on success
          location.reload(); // Reload the page to show the new note.
        } else {
          alert(response.data.message || "Error adding note."); // Display error message or default message
          console.error(response); // Log the full response for debugging
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        alert("An error occurred: " + textStatus + " " + errorThrown);
        console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
      },
    });
  });

  $("#note_attachment").on("change", function () {
    var files = $(this)[0].files;
    var fileList = $("#file-list");
    fileList.empty(); // Clear previous file list
    for (var i = 0; i < files.length; i++) {
      fileList.append("<div>" + files[i].name + "</div>");
    }
  });
});

jQuery(document).ready(function ($) {
  // ... other JavaScript code

  // Load Add Credential Form via AJAX
  $("#add-credential-button").click(function () {
    var existing = $("#credential-form");
    if (existing.length) {
      existing.slideUp(function () { $(this).remove(); });
      return;
    }
    var container = $("<div id='credential-form'></div>").insertAfter(this);
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_get_credential_form",
        nonce: robodesk_ajax_object.add_credential_nonce,
      },
      success: function (response) {
        if (response.success) {
          container.html(response.data.html).slideDown();
          bindAddCredentialForm();
        }
      },
    });
  });

  function bindAddCredentialForm() {
    $("#robodesk-add-credential")
      .off("submit")
      .on("submit", function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        $.ajax({
          url: robodesk_ajax_object.ajax_url,
          type: "POST",
          data: formData,
          success: function (response) {
            if (response.success) {
              $("#robodesk-add-credential")[0].reset();
              $("#credential-form").slideUp();
              var cred = response.data;
              var row = buildCredentialRow(cred);
              var wrapper = $(".credential-table-wrapper");
              if (wrapper.length) {
                wrapper.find("table tbody").append(row);
              } else {
                $(".robodesk-my-credentials").html(
                  '<div class="credential-table-wrapper"><table><thead><tr><th>Name</th><th>Username</th><th>Password</th><th>URL</th><th>Notes</th><th>Actions</th></tr></thead><tbody></tbody></table></div>'
                );
                $(".credential-table-wrapper table tbody").append(row);
              }
            } else {
              alert(response.data);
              console.error(response);
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            alert("An error occurred: " + textStatus + " " + errorThrown);
            console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
          },
        });
      });
  }

  bindAddCredentialForm();

  function buildCredentialRow(cred) {
    var urlHtml = cred.url
      ? '<span class="credential-cell-wrap"><a href="' + cred.url + '" target="_blank" rel="noopener noreferrer" class="credential-url-link" title="' + cred.url + '"><span class="dashicons dashicons-external" aria-hidden="true"></span></a><button type="button" class="copy-credential" data-copy-value="' + cred.url + '" title="' + cred.url + '"><span class="dashicons dashicons-admin-page"></span></button></span>'
      : '<span class="credential-url-text">&mdash;</span>';

    return '<tr class="robodesk-table-content">' +
      '<td><span class="credential-cell-wrap"><span class="credential-name-text">' + cred.name + '</span><button type="button" class="copy-credential" data-copy-value="' + cred.name + '" title="Copy Name"><span class="dashicons dashicons-admin-page"></span></button></span></td>' +
      '<td><span class="credential-cell-wrap"><span class="credential-username-text">' + cred.username + '</span><button type="button" class="copy-credential" data-copy-value="' + cred.username + '" title="Copy Username"><span class="dashicons dashicons-admin-page"></span></button></span></td>' +
      '<td><span class="credential-cell-wrap"><span class="password-hidden">********</span><button type="button" class="show-password" data-credential-id="' + cred.id + '" title="Show Password"><span class="dashicons dashicons-visibility"></span></button><button type="button" class="copy-password" data-credential-id="' + cred.id + '" title="Copy Password"><span class="dashicons dashicons-admin-page"></span></button></span></td>' +
      '<td>' + urlHtml + '</td>' +
      '<td>' + (cred.notes || '') + '</td>' +
      '<td><button class="delete-credential" data-credential-id="' + cred.id + '">Delete</button></td>' +
      '</tr>';
  }

  // Delete Credential AJAX
  $(document).on("click", ".delete-credential", function () {
    var credentialId = $(this).data("credential-id");
    var confirmDelete = confirm(
      "Are you sure you want to delete this credential?",
    );
    if (confirmDelete) {
      var row = $(this).closest("tr");
      $.ajax({
        url: robodesk_ajax_object.ajax_url,
        type: "POST",
        data: {
          action: "robodesk_delete_credential",
          credential_id: credentialId,
          nonce: robodesk_ajax_object.delete_credential_nonce,
        },
        success: function (response) {
          if (response.success) {
            row.remove();
            var tbody = $(".credential-table-wrapper table tbody");
            if (tbody.length && tbody.find("tr").length === 0) {
              $(".robodesk-my-credentials").html(
                "<p>No credentials saved yet.</p>"
              );
            }
          } else {
            alert(response.data);
            console.error(response);
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          alert("An error occurred: " + textStatus + " " + errorThrown);
          console.error("AJAX Error:", jqXHR, textStatus, errorThrown);
        },
      });
    }
  });

  // Copy to Clipboard Functionality
  $(document).on("click", ".copy-credential", function () {
    var value = $(this).data("copy-value");
    if (!value) {
      return;
    }
    copyToClipboard(value, $(this));
  });

  // Copy Password Functionality (AJAX-based)
  $(document).on("click", ".copy-password", function () {
    var button = $(this);
    var credentialId = button.data("credential-id");

    if (!credentialId) {
      return;
    }

    // If password is already revealed, copy directly from the span
    var passwordSpan = button.siblings(".password-hidden");
    if (passwordSpan.data("revealed")) {
      copyToClipboard(passwordSpan.text(), button);
      return;
    }

    // Fetch decrypted password from server and copy
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_reveal_credential_password",
        credential_id: credentialId,
        nonce: robodesk_ajax_object.reveal_credential_nonce,
      },
      beforeSend: function () {
        button.prop("disabled", true);
      },
      success: function (response) {
        if (response.success) {
          copyToClipboard(response.data.password, button);
        } else {
          alert(response.data || "Unable to copy password.");
        }
      },
      error: function () {
        alert("An error occurred while fetching the password.");
      },
      complete: function () {
        button.prop("disabled", false);
      },
    });
  });

  function copyToClipboard(text, button) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        showCopyFeedback(button);
      }).catch(function () {
        fallbackCopyToClipboard(text, button);
      });
    } else {
      fallbackCopyToClipboard(text, button);
    }
  }

  function fallbackCopyToClipboard(text, button) {
    var tempInput = $("<textarea>");
    $("body").append(tempInput);
    tempInput.val(text).trigger("select");
    document.execCommand("copy");
    tempInput.remove();
    showCopyFeedback(button);
  }

  function showCopyFeedback(button) {
    var originalIcon = button.find(".dashicons").attr("class");
    button.find(".dashicons").removeClass().addClass("dashicons dashicons-yes");
    button.addClass("is-copied");
    setTimeout(function () {
      button.find(".dashicons").removeClass().addClass(originalIcon);
      button.removeClass("is-copied");
    }, 1500);
  }

  // Show Password Functionality (AJAX-based decryption)
  $(document).on("click", ".show-password", function () {
    var button = $(this);
    var passwordSpan = button.siblings(".password-hidden");
    var icon = button.find(".dashicons");
    var credentialId = button.data("credential-id");

    if (!credentialId) {
      return;
    }

    // If already revealed, hide it
    if (passwordSpan.data("revealed")) {
      passwordSpan.text("********");
      passwordSpan.data("revealed", false);
      icon.removeClass("dashicons-hidden").addClass("dashicons-visibility");
      return;
    }

    // Fetch decrypted password from server
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "robodesk_reveal_credential_password",
        credential_id: credentialId,
        nonce: robodesk_ajax_object.reveal_credential_nonce,
      },
      beforeSend: function () {
        passwordSpan.text("Loading...");
        button.prop("disabled", true);
      },
      success: function (response) {
        if (response.success) {
          passwordSpan.text(response.data.password);
          passwordSpan.data("revealed", true);
          icon.removeClass("dashicons-visibility").addClass("dashicons-hidden");
        } else {
          passwordSpan.text("********");
          alert(response.data || "Unable to reveal password.");
        }
      },
      error: function () {
        passwordSpan.text("********");
        alert("An error occurred while fetching the password.");
      },
      complete: function () {
        button.prop("disabled", false);
      },
    });
  });

  $("body").append('<div class="spinner"></div>');
  $("#robodesk-create-ticket").submit(function (e) {
    e.preventDefault();

    $("#robodesk-response-message").html("");
    var formData = {
      action: "robodesk_create_new_ticket",
      ticket_title: $("#ticket_title").val(),
      ticket_content: $("#ticket_content").val(),
      ticket_priority: $("#ticket_priority").val(),
      robodesk_nonce: $("#_wpnonce").val(),
    };
    $.ajax({
      url: robodesk_ajax_object.ajax_url,
      type: "POST",
      data: formData,
      beforeSend: function () {
        $("#robodesk-create-ticket").addClass("blurred");
        $(".spinner").css("display", "block");
      },
      success: function (response) {
        if (response.success) {
          $("#robodesk-response-message").html(
            '<p style="color: green;">Ticket created successfully!</p>',
          );
          $("#robodesk-create-ticket")[0].reset();
          $("#robodesk-response-message").delay(5000).fadeOut(2000);
        } else {
          $("#robodesk-response-message")
            .html(
              '<p style="color: red;">Error: ' + response.data.message + "</p>",
            )
            .fadeOut(1000);
          $("#robodesk-response-message").delay(5000).fadeOut(2000);
        }
      },
      error: function () {
        $("#robodesk-response-message").html(
          '<p style="color: red;">An error occurred. Please try again.</p>',
        );
        $("#robodesk-response-message").delay(5000).fadeOut(2000);
      },

      complete: function () {
        $("#robodesk-create-ticket").removeClass("blurred");
        $(".spinner").css("display", "none");
      },
    });
  });
  // --- Profile: image preview ---
  var fileInput = document.getElementById('profile_picture');
  var previewImg = document.getElementById('profile-preview');
  if (fileInput && previewImg) {
    fileInput.addEventListener('change', function () {
      var file = this.files[0];
      if (previewImg.src && previewImg.src.indexOf('blob:') === 0) {
        URL.revokeObjectURL(previewImg.src);
      }
      if (file) {
        previewImg.src = URL.createObjectURL(file);
        previewImg.style.display = 'inline';
      } else {
        previewImg.src = '';
        previewImg.style.display = 'none';
      }
    });
  }

  // --- Profile: password validation ---
  var $pwField = $('#password');
  var $confirmField = $('#confirm_password');
  var $pwFeedback = $('#password-feedback');
  var $cfFeedback = $('#confirm-password-feedback');
  var $matchFeedback = $('#password-match-feedback');

  if ($pwField.length && $confirmField.length) {
    function getPwErrors(val) {
      var errors = [];
      if (val.length < 8) errors.push('min 8 characters');
      if (!/[A-Z]/.test(val)) errors.push('uppercase');
      if (!/[a-z]/.test(val)) errors.push('lowercase');
      if (!/[0-9]/.test(val)) errors.push('number');
      if (!/[^A-Za-z0-9]/.test(val)) errors.push('special char');
      return errors;
    }

    function validatePwFields() {
      var pw = $pwField.val();
      var cf = $confirmField.val();
      var pwErrors = getPwErrors(pw);
      var cfErrors = getPwErrors(cf);
      var match = pw === cf && pw.length > 0;

      if (pw.length === 0) {
        $pwFeedback.text('');
      } else if (pwErrors.length === 0) {
        $pwFeedback.text('\u2713').css('color', 'green');
      } else {
        $pwFeedback.text('\u2717 ' + pwErrors.join(', ')).css('color', 'red');
      }

      if (cf.length === 0) {
        $cfFeedback.text('');
      } else if (cfErrors.length === 0) {
        $cfFeedback.text('\u2713').css('color', 'green');
      } else {
        $cfFeedback.text('\u2717 ' + cfErrors.join(', ')).css('color', 'red');
      }

      if (pw.length > 0 || cf.length > 0) {
        var pwValid = pwErrors.length === 0;
        var cfValid = cfErrors.length === 0;
        if (pwValid && cfValid && match) {
          $matchFeedback.text('\u2713 Passwords match').css('color', 'green');
        } else if (pwValid && cfValid && !match) {
          $matchFeedback.text('\u2717 Passwords do not match').css('color', 'red');
        } else {
          $matchFeedback.text('');
        }
      } else {
        $matchFeedback.text('');
      }
    }

    $pwField.on('keyup', validatePwFields);
    $confirmField.on('keyup', validatePwFields);
  }

});
