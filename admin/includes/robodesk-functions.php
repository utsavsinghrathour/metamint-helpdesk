<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('robodesk_get_debug_log_path')) {
    function robodesk_get_debug_log_path()
    {
        return ROBODESK_PLUGIN_PATH . 'logs/debug.log';
    }
}

if (!function_exists('robodesk_read_debug_log_tail')) {
    function robodesk_read_debug_log_tail($lines = 80, $max_bytes = 524288)
    {
        $log_path = robodesk_get_debug_log_path();

        if (!file_exists($log_path) || !is_readable($log_path)) {
            return [];
        }

        $size = filesize($log_path);
        if ($size === false || $size <= 0) {
            return [];
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $offset = max(0, $size - (int) $max_bytes);
        $full_content = $wp_filesystem->get_contents($log_path);
        if ($full_content === false) {
            return [];
        }
        $content = $offset > 0 ? substr($full_content, $offset) : $full_content;

        if ($content === false || $content === '') {
            return [];
        }

        if ($offset > 0) {
            $first_newline = strpos($content, "\n");
            if ($first_newline !== false) {
                $content = substr($content, $first_newline + 1);
            }
        }

        $rows = preg_split('/\r\n|\r|\n/', $content);
        if (!is_array($rows)) {
            return [];
        }

        $rows = array_values(array_filter($rows, static function ($line) {
            return trim($line) !== '';
        }));

        return array_slice($rows, -1 * max(1, (int) $lines));
    }
}

if (!function_exists('robodesk_read_debug_log_recent')) {
    function robodesk_read_debug_log_recent($days = 30, $max_bytes = 2097152)
    {
        $log_path = robodesk_get_debug_log_path();

        if (!file_exists($log_path) || !is_readable($log_path)) {
            return [];
        }

        $size = filesize($log_path);
        if ($size === false || $size <= 0) {
            return [];
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $offset = max(0, $size - (int) $max_bytes);
        $full_content = $wp_filesystem->get_contents($log_path);
        if ($full_content === false) {
            return [];
        }

        $content = $offset > 0 ? substr($full_content, $offset) : $full_content;
        if ($content === false || $content === '') {
            return [];
        }

        if ($offset > 0) {
            $first_newline = strpos($content, "\n");
            if ($first_newline !== false) {
                $content = substr($content, $first_newline + 1);
            }
        }

        $rows = preg_split('/\r\n|\r|\n/', $content);
        if (!is_array($rows)) {
            return [];
        }

        $cutoff = strtotime('-' . max(1, (int) $days) . ' days');
        if ($cutoff === false) {
            $cutoff = time() - (max(1, (int) $days) * DAY_IN_SECONDS);
        }

        $recent_rows = [];
        $current_entry = [];
        $current_timestamp = null;

        foreach ($rows as $row) {
            $row = rtrim($row);
            if ($row === '') {
                continue;
            }

            if (preg_match('/^\[(.*?)\]/', $row, $matches)) {
                if (!empty($current_entry) && $current_timestamp !== null && $current_timestamp >= $cutoff) {
                    $recent_rows = array_merge($recent_rows, $current_entry);
                }

                $current_timestamp = strtotime($matches[1]);
                $current_entry = [$row];
                continue;
            }

            if (!empty($current_entry)) {
                $current_entry[] = $row;
            }
        }

        if (!empty($current_entry) && $current_timestamp !== null && $current_timestamp >= $cutoff) {
            $recent_rows = array_merge($recent_rows, $current_entry);
        }

        return $recent_rows;
    }
}

if (!function_exists('robodesk_prune_debug_log_on_admin_load')) {
    function robodesk_prune_debug_log_on_admin_load()
    {
        if (function_exists('robodesk_prune_debug_log')) {
            robodesk_prune_debug_log();
        }
    }
}

add_action('admin_init', 'robodesk_prune_debug_log_on_admin_load');

if (!function_exists('robodesk_download_debug_log')) {
    function robodesk_download_debug_log()
    {
        if (!current_user_can('robodesk_access_dashboard')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this file.', 'robodesk'));
        }

        check_admin_referer('robodesk_download_debug_log', 'robodesk_debug_log_nonce');

        $log_path = robodesk_get_debug_log_path();
        if (!file_exists($log_path) || !is_readable($log_path)) {
            wp_die(esc_html__('Debug log file is not available.', 'robodesk'));
        }

        $debug_log_lines = robodesk_read_debug_log_recent(30);
        $download_content = implode("\n", $debug_log_lines);
        if ($download_content !== '') {
            $download_content .= "\n";
        }

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="robodesk-debug-30-days.log"');
        header('Content-Length: ' . strlen($download_content));
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw log file download, output sent as text/plain attachment
        echo $download_content;
        exit;
    }
}
add_action('admin_post_robodesk_download_debug_log', 'robodesk_download_debug_log');

if (!function_exists('robodesk_debug_log_page_callback')) {
    function robodesk_debug_log_page_callback()
    {
        if (!current_user_can('robodesk_access_dashboard')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'robodesk'));
        }

        if (function_exists('robodesk_prune_debug_log')) {
            robodesk_prune_debug_log();
        }

        $debug_log_path = robodesk_get_debug_log_path();
        $debug_log_exists = file_exists($debug_log_path) && is_readable($debug_log_path);
        $debug_log_lines = $debug_log_exists ? robodesk_read_debug_log_recent(30) : [];
        $debug_log_size = $debug_log_exists ? (int) filesize($debug_log_path) : 0;
        $debug_log_modified = $debug_log_exists ? (int) filemtime($debug_log_path) : 0;
        $debug_log_download_url = wp_nonce_url(
            admin_url('admin-post.php?action=robodesk_download_debug_log'),
            'robodesk_download_debug_log',
            'robodesk_debug_log_nonce'
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Robodesk Debug Log', 'robodesk') . '</h1>';
        echo '<p>' . esc_html__('This view shows only the last 30 days of logs. Anything older is automatically deleted.', 'robodesk') . '</p>';

        if ($debug_log_exists) {
            echo '<p>';
            printf(
                /* translators: 1: file size, 2: last modified date */
                esc_html__('Size: %1$s | Last updated: %2$s', 'robodesk'),
                esc_html(size_format($debug_log_size)),
                esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $debug_log_modified))
            );
            echo '</p>';

            echo '<p><a href="' . esc_url($debug_log_download_url) . '" class="button button-primary">' . esc_html__('Download Last 30 Days', 'robodesk') . '</a></p>';

            echo '<textarea readonly style="width:100%;min-height:520px;font-family:monospace;line-height:1.4;">'
                . esc_textarea(implode("\n", $debug_log_lines)) .
                '</textarea>';
        } else {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('Debug log file was not found or is not readable at logs/debug.log.', 'robodesk') .
                '</p></div>';
        }

        echo '</div>';
    }
}

// Ajax handler for testing credential for IMAP connection
// This function will be called via AJAX to test the IMAP connection
add_action('wp_ajax_robodesk_imap_test_connection', 'robodesk_imap_test_connection');
function robodesk_imap_test_connection()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_imap_settings_action')) {
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

    $auth_type = (isset($_POST['robodesk_imap_auth']) && sanitize_text_field(wp_unslash($_POST['robodesk_imap_auth'])) == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field(wp_unslash($_POST['robodesk_imap_hostname'])) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';
    $username = sanitize_text_field(wp_unslash($_POST['robodesk_imap_username']));
    $password = sanitize_text_field(wp_unslash($_POST['robodesk_imap_password']));

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
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_imap_settings_action')) {
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

    $auth_type = (isset($_POST['robodesk_imap_auth']) && sanitize_text_field(wp_unslash($_POST['robodesk_imap_auth'])) == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field(wp_unslash($_POST['robodesk_imap_hostname'])) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';
    $username = sanitize_text_field(wp_unslash($_POST['robodesk_imap_username']));
    $password = sanitize_text_field(wp_unslash($_POST['robodesk_imap_password']));

    // Clear previous errors
    imap_errors();

    $mailbox = @imap_open($hostname, $username, $password);

    if ($mailbox) {
        $message = 'IMAP connection settings saved successfully!';
        // Save settings to the database
        $settings = array(
            'hostname' => sanitize_text_field(wp_unslash($_POST['robodesk_imap_hostname'])),
            'port' => intval($_POST['robodesk_imap_port']),
            'username' => sanitize_email(wp_unslash($_POST['robodesk_imap_username'])),
            'password' => sanitize_text_field(wp_unslash($_POST['robodesk_imap_password'])),
            'auth' => isset($_POST['robodesk_imap_auth']) ? sanitize_text_field(wp_unslash($_POST['robodesk_imap_auth'])) : '',

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

function robodesk_get_smtp_settings_from_request()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $enabled = isset($_POST['robodesk_smtp_enabled']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_enabled'])) : '0';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $encryption = isset($_POST['robodesk_smtp_encryption']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_encryption'])) : 'tls';
    $allowed_encryption = ['none', 'ssl', 'tls'];

    if (!in_array($encryption, $allowed_encryption, true)) {
        $encryption = 'tls';
    }

    return [
        'enabled' => $enabled === '1' || $enabled === 'true',
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'host' => isset($_POST['robodesk_smtp_host']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_host'])) : '',
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'port' => isset($_POST['robodesk_smtp_port']) ? absint(wp_unslash($_POST['robodesk_smtp_port'])) : 587,
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'username' => isset($_POST['robodesk_smtp_username']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_username'])) : '',
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'password' => isset($_POST['robodesk_smtp_password']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_password'])) : '',
        'encryption' => $encryption,
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'from_email' => isset($_POST['robodesk_smtp_from_email']) ? sanitize_email(wp_unslash($_POST['robodesk_smtp_from_email'])) : '',
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        'from_name' => isset($_POST['robodesk_smtp_from_name']) ? sanitize_text_field(wp_unslash($_POST['robodesk_smtp_from_name'])) : '',
    ];
}

function robodesk_validate_smtp_settings(array $settings)
{
    if (empty($settings['enabled'])) {
        return true;
    }

    if (empty($settings['host'])) {
        return 'SMTP host is required';
    }

    if (empty($settings['port']) || $settings['port'] < 1 || $settings['port'] > 65535) {
        return 'SMTP port must be between 1 and 65535';
    }

    if (empty($settings['from_email']) || !is_email($settings['from_email'])) {
        return 'Valid From Email is required';
    }

    return true;
}

function robodesk_configure_phpmailer_for_smtp($phpmailer, array $settings)
{
    $phpmailer->isSMTP();
    $phpmailer->Host = $settings['host'];
    $phpmailer->Port = (int) $settings['port'];
    $phpmailer->SMTPAuth = !empty($settings['username']) && !empty($settings['password']);

    if ($phpmailer->SMTPAuth) {
        $phpmailer->Username = $settings['username'];
        $phpmailer->Password = $settings['password'];
    }

    $phpmailer->SMTPSecure = $settings['encryption'] === 'none' ? '' : $settings['encryption'];

    if (!empty($settings['from_email']) && is_email($settings['from_email'])) {
        $phpmailer->setFrom($settings['from_email'], $settings['from_name'] ?: get_bloginfo('name'), false);
    }
}

add_action('wp_ajax_robodesk_smtp_save_settings', 'robodesk_smtp_save_settings');
function robodesk_smtp_save_settings()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_smtp_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    $settings = robodesk_get_smtp_settings_from_request();
    $validation = robodesk_validate_smtp_settings($settings);

    if ($validation !== true) {
        wp_send_json_error(['message' => $validation]);
        exit;
    }

    update_option('robodesk_smtp_settings', $settings);
    wp_send_json_success(['message' => 'SMTP settings saved successfully']);
}

add_action('wp_ajax_robodesk_smtp_test_connection', 'robodesk_smtp_test_connection');
function robodesk_smtp_test_connection()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_smtp_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    $settings = robodesk_get_smtp_settings_from_request();
    $validation = robodesk_validate_smtp_settings($settings);

    if ($validation !== true) {
        wp_send_json_error(['message' => $validation]);
        exit;
    }

    if (empty($settings['enabled'])) {
        wp_send_json_error(['message' => 'Enable SMTP first to test connection']);
        exit;
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
    }

    try {
        $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);
        robodesk_configure_phpmailer_for_smtp($phpmailer, $settings);

        if ($phpmailer->smtpConnect()) {
            $phpmailer->smtpClose();
            wp_send_json_success(['message' => 'SMTP connection successful']);
            exit;
        }

        wp_send_json_error(['message' => 'SMTP connection failed']);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'SMTP connection failed: ' . $e->getMessage()]);
    }
}

