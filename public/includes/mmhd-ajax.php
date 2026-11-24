<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Change Ticket Status
add_action('wp_ajax_mmhd_change_ticket_status', 'mmhd_change_ticket_status_callback');
function mmhd_change_ticket_status_callback()
{
    check_ajax_referer('mmhd_change_ticket_status_nonce', 'nonce');

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';
    $old_status = $ticket_id > 0 ? (string) get_post_meta($ticket_id, '_mmhd_ticket_status', true) : '';

    if (!current_user_can('edit_post', $ticket_id) && !current_user_can('mmhd_access_dashboard')) {
        wp_send_json_error('Insufficient permissions');
    }

    $allowed_statuses = array('new', 'in-progress', 'archived', 'completed'); // Valid statuses
    if ($ticket_id > 0 && in_array($new_status, $allowed_statuses)) {
        $updated = update_post_meta($ticket_id, '_mmhd_ticket_status', $new_status);
        if ($updated !== false) {
            do_action('mmhd_after_ticket_status_change', $ticket_id, $old_status, $new_status, get_current_user_id());
            if ($old_status !== $new_status && function_exists('mmhd_notify_status_changed')) {
                mmhd_notify_status_changed($ticket_id, $new_status, get_current_user_id(), 'chat');
            }
            wp_send_json_success(
                function_exists('mmhd_get_dashboard_summary')
                    ? mmhd_get_dashboard_summary()
                    : []
            );
        } else {
            wp_send_json_error('Failed to update ticket status.');
        }
    } else {
        wp_send_json_error('Invalid ticket ID or status.');
    }
    wp_die();
}

// Create New Ticket
// add_action('wp_ajax_mmhd_create_new_ticket', 'mmhd_create_new_ticket_callback');
// function mmhd_create_new_ticket_callback()
// {
//     // check_ajax_referer('mmhd_create_ticket_nonce');

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
//         'post_type'     => 'mmhd_ticket',
//         'post_author' => $user_id
//     );

//     $post_id = wp_insert_post($post_data);

//     if (is_wp_error($post_id)) {
//         wp_send_json_error(array('message' => $post_id->get_error_message()));
//     } else {
//         update_post_meta($post_id, '_mmhd_ticket_status', 'open');
//         if ($priority > 0) {
//             wp_set_object_terms($post_id, $priority, 'mmhd_ticket_priority');
//         }else{
//            wp_set_object_terms($post_id, 31, 'mmhd_ticket_priority');
//         }

//         $origin =  create_term_if_not_exists();

//         wp_set_object_terms($post_id, (int) $origin['term_id'], 'mmhd_ticket_origin');

//         wp_send_json_success(array('message' => 'Ticket created successfully!'));
//     }
// }


function mmhd_create_term_if_not_exists($term_name = 'Web', $taxonomy = 'mmhd_ticket_origin')
{

    $term = term_exists($term_name, $taxonomy);


    if ($term === 0 || $term === null) {
        $term = wp_insert_term($term_name, $taxonomy);
    }

    return $term;
}



add_action('wp_ajax_mmhd_create_new_ticket', 'mmhd_handle_ticket_submission');

