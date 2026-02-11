<?php

// Change Ticket Status
add_action('wp_ajax_robodesk_change_ticket_status', 'robodesk_change_ticket_status_callback');
function robodesk_change_ticket_status_callback()
{
    check_ajax_referer('robodesk_change_ticket_status_nonce', 'nonce');

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

    $allowed_statuses = array('new', 'in-progress', 'archived', 'completed'); // Valid statuses
    if ($ticket_id > 0 && in_array($new_status, $allowed_statuses)) {
        $updated = update_post_meta($ticket_id, '_robodesk_ticket_status', $new_status);
        if ($updated !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update ticket status.');
        }
    } else {
        wp_send_json_error('Invalid ticket ID or status.');
    }
    wp_die();
}

// Create New Ticket
// add_action('wp_ajax_robodesk_create_new_ticket', 'robodesk_create_new_ticket_callback');
// function robodesk_create_new_ticket_callback()
// {
//     // check_ajax_referer('robodesk_create_ticket_nonce');

//     $title = isset($_POST['ticket_title']) ? sanitize_text_field($_POST['ticket_title']) : '';
//     $content = isset($_POST['ticket_content']) ? wp_kses_post($_POST['ticket_content']) : '';
//     $priority = isset($_POST['ticket_priority']) ? (int)$_POST['ticket_priority'] : 'medium';
//     $user_id = get_current_user_id();

//     if (empty($title) || empty($content)) {
//         wp_send_json_error(array('message' => 'Please fill in all required fields.'));
//     }

//     $post_data = array(
//         'post_title'    => $title,
//         'post_content'  => $content,
//         'post_status'   => 'publish',
//         'post_type'     => 'robodesk_ticket',
//         'post_author' => $user_id
//     );

//     $post_id = wp_insert_post($post_data);

//     if (is_wp_error($post_id)) {
//         wp_send_json_error(array('message' => $post_id->get_error_message()));
//     } else {
//         update_post_meta($post_id, '_robodesk_ticket_status', 'open');
//         if ($priority > 0) {
//             wp_set_object_terms($post_id, $priority, 'robodesk_ticket_priority');
//         }else{
//            wp_set_object_terms($post_id, 31, 'robodesk_ticket_priority');
//         }

//         $origin =  create_term_if_not_exists();

//         wp_set_object_terms($post_id, (int) $origin['term_id'], 'robodesk_ticket_origin');

//         wp_send_json_success(array('message' => 'Ticket created successfully!'));
//     }
// }


function create_term_if_not_exists($term_name = 'Web', $taxonomy = 'robodesk_ticket_origin')
{

    $term = term_exists($term_name, $taxonomy);


    if ($term === 0 || $term === null) {
        $term = wp_insert_term($term_name, $taxonomy);
    }

    return $term;
}



add_action('wp_ajax_robodesk_create_new_ticket', 'handle_robodesk_ticket_submission');
add_action('wp_ajax_nopriv_robodesk_create_new_ticket', 'handle_robodesk_ticket_submission');

