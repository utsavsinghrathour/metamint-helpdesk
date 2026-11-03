<?php

/**
 * Plugin Name: Metamint Helpdesk
 * Plugin URI: https://metamintapps.com/
 * Description: A support ticket and FAQ system.
 * Version: 1.5.7
 * Author: Code Pixelz Media
 * Author URI: https://codepixelzmedia.com
 * License: GPL2
 * Text Domain: metamint-helpdesk
 */

// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('MMHD_VERSION', '1.5.7');
define('MMHD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MMHD_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!function_exists('mmhd_datetime_to_utc_iso')) {
    function mmhd_datetime_to_utc_iso($datetime, $is_gmt = false)
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

if (!function_exists('mmhd_comment_utc_iso')) {
    function mmhd_comment_utc_iso($comment)
    {
        $comment = is_numeric($comment) ? get_comment((int) $comment) : $comment;

        if (!$comment) {
            return '';
        }

        if (!empty($comment->comment_date_gmt) && $comment->comment_date_gmt !== '0000-00-00 00:00:00') {
            return mmhd_datetime_to_utc_iso($comment->comment_date_gmt, true);
        }

        if (!empty($comment->comment_date) && $comment->comment_date !== '0000-00-00 00:00:00') {
            return mmhd_datetime_to_utc_iso($comment->comment_date, false);
        }

        return '';
    }
}

if (!function_exists('mmhd_post_utc_iso')) {
    function mmhd_post_utc_iso($post, $modified = false)
    {
        $post = is_numeric($post) ? get_post((int) $post) : $post;

        if (!$post) {
            return '';
        }

        $gmt_field = $modified ? 'post_modified_gmt' : 'post_date_gmt';
        if (!empty($post->$gmt_field) && $post->$gmt_field !== '0000-00-00 00:00:00') {
            return mmhd_datetime_to_utc_iso($post->$gmt_field, true);
        }

        $local_field = $modified ? 'post_modified' : 'post_date';
        if (!empty($post->$local_field) && $post->$local_field !== '0000-00-00 00:00:00') {
            return mmhd_datetime_to_utc_iso($post->$local_field, false);
        }

        return '';
    }
}

// Activation/Deactivation Hooks
register_activation_hook(__FILE__, 'mmhd_activate');
register_deactivation_hook(__FILE__, 'mmhd_deactivate');

add_filter('user_has_cap', function ($allcaps, $caps, $args) {
    $mmhd_caps = [
        'mmhd_access_dashboard',
        'create_tickets',
        'edit_tickets',
        'edit_others_tickets',
        'publish_tickets',
        'read_private_tickets',
        'read_ticket',
        'delete_ticket',
    ];
    if (!empty($args[0]) && in_array($args[0], $mmhd_caps, true)) {
        // manage_options: administrator, mmhd_support_manager
        // edit_others_posts: editor, shop_manager
        // edit_published_posts: author
        if (!empty($allcaps['manage_options']) || !empty($allcaps['edit_others_posts']) || !empty($allcaps['edit_published_posts'])) {
            $allcaps[$args[0]] = true;
        }
    }
    // Settings/Addons access is limited to the four full-access roles only.
    if (!empty($args[0]) && $args[0] === 'mmhd_manage_settings') {
        $manage_settings_roles = array('administrator', 'editor', 'mmhd_support_manager', 'shop_manager');
        $user_id = !empty($args[1]) ? intval($args[1]) : get_current_user_id();
        $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
        if ($user && array_intersect($manage_settings_roles, (array) $user->roles)) {
            $allcaps[$args[0]] = true;
        }
    }
    return $allcaps;
}, 10, 3);

require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-shortcodes.php';

function mmhd_activate()
{
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('create_tickets');
        $admin_role->add_cap('edit_tickets');
        $admin_role->add_cap('edit_others_tickets');
        $admin_role->add_cap('publish_tickets');
        $admin_role->add_cap('read_private_tickets');
        $admin_role->add_cap('read_ticket');
        $admin_role->add_cap('delete_ticket');
        $admin_role->add_cap('mmhd_access_dashboard');
        $admin_role->add_cap('mmhd_manage_settings');
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
        $editor_role->add_cap('mmhd_access_dashboard');
        $editor_role->add_cap('mmhd_manage_settings');
    }

    // WooCommerce shop manager - full dashboard access (settings included)
    $shop_manager_role = get_role('shop_manager');
    if ($shop_manager_role) {
        $shop_manager_role->add_cap('create_tickets');
        $shop_manager_role->add_cap('edit_tickets');
        $shop_manager_role->add_cap('edit_others_tickets');
        $shop_manager_role->add_cap('publish_tickets');
        $shop_manager_role->add_cap('read_private_tickets');
        $shop_manager_role->add_cap('read_ticket');
        $shop_manager_role->add_cap('delete_ticket');
        $shop_manager_role->add_cap('mmhd_access_dashboard');
        $shop_manager_role->add_cap('mmhd_manage_settings');
    }

    // Author - dashboard access, no settings/addons tabs
    $author_role = get_role('author');
    if ($author_role) {
        $author_role->add_cap('create_tickets');
        $author_role->add_cap('edit_tickets');
        $author_role->add_cap('edit_others_tickets');
        $author_role->add_cap('publish_tickets');
        $author_role->add_cap('read_private_tickets');
        $author_role->add_cap('read_ticket');
        $author_role->add_cap('delete_ticket');
        $author_role->add_cap('mmhd_access_dashboard');
    }

    add_role('mmhd_support_manager', 'Support Manager', array('read' => true, 'manage_options' => true, 'edit_posts' => true, 'manage_categories' => true, 'mmhd_access_dashboard' => true, 'mmhd_manage_settings' => true, 'create_tickets' => true, 'edit_tickets' => true, 'edit_others_tickets' => true, 'publish_tickets' => true, 'read_private_tickets' => true, 'read_ticket' => true, 'delete_ticket' => true));

    add_role('mmhd_support_staff', 'Support Staff', array(
        'read' => true,
        'edit_posts' => true,
        'manage_categories' => true,
        'mmhd_access_dashboard' => true,
        'create_tickets' => true,
        'edit_tickets' => true,
        'edit_others_tickets' => true,
        'publish_tickets' => true,
        'read_private_tickets' => true,
        'read_ticket' => true,
        'delete_ticket' => true
    ));

    add_role('mmhd_support_customer', 'Support Customer', array('read' => true, 'create_tickets' => true, 'read_ticket' => true));
}

function mmhd_deactivate()
{
    remove_role('mmhd_support_manager');
    remove_role('mmhd_support_staff');
    remove_role('mmhd_support_customer');

    // Clear the email fetch cron event
    wp_clear_scheduled_hook('mmhd_email_fetch_cron');
}

/**
 * Build threading headers (In-Reply-To / References) for a ticket so every
 * email sent about it — ticket creation, admin replies, unseen-reply emails,
 * close notifications — stays in the same conversation thread as the original
 * email that created the ticket, and customer replies come back to the IMAP
 * inbox and are matched to this ticket.
 *
 * @param int $ticket_id The ticket (mmhd_ticket post) ID.
 * @return array Headers like [ 'In-Reply-To: <id>', 'References: <id>' ].
 */
function mmhd_get_ticket_thread_headers($ticket_id)
{
    $message_id = trim((string) get_post_meta((int) $ticket_id, 'email_message_id', true));
    if ($message_id === '') {
        return [];
    }

    // Normalize to angle-bracket form for RFC 5322 (In-Reply-To / References).
    if (strpos($message_id, '<') !== 0) {
        $message_id = '<' . $message_id . '>';
    }

    return [
        'In-Reply-To: ' . $message_id,
        'References: ' . $message_id,
    ];
}

/**
 * Apply our deterministic outbound Message-ID to PHPMailer. Setting it here
 * REPLACES PHPMailer's auto-generated one — passing Message-ID as a raw
 * wp_mail() custom header would create TWO Message-ID headers (RFC violation)
 * and receiving servers like Gmail silently drop such mail.
 */
function mmhd_apply_outbound_message_id($phpmailer)
{
    global $mmhd_outbound_message_id;
    if (!empty($mmhd_outbound_message_id)) {
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName
        $phpmailer->MessageID = $mmhd_outbound_message_id;
    }
}
add_action('phpmailer_init', 'mmhd_apply_outbound_message_id');

/**
 * Generate a deterministic outbound Message-ID for a ticket reply comment and
 * persist it on the comment. When the customer replies to that email, their
 * In-Reply-To / References will contain this ID, which is matched back to the
 * ticket during email fetching.
 *
 * @param int $ticket_id  Ticket ID.
 * @param int $comment_id Reply comment ID.
 * @return string Message-ID including angle brackets.
 */
function mmhd_generate_outbound_message_id($ticket_id, $comment_id = 0)
{
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    if (empty($domain)) {
        $domain = 'mmhd.local';
    }
    $suffix = $comment_id > 0 ? '-c' . (int) $comment_id : '';
    return '<mmhd-ticket-' . (int) $ticket_id . $suffix . '@' . $domain . '>';
}

/**
 * Merge default settings into stored options on activation/update so that:
 *  - old saved values are always preserved, and
 *  - any new keys introduced by a plugin update get their default values
 *    written automatically (no manual re-save required after updating).
 */