add_action('phpmailer_init', 'robodesk_apply_smtp_to_phpmailer');
function robodesk_apply_smtp_to_phpmailer($phpmailer)
{
    $settings = get_option('robodesk_smtp_settings', []);
    if (empty($settings) || empty($settings['enabled'])) {
        return;
    }

    $validation = robodesk_validate_smtp_settings($settings);
    if ($validation !== true) {
        return;
    }

    robodesk_configure_phpmailer_for_smtp($phpmailer, $settings);
}

// email template save function (save new ticket, ticket closed, and auto-reply templates)
add_action('wp_ajax_robodesk_email_template_save', 'robodesk_email_template_save');

function robodesk_email_template_save()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_email_template_action')) {
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

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-key inside the loop below
    $raw_templates = wp_unslash($_POST['templates']);
    foreach ($raw_templates as $template_name => $template_content) {
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
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_advanced_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Check if settings array is provided
    if (empty($_POST['settings']) || !is_array($_POST['settings'])) {
        wp_send_json_error(['message' => 'No settings provided']);
        exit;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-key inside the loop below
    $settings = wp_unslash($_POST['settings']);

    // Define expected settings keys
    $valid_settings = [
        'enable_auto_assign',
        'enable_notifications',
        'enable_customer_portal',
        'max_file_upload_size',
        'reply_unseen_email_minutes',
        'polling_interval',
        'blocked_ips',
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
        } elseif ($key === 'reply_unseen_email_minutes') {
            $value = absint($settings[$key]);
            if ($value < 0 || $value > 1440) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid reply email minutes (must be 0–1440)'];
                continue;
            }
            $sanitized_settings[$key] = $value;
        } elseif ($key === 'polling_interval') {
            $value = absint($settings[$key]);
            $allowed_intervals = [15, 30, 45, 60, 120];
            if (!in_array($value, $allowed_intervals)) {
                $value = 30;
            }
            $sanitized_settings[$key] = $value;
        } elseif ($key === 'blocked_ips') {
            $raw = sanitize_textarea_field($settings[$key]);
            $lines = explode("\n", $raw);
            $valid_ips = array();
            foreach ($lines as $line) {
                $ip = trim($line);
                if ($ip === '') {
                    continue;
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $valid_ips[] = $ip;
                }
            }
            //testing 
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_robodesk_signup_rate_') . '%',
                    $wpdb->esc_like('_transient_robodesk_signup_strike_') . '%'
                )
            );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_robodesk_email_check_rate_') . '%',
                    $wpdb->esc_like('_transient_robodesk_reset_rate_') . '%'
                )
            );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_timeout_robodesk_signup_rate_') . '%',
                    $wpdb->esc_like('_transient_timeout_robodesk_signup_strike_') . '%'
                )
            );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_timeout_robodesk_email_check_rate_') . '%',
                    $wpdb->esc_like('_transient_timeout_robodesk_reset_rate_') . '%'
                )
            );
            //testing
            $sanitized_settings[$key] = $raw;
            update_option('robodesk_blocked_ips', $valid_ips, false);
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
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_basic_settings_action')) {
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

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-key in loop below
    $settings = wp_unslash($_POST['settings']);
    $valid_settings = ['site_name', 'admin_email', 'theme_color', 'default_ticket_category', 'chat_heading', 'welcome_message', 'ticket_intro_message'];
    $image_fields = [
        'popover_icon' => 'robodesk_popover_icon',
        'popover_top_icon' => 'robodesk_popover_top_icon',
        'logo' => 'robodesk_logo',
        'wide_logo' => 'robodesk_wide_logo'
    ];

    $sanitized_settings = [];
    $results = [];
    $urls = [];
    $uploaded_attachments = [];

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
        } elseif ($key === 'theme_color') {
            $sanitized_settings[$key] = sanitize_hex_color($settings[$key]);
            if (is_null($sanitized_settings[$key])) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid color code'];
                continue;
            }
        } elseif ($key === 'default_ticket_category') {
            $sanitized_settings[$key] = absint($settings[$key]);
            if (!empty($sanitized_settings[$key]) && !term_exists($sanitized_settings[$key], 'robodesk_ticket_category')) {
                $results[$key] = ['status' => 'error', 'message' => 'Invalid ticket category'];
                continue;
            }
        } elseif ($key === 'chat_heading') {
            $sanitized_settings[$key] = sanitize_text_field($settings[$key]);
        } elseif ($key === 'ticket_intro_message') {
            $sanitized_settings[$key] = sanitize_textarea_field($settings[$key]);
        } elseif ($key === 'welcome_message') {
            $sanitized_settings[$key] = sanitize_textarea_field($settings[$key]);
        }
        $results[$key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $key)) . ' saved successfully'];
    }

    // Process image uploads
    foreach ($image_fields as $file_key => $option_key) {
        $existing_url = isset($_POST[$option_key]) ? esc_url_raw(wp_unslash($_POST[$option_key])) : get_option($option_key, '');
        $urls[$option_key] = $existing_url;

        if (!empty($_FILES[$file_key]['name'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $file = $_FILES[$file_key];
            $allowed_types = array(
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
            );
            $filetype = wp_check_filetype(basename(sanitize_file_name($file['name'])), $allowed_types);
            if (!$filetype['type']) {
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
                $uploaded_attachments[$file_key] = absint($attachment_id);
                $results[$file_key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $file_key)) . ' uploaded successfully'];
            } else {
                $results[$file_key] = ['status' => 'error', 'message' => 'Failed to retrieve URL for ' . str_replace('_', ' ', $file_key)];
            }
        } else {
            $results[$file_key] = ['status' => 'success', 'message' => ucfirst(str_replace('_', ' ', $file_key)) . ' unchanged'];
        }
    }

    // Process notification sound from dropdown
    $notification_sound = isset($_POST['robodesk_notification_sound']) ? esc_url_raw(wp_unslash($_POST['robodesk_notification_sound'])) : '';
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
        wp_send_json_success(['message' => 'Basic settings saved successfully', 'urls' => $urls, 'settings' => $sanitized_settings, 'details' => $results, 'uploaded_attachments' => $uploaded_attachments]);
    } else {
        wp_send_json_error(['message' => 'Some settings failed to save', 'urls' => $urls, 'settings' => $sanitized_settings, 'details' => $results, 'uploaded_attachments' => $uploaded_attachments]);
    }
}

add_action('wp_ajax_robodesk_save_cropped_logo', 'robodesk_save_cropped_logo');
function robodesk_save_cropped_logo()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_basic_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    $option_key = isset($_POST['option_key']) ? sanitize_key($_POST['option_key']) : '';
    $cropped_url = isset($_POST['cropped_url']) ? esc_url_raw(wp_unslash($_POST['cropped_url'])) : '';

    if (!in_array($option_key, ['robodesk_logo', 'robodesk_wide_logo'], true)) {
        wp_send_json_error(['message' => 'Invalid option key']);
        exit;
    }

    if ($cropped_url === '') {
        wp_send_json_error(['message' => 'Cropped URL is required']);
        exit;
    }

    update_option($option_key, $cropped_url);
    wp_send_json_success(['message' => 'Cropped image saved successfully', 'option_key' => $option_key, 'url' => $cropped_url]);
}


// configure automatic ticket assignment and fetch timing

add_filter('cron_schedules', 'robodesk_add_fetch_cron_interval');
function robodesk_add_fetch_cron_interval($schedules)
{
    $interval = get_option('robodesk_fetch_interval', 15);
    $schedules['robodesk_fetch_interval'] = [
        'interval' => $interval * 60,
        // translators: %d is the number of minutes between fetch intervals.
        'display'  => sprintf(esc_html__('Every %d minutes (Robodesk fetch)', 'robodesk'), $interval),
    ];
    return $schedules;
}

add_action('robodesk_email_fetch_cron', 'robodesk_fetch_emails');