function handle_robodesk_ticket_submission()
{

    if (!isset($_POST['robodesk_nonce']) || !wp_verify_nonce($_POST['robodesk_nonce'], 'robodesk_create_ticket_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    $user_id = get_current_user_id();
    $title = sanitize_text_field($_POST['ticket_title']);
    $content = wp_kses_post($_POST['ticket_content']);
    $priority = intval($_POST['ticket_priority']);

    if (empty(trim($title)) || empty(trim($content))) {
        wp_send_json_error(array('message' => 'Fill in the required fields first'));
    }

    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'robodesk_ticket',
        'post_author'   => $user_id,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
    } else {
        update_post_meta($post_id, '_robodesk_ticket_status', 'open');
        if ($priority > 0) {
            wp_set_object_terms($post_id, $priority, 'robodesk_ticket_priority');
        } else {
            wp_set_object_terms($post_id, 31, 'robodesk_ticket_priority');
        }



        $origin =  create_term_if_not_exists();

        $user_obj = get_user_by('id', $user_id);
        $user_name = $user_obj->display_name;
        $email_address = $user_obj->user_email;


        $comment_id = wp_insert_comment(array(
            'comment_post_ID' => $post_id,
            'user_id'  => $user_id,
            'comment_content' => $title,
            'comment_author' => $user_name,
            'comment_author_email' => $email_address,
            'comment_approved' => 1,
            'comment_type' => 'ticket_replies'
        ));

        if (!is_wp_error($comment_id)) {
            add_comment_meta($comment_id, 'comment_source', 'chat', true);
            add_comment_meta($comment_id, 'author_type', robodesk_get_author_type($user_id), true);
            add_comment_meta($comment_id, 'rbd_suppress_notification', 1, true);
        }

        update_post_meta($post_id, 'rbd_all_seen', 'yes');

        wp_set_object_terms($post_id, (int) $origin['term_id'], 'robodesk_ticket_origin');

        robodesk_notify_ticket_created($post_id, 'chat');

        wp_send_json_success(array('message' => 'Ticket created successfully!'));
    }
}


// Add Ticket Note
add_action('wp_ajax_robodesk_add_ticket_note', 'robodesk_add_ticket_note_callback');
/*
function robodesk_add_ticket_note_callback()
{
    check_ajax_referer('robodesk_add_ticket_note_nonce');
    global $wpdb;
    $notes_table = $wpdb->prefix . 'robodesk_ticket_notes';
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $note_content = isset($_POST['note_content']) ? wp_kses_post($_POST['note_content']) : '';
    $user_id = get_current_user_id();
    $is_private = isset($_POST['is_private']) ? intval($_POST['is_private']) : 0;
    $type = 'user_note';

    if ($ticket_id <= 0 || empty($note_content)) {
        wp_send_json_error(array('message' => 'Invalid ticket ID or empty note.'));
    }

    $inserted = $wpdb->insert(
        $notes_table,
        array(
            'ticket_id' => $ticket_id,
            'user_id' => $user_id,
            'note_content' => $note_content,
            'created_at' => current_time('mysql'),
            'is_private' => $is_private,
            'type' => $type,
            'status' => 'unread'
        ),
        array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
    );

    if ($inserted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Error adding note.'));
    }
}
*/

//FAQ Voting
add_action('wp_ajax_robodesk_faq_vote', 'robodesk_faq_vote_callback');
function robodesk_faq_vote_callback()
{
    check_ajax_referer('robodesk_faq_vote_nonce');
    $faq_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
    $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';

    if ($faq_id <= 0 || !in_array($vote_type, array('up', 'down'))) {
        wp_send_json_error('Invalid FAQ ID or vote type.');
    }

    $upvotes = (int) get_post_meta($faq_id, '_robodesk_faq_upvotes', true);
    $downvotes = (int) get_post_meta($faq_id, '_robodesk_faq_downvotes', true);

    if ($vote_type === 'up') {
        update_post_meta($faq_id, '_robodesk_faq_upvotes', ++$upvotes);
    } elseif ($vote_type === 'down') {
        update_post_meta($faq_id, '_robodesk_faq_downvotes', ++$downvotes);
    }

    wp_send_json_success(array(
        'upvotes' => $upvotes,
        'downvotes' => $downvotes
    ));
}


add_action('wp_ajax_robodesk_add_credential', 'robodesk_add_credential_callback');
function robodesk_add_credential_callback()
{
    // echo 'asdfasdfasdfasdf';
    // echo '<script>alert("asdfasdfasdf" );</script>';
    check_ajax_referer('robodesk_add_credential_nonce');
    global $wpdb;
    $credentials_table = $wpdb->prefix . 'robodesk_credentials';
    $user_id = get_current_user_id();

    $name = isset($_POST['credential_name']) ? sanitize_text_field($_POST['credential_name']) : '';
    $username = isset($_POST['credential_username']) ? sanitize_text_field($_POST['credential_username']) : '';
    $password = isset($_POST['credential_password']) ? sanitize_text_field($_POST['credential_password']) : '';
    $url = isset($_POST['credential_url']) ? esc_url_raw($_POST['credential_url']) : '';
    $notes = isset($_POST['credential_notes']) ? sanitize_textarea_field($_POST['credential_notes']) : '';

    if (empty($name) || empty($username) || empty($password)) {
        wp_send_json_error('Please fill in all required fields.');
    }

    $inserted = $wpdb->insert(
        $credentials_table,
        array(
            'user_id' => $user_id,
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'url' => $url,
            'notes' => $notes,
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s')
    );

    if ($inserted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Error adding credential.');
    }
    wp_die();
}


add_action('wp_ajax_robodesk_delete_credential', 'robodesk_delete_credential_callback');
function robodesk_delete_credential_callback()
{
    check_ajax_referer('robodesk_delete_credential_nonce');
    global $wpdb;
    $credentials_table = $wpdb->prefix . 'robodesk_credentials';
    $credential_id = isset($_POST['credential_id']) ? intval($_POST['credential_id']) : 0;
    $user_id = get_current_user_id();

    if ($credential_id <= 0) {
        wp_send_json_error('Invalid Credential ID');
        wp_die(); // Important: Always call wp_die() in AJAX callbacks
    }

    // Security check: Make sure the user owns the credential
    $credential_user = $wpdb->get_var("SELECT user_id FROM $credentials_table WHERE id = $credential_id");
    if ($credential_user != $user_id) {
        wp_send_json_error('You do not have permission to delete this credential.');
        wp_die(); // Important: Always call wp_die() in AJAX callbacks
    }

    $deleted = $wpdb->delete(
        $credentials_table,
        array('id' => $credential_id, 'user_id' => $user_id), // added user id for security
        array('%d', '%d')
    );

    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Error deleting credential.');
    }
    wp_die(); // Important: Always call wp_die() in AJAX callbacks
}



function robodesk_add_ticket_note_callback()
{
    check_ajax_referer('robodesk_add_ticket_note_nonce');
    global $wpdb;
    $notes_table = $wpdb->prefix . 'robodesk_ticket_notes';
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $user_id = get_current_user_id();
    $is_private = isset($_POST['is_private']) ? intval($_POST['is_private']) : 0;
    $type = 'user_note';

    if ($ticket_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid ticket ID.'));
    }

    $uploaded_files = array();
    if (!empty($_FILES['note_content_ifr'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'zip', 'pdf', 'docx', 'xlsx', 'rar');
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($_FILES['note_content_ifr'], $upload_overrides);

        if ($movefile && ! isset($movefile['error'])) {
            $attachment = array(
                'post_mime_type' => $movefile['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            $uploaded_files[] = $attach_id;
        } else {
            wp_send_json_error(array('message' => $movefile['error']));
            wp_die();
        }
    }
    $note_content = isset($_POST['note_content']) ? wp_kses_post($_POST['note_content']) : '';
    $inserted = $wpdb->insert(
        $notes_table,
        array(
            'ticket_id' => $ticket_id,
            'user_id' => $user_id,
            'note_content' => $note_content,
            'created_at' => current_time('mysql'),
            'is_private' => $is_private,
            'type' => $type,
            'status' => 'unread'
        ),
        array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
    );

    if ($inserted !== false) {
        if (!empty($uploaded_files)) {
            foreach ($uploaded_files as $file_id) {
                add_post_meta($file_id, '_robodesk_ticket_note_id', $wpdb->insert_id);
            }
        }
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Error adding note.'));
    }
}


add_action('wp_ajax_robodesk_login_action', 'robodesk_login_action_callback');
add_action('wp_ajax_nopriv_robodesk_login_action', 'robodesk_login_action_callback');
function robodesk_login_action_callback()
{

    check_ajax_referer('robodesk-nonce', 'security');

    $email = sanitize_email($_POST['email']);
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'message' => __('Invalid email format.', 'robodesk')
        ]);
    }

    $user_exists = email_exists($email);
    if ($user_exists) {
        wp_send_json_success([
            'exists' => true,
            'showPassword' => true
        ]);
    }

    $password = wp_generate_password(12, true);
    $username = sanitize_user(strstr($email, '@', true), true);
    //this is not checking if the user exists
    $user_id = wp_create_user($username, $password, $email);

    if (!is_wp_error($user_id)) {

        wp_send_json_success([
            'message' => __('Account created! Check your email for your password.', 'robodesk'),
            'showPassword' => false
        ]);
    }
    wp_send_json_error([
        'message' => sprintf(
            __('Failed to create account: %s', 'robodesk'),
            $user_id->get_error_message()
        )
    ]);
}


add_action('wp_ajax_robodesk_check_email', 'robodesk_check_email');
add_action('wp_ajax_nopriv_robodesk_check_email', 'robodesk_check_email');
function robodesk_check_email()
{
    $email = sanitize_email($_POST['email']);
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'exists' => false,
            'message' => __('Invalid email format.', 'robodesk')
        ]);
    }
    $user_exists = email_exists($email);
    if ($user_exists) {
        wp_send_json_success([
            'exists' => true,
        ]);
    }

    wp_send_json_error([
        'exists' => false,
    ]);
}