function mmhd_handle_ticket_submission()
{

    if (!isset($_POST['mmhd_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mmhd_nonce'])), 'mmhd_create_ticket_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    $user_id = get_current_user_id();
    $title = isset($_POST['ticket_title']) ? sanitize_text_field(wp_unslash($_POST['ticket_title'])) : '';
    $content = isset($_POST['ticket_content']) ? wp_kses_post(wp_unslash($_POST['ticket_content'])) : '';
    $priority = isset($_POST['ticket_priority']) ? intval($_POST['ticket_priority']) : 0;

    if (empty(trim($title)) || empty(trim($content))) {
        wp_send_json_error(array('message' => 'Fill in the required fields first'));
    }

    $priority_term = term_exists($priority, 'mmhd_ticket_priority');
    if ($priority > 0 && !$priority_term) {
        $priority = 0;
    }

    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'mmhd_ticket',
        'post_author'   => $user_id,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
    } else {
        update_post_meta($post_id, '_mmhd_ticket_status', 'open');
        if ($priority > 0) {
            wp_set_object_terms($post_id, $priority, 'mmhd_ticket_priority');
        } else {
            wp_set_object_terms($post_id, 31, 'mmhd_ticket_priority');
        }



        $origin =  mmhd_create_term_if_not_exists();

        $user_obj = get_user_by('id', $user_id);
        $user_name = $user_obj->display_name;
        $email_address = $user_obj->user_email;

        if ($email_address) {
            update_post_meta($post_id, '_mmhd_customer_email', $email_address);
        }


        $comment_id = wp_insert_comment(array(
            'comment_post_ID' => $post_id,
            'user_id'  => $user_id,
            'comment_content' => trim($title . "\n\n" . $content),
            'comment_author' => $user_name,
            'comment_author_email' => $email_address,
            'comment_approved' => 1,
            'comment_type' => 'ticket_replies'
        ));

        if (!is_wp_error($comment_id)) {
            add_comment_meta($comment_id, 'comment_source', 'chat', true);
            add_comment_meta($comment_id, 'author_type', mmhd_get_author_type($user_id), true);
            add_comment_meta($comment_id, 'mmhd_suppress_notification', 1, true);
        }

        update_post_meta($post_id, 'mmhd_all_seen', 'yes');

        wp_set_object_terms($post_id, (int) $origin['term_id'], 'mmhd_ticket_origin');

        // Auto-assign if enabled in Advanced Settings
        $settings = get_option('mmhd_advanced_settings', []);
        if (!empty($settings['enable_auto_assign'])) {
            $assigned = mmhd_auto_assign_ticket_to_support_staff(false, false, $post_id);
            if (!empty($assigned)) {
                update_post_meta($post_id, 'ticket_assignee', $assigned);
                mmhd_store_ticket_assignee_id($post_id, $assigned);
            }
        }

        mmhd_notify_ticket_created($post_id, 'chat');

        do_action('mmhd_after_ticket_created', $post_id, $post_data, $user_id);

        wp_send_json_success(array('message' => 'Ticket created successfully!'));
    }
}