add_action('wp_ajax_robodesk_save_ticket_assignment_settings', 'robodesk_save_ticket_assignment_settings');
function robodesk_save_ticket_assignment_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_ticket_assignment_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (!isset($_POST['fetch_interval'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    $fetch_interval = intval($_POST['fetch_interval']);
    $default_agent = isset($_POST['default_agent']) ? sanitize_text_field(wp_unslash($_POST['default_agent'])) : '';

    if ($fetch_interval < 1 || $fetch_interval > 1440) {
        wp_send_json_error(['message' => 'Fetch interval must be between 1 and 1440 minutes']);
        exit;
    }

    // Save settings to the database
    update_option('robodesk_fetch_interval', $fetch_interval);
    update_option('robodesk_default_agent', $default_agent);

    // Reschedule the cron event with the new interval
    if (wp_next_scheduled('robodesk_email_fetch_cron')) {
        wp_clear_scheduled_hook('robodesk_email_fetch_cron');
    }
    wp_schedule_event(time(), 'robodesk_fetch_interval', 'robodesk_email_fetch_cron');

    wp_send_json_success(['message' => 'Ticket assignment settings saved successfully']);
}


// creating a ticket
add_action('wp_ajax_robodesk_create_ticket', 'robodesk_create_ticket');
function robodesk_create_ticket()
{
    // Verify nonce (accept legacy and refactored keys/action names for compatibility)
    $nonce = '';
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    } elseif (isset($_POST['robodesk_create_ticket_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['robodesk_create_ticket_nonce']));
    }

    $valid_nonce = false;
    if ($nonce !== '') {
        $valid_nonce = wp_verify_nonce($nonce, 'robodesk_create_ticket_action') ||
            wp_verify_nonce($nonce, 'rbodesk_create_ticket_action');
    }

    if (!$valid_nonce) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    // Validate inputs
    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $customer_email = isset($_POST['customer_email']) ? sanitize_email(wp_unslash($_POST['customer_email'])) : '';
    $ticket_title = isset($_POST['ticket_title']) ? sanitize_text_field(wp_unslash($_POST['ticket_title'])) : '';
    $ticket_priority = isset($_POST['ticket_priority']) ? sanitize_text_field(wp_unslash($_POST['ticket_priority'])) : '';
    $ticket_category = isset($_POST['ticket_category']) ? sanitize_text_field(wp_unslash($_POST['ticket_category'])) : '';
    $ticket_assignee = isset($_POST['ticket_assignee']) ? sanitize_text_field(wp_unslash($_POST['ticket_assignee'])) : '';
    $ticket_description = isset($_POST['ticket_description']) ? sanitize_textarea_field(wp_unslash($_POST['ticket_description'])) : '';
    // $ticket_source = isset($_POST['ticket_source']) ? sanitize_text_field($_POST['ticket_source']) : 'Form';

    // Validate required fields
    if (
        empty($customer_name) || empty($customer_email) || empty($ticket_title) ||
        empty($ticket_priority) || empty($ticket_assignee) ||
        empty($ticket_description)
    ) {
        wp_send_json_error(['message' => 'All required fields must be filled']);
        wp_die();
    }

    // Validate email format
    if (!is_email($customer_email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
        wp_die();
    }

    // Validate priority against taxonomy terms
    //  $valid_priorities = ['Low', 'Medium', 'High'];
    $ticket_priorities = get_terms([
        'taxonomy' => 'robodesk_ticket_priority',
        'hide_empty' => false,
    ]);

    if (is_wp_error($ticket_priorities)) {
        wp_send_json_error(['message' => 'Failed to retrieve ticket priorities']);
        wp_die();
    }

    $valid_priorities = array_map(function ($priority) {
        return $priority->name;
    }, $ticket_priorities);

    if (!in_array($ticket_priority, $valid_priorities)) {
        wp_send_json_error(['message' => 'Invalid priority selected']);
        wp_die();
    }

    // Find or create a user based on email
    $user = get_user_by('email', $customer_email);
    if (!$user) {
        rbd_log('User not found, creating new user with email: ' . $customer_email);

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
            rbd_log('Failed to create user: ' . $user_id->get_error_message());
            wp_send_json_error(['message' => 'Failed to create user: ' . $user_id->get_error_message()]);
            wp_die();
        }

        rbd_log('Created new user with ID: ' . $user_id);
        update_user_meta($user_id, 'nickname', $customer_name);
        wp_send_new_user_notifications($user_id, 'user');
    } else {
        $user_id = $user->ID;
        rbd_log('Using existing user with ID: ' . $user_id);
    }

    // Normalize assignee for display name + ID
    $assignee_user = null;
    if (is_numeric($ticket_assignee)) {
        $assignee_user = get_user_by('id', (int) $ticket_assignee);
    } elseif (!empty($ticket_assignee)) {
        $assignee_user = get_user_by('display_name', $ticket_assignee);
        if (!$assignee_user) {
            $assignee_user = get_user_by('login', $ticket_assignee);
        }
    }

    $assignee_display = $assignee_user ? $assignee_user->display_name : $ticket_assignee;

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

            'ticket_assignee'          => $assignee_display,
            'robodesk_ticket_origin'   => 'Admin-Form', // Default source
        ],
    ]);

    // adding the message of the ticket to comment of the ticket
    if ($ticket_id && !is_wp_error($ticket_id)) {
        if ($assignee_user && $assignee_user->ID) {
            update_post_meta($ticket_id, 'ticket_assignee_id', (int) $assignee_user->ID);
        } else {
            robodesk_store_ticket_assignee_id($ticket_id, $assignee_display);
        }

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
            rbd_log('Failed to add comment to ticket: ' . (is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Unknown error'));
            wp_send_json_error(['message' => 'Failed to add comment to ticket']);
            wp_die();
        } else {
            add_comment_meta($comment_id, 'comment_source', 'admin', true);
            add_comment_meta($comment_id, 'author_type', 'agent', true);
            add_comment_meta($comment_id, 'rbd_suppress_notification', 1, true);
        }
    }

    if (is_wp_error($ticket_id)) {
        rbd_log('Failed to create ticket: ' . $ticket_id->get_error_message());
        wp_send_json_error(['message' => 'Failed to create ticket: ' . $ticket_id->get_error_message()]);
        wp_die();
    }

    // Assign priority to robodesk_ticket_priority taxonomy
    $priority_term = term_exists($ticket_priority, 'robodesk_ticket_priority');
    if (!$priority_term) {
        $priority_term = wp_insert_term($ticket_priority, 'robodesk_ticket_priority');
        rbd_log('Created new priority term: ' . $ticket_priority);
    }
    if (!is_wp_error($priority_term)) {
        $priority_term_id = is_array($priority_term) ? $priority_term['term_id'] : $priority_term;
        $result = wp_set_post_terms($ticket_id, [$priority_term_id], 'robodesk_ticket_priority', false);
        if (is_wp_error($result)) {
            rbd_log('Failed to set priority term: ' . $result->get_error_message());
        }
    } else {
        rbd_log('Failed to create priority term: ' . $priority_term->get_error_message());
    }


    // Assign category to robodesk_ticket_category taxonomy (only if category is provided)
    if (!empty($ticket_category)) {
        wp_set_object_terms($ticket_id, intval($ticket_category), 'robodesk_ticket_category');
    }



    if (!is_wp_error($ticket_id)) {
        robodesk_notify_ticket_created($ticket_id, 'admin');
    }

    wp_send_json_success(['message' => 'Conversation created successfully', 'ticket_id' => $ticket_id]);
    wp_die();
}

add_action('wp_ajax_get_ticket_detail', 'robodesk_get_ticket_detail');
function robodesk_get_ticket_detail()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_get_ticket_detail_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
    if (!$ticket_id) {
        wp_send_json_error(['message' => 'Invalid ticket id']);
    }

    $post = get_post($ticket_id);
    if (!$post || $post->post_type !== 'robodesk_ticket') {
        wp_send_json_error(['message' => 'Ticket not found']);
    }

    if (!current_user_can('read_post', $ticket_id) && !current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $author = get_userdata((int) $post->post_author);
    $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'N/A';

    $ticket_origin = function_exists('robodesk_get_ticket_origin_label')
        ? robodesk_get_ticket_origin_label($ticket_id)
        : (
            get_post_meta($ticket_id, 'robodesk_ticket_origin', true)
            ?: 'Web'
        );

    $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
    $ticket_priority = $priority_terms && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'N/A';

    $ticket_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_category');
    $ticket_category = '';
    if (!is_wp_error($ticket_terms) && !empty($ticket_terms)) {
        $ticket_category = [
            'name' => $ticket_terms[0]->name,
            'id' => $ticket_terms[0]->term_id,
            'term_id' => $ticket_terms[0]->term_id,
        ];
    }

    $ticket_source_meta = function_exists('robodesk_get_ticket_source_meta') ? robodesk_get_ticket_source_meta($ticket_id) : array('ip' => '', 'country' => '', 'browser' => '');

    $replies = get_comments(['post_id' => $ticket_id]);
    $formatted_replies = array_map(function ($reply) {
        $comment_meta = get_comment_meta($reply->comment_ID);
        $author_type = isset($comment_meta['author_type']) ? $comment_meta['author_type'][0] : 'agent';

        // Determine if this reply should be considered a bot response.
        $is_bot = false;
        if (isset($comment_meta['is_bot_response']) && $comment_meta['is_bot_response'][0] === '1') {
            $is_bot = true;
        }

        // Legacy heuristic: comments with user_id 0 and chat source are likely bot replies
        if (!$is_bot && intval($reply->user_id) === 0) {
            if (isset($comment_meta['comment_source']) && $comment_meta['comment_source'][0] === 'chat') {
                $is_bot = true;
            }
        }

        // Determine author image: for bot use configured bot image, otherwise prefer support profile avatar helper
        $author_image = '';
        if ($is_bot) {
            $author_image = esc_url(get_option('robodesk_bot_profile_image', '')) ?: '';
        }

        if (empty($author_image)) {
            if (function_exists('robodesk_get_comment_avatar')) {
                $author_image = robodesk_get_comment_avatar($reply->user_id ?: 0, 32);
            } elseif (function_exists('robodesk_get_avatar_url')) {
                $author_image = robodesk_get_avatar_url($reply->user_id ?: 0, 32);
            } else {
                $author_image = get_avatar_url($reply->comment_author_email ?: '', array('size' => 32));
            }
        }

        return [
            'id' => $reply->comment_ID,
            'author' => $reply->comment_author,
            'message' => $reply->comment_content,
            'content' => $reply->comment_content,
            'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($reply) : $reply->comment_date,
            'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($reply) : $reply->comment_date,
            'type' => $author_type,
            'author_type' => $author_type,
            'is_bot_response' => $is_bot,
            'author_image' => $author_image,
        ];
    }, $replies);

    wp_send_json_success([
        'id' => $ticket_id,
        'title' => get_the_title($ticket_id),
        'customer' => $author ? $author->display_name : '',
        'email' => $author ? $author->user_email : '',
        'author_id' => $author ? (int) $author->ID : 0,
        'category' => $ticket_category,
        'source' => $ticket_origin,
        'user_location' => $author ? get_user_meta((int) $author->ID, 'robodesk_user_location', true) : '',
        'source_ip' => $ticket_source_meta['ip'],
        'source_country' => $ticket_source_meta['country'],
        'source_browser' => $ticket_source_meta['browser'],
        'status' => $ticket_status,
        'author_image' => function_exists('robodesk_get_avatar_url') ? robodesk_get_avatar_url((int) $post->post_author, 32) : get_avatar_url($post->post_author, ['size' => 32]),
        'created' => function_exists('robodesk_post_utc_iso') ? robodesk_post_utc_iso($post) : get_the_date('Y-m-d H:i:s', $ticket_id),
        'priority' => $ticket_priority,
        'updated' => function_exists('robodesk_post_utc_iso') ? robodesk_post_utc_iso($post, true) : get_the_modified_date('Y-m-d H:i:s', $ticket_id),
        'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'N/A',
        'assignee_id' => (int) get_post_meta($ticket_id, 'ticket_assignee_id', true),
        'replies' => $formatted_replies,
        'replies_count' => count($formatted_replies),
        'notes' => [],
    ]);
}


//for search of username and email in create ticket form
add_action('wp_ajax_robodesk_user_lookup', 'robodesk_user_lookup');

function robodesk_user_lookup()
{
    check_ajax_referer('robodesk_user_lookup_action', 'nonce');

    if (!isset($_POST['type'], $_POST['value'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        return;
    }
    $type = sanitize_text_field(wp_unslash($_POST['type']));
    $value = sanitize_text_field(wp_unslash($_POST['value']));

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
    $new_status = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));
    $old_status = $ticket_id > 0 ? (string) get_post_meta($ticket_id, '_robodesk_ticket_status', true) : '';

    if (!$ticket_id || !$new_status) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $current_user = wp_get_current_user();
    $roles = (array) $current_user->roles;
    $is_support_role = in_array('administrator', $roles, true) ||
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true);

    if (!$is_support_role && !current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    update_post_meta($ticket_id, '_robodesk_ticket_status', $new_status);
    $updated = function_exists('robodesk_datetime_to_utc_iso') ? robodesk_datetime_to_utc_iso(current_time('mysql')) : current_time('mysql');

    if ($old_status !== $new_status && function_exists('robodesk_notify_status_changed')) {
        robodesk_notify_status_changed($ticket_id, $new_status, get_current_user_id(), 'admin');
    }

    // Add a comment to indicate the status change
    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);
    if ($user_object) {
        wp_insert_comment([
            'comment_post_ID'      => $ticket_id,
            'comment_author'       => $user_object->display_name,
            'comment_author_email' => $user_object->user_email,
            'user_id'              => $user_id,
            'comment_content'      => 'Conversation status changed to ' . $new_status . ' by ' . $user_object->display_name,
            'comment_approved'     => 1,
            'comment_type'         => 'ticket_replies',
            'is_robodesk_reply'    => true, // Custom field to identify as a Robodesk reply
            //add comment meta to indicate the status change
            'comment_meta'         => [
                'is_action' => true,
            ],
        ]);
    }

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
    $new_priority = sanitize_text_field(wp_unslash($_POST['priority'] ?? ''));

    if (!$ticket_id || !$new_priority) {
        wp_send_json_error(['message' => 'Invalid input']);
    }

    $current_user = wp_get_current_user();
    $roles = (array) $current_user->roles;
    $is_support_role = in_array('administrator', $roles, true) ||
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true);

    if (!$is_support_role && !current_user_can('edit_post', $ticket_id)) {
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

    $updated = function_exists('robodesk_datetime_to_utc_iso') ? robodesk_datetime_to_utc_iso(current_time('mysql')) : current_time('mysql');

    //add a comment to indicate the priority change
    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);
    if ($user_object) {
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $ticket_id,
            'comment_author'       => $user_object->display_name,
            'comment_author_email' => $user_object->user_email,
            'user_id'              => $user_id,
            'comment_content'      => 'Conversation priority changed to ' . $new_priority . ' by ' . $user_object->display_name,
            'comment_approved'     => 1,
            'comment_type'         => 'ticket_replies',
            'is_robodesk_reply'    => true, // Custom field to identify as a Robodesk reply
            //add comment meta to indicate the priority change
            'comment_meta'         => [
                'is_action' => true,
            ],
        ]);

        if (!is_wp_error($comment_id)) {
            add_comment_meta($comment_id, 'comment_source', 'admin', true);
            add_comment_meta($comment_id, 'author_type', 'agent', true);
        }
    }
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
    $new_assignee = sanitize_text_field(wp_unslash($_POST['assignee'] ?? ''));

    if (!$ticket_id || !$new_assignee) {
        wp_send_json_error(['message' => 'Invalid input']);
    }

    $current_user = wp_get_current_user();
    $roles = (array) $current_user->roles;
    $is_support_role = in_array('administrator', $roles, true) ||
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true);

    if (!$is_support_role && !current_user_can('edit_post', $ticket_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $assignee_user = null;
    if (is_numeric($new_assignee)) {
        $assignee_user = get_user_by('id', (int) $new_assignee);
    } elseif (!empty($new_assignee)) {
        $assignee_user = get_user_by('display_name', $new_assignee);
        if (!$assignee_user) {
            $assignee_user = get_user_by('login', $new_assignee);
        }
    }

    $assignee_display = $assignee_user ? $assignee_user->display_name : $new_assignee;

    // Update post meta
    update_post_meta($ticket_id, 'ticket_assignee', $assignee_display);
    if ($assignee_user && $assignee_user->ID) {
        update_post_meta($ticket_id, 'ticket_assignee_id', (int) $assignee_user->ID);
    } else {
        robodesk_store_ticket_assignee_id($ticket_id, $assignee_display);
    }
    $updated = function_exists('robodesk_datetime_to_utc_iso') ? robodesk_datetime_to_utc_iso(current_time('mysql')) : current_time('mysql');


    // create a new comment to indicate the assignee change
    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);
    if ($user_object) {
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $ticket_id,
            'comment_author'       => $user_object->display_name,
            'comment_author_email' => $user_object->user_email,
            'user_id'              => $user_id,
            'comment_content'      => 'Conversation assigned to ' . $assignee_display . ' by ' . $user_object->display_name,
            'comment_approved'     => 1,
            'comment_type'         => 'ticket_replies',
            'is_robodesk_reply'    => true, // Custom field to identify as a Robodesk reply
            //add comment meta to indicate the assignee change
            'comment_meta'         => [
                'is_action' => true,
            ],
        ]);

        if (!is_wp_error($comment_id)) {
            add_comment_meta($comment_id, 'comment_source', 'admin', true);
            add_comment_meta($comment_id, 'author_type', 'agent', true);
        }
    }
    wp_send_json_success([
        'assignee' => $assignee_display,
        'assignee_id' => $assignee_user ? (int) $assignee_user->ID : 0,
        'updated'  => $updated,
    ]);
}





