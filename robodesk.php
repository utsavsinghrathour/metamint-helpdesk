<?php

/**
 * Plugin Name: Robodesk Support Ticket System
 * Plugin URI: https://robodesk.io
 * Description: A support ticket and FAQ system.
 * Version: 1.4.1
 * Author: Code Pixelz Media
 * Author URI: https://codepixelzmedia.com
 * License: GPL2
 * Text Domain: robodesk
 */

// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('ROBODESK_VERSION', '1.4.1');
define('ROBODESK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ROBODESK_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!function_exists('robodesk_datetime_to_utc_iso')) {
    function robodesk_datetime_to_utc_iso($datetime, $is_gmt = false)
    {
        $datetime = is_string($datetime) ? trim($datetime) : $datetime;

        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return '';
        }

        if (is_numeric($datetime)) {
            return gmdate('Y-m-d\TH:i:s\Z', (int) $datetime);
        }

        try {
            if ($is_gmt) {
                $date = new DateTimeImmutable((string) $datetime, new DateTimeZone('UTC'));
            } else {
                $date = new DateTimeImmutable((string) $datetime, wp_timezone());
            }
        } catch (Exception $e) {
            $timestamp = strtotime((string) $datetime);
            return $timestamp ? gmdate('Y-m-d\TH:i:s\Z', $timestamp) : '';
        }

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}

if (!function_exists('robodesk_comment_utc_iso')) {
    function robodesk_comment_utc_iso($comment)
    {
        $comment = is_numeric($comment) ? get_comment((int) $comment) : $comment;

        if (!$comment) {
            return '';
        }

        if (!empty($comment->comment_date_gmt) && $comment->comment_date_gmt !== '0000-00-00 00:00:00') {
            return robodesk_datetime_to_utc_iso($comment->comment_date_gmt, true);
        }

        if (!empty($comment->comment_date) && $comment->comment_date !== '0000-00-00 00:00:00') {
            return robodesk_datetime_to_utc_iso($comment->comment_date, false);
        }

        return '';
    }
}

if (!function_exists('robodesk_post_utc_iso')) {
    function robodesk_post_utc_iso($post, $modified = false)
    {
        $post = is_numeric($post) ? get_post((int) $post) : $post;

        if (!$post) {
            return '';
        }

        $gmt_field = $modified ? 'post_modified_gmt' : 'post_date_gmt';
        if (!empty($post->$gmt_field) && $post->$gmt_field !== '0000-00-00 00:00:00') {
            return robodesk_datetime_to_utc_iso($post->$gmt_field, true);
        }

        $local_field = $modified ? 'post_modified' : 'post_date';
        if (!empty($post->$local_field) && $post->$local_field !== '0000-00-00 00:00:00') {
            return robodesk_datetime_to_utc_iso($post->$local_field, false);
        }

        return '';
    }
}

// Activation/Deactivation Hooks
register_activation_hook(__FILE__, 'robodesk_activate');
register_deactivation_hook(__FILE__, 'robodesk_deactivate');

add_filter('user_has_cap', function ($allcaps, $caps, $args) {
    $robodesk_caps = [
        'robodesk_access_dashboard',
        'create_tickets',
        'edit_tickets',
        'edit_others_tickets',
        'publish_tickets',
        'read_private_tickets',
        'read_ticket',
        'delete_ticket',
    ];
    if (!empty($args[0]) && in_array($args[0], $robodesk_caps, true)) {
        if (!empty($allcaps['manage_options']) || !empty($allcaps['edit_others_posts'])) {
            $allcaps[$args[0]] = true;
        }
    }
    return $allcaps;
}, 10, 3);

require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-shortcodes.php';

function robodesk_activate()
{
    // Ensure WordPress functions are loaded
    if (!function_exists('add_role')) {
        require_once ABSPATH . 'wp-includes/capabilities.php';
    }

    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('create_tickets');
        $admin_role->add_cap('edit_tickets');
        $admin_role->add_cap('edit_others_tickets');
        $admin_role->add_cap('publish_tickets');
        $admin_role->add_cap('read_private_tickets');
        $admin_role->add_cap('read_ticket');
        $admin_role->add_cap('delete_ticket');
        $admin_role->add_cap('robodesk_access_dashboard');
    }

    $editor_role = get_role('editor');
    if ($editor_role) {
        $editor_role->add_cap('create_tickets');
        $editor_role->add_cap('edit_tickets');
        $editor_role->add_cap('edit_others_tickets');
        $editor_role->add_cap('publish_tickets');
        $editor_role->add_cap('read_private_tickets');
        $editor_role->add_cap('read_ticket');
        $editor_role->add_cap('delete_ticket');
        $editor_role->add_cap('robodesk_access_dashboard');
    }

    add_role('robodesk_support_manager', 'Support Manager', array('read' => true, 'manage_options' => true, 'edit_posts' => true, 'manage_categories' => true, 'robodesk_access_dashboard' => true, 'create_tickets' => true, 'edit_tickets' => true, 'edit_others_tickets' => true, 'publish_tickets' => true, 'read_private_tickets' => true, 'read_ticket' => true, 'delete_ticket' => true));

    add_role('robodesk_support_staff', 'Support Staff', array(
        'read' => true,
        'edit_posts' => true,
        'manage_categories' => true,
        'robodesk_access_dashboard' => true,
        'create_tickets' => true,
        'edit_tickets' => true,
        'edit_others_tickets' => true,
        'read_ticket' => true
    ));

    add_role('robodesk_support_customer', 'Support Customer', array('read' => true, 'create_tickets' => true, 'read_ticket' => true));
}

function robodesk_deactivate()
{
    remove_role('robodesk_support_manager');
    remove_role('robodesk_support_staff');
    remove_role('robodesk_support_customer');

    // Clear the email fetch cron event
    wp_clear_scheduled_hook('robodesk_email_fetch_cron');
}

/**
 * Initialize debug log file
 * Creates logs directory and debug.log file if they don't exist
 */