//For registering user
add_action('wp_ajax_robodesk_register_user', 'robodesk_register_user');
add_action('wp_ajax_nopriv_robodesk_register_user', 'robodesk_register_user');
function robodesk_register_user()
{
    $email = sanitize_email($_POST['email']);
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'message' => __('Invalid email .', 'robodesk')
        ]);
    }

    $password = wp_generate_password(12, true);
    $username = sanitize_user(strstr($email, '@', true), true);
    $user_id = wp_create_user($username, $password, $email);

    if (!is_wp_error($user_id)) {

        wp_send_json_success([
            'message' => __('Account created! Check your email for your password.', 'robodesk'),
            'userId' => $user_id,
            'loggedIn' => true
        ]);
    }
    wp_send_json_error([
        'message' => sprintf(
            __('Failed to create account: %s', 'robodesk'),
            $user_id->get_error_message()
        )
    ]);
}


// Handle Ajax request
function handle_change_password()
{

    check_ajax_referer('password-reset', 'nonce');
    $password  = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
    $reset_key = isset($_POST['reset_key']) ? sanitize_text_field($_POST['reset_key']) : '';
    $user_login = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';

    if (empty($password) || empty($reset_key) || empty($user_login)) {
        wp_send_json_error(['message' => 'Missing required fields.']);
    }

    $user = get_user_by('login', $user_login);
    if (!$user && is_email($user_login)) {
        $user = get_user_by('email', $user_login);
    }

    if (!$user) {
        wp_send_json_error(['message' => 'Invalid user.']);
    }


    $check = check_password_reset_key($reset_key, $user->user_login);
    if (is_wp_error($check)) {
        wp_send_json_error(['message' => 'Invalid or expired reset key.']);
    }


    reset_password($user, $password);

    wp_send_json_success(['message' => 'Password changed successfully.']);


    wp_send_json_success('Password changed successfully');

    wp_die();
}
add_action('wp_ajax_change_password', 'handle_change_password');
add_action('wp_ajax_nopriv_change_password', 'handle_change_password'); // if not logged in