// ADD REPLY TO TICKET
add_action('wp_ajax_add_ticket_reply', 'robodesk_handle_add_ticket_reply');

function robodesk_handle_add_ticket_reply()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_add_ticket_reply_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $reply = isset($_POST['reply']) ? wp_kses_post(wp_unslash($_POST['reply'])) : '';

    // Normalize and strip HTML to detect visible text; handle non-breaking and zero-width spaces
    $decoded = html_entity_decode($reply, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Replace NBSP (U+00A0) and common zero-width chars with regular space
    $decoded = preg_replace('/[\x{00A0}\x{200B}\x{200C}\x{FEFF}]+/u', ' ', $decoded);
    $reply_text_only = trim(wp_strip_all_tags($decoded));
    if (!$ticket_id || $reply_text_only === '') {
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
        'is_robodesk_reply'   => true,
    ]);

    if (is_wp_error($comment_id)) {
        wp_send_json_error(['message' => 'Failed to insert comment']);
    }

    add_comment_meta($comment_id, 'comment_source', 'admin', true);
    add_comment_meta($comment_id, 'author_type', 'agent', true);

    // Trigger action for integrations (Slack, Discord, etc.)
    do_action('robodesk_after_reply_added', $ticket_id, $comment_id, $reply, $user_object);

    // Build human-readable role label
    $user_role_label = 'Support Staff';
    if (!empty($user_object->roles)) {
        $roles = $user_object->roles;
        if (in_array('administrator', $roles)) {
            $user_role_label = 'Administrator';
        } elseif (in_array('robodesk_support_manager', $roles)) {
            $user_role_label = 'Support Manager';
        } elseif (in_array('robodesk_support_staff', $roles)) {
            $user_role_label = 'Support Staff';
        } elseif (in_array('robodesk_agent', $roles)) {
            $user_role_label = 'Agent';
        } elseif (in_array('editor', $roles)) {
            $user_role_label = 'Editor';
        }
    }

    wp_send_json_success([
        'comment_id' => $comment_id,
        'author'     => $user_object->display_name,
        'userRole'   => $user_role_label,
    ]);
}


// add a ticket reply if onclick="closeTicket()" is clicked also change the status of the ticket to closed
add_action('wp_ajax_close_ticket', 'robodesk_handle_close_ticket');
function robodesk_handle_close_ticket()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_close_ticket_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

    if (!$ticket_id) {
        wp_send_json_error(['message' => 'Invalid ticket ID']);
    }

    // Update ticket status to closed
    $status_updated = update_post_meta($ticket_id, '_robodesk_ticket_status', 'closed');

    if (!$status_updated) {
        wp_send_json_error(['message' => 'Failed to close the ticket']);
    }

    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);

    if (!$user_id || !$user_object) {
        wp_send_json_error(['message' => 'User not logged in']);
    }

    // Add a reply comment if reply content is provided
    $reply = isset($_POST['reply']) ? wp_kses_post(wp_unslash($_POST['reply'])) : '';

    if (!empty($reply)) {
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $ticket_id,
            'comment_author'       => $user_object->display_name,
            'comment_author_email' => $user_object->user_email,
            'user_id'              => $user_id,
            'comment_content'      => $reply,
            'comment_approved'     => 1,
            'comment_type'         => 'ticket_replies',
            'is_robodesk_reply'   => true,
        ]);

        if (is_wp_error($comment_id)) {
            wp_send_json_error(['message' => 'Failed to insert comment']);
        } else {
            add_comment_meta($comment_id, 'comment_source', 'admin', true);
            add_comment_meta($comment_id, 'author_type', 'agent', true);
        }
    }

    // Add a comment indicating the ticket was closed
    $close_comment_id = wp_insert_comment([
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user_object->display_name,
        'comment_author_email' => $user_object->user_email,
        'user_id'              => $user_id,
        'comment_content'      => 'Conversation closed by ' . $user_object->display_name,
        'comment_approved'     => 1,
        'comment_type'         => 'ticket_replies',
        'is_robodesk_reply'   => true,
        'comment_meta'         => [
            'is_action' => true,
        ],
    ]);

    if (is_wp_error($close_comment_id)) {
        wp_send_json_error(['message' => 'Failed to add close comment']);
    } else {
        add_comment_meta($close_comment_id, 'comment_source', 'admin', true);
        add_comment_meta($close_comment_id, 'author_type', 'agent', true);
    }
    //if successful send email notification to the customer
    $customer_email = get_post_meta($ticket_id, '_robodesk_customer_email', true);

    if (!$customer_email) {
        rbd_log("No email found for ticket ID: $ticket_id");
    } elseif (!is_email($customer_email)) {
        rbd_log("Invalid email: $customer_email");
    } else {
        rbd_log("Sending email to: $customer_email");
        $subject = 'Your ticket has been closed';
        $ticket_link = robodesk_build_ticket_deeplink($ticket_id);
        $message = 'Hello, your ticket #' . $ticket_id . ' has been closed. If you have any further questions, feel free to reply to this email or visit: ' . $ticket_link;
        robodesk_send_email($customer_email, $subject, $message);
    }
    wp_send_json_success(['message' => 'Conversation closed successfully']);
}





// GET TICKET REPLIES
add_action('wp_ajax_get_ticket_replies', 'robodesk_handle_get_ticket_replies');