function robodesk_init_debug_log()
{
    $upload_dir = wp_upload_dir();
    $logs_dir = $upload_dir['basedir'] . '/robodesk';
    $debug_log = $logs_dir . '/debug.log';

    if (!is_dir($logs_dir)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
        if (!mkdir($logs_dir, 0755, true)) {
            rbd_log('Failed to create Robodesk logs directory');
            return false;
        }
    }

    if (!file_exists($debug_log)) {
        $initial_content = "[DEBUG LOG]\n";

        if (!file_put_contents($debug_log, $initial_content)) {
            rbd_log('Failed to create Robodesk debug.log file');
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        chmod($debug_log, 0644);
    }

    return true;
}

// Call debug log initialization on plugin load
robodesk_init_debug_log();

// Include necessary files
// if (is_admin()) {
require_once ROBODESK_PLUGIN_PATH . 'admin/robodesk-admin.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-dashboard.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-fetch-tickets.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-functions.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-functions.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-shortcodes.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-ajax.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-notes.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-custom-rest.php';

require_once ROBODESK_PLUGIN_PATH . 'admin/includes/rbodesk-admin-rest.php';


// }

// Enqueue Scripts
add_action('wp_enqueue_scripts', 'robodesk_enqueue_scripts');

function robodesk_get_default_staff_id()
{
    $id = get_option('robodesk_default_agent', '');
    if (empty($id)) {
        $id = get_option('robodesk_default_staff', '');
    }
    return $id ? (int) $id : 0;
}

function robodesk_enqueue_scripts()
{

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue styles (already existing)
    wp_enqueue_style('robodesk-styles', ROBODESK_PLUGIN_URL . 'public/assets/css/robodesk-styles.css', array(), ROBODESK_VERSION);

    // Enqueue scripts (already existing)
    wp_enqueue_script('robodesk-scripts', ROBODESK_PLUGIN_URL . 'public/assets/js/robodesk-scripts.js', array('jquery'), ROBODESK_VERSION, true);

    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'robodesk_enqueue_admin_scripts');
    } else {
        // Enqueue TinyMCE for frontend
        wp_enqueue_script('tinymce');

        // Re-enqueue styles (already existing, adjust if needed)
        wp_enqueue_style('robodesk-styles', ROBODESK_PLUGIN_URL . 'public/assets/css/robodesk-styles.css', array(), ROBODESK_VERSION);
        // Re-enqueue scripts with TinyMCE dependency
        wp_enqueue_script('robodesk-scripts', ROBODESK_PLUGIN_URL . 'public/assets/js/robodesk-scripts.js', array('jquery', 'tinymce', 'wp-api', 'wp-data', 'wp-core-data'), ROBODESK_VERSION, true);

        // Existing wp_localize_script for AJAX
        wp_localize_script('robodesk-scripts', 'robodesk_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('robodesk_change_ticket_status_nonce'),
            'create_ticket_nonce' => wp_create_nonce('robodesk_create_ticket_nonce'),
            'add_ticket_note_nonce' => wp_create_nonce('robodesk_add_ticket_note_nonce'),
            'faq_nonce' => wp_create_nonce('robodesk_faq_vote_nonce'),
            'add_credential_nonce' => wp_create_nonce('robodesk_add_credential_nonce'),
            'delete_credential_nonce' => wp_create_nonce('robodesk_delete_credential_nonce'),
            'reveal_credential_nonce' => wp_create_nonce('robodesk_reveal_credential_nonce'),
            'notifications_nonce' => wp_create_nonce('robodesk_notifications_action'),


        ));

        // Enqueue React script
        $asset_file = file_exists(ROBODESK_PLUGIN_PATH . 'public/build/index.asset.php')
            ? include ROBODESK_PLUGIN_PATH . 'public/build/index.asset.php'
            : array('dependencies' => array('wp-element'), 'version' => '1.0.6');

        wp_enqueue_script(
            'robodesk-react-script',
            ROBODESK_PLUGIN_URL . 'public/build/index.js',
            array_merge(array('jquery'), $asset_file['dependencies']),
            ROBODESK_VERSION,
            true
        );

        $deep_link_ticket_id = robodesk_get_requested_ticket_id_from_query();

        // Pass image URL (logo only for now)
        $image_urls = [
            'popover_icon' => esc_url(get_option('robodesk_popover_icon', '')),
            'logo' => esc_url(get_option('robodesk_logo', '')),
            'popover_top_icon' => esc_url(get_option('robodesk_popover_top_icon', '')),
            'wide_logo' => esc_url(get_option('robodesk_wide_logo', '')),
        ];
        wp_localize_script(
            'robodesk-react-script',
            'robodeskData',
            [
                'imageUrls' => $image_urls,
                'chatHeading' => get_option('robodesk_chat_heading', 'Have Questions?'),
                'welcomeMessage' => get_option('robodesk_welcome_message', 'Have any questions or need a quote? Leave us a message.'),
                'ticketIntroMessage' => get_option('robodesk_ticket_intro_message', 'If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!'),
                'deepLinkTicketId' => $deep_link_ticket_id,
                'currentUserAvatar' => is_user_logged_in() ? esc_url(robodesk_get_avatar_url(get_current_user_id(), 32)) : '',
                // Bot configuration for avatar/title used in ticket/chat rendering
                'robodesk_bot_profile_image' => esc_url(get_option('robodesk_bot_profile_image', '')),
                'robodesk_bot_title_name' => get_option('robodesk_bot_title_name', ''),
                'robodesk_addon_active' => defined('ROBODESK_ADDON_VERSION'),
                'robodesk_ai_bot_active' => get_option('robodesk_addon_ai_bot_status', 'inactive') === 'active',
                'enable_chat' => get_option('robodesk_enable_chat', '1'),
            ]
        );

        // Build priority colors array
        $priority_terms = get_terms(array(
            'taxonomy' => 'robodesk_ticket_priority',
            'hide_empty' => false,
        ));
        $priority_colors = [];
        if ($priority_terms && !is_wp_error($priority_terms)) {
            foreach ($priority_terms as $term) {
                $color = get_term_meta($term->term_id, 'priority_color', true);
                $priority_colors[$term->name] = $color ?: '#000000';
            }
        }

        wp_localize_script('robodesk-react-script', 'robodeskAuth', array(
            'isLoggedIn' => is_user_logged_in() ? 'true' : 'false',
            'loggedInUserId' => is_user_logged_in() ? get_current_user_id() : 'false',

            'notificationSound' => esc_url(get_option('robodesk_notification_sound', '')),
            'priorityTerms' => $priority_terms,
            'priorityColors' => $priority_colors,

            'defaultStaffId' => robodesk_get_default_staff_id(),

            'restNonce' => wp_create_nonce('wp_rest'),

            'status' => [
                'active-conversations' => 'Active Conversations',
                'open' => 'Open',
                'in-progress' => 'In Progress',
                'archived' => 'Archived',
                'closed' => 'Closed',
            ],
            'wpAjax' => [
                'nonce' => wp_create_nonce('robodesk-nonce'),
                'passwordResetNonce' => wp_create_nonce('password-reset'),
                'ajaxUrl' => admin_url('admin-ajax.php'),

            ],
        ));

        // Supply wpApiSettings for @wordpress/api-fetch nonce middleware
        if (!wp_script_is('wp-api', 'enqueued')) {
            wp_enqueue_script('wp-api');
        }

        // Enqueue React styles
        wp_enqueue_style(
            'robodesk-react-style',
            ROBODESK_PLUGIN_URL . 'public/build/index.css',
            array(),
            $asset_file['version']
        );
    }
}
add_action('wp_enqueue_scripts', 'robodesk_enqueue_scripts');

