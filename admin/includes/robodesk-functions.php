<?php
// Ajax handler for testing credential for IMAP connection
// This function will be called via AJAX to test the IMAP connection
add_action('wp_ajax_robodesk_imap_test_connection', 'robodesk_imap_test_connection');
function robodesk_imap_test_connection()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_imap_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (empty($_POST['robodesk_imap_hostname']) || empty($_POST['robodesk_imap_port']) || empty($_POST['robodesk_imap_username']) || empty($_POST['robodesk_imap_password'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    // Check IMAP extension
    if (!function_exists('imap_open')) {
        wp_send_json_error(['message' => 'IMAP extension is not enabled on the server']);
        exit;
    }

    $auth_type = ($_POST['robodesk_imap_auth'] == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field($_POST['robodesk_imap_hostname']) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';
    $username = sanitize_text_field($_POST['robodesk_imap_username']);
    $password = $_POST['robodesk_imap_password'];

    // Clear previous errors
    imap_errors();

    $mailbox = @imap_open($hostname, $username, $password);

    if ($mailbox) {
        $message = ' IMAP connection successful!';
        imap_close($mailbox);
        wp_send_json_success(['message' => $message]);
    } else {
        $error = imap_last_error();
        $message = ' IMAP connection failed: ' . esc_html($error);
        wp_send_json_error(['message' => $message]);
    }
}


//function to save the credentials(hostname, port, username, password, and auth type) for IMAP connection

add_action('wp_ajax_robodesk_imap_save_settings', 'robodesk_imap_save_settings');
function robodesk_imap_save_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_imap_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (empty($_POST['robodesk_imap_hostname']) || empty($_POST['robodesk_imap_port']) || empty($_POST['robodesk_imap_username']) || empty($_POST['robodesk_imap_password'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    // Check IMAP extension
    if (!function_exists('imap_open')) {
        wp_send_json_error(['message' => 'IMAP extension is not enabled on the server']);
        exit;
    }

    $auth_type = ($_POST['robodesk_imap_auth'] == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field($_POST['robodesk_imap_hostname']) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';
    $username = sanitize_text_field($_POST['robodesk_imap_username']);
    $password = $_POST['robodesk_imap_password'];

    // Clear previous errors
    imap_errors();

    $mailbox = @imap_open($hostname, $username, $password);

    if ($mailbox) {
        $message = 'IMAP connection settings saved successfully!';
        // Save settings to the database
        $settings = array(
            'hostname' => sanitize_text_field($_POST['robodesk_imap_hostname']),
            'port' => intval($_POST['robodesk_imap_port']),
            'username' => sanitize_email($_POST['robodesk_imap_username']),
            'password' => sanitize_text_field($_POST['robodesk_imap_password']),
            'auth' => sanitize_text_field($_POST['robodesk_imap_auth']),

        );

        update_option('robodesk_imap_settings', $settings); // Save the settings to the database
        imap_close($mailbox);
        wp_send_json_success(['message' => $message]);
    } else {
        $error = imap_last_error();
        $message = 'IMAP connection settings not saved successfully! ' . esc_html($error);
        wp_send_json_error(['message' => $message]);
    }
}

// email template save function (save new ticket, ticket closed, and auto-reply templates)
add_action('wp_ajax_robodesk_email_template_save', 'robodesk_email_template_save');

function robodesk_email_template_save()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_email_template_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Check if templates array is provided
    if (empty($_POST['templates']) || !is_array($_POST['templates'])) {
        wp_send_json_error(['message' => 'No templates provided']);
        exit;
    }

    // Define allowed HTML tags for template content
    $allowed_tags = [
        'strong' => [],
        'em' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'p' => [],
        'br' => [],
    ];

    $results = [];
    // Define expected template keys
    $valid_templates = ['new-ticket', 'ticket-closed', 'auto-reply'];

    foreach ($_POST['templates'] as $template_name => $template_content) {
        // Validate template name
        if (!in_array($template_name, $valid_templates)) {
            $results[$template_name] = ['status' => 'error', 'message' => 'Invalid template name'];
            continue;
        }

        // Sanitize inputs
        $template_name = sanitize_text_field($template_name);
        $template_content = wp_kses($template_content, $allowed_tags);

        if (empty($template_content)) {
            $results[$template_name] = ['status' => 'error', 'message' => 'Template content cannot be empty'];
            continue;
        }

        // Save to options table
        $option_name = 'robodesk_email_template_' . sanitize_title($template_name);
        $success = update_option($option_name, $template_content);

        $results[$template_name] = [
            'status' => $success || get_option($option_name) === $template_content ? 'success' : 'error',
            'message' => $success || get_option($option_name) === $template_content
                ? 'Template saved successfully'
                : 'Failed to save template',
        ];
    }

    // Check if all templates were processed successfully
    $all_success = array_reduce($results, function ($carry, $result) {
        return $carry && $result['status'] === 'success';
    }, true);

    if ($all_success && count($results) === count($valid_templates)) {
        wp_send_json_success(['message' => 'All templates saved successfully', 'details' => $results]);
    } else {
        wp_send_json_error(['message' => 'Some templates failed to save or were missing', 'details' => $results]);
    }
}

function robodesk_replace_template_tags($template, $ticket_id)
{
    $ticket = get_post($ticket_id);

    if (!$ticket || $ticket->post_type !== 'robodesk_ticket') {
        return $template;
    }

    $author_id = $ticket->post_author;
    $author = get_userdata($author_id);
    $author_name = $author ? $author->display_name : 'Customer';

    $ticket_link = get_permalink($ticket_id);
    $ticket_title = get_the_title($ticket_id);
    $ticket_status = get_post_status($ticket_id);

    // You can add more fields as needed
    $replacements = [
        '{ticket_id}'     => $ticket_id,
        '{ticket_author}' => esc_html($author_name),
        '{ticket_link}'   => esc_url($ticket_link),
        '{ticket_title}'  => esc_html($ticket_title),
        '{ticket_status}' => ucfirst(esc_html($ticket_status)),
    ];

    return strtr($template, $replacements);
}



// advance settings save function (save auto assign, notifications, customer portal, and max file upload size)
add_action('wp_ajax_robodesk_save_advanced_settings', 'robodesk_save_advanced_settings');

function robodesk_save_advanced_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_advanced_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Check if settings array is provided
    if (empty($_POST['settings']) || !is_array($_POST['settings'])) {
        wp_send_json_error(['message' => 'No settings provided']);
        exit;
    }

    $settings = $_POST['settings'];

    // Define expected settings keys
    $valid_settings = [
        'enable_auto_assign',
        'enable_notifications',
        'enable_customer_portal',
        'max_file_upload_size'
    ];

    $sanitized_settings = [];
    $results = [];

    foreach ($valid_settings as $key) {
        if (!isset($settings[$key])) {
            $results[$key] = ['status' => 'error', 'message' => 'Setting missing'];
            continue;
        }

        if ($key === 'max_file_upload_size') {
            // Sanitize and validate number input
            $value = intval($settings[$key]);
            if ($value < 1 || $value > 100) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid file upload size (must be 1–100 MB)'];
                continue;
            }
            $sanitized_settings[$key] = $value;
        } else {
            // Sanitize boolean inputs
            $sanitized_settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
        }

        $results[$key] = ['status' => 'success', 'message' => 'Setting saved successfully'];
    }

    // Save all settings as a single option
    $success = update_option('robodesk_advanced_settings', $sanitized_settings);

    // Check if save was successful
    if ($success || get_option('robodesk_advanced_settings') === $sanitized_settings) {
        wp_send_json_success(['message' => 'Advanced settings saved successfully', 'details' => $results]);
    } else {
        wp_send_json_error(['message' => 'Failed to save some settings', 'details' => $results]);
    }
}


//basic settings save function (save images and other basic settings including site name, admin email, timezone, notification and language)

add_action('wp_ajax_robodesk_save_basic_settings', 'robodesk_save_basic_settings');

function robodesk_save_basic_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_basic_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Check if settings array is provided
    if (empty($_POST['settings']) || !is_array($_POST['settings'])) {
        wp_send_json_error(['message' => 'No settings provided']);
        exit;
    }

    // Require WordPress media handling functions for image uploads
    if (!function_exists('media_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $settings = $_POST['settings'];
    $valid_settings = ['site_name', 'admin_email', 'timezone', 'language', 'theme_color'];
    $valid_timezones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'GMT'];
    $valid_languages = ['en', 'es', 'fr', 'de'];
    $image_fields = [
        'popover_icon' => 'robodesk_popover_icon',
        'popover_top_icon' => 'robodesk_popover_top_icon',
        'logo' => 'robodesk_logo',
        'wide_logo' => 'robodesk_wide_logo'
    ];

    $sanitized_settings = [];
    $results = [];
    $urls = [];

    // Process basic settings
    foreach ($valid_settings as $key) {
        if (!isset($settings[$key])) {
            $results[$key] = ['status' => 'error', 'message' => ucfirst(str_replace('_', ' ', $key)) . ' is missing'];
            continue;
        }

        if ($key === 'site_name') {
            $sanitized_settings[$key] = sanitize_text_field($settings[$key]);
            if (empty($sanitized_settings[$key])) {
                $results[$key] = ['status' => 'error', 'message' => 'Site name cannot be empty'];
                continue;
            }
        } elseif ($key === 'admin_email') {
            $sanitized_settings[$key] = sanitize_email($settings[$key]);
            if (!is_email($sanitized_settings[$key])) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid email address'];
                continue;
            }
        } elseif ($key === 'timezone') {
            $sanitized_settings[$key] = sanitize_text_field($settings[$key]);
            if (!in_array($sanitized_settings[$key], $valid_timezones)) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid timezone'];
                continue;
            }
        } elseif ($key === 'language') {
            $sanitized_settings[$key] = sanitize_text_field($settings[$key]);
            if (!in_array($sanitized_settings[$key], $valid_languages)) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid language'];
                continue;
            }
        } elseif ($key === 'theme_color') {
            $sanitized_settings[$key] = sanitize_hex_color($settings[$key]);
            if (is_null($sanitized_settings[$key])) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid color code'];
                continue;
            }
        }
        $results[$key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $key)) . ' saved successfully'];
    }

    // Process image uploads
    foreach ($image_fields as $file_key => $option_key) {
        $existing_url = isset($_POST[$option_key]) ? esc_url_raw($_POST[$option_key]) : get_option($option_key, '');
        $urls[$option_key] = $existing_url;

        if (!empty($_FILES[$file_key]['name'])) {
            $file = $_FILES[$file_key];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                $results[$file_key] = ['status' => 'error', 'message' => 'Invalid file type for ' . str_replace('_', ' ', $file_key) . '. Only JPEG, PNG, and GIF are allowed'];
                continue;
            }

            $advanced_settings = get_option('robodesk_advanced_settings', []);
            $max_size = $advanced_settings['max_file_upload_size'] ?? 10;
            $max_size_bytes = $max_size * 1024 * 1024;
            if ($file['size'] > $max_size_bytes) {
                $results[$file_key] = ['status' => 'error', 'message' => ucfirst(str_replace('_', ' ', $file_key)) . ' exceeds maximum file size of ' . $max_size . 'MB'];
                continue;
            }

            $attachment_id = media_handle_upload($file_key, 0);
            if (is_wp_error($attachment_id)) {
                $results[$file_key] = ['status' => 'error', 'message' => 'Failed to upload ' . str_replace('_', ' ', $file_key) . ': ' . $attachment_id->get_error_message()];
                continue;
            }

            $image_url = wp_get_attachment_url($attachment_id);
            if ($image_url) {
                $urls[$option_key] = $image_url;
                $results[$file_key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $file_key)) . ' uploaded successfully'];
            } else {
                $results[$file_key] = ['status' => 'error', 'message' => 'Failed to retrieve URL for ' . str_replace('_', ' ', $file_key)];
            }
        } else {
            $results[$file_key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $file_key)) . ' unchanged'];
        }
    }

    // Process notification sound from dropdown
    $notification_sound = isset($_POST['robodesk_notification_sound']) ? esc_url_raw($_POST['robodesk_notification_sound']) : '';
    $urls['robodesk_notification_sound'] = $notification_sound;
    if ($notification_sound) {
        $results['robodesk_notification_sound'] = ['status' => 'success', 'message' => 'Notification sound updated successfully'];
    } else {
        $results['robodesk_notification_sound'] = ['status' => 'success', 'message' => 'Notification sound unchanged'];
    }

    // Save all settings to the options table
    foreach ($sanitized_settings as $key => $value) {
        update_option('robodesk_' . $key, $value);
    }
    foreach ($urls as $option_key => $url) {
        update_option($option_key, $url);
    }

    // Check if all operations were successful
    $all_success = array_reduce($results, function ($carry, $result) {
        return $carry && $result['status'] === 'success';
    }, true);

    if ($all_success) {
        wp_send_json_success(['message' => 'Basic settings saved successfully', 'urls' => $urls, 'settings' => $sanitized_settings, 'details' => $results]);
    } else {
        wp_send_json_error(['message' => 'Some settings failed to save', 'urls' => $urls, 'settings' => $sanitized_settings, 'details' => $results]);
    }
}