function robodesk_handle_get_ticket_replies()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_get_ticket_replies_action')) {
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
        'is_robodesk_reply' => true,
    ]);

    $replies = [];

    // Retrieve IMAP support inbox email once (used to identify email-piped agent replies)
    $imap_settings    = get_option('robodesk_imap_settings', []);
    $support_inbox    = strtolower(trim($imap_settings['username'] ?? ''));

    foreach ($comments as $comment) {
        $is_bot_response = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;

        // ── Determine type & role ─────────────────────────────────────────────
        // Priority order:
        //   1. Bot detection (always wins)
        //   2. author_type comment meta (set by email-piping and reply handlers)
        //   3. WP user role fallback (for replies added through the dashboard UI)
        // -----------------------------------------------------------------

        $saved_author_type = get_comment_meta($comment->comment_ID, 'author_type', true); // 'agent' | 'customer' | ''

        $type = 'customer';
        $role = 'Customer';

        if ($is_bot_response) {
            $type = 'agent';
            $role = 'AI BOT';
        } elseif ($saved_author_type === 'agent') {
            // Marked as agent by the email-piping code or dashboard handler.
            $type = 'agent';
            // Derive a human-readable role label from the WP user (if available),
            // falling back to 'Support Staff' for email-piped replies (user_id may be 0).
            $user = $comment->user_id ? get_userdata($comment->user_id) : false;
            if ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles)) {
                    $role = 'Administrator';
                } elseif (in_array('robodesk_support_manager', $roles)) {
                    $role = 'Support Manager';
                } elseif (in_array('robodesk_support_staff', $roles)) {
                    $role = 'Support Staff';
                } elseif (in_array('robodesk_agent', $roles)) {
                    $role = 'Agent';
                } elseif (in_array('editor', $roles)) {
                    $role = 'Editor';
                } else {
                    $role = 'Support Staff';
                }
            } else {
                // No WP user (email-piped reply) — show inbox address as role label
                // if available, otherwise generic label.
                $comment_email = strtolower(trim($comment->comment_author_email));
                if ($comment_email && $comment_email === $support_inbox) {
                    $role = 'Support';
                } else {
                    $role = 'Support Staff';
                }
            }
        } elseif ($saved_author_type === 'customer') {
            $type = 'customer';
            $role = 'Customer';
        } else {
            // No meta saved — fall back to WP role detection (covers legacy comments
            // added before author_type meta was introduced).
            $user = $comment->user_id ? get_userdata($comment->user_id) : false;
            if ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles)) {
                    $type = 'agent';
                    $role = 'Administrator';
                } elseif (in_array('robodesk_support_manager', $roles)) {
                    $type = 'agent';
                    $role = 'Support Manager';
                } elseif (in_array('robodesk_support_staff', $roles)) {
                    $type = 'agent';
                    $role = 'Support Staff';
                } elseif (in_array('robodesk_agent', $roles)) {
                    $type = 'agent';
                    $role = 'Agent';
                } elseif (in_array('editor', $roles)) {
                    $type = 'agent';
                    $role = 'Editor';
                }
            }
        }

        // Also check sender address against IMAP inbox as a final safety net:
        // if the comment came from the support inbox but meta/role detection missed it.
        if ($type === 'customer' && $support_inbox !== '') {
            $comment_email = strtolower(trim($comment->comment_author_email));
            if ($comment_email === $support_inbox) {
                $type = 'agent';
                $role = 'Support';
            }
        }

        // Get comment meta
        $is_action = get_comment_meta($comment->comment_ID, 'is_action', true);
        $comment_meta = [
            'is_action'      => $is_action ? true : false,
            'is_bot_response' => $is_bot_response,
        ];

        $replies[] = [
            'id'             => $comment->comment_ID,
            'author'         => $is_bot_response && function_exists('robodesk_get_bot_display_name') ? robodesk_get_bot_display_name() : $comment->comment_author,
            'message'        => $comment->comment_content,
            'timestamp'      => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
            'type'           => $type,
            'role'           => $role,
            'is_bot_response' => $is_bot_response,
            'comment_meta'   => $comment_meta,
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
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_add_ticket_note_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $note = isset($_POST['note']) ? wp_kses_post(wp_unslash($_POST['note'])) : '';

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

    // Build human-readable role label
    $user_role_label = 'Agent';
    if (!empty($user_object->roles)) {
        $roles = $user_object->roles;
        if (in_array('administrator', $roles)) {
            $user_role_label = 'Administrator';
        } elseif (in_array('robodesk_support_manager', $roles)) {
            $user_role_label = 'Support Manager';
        } elseif (in_array('robodesk_support_staff', $roles)) {
            $user_role_label = 'Support Staff';
        } elseif (in_array('robodesk_agent', $roles)) {
            $user_role_label = 'Agent';
        } elseif (in_array('editor', $roles)) {
            $user_role_label = 'Editor';
        }
    }

    wp_send_json_success([
        'note_id'  => $note_id,
        'author'   => $user_object->display_name,
        'userRole' => $user_role_label,
    ]);
}

// GET TICKET NOTES
add_action('wp_ajax_get_ticket_notes', 'robodesk_handle_get_ticket_notes');

function robodesk_handle_get_ticket_notes()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_get_ticket_notes_action')) {
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
        // Determine user type and role
        $user = get_userdata($comment->user_id);
        $type = 'agent'; // Notes are internal, so always agent
        $role = 'Agent';
        if ($user) {
            $roles = $user->roles;
            if (in_array('administrator', $roles)) {
                $type = 'agent';
                $role = 'Administrator';
            } elseif (in_array('robodesk_support_manager', $roles)) {
                $type = 'agent';
                $role = 'Support Manager';
            } elseif (in_array('robodesk_support_staff', $roles)) {
                $type = 'agent';
                $role = 'Support Staff';
            } elseif (in_array('robodesk_agent', $roles)) {
                $type = 'agent';
                $role = 'Agent';
            } elseif (in_array('editor', $roles)) {
                $type = 'agent';
                $role = 'Editor';
            } else {
                $type = 'customer'; // If not an agent, consider it a customer note
                $role = 'Customer';
            }
        }
        $notes[] = [
            'id'        => $comment->comment_ID,
            'author'    => $comment->comment_author,
            'message'   => $comment->comment_content,
            'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
            'type'      => $type,
            'role'      => $role,
        ];
    }

    wp_send_json_success([
        'notes' => $notes,
        'count' => count($notes),
    ]);
}

// GET TICKET REPLIES AND NOTES (combined)
add_action('wp_ajax_get_ticket_replies_and_notes', 'robodesk_handle_get_ticket_replies_and_notes');

function robodesk_handle_get_ticket_replies_and_notes()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_get_ticket_replies_and_notes_action')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

    if (!$ticket_id) {
        wp_send_json_error(['message' => 'Invalid ticket ID']);
    }

    // ── Replies ──
    $reply_comments = get_comments([
        'post_id' => $ticket_id,
        'order'   => 'ASC',
        'type'    => 'ticket_replies',
        'is_robodesk_reply' => true,
    ]);

    $imap_settings = get_option('robodesk_imap_settings', []);
    $support_inbox = strtolower(trim($imap_settings['username'] ?? ''));

    $replies = [];

    foreach ($reply_comments as $comment) {
        $is_bot_response = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;

        $saved_author_type = get_comment_meta($comment->comment_ID, 'author_type', true);

        $type = 'customer';
        $role = 'Customer';

        if ($is_bot_response) {
            $type = 'agent';
            $role = 'AI BOT';
        } elseif ($saved_author_type === 'agent') {
            $type = 'agent';
            $user = $comment->user_id ? get_userdata($comment->user_id) : false;
            if ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles)) {
                    $role = 'Administrator';
                } elseif (in_array('robodesk_support_manager', $roles)) {
                    $role = 'Support Manager';
                } elseif (in_array('robodesk_support_staff', $roles)) {
                    $role = 'Support Staff';
                } elseif (in_array('robodesk_agent', $roles)) {
                    $role = 'Agent';
                } elseif (in_array('editor', $roles)) {
                    $role = 'Editor';
                } else {
                    $role = 'Support Staff';
                }
            } else {
                $comment_email = strtolower(trim($comment->comment_author_email));
                if ($comment_email && $comment_email === $support_inbox) {
                    $role = 'Support';
                } else {
                    $role = 'Support Staff';
                }
            }
        } elseif ($saved_author_type === 'customer') {
            $type = 'customer';
            $role = 'Customer';
        } else {
            $user = $comment->user_id ? get_userdata($comment->user_id) : false;
            if ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles)) {
                    $type = 'agent';
                    $role = 'Administrator';
                } elseif (in_array('robodesk_support_manager', $roles)) {
                    $type = 'agent';
                    $role = 'Support Manager';
                } elseif (in_array('robodesk_support_staff', $roles)) {
                    $type = 'agent';
                    $role = 'Support Staff';
                } elseif (in_array('robodesk_agent', $roles)) {
                    $type = 'agent';
                    $role = 'Agent';
                } elseif (in_array('editor', $roles)) {
                    $type = 'agent';
                    $role = 'Editor';
                }
            }
        }

        if ($type === 'customer' && $support_inbox !== '') {
            $comment_email = strtolower(trim($comment->comment_author_email));
            if ($comment_email === $support_inbox) {
                $type = 'agent';
                $role = 'Support';
            }
        }

        $is_action = get_comment_meta($comment->comment_ID, 'is_action', true);
        $comment_meta = [
            'is_action'       => $is_action ? true : false,
            'is_bot_response' => $is_bot_response,
        ];

        $replies[] = [
            'id'              => $comment->comment_ID,
            'author'          => $is_bot_response && function_exists('robodesk_get_bot_display_name') ? robodesk_get_bot_display_name() : $comment->comment_author,
            'message'         => $comment->comment_content,
            'timestamp'       => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
            'type'            => $type,
            'role'            => $role,
            'is_bot_response' => $is_bot_response,
            'comment_meta'    => $comment_meta,
        ];
    }

    // ── Notes ──
    $note_comments = get_comments([
        'post_id' => $ticket_id,
        'order'   => 'ASC',
        'type'    => 'ticket_notes',
        'is_robodesk_note' => true,
    ]);

    $notes = [];
    foreach ($note_comments as $comment) {
        $user = get_userdata($comment->user_id);
        $type = 'agent';
        $role = 'Agent';
        if ($user) {
            $roles = $user->roles;
            if (in_array('administrator', $roles)) {
                $type = 'agent';
                $role = 'Administrator';
            } elseif (in_array('robodesk_support_manager', $roles)) {
                $type = 'agent';
                $role = 'Support Manager';
            } elseif (in_array('robodesk_support_staff', $roles)) {
                $type = 'agent';
                $role = 'Support Staff';
            } elseif (in_array('robodesk_agent', $roles)) {
                $type = 'agent';
                $role = 'Agent';
            } elseif (in_array('editor', $roles)) {
                $type = 'agent';
                $role = 'Editor';
            } else {
                $type = 'customer';
                $role = 'Customer';
            }
        }
        $notes[] = [
            'id'        => $comment->comment_ID,
            'author'    => $comment->comment_author,
            'message'   => $comment->comment_content,
            'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
            'type'      => $type,
            'role'      => $role,
        ];
    }

    wp_send_json_success([
        'replies' => $replies,
        'replies_count' => count($replies),
        'notes'   => $notes,
        'notes_count'   => count($notes),
    ]);
}

// added a ticket 






include_once ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-fetch-tickets.php'; // Include the file that contains the robodesk_fetch_emails function
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


add_action('wp_ajax_update_ticket_category', 'handle_update_ticket_category');

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function handle_update_ticket_category()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_update_ticket_category_action')) {
        wp_die('Security check failed');
    }

    // Check user permissions
    $current_user = wp_get_current_user();
    $roles = (array) $current_user->roles;
    $is_support_role = in_array('administrator', $roles, true) ||
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true);

    if (!$is_support_role && !current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    // Validate inputs
    if (!$ticket_id || !$category_id) {
        wp_send_json_error('Invalid ticket ID or category ID');
        return;
    }

    // Update the ticket category
    $result = wp_set_post_terms($ticket_id, array($category_id), 'robodesk_ticket_category');

    if (is_wp_error($result)) {
        wp_send_json_error('Failed to update category: ' . $result->get_error_message());
        return;
    }

    $category = get_term($category_id, 'robodesk_ticket_category');

    wp_send_json_success(array(
        'message' => 'Category updated successfully',
        'category' => array(
            'term_id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug
        )
    ));
}

// Set default taxonomy term for robodesk_ticket_category when a ticket is created
add_action('save_post', 'set_default_taxonomy_term', 10, 3);
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function set_default_taxonomy_term($post_id, $post, $update)
{
    // Check if this is the correct post type
    if ($post->post_type !== 'robodesk_ticket') {
        return;
    }

    // Prevent infinite loops by removing the action temporarily
    remove_action('save_post', 'set_default_taxonomy_term');

    // Check if the post already has terms for the taxonomy
    $taxonomy = 'robodesk_ticket_category';
    $terms = get_the_terms($post_id, $taxonomy);

    // If no terms are set, assign the default term from the option
    if (empty($terms) || is_wp_error($terms)) {
        $default_term_id = get_option('robodesk_default_ticket_category', false);
        if ($default_term_id) {
            wp_set_post_terms($post_id, array((int)$default_term_id), $taxonomy, false);
        }
    }

    // Re-add the action
    add_action('save_post', 'set_default_taxonomy_term', 10, 3);
}