function robodesk_add_plugin()
{
    echo '<div id="robodesk-plugin"></div>';
}
add_action('wp_footer', 'robodesk_add_plugin');
function robodesk_handle_settings_save()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'robodesk_save_settings')) {
        return;
    }

    // Handle logo upload
    if (!empty($_FILES['logo']['name'])) {
        $upload = wp_handle_upload($_FILES['logo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('robodesk_logo', $upload['url']);
        }
    }

    // Handle popover icon upload
    if (!empty($_FILES['popover_icon']['name'])) {
        $upload = wp_handle_upload($_FILES['popover_icon'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('robodesk_popover_icon', $upload['url']);
        }
    }
    // popup top image
    if (!empty($_FILES['popover_top_icon']['name'])) {
        $upload = wp_handle_upload($_FILES['popover_top_icon'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('robodesk_popover_top_icon', $upload['url']);
        }
    }
    // wide logo
    if (!empty($_FILES['wide_logo']['name'])) {
        $upload = wp_handle_upload($_FILES['wide_logo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('robodesk_wide_logo', $upload['url']);
        }
    }
}
add_action('admin_post_robodesk_save_settings', 'robodesk_handle_settings_save');



/**
 * Enqueues admin-specific scripts and styles for the Robodesk plugin.
 *
 * This function is hooked into the admin_enqueue_scripts action and ensures
 * that scripts and styles are only loaded on the post.php and post-new.php
 * pages for the 'robodesk_ticket' post type.
 *
 * @param string $hook The current admin page hook suffix.
 */

function robodesk_enqueue_admin_scripts($hook)
{
    global $post;
    wp_enqueue_script('jquery');
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field(wp_unslash($_GET['taxonomy'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

    if ($taxonomy === 'robodesk_ticket_priority' && $post_type === 'robodesk_ticket') {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('robodesk-custom-taxonomy-order', ROBODESK_PLUGIN_URL . 'admin/assets/js/custom-taxonomy-order.js', array('jquery', 'jquery-ui-sortable'), ROBODESK_VERSION, true);
        wp_localize_script('robodesk-custom-taxonomy-order', 'taxonomyOrderData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('taxonomy_order_nonce')
        ));
    }

    if ('post.php' != $hook && 'post-new.php' != $hook)
        return;
    if (!isset($post) || $post->post_type != 'robodesk_ticket')
        return;

    wp_enqueue_style('robodesk-admin-styles', ROBODESK_PLUGIN_URL . 'admin/assets/css/robodesk-admin-styles.css', array(), '1.0');

    wp_enqueue_script('robodesk-admin-scripts', ROBODESK_PLUGIN_URL . 'admin/assets/js/robodesk-admin-scripts.js', array('jquery'), '1.0', true);
    wp_localize_script('robodesk-admin-scripts', 'robodesk_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'ticket_id' => $post->ID, 'nonce' => wp_create_nonce('robodesk_change_ticket_nonce'), 'faq_nonce' => wp_create_nonce('robodesk_faq_vote_nonce'), 'credentials_nonce' => wp_create_nonce('robodesk_credentials_nonce')));
}
add_action('admin_enqueue_scripts', 'robodesk_enqueue_admin_scripts');


//@utsav sir code dont remove now
// function robodesk_create_credentials_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'robodesk_credentials';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         user_id BIGINT(20) UNSIGNED NOT NULL,
//         name VARCHAR(255) NOT NULL,
//         username VARCHAR(255) NOT NULL,
//         password VARCHAR(255) NOT NULL,
//         url VARCHAR(255),
//         notes TEXT,
//         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
//         PRIMARY KEY (id),
//         KEY user_id (user_id),
//         CONSTRAINT fk_robodesk_credentials_user FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// }
// register_activation_hook(__FILE__, 'robodesk_create_credentials_table');



// Function to create the table
function robodesk_create_credentials_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'robodesk_credentials'; // Add WordPress table prefix
    $charset_collate = $wpdb->get_charset_collate();


    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL,
        password LONGTEXT NOT NULL,
        url VARCHAR(255),
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        CONSTRAINT fk_robodesk_credentials_user FOREIGN KEY (user_id) 
            REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to run the function on plugin activation
register_activation_hook(__FILE__, 'robodesk_create_credentials_table');

function robodesk_maybe_create_credentials_table()
{
    $version = get_option('robodesk_credentials_table_version');
    if ($version !== '1.1') {
        robodesk_create_credentials_table();
        update_option('robodesk_credentials_table_version', '1.1');
    }
}

add_action('init', 'robodesk_maybe_create_credentials_table');

// Create notifications table
function robodesk_create_notifications_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'robodesk_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        notification_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        user_id bigint(20) UNSIGNED NOT NULL,
        message text NOT NULL,
        ticket_id bigint(20) UNSIGNED DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'unread',
        created_by bigint(20) UNSIGNED NOT NULL,
        recipient_user_id bigint(20) UNSIGNED NOT NULL,
        comment_id bigint(20) UNSIGNED DEFAULT NULL,
        type varchar(32) NOT NULL,
        source varchar(32) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        read_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY recipient_user_id (recipient_user_id),
        KEY ticket_id (ticket_id),
        KEY comment_id (comment_id),
        KEY read_at (read_at),
        KEY user_id (user_id),
        KEY created_by (created_by),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'robodesk_create_notifications_table');

function robodesk_maybe_create_notifications_table()
{
    $version = get_option('robodesk_notifications_table_version');
    if ($version !== '1.1') {
        robodesk_create_notifications_table();
        update_option('robodesk_notifications_table_version', '1.1');
    }
}
add_action('init', 'robodesk_maybe_create_notifications_table');



function robodesk_get_encryption_key()
{
    if (defined('ROBODESK_ENCRYPTION_KEY') && ROBODESK_ENCRYPTION_KEY !== '') {
        return ROBODESK_ENCRYPTION_KEY;
    }

    return wp_hash('robodesk-encryption-key');
}

function robodesk_encrypt($plaintext, $key = null)
{
    if ($key === null) {
        $key = robodesk_get_encryption_key();
    }

    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options = 0, $iv);
    return base64_encode($ciphertext . '::' . $iv);
}


function robodesk_decrypt($ciphertext, $key = null)
{
    if ($key === null) {
        $key = robodesk_get_encryption_key();
    }

    $decoded = base64_decode($ciphertext, true);
    if ($decoded === false || strpos($decoded, '::') === false) {
        return false;
    }

    list($encrypted_data, $iv) = explode('::', $decoded, 2);
    $plaintext = openssl_decrypt($encrypted_data, "AES-256-CBC", $key, $options = 0, $iv);

    if ($plaintext === false && defined('AUTH_KEY') && AUTH_KEY !== '') {
        $plaintext = openssl_decrypt($encrypted_data, "AES-256-CBC", AUTH_KEY, $options = 0, $iv);
    }

    return $plaintext;
}

function robodesk_build_ticket_deeplink($ticket_id)
{
    $ticket_id = absint($ticket_id);
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return home_url('/');
    }

    $encrypted_ticket = robodesk_encrypt((string) $ticket_id, 'robodesk');

    return add_query_arg(
        [
            'rbd_popup' => 'true',
            'initiator' => 'robodesk',
            'rtk' => $encrypted_ticket,
        ],
        home_url('/')
    );
}

function robodesk_user_can_access_ticket($ticket_id)
{
    if (!is_user_logged_in()) {
        return true;
    }

    $ticket_id = absint($ticket_id);
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return false;
    }

    $current_user_id = get_current_user_id();
    $customer_user_id = (int) robodesk_get_ticket_customer_user_id($ticket_id);

    if ($customer_user_id > 0 && $current_user_id === $customer_user_id) {
        return true;
    }

    if (current_user_can('robodesk_access_dashboard')) {
        return true;
    }

    return in_array($current_user_id, robodesk_get_support_user_ids(), true);
}

function robodesk_get_requested_ticket_id_from_query()
{
    $ticket_id = 0;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['rtk'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $encrypted_ticket = sanitize_text_field(wp_unslash($_GET['rtk']));
        $encrypted_ticket = trim((string) $encrypted_ticket);
        if ($encrypted_ticket !== '') {
            $encrypted_ticket = str_replace(' ', '+', $encrypted_ticket);
            $decrypted_ticket = robodesk_decrypt($encrypted_ticket, 'robodesk');
            if ($decrypted_ticket !== false) {
                $ticket_id = absint($decrypted_ticket);
            }
        }
    }

    if ($ticket_id <= 0) {
        foreach (['ticket_id', 'ticket', 'tid'] as $key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[$key])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $ticket_id = absint(wp_unslash($_GET[$key]));
                if ($ticket_id > 0) {
                    break;
                }
            }
        }
    }

    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return 0;
    }

    if (!robodesk_user_can_access_ticket($ticket_id)) {
        return 0;
    }

    return $ticket_id;
}


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function rbd_log($message)
{
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/robodesk/debug.log';
    $log_dir  = dirname($log_file);

    if (!file_exists($log_dir)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
        mkdir($log_dir, 0755, true);
    }

    if (is_array($message) || is_object($message)) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        $message = print_r($message, true);
    }

    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log(wp_date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);

    if (function_exists('robodesk_prune_debug_log')) {
        robodesk_prune_debug_log($log_file, 30);
    }
}

if (!function_exists('robodesk_prune_debug_log')) {
    function robodesk_prune_debug_log($log_file = '', $days = 30)
    {
        if ($log_file === '') {
            $upload_dir = wp_upload_dir();
            $log_file   = $upload_dir['basedir'] . '/robodesk/debug.log';
        }

        if (!file_exists($log_file) || !is_readable($log_file)) {
            return false;
        }

        $contents = file_get_contents($log_file);
        if ($contents === false || $contents === '') {
            return false;
        }

        $lines = preg_split('/\r\n|\r|\n/', $contents);
        if (!is_array($lines) || empty($lines)) {
            return false;
        }

        $cutoff = current_time('timestamp') - (max(1, (int) $days) * DAY_IN_SECONDS);
        $preamble = [];
        $kept_entries = [];
        $current_entry = [];
        $current_timestamp = null;
        $seen_timestamp = false;

        foreach ($lines as $line) {
            $line = rtrim((string) $line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $seen_timestamp = true;

                if (!empty($current_entry) && $current_timestamp !== null && $current_timestamp >= $cutoff) {
                    $kept_entries[] = implode(PHP_EOL, $current_entry);
                }

                $timestamp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $matches[1], wp_timezone());
                $current_timestamp = $timestamp ? $timestamp->getTimestamp() : strtotime($matches[1]);
                $current_entry = [$line];
                continue;
            }

            if (!$seen_timestamp) {
                $preamble[] = $line;
                continue;
            }

            if (!empty($current_entry)) {
                $current_entry[] = $line;
            }
        }

        if (!empty($current_entry) && $current_timestamp !== null && $current_timestamp >= $cutoff) {
            $kept_entries[] = implode(PHP_EOL, $current_entry);
        }

        $new_contents = [];
        if (!empty($preamble)) {
            $new_contents[] = implode(PHP_EOL, $preamble);
        }

        if (!empty($kept_entries)) {
            $new_contents[] = implode(PHP_EOL, $kept_entries);
        }

        if (empty($new_contents)) {
            return false;
        }

        $final_contents = implode(PHP_EOL, $new_contents) . PHP_EOL;
        file_put_contents($log_file, $final_contents, LOCK_EX);

        return true;
    }
}

function robodesk_get_support_user_ids()
{
    $users = get_users([
        'role__in' => [
            'administrator',
            'editor',
            'robodesk_support_manager',
            'robodesk_support_staff',
        ],
        'fields' => ['ID'],
    ]);

    return array_values(array_unique(array_map(function ($user) {
        return (int) $user->ID;
    }, $users)));
}