// configure automatic ticket assignment and fetch timing

add_action('wp_ajax_robodesk_save_ticket_assignment_settings', 'robodesk_save_ticket_assignment_settings');
function robodesk_save_ticket_assignment_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_ticket_assignment_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (!isset($_POST['fetch_interval']) || !isset($_POST['default_agent'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    $fetch_interval = intval($_POST['fetch_interval']);
    $default_agent = sanitize_text_field($_POST['default_agent']);

    if ($fetch_interval < 1 || $fetch_interval > 1440) {
        wp_send_json_error(['message' => 'Fetch interval must be between 1 and 1440 minutes']);
        exit;
    }

    if (empty($default_agent)) {
        wp_send_json_error(['message' => 'Please select a team for auto-assignment']);
        exit;
    }

    // Save settings to the database
    update_option('robodesk_fetch_interval', $fetch_interval);
    update_option('robodesk_default_agent', $default_agent);

    wp_send_json_success(['message' => 'Ticket assignment settings saved successfully']);
}


// creating a ticket
add_action('wp_ajax_robodesk_create_ticket', 'robodesk_create_ticket');
function robodesk_create_ticket()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_create_ticket_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    // Validate inputs
    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
    $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
    $ticket_title = isset($_POST['ticket_title']) ? sanitize_text_field($_POST['ticket_title']) : '';
    $ticket_priority = isset($_POST['ticket_priority']) ? sanitize_text_field($_POST['ticket_priority']) : '';
    $ticket_category = isset($_POST['ticket_category']) ? sanitize_text_field($_POST['ticket_category']) : '';
    $ticket_assignee = isset($_POST['ticket_assignee']) ? sanitize_text_field($_POST['ticket_assignee']) : '';
    $ticket_description = isset($_POST['ticket_description']) ? sanitize_textarea_field($_POST['ticket_description']) : '';
    // $ticket_source = isset($_POST['ticket_source']) ? sanitize_text_field($_POST['ticket_source']) : 'Form';

    // Validate required fields
    if (
        empty($customer_name) || empty($customer_email) || empty($ticket_title) ||
        empty($ticket_priority) || empty($ticket_category) || empty($ticket_assignee) ||
        empty($ticket_description)
    ) {
        wp_send_json_error(['message' => 'All fields are required']);
        wp_die();
    }

    // Validate email format
    if (!is_email($customer_email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
        wp_die();
    }

    // Validate priority against taxonomy terms
    $valid_priorities = ['Low', 'Medium', 'High'];
    if (!in_array($ticket_priority, $valid_priorities)) {
        wp_send_json_error(['message' => 'Invalid priority selected']);
        wp_die();
    }

    // Find or create a user based on email
    $user = get_user_by('email', $customer_email);
    if (!$user) {
        error_log('User not found, creating new user with email: ' . $customer_email);

        // Check if role exists
        $roles = wp_roles();
        $role = isset($roles->roles['robodesk_support_customer']) ? 'robodesk_support_customer' : 'subscriber';

        // Ensure unique user_login
        $base_login = explode('@', $customer_email)[0];
        $user_login = $base_login;
        $counter = 1;
        while (username_exists($user_login)) {
            $user_login = $base_login . '_' . $counter++;
        }

        $user_id = wp_insert_user([
            'user_login'   => $user_login,
            'user_email'   => $customer_email,
            'user_pass'    => wp_generate_password(),
            'display_name' => $customer_name,
            'role'         => $role,
        ]);

        if (is_wp_error($user_id)) {
            error_log('Failed to create user: ' . $user_id->get_error_message());
            wp_send_json_error(['message' => 'Failed to create user: ' . $user_id->get_error_message()]);
            wp_die();
        }

        error_log('Created new user with ID: ' . $user_id);
        update_user_meta($user_id, 'nickname', $customer_name);
        wp_send_new_user_notifications($user_id, 'user');
    } else {
        $user_id = $user->ID;
        error_log('Using existing user with ID: ' . $user_id);
    }

    // Create the ticket
    $ticket_id = wp_insert_post([
        'post_title'   => $ticket_title,
        'post_content' => $ticket_description,
        'post_type'    => 'robodesk_ticket',
        'post_status'  => 'publish',
        'post_author'  => $user_id, // Set the user ID as the post author
        'meta_input'   => [
            '_robodesk_customer_email' => $customer_email,
            '_robodesk_ticket_status'  => 'open',

            'robodesk_default_agent'          => $ticket_assignee,
            'robodesk_ticket_origin'   => 'Admin-Form', // Default source
        ],
    ]);

    // adding the message of the ticket to comment of the ticket
    if ($ticket_id && !is_wp_error($ticket_id)) {
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $ticket_id,
            'comment_author'       => $customer_name,
            'comment_author_email' => $customer_email,
            'user_id'              => $user_id,
            'comment_content'      => $ticket_description,
            'comment_approved'     => 1,
            'comment_type'         => 'ticket_replies',
            'is_robodesk_reply'    => true, // Custom field to identify as a Robodesk reply
        ]);

        if ($comment_id === false || is_wp_error($comment_id)) {
            error_log('Failed to add comment to ticket: ' . (is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Unknown error'));
            wp_send_json_error(['message' => 'Failed to add comment to ticket']);
            wp_die();
        }
    }

    if (is_wp_error($ticket_id)) {
        error_log('Failed to create ticket: ' . $ticket_id->get_error_message());
        wp_send_json_error(['message' => 'Failed to create ticket: ' . $ticket_id->get_error_message()]);
        wp_die();
    }

    // Assign priority to robodesk_ticket_priority taxonomy
    $priority_term = term_exists($ticket_priority, 'robodesk_ticket_priority');
    if (!$priority_term) {
        $priority_term = wp_insert_term($ticket_priority, 'robodesk_ticket_priority');
        error_log('Created new priority term: ' . $ticket_priority);
    }
    if (!is_wp_error($priority_term)) {
        $priority_term_id = is_array($priority_term) ? $priority_term['term_id'] : $priority_term;
        $result = wp_set_post_terms($ticket_id, [$priority_term_id], 'robodesk_ticket_priority', false);
        if (is_wp_error($result)) {
            error_log('Failed to set priority term: ' . $result->get_error_message());
        }
    } else {
        error_log('Failed to create priority term: ' . $priority_term->get_error_message());
    }


    // Assign assignee to robodesk_ticket_assignee taxonomy
    wp_set_object_terms($ticket_id, intval($ticket_category), 'robodesk_ticket_category');



    wp_send_json_success(['message' => 'Ticket created successfully', 'ticket_id' => $ticket_id]);
    wp_die();
}


//for search of username and email in create ticket form
add_action('wp_ajax_robodesk_user_lookup', 'robodesk_user_lookup');
add_action('wp_ajax_nopriv_robodesk_user_lookup', 'robodesk_user_lookup');

function robodesk_user_lookup()
{
    $type = sanitize_text_field($_POST['type']);
    $value = sanitize_text_field($_POST['value']);

    $args = [
        'number' => 5,
        'fields' => ['display_name', 'user_email'],
        'search_columns' => [$type === 'email' ? 'user_email' : 'display_name'],
        'search' => '*' . esc_attr($value) . '*',

    ];

    $users = get_users($args);

    $results = array_map(function ($user) {
        return [
            'display_name' => $user->display_name,
            'user_email'   => $user->user_email,
        ];
    }, $users);

    wp_send_json($results);
}


// Ajax handler to update ticket status
// This function will be called via AJAX to update the status of a ticket
add_action('wp_ajax_update_ticket_status', 'robodesk_update_ticket_status');

function robodesk_update_ticket_status()
{
    check_ajax_referer('robodesk_ticket_status_action', 'security'); // ✅ must match your localized name

    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $new_status = sanitize_text_field($_POST['status'] ?? '');

    if (!$ticket_id || !$new_status) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    if (!current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    update_post_meta($ticket_id, '_robodesk_ticket_status', $new_status);
    $updated = current_time('mysql');

    wp_send_json_success([
        'status' => $new_status,
        'updated' => $updated
    ]);
}


// Ajax handler to update ticket priority
add_action('wp_ajax_update_ticket_priority', 'robodesk_update_ticket_priority');

function robodesk_update_ticket_priority()
{
    check_ajax_referer('robodesk_ticket_priority_action', 'security');

    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $new_priority = sanitize_text_field($_POST['priority'] ?? '');

    if (!$ticket_id || !$new_priority) {
        wp_send_json_error(['message' => 'Invalid input']);
    }

    if (!current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Assign the priority to custom taxonomy 'robodesk_ticket_priority'
    $term = term_exists($new_priority, 'robodesk_ticket_priority');
    if (!$term) {
        $term = wp_insert_term($new_priority, 'robodesk_ticket_priority');
    }

    if (is_wp_error($term)) {
        wp_send_json_error(['message' => 'Failed to assign priority']);
    }

    wp_set_post_terms($ticket_id, [(int)$term['term_id']], 'robodesk_ticket_priority');

    $updated = current_time('mysql');
    wp_send_json_success([
        'priority' => $new_priority,
        'updated'  => $updated,
    ]);
}


// Ajax handler to assign a ticket to an agent
add_action('wp_ajax_update_ticket_assignee', 'robodesk_update_ticket_assignee');

function robodesk_update_ticket_assignee()
{
    check_ajax_referer('robodesk_ticket_assignment_action', 'security');

    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $new_assignee = sanitize_text_field($_POST['assignee'] ?? '');

    if (!$ticket_id || !$new_assignee) {
        wp_send_json_error(['message' => 'Invalid input']);
    }

    if (!current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Update post meta
    update_post_meta($ticket_id, 'ticket_assignee', $new_assignee);
    $updated = current_time('mysql');

    wp_send_json_success([
        'assignee' => $new_assignee,
        'updated'  => $updated,
    ]);
}





// ADD REPLY TO TICKET
add_action('wp_ajax_add_ticket_reply', 'robodesk_handle_add_ticket_reply');

function robodesk_handle_add_ticket_reply()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_add_ticket_reply_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $reply = isset($_POST['reply']) ? wp_kses_post($_POST['reply']) : '';

    if (!$ticket_id || empty($reply)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);

    if (!$user_id || !$user_object) {
        wp_send_json_error(['message' => 'User not logged in']);
    }

    $comment_id = wp_insert_comment([
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user_object->display_name,
        'comment_author_email' => $user_object->user_email,
        'user_id'              => $user_id,
        'comment_content'      => $reply,
        'comment_approved'     => 1,
        'comment_type'         => 'ticket_replies',
        'is_robodesk_reply'   => true, // Custom field to identify as a Robodesk reply
    ]);

    if (is_wp_error($comment_id)) {
        wp_send_json_error(['message' => 'Failed to insert comment']);
    }

    wp_send_json_success([
        'comment_id' => $comment_id,
        'author'     => $user_object->display_name,
    ]);
}

// GET TICKET REPLIES
add_action('wp_ajax_get_ticket_replies', 'robodesk_handle_get_ticket_replies');

function robodesk_handle_get_ticket_replies()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_get_ticket_replies_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

    if (!$ticket_id) {
        wp_send_json_error(['message' => 'Invalid ticket ID']);
    }

    // Get ticket replies (comments)
    $comments = get_comments([
        'post_id' => $ticket_id,
        'order'   => 'ASC',
        'type'    => 'ticket_replies',
        'is_robodesk_reply'   => true,

    ]);

    $replies = [];
    foreach ($comments as $comment) {
        // Determine user type
        $user = get_userdata($comment->user_id);
        $type = 'customer';

        if ($user) {
            $roles = $user->roles;
            if (in_array('administrator', $roles) || in_array('robodesk_agent', $roles)) {
                $type = 'agent';
            }
        }

        $replies[] = [
            'id'        => $comment->comment_ID,
            'author'    => $comment->comment_author,
            'message'   => $comment->comment_content,
            'timestamp' => $comment->comment_date,
            'type'      => $type,
        ];
    }

    wp_send_json_success([
        'replies' => $replies,
        'count'   => count($replies),
    ]);
}











// ADD TICKET NOTE
add_action('wp_ajax_add_ticket_note', 'robodesk_handle_add_ticket_note');

function robodesk_handle_add_ticket_note()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_add_ticket_note_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $note = isset($_POST['note']) ? wp_kses_post($_POST['note']) : '';

    if (!$ticket_id || empty($note)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);

    if (!$user_id || !$user_object) {
        wp_send_json_error(['message' => 'User not logged in']);
    }

    $note_id = wp_insert_comment([
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user_object->display_name,
        'comment_author_email' => $user_object->user_email,
        'user_id'              => $user_id,
        'comment_content'      => $note,
        'comment_approved'     => 1,
        'comment_type'         => 'ticket_notes',
        'is_robodesk_note'     => true, // Custom field to identify as a Robodesk note
    ]);

    if (is_wp_error($note_id)) {
        wp_send_json_error(['message' => 'Failed to insert note']);
    }

    wp_send_json_success([
        'note_id' => $note_id,
        'author'  => $user_object->display_name,
    ]);
}

// GET TICKET NOTES
add_action('wp_ajax_get_ticket_notes', 'robodesk_handle_get_ticket_notes');

function robodesk_handle_get_ticket_notes()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_get_ticket_notes_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

    if (!$ticket_id) {
        wp_send_json_error(['message' => 'Invalid ticket ID']);
    }

    // Get ticket notes (comments)
    $comments = get_comments([
        'post_id' => $ticket_id,
        'order'   => 'ASC',
        'type'    => 'ticket_notes',
        'is_robodesk_note' => true,
    ]);

    $notes = [];
    foreach ($comments as $comment) {
        // Determine user type
        $user = get_userdata($comment->user_id);
        $type = 'agent'; // Notes are internal, so always agent
        if ($user) {
            $roles = $user->roles;
            if (in_array('administrator', $roles) || in_array('robodesk_agent', $roles)) {
                $type = 'agent';
            } else {
                $type = 'customer'; // If not an agent, consider it a customer note
            }
        }
        $notes[] = [
            'id'        => $comment->comment_ID,
            'author'    => $comment->comment_author,
            'message'   => $comment->comment_content,
            'timestamp' => $comment->comment_date,
            'type'      => $type,
        ];
    }

    wp_send_json_success([
        'notes' => $notes,
        'count' => count($notes),
    ]);
}






include_once 'robodesk-fetch-tickets.php'; // Include the file that contains the robodesk_fetch_emails function
// for fetching emails
add_action('wp_ajax_robodesk_fetch_emails', 'robodesk_fetch_emails_handler');
//display the email fetching result
function robodesk_fetch_emails_handler()
{
    check_ajax_referer('robodesk_fetch_emails_action', 'nonce');

    try {
        $summary = robodesk_fetch_emails(); // returns the string
        wp_send_json_success($summary); // ✅ send summary as success data
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }

    wp_die(); // ✅ important for AJAX
}

// cron job to fetch emails