// adding a custom meta field in the user profile
add_action('show_user_profile', 'add_robodesk_user_meta_fields');
add_action('edit_user_profile', 'add_robodesk_user_meta_fields');
add_action('personal_options_update', 'save_robodesk_user_meta_fields');
add_action('edit_user_profile_update', 'save_robodesk_user_meta_fields');

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function add_robodesk_user_meta_fields($user)
{
    $user_location = get_user_meta($user->ID, 'robodesk_user_location', true);
?>
    <h3><?php esc_html_e('Robodesk Support', 'robodesk'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="robodesk_user_department"><?php esc_html_e('Department', 'robodesk'); ?></label></th>
            <td>
                <select name="robodesk_user_department" id="robodesk_user_department">
                    <option value=""><?php esc_html_e('Select Department', 'robodesk'); ?></option>
                    <?php
                    $departments = get_terms(array(
                        'taxonomy' => 'robodesk_ticket_category',
                        'hide_empty' => false,
                    ));
                    $user_department = get_user_meta($user->ID, 'robodesk_user_department', true);
                    foreach ($departments as $department) {
                        echo '<option value="' . esc_attr($department->term_id) . '" ' .
                            selected($user_department, $department->term_id, false) .
                            '>' . esc_html($department->name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php esc_html_e('Select the department this user belongs to.', 'robodesk'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="robodesk_user_location"><?php esc_html_e('Location', 'robodesk'); ?></label></th>
            <td>
                <input type="text" name="robodesk_user_location" id="robodesk_user_location" value="<?php echo esc_attr($user_location); ?>" class="regular-text">
                <p class="description"><?php esc_html_e('Enter the location of this user (e.g. Denmark, USA).', 'robodesk'); ?></p>
            </td>
        </tr>
    </table>
<?php
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function save_robodesk_user_meta_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-user_' . $user_id)) {
        return false;
    }

    if (isset($_POST['robodesk_user_department'])) {
        update_user_meta($user_id, 'robodesk_user_department', sanitize_text_field(wp_unslash($_POST['robodesk_user_department'])));
    }

    if (isset($_POST['robodesk_user_location'])) {
        update_user_meta($user_id, 'robodesk_user_location', sanitize_text_field(wp_unslash($_POST['robodesk_user_location'])));
    }
}

// Automatically assign a ticket to support staff. Email fetch can enforce strict default-agent-only assignment.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function auto_assign_ticket_to_support_staff($prefer_default_agent = false, $strict_default_only = false)
{
    // Match eligible users with roles shown in settings "Auto-assign to".
    $support_staff = get_users(array(
        'role__in' => array('robodesk_support_staff', 'robodesk_support_manager', 'administrator', 'author', 'editor'),
        'fields' => array('ID', 'display_name', 'user_login')
    ));

    if (empty($support_staff)) {
        rbd_log('Robodesk: No support staff users found.');
        return false;
    }

    rbd_log('Robodesk: Assigning new ticket to a support staff member.');

    // For email-imported tickets, prefer configured default agent when requested.
    $default_agent = trim((string) get_option('robodesk_default_agent', ''));
    if ($prefer_default_agent && $default_agent !== '') {
        foreach ($support_staff as $staff) {
            $matches_default = ((string) $staff->ID === $default_agent) ||
                ((string) $staff->display_name === $default_agent) ||
                ((string) $staff->user_login === $default_agent);

            if ($matches_default) {
                $assigned_name = !empty($staff->display_name) ? $staff->display_name : $staff->user_login;
                rbd_log('Robodesk: Assigned to configured default agent: ' . $assigned_name . ' (ID: ' . $staff->ID . ')');
                return $assigned_name;
            }
        }

        if ($strict_default_only) {
            rbd_log('Robodesk: Default agent setting does not match any eligible support user. Leaving ticket unassigned.');
            return false;
        }
    }

    if ($strict_default_only) {
        rbd_log('Robodesk: No default agent configured for email fetch. Leaving ticket unassigned.');
        return false;
    }

    // Get assignment counts for each staff member
    $staff_assignments = array();
    foreach ($support_staff as $staff) {
        $assigned_tickets = get_posts(array(
            'post_type' => 'robodesk_ticket',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'ticket_assignee',
                    'value' => $staff->display_name,
                    'compare' => '='
                ),
                array(
                    'key' => '_robodesk_ticket_status',
                    'value' => array('open', 'in-progress'),
                    'compare' => 'IN'
                )
            ),
            'numberposts' => -1,
            'fields' => 'ids'
        ));

        $staff_assignments[$staff->ID] = array(
            'user' => $staff,
            'count' => count($assigned_tickets)
        );
    }

    // Sort by assignment count (ascending), maintaining order for equal counts
    uasort($staff_assignments, function ($a, $b) {
        return $a['count'] - $b['count'];
    });

    // Get the staff member with the least assignments (first in sorted array)
    $least_assigned = reset($staff_assignments);
    $assigned_user = $least_assigned['user'];
    $assigned_name = !empty($assigned_user->display_name) ? $assigned_user->display_name : $assigned_user->user_login;

    rbd_log('Robodesk: New ticket assigned to: ' . $assigned_name . ' (ID: ' . $assigned_user->ID . ')');

    return $assigned_name;
}



// bulk action
// Hook the AJAX action for bulk operations
add_action('wp_ajax_robodesk_bulk_action', 'robodesk_handle_bulk_action');

function robodesk_handle_bulk_action()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_bulk_action')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }

    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    // Get the posted data
    $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
    $ticket_ids_json = isset($_POST['ticket_ids']) ? sanitize_text_field(wp_unslash($_POST['ticket_ids'])) : '';

    // Decode ticket IDs
    $ticket_ids = json_decode($ticket_ids_json, true);
    if (!is_array($ticket_ids) || empty($ticket_ids)) {
        wp_send_json_error(array('message' => 'No valid ticket IDs provided.'));
    }

    // Extract the action type and value
    $action_type = $bulk_action;
    $action_value = '';
    if (strpos($bulk_action, 'status-') === 0) {
        $action_type = 'status';
        $action_value = str_replace('status-', '', $bulk_action);
    } elseif (strpos($bulk_action, 'priority-') === 0) {
        $action_type = 'priority';
        $action_value = str_replace('priority-', '', $bulk_action);
    } elseif (strpos($bulk_action, 'assignee-') === 0) {
        $action_type = 'assignee';
        $action_value = str_replace('assignee-', '', $bulk_action);
    }

    // Validate bulk action
    if (!in_array($action_type, ['delete', 'status', 'priority', 'assignee'])) {
        wp_send_json_error(array('message' => 'Invalid bulk action.'));
    }

    // Validate action value for actions that require it
    $valid_statuses = ['open', 'in-progress', 'closed'];
    if ($action_type === 'status' && !in_array($action_value, $valid_statuses)) {
        wp_send_json_error(array('message' => "Invalid status: $action_value"));
    }

    // Sanitize ticket IDs to integers
    $ticket_ids = array_map('intval', $ticket_ids);

    // Process based on bulk action
    $updated_count = 0;
    $errors = array();
    $user_id = get_current_user_id();
    $user_object = get_userdata($user_id);

    foreach ($ticket_ids as $ticket_id) {
        // Verify post type to prevent accidental updates
        if (get_post_type($ticket_id) !== 'robodesk_ticket') {
            $errors[] = "Invalid ticket ID: $ticket_id";
            continue;
        }

        switch ($action_type) {
            case 'delete':
                // Force delete the ticket (bypass trash)
                if (wp_delete_post($ticket_id, true)) {
                    $updated_count++;
                } else {
                    $errors[] = "Failed to delete ticket ID: $ticket_id";
                }
                break;

            case 'status':
                $current_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true);

                if ($current_status === $action_value) {
                    // Status is already the desired one → count as successful
                    $updated_count++;
                } else {
                    // Update status meta
                    if (update_post_meta($ticket_id, '_robodesk_ticket_status', $action_value)) {
                        // Update modified date to reflect the change
                        wp_update_post(array(
                            'ID' => $ticket_id,
                            'post_modified' => current_time('mysql'),
                            'post_modified_gmt' => current_time('mysql', 1)
                        ));
                        $updated_count++;
                    } else {
                        $errors[] = "Failed to update status for ticket ID: $ticket_id";
                    }
                }
                break;

                // Update status meta
                if (update_post_meta($ticket_id, '_robodesk_ticket_status', $action_value)) {
                    // Update modified date to reflect the change
                    wp_update_post(array(
                        'ID' => $ticket_id,
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ));
                    $updated_count++;
                } else {
                    $errors[] = "Failed to update status for ticket ID: $ticket_id";
                }
                break;

            case 'priority':
                // Get the term by name
                $term = get_term_by('name', $action_value, 'robodesk_ticket_priority');
                if (!$term || is_wp_error($term)) {
                    $errors[] = "Invalid priority: $action_value for ticket ID: $ticket_id";
                    continue 2;
                }

                $result = wp_set_object_terms($ticket_id, (int)$term->term_id, 'robodesk_ticket_priority', false);
                if (is_wp_error($result)) {
                    $errors[] = "Failed to update priority for ticket ID: $ticket_id - " . $result->get_error_message();
                } else {
                    // Update modified date to reflect the change
                    wp_update_post(array(
                        'ID' => $ticket_id,
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ));
                    $updated_count++;
                }
                break;

            case 'assignee':
                $user = null;
                if (is_numeric($action_value)) {
                    $user = get_user_by('id', (int) $action_value);
                } else {
                    $user = get_user_by('display_name', $action_value);
                    if (!$user) {
                        $user = get_user_by('login', $action_value);
                    }
                }

                if (!$user) {
                    $errors[] = "Invalid assignee: $action_value for ticket ID: $ticket_id";
                    continue 2;
                }

                $assignee_display = $user->display_name;

                if (update_post_meta($ticket_id, 'ticket_assignee', $assignee_display)) {
                    update_post_meta($ticket_id, 'ticket_assignee_id', (int) $user->ID);
                    // Update modified date to reflect the change
                    wp_update_post(array(
                        'ID' => $ticket_id,
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ));
                    $updated_count++;
                } else {
                    $errors[] = "Failed to update assignee for ticket ID: $ticket_id";
                }
                break;
        }

        if ($action_type !== 'delete' && $user_object) {
            $comment_content = '';
            $comment_meta = array(
                'is_action' => true,
            );

            if ($action_type === 'status') {
                $comment_content = 'Conversation status changed to ' . $action_value . ' by ' . $user_object->display_name;
            } elseif ($action_type === 'priority') {
                $comment_content = 'Conversation priority changed to ' . $action_value . ' by ' . $user_object->display_name;
            } elseif ($action_type === 'assignee') {
                $assignee_display = '';
                if (is_numeric($action_value)) {
                    $assignee_user = get_user_by('id', (int) $action_value);
                } else {
                    $assignee_user = get_user_by('display_name', $action_value);
                    if (!$assignee_user) {
                        $assignee_user = get_user_by('login', $action_value);
                    }
                }

                if ($assignee_user) {
                    $assignee_display = $assignee_user->display_name;
                }

                if ($assignee_display === '') {
                    $assignee_display = $action_value;
                }

                $comment_content = 'Conversation assigned to ' . $assignee_display . ' by ' . $user_object->display_name;
            }

            if ($comment_content !== '') {
                $comment_id = wp_insert_comment(array(
                    'comment_post_ID'      => $ticket_id,
                    'comment_author'       => $user_object->display_name,
                    'comment_author_email' => $user_object->user_email,
                    'user_id'              => $user_id,
                    'comment_content'      => $comment_content,
                    'comment_approved'     => 1,
                    'comment_type'         => 'ticket_replies',
                    'is_robodesk_reply'    => true,
                    'comment_meta'         => $comment_meta,
                ));

                if (is_wp_error($comment_id)) {
                    $errors[] = "Failed to add comment for ticket ID: $ticket_id";
                } elseif ($action_type === 'priority' || $action_type === 'assignee') {
                    add_comment_meta($comment_id, 'comment_source', 'admin', true);
                    add_comment_meta($comment_id, 'author_type', 'agent', true);
                }
            }
        }
    }

    // Prepare response
    if ($updated_count > 0) {
        $action_text = ($action_type === 'delete') ? 'deleted' : 'updated';
        $message = sprintf(
            '%d conversation%s successfully %s.',
            $updated_count,
            ($updated_count !== 1) ? 's' : '',
            $action_text
        );

        if (!empty($errors)) {
            $message .= ' However, there were some errors: ' . implode('; ', $errors);
        }

        wp_send_json_success(array('message' => $message));
    } else {
        $error_message = !empty($errors) ? implode('; ', $errors) : 'No tickets were updated.';
        wp_send_json_error(array('message' => $error_message));
    }
}