function robodesk_resolve_user_id_from_assignee($assignee_name)
{
    $assignee_name = trim((string) $assignee_name);
    if ($assignee_name === '') {
        return 0;
    }

    if (is_numeric($assignee_name)) {
        return (int) $assignee_name;
    }

    $assignee_lower = strtolower($assignee_name);
    if ($assignee_lower === 'n/a' || $assignee_lower === 'unassigned') {
        return 0;
    }

    $user = get_user_by('display_name', $assignee_name);
    if ($user && $user->ID) {
        return (int) $user->ID;
    }

    $user = get_user_by('login', $assignee_name);
    if ($user && $user->ID) {
        return (int) $user->ID;
    }

    $assignee_key = strtolower($assignee_name);
    $candidates = get_users([
        'search' => '*' . $assignee_name . '*',
        'search_columns' => ['display_name', 'user_login'],
        'fields' => ['ID', 'display_name', 'user_login'],
        'number' => 20,
    ]);

    foreach ($candidates as $candidate) {
        if (strtolower((string) $candidate->display_name) === $assignee_key) {
            return (int) $candidate->ID;
        }
        if (strtolower((string) $candidate->user_login) === $assignee_key) {
            return (int) $candidate->ID;
        }
    }

    return 0;
}

function robodesk_backfill_ticket_assignee_ids()
{
    if (get_option('robodesk_assignee_id_backfill_done') === '1') {
        return;
    }

    $query = new WP_Query([
        'post_type' => 'robodesk_ticket',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'ticket_assignee',
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'ticket_assignee_id',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);

    if (!empty($query->posts)) {
        foreach ($query->posts as $ticket_id) {
            $assignee_name = get_post_meta($ticket_id, 'ticket_assignee', true);
            robodesk_store_ticket_assignee_id($ticket_id, $assignee_name);
        }
    }

    update_option('robodesk_assignee_id_backfill_done', '1');
}
add_action('init', 'robodesk_backfill_ticket_assignee_ids');

function robodesk_store_ticket_assignee_id($ticket_id, $assignee_name)
{
    $assignee_id = robodesk_resolve_user_id_from_assignee($assignee_name);
    if ($assignee_id > 0) {
        update_post_meta($ticket_id, 'ticket_assignee_id', $assignee_id);
    }

    return $assignee_id;
}

function robodesk_get_ticket_source($ticket_id)
{
    $origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true);
    if (empty($origin)) {
        $origin_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_origin');
        if (!is_wp_error($origin_terms) && !empty($origin_terms)) {
            $origin = $origin_terms[0]->name;
        }
    }

    $origin = strtolower((string) $origin);
    if (strpos($origin, 'email') !== false) {
        return 'email';
    }
    if (strpos($origin, 'admin') !== false) {
        return 'admin';
    }
    if (strpos($origin, 'web') !== false) {
        return 'web';
    }
    if (strpos($origin, 'chat') !== false) {
        return 'chat';
    }

    return 'chat';
}

function robodesk_get_request_header_values($request = null)
{
    $headers = array();

    if ($request instanceof WP_REST_Request) {
        foreach ($request->get_headers() as $header_name => $header_value) {
            $headers[strtolower((string) $header_name)] = is_array($header_value) ? implode(', ', $header_value) : (string) $header_value;
        }
    }

    $server_header_map = array(
        'HTTP_CF_CONNECTING_IP' => 'cf-connecting-ip',
        'HTTP_X_FORWARDED_FOR' => 'x-forwarded-for',
        'HTTP_X_REAL_IP' => 'x-real-ip',
        'HTTP_X_CLIENT_IP' => 'x-client-ip',
        'HTTP_CF_IPCOUNTRY' => 'cf-ipcountry',
        'HTTP_X_VERCEL_IP_COUNTRY' => 'x-vercel-ip-country',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY' => 'cloudfront-viewer-country',
        'HTTP_USER_AGENT' => 'user-agent',
        'HTTP_X_MAILER' => 'x-mailer',
        'REMOTE_ADDR' => 'remote-addr',
    );

    foreach ($server_header_map as $server_key => $header_key) {
        if (!empty($_SERVER[$server_key])) {
            $headers[$header_key] = sanitize_text_field(wp_unslash((string) $_SERVER[$server_key]));
        }
    }

    return $headers;
}

function robodesk_get_first_ip_from_value($value)
{
    $candidates = preg_split('/\s*,\s*/', (string) $value);
    if (!is_array($candidates)) {
        $candidates = array($value);
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate, " \t\n\r\0\x0B[]");
        if ($candidate !== '' && robodesk_is_public_ip($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function robodesk_is_public_ip($ip)
{
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function robodesk_get_client_ip()
{
    $headers = robodesk_get_request_header_values();
    $priority = array('cf-connecting-ip', 'x-forwarded-for', 'x-real-ip', 'x-client-ip', 'remote-addr');
    foreach ($priority as $key) {
        if (!empty($headers[$key])) {
            $ip = robodesk_get_first_ip_from_value($headers[$key]);
            if ($ip !== '') {
                return $ip;
            }
        }
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return '0.0.0.0';
}

function robodesk_parse_browser_label($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $rules = array(
        array('label' => 'Edge', 'regex' => '/\b(?:edg|edge)\/([0-9\.]+)/i'),
        array('label' => 'Opera', 'regex' => '/\b(?:opr|opera)\/([0-9\.]+)/i'),
        array('label' => 'Chrome', 'regex' => '/\b(?:chrome|crios)\/([0-9\.]+)/i'),
        array('label' => 'Firefox', 'regex' => '/\b(?:firefox|fxios)\/([0-9\.]+)/i'),
        array('label' => 'Safari', 'regex' => '/\bversion\/([0-9\.]+).*\bsafari\//i'),
        array('label' => 'Internet Explorer', 'regex' => '/\bmsie\s([0-9\.]+)/i'),
        array('label' => 'Internet Explorer', 'regex' => '/\brv:([0-9\.]+)\) like gecko/i'),
    );

    foreach ($rules as $rule) {
        if (preg_match($rule['regex'], $value, $matches)) {
            $version = isset($matches[1]) ? trim((string) $matches[1]) : '';
            return $version !== '' ? $rule['label'] . ' ' . $version : $rule['label'];
        }
    }

    return $value;
}

function robodesk_collect_source_context($request = null, $overrides = array())
{
    $headers = robodesk_get_request_header_values($request);
    foreach ($overrides as $key => $value) {
        if ($value !== null && $value !== '') {
            $headers[strtolower((string) $key)] = (string) $value;
        }
    }

    $ip = '';
    foreach (array('cf-connecting-ip', 'x-forwarded-for', 'x-real-ip', 'x-client-ip', 'remote-addr') as $header_key) {
        if (empty($headers[$header_key])) {
            continue;
        }

        $candidate_ip = robodesk_get_first_ip_from_value($headers[$header_key]);
        if ($candidate_ip !== '') {
            $ip = $candidate_ip;
            break;
        }
    }

    if ($ip === '' && !empty($headers['forwarded'])) {
        if (preg_match_all('/for=(?:"?\[?)([a-f0-9:\.]+)(?:\]?"?)/i', (string) $headers['forwarded'], $matches)) {
            foreach ($matches[1] as $candidate_ip) {
                $candidate_ip = trim((string) $candidate_ip, " \t\n\r\0\x0B[]\"");
                if (robodesk_is_public_ip($candidate_ip)) {
                    $ip = $candidate_ip;
                    break;
                }
            }
        }
    }

    $country = '';
    foreach (array('cf-ipcountry', 'x-vercel-ip-country', 'cloudfront-viewer-country', 'x-country-code') as $header_key) {
        if (!empty($headers[$header_key])) {
            $country = strtoupper(trim((string) $headers[$header_key]));
            break;
        }
    }

    $browser_source = '';
    foreach (array('user-agent', 'x-mailer') as $header_key) {
        if (!empty($headers[$header_key])) {
            $browser_source = (string) $headers[$header_key];
            break;
        }
    }

    return array(
        'ip' => $ip,
        'country' => $country,
        'browser' => robodesk_parse_browser_label($browser_source),
    );
}

function robodesk_collect_email_source_context($raw_headers)
{
    $headers = array();
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw_headers);
    if (!is_array($lines)) {
        $lines = array();
    }

    foreach ($lines as $line) {
        if (strpos($line, ':') === false) {
            continue;
        }

        list($name, $value) = explode(':', $line, 2);
        $name = strtolower(trim((string) $name));
        $value = trim((string) $value);
        if ($name === '') {
            continue;
        }

        if (!isset($headers[$name])) {
            $headers[$name] = $value;
        } else {
            $headers[$name] .= ', ' . $value;
        }
    }

    $ip_candidates = array();
    foreach (array('x-originating-ip', 'x-client-ip', 'x-forwarded-for') as $header_key) {
        if (!empty($headers[$header_key])) {
            $ip_candidates[] = $headers[$header_key];
        }
    }

    if (!empty($headers['received'])) {
        $ip_candidates[] = $headers['received'];
    }

    $ip = '';
    foreach ($ip_candidates as $candidate_value) {
        if (preg_match_all('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', (string) $candidate_value, $matches)) {
            foreach ($matches[0] as $candidate_ip) {
                if (filter_var($candidate_ip, FILTER_VALIDATE_IP)) {
                    $ip = $candidate_ip;
                    break 2;
                }
            }
        }
    }

    $browser_source = '';
    foreach (array('user-agent', 'x-mailer') as $header_key) {
        if (!empty($headers[$header_key])) {
            $browser_source = (string) $headers[$header_key];
            break;
        }
    }

    $country = '';
    if (!empty($headers['x-originating-country'])) {
        $country = strtoupper(trim((string) $headers['x-originating-country']));
    } elseif (!empty($headers['x-country-code'])) {
        $country = strtoupper(trim((string) $headers['x-country-code']));
    }

    return array(
        'ip' => $ip,
        'country' => $country,
        'browser' => robodesk_parse_browser_label($browser_source),
    );
}

function robodesk_store_ticket_source_meta($ticket_id, $context, $comment_id = 0)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0 || !is_array($context)) {
        return array();
    }

    $meta_map = array(
        'ip' => '_robodesk_source_ip',
        'country' => '_robodesk_source_country',
        'browser' => '_robodesk_source_browser',
    );

    $stored = array();
    foreach ($meta_map as $context_key => $meta_key) {
        $value = isset($context[$context_key]) ? trim((string) $context[$context_key]) : '';
        if ($value === '') {
            continue;
        }

        update_post_meta($ticket_id, $meta_key, $value);
        if ((int) $comment_id > 0) {
            update_comment_meta((int) $comment_id, $meta_key, $value);
        }
        $stored[$context_key] = $value;
    }

    return $stored;
}

function robodesk_get_ticket_source_meta($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return array(
            'ip' => '',
            'country' => '',
            'browser' => '',
        );
    }

    $ip = trim((string) get_post_meta($ticket_id, '_robodesk_source_ip', true));
    if ($ip !== '' && !robodesk_is_public_ip($ip)) {
        $ip = '';
    }

    $country = trim((string) get_post_meta($ticket_id, '_robodesk_source_country', true));
    $browser = trim((string) get_post_meta($ticket_id, '_robodesk_source_browser', true));

    return array(
        'ip' => $ip,
        'country' => $country,
        'browser' => $browser,
    );
}

function robodesk_get_author_type($user_id)
{
    $user_id = (int) $user_id;
    // Bot messages are stored with user ID 0 or less
    if ($user_id <= 0) {
        return 'bot';
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return 'customer';
    }

    $roles = (array) $user->roles;
    if (
        user_can($user_id, 'robodesk_access_dashboard') ||
        in_array('administrator', $roles, true) ||
        in_array('editor', $roles, true) ||
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true)
    ) {
        return 'agent';
    }

    return 'customer';
}