function mmhd_migrate_settings_on_update()
{
    $installed_version = get_option('mmhd_installed_version', '0');
    if (version_compare($installed_version, MMHD_VERSION, '>=')) {
        return;
    }

    // Advanced settings — preserve existing values, fill in missing defaults.
    $advanced_defaults = [
        'enable_auto_assign'           => false,
        'enable_notifications'         => true,
        'enable_customer_portal'       => true,
        'enable_signup_rate_limiting'  => false,
        'max_file_upload_size'         => 10,
        'reply_unseen_email_minutes'   => 15,
        'polling_interval'             => 30,
        'auto_close_chat_minutes'      => 30,
        'auto_close_ticket_days'       => 0,
    ];
    $existing_advanced = get_option('mmhd_advanced_settings', []);
    $merged_advanced  = wp_parse_args((array) $existing_advanced, $advanced_defaults);
    update_option('mmhd_advanced_settings', $merged_advanced);

    update_option('mmhd_installed_version', MMHD_VERSION);
}
add_action('admin_init', 'mmhd_migrate_settings_on_update');

/**
 * One-time migration: move any notices stored under the legacy, unprefixed
 * `notice` post type to the prefixed `mmhd_notice` post type introduced for
 * WordPress.org naming compliance, so existing notices are not orphaned.
 */
function mmhd_migrate_notice_post_type()
{
    if (get_option('mmhd_notice_cpt_migrated')) {
        return;
    }

    $legacy_notices = get_posts(array(
        'post_type'   => 'notice',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
    ));

    foreach ($legacy_notices as $legacy_id) {
        set_post_type($legacy_id, 'mmhd_notice');
    }

    update_option('mmhd_notice_cpt_migrated', 1);
    flush_rewrite_rules();
}
add_action('admin_init', 'mmhd_migrate_notice_post_type');

/**
 * Initialize debug log file
 * Creates logs directory and debug.log file if they don't exist
 */
function mmhd_init_debug_log()
{
    $upload_dir = wp_upload_dir();
    $logs_dir = $upload_dir['basedir'] . '/mmhd';
    $debug_log = $logs_dir . '/debug.log';

    if (!is_dir($logs_dir)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
        if (!mkdir($logs_dir, 0755, true)) {
            mmhd_log('Failed to create Metamint Helpdesk logs directory');
            return false;
        }
    }

    if (!file_exists($debug_log)) {
        $initial_content = "[DEBUG LOG]\n";

        if (!file_put_contents($debug_log, $initial_content)) {
            mmhd_log('Failed to create Metamint Helpdesk debug.log file');
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        chmod($debug_log, 0644);
    }

    return true;
}

// Call debug log initialization on plugin load
mmhd_init_debug_log();

// Include necessary files
// if (is_admin()) {
require_once MMHD_PLUGIN_PATH . 'admin/mmhd-admin.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-dashboard.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-fetch-tickets.php';
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-functions.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-functions.php';
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-shortcodes.php';
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-ajax.php';
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-notes.php';
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-custom-rest.php';

require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-admin-rest.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-notice-context.php';


// }

// Enqueue Scripts
add_action('wp_enqueue_scripts', 'mmhd_enqueue_scripts');

function mmhd_get_default_staff_id()
{
    $id = get_option('mmhd_default_agent', '');
    if (empty($id)) {
        $id = get_option('mmhd_default_staff', '');
    }
    return $id ? (int) $id : 0;
}

function mmhd_enqueue_scripts()
{

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue styles (already existing)
    wp_enqueue_style('mmhd-styles', MMHD_PLUGIN_URL . 'public/assets/css/mmhd-styles.css', array(), MMHD_VERSION);

    // Enqueue scripts (already existing)
    wp_enqueue_script('mmhd-scripts', MMHD_PLUGIN_URL . 'public/assets/js/mmhd-scripts.js', array('jquery'), MMHD_VERSION, true);

    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'mmhd_enqueue_admin_scripts');
    } else {
        // Enqueue TinyMCE for frontend
        wp_enqueue_script('tinymce');

        // Re-enqueue styles (already existing, adjust if needed)
        wp_enqueue_style('mmhd-styles', MMHD_PLUGIN_URL . 'public/assets/css/mmhd-styles.css', array(), MMHD_VERSION);
        // Re-enqueue scripts with TinyMCE dependency
        wp_enqueue_script('mmhd-scripts', MMHD_PLUGIN_URL . 'public/assets/js/mmhd-scripts.js', array('jquery', 'tinymce', 'wp-api', 'wp-data', 'wp-core-data'), MMHD_VERSION, true);

        // Existing wp_localize_script for AJAX
        wp_localize_script('mmhd-scripts', 'mmhd_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mmhd_change_ticket_status_nonce'),
            'create_ticket_nonce' => wp_create_nonce('mmhd_create_ticket_nonce'),
            'add_ticket_note_nonce' => wp_create_nonce('mmhd_add_ticket_note_nonce'),
            'faq_nonce' => wp_create_nonce('mmhd_faq_vote_nonce'),
            'add_credential_nonce' => wp_create_nonce('mmhd_add_credential_nonce'),
            'delete_credential_nonce' => wp_create_nonce('mmhd_delete_credential_nonce'),
            'reveal_credential_nonce' => wp_create_nonce('mmhd_reveal_credential_nonce'),
            'notifications_nonce' => wp_create_nonce('mmhd_notifications_action'),


        ));

        // Enqueue React script
        $asset_file = file_exists(MMHD_PLUGIN_PATH . 'public/build/index.asset.php')
            ? include MMHD_PLUGIN_PATH . 'public/build/index.asset.php'
            : array('dependencies' => array('wp-element'), 'version' => '1.0.6');

        wp_enqueue_script(
            'mmhd-react-script',
            MMHD_PLUGIN_URL . 'public/build/index.js',
            array_merge(array('jquery'), $asset_file['dependencies']),
            MMHD_VERSION,
            true
        );

        $deep_link_ticket_id = mmhd_get_requested_ticket_id_from_query();

        // Pass image URL (logo only for now)
        $image_urls = [
            'popover_icon' => esc_url(get_option('mmhd_popover_icon', '')),
            'logo' => esc_url(get_option('mmhd_logo', '')),
            'popover_top_icon' => esc_url(get_option('mmhd_popover_top_icon', '')),
            'wide_logo' => esc_url(get_option('mmhd_wide_logo', '')),
        ];
        wp_localize_script(
            'mmhd-react-script',
            'mmhdData',
            [
                'imageUrls' => $image_urls,
                'chatHeading' => get_option('mmhd_chat_heading', 'Have Questions?'),
                'welcomeMessage' => get_option('mmhd_welcome_message', 'Have any questions or need a quote? Leave us a message.'),
                'ticketIntroMessage' => get_option('mmhd_ticket_intro_message', 'If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!'),
                'deepLinkTicketId' => $deep_link_ticket_id,
                'currentUserAvatar' => is_user_logged_in() ? esc_url(mmhd_get_avatar_url(get_current_user_id(), 32)) : '',
                // Bot configuration for avatar/title used in ticket/chat rendering
                'mmhd_bot_profile_image' => esc_url(get_option('mmhd_bot_profile_image', '')),
                'mmhd_bot_title_name' => get_option('mmhd_bot_title_name', ''),
                'mmhd_addon_active' => defined('MMHD_ADDON_VERSION'),
                'mmhd_ai_bot_active' => get_option('mmhd_addon_ai_bot_status', 'inactive') === 'active',
                'enable_chat' => get_option('mmhd_enable_chat', '1'),
                'show_credits' => get_option('mmhd_show_credits', '0'),
                'noticeContext' => mmhd_get_current_notice_context(),
            ]
        );

        // Build priority colors array
        $priority_terms = get_terms(array(
            'taxonomy' => 'mmhd_ticket_priority',
            'hide_empty' => false,
        ));
        $priority_colors = [];
        if ($priority_terms && !is_wp_error($priority_terms)) {
            foreach ($priority_terms as $term) {
                $color = get_term_meta($term->term_id, 'priority_color', true);
                $priority_colors[$term->name] = $color ?: '#000000';
            }
        }

        wp_localize_script('mmhd-react-script', 'mmhdAuth', array(
            'isLoggedIn' => is_user_logged_in() ? 'true' : 'false',
            'loggedInUserId' => is_user_logged_in() ? get_current_user_id() : 'false',

            'notificationSound' => esc_url(get_option('mmhd_notification_sound', '')),
            'priorityTerms' => $priority_terms,
            'priorityColors' => $priority_colors,

            'defaultStaffId' => mmhd_get_default_staff_id(),

            'restNonce' => wp_create_nonce('wp_rest'),

            'status' => [
                'active-conversations' => 'Active Conversations',
                'open' => 'Open',
                'in-progress' => 'In Progress',
                'archived' => 'Archived',
                'closed' => 'Closed',
            ],
            'wpAjax' => [
                'nonce' => wp_create_nonce('mmhd-nonce'),
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
            'mmhd-react-style',
            MMHD_PLUGIN_URL . 'public/build/index.css',
            array(),
            $asset_file['version']
        );
    }
}
add_action('wp_enqueue_scripts', 'mmhd_enqueue_scripts');

