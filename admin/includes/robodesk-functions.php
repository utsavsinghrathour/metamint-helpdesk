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


//function to save the credentials

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

// email template save function
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
    $valid_templates = ['new-ticket', 'ticket-resolved', 'auto-reply'];

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


// advance settings save function
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