function robodesk_get_agent_source_by_user($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return '';
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }

    $roles = (array) $user->roles;
    if (in_array('administrator', $roles, true)) {
        return 'admin';
    }

    if (in_array('editor', $roles, true)) {
        return 'editor';
    }

    if (
        in_array('robodesk_support_manager', $roles, true) ||
        in_array('robodesk_support_staff', $roles, true)
    ) {
        return 'staff';
    }

    return '';
}

function robodesk_get_ticket_customer_user_id($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return 0;
    }

    $ticket_author_id = (int) get_post_field('post_author', $ticket_id);
    if ($ticket_author_id > 0 && robodesk_get_author_type($ticket_author_id) === 'customer') {
        return $ticket_author_id;
    }

    $customer_email = trim((string) get_post_meta($ticket_id, '_robodesk_customer_email', true));
    if ($customer_email !== '' && is_email($customer_email)) {
        $email_user = get_user_by('email', $customer_email);
        if ($email_user && !empty($email_user->ID)) {
            return (int) $email_user->ID;
        }
    }

    return 0;
}

function robodesk_get_avatar_url($user_id, $size = 96)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return '';
    }

    $avatar_id = (int) get_user_meta($user_id, 'wp_user_avatar', true);
    if ($avatar_id > 0) {
        $avatar_url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
        if ($avatar_url) {
            return $avatar_url;
        }
    }

    return get_avatar_url($user_id, array('size' => (int) $size));
}

function robodesk_get_default_avatar_url($size = 96)
{
    return get_avatar_url('', array(
        'size' => (int) $size,
        'default' => 'mystery',
        'force_default' => true,
    ));
}

function robodesk_get_bot_display_name()
{
    $bot_name = trim((string) get_option('robodesk_bot_title_name', ''));

    return $bot_name !== '' ? $bot_name : __('Bot', 'robodesk');
}

function robodesk_get_bot_avatar_url($size = 96)
{
    $bot_image = trim((string) get_option('robodesk_bot_profile_image', ''));

    return $bot_image !== '' ? esc_url($bot_image) : robodesk_get_default_avatar_url($size);
}

function robodesk_is_bot_comment($comment_id)
{
    $comment_id = (int) $comment_id;
    $val = get_comment_meta($comment_id, 'is_bot_response', true);
    if ($val === '0' || $val === 0 || $val === false) {
        return false;
    }
    if ($val === '' || $val === null) {
        $comment_source = get_comment_meta($comment_id, 'comment_source', true);
        $author_type = get_comment_meta($comment_id, 'author_type', true);
        $comment = get_comment($comment_id);
        if ($comment) {
            $comment_text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $comment->comment_content)));
            $ticket_text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) get_post_field('post_content', $comment->comment_post_ID))));
            $ticket_title = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) get_the_title($comment->comment_post_ID))));
            if (
                $comment_text !== '' &&
                (
                    strcasecmp($comment_text, $ticket_title) === 0 ||
                    ($ticket_text !== '' && stripos($ticket_text, $comment_text) !== false)
                )
            ) {
                return false;
            }
        }

        return $comment_source === 'chat' && in_array($author_type, array('agent', 'bot'), true);
    }

    return $val === '1' || $val === 1 || $val === true;
}

function robodesk_get_ticket_comment_author_name($comment)
{
    if ($comment && robodesk_is_bot_comment($comment->comment_ID)) {
        return robodesk_get_bot_display_name();
    }

    return $comment->display_name ?: $comment->comment_author;
}

function robodesk_get_ticket_comment_avatar_url($comment, $size = 96)
{
    if ($comment && robodesk_is_bot_comment($comment->comment_ID)) {
        return robodesk_get_bot_avatar_url($size);
    }

    $user_id = $comment ? (int) $comment->user_id : 0;
    if ($user_id > 0 && function_exists('robodesk_get_avatar_url')) {
        $avatar_url = robodesk_get_avatar_url($user_id, $size);
        if ($avatar_url) {
            return $avatar_url;
        }
    }

    $author_email = $comment ? $comment->comment_author_email : '';
    if ($author_email) {
        return get_avatar_url($author_email, array('size' => (int) $size));
    }

    return robodesk_get_default_avatar_url($size);
}

function robodesk_create_notification($recipient_user_id, $ticket_id, $comment_id, $type, $source, $message)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'robodesk_notifications';

    $ticket_id = (int) $ticket_id;
    $comment_id = (int) $comment_id;
    $created_by = (int) get_current_user_id();

    $normalized_source = strtolower(trim((string) $source));
    $comment_user_id = 0;
    $comment_author_type = '';

    if ($comment_id > 0) {
        $comment = get_comment($comment_id);
        if ($comment && !is_wp_error($comment)) {
            $comment_user_id = (int) $comment->user_id;
            $comment_author_type = (string) get_comment_meta($comment_id, 'author_type', true);
            if ($comment_author_type === '') {
                $comment_author_type = robodesk_get_author_type($comment_user_id);
            }

            if ($normalized_source === '') {
                $normalized_source = strtolower((string) get_comment_meta($comment_id, 'comment_source', true));
            }
        }
    }

    if ($normalized_source === '' && $ticket_id > 0) {
        $normalized_source = robodesk_get_ticket_source($ticket_id);
    }

    $customer_user_id = $ticket_id > 0 ? robodesk_get_ticket_customer_user_id($ticket_id) : 0;

    switch ($normalized_source) {
        case 'chat':
            if ($comment_user_id > 0 && $comment_author_type === 'customer') {
                $created_by = $comment_user_id;
            } elseif ($customer_user_id > 0) {
                $created_by = $customer_user_id;
            }
            break;

        case 'email':
            if ($comment_user_id > 0 && $comment_author_type === 'customer') {
                $created_by = $comment_user_id;
            } elseif ($customer_user_id > 0) {
                $created_by = $customer_user_id;
            }
            break;

        case 'admin':
        case 'staff':
            if ($comment_user_id > 0) {
                $created_by = $comment_user_id;
            }
            if ($created_by > 0) {
                $agent_source = robodesk_get_agent_source_by_user($created_by);
                if ($agent_source !== '') {
                    $normalized_source = $agent_source;
                }
            }
            break;

        default:
            if ($comment_user_id > 0) {
                $created_by = $comment_user_id;
            }
            break;
    }

    if ($created_by <= 0 && $ticket_id > 0) {
        $ticket_author_id = (int) get_post_field('post_author', $ticket_id);
        if ($ticket_author_id > 0) {
            $created_by = $ticket_author_id;
        }
    }

    if ($created_by <= 0) {
        $created_by = 1;
    }

    if ($normalized_source === '') {
        $normalized_source = 'chat';
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(
        $table_name,
        [
            'recipient_user_id' => (int) $recipient_user_id,
            'user_id' => (int) $recipient_user_id, // Map recipient to user_id for frontend fetch compatibility
            'ticket_id' => (int) $ticket_id,
            'comment_id' => $comment_id ? (int) $comment_id : null,
            'type' => sanitize_text_field($type),
            'source' => sanitize_text_field($normalized_source),
            'message' => sanitize_textarea_field($message),
            'status' => 'unread',
            'created_by' => (int) $created_by,
            'notification_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'read_at' => null,
        ],
        ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
    );
}