function mmhd_add_plugin()
{
    echo '<div id="mmhd-plugin"></div>';
}
add_action('wp_footer', 'mmhd_add_plugin');
function mmhd_handle_settings_save()
{
    if (!current_user_can('mmhd_access_dashboard')) {
        return;
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'mmhd_save_settings')) {
        return;
    }

    // Handle logo upload
    if (!empty($_FILES['logo']['name'])) {
        $upload = wp_handle_upload($_FILES['logo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('mmhd_logo', $upload['url']);
        }
    }

    // Handle popover icon upload
    if (!empty($_FILES['popover_icon']['name'])) {
        $upload = wp_handle_upload($_FILES['popover_icon'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('mmhd_popover_icon', $upload['url']);
        }
    }
    // popup top image
    if (!empty($_FILES['popover_top_icon']['name'])) {
        $upload = wp_handle_upload($_FILES['popover_top_icon'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('mmhd_popover_top_icon', $upload['url']);
        }
    }
    // wide logo
    if (!empty($_FILES['wide_logo']['name'])) {
        $upload = wp_handle_upload($_FILES['wide_logo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            update_option('mmhd_wide_logo', $upload['url']);
        }
    }
}
add_action('admin_post_mmhd_save_settings', 'mmhd_handle_settings_save');



/**
 * Enqueues admin-specific scripts and styles for the Metamint Helpdesk plugin.
 *
 * This function is hooked into the admin_enqueue_scripts action and ensures
 * that scripts and styles are only loaded on the post.php and post-new.php
 * pages for the 'mmhd_ticket' post type.
 *
 * @param string $hook The current admin page hook suffix.
 */

function mmhd_enqueue_admin_scripts($hook)
{
    global $post;
    wp_enqueue_script('jquery');
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field(wp_unslash($_GET['taxonomy'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

    if ($taxonomy === 'mmhd_ticket_priority' && $post_type === 'mmhd_ticket') {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('mmhd-custom-taxonomy-order', MMHD_PLUGIN_URL . 'admin/assets/js/custom-taxonomy-order.js', array('jquery', 'jquery-ui-sortable'), MMHD_VERSION, true);
        wp_localize_script('mmhd-custom-taxonomy-order', 'mmhdTaxonomyOrderData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('taxonomy_order_nonce')
        ));
    }

    if ('post.php' != $hook && 'post-new.php' != $hook)
        return;
    if (!isset($post) || $post->post_type != 'mmhd_ticket')
        return;

    wp_enqueue_style('mmhd-admin-styles', MMHD_PLUGIN_URL . 'admin/assets/css/mmhd-admin-styles.css', array(), '1.0');

    wp_enqueue_script('mmhd-admin-scripts', MMHD_PLUGIN_URL . 'admin/assets/js/mmhd-admin-scripts.js', array('jquery'), '1.0', true);
    wp_localize_script('mmhd-admin-scripts', 'mmhd_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'ticket_id' => $post->ID, 'nonce' => wp_create_nonce('mmhd_change_ticket_nonce'), 'faq_nonce' => wp_create_nonce('mmhd_faq_vote_nonce'), 'credentials_nonce' => wp_create_nonce('mmhd_credentials_nonce')));
}
add_action('admin_enqueue_scripts', 'mmhd_enqueue_admin_scripts');


//@utsav sir code dont remove now
// function mmhd_create_credentials_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'mmhd_credentials';
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
//         CONSTRAINT fk_mmhd_credentials_user FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// }
// register_activation_hook(__FILE__, 'mmhd_create_credentials_table');



// Function to create the table
function mmhd_create_credentials_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mmhd_credentials'; // Add WordPress table prefix
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
        KEY user_id (user_id)
    ) $charset_collate;";

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table_name is built from $wpdb->prefix, and schema changes are only possible via direct SQL.
    if (!empty($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'"))) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery -- intentional schema migration on activation.
        $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY fk_mmhd_credentials_user");
    }

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table_name is built from $wpdb->prefix, and schema changes are only possible via direct SQL.
    if (!empty($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'"))) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- intentional schema migration on activation; identifiers are built from $wpdb->prefix.
        $add_fk_sql = "ALTER TABLE {$table_name}
             ADD CONSTRAINT fk_mmhd_credentials_user FOREIGN KEY (user_id)
             REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- intentional schema migration on activation; identifiers are built from $wpdb->prefix.
        $wpdb->query($add_fk_sql);
    }
}

// Hook to run the function on plugin activation
register_activation_hook(__FILE__, 'mmhd_create_credentials_table');

function mmhd_maybe_create_credentials_table()
{
    $version = get_option('mmhd_credentials_table_version');
    if ($version !== '1.1') {
        mmhd_create_credentials_table();
        update_option('mmhd_credentials_table_version', '1.1');
    }
}

add_action('init', 'mmhd_maybe_create_credentials_table');

// Create notifications table
function mmhd_create_notifications_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mmhd_notifications';
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

register_activation_hook(__FILE__, 'mmhd_create_notifications_table');

function mmhd_maybe_create_notifications_table()
{
    $version = get_option('mmhd_notifications_table_version');
    if ($version !== '1.1') {
        mmhd_create_notifications_table();
        update_option('mmhd_notifications_table_version', '1.1');
    }
}
add_action('init', 'mmhd_maybe_create_notifications_table');



function mmhd_get_encryption_key()
{
    if (defined('MMHD_ENCRYPTION_KEY') && MMHD_ENCRYPTION_KEY !== '') {
        return MMHD_ENCRYPTION_KEY;
    }

    return wp_hash('mmhd-encryption-key');
}

function mmhd_encrypt($plaintext, $key = null)
{
    if ($key === null) {
        $key = mmhd_get_encryption_key();
    }

    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options = 0, $iv);
    return base64_encode($ciphertext . '::' . $iv);
}


function mmhd_decrypt($ciphertext, $key = null)
{
    if ($key === null) {
        $key = mmhd_get_encryption_key();
    }

    $decoded = base64_decode($ciphertext, true);
    if ($decoded === false || strpos($decoded, '::') === false) {
        return false;
    }

    list($encrypted_data, $iv) = explode('::', $decoded, 2);
    $plaintext = openssl_decrypt($encrypted_data, "AES-256-CBC", $key, $options = 0, $iv);

    // Legacy links were encrypted with a hardcoded 'mmhd' key.
    if ($plaintext === false) {
        $plaintext = openssl_decrypt($encrypted_data, "AES-256-CBC", 'mmhd', $options = 0, $iv);
    }

    if ($plaintext === false && defined('AUTH_KEY') && AUTH_KEY !== '') {
        $plaintext = openssl_decrypt($encrypted_data, "AES-256-CBC", AUTH_KEY, $options = 0, $iv);
    }

    return $plaintext;
}

function mmhd_build_ticket_deeplink($ticket_id)
{
    $ticket_id = absint($ticket_id);
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return home_url('/');
    }

    $encrypted_ticket = mmhd_encrypt((string) $ticket_id);

    return add_query_arg(
        [
            'mmhd_popup' => 'true',
            'initiator' => 'mmhd',
            'rtk' => $encrypted_ticket,
        ],
        home_url('/')
    );
}

function mmhd_user_can_access_ticket($ticket_id)
{
    if (!is_user_logged_in()) {
        return true;
    }

    $ticket_id = absint($ticket_id);
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return false;
    }

    $current_user_id = get_current_user_id();
    $customer_user_id = (int) mmhd_get_ticket_customer_user_id($ticket_id);

    if ($customer_user_id > 0 && $current_user_id === $customer_user_id) {
        return true;
    }

    if (current_user_can('mmhd_access_dashboard')) {
        return true;
    }

    return in_array($current_user_id, mmhd_get_support_user_ids(), true);
}

function mmhd_get_requested_ticket_id_from_query()
{
    $ticket_id = 0;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['rtk'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $encrypted_ticket = sanitize_text_field(wp_unslash($_GET['rtk']));
        $encrypted_ticket = trim((string) $encrypted_ticket);
        if ($encrypted_ticket !== '') {
            $encrypted_ticket = str_replace(' ', '+', $encrypted_ticket);
            $decrypted_ticket = mmhd_decrypt($encrypted_ticket);
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

    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return 0;
    }

    if (!mmhd_user_can_access_ticket($ticket_id)) {
        return 0;
    }

    return $ticket_id;
}


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function mmhd_log($message)
{
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/mmhd/debug.log';
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

    if (function_exists('mmhd_prune_debug_log')) {
        mmhd_prune_debug_log($log_file, 30);
    }
}

if (!function_exists('mmhd_prune_debug_log')) {
    function mmhd_prune_debug_log($log_file = '', $days = 30)
    {
        if ($log_file === '') {
            $upload_dir = wp_upload_dir();
            $log_file   = $upload_dir['basedir'] . '/mmhd/debug.log';
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

function mmhd_get_support_user_ids()
{
    $users = get_users([
        'role__in' => [
            'administrator',
            'editor',
            'mmhd_support_manager',
            'mmhd_support_staff',
        ],
        'fields' => ['ID'],
    ]);

    return array_values(array_unique(array_map(function ($user) {
        return (int) $user->ID;
    }, $users)));
}

function mmhd_resolve_user_id_from_assignee($assignee_name)
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

function mmhd_backfill_ticket_assignee_ids()
{
    if (get_option('mmhd_assignee_id_backfill_done') === '1') {
        return;
    }

    $query = new WP_Query([
        'post_type' => 'mmhd_ticket',
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
            mmhd_store_ticket_assignee_id($ticket_id, $assignee_name);
        }
    }

    update_option('mmhd_assignee_id_backfill_done', '1');
}
add_action('init', 'mmhd_backfill_ticket_assignee_ids');

function mmhd_store_ticket_assignee_id($ticket_id, $assignee_name)
{
    $assignee_id = mmhd_resolve_user_id_from_assignee($assignee_name);
    if ($assignee_id > 0) {
        update_post_meta($ticket_id, 'ticket_assignee_id', $assignee_id);
    }

    return $assignee_id;
}

function mmhd_get_ticket_source($ticket_id)
{
    $origin = get_post_meta($ticket_id, 'mmhd_ticket_origin', true);
    if (empty($origin)) {
        $origin_terms = wp_get_post_terms($ticket_id, 'mmhd_ticket_origin');
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

function mmhd_get_request_header_values($request = null)
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

function mmhd_get_first_ip_from_value($value)
{
    $candidates = preg_split('/\s*,\s*/', (string) $value);
    if (!is_array($candidates)) {
        $candidates = array($value);
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate, " \t\n\r\0\x0B[]");
        if ($candidate !== '' && mmhd_is_public_ip($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function mmhd_is_public_ip($ip)
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

function mmhd_get_client_ip()
{
    $remote_addr = '';
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $remote_addr = sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
        if (!filter_var($remote_addr, FILTER_VALIDATE_IP)) {
            $remote_addr = '';
        }
    }

    // Proxy-supplied headers (Cloudflare, X-Forwarded-For, etc.) are only
    // trustworthy when the direct peer is a known reverse proxy. Otherwise
    // anyone can forge them and spoof rate-limit and blocklist identity.
    // Add your proxy IPs/ranges via the 'mmhd_trusted_proxy_ips' filter.
    if ($remote_addr !== '' && mmhd_is_trusted_proxy($remote_addr)) {
        $headers = mmhd_get_request_header_values();
        $priority = array('cf-connecting-ip', 'x-forwarded-for', 'x-real-ip', 'x-client-ip');
        foreach ($priority as $key) {
            if (!empty($headers[$key])) {
                $ip = mmhd_get_first_ip_from_value($headers[$key]);
                if ($ip !== '') {
                    return $ip;
                }
            }
        }
    }

    if ($remote_addr !== '') {
        return $remote_addr;
    }

    return '0.0.0.0';
}

function mmhd_is_trusted_proxy($ip)
{
    $trusted = apply_filters('mmhd_trusted_proxy_ips', array());
    if (!is_array($trusted)) {
        $trusted = array();
    }

    foreach ($trusted as $proxy) {
        $proxy = strtolower(trim((string) $proxy));
        if ($proxy === '') {
            continue;
        }

        if (strpos($proxy, '/') !== false) {
            list($range_ip, $prefix) = explode('/', $proxy, 2);
            if (mmhd_ip_in_range($ip, $range_ip, (int) $prefix)) {
                return true;
            }
        } elseif ($proxy === strtolower($ip)) {
            return true;
        }
    }

    return false;
}

function mmhd_ip_in_range($ip, $range_ip, $prefix)
{
    if ($prefix < 0 || $prefix > 128) {
        return false;
    }

    $ip_bin = inet_pton($ip);
    $range_bin = inet_pton($range_ip);
    if ($ip_bin === false || $range_bin === false || strlen($ip_bin) !== strlen($range_bin)) {
        return false;
    }

    $bits = min($prefix, strlen($ip_bin) * 8);
    $full_bytes = intdiv($bits, 8);
    $rem_bits = $bits % 8;

    if ($full_bytes > 0 && substr($ip_bin, 0, $full_bytes) !== substr($range_bin, 0, $full_bytes)) {
        return false;
    }

    if ($rem_bits > 0) {
        $mask = chr(0xFF << (8 - $rem_bits));
        if ((ord($ip_bin[$full_bytes]) & ord($mask)) !== (ord($range_bin[$full_bytes]) & ord($mask))) {
            return false;
        }
    }

    return true;
}

function mmhd_parse_browser_label($value)
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

function mmhd_collect_source_context($request = null, $overrides = array())
{
    $headers = mmhd_get_request_header_values($request);
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

        $candidate_ip = mmhd_get_first_ip_from_value($headers[$header_key]);
        if ($candidate_ip !== '') {
            $ip = $candidate_ip;
            break;
        }
    }

    if ($ip === '' && !empty($headers['forwarded'])) {
        if (preg_match_all('/for=(?:"?\[?)([a-f0-9:\.]+)(?:\]?"?)/i', (string) $headers['forwarded'], $matches)) {
            foreach ($matches[1] as $candidate_ip) {
                $candidate_ip = trim((string) $candidate_ip, " \t\n\r\0\x0B[]\"");
                if (mmhd_is_public_ip($candidate_ip)) {
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
        'browser' => mmhd_parse_browser_label($browser_source),
    );
}

function mmhd_collect_email_source_context($raw_headers)
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
        'browser' => mmhd_parse_browser_label($browser_source),
    );
}

function mmhd_store_ticket_source_meta($ticket_id, $context, $comment_id = 0)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0 || !is_array($context)) {
        return array();
    }

    $meta_map = array(
        'ip' => '_mmhd_source_ip',
        'country' => '_mmhd_source_country',
        'browser' => '_mmhd_source_browser',
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

function mmhd_get_ticket_source_meta($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return array(
            'ip' => '',
            'country' => '',
            'browser' => '',
        );
    }

    $ip = trim((string) get_post_meta($ticket_id, '_mmhd_source_ip', true));
    if ($ip !== '' && !mmhd_is_public_ip($ip)) {
        $ip = '';
    }

    $country = trim((string) get_post_meta($ticket_id, '_mmhd_source_country', true));
    $browser = trim((string) get_post_meta($ticket_id, '_mmhd_source_browser', true));

    return array(
        'ip' => $ip,
        'country' => $country,
        'browser' => $browser,
    );
}

function mmhd_get_author_type($user_id)
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
        user_can($user_id, 'mmhd_access_dashboard') ||
        in_array('administrator', $roles, true) ||
        in_array('editor', $roles, true) ||
        in_array('mmhd_support_manager', $roles, true) ||
        in_array('mmhd_support_staff', $roles, true)
    ) {
        return 'agent';
    }

    return 'customer';
}

function mmhd_get_agent_source_by_user($user_id)
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
        in_array('mmhd_support_manager', $roles, true) ||
        in_array('mmhd_support_staff', $roles, true)
    ) {
        return 'staff';
    }

    return '';
}

function mmhd_get_ticket_customer_user_id($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return 0;
    }

    $ticket_author_id = (int) get_post_field('post_author', $ticket_id);
    if ($ticket_author_id > 0 && mmhd_get_author_type($ticket_author_id) === 'customer') {
        return $ticket_author_id;
    }

    $customer_email = trim((string) get_post_meta($ticket_id, '_mmhd_customer_email', true));
    if ($customer_email !== '' && is_email($customer_email)) {
        $email_user = get_user_by('email', $customer_email);
        if ($email_user && !empty($email_user->ID)) {
            return (int) $email_user->ID;
        }
    }

    return 0;
}

function mmhd_get_avatar_url($user_id, $size = 96)
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

if (!function_exists('mmhd_get_name_initials')) {
    function mmhd_get_name_initials($name, $user_id = 0)
    {
        $display_name = (string) $name;
        if ((int) $user_id > 0) {
            $user = get_userdata((int) $user_id);
            if ($user && !empty($user->display_name)) {
                $display_name = $user->display_name;
            }
        }
        $parts    = preg_split('/\s+/', trim($display_name));
        $initials = '';
        foreach (array_slice((array) $parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        return $initials !== '' ? $initials : '?';
    }
}

if (!function_exists('mmhd_get_avatar_color')) {
    function mmhd_get_avatar_color($name)
    {
        $palette = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#ef4444'];
        return $palette[crc32((string) $name) % count($palette)];
    }
}

function mmhd_get_default_avatar_url($size = 96)
{
    return get_avatar_url('', array(
        'size' => (int) $size,
        'default' => 'mystery',
        'force_default' => true,
    ));
}

function mmhd_get_bot_display_name()
{
    $bot_name = trim((string) get_option('mmhd_bot_title_name', ''));

    return $bot_name !== '' ? $bot_name : __('Bot', 'metamint-helpdesk');
}

function mmhd_get_bot_avatar_url($size = 96)
{
    $bot_image = trim((string) get_option('mmhd_bot_profile_image', ''));

    return $bot_image !== '' ? esc_url($bot_image) : mmhd_get_default_avatar_url($size);
}

function mmhd_is_bot_comment($comment_id)
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

function mmhd_get_ticket_comment_author_name($comment)
{
    if ($comment && mmhd_is_bot_comment($comment->comment_ID)) {
        return mmhd_get_bot_display_name();
    }

    return $comment->display_name ?: $comment->comment_author;
}

function mmhd_get_ticket_comment_avatar_url($comment, $size = 96)
{
    if ($comment && mmhd_is_bot_comment($comment->comment_ID)) {
        return mmhd_get_bot_avatar_url($size);
    }

    $user_id = $comment ? (int) $comment->user_id : 0;
    if ($user_id > 0 && function_exists('mmhd_get_avatar_url')) {
        $avatar_url = mmhd_get_avatar_url($user_id, $size);
        if ($avatar_url) {
            return $avatar_url;
        }
    }

    $author_email = $comment ? $comment->comment_author_email : '';
    if ($author_email) {
        return get_avatar_url($author_email, array('size' => (int) $size));
    }

    return mmhd_get_default_avatar_url($size);
}

function mmhd_create_notification($recipient_user_id, $ticket_id, $comment_id, $type, $source, $message)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mmhd_notifications';

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
                $comment_author_type = mmhd_get_author_type($comment_user_id);
            }

            if ($normalized_source === '') {
                $normalized_source = strtolower((string) get_comment_meta($comment_id, 'comment_source', true));
            }
        }
    }

    if ($normalized_source === '' && $ticket_id > 0) {
        $normalized_source = mmhd_get_ticket_source($ticket_id);
    }

    $customer_user_id = $ticket_id > 0 ? mmhd_get_ticket_customer_user_id($ticket_id) : 0;

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
                $agent_source = mmhd_get_agent_source_by_user($created_by);
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

function mmhd_notify_ticket_created($ticket_id, $source = '')
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return;
    }

    $assignee_name = trim((string) get_post_meta($ticket_id, 'ticket_assignee', true));
    $assignee_is_unassigned = $assignee_name === '' ||
        strtolower($assignee_name) === 'n/a' ||
        strtolower($assignee_name) === 'unassigned';

    $assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        $assignee_id = mmhd_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        return;
    }

    if ($assignee_id > 0) {
        // Ticket is assigned — notify only the assigned user
        $recipients = [$assignee_id];
    } else {
        // Ticket is unassigned — notify all support users (admins + staff)
        $recipients = mmhd_get_support_user_ids();
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    if (empty($recipients)) {
        return;
    }

    $source = $source !== '' ? $source : mmhd_get_ticket_source($ticket_id);
    $message = get_the_title($ticket_id);

    foreach ($recipients as $recipient_id) {
        mmhd_create_notification($recipient_id, $ticket_id, null, 'ticket_created', $source, $message);
    }
}

function mmhd_notify_status_changed($ticket_id, $new_status, $actor_user_id = 0, $source = '')
{
    $ticket_id = (int) $ticket_id;
    $new_status = sanitize_text_field((string) $new_status);
    $actor_user_id = (int) $actor_user_id;

    if ($ticket_id <= 0 || $new_status === '' || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return;
    }

    $assignee_name = trim((string) get_post_meta($ticket_id, 'ticket_assignee', true));
    $assignee_is_unassigned = $assignee_name === '' ||
        strtolower($assignee_name) === 'n/a' ||
        strtolower($assignee_name) === 'unassigned';

    $assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        $assignee_id = mmhd_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    $customer_user_id = mmhd_get_ticket_customer_user_id($ticket_id);

    if ($assignee_id > 0) {
        $recipients = [$assignee_id];
    } else {
        $recipients = mmhd_get_support_user_ids();
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
        $source = mmhd_get_ticket_source($ticket_id);
    }

    $readable_status = ucwords(str_replace('-', ' ', $new_status));
    $message = 'Ticket status changed to ' . $readable_status;

    foreach ($recipients as $recipient_id) {
        if ($recipient_id === $actor_user_id) {
            continue;
        }

        mmhd_create_notification($recipient_id, $ticket_id, null, 'status_changed', $source, $message);
    }
}

function mmhd_notify_comment_created($comment_id)
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
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
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

    if (get_comment_meta($comment_id, 'mmhd_suppress_notification', true)) {
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
        $assignee_id = mmhd_store_ticket_assignee_id($ticket_id, $assignee_name);
    }

    if ($assignee_id <= 0 && !$assignee_is_unassigned) {
        return;
    }

    if ($assignee_id > 0) {
        // Ticket is assigned — notify only the assigned user
        $recipients = [$assignee_id];
    } else {
        // Ticket is unassigned — notify all support users (admins + staff)
        $recipients = mmhd_get_support_user_ids();
    }

    $source = get_comment_meta($comment_id, 'comment_source', true);
    if ($source === '') {
        $source = mmhd_get_ticket_source($ticket_id);
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = mmhd_get_author_type($comment->user_id);
    }

    // Also notify the ticket customer when the reply is from support/admin.
    $customer_user_id = mmhd_get_ticket_customer_user_id($ticket_id);
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
            $table_name = $wpdb->prefix . 'mmhd_notifications';

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

            mmhd_create_notification($recipient_id, $ticket_id, $comment_id, 'reply_added', $source, '1 new reply');
            continue;
        }

        mmhd_create_notification($recipient_id, $ticket_id, $comment_id, 'reply_added', $source, $message);
    }
}