// Add Ticket Note
add_action('wp_ajax_mmhd_add_ticket_note', 'mmhd_add_ticket_note_callback');
/*
function mmhd_add_ticket_note_callback()
{
    check_ajax_referer('mmhd_add_ticket_note_nonce');
    global $wpdb;
    $notes_table = $wpdb->prefix . 'mmhd_ticket_notes';
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
add_action('wp_ajax_mmhd_faq_vote', 'mmhd_faq_vote_callback');
function mmhd_faq_vote_callback()
{
    check_ajax_referer('mmhd_faq_vote_nonce');
    $faq_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
    $vote_type = isset($_POST['vote_type']) ? sanitize_text_field(wp_unslash($_POST['vote_type'])) : '';

    if ($faq_id <= 0 || !in_array($vote_type, array('up', 'down'))) {
        wp_send_json_error('Invalid FAQ ID or vote type.');
    }

    $user_id = get_current_user_id();
    $vote_key = '_mmhd_faq_voted_' . absint($faq_id);
    if ($user_id > 0) {
        $existing_vote = get_user_meta($user_id, $vote_key, true);
        if ($existing_vote !== '') {
            wp_send_json_error('You have already voted on this FAQ.');
        }
        update_user_meta($user_id, $vote_key, $vote_type);
    } else {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        $guest_key = 'mmhd_faq_vote_' . md5($ip . '|' . absint($faq_id));
        if (get_transient($guest_key)) {
            wp_send_json_error('You have already voted on this FAQ.');
        }
        set_transient($guest_key, 1, 15 * MINUTE_IN_SECONDS);
    }

    $upvotes = (int) get_post_meta($faq_id, '_mmhd_faq_upvotes', true);
    $downvotes = (int) get_post_meta($faq_id, '_mmhd_faq_downvotes', true);

    if ($vote_type === 'up') {
        update_post_meta($faq_id, '_mmhd_faq_upvotes', ++$upvotes);
    } elseif ($vote_type === 'down') {
        update_post_meta($faq_id, '_mmhd_faq_downvotes', ++$downvotes);
    }

    wp_send_json_success(array(
        'upvotes' => $upvotes,
        'downvotes' => $downvotes
    ));
}


add_action('wp_ajax_mmhd_get_credential_form', 'mmhd_get_credential_form_callback');
function mmhd_get_credential_form_callback()
{
    check_ajax_referer('mmhd_add_credential_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in.');
    }
    ob_start();
?>
    <h3>Add New Credential</h3>
    <form id="mmhd-add-credential">
        <label for="credential_name">Name:</label><br>
        <input type="text" id="credential_name" name="credential_name" required><br><br>

        <label for="credential_username">Username:</label><br>
        <input type="text" id="credential_username" name="credential_username" required><br><br>

        <label for="credential_password">Password:</label><br>
        <input type="password" id="credential_password" name="credential_password" required><br><br>

        <label for="credential_url">URL:</label><br>
        <input type="text" id="credential_url" name="credential_url"><br><br>

        <label for="credential_notes">Notes:</label><br>
        <textarea id="credential_notes" name="credential_notes"></textarea><br><br>

        <input type="hidden" name="action" value="mmhd_add_credential">
        <?php wp_nonce_field('mmhd_add_credential_nonce'); ?>
        <input type="submit" value="Add">
    </form>
<?php
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
    wp_die();
}

add_action('wp_ajax_mmhd_add_credential', 'mmhd_add_credential_callback');
function mmhd_add_credential_callback()
{
    check_ajax_referer('mmhd_add_credential_nonce');
    global $wpdb;
    $credentials_table = $wpdb->prefix . 'mmhd_credentials';
    $user_id = get_current_user_id();

    $name = isset($_POST['credential_name']) ? sanitize_text_field(wp_unslash($_POST['credential_name'])) : '';
    $username = isset($_POST['credential_username']) ? sanitize_text_field(wp_unslash($_POST['credential_username'])) : '';
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $password = isset($_POST['credential_password']) ? wp_unslash($_POST['credential_password']) : '';
    $url = isset($_POST['credential_url']) ? esc_url_raw(wp_unslash($_POST['credential_url'])) : '';
    $notes = isset($_POST['credential_notes']) ? sanitize_textarea_field(wp_unslash($_POST['credential_notes'])) : '';

    if (empty($name) || empty($username) || trim($password) === '') {
        wp_send_json_error('Please fill in all required fields.');
    }

    $encrypted_password = mmhd_encrypt($password);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert(
        $credentials_table,
        array(
            'user_id' => $user_id,
            'name' => $name,
            'username' => $username,
            'password' => $encrypted_password,
            'url' => $url,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($inserted !== false) {
        $new_id = $wpdb->insert_id;
        wp_send_json_success(array(
            'id' => $new_id,
            'name' => $name,
            'username' => $username,
            'url' => $url,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
        ));
    } else {
        wp_send_json_error('Error adding credential.');
    }
    wp_die();
}


add_action('wp_ajax_mmhd_delete_credential', 'mmhd_delete_credential_callback');
function mmhd_delete_credential_callback()
{
    check_ajax_referer('mmhd_delete_credential_nonce', 'nonce');
    global $wpdb;
    $credentials_table = $wpdb->prefix . 'mmhd_credentials';
    $credential_id = isset($_POST['credential_id']) ? intval($_POST['credential_id']) : 0;
    $user_id = get_current_user_id();

    if ($credential_id <= 0) {
        wp_send_json_error('Invalid Credential ID');
        wp_die();
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $credential_user = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$credentials_table} WHERE id = %d", $credential_id));
    if ((int) $credential_user !== (int) $user_id) {
        wp_send_json_error('You do not have permission to delete this credential.');
        wp_die();
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->delete(
        $credentials_table,
        array('id' => $credential_id, 'user_id' => $user_id),
        array('%d', '%d')
    );

    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Error deleting credential.');
    }

    wp_die();
}

add_action('wp_ajax_mmhd_reveal_credential_password', 'mmhd_reveal_credential_password_callback');
function mmhd_reveal_credential_password_callback()
{
    check_ajax_referer('mmhd_reveal_credential_nonce', 'nonce');

    $credential_id = isset($_POST['credential_id']) ? intval($_POST['credential_id']) : 0;
    $user_id = get_current_user_id();

    if ($credential_id <= 0 || !$user_id) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $credential = $wpdb->get_row($wpdb->prepare(
        "SELECT password FROM {$wpdb->prefix}mmhd_credentials WHERE id = %d AND user_id = %d",
        $credential_id,
        $user_id
    ));

    if (!$credential) {
        wp_send_json_error('Credential not found or access denied.');
    }

    $decrypted_password = mmhd_decrypt($credential->password);
    if ($decrypted_password === false) {
        wp_send_json_error('Unable to decrypt password.');
    }

    wp_send_json_success(array(
        'password' => $decrypted_password,
    ));
}

add_action('wp_ajax_mmhd_mark_user_notification_read', 'mmhd_mark_user_notification_read');
function mmhd_mark_user_notification_read()
{
    check_ajax_referer('mmhd_notifications_action', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    if ($notification_id <= 0) {
        wp_send_json_error('Invalid notification ID.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_notifications';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->delete(
        $table_name,
        array(
            'id' => $notification_id,
            'recipient_user_id' => $user_id,
        ),
        array('%d', '%d')
    );

    if ($deleted === false) {
        wp_send_json_error('Could not delete notification.');
    }

    if ($deleted < 1) {
        wp_send_json_error('Notification not found.');
    }

    wp_send_json_success();
}

add_action('wp_ajax_mmhd_mark_all_user_notifications_read', 'mmhd_mark_all_user_notifications_read');
function mmhd_mark_all_user_notifications_read()
{
    check_ajax_referer('mmhd_notifications_action', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_notifications';

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE recipient_user_id = %d",
            $user_id
        )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

    if ($deleted === false) {
        wp_send_json_error('Could not delete notifications.');
    }

    wp_send_json_success(array('deleted_count' => (int) $deleted));
}



function mmhd_add_ticket_note_callback()
{
    check_ajax_referer('mmhd_add_ticket_note_nonce');
    global $wpdb;
    $notes_table = $wpdb->prefix . 'mmhd_ticket_notes';
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $user_id = get_current_user_id();
    $is_private = isset($_POST['is_private']) ? intval($_POST['is_private']) : 0;
    $type = 'user_note';

    if ($ticket_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid ticket ID.'));
    }

    if (!current_user_can('edit_post', $ticket_id) && !current_user_can('mmhd_access_dashboard')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    $uploaded_files = array();
    if (!empty($_FILES['note_content_ifr'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'pdf', 'docx', 'xlsx', 'rar');
        $mime_map = wp_get_mime_types();
        $mimes = array();
        foreach ($allowed_types as $ext) {
            foreach ($mime_map as $pattern => $mime) {
                if (preg_match('/(^|[\s|])' . preg_quote($ext, '/') . '[\s|]|\$/i', $pattern)) {
                    $mimes[$pattern] = $mime;
                }
            }
        }
        $upload_overrides = array('test_form' => false, 'mimes' => $mimes);
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
    $note_content = isset($_POST['note_content']) ? wp_kses_post(wp_unslash($_POST['note_content'])) : '';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
                add_post_meta($file_id, '_mmhd_ticket_note_id', $wpdb->insert_id);
            }
        }
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Error adding note.'));
    }
}


/*
add_action('wp_ajax_mmhd_login_action', 'mmhd_login_action_callback');
add_action('wp_ajax_nopriv_mmhd_login_action', 'mmhd_login_action_callback');
function mmhd_login_action_callback()
{

    check_ajax_referer('mmhd-nonce', 'security');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'message' => __('Invalid email format.', 'metamint-helpdesk')
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
            'message' => __('Account created! Check your email for your password.', 'metamint-helpdesk'),
            'showPassword' => false
        ]);
    }
    wp_send_json_error([
        'message' => sprintf(
            //translators: %s: error message returned from wp_insert_user 
            __('Failed to create account: %s', 'metamint-helpdesk'),
            $user_id->get_error_message()
        )
    ]);
}
*/