function robodesk_notify_ticket_created($ticket_id, $source = '')
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return;
    }

    $assignee_name = trim((string) get_post_meta($ticket_id, 'ticket_assignee', true));
    $assignee_is_unassigned = $assignee_name === '' ||
        strtolower($assignee_name) === 'n/a' ||
        strtolower($assignee_name) === 'unassigned';

    $assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        $assignee_id = robodesk_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        return;
    }

    if ($assignee_id > 0) {
        // Ticket is assigned — notify only the assigned user
        $recipients = [$assignee_id];
    } else {
        // Ticket is unassigned — notify all support users (admins + staff)
        $recipients = robodesk_get_support_user_ids();
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    if (empty($recipients)) {
        return;
    }

    $source = $source !== '' ? $source : robodesk_get_ticket_source($ticket_id);
    $message = get_the_title($ticket_id);

    foreach ($recipients as $recipient_id) {
        robodesk_create_notification($recipient_id, $ticket_id, null, 'ticket_created', $source, $message);
    }
}

function robodesk_notify_status_changed($ticket_id, $new_status, $actor_user_id = 0, $source = '')
{
    $ticket_id = (int) $ticket_id;
    $new_status = sanitize_text_field((string) $new_status);
    $actor_user_id = (int) $actor_user_id;

    if ($ticket_id <= 0 || $new_status === '' || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return;
    }

    $assignee_name = trim((string) get_post_meta($ticket_id, 'ticket_assignee', true));
    $assignee_is_unassigned = $assignee_name === '' ||
        strtolower($assignee_name) === 'n/a' ||
        strtolower($assignee_name) === 'unassigned';

    $assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        $assignee_id = robodesk_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    $customer_user_id = robodesk_get_ticket_customer_user_id($ticket_id);

    if ($assignee_id > 0) {
        $recipients = [$assignee_id];
    } else {
        $recipients = robodesk_get_support_user_ids();
    }

    if ($customer_user_id > 0) {
        $recipients[] = $customer_user_id;
    }

    $recipients = array_values(array_unique(array_filter(array_map('intval', $recipients))));
    if (empty($recipients)) {
        return;
    }

    if ($actor_user_id <= 0) {
        $actor_user_id = (int) get_current_user_id();
    }

    if ($source === '') {
        $source = robodesk_get_ticket_source($ticket_id);
    }

    $readable_status = ucwords(str_replace('-', ' ', $new_status));
    $message = 'Ticket status changed to ' . $readable_status;

    foreach ($recipients as $recipient_id) {
        if ($recipient_id === $actor_user_id) {
            continue;
        }

        robodesk_create_notification($recipient_id, $ticket_id, null, 'status_changed', $source, $message);
    }
}

function robodesk_notify_comment_created($comment_id)
{
    $comment_id = (int) $comment_id;
    if ($comment_id <= 0) {
        return;
    }

    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        return;
    }

    $ticket_id = (int) $comment->comment_post_ID;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return;
    }

    $first_reply_ids = get_comments([
        'post_id' => $ticket_id,
        'type' => 'ticket_replies',
        'status' => 'approve',
        'orderby' => 'comment_ID',
        'order' => 'ASC',
        'number' => 1,
        'fields' => 'ids',
    ]);

    if (!empty($first_reply_ids) && (int) $first_reply_ids[0] === $comment_id) {
        return;
    }

    if (get_comment_meta($comment_id, 'rbd_suppress_notification', true)) {
        return;
    }

    if (get_comment_meta($comment_id, 'is_action', true)) {
        return;
    }

    $assignee_name = trim((string) get_post_meta($ticket_id, 'ticket_assignee', true));
    $assignee_is_unassigned = $assignee_name === '' ||
        strtolower($assignee_name) === 'n/a' ||
        strtolower($assignee_name) === 'unassigned';

    $assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        $assignee_id = robodesk_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        return;
    }

    if ($assignee_id > 0) {
        // Ticket is assigned — notify only the assigned user
        $recipients = [$assignee_id];
    } else {
        // Ticket is unassigned — notify all support users (admins + staff)
        $recipients = robodesk_get_support_user_ids();
    }

    $source = get_comment_meta($comment_id, 'comment_source', true);
    if ($source === '') {
        $source = robodesk_get_ticket_source($ticket_id);
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = robodesk_get_author_type($comment->user_id);
    }

    // Also notify the ticket customer when the reply is from support/admin.
    $customer_user_id = robodesk_get_ticket_customer_user_id($ticket_id);
    if ($customer_user_id > 0 && $author_type !== 'customer') {
        $recipients[] = $customer_user_id;
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    if (empty($recipients)) {
        return;
    }

    $author_id = (int) $comment->user_id;
    $message = $comment->comment_author . ' replied';

    foreach ($recipients as $recipient_id) {
        if ($recipient_id === $author_id) {
            continue;
        }
        if ($author_type === 'customer') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'robodesk_notifications';

            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, message FROM $table_name
                     WHERE recipient_user_id = %d
                       AND ticket_id = %d
                       AND type = %s
                       AND read_at IS NULL
                     ORDER BY id DESC LIMIT 1",
                    $recipient_id,
                    $ticket_id,
                    'reply_added'
                ),
                ARRAY_A
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

            if ($existing) {
                $count = 1;
                if (preg_match('/^(\d+)\s+new\s+repl(?:y|ies)$/i', (string) $existing['message'], $matches)) {
                    $count = (int) $matches[1];
                }
                $count += 1;
                $new_message = $count . ' new replies';

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table_name,
                    [
                        'message' => $new_message,
                        'created_at' => current_time('mysql'),
                    ],
                    ['id' => (int) $existing['id']],
                    ['%s', '%s'],
                    ['%d']
                );
                continue;
            }

            robodesk_create_notification($recipient_id, $ticket_id, $comment_id, 'reply_added', $source, '1 new reply');
            continue;
        }

        robodesk_create_notification($recipient_id, $ticket_id, $comment_id, 'reply_added', $source, $message);
    }
}

add_action('robodesk_after_reply_added', 'robodesk_schedule_unseen_reply_email', 10, 4);
function robodesk_add_internal_ticket_note($ticket_id, $note_content)
{
    $ticket_id = (int) $ticket_id;
    $note_content = trim((string) $note_content);

    if ($ticket_id <= 0 || $note_content === '') {
        return 0;
    }

    $system_email = (string) get_option('admin_email');
    if (!is_email($system_email)) {
        $system_email = 'no-reply@localhost.invalid';
    }

    $note_id = wp_insert_comment([
        'comment_post_ID' => $ticket_id,
        'comment_author' => 'Robodesk System',
        'comment_author_email' => $system_email,
        'user_id' => 0,
        'comment_content' => $note_content,
        'comment_approved' => 1,
        'comment_type' => 'ticket_notes',
        'comment_meta' => [
            'is_robodesk_note' => 1,
            'is_system_note' => 1,
        ],
    ]);

    if (!$note_id || is_wp_error($note_id)) {
        return 0;
    }

    return (int) $note_id;
}

function robodesk_output_unseen_reply_tracking_pixel()
{
    if (!headers_sent()) {
        nocache_headers();
        header('Content-Type: image/gif');
    }

    $pixel = base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary GIF output, safe and intentional
    echo $pixel;

    wp_die();
}