add_action('mmhd_after_reply_added', 'mmhd_schedule_unseen_reply_email', 10, 4);
function mmhd_add_internal_ticket_note($ticket_id, $note_content)
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
        'comment_author' => 'Metamint Helpdesk System',
        'comment_author_email' => $system_email,
        'user_id' => 0,
        'comment_content' => $note_content,
        'comment_approved' => 1,
        'comment_type' => 'ticket_notes',
        'comment_meta' => [
            'is_mmhd_note' => 1,
            'is_system_note' => 1,
        ],
    ]);

    if (!$note_id || is_wp_error($note_id)) {
        return 0;
    }

    return (int) $note_id;
}

function mmhd_output_unseen_reply_tracking_pixel()
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

add_action('wp_ajax_mmhd_track_unseen_reply_email_read', 'mmhd_track_unseen_reply_email_read');
add_action('wp_ajax_nopriv_mmhd_track_unseen_reply_email_read', 'mmhd_track_unseen_reply_email_read');
function mmhd_track_unseen_reply_email_read()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $comment_id = isset($_GET['comment_id']) ? absint(wp_unslash($_GET['comment_id'])) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $token = preg_replace('/[^a-zA-Z0-9]/', '', $token);

    if ($comment_id <= 0 || $token === '') {
        mmhd_output_unseen_reply_tracking_pixel();
    }

    $stored_token = (string) get_comment_meta($comment_id, 'mmhd_unseen_email_tracking_token', true);
    if ($stored_token === '' || !hash_equals($stored_token, $token)) {
        mmhd_output_unseen_reply_tracking_pixel();
    }

    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        mmhd_output_unseen_reply_tracking_pixel();
    }

    $ticket_id = (int) $comment->comment_post_ID;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        mmhd_output_unseen_reply_tracking_pixel();
    }

    $already_read_at = (string) get_comment_meta($comment_id, 'mmhd_unseen_email_read_at', true);
    if ($already_read_at === '') {
        $read_at = current_time('mysql');
        update_comment_meta($comment_id, 'mmhd_unseen_email_read_at', $read_at);
        update_comment_meta($comment_id, 'mmhd_unseen_email_read', 1);

        mmhd_add_internal_ticket_note(
            $ticket_id,
            sprintf('Unseen reply reminder email was read at %s.', $read_at)
        );

        mmhd_log("Metamint Helpdesk: Unseen reply email read tracked for ticket #{$ticket_id}, comment #{$comment_id} at {$read_at}");
    }

    mmhd_output_unseen_reply_tracking_pixel();
}

function mmhd_schedule_unseen_reply_email($ticket_id, $comment_id, $reply, $user_object)
{
    mmhd_log("Metamint Helpdesk: Unseen reply email check triggered for ticket #{$ticket_id}, comment #{$comment_id}");

    if (mmhd_are_emails_suppressed()) {
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - email sending disabled (do not send email on import) for comment #{$comment_id}");
        return;
    }

    $settings = get_option('mmhd_advanced_settings', []);
    $minutes = isset($settings['reply_unseen_email_minutes']) ? absint($settings['reply_unseen_email_minutes']) : 0;
    if ($minutes <= 0) {
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - minutes not configured (value: {$minutes})");
        return;
    }

    if (isset($settings['enable_notifications']) && !$settings['enable_notifications']) {
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - notifications disabled");
        return;
    }

    $comment_id = (int) $comment_id;
    $ticket_id = (int) $ticket_id;
    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        $type = $comment ? $comment->comment_type : 'null';
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - invalid comment type: {$type}");
        return;
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = mmhd_get_author_type($comment->user_id);
    }
    if ($author_type !== 'agent') {
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - author type is '{$author_type}' not 'agent'");
        return;
    }

    if (get_comment_meta($comment_id, 'mmhd_unseen_email_scheduled', true)) {
        mmhd_log("Metamint Helpdesk: Unseen reply email skipped - already scheduled for comment #{$comment_id}");
        return;
    }

    update_comment_meta($comment_id, 'mmhd_unseen_email_scheduled', 1);

    $timestamp = time() + ($minutes * 60);
    wp_schedule_single_event($timestamp, 'mmhd_send_unseen_reply_email', [$comment_id]);

    mmhd_log("Metamint Helpdesk: Unseen reply email scheduled for comment #{$comment_id} to run in {$minutes} minutes");
}

add_action('mmhd_send_unseen_reply_email', 'mmhd_send_unseen_reply_email', 10, 1);
function mmhd_send_unseen_reply_email($comment_id)
{
    $comment_id = (int) $comment_id;
    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'ticket_replies') {
        return;
    }

    $ticket_id = (int) $comment->comment_post_ID;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return;
    }

    $settings = get_option('mmhd_advanced_settings', []);
    $minutes = isset($settings['reply_unseen_email_minutes']) ? absint($settings['reply_unseen_email_minutes']) : 0;
    if ($minutes <= 0) {
        return;
    }

    if (isset($settings['enable_notifications']) && !$settings['enable_notifications']) {
        return;
    }

    if (get_post_meta($ticket_id, 'mmhd_all_seen', true) === 'yes') {
        return;
    }

    if (get_comment_meta($comment_id, 'mmhd_unseen_email_sent', true)) {
        return;
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = mmhd_get_author_type($comment->user_id);
    }
    if ($author_type !== 'agent') {
        return;
    }

    $customer_email = get_post_meta($ticket_id, '_mmhd_customer_email', true);
    if (!$customer_email || !is_email($customer_email)) {
        return;
    }

    $reply_author_name = trim((string) $comment->comment_author);

    $ticket_title = get_the_title($ticket_id);
    $reply_plain = trim(wp_strip_all_tags($comment->comment_content));
    $subject = 'You have a new reply on your conversation:#' . $ticket_id;

    $ticket_link = mmhd_build_ticket_deeplink($ticket_id);

    $tracking_token = wp_generate_password(32, false, false);
    update_comment_meta($comment_id, 'mmhd_unseen_email_tracking_token', $tracking_token);

    $tracking_url = add_query_arg(
        [
            'action' => 'mmhd_track_unseen_reply_email_read',
            'comment_id' => $comment_id,
            'token' => $tracking_token,
        ],
        admin_url('admin-ajax.php')
    );

    $theme_color = function_exists('mmhd_get_email_theme_color') ? mmhd_get_email_theme_color() : '#000000';
    $reply_excerpt = $reply_plain;
    if ($reply_plain !== '') {
        $reply_words = preg_split('/\s+/', $reply_plain);
        if (is_array($reply_words) && count($reply_words) > 20) {
            $reply_excerpt = implode(' ', array_slice($reply_words, 0, 20)) . '...';
        }
    }


    $message = "<p><strong>" . esc_html($reply_author_name) . "</strong> replied with:</p>";
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
        // Store our outbound Message-ID on the comment so the customer's reply
        // (In-Reply-To / References) matches back to this ticket when fetched.
        $outbound_message_id = mmhd_generate_outbound_message_id($ticket_id, $comment_id);
        add_comment_meta($comment_id, 'email_message_id', $outbound_message_id, true);

        $reply_to = mmhd_get_inbox_email_for_ticket($ticket_id);
        $thread_headers = mmhd_get_ticket_thread_headers($ticket_id);
        $sent = mmhd_send_email($customer_email, $subject, $message, $thread_headers, [], $reply_to, $outbound_message_id);
        if (!$sent && $mail_failed_message !== '') {
            $send_error_message = $mail_failed_message;
        }
    } catch (Throwable $e) {
        $send_error_message = $e->getMessage();
    }

    remove_action('wp_mail_failed', $mail_failed_listener, 10);

    $event_time = current_time('mysql');

    if ($sent) {
        update_comment_meta($comment_id, 'mmhd_unseen_email_sent', 1);
        update_comment_meta($comment_id, 'mmhd_unseen_email_sent_at', $event_time);

        // Flag the ticket so a later email reply can flip a chat ticket's source
        // from chat to email (case 2: reminder sent, customer replies via email).
        update_post_meta($ticket_id, 'mmhd_reminder_email_sent', $event_time);

        mmhd_add_internal_ticket_note(
            $ticket_id,
            sprintf('Unseen reply reminder email sent successfully to %s at %s.', $customer_email, $event_time)
        );

        mmhd_log("Metamint Helpdesk: Unseen reply email sent to {$customer_email} for ticket #{$ticket_id}");
    } else {
        $send_error_message = trim(wp_strip_all_tags((string) $send_error_message));
        if ($send_error_message !== '') {
            $send_error_message = ' Error: ' . $send_error_message;
        }

        mmhd_add_internal_ticket_note(
            $ticket_id,
            sprintf('Failed to send unseen reply reminder email to %s at %s.%s', $customer_email, $event_time, $send_error_message)
        );

        mmhd_log("Metamint Helpdesk: Unseen reply email failed to send to {$customer_email} for ticket #{$ticket_id}");
    }
}

// Auto-close chat conversations: when a Support Staff / Manager / Admin posts a
// reply on a chat ticket, schedule the ticket to be closed after the configured
// delay if the customer does not respond in the meantime.
add_action('mmhd_after_reply_added', 'mmhd_schedule_chat_ticket_auto_close', 20, 4);
function mmhd_schedule_chat_ticket_auto_close($ticket_id, $comment_id, $reply, $user_object)
{
    $settings = get_option('mmhd_advanced_settings', []);
    $minutes = isset($settings['auto_close_chat_minutes']) ? absint($settings['auto_close_chat_minutes']) : 30;
    if ($minutes <= 0) {
        mmhd_log("Metamint Helpdesk: Chat auto-close skipped - minutes not configured (ticket #{$ticket_id})");
        return;
    }

    $ticket_id  = (int) $ticket_id;
    $comment_id = (int) $comment_id;
    if ($ticket_id <= 0 || $comment_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return;
    }

    // Only chat-sourced tickets are eligible.
    if (mmhd_get_ticket_source($ticket_id) !== 'chat') {
        return;
    }

    // Only real agent (staff/manager/admin) replies restart the countdown.
    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $comment = get_comment($comment_id);
        $author_type = $comment ? mmhd_get_author_type((int) $comment->user_id) : '';
    }
    if ($author_type !== 'agent') {
        mmhd_log("Metamint Helpdesk: Chat auto-close skipped - comment #{$comment_id} author type '{$author_type}' is not agent");
        return;
    }

    if (get_post_meta($ticket_id, '_mmhd_ticket_status', true) === 'closed') {
        return;
    }

    // Schedule the close. Older pending events for this ticket are left in place
    // and simply become no-ops when a newer reply arrives (validated at run time).
    $timestamp = time() + ($minutes * MINUTE_IN_SECONDS);
    wp_schedule_single_event($timestamp, 'mmhd_auto_close_chat_ticket', [$ticket_id, $comment_id]);
    update_post_meta($ticket_id, 'mmhd_auto_close_scheduled', $timestamp);

    mmhd_log("Metamint Helpdesk: Chat auto-close scheduled for ticket #{$ticket_id} after comment #{$comment_id} to run in {$minutes} minutes");
}

add_action('mmhd_auto_close_chat_ticket', 'mmhd_run_chat_ticket_auto_close', 10, 2);
function mmhd_run_chat_ticket_auto_close($ticket_id, $comment_id)
{
    $ticket_id  = (int) $ticket_id;
    $comment_id = (int) $comment_id;

    $settings = get_option('mmhd_advanced_settings', []);
    $minutes = isset($settings['auto_close_chat_minutes']) ? absint($settings['auto_close_chat_minutes']) : 30;
    if ($minutes <= 0) {
        return;
    }

    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return;
    }

    if (mmhd_get_ticket_source($ticket_id) !== 'chat') {
        return;
    }

    $current_status = (string) get_post_meta($ticket_id, '_mmhd_ticket_status', true);
    if ($current_status === 'closed') {
        return;
    }

    // The scheduled comment must still be the last real reply — this ensures the
    // customer never replied and no newer agent reply rescheduled the close.
    $last_reply = mmhd_get_last_real_ticket_reply($ticket_id);
    if (!$last_reply || (int) $last_reply->comment_ID !== (int) $comment_id) {
        mmhd_log("Metamint Helpdesk: Chat auto-close cancelled - newer reply posted on ticket #{$ticket_id}");
        return;
    }

    $author_type = get_comment_meta($comment_id, 'author_type', true);
    if ($author_type === '') {
        $author_type = mmhd_get_author_type((int) $last_reply->user_id);
    }
    if ($author_type !== 'agent') {
        return;
    }

    update_post_meta($ticket_id, '_mmhd_ticket_status', 'closed');

    // Attribute the auto-close comment to the ticket assignee, defaulting to the
    // configurable default agent, then to the first administrator.
    $author_id = mmhd_get_auto_close_author_id($ticket_id);
    $author_user = $author_id > 0 ? get_userdata($author_id) : false;

    // Add an action comment marking the auto-close so it is visible in the thread.
    $close_comment_id = wp_insert_comment([
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $author_user ? $author_user->display_name : 'System',
        'comment_author_email' => $author_user ? $author_user->user_email : '',
        'user_id'              => $author_id,
        'comment_content'      => 'This conversation has been closed due to inactivity. Please start a new conversation if you still need assistance.',
        'comment_approved'     => 1,
        'comment_type'         => 'ticket_replies',
        'is_mmhd_reply'        => true,
        'comment_meta'         => [
            'is_action' => true,
            // Explicitly mark this as a real (authentic) reply so the thread
            // renders the resolved author's name instead of the AI bot label.
            'is_bot_response' => '0',
        ],
    ]);
    if (!is_wp_error($close_comment_id)) {
        update_comment_meta($close_comment_id, 'comment_source', 'chat');
        update_comment_meta($close_comment_id, 'author_type', 'agent');
        update_comment_meta($close_comment_id, 'admin_comment', 'yes');
    }

    delete_post_meta($ticket_id, 'mmhd_auto_close_scheduled');

    do_action('mmhd_after_ticket_closed', $ticket_id, $author_id);
    do_action('mmhd_after_ticket_status_change', $ticket_id, $current_status, 'closed', $author_id);

    if (function_exists('mmhd_notify_status_changed')) {
        mmhd_notify_status_changed($ticket_id, 'closed', $author_id, 'chat');
    }

    mmhd_log("Metamint Helpdesk: Chat ticket #{$ticket_id} auto-closed after no customer response");
}

/**
 * Get the latest real reply on a ticket, skipping system/status action comments
 * and bot responses so only genuine customer or agent messages are considered.
 */
function mmhd_get_last_real_ticket_reply($ticket_id)
{
    $comments = get_comments([
        'post_id' => (int) $ticket_id,
        'number'  => 50,
        'orderby' => 'comment_ID',
        'order'   => 'DESC',
        'type'    => 'ticket_replies',
    ]);

    foreach ($comments as $comment) {
        if (get_comment_meta($comment->comment_ID, 'is_action', true)) {
            continue;
        }
        if (function_exists('mmhd_is_bot_comment') && mmhd_is_bot_comment($comment->comment_ID)) {
            continue;
        }
        return $comment;
    }

    return null;
}

/**
 * Resolve which user should author the auto-close comment: the ticket's
 * assignee, falling back to the configured default agent, then to the first
 * administrator. Returns 0 when no eligible user can be found.
 */
function mmhd_get_auto_close_author_id($ticket_id)
{
    $ticket_id = (int) $ticket_id;

    $assignee_id = $ticket_id > 0 ? (int) get_post_meta($ticket_id, 'ticket_assignee_id', true) : 0;
    if ($assignee_id > 0 && get_userdata($assignee_id)) {
        return $assignee_id;
    }

    $default_agent = trim((string) get_option('mmhd_default_agent', ''));
    if ($default_agent === '') {
        $default_agent = trim((string) get_option('mmhd_default_staff', ''));
    }
    $default_id = function_exists('mmhd_resolve_user_id_from_assignee') ? mmhd_resolve_user_id_from_assignee($default_agent) : 0;
    if ($default_id > 0 && get_userdata($default_id)) {
        return $default_id;
    }

    $administrators = get_users([
        'role'    => 'administrator',
        'number'  => 1,
        'fields'  => 'ID',
        'orderby' => 'ID',
        'order'   => 'ASC',
    ]);
    $admin_id = !empty($administrators) ? (int) $administrators[0] : 0;
    if ($admin_id > 0 && get_userdata($admin_id)) {
        return $admin_id;
    }

    return 0;
}

/**
 * Backwards-compatible wrapper for the chat-specific auto-close flow.
 */
function mmhd_get_chat_auto_close_author_id($ticket_id)
{
    return mmhd_get_auto_close_author_id($ticket_id);
}

/**
 * Auto-close tickets from every source after a configurable number of days
 * without a reply from the end user. The timer is based on the most recent
 * customer reply, or the ticket creation date when there is no customer reply.
 */
add_action('init', 'mmhd_schedule_auto_close_tickets_cron');
function mmhd_schedule_auto_close_tickets_cron()
{
    $settings = get_option('mmhd_advanced_settings', []);
    $days = isset($settings['auto_close_ticket_days']) ? absint($settings['auto_close_ticket_days']) : 0;

    if ($days <= 0) {
        return;
    }

    if (!wp_next_scheduled('mmhd_auto_close_tickets_cron')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'mmhd_auto_close_tickets_cron');
    }
}

add_action('mmhd_auto_close_tickets_cron', 'mmhd_run_auto_close_tickets_cron');
function mmhd_run_auto_close_tickets_cron()
{
    $settings = get_option('mmhd_advanced_settings', []);
    $days = isset($settings['auto_close_ticket_days']) ? absint($settings['auto_close_ticket_days']) : 0;

    if ($days <= 0) {
        return;
    }

    $cutoff = (int) current_time('timestamp', true) - ($days * DAY_IN_SECONDS);

    $ticket_ids = get_posts([
        'post_type'              => 'mmhd_ticket',
        'post_status'            => 'any',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
        'meta_query'             => [
            'relation' => 'OR',
            [
                'key'     => '_mmhd_ticket_status',
                'value'   => 'closed',
                'compare' => '!=',
            ],
            [
                'key'     => '_mmhd_ticket_status',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);

    if (empty($ticket_ids)) {
        return;
    }

    $closed_count = 0;

    foreach ($ticket_ids as $ticket_id) {
        $ticket_id = (int) $ticket_id;
        if ($ticket_id <= 0) {
            continue;
        }

        $current_status = (string) get_post_meta($ticket_id, '_mmhd_ticket_status', true);
        if ($current_status === 'closed') {
            continue;
        }

        $reference_time = mmhd_get_ticket_auto_close_reference_time($ticket_id);
        if ($reference_time <= 0 || $reference_time > $cutoff) {
            continue;
        }

        if (mmhd_auto_close_ticket($ticket_id)) {
            $closed_count++;
        }
    }

    if ($closed_count > 0) {
        mmhd_log(sprintf('Metamint Helpdesk: Auto-close cron closed %d ticket(s).', $closed_count));
    }
}

/**
 * Find the most recent genuine reply from the end user on a ticket.
 *
 * Customer replies carry author_type=customer across every source (chat, email,
 * WhatsApp, web and the customer portal). The fallback scan covers legacy
 * comments created before that metadata was introduced.
 */
function mmhd_get_last_customer_ticket_reply($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return null;
    }

    $comments = get_comments([
        'post_id'    => $ticket_id,
        'number'     => 10,
        'orderby'    => 'comment_date_gmt',
        'order'      => 'DESC',
        'type'       => 'ticket_replies',
        'status'     => 'approve',
        'meta_query' => [
            [
                'key'     => 'author_type',
                'value'   => 'customer',
                'compare' => '=',
            ],
        ],
    ]);

    foreach ($comments as $comment) {
        if (get_comment_meta($comment->comment_ID, 'is_action', true)) {
            continue;
        }
        return $comment;
    }

    $customer_email = strtolower(trim((string) get_post_meta($ticket_id, '_mmhd_customer_email', true)));

    $legacy_comments = get_comments([
        'post_id' => $ticket_id,
        'number'  => 200,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
        'type'    => 'ticket_replies',
        'status'  => 'approve',
    ]);

    foreach ($legacy_comments as $comment) {
        if (get_comment_meta($comment->comment_ID, 'is_action', true)) {
            continue;
        }

        if (function_exists('mmhd_is_bot_comment') && mmhd_is_bot_comment($comment->comment_ID)) {
            continue;
        }

        $author_type = (string) get_comment_meta($comment->comment_ID, 'author_type', true);
        if ($author_type === '') {
            $author_type = mmhd_get_author_type((int) $comment->user_id);
        }

        if ($author_type === 'customer') {
            return $comment;
        }

        // Legacy email replies from customers who do not have a WordPress account.
        $comment_email = strtolower(trim((string) $comment->comment_author_email));
        if (
            (int) $comment->user_id <= 0 &&
            $customer_email !== '' &&
            $comment_email !== '' &&
            $comment_email === $customer_email
        ) {
            return $comment;
        }
    }

    return null;
}

/**
 * Resolve the moment the auto-close countdown starts for a ticket.
 */
function mmhd_get_ticket_auto_close_reference_time($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0) {
        return 0;
    }

    $last_customer_reply = mmhd_get_last_customer_ticket_reply($ticket_id);
    if ($last_customer_reply) {
        $date_gmt = (string) $last_customer_reply->comment_date_gmt;
        if ($date_gmt === '' || $date_gmt === '0000-00-00 00:00:00') {
            $date_gmt = get_gmt_from_date($last_customer_reply->comment_date);
        }

        $timestamp = strtotime($date_gmt . ' UTC');
        if ($timestamp) {
            return (int) $timestamp;
        }
    }

    $created_at = get_post_time('U', true, $ticket_id);
    return $created_at ? (int) $created_at : 0;
}

/**
 * Close a ticket and add the rendered "Conversation Closed" template as a
 * public comment. Returns true when the ticket was closed.
 */
function mmhd_auto_close_ticket($ticket_id)
{
    $ticket_id = (int) $ticket_id;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return false;
    }

    $current_status = (string) get_post_meta($ticket_id, '_mmhd_ticket_status', true);
    if ($current_status === 'closed') {
        return false;
    }

    $settings = get_option('mmhd_advanced_settings', []);
    $days = isset($settings['auto_close_ticket_days']) ? absint($settings['auto_close_ticket_days']) : 0;

    $template = (string) get_option(
        'mmhd_email_template_ticket-closed',
        'Your ticket has been closed. Please let us know if you need further assistance.'
    );
    $content = wp_kses_post(mmhd_replace_template_tags($template, $ticket_id));

    if (trim(wp_strip_all_tags($content)) === '') {
        $content = esc_html__('Your ticket has been closed. Please let us know if you need further assistance.', 'metamint-helpdesk');
    }

    $author_id = mmhd_get_auto_close_author_id($ticket_id);
    $author_user = $author_id > 0 ? get_userdata($author_id) : false;
    $author_name = $author_user ? $author_user->display_name : __('System', 'metamint-helpdesk');

    $comment_id = wp_insert_comment([
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $author_name,
        'comment_author_email' => $author_user ? $author_user->user_email : '',
        'user_id'              => $author_id,
        'comment_content'      => $content,
        'comment_approved'     => 1,
        'comment_type'         => 'ticket_replies',
        'is_mmhd_reply'        => true,
        'comment_meta'         => [
            'comment_source'            => 'admin',
            'author_type'               => 'agent',
            'is_bot_response'           => '0',
            'admin_comment'             => 'yes',
            'mmhd_suppress_notification' => 1,
            'mmhd_auto_close_comment'   => 1,
        ],
    ]);

    if (!$comment_id || is_wp_error($comment_id)) {
        mmhd_log(sprintf('Metamint Helpdesk: Auto-close failed to add a comment to ticket #%d.', $ticket_id));
        return false;
    }

    update_post_meta($ticket_id, '_mmhd_ticket_status', 'closed');
    update_post_meta($ticket_id, '_mmhd_auto_closed_at', current_time('mysql', true));
    update_post_meta($ticket_id, '_mmhd_auto_close_comment_id', (int) $comment_id);

    $source = mmhd_get_ticket_source($ticket_id);

    do_action('mmhd_after_ticket_closed', $ticket_id, $author_id);
    do_action('mmhd_after_ticket_status_change', $ticket_id, $current_status, 'closed', $author_id);

    if (function_exists('mmhd_notify_status_changed')) {
        mmhd_notify_status_changed($ticket_id, 'closed', $author_id, $source);
    }

    mmhd_log(sprintf(
        'Metamint Helpdesk: Ticket #%d auto-closed after %d day(s) without an end-user reply.',
        $ticket_id,
        $days
    ));

    return true;
}

if (is_admin()) {
    add_filter('use_block_editor_for_post', 'mmhd_disable_block_for_post_type', 10, 2);
}

function mmhd_disable_block_for_post_type($use_block_editor, $post)
{
    if ('mmhd_faq' === $post->post_type) {
        return false;
    }
    if ('mmhd_notice' === $post->post_type) {
        return false;
    }
    return $use_block_editor;
}



/**
 * Create a support dashboard page on plugin activation
 */
function mmhd_create_support_page()
{

    $support_pages = get_posts(array(
        'post_type' => 'page',
        'title' => 'Metamint Helpdesk Support',
        'post_status' => 'any',
        'numberposts' => 1,
    ));
    $support_page = !empty($support_pages) ? $support_pages[0] : null;


    mmhd_log('Metamint Helpdesk Support activation hook triggered');


    if (!$support_page) {
        mmhd_log('Creating Metamint Helpdesk Support page');


        $page_data = array(
            'post_title' => 'Metamint Helpdesk Support',
            'post_content' => '[mmhd_frontend_dashboard]',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'mmhd-support',
        );


        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {

            update_option('mmhd_support_page_id', $page_id);
            update_post_meta($page_id, 'page_state', '--support dashboard');
        }
    } else {
        mmhd_log('Metamint Helpdesk Support page already exists with ID: ' . $support_page->ID);
    }
}

/**
 * Delete the support dashboard page on plugin deactivation
 */