// Additional helper function to get available users for assignee dropdown (optional)
function robodesk_get_available_assignees()
{
    $users = get_users(array(
        'role__not_in' => array('subscriber'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));

    $assignees = array();
    foreach ($users as $user) {
        $assignees[] = array(
            'id' => $user->ID,
            'display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email
        );
    }

    return $assignees;
}

// Helper function to get available priorities
function robodesk_get_available_priorities()
{
    $terms = get_terms(array(
        'taxonomy' => 'robodesk_ticket_priority',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    if (is_wp_error($terms)) {
        return array();
    }

    return $terms;
}


// function to send email notifications when a ticket is created of posttype robodesk_ticket
add_action('save_post', 'robodesk_send_ticket_creation_email', 10, 3);
function robodesk_send_ticket_creation_email($post_id, $post, $update)
{
    // Only send email for new tickets
    if ($post->post_type !== 'robodesk_ticket' || $update) {
        return;
    }

    // Get customer email from post meta
    $customer_email = get_post_meta($post_id, '_robodesk_customer_email', true);
    if (!$customer_email || !is_email($customer_email)) {
        $author = get_userdata($post->post_author);
        $author_email = $author ? $author->user_email : '';

        if ($author_email && is_email($author_email)) {
            update_post_meta($post_id, '_robodesk_customer_email', $author_email);
            $customer_email = $author_email;
        } else {
            rbd_log("Robodesk: Invalid or missing customer email for ticket ID: $post_id");
            return;
        }
    }

    // Prepare email content
    $subject = 'Your support ticket has been created.';
    $ticket_link = robodesk_build_ticket_deeplink($post_id);
    $theme_color = robodesk_get_email_theme_color();

    $author = get_userdata($post->post_author);
    $first_name = '';
    if ($author && $author->ID) {
        $first_name = trim((string) get_user_meta($author->ID, 'first_name', true));
        if ($first_name === '') {
            $display_name = trim((string) $author->display_name);
            if ($display_name !== '') {
                $name_parts = preg_split('/\s+/', $display_name);
                $first_name = isset($name_parts[0]) ? (string) $name_parts[0] : '';
            }
        }
    }

    if ($first_name === '') {
        $first_name = 'there';
    }

    $message = '<p>Hello ' . esc_html($first_name) . ',</p>';
    $message .= '<p>Your support ticket #' . (int) $post_id . ' has been created.</p>';
    $message .= '<p>We will get back to you shortly. If you need to add to your conversation you can do so by clicking the button.</p> <br>';
    $message .= '<p><a href="' . esc_url($ticket_link) . '" style="display:inline-block;padding:12px 18px;background-color:' . esc_attr($theme_color) . ';color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">View your conversation</a></p> <br>';
    $message .= '<p>If the button above does not work, please use this link: <a href="' . esc_url($ticket_link) . '">' . esc_html($ticket_link) . '</a></p>';

    // Send email
    robodesk_send_email($customer_email, $subject, $message);
    rbd_log("Robodesk: Ticket creation email sent to $customer_email for ticket ID: $post_id");
}

// AJAX handler for refreshing dashboard tickets
add_action('wp_ajax_robodesk_refresh_dashboard_tickets', 'robodesk_refresh_dashboard_tickets');
function robodesk_refresh_dashboard_tickets()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_refresh_dashboard_tickets_action')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }

    // Check user capabilities
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    // Get current user info for filtering
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name ?: $current_user->user_login;

    // Initialize tickets array
    $dashboard_tickets = [];

    // WP_Query arguments to fetch ALL tickets
    $args = [
        'post_type'      => 'robodesk_ticket',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $ticket_id = get_the_ID();

            $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'N/A';
            $ticket_origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true);
            if (empty($ticket_origin)) {
                $origin_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_origin');
                if (!is_wp_error($origin_terms) && !empty($origin_terms)) {
                    $ticket_origin = $origin_terms[0]->name;
                }
            }
            $ticket_origin = $ticket_origin ?: 'N/A';
            $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
            $author_image = function_exists('robodesk_get_avatar_url') ? robodesk_get_avatar_url((int) get_the_author_meta('ID'), 32) : get_avatar_url(get_the_author_meta('ID'), ['size' => 32]);

            $ticket_priority = $priority_terms && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'N/A';
            $ticket_post = get_post($ticket_id);
            $updated_at = function_exists('robodesk_post_utc_iso') ? robodesk_post_utc_iso($ticket_post, true) : get_the_modified_date('Y-m-d H:i:s', $ticket_id);
            $created_at = function_exists('robodesk_post_utc_iso') ? robodesk_post_utc_iso($ticket_post) : get_the_date('Y-m-d H:i:s', $ticket_id);
            $ticket_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_category');

            $ticket_category = '';
            if (!is_wp_error($ticket_terms) && !empty($ticket_terms)) {
                $ticket_category = [
                    'name' => $ticket_terms[0]->name,
                    'id'   => $ticket_terms[0]->term_id,
                    'term_id' => $ticket_terms[0]->term_id,
                ];
            }

            $ticket_source_meta = function_exists('robodesk_get_ticket_source_meta') ? robodesk_get_ticket_source_meta($ticket_id) : array('ip' => '', 'country' => '', 'browser' => '');

            // Fetch replies using comments
            $replies = get_comments(['post_id' => $ticket_id]);
            $replies_count = count($replies);
            $formatted_replies = array_map(function ($reply) use ($ticket_id) {
                $comment_meta = get_comment_meta($reply->comment_ID);
                $author_type = isset($comment_meta['author_type']) ? $comment_meta['author_type'][0] : 'agent';

                return [
                    'id' => $reply->comment_ID,
                    'author' => $reply->comment_author,
                    'message' => $reply->comment_content,
                    'content' => $reply->comment_content,
                    'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($reply) : $reply->comment_date,
                    'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($reply) : $reply->comment_date,
                    'type' => $author_type,
                    'author_type' => $author_type,
                ];
            }, $replies);

            $dashboard_tickets[] = [
                'id' => $ticket_id,
                'title' => get_the_title(),
                'customer' => get_the_author(),
                'email' => get_the_author_meta('user_email'),
                'author_id' => get_the_author_meta('ID'),
                'user_location' => get_user_meta((int) get_the_author_meta('ID'), 'robodesk_user_location', true),
                'category' => $ticket_category,
                'source' => $ticket_origin,
                'source_ip' => $ticket_source_meta['ip'],
                'source_country' => $ticket_source_meta['country'],
                'source_browser' => $ticket_source_meta['browser'],
                'status' => $ticket_status,
                'author_image' => $author_image,
                'created' => $created_at,
                'priority' => $ticket_priority,
                'updated' => $updated_at,
                'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'N/A',
                'assignee_id' => (int) get_post_meta($ticket_id, 'ticket_assignee_id', true),
                'replies' => $formatted_replies,
                'replies_count' => $replies_count,
            ];
        }
        wp_reset_postdata();
    }

    // Return the tickets data
    wp_send_json_success([
        'tickets' => $dashboard_tickets,
        'current_user_name' => $current_user_name,
    ]);
}

add_action('wp_ajax_robodesk_get_notifications', 'robodesk_get_notifications');
function robodesk_get_notifications()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_notifications_action')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, ticket_id, comment_id, type, source, message, created_at
             FROM {$table_name}
             WHERE recipient_user_id = %d AND read_at IS NULL
             ORDER BY created_at DESC
             LIMIT 50",
            $user_id
        ),
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

    $items = [];
    foreach ($rows as $row) {
        $ticket_id = (int) $row['ticket_id'];

        if ($ticket_id > 0) {
            $current_assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
            if ($current_assignee_id > 0 && $current_assignee_id !== $user_id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
                $wpdb->delete(
                    $table_name,
                    ['id' => (int) $row['id'], 'recipient_user_id' => $user_id],
                    ['%d', '%d']
                );
                continue;
            }
        }

        $items[] = [
            'id' => (int) $row['id'],
            'ticketId' => $ticket_id,
            'commentId' => $row['comment_id'] ? (int) $row['comment_id'] : null,
            'type' => $row['type'],
            'source' => $row['source'],
            'message' => $row['message'],
            'title' => $row['type'] === 'ticket_created'
                ? 'New Conversation #' . $ticket_id
                : 'New Reply on #' . $ticket_id,
            'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'Unassigned',
            'timestamp' => function_exists('robodesk_datetime_to_utc_iso') ? robodesk_datetime_to_utc_iso($row['created_at']) : $row['created_at'],
        ];
    }

    wp_send_json_success([
        'items' => $items,
        'count' => count($items),
    ]);
}

add_action('wp_ajax_robodesk_mark_notification_read', 'robodesk_mark_notification_read');
function robodesk_mark_notification_read()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_notifications_action')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    if (!$notification_id) {
        wp_send_json_error(['message' => 'Invalid notification ID.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, ticket_id FROM {$table_name} WHERE id = %d AND recipient_user_id = %d LIMIT 1",
            $notification_id,
            $user_id
        ),
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

    if (!$row) {
        wp_send_json_error(['message' => 'Notification not found.']);
    }

    $ticket_id = isset($row['ticket_id']) ? (int) $row['ticket_id'] : 0;
    if ($ticket_id > 0) {
        $current_assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
        if ($current_assignee_id > 0 && $current_assignee_id !== $user_id) {
            wp_send_json_error(['message' => 'Notification is no longer assigned to you.']);
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $deleted = $wpdb->delete(
        $table_name,
        ['id' => $notification_id, 'recipient_user_id' => $user_id],
        ['%d', '%d']
    );

    if ($deleted === false) {
        wp_send_json_error(['message' => 'Failed to remove notification.']);
    }

    if ($deleted < 1) {
        wp_send_json_error(['message' => 'Notification not found.']);
    }

    wp_send_json_success(['message' => 'Notification opened and removed.']);
}

add_action('wp_ajax_robodesk_clear_notifications', 'robodesk_clear_notifications');
function robodesk_clear_notifications()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'robodesk_notifications_action')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE recipient_user_id = %d AND read_at IS NULL",
            $user_id
        )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

    if ($deleted === false) {
        wp_send_json_error(['message' => 'Failed to clear notifications.']);
    }

    wp_send_json_success([
        'message' => 'Notifications cleared.',
        'deleted_count' => (int) $deleted,
    ]);
}