add_action('wp_ajax_robodesk_track_unseen_reply_email_read', 'robodesk_track_unseen_reply_email_read');
add_action('wp_ajax_nopriv_robodesk_track_unseen_reply_email_read', 'robodesk_track_unseen_reply_email_read');
function robodesk_track_unseen_reply_email_read()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $comment_id = isset($_GET['comment_id']) ? absint(wp_unslash($_GET['comment_id'])) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $token = preg_replace('/[^a-zA-Z0-9]/', '', $token);

    if ($comment_id <= 0 || $token === '') {
        robodesk_output_unseen_reply_tracking_pixel();
    }

    $stored_token = (string) get_comment_meta($comment_id, 'rbd_unseen_email_tracking_token', true);
    if ($stored_token === '' || !hash_equals($stored_token, $token)) {
        robodesk_output_unseen_reply_tracking_pixel();
    }

    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        robodesk_output_unseen_reply_tracking_pixel();
    }

    $ticket_id = (int) $comment->comment_post_ID;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        robodesk_output_unseen_reply_tracking_pixel();
    }

    $already_read_at = (string) get_comment_meta($comment_id, 'rbd_unseen_email_read_at', true);
    if ($already_read_at === '') {
        $read_at = current_time('mysql');
        update_comment_meta($comment_id, 'rbd_unseen_email_read_at', $read_at);
        update_comment_meta($comment_id, 'rbd_unseen_email_read', 1);

        robodesk_add_internal_ticket_note(
            $ticket_id,
            sprintf('Unseen reply reminder email was read at %s.', $read_at)
        );

        rbd_log("Robodesk: Unseen reply email read tracked for ticket #{$ticket_id}, comment #{$comment_id} at {$read_at}");
    }

    robodesk_output_unseen_reply_tracking_pixel();
}

function robodesk_schedule_unseen_reply_email($ticket_id, $comment_id, $reply, $user_object)
{
    rbd_log("Robodesk: Unseen reply email check triggered for ticket #{$ticket_id}, comment #{$comment_id}");

    $settings = get_option('robodesk_advanced_settings', []);
    $minutes = isset($settings['reply_unseen_email_minutes']) ? absint($settings['reply_unseen_email_minutes']) : 0;
    if ($minutes <= 0) {
        rbd_log("Robodesk: Unseen reply email skipped - minutes not configured (value: {$minutes})");
        return;
    }

    if (isset($settings['enable_notifications']) && !$settings['enable_notifications']) {
        rbd_log("Robodesk: Unseen reply email skipped - notifications disabled");
        return;
    }

    $comment_id = (int) $comment_id;
    $ticket_id = (int) $ticket_id;
    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        $type = $comment ? $comment->comment_type : 'null';
        rbd_log("Robodesk: Unseen reply email skipped - invalid comment type: {$type}");
        return;
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = robodesk_get_author_type($comment->user_id);
    }
    if ($author_type !== 'agent') {
        rbd_log("Robodesk: Unseen reply email skipped - author type is '{$author_type}' not 'agent'");
        return;
    }

    if (get_comment_meta($comment_id, 'rbd_unseen_email_scheduled', true)) {
        rbd_log("Robodesk: Unseen reply email skipped - already scheduled for comment #{$comment_id}");
        return;
    }

    update_comment_meta($comment_id, 'rbd_unseen_email_scheduled', 1);

    $timestamp = time() + ($minutes * 60);
    wp_schedule_single_event($timestamp, 'robodesk_send_unseen_reply_email', [$comment_id]);

    rbd_log("Robodesk: Unseen reply email scheduled for comment #{$comment_id} to run in {$minutes} minutes");
}

add_action('robodesk_send_unseen_reply_email', 'robodesk_send_unseen_reply_email', 10, 1);
function robodesk_send_unseen_reply_email($comment_id)
{
    $comment_id = (int) $comment_id;
    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        return;
    }

    $ticket_id = (int) $comment->comment_post_ID;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return;
    }

    $settings = get_option('robodesk_advanced_settings', []);
    $minutes = isset($settings['reply_unseen_email_minutes']) ? absint($settings['reply_unseen_email_minutes']) : 0;
    if ($minutes <= 0) {
        return;
    }

    if (isset($settings['enable_notifications']) && !$settings['enable_notifications']) {
        return;
    }

    if (get_post_meta($ticket_id, 'rbd_all_seen', true) === 'yes') {
        return;
    }

    if (get_comment_meta($comment_id, 'rbd_unseen_email_sent', true)) {
        return;
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = robodesk_get_author_type($comment->user_id);
    }
    if ($author_type !== 'agent') {
        return;
    }

    $customer_email = get_post_meta($ticket_id, '_robodesk_customer_email', true);
    if (!$customer_email || !is_email($customer_email)) {
        return;
    }

    $reply_author_name = trim((string) $comment->comment_author);

    $ticket_title = get_the_title($ticket_id);
    $reply_plain = trim(wp_strip_all_tags($comment->comment_content));
    $subject = 'You have a new reply on your conversation:#' . $ticket_id;

    $ticket_link = robodesk_build_ticket_deeplink($ticket_id);

    $tracking_token = wp_generate_password(32, false, false);
    update_comment_meta($comment_id, 'rbd_unseen_email_tracking_token', $tracking_token);

    $tracking_url = add_query_arg(
        [
            'action' => 'robodesk_track_unseen_reply_email_read',
            'comment_id' => $comment_id,
            'token' => $tracking_token,
        ],
        admin_url('admin-ajax.php')
    );

    $theme_color = function_exists('robodesk_get_email_theme_color') ? robodesk_get_email_theme_color() : '#000000';
    $reply_excerpt = $reply_plain;
    if ($reply_plain !== '') {
        $reply_words = preg_split('/\s+/', $reply_plain);
        if (is_array($reply_words) && count($reply_words) > 20) {
            $reply_excerpt = implode(' ', array_slice($reply_words, 0, 20)) . '...';
        }
    }


    $message .= "<p><strong>" . esc_html($reply_author_name) . "</strong> replied with:</p>";
    if ($reply_excerpt !== '') {
        $message .= "<p>\"" . esc_html($reply_excerpt) . "\"</p>";
    }
    $message .= '<p><a href="' . esc_url($ticket_link) . '" style="display:inline-block;padding:12px 18px;background-color:' . esc_attr($theme_color) . ';color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">View conversation</a></p>';
    $message .= '<p>If the button above doesn\'t work, please use this link: <a href="' . esc_url($ticket_link) . '">' . esc_html($ticket_link) . '</a></p>';
    $message .= '<img src="' . esc_url($tracking_url) . '" width="1" height="1" alt="" style="display:block;border:0;outline:none;text-decoration:none;" />';

    $mail_failed_message = '';
    $mail_failed_listener = function ($wp_error) use (&$mail_failed_message) {
        if (is_wp_error($wp_error)) {
            $mail_failed_message = (string) $wp_error->get_error_message();
        }
    };

    add_action('wp_mail_failed', $mail_failed_listener, 10, 1);

    $sent = false;
    $send_error_message = '';

    try {
        $reply_to = robodesk_get_inbox_email_for_ticket($ticket_id);
        $sent = robodesk_send_email($customer_email, $subject, $message, [], [], $reply_to);
        if (!$sent && $mail_failed_message !== '') {
            $send_error_message = $mail_failed_message;
        }
    } catch (Throwable $e) {
        $send_error_message = $e->getMessage();
    }

    remove_action('wp_mail_failed', $mail_failed_listener, 10);

    $event_time = current_time('mysql');

    if ($sent) {
        update_comment_meta($comment_id, 'rbd_unseen_email_sent', 1);
        update_comment_meta($comment_id, 'rbd_unseen_email_sent_at', $event_time);

        robodesk_add_internal_ticket_note(
            $ticket_id,
            sprintf('Unseen reply reminder email sent successfully to %s at %s.', $customer_email, $event_time)
        );

        rbd_log("Robodesk: Unseen reply email sent to {$customer_email} for ticket #{$ticket_id}");
    } else {
        $send_error_message = trim(wp_strip_all_tags((string) $send_error_message));
        if ($send_error_message !== '') {
            $send_error_message = ' Error: ' . $send_error_message;
        }

        robodesk_add_internal_ticket_note(
            $ticket_id,
            sprintf('Failed to send unseen reply reminder email to %s at %s.%s', $customer_email, $event_time, $send_error_message)
        );

        rbd_log("Robodesk: Unseen reply email failed to send to {$customer_email} for ticket #{$ticket_id}");
    }
}

if (is_admin()) {
    add_filter('use_block_editor_for_post', 'robodesk_disable_block_for_post_type', 10, 2);
}

function robodesk_disable_block_for_post_type($use_block_editor, $post)
{
    if ('robodesk_faq' === $post->post_type) {
        return false;
    }
    if ('notice' === $post->post_type) {
        return false;
    }
    return $use_block_editor;
}