function mmhd_delete_support_page()
{


    $page_id = get_option('mmhd_support_page_id');


    if ($page_id) {
        wp_delete_post($page_id, true);
        mmhd_log('Deleted Metamint Helpdesk Support page with ID: ' . $page_id);
    } else {
        $support_pages = get_posts(array(
            'post_type' => 'page',
            'title' => 'Metamint Helpdesk Support',
            'post_status' => 'any',
            'numberposts' => 1,
        ));
        $support_page = !empty($support_pages) ? $support_pages[0] : null;
        if ($support_page) {
            wp_delete_post($support_page->ID, true);
        } else {
            mmhd_log('No Metamint Helpdesk Support page found to delete');
        }
    }

    delete_option('mmhd_support_page_id');
}

register_activation_hook(__FILE__, 'mmhd_create_support_page');
register_deactivation_hook(__FILE__, 'mmhd_delete_support_page');


function mmhd_check_support_page()
{
    // Only run this once
    if (!get_option('mmhd_page_check_complete')) {
        mmhd_create_support_page();
        update_option('mmhd_page_check_complete', true);
    }
}
add_action('plugins_loaded', 'mmhd_check_support_page');
register_activation_hook(__FILE__, 'mmhd_create_support_page');
register_deactivation_hook(__FILE__, 'mmhd_delete_support_page');

// function for theme color 
function mmhd_enqueue_custom_styles()
{
    // Enqueue your main stylesheet first
    wp_enqueue_style('mmhd-style', get_stylesheet_uri(), array(), '1.0.0');

    // Get the theme color from the options
    $theme_color = sanitize_hex_color((string) get_option('mmhd_theme_color', ''));

    if (empty($theme_color)) {
        $theme_color = '#0073aa';
    }

    // Prepare inline CSS
    $custom_css = "
        :root {
            --mmhd-theme-color: {$theme_color};
        }
    ";

    wp_add_inline_style('mmhd-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'mmhd_enqueue_custom_styles');



add_filter('rest_comment_query', 'mmhd_include_ticket_replies_in_rest_comments', 10, 2);
function mmhd_include_ticket_replies_in_rest_comments($args, $request)
{
    // WP REST hands 'post' over as a scalar or a list depending on the route;
    // normalize to a list of IDs so the guard works in both shapes.
    $post_param = $request->get_param('post');
    $post_ids = is_array($post_param) ? array_map('absint', $post_param) : array(absint($post_param));
    $ticket_ids = array_values(array_filter($post_ids, function ($id) {
        return $id > 0 && get_post_type($id) === 'mmhd_ticket';
    }));
    if (empty($ticket_ids)) {
        return $args;
    }

    $is_staff = current_user_can('mmhd_access_dashboard') || current_user_can('manage_options');
    if (!$is_staff) {
        // Non-staff may only read the comments of tickets they own.
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            $args['post__in'] = array(0);
            return $args;
        }
        foreach ($ticket_ids as $ticket_id) {
            $ticket = get_post($ticket_id);
            if (!$ticket || (int) $ticket->post_author !== $user_id) {
                $args['post__in'] = array(0);
                return $args;
            }
        }
    }

    $args['type__in'] = array('ticket_replies');

    return $args;
}

// Restrict the mmhd_ticket REST collection to staff and single-item
// reads to the ticket owner or staff. The chat widget deep-link relies on
// the single-item route for ticket owners, and the widget ticket list
// fetches the collection filtered by the current user's author ID.
add_filter('rest_mmhd_ticket_query', function ($args, $request) {
    $is_staff = current_user_can('mmhd_access_dashboard') || current_user_can('manage_options');
    if ($is_staff) {
        return $args;
    }

    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        $args['post__in'] = array(0);
        return $args;
    }

    $ticket_id = (int) $request->get_param('id');
    if ($ticket_id > 0) {
        $ticket = get_post($ticket_id);
        if (!$ticket || $ticket->post_type !== 'mmhd_ticket' || (int) $ticket->post_author !== $user_id) {
            $args['post__in'] = array(0);
        }
        return $args;
    }

    $author_param = $request->get_param('author');
    $requested_author = is_array($author_param) ? (int) end($author_param) : (int) $author_param;
    if ($requested_author !== $user_id) {
        $args['post__in'] = array(0);
    }
    return $args;
}, 10, 2);

// Runs after URL params are resolved (rest_pre_dispatch fires too early to
// see the route's {id} param), so item-level authorization is reliable here.
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    $route = $request->get_route();
    if (0 !== strpos($route, '/wp/v2/mmhd_ticket')) {
        return $response;
    }

    $target_id = (int) $request->get_param('id');
    $current_id = get_current_user_id();
    $is_staff = $current_id > 0 && (current_user_can('mmhd_access_dashboard') || current_user_can('manage_options'));

    if ($target_id > 0) {
        $post = get_post($target_id);
        if (!$post || get_post_type($post) !== 'mmhd_ticket') {
            return new WP_Error('rest_not_found', 'Ticket not found.', array('status' => 404));
        }

        $customer_user_id = (int) mmhd_get_ticket_customer_user_id($target_id);
        $is_owner = $current_id > 0 && ((int) $post->post_author === $current_id || ($customer_user_id > 0 && $customer_user_id === $current_id));

        if (!$is_owner && !$is_staff) {
            return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to view this ticket.', array('status' => 403));
        }
    } elseif (!$is_staff) {
        // The chat widget lists the customer's own tickets via ?author=<current user>.
        $author_param = $request->get_param('author');
        $requested_author = is_array($author_param) ? (int) end($author_param) : (int) $author_param;
        if ($requested_author <= 0 || $requested_author !== $current_id) {
            return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to view tickets.', array('status' => 403));
        }
    }

    return $response;
}, 10, 3);


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
        $comment_utc = function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date_gmt;
        if ($comment_utc !== '') {
            $data['date'] = $comment_utc;
            $data['date_gmt'] = $comment_utc;
            $data['date_utc'] = $comment_utc;
        }

        $author_name = $comment->comment_author;
        if (function_exists('mmhd_get_comment_avatar')) {
            $author_avatar = mmhd_get_comment_avatar($author_id, 32);
        } else {
            $author_avatar = get_avatar_url($comment->comment_author_email);
        }

        $data['ticket_id'] = $comment->comment_post_ID;

        // Resolve author_type for sender alignment in frontend
        $author_type = get_comment_meta($comment->comment_ID, 'author_type', true);
        if ($author_type === '' && $author_id > 0) {
            $author_type = function_exists('mmhd_get_author_type')
                ? mmhd_get_author_type($author_id)
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

            update_post_meta($comment->comment_post_ID, 'mmhd_all_seen', 'yes');
        }
    }
    return $response;
}, 10, 3);



add_action('comment_post', 'mmhd_comment_creation', 10, 3);

function mmhd_comment_creation($comment_ID, $comment_approved, $commentdata)
{
    if (is_admin() && isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'ticket_replies') {

        $ticketId = $commentdata['comment_post_ID'];
        if (get_post_type($ticketId) == 'mmhd_ticket') {
            update_post_meta($ticketId, 'last_admin_comment_id', $comment_ID);
        }
    }
}

add_action('wp_insert_comment', 'mmhd_comment_creation_on_insert', 10, 2);

function mmhd_comment_creation_on_insert($comment_ID, $comment_object)
{
    // Return early if not a ticket reply
    if (!isset($comment_object->comment_type) || $comment_object->comment_type !== 'ticket_replies')
        return;

    $ticketId = $comment_object->comment_post_ID;

    if (get_post_type($ticketId) !== 'mmhd_ticket')
        return;

    $author_type = get_comment_meta($comment_ID, 'author_type', true);
    if ($author_type === '') {
        $author_type = mmhd_get_author_type($comment_object->user_id);
        update_comment_meta($comment_ID, 'author_type', $author_type);
    }

    $meta_value = $author_type === 'agent' ? 'yes' : 'no';

    update_comment_meta($comment_ID, 'admin_comment', $meta_value);

    if ($meta_value == 'yes' && $ticketId) {
        update_post_meta($ticketId, 'mmhd_all_seen', 'no');
    }

    if ($author_type === 'customer' && $ticketId) {
        $context = mmhd_collect_source_context();

        $comment_context = array(
            'ip' => (string) get_comment_meta($comment_ID, '_mmhd_source_ip', true),
            'country' => (string) get_comment_meta($comment_ID, '_mmhd_source_country', true),
            'browser' => (string) get_comment_meta($comment_ID, '_mmhd_source_browser', true),
        );

        foreach ($comment_context as $context_key => $context_value) {
            if ($context_value !== '') {
                $context[$context_key] = $context_value;
            }
        }

        mmhd_store_ticket_source_meta($ticketId, $context, $comment_ID);
    }

    $comment_source = get_comment_meta($comment_ID, 'comment_source', true);
    if ($comment_source === '') {
        if ($author_type === 'customer' && $comment_object->user_id === 0 && mmhd_get_ticket_source($ticketId) === 'email') {
            $comment_source = 'email';
        } elseif ($author_type === 'customer') {
            $comment_source = is_admin() ? 'admin' : 'web';
        } else {
            $comment_source = is_admin() ? 'admin' : mmhd_get_ticket_source($ticketId);
        }
        update_comment_meta($comment_ID, 'comment_source', $comment_source);
    }

    mmhd_notify_comment_created($comment_ID);
}


function mmhd_disable_jquery_migrate_logging($scripts)
{
    if (!is_admin() && isset($scripts->registered['jquery-migrate'])) {
        $scripts->registered['jquery-migrate']->src = '';
    }
}
add_action('wp_default_scripts', 'mmhd_disable_jquery_migrate_logging');


add_filter('show_admin_bar', 'mmhd_show_admin_bar');

/** * Hide the WordPress admin bar for everyone except administrators Support Managers/Support Staff. */

function mmhd_show_admin_bar($show)
{
    return current_user_can('administrator')
        || current_user_can('mmhd_access_dashboard');
}