// Custom email function for wp_mail with html with header and footer
function robodesk_prepare_email_content($message)
{
    $message = (string) $message;
    if ($message === '') {
        return '';
    }

    if ($message === wp_strip_all_tags($message)) {
        $safe_message = esc_html($message);
        $safe_message = preg_replace_callback(
            '#(https?://[^\s<]+)#i',
            function ($matches) {
                $display_url = $matches[1];
                $href_url = esc_url(html_entity_decode($display_url, ENT_QUOTES, 'UTF-8'));
                if ($href_url === '') {
                    return $display_url;
                }

                return '<a href="' . $href_url . '" target="_blank" rel="noopener noreferrer">' . $display_url . '</a>';
            },
            $safe_message
        );

        return nl2br(wp_kses_post($safe_message));
    }

    return wp_kses_post($message);
}

function robodesk_get_email_theme_color()
{
    $theme_color = sanitize_hex_color((string) get_option('robodesk_theme_color', ''));
    return $theme_color ?: '#000000';
}

function robodesk_get_email_logo_url()
{
    $robodesk_wide_logo = trim((string) get_option('robodesk_wide_logo', ''));
    if ($robodesk_wide_logo !== '') {
        return $robodesk_wide_logo;
    }

    return '';
}

/**
 * Resolve a filesystem path for the configured email logo (wide or regular).
 * Returns empty string when no local file can be found.
 */
function robodesk_get_email_logo_file_path()
{
    // Try wide logo first, then regular logo
    $candidates = [
        trim((string) get_option('robodesk_wide_logo', '')),
        trim((string) get_option('robodesk_logo', '')),
    ];

    $uploads = wp_get_upload_dir();

    foreach ($candidates as $val) {
        if ($val === '') {
            continue;
        }

        // Attachment ID
        if (ctype_digit($val)) {
            $path = get_attached_file((int) $val);
            if ($path && file_exists($path)) {
                return $path;
            }
            continue;
        }

        // If stored as URL inside uploads, map to basedir
        if (strpos($val, $uploads['baseurl']) === 0) {
            $path = str_replace($uploads['baseurl'], $uploads['basedir'], $val);
            if ($path && file_exists($path)) {
                return $path;
            }
            continue;
        }

        // If it's an absolute path (unlikely), check directly
        if (file_exists($val)) {
            return $val;
        }
    }

    return '';
}

function robodesk_get_default_email_header_template()
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{EMAIL_SUBJECT}}</title>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f1f1f1; font-family: Arial, sans-serif; color: #212529; }
        table { border-collapse: collapse; border-spacing: 0; }
        .wrapper { width: 100%; background-color: #f1f1f1; padding: 24px 0; }
        .container { width: 100%; max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; }
        .header { padding: 24px 28px; text-align: center; }
        .header img { max-width: 220px; width: 100%; height: auto; }
        .subject { margin: 0; padding: 0; font-size: 22px; line-height: 1.4; color: #2c2c2c; font-weight: 700; }
        .content { padding: 30px; font-size: 16px; line-height: 1.6; color: #212529; }
        .content a { color: {{THEME_COLOR}}; word-break: break-word; }
        .content p { margin: 0 0 14px; }
        .footer { text-align: center; padding: 18px 24px; }
        .footer, .footer a { color: #ffffff; font-size: 12px; line-height: 1.5; text-decoration: none; }
        @media only screen and (max-width: 680px) {
            .wrapper { padding: 12px; }
            .content { padding: 20px; }
            .subject { font-size: 20px; }
        }
    </style>
</head>
<body>
    <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" class="container" width="650" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="header" style="background-color: {{THEME_COLOR}};">
                            {{BRAND_HTML}}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 30px 0;">
                            <h2 class="subject">{{EMAIL_SUBJECT}}</h2>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
HTML;
}

function robodesk_get_default_email_footer_template()
{
    return <<<'HTML'
                        </td>
                    </tr>
                    <tr>
                        <td class="footer" style="background-color: {{THEME_COLOR}};">
                            <div>
                                <a href="{{SITE_URL}}" target="_blank" rel="noopener noreferrer">{{SITE_NAME}}</a>
                            </div>
                            <div>&copy; {{YEAR}} {{SITE_NAME}}. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

function robodesk_get_email_template_parts()
{
    $header_option_key = 'robodesk_email_template_header';
    $footer_option_key = 'robodesk_email_template_footer';

    $header_template = get_option($header_option_key, '');
    if (!is_string($header_template) || trim($header_template) === '') {
        $header_template = robodesk_get_default_email_header_template();
        update_option($header_option_key, $header_template);
    } else {
        $original_header_template = $header_template;

        $header_template = preg_replace(
            '#\.content\s+a\s*\{\s*color\s*:[^;]+;#i',
            '.content a { color: {{THEME_COLOR}};',
            $header_template
        );

        if (strpos($header_template, '{{BRAND_HTML}}') === false) {
            $header_template = preg_replace(
                '#<td([^>]*)class="header"([^>]*)>.*?</td>#is',
                '<td$1class="header"$2>{{BRAND_HTML}}</td>',
                $header_template,
                1
            );
        }

        if (!is_string($header_template) || strpos($header_template, '{{BRAND_HTML}}') === false) {
            $header_template = robodesk_get_default_email_header_template();
        }

        if ($header_template !== $original_header_template) {
            update_option($header_option_key, $header_template);
        }
    }

    $footer_template = get_option($footer_option_key, '');
    if (!is_string($footer_template) || trim($footer_template) === '') {
        $footer_template = robodesk_get_default_email_footer_template();
        update_option($footer_option_key, $footer_template);
    } elseif (strpos($footer_template, '{{THEME_COLOR}}') === false) {
        $original_footer_template = $footer_template;
        $footer_template = preg_replace(
            '#<td([^>]*)class="footer"([^>]*)style="([^"]*)"#i',
            '<td$1class="footer"$2style="background-color: {{THEME_COLOR}}; $3"',
            $footer_template,
            1
        );

        if ($footer_template !== $original_footer_template) {
            update_option($footer_option_key, $footer_template);
        }
    }

    return [
        'header' => $header_template,
        'footer' => $footer_template,
    ];
}

function robodesk_render_email_template($subject, $message)
{
    $site_name = get_bloginfo('name');
    $site_url = get_home_url();
    $year = date_i18n('Y');
    $email_subject = wp_strip_all_tags((string) $subject);
    $email_content = robodesk_prepare_email_content($message);
    $logo_url = esc_url(robodesk_get_email_logo_url());
    $theme_color = robodesk_get_email_theme_color();

    if ($email_subject === '') {
        $email_subject = $site_name;
    }

    $template_parts = robodesk_get_email_template_parts();

    if ($logo_url !== '') {
        $brand_html = '<a href="' . esc_url($site_url) . '" target="_blank" rel="noopener noreferrer"><img src="' . $logo_url . '" alt="' . esc_attr($site_name) . '" /></a>';
    } else {
        $brand_html = '<a href="' . esc_url($site_url) . '" target="_blank" rel="noopener noreferrer" style="text-decoration:none;"><h1 class="subject" style="margin:0;color:#ffffff;">' . esc_html($site_name) . '</h1></a>';
    }

    $replacements = [
        '{{SITE_NAME}}' => esc_html($site_name),
        '{{SITE_NAME_ATTR}}' => esc_attr($site_name),
        '{{SITE_URL}}' => esc_url($site_url),
        '{{YEAR}}' => esc_html($year),
        '{{EMAIL_SUBJECT}}' => esc_html($email_subject),
        '{{LOGO_URL}}' => $logo_url,
        '{{THEME_COLOR}}' => esc_attr($theme_color),
        '{{BRAND_HTML}}' => $brand_html,
    ];

    $email_header = strtr((string) $template_parts['header'], $replacements);
    $email_footer = strtr((string) $template_parts['footer'], $replacements);

    return $email_header . $email_content . $email_footer;
}

function robodesk_send_email($to, $subject, $message, $headers = [], $attachments = [])
{
    if (!is_array($headers)) {
        $headers = [$headers];
    }

    $headers = array_values(array_filter($headers, function ($header) {
        return stripos((string) $header, 'Content-Type:') !== 0;
    }));

    array_unshift($headers, 'Content-Type: text/html; charset=UTF-8');

    $email_template = robodesk_render_email_template($subject, $message);

    // Try to embed logo as a CID attachment when possible for reliable display
    $logo_file = robodesk_get_email_logo_file_path();
    $logo_url = robodesk_get_email_logo_url();

    if ($logo_file !== '') {
        // Ensure PHPMailer classes are available
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            if (file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')) {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            }
            if (file_exists(ABSPATH . WPINC . '/PHPMailer/SMTP.php')) {
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            }
            if (file_exists(ABSPATH . WPINC . '/PHPMailer/Exception.php')) {
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            }
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                // Apply SMTP settings if configured
                if (function_exists('robodesk_apply_smtp_to_phpmailer')) {
                    robodesk_apply_smtp_to_phpmailer($mail);
                }

                // Ensure From is set
                if (empty($mail->From)) {
                    $from_email = get_option('robodesk_smtp_settings', [])['from_email'] ?? get_option('admin_email');
                    $from_name = get_option('robodesk_smtp_settings', [])['from_name'] ?? get_bloginfo('name');
                    $mail->setFrom($from_email, $from_name);
                }

                $mail->isHTML(true);
                $mail->Subject = $subject;

                // Embed the logo and replace URL references with CID
                $cid = 'robodesk_logo_' . md5($logo_file);
                try {
                    $mail->addEmbeddedImage($logo_file, $cid, basename($logo_file));
                    if (!empty($logo_url)) {
                        $email_template = str_replace($logo_url, 'cid:' . $cid, $email_template);
                    }
                } catch (Exception $e) {
                    // ignore embedding errors and continue to send without CID
                }

                $mail->Body = $email_template;
                $mail->AltBody = wp_strip_all_tags($message);

                // Add recipient
                $mail->clearAllRecipients();
                $mail->addAddress($to);

                // Attach files if any (only local file paths)
                if (!empty($attachments) && is_array($attachments)) {
                    foreach ($attachments as $att) {
                        if (is_string($att) && file_exists($att)) {
                            $mail->addAttachment($att);
                        }
                    }
                }

                $sent = $mail->send();
                return (bool) $sent;
            } catch (\Exception $e) {
                rbd_log('PHPMailer send failed: ' . $e->getMessage());
                // Fall back to wp_mail below
            }
        }
    }

    // Fallback to WordPress mail.
    return wp_mail($to, $subject, $email_template, $headers, $attachments);
}