/**
 * Create a support dashboard page on plugin activation
 */
function robodesk_create_support_page()
{

    $support_pages = get_posts(array(
        'post_type' => 'page',
        'title' => 'Robodesk Support',
        'post_status' => 'any',
        'numberposts' => 1,
    ));
    $support_page = !empty($support_pages) ? $support_pages[0] : null;


    rbd_log('Robodesk activation hook triggered');


    if (!$support_page) {
        rbd_log('Creating Robodesk Support page');


        $page_data = array(
            'post_title' => 'Robodesk Support',
            'post_content' => '[robodesk_frontend_dashboard]',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'robodesk-support',
        );


        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {

            update_option('robodesk_support_page_id', $page_id);
            update_post_meta($page_id, 'page_state', '--support dashboard');
        }
    } else {
        rbd_log('Robodesk Support page already exists with ID: ' . $support_page->ID);
    }
}

/**
 * Delete the support dashboard page on plugin deactivation
 */
function robodesk_delete_support_page()
{


    $page_id = get_option('robodesk_support_page_id');


    if ($page_id) {
        wp_delete_post($page_id, true);
        rbd_log('Deleted Robodesk Support page with ID: ' . $page_id);
    } else {
        $support_pages = get_posts(array(
            'post_type' => 'page',
            'title' => 'Robodesk Support',
            'post_status' => 'any',
            'numberposts' => 1,
        ));
        $support_page = !empty($support_pages) ? $support_pages[0] : null;
        if ($support_page) {
            wp_delete_post($support_page->ID, true);
        } else {
            rbd_log('No Robodesk Support page found to delete');
        }
    }

    delete_option('robodesk_support_page_id');
}

register_activation_hook(__FILE__, 'robodesk_create_support_page');
register_deactivation_hook(__FILE__, 'robodesk_delete_support_page');


function robodesk_check_support_page()
{
    // Only run this once
    if (!get_option('robodesk_page_check_complete')) {
        robodesk_create_support_page();
        update_option('robodesk_page_check_complete', true);
    }
}
add_action('plugins_loaded', 'robodesk_check_support_page');
register_activation_hook(__FILE__, 'robodesk_create_support_page');
register_deactivation_hook(__FILE__, 'robodesk_delete_support_page');

// function for theme color 
function robodesk_enqueue_custom_styles()
{
    // Enqueue your main stylesheet first
    wp_enqueue_style('robodesk-style', get_stylesheet_uri(), array(), '1.0.0');

    // Get the theme color from the options
    $theme_color = sanitize_hex_color((string) get_option('robodesk_theme_color', ''));

    if (empty($theme_color)) {
        $theme_color = '#0073aa';
    }

    // Prepare inline CSS
    $custom_css = "
        :root {
            --robodesk-theme-color: {$theme_color};
        }
    ";

    wp_add_inline_style('robodesk-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'robodesk_enqueue_custom_styles');



add_filter('rest_comment_query', 'robodesk_include_ticket_replies_in_rest_comments', 10, 2);
function robodesk_include_ticket_replies_in_rest_comments($args, $request)
{

    $args['type__in'] = array('ticket_replies');

    return $args;
}


add_filter('rest_prepare_comment', function ($response, $comment, $request) {

    if (!is_wp_error($response) && $response instanceof WP_REST_Response) {
        $data = $response->get_data();

        $data['content']['plain'] = wp_strip_all_tags($data['content']['rendered']);

        $author_id = $comment->user_id;

        $comment_time_local = strtotime(get_date_from_gmt($comment->comment_date_gmt));
        $now_local = current_time('timestamp');

        $diff = $now_local - $comment_time_local;

        $comment_action_meta = get_comment_meta($comment->comment_ID, 'is_action', true);

        $data['comment_action'] = $comment_action_meta == '1' ? true : false;

        $human_readable_time = $diff < 5 ? 'just now' : human_time_diff($comment_time_local, $now_local) . ' ago';

        $data['time_ago'] = $human_readable_time;
        $comment_utc = function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date_gmt;
        if ($comment_utc !== '') {
            $data['date'] = $comment_utc;
            $data['date_gmt'] = $comment_utc;
            $data['date_utc'] = $comment_utc;
        }

        $author_name = $comment->comment_author;
        if (function_exists('robodesk_get_comment_avatar')) {
            $author_avatar = robodesk_get_comment_avatar($author_id, 32);
        } else {
            $author_avatar = get_avatar_url($comment->comment_author_email);
        }

        $data['ticket_id'] = $comment->comment_post_ID;

        // Resolve author_type for sender alignment in frontend
        $author_type = get_comment_meta($comment->comment_ID, 'author_type', true);
        if ($author_type === '' && $author_id > 0) {
            $author_type = function_exists('robodesk_get_author_type')
                ? robodesk_get_author_type($author_id)
                : 'customer';
        }

        $data['author_type'] = $author_type;

        $data['author_data'] = array(
            'id' => $author_id,
            'name' => $author_name,
            'avatar' => $author_avatar,
        );

        $response->set_data($data);

        $site_url = site_url();
        $referer = $request->get_header('referer');
        $origin = $request->get_header('origin');

        if (
            (!empty($referer) && strpos($referer, $site_url) === 0) ||
            (!empty($origin) && strpos($origin, $site_url) === 0)
        ) {

            update_post_meta($comment->comment_post_ID, 'rbd_all_seen', 'yes');
        }
    }
    return $response;
}, 10, 3);



add_action('comment_post', 'robodesk_comment_creation', 10, 3);

function robodesk_comment_creation($comment_ID, $comment_approved, $commentdata)
{
    if (is_admin() && isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'ticket_replies') {

        $ticketId = $commentdata['comment_post_ID'];
        if (get_post_type($ticketId) == 'robodesk_ticket') {
            update_post_meta($ticketId, 'last_admin_comment_id', $comment_ID);
        }
    }
}

add_action('wp_insert_comment', 'robodesk_comment_creation_on_insert', 10, 2);

function robodesk_comment_creation_on_insert($comment_ID, $comment_object)
{
    // Return early if not a ticket reply
    if (!isset($comment_object->comment_type) || $comment_object->comment_type !== 'ticket_replies')
        return;

    $ticketId = $comment_object->comment_post_ID;

    if (get_post_type($ticketId) !== 'robodesk_ticket')
        return;

    $author_type = get_comment_meta($comment_ID, 'author_type', true);
    if ($author_type === '') {
        $author_type = robodesk_get_author_type($comment_object->user_id);
        update_comment_meta($comment_ID, 'author_type', $author_type);
    }

    $meta_value = $author_type === 'agent' ? 'yes' : 'no';

    update_comment_meta($comment_ID, 'admin_comment', $meta_value);

    if ($meta_value == 'yes' && $ticketId) {
        update_post_meta($ticketId, 'rbd_all_seen', 'no');
    }

    if ($author_type === 'customer' && $ticketId) {
        $context = robodesk_collect_source_context();

        $comment_context = array(
            'ip' => (string) get_comment_meta($comment_ID, '_robodesk_source_ip', true),
            'country' => (string) get_comment_meta($comment_ID, '_robodesk_source_country', true),
            'browser' => (string) get_comment_meta($comment_ID, '_robodesk_source_browser', true),
        );

        foreach ($comment_context as $context_key => $context_value) {
            if ($context_value !== '') {
                $context[$context_key] = $context_value;
            }
        }

        robodesk_store_ticket_source_meta($ticketId, $context, $comment_ID);
    }

    $comment_source = get_comment_meta($comment_ID, 'comment_source', true);
    if ($comment_source === '') {
        if ($author_type === 'customer' && $comment_object->user_id === 0 && robodesk_get_ticket_source($ticketId) === 'email') {
            $comment_source = 'email';
        } elseif ($author_type === 'customer') {
            $comment_source = is_admin() ? 'admin' : 'web';
        } else {
            $comment_source = is_admin() ? 'admin' : robodesk_get_ticket_source($ticketId);
        }
        update_comment_meta($comment_ID, 'comment_source', $comment_source);
    }

    robodesk_notify_comment_created($comment_ID);
}


function robodesk_disable_jquery_migrate_logging($scripts)
{
    if (!is_admin() && isset($scripts->registered['jquery-migrate'])) {
        $scripts->registered['jquery-migrate']->src = '';
    }
}
add_action('wp_default_scripts', 'robodesk_disable_jquery_migrate_logging');


add_filter('show_admin_bar', 'robodesk_show_admin_bar');

/** * Hide the WordPress admin bar for everyone except administrators Support Managers/Support Staff. */

function robodesk_show_admin_bar($show)
{
    return current_user_can('administrator')
        || current_user_can('robodesk_access_dashboard');
}