add_action('wp_ajax_mmhd_check_email', 'mmhd_check_email');
add_action('wp_ajax_nopriv_mmhd_check_email', 'mmhd_check_email');
function mmhd_check_email()
{
    check_ajax_referer('mmhd-nonce', 'security');

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    $rate_key = 'mmhd_email_check_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    if ($attempts >= 10) {
        wp_send_json_error(['message' => 'Too many attempts. Please try again later.']);
        wp_die();
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'exists' => false,
            'message' => __('Invalid email format.', 'metamint-helpdesk')
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
add_action('wp_ajax_mmhd_register_user', 'mmhd_register_user');
add_action('wp_ajax_nopriv_mmhd_register_user', 'mmhd_register_user');
function mmhd_register_user()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mmhd_register_user_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (get_option('users_can_register') != '1') {
        wp_send_json_error(['message' => 'Registration is currently disabled.']);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    $rate_key = 'mmhd_register_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    if ($attempts >= 5) {
        wp_send_json_error(['message' => 'Too many registration attempts. Please try again later.']);
        wp_die();
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (empty($email) || ! is_email($email)) {
        wp_send_json_error([
            'message' => __('Invalid email .', 'metamint-helpdesk')
        ]);
    }

    $password = wp_generate_password(12, true);
    $username = sanitize_user(strstr($email, '@', true), true);
    $user_id = wp_create_user($username, $password, $email);

    if (!is_wp_error($user_id)) {
        if (!mmhd_are_emails_suppressed()) {
            // The $password param was deprecated in WP 4.3.1; pass only the user ID.
            wp_new_user_notification($user_id);
        }

        wp_send_json_success([
            'message' => __('Account created! Check your email for your password.', 'metamint-helpdesk'),
            'userId' => $user_id,
            'loggedIn' => false
        ]);
    }
    wp_send_json_error([
        'message' => sprintf(
            /* translators: %s: error message returned from wp_insert_user */
            __('Failed to create account: %s', 'metamint-helpdesk'),
            $user_id->get_error_message()
        )
    ]);
}


// Handle Ajax request
function mmhd_handle_change_password()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    $rate_key = 'mmhd_reset_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    if ($attempts >= 5) {
        wp_send_json_error(['message' => 'Too many password reset attempts. Please try again later.']);
        wp_die();
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    check_ajax_referer('password-reset', 'nonce');
    $password  = isset($_POST['password']) ? sanitize_text_field(wp_unslash($_POST['password'])) : '';
    $reset_key = isset($_POST['reset_key']) ? sanitize_text_field(wp_unslash($_POST['reset_key'])) : '';
    $user_login = isset($_POST['user']) ? sanitize_text_field(wp_unslash($_POST['user'])) : '';

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
add_action('wp_ajax_mmhd_change_password', 'mmhd_handle_change_password');
add_action('wp_ajax_nopriv_mmhd_change_password', 'mmhd_handle_change_password'); // if not logged in