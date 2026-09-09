<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include admin-side post types
require_once MMHD_PLUGIN_PATH . 'admin/includes/post-types/mmhd-ticket-post-type.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/post-types/mmhd-faq-post-type.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/mmhd-fetch-tickets.php';
require_once MMHD_PLUGIN_PATH . 'admin/includes/post-types/mmhd-notice-post-type.php';


// Other admin related functions can go here
add_action('admin_menu', 'mmhd_add_admin_menu');
function mmhd_add_admin_menu()
{
    // Top-level menu pointing to the dashboard
    add_menu_page(
        'Metamint Helpdesk',
        'Metamint Helpdesk',
        'mmhd_access_dashboard',
        'mmhd-dashboard',
        'mmhd_dashboard_callback',
        'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents(plugin_dir_path(__FILE__) . 'assets/images/mmhd-icon.svg')),
        30
    );

    // Dashboard (default submenu)
    add_submenu_page(
        'mmhd-dashboard',
        'Dashboard',
        'Dashboard',
        'mmhd_access_dashboard',
        'mmhd-dashboard',
        'mmhd_dashboard_callback'
    );



    add_submenu_page(
        'mmhd-dashboard',
        'Departments',
        'Departments',
        'manage_categories',
        'edit-tags.php?taxonomy=mmhd_ticket_category&post_type=mmhd_ticket'
    );


    // Ticket Priorities (taxonomy)
    add_submenu_page(
        'mmhd-dashboard',
        'Priorities',
        'Priorities',
        'manage_categories',
        'edit-tags.php?taxonomy=mmhd_ticket_priority&post_type=mmhd_ticket'
    );

    // FAQs list
    add_submenu_page(
        'mmhd-dashboard',
        'All FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=mmhd_faq'
    );

    // Add FAQ
    add_submenu_page(
        'mmhd-dashboard',
        'Add New FAQ',
        'Add New FAQ',
        'edit_posts',
        'post-new.php?post_type=mmhd_faq'
    );

    // FAQ Categories
    add_submenu_page(
        'mmhd-dashboard',
        'FAQ Categories',
        'FAQ Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=mmhd_faq_category&post_type=mmhd_faq'
    );

    // Notices list
    add_submenu_page(
        'mmhd-dashboard',
        'All Notices',
        'All Notices',
        'edit_posts',
        'edit.php?post_type=notice'
    );

    // Add Notice
    add_submenu_page(
        'mmhd-dashboard',
        'Add New Notice',
        'Add New Notice',
        'edit_posts',
        'post-new.php?post_type=notice'
    );

    // Debug Log
    add_submenu_page(
        'mmhd-dashboard',
        'Debug Log',
        'Debug Log',
        'mmhd_access_dashboard',
        'mmhd-debug-log',
        'mmhd_debug_log_page_callback'
    );
}


function mmhd_update_taxonomy_order()
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'taxonomy_order_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Insufficient permissions');
    }

    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
        wp_send_json_error('Invalid order data');
    }

    $term_order = array_map('intval', $_POST['order']);

    foreach ($term_order as $index => $term_id) {
        if ($term_id) {
            update_term_meta($term_id, 'taxonomy_order', $index);
        }
    }

    wp_send_json_success('Order updated');
}
add_action('wp_ajax_mmhd_update_taxonomy_order', 'mmhd_update_taxonomy_order');

function mmhd_modify_taxonomy_query($query)
{
    if (
        is_admin() &&
        isset($query->query_vars['taxonomy']) &&
        $query->query_vars['taxonomy'] === 'mmhd_ticket_priority'
    ) {
        $query->query_vars['meta_key'] = 'taxonomy_order';
        $query->query_vars['orderby'] = array(
            'meta_value_num' => 'ASC',
            'name' => 'ASC'
        );


        $query->query_vars['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => 'taxonomy_order',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'taxonomy_order',
                'compare' => 'NOT EXISTS',
                'value' => ''
            )
        );
    }
}
add_action('pre_get_terms', 'mmhd_modify_taxonomy_query');

function mmhd_set_default_taxonomy_order($term_id, $tt_id, $taxonomy)
{
    if ($taxonomy === 'mmhd_ticket_priority') {
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'meta_key'   => 'taxonomy_order',
            'orderby'    => 'meta_value_num',
            'order'      => 'DESC',
            'fields'     => 'ids',
            'number'     => 1
        ));

        $new_order = (!empty($terms) && get_term_meta($terms[0], 'taxonomy_order', true) !== '')
            ? (int)get_term_meta($terms[0], 'taxonomy_order', true) + 1
            : 0;
        update_term_meta($term_id, 'taxonomy_order', $new_order);
    }
}
add_action('created_term', 'mmhd_set_default_taxonomy_order', 10, 3);

function mmhd_custom_taxonomy_order($terms, $taxonomies, $args)
{
    if (
        is_admin() &&
        in_array('mmhd_ticket_priority', $taxonomies) &&
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        !isset($_GET['orderby'])
    ) {

        usort($terms, function ($a, $b) {

            $a_id = is_object($a) ? $a->term_id : $a;
            $b_id = is_object($b) ? $b->term_id : $b;

            $a_order = get_term_meta($a_id, 'taxonomy_order', true);
            $b_order = get_term_meta($b_id, 'taxonomy_order', true);
            $a_order = $a_order !== '' ? (int)$a_order : PHP_INT_MAX;
            $b_order = $b_order !== '' ? (int)$b_order : PHP_INT_MAX;

            return $a_order - $b_order;
        });
    }
    return $terms;
}
add_filter('get_terms', 'mmhd_custom_taxonomy_order', 10, 3);


function mmhd_add_custom_comment_type($commentdata)
{
    if (get_post_type($commentdata['comment_post_ID']) === 'mmhd_ticket') {
        $commentdata['comment_type'] = 'ticket_replies';
        $commentdata['comment_content'] = wp_kses_post($commentdata['comment_content']);
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'mmhd_add_custom_comment_type');



// Enqueue Tailwind CSS and custom styles
//if page=mmhd-dashboard then enqueue styles
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (isset($_GET['page']) && $_GET['page'] === 'mmhd-dashboard') {
    add_action('admin_enqueue_scripts', 'mmhd_enqueue_tailwind_and_styles');
}
function mmhd_enqueue_tailwind_and_styles()
{
    // Needed for the native WordPress crop modal.
    wp_enqueue_media();

    wp_enqueue_style(
        'tailwind-css',
        plugin_dir_url(__FILE__) . 'assets/css/tailwind/tailwind.min.css',
        [],
        '3.4.15'
    );

    wp_enqueue_style(
        'mmhd-dashboard-styles',
        plugin_dir_url(__FILE__) . 'assets/css/mmhd-dashboard.css',
        [],
        '1.0.0'
    );
    wp_add_inline_style('mmhd-dashboard-styles', '.hidden { display: none; }');

    wp_enqueue_script(
        'mmhd-dashboard-script',
        plugin_dir_url(__FILE__) . 'assets/js/mmhd-dashboard.js',
        ['jquery'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/mmhd-dashboard.js'),
        true
    );

    // Note: Slack and Discord integration scripts have been moved to the mmhd-addon plugin
    // They will be enqueued automatically when the respective addons are activated

    wp_enqueue_style(
        'font-awesome',
        plugin_dir_url(__FILE__) . 'assets/css/font-awesome.min.css',
        [],
        '6.0.0'
    );

    // Localize script with all necessary nonces
    wp_localize_script('mmhd-dashboard-script', 'mmhdAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajax_url' => admin_url('admin-ajax.php'), // Backward compatibility
        'rest_url' => rest_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'nonces' => [
            'imap_settings' => wp_create_nonce('mmhd_imap_settings_action'),
            'email_template' => wp_create_nonce('mmhd_email_template_action'),
            'advanced_settings' => wp_create_nonce('mmhd_advanced_settings_action'),
            'basic_settings' => wp_create_nonce('mmhd_basic_settings_action'),
            'user_lookup' => wp_create_nonce('mmhd_user_lookup_action'),
            'fetch_emails' => wp_create_nonce('mmhd_fetch_emails_action'),
            'ticket_assignment' => wp_create_nonce('mmhd_ticket_assignment_action'),
            'create_ticket' => wp_create_nonce('mmhd_create_ticket_action'),
            'get_ticket_detail' => wp_create_nonce('mmhd_get_ticket_detail_action'),
            'ticket_status' => wp_create_nonce('mmhd_ticket_status_action'),
            'ticket_priority' => wp_create_nonce('mmhd_ticket_priority_action'),
            'ticket_assignment' => wp_create_nonce('mmhd_ticket_assignment_action'),
            'add_ticket_reply' => wp_create_nonce('mmhd_add_ticket_reply_action'),
            'get_ticket_replies' => wp_create_nonce('mmhd_get_ticket_replies_action'),
            'add_ticket_note' => wp_create_nonce('mmhd_add_ticket_note_action'),
            'get_ticket_notes' => wp_create_nonce('mmhd_get_ticket_notes_action'),
            'get_ticket_replies_and_notes' => wp_create_nonce('mmhd_get_ticket_replies_and_notes_action'),
            'fetch_emails' => wp_create_nonce('mmhd_fetch_emails_action'),
            // Slack Integration Nonces
            'slack_status' => wp_create_nonce('mmhd_slack_status_action'),
            'slack_connect' => wp_create_nonce('mmhd_slack_connect_action'),
            'slack_disconnect' => wp_create_nonce('mmhd_slack_disconnect_action'),
            'slack_save_credentials' => wp_create_nonce('mmhd_slack_credentials_action'),
            'slack_get_credentials' => wp_create_nonce('mmhd_slack_get_credentials_action'),
            'slack_save_settings' => wp_create_nonce('mmhd_slack_settings_action'),
            'slack_workspace' => wp_create_nonce('mmhd_slack_workspace_action'),
            'slack_send_message' => wp_create_nonce('mmhd_slack_send_message_action'),
            'slack_send_ticket' => wp_create_nonce('mmhd_slack_send_ticket_action'),
            'slack_send_reply' => wp_create_nonce('mmhd_slack_send_reply_action'),
            'slack_fetch_messages' => wp_create_nonce('mmhd_slack_fetch_messages_action'),
            'slack_test' => wp_create_nonce('mmhd_slack_test_action'),
            // Discord Integration Nonces
            'discord_save_webhook' => wp_create_nonce('mmhd_discord_save_webhook'),
            'discord_get_webhook' => wp_create_nonce('mmhd_discord_get_webhook'),
            'discord_test_connection' => wp_create_nonce('mmhd_discord_test_connection'),
            'discord_disconnect' => wp_create_nonce('mmhd_discord_disconnect'),
            'discord_save_settings' => wp_create_nonce('mmhd_discord_save_settings'),
            'discord_send_test_message' => wp_create_nonce('mmhd_discord_send_test_message'),
            'close_ticket' => wp_create_nonce('mmhd_close_ticket_action'),
            'update_ticket_category' => wp_create_nonce('mmhd_update_ticket_category_action'),
            'bulk_action' => wp_create_nonce('mmhd_bulk_action'),
            'refresh_dashboard_tickets' => wp_create_nonce('mmhd_refresh_dashboard_tickets_action'),
            'customer_tickets' => wp_create_nonce('mmhd_customer_tickets_action'),
            'notifications' => wp_create_nonce('mmhd_notifications_action'),

        ]
    ]);

    // Pass notification sound URL to JavaScript
    wp_localize_script('mmhd-dashboard-script', 'mmhdNotificationSound', get_option('mmhd_notification_sound', ''));

    // Pass advanced settings to JavaScript
    $mmhd_adv_settings = get_option('mmhd_advanced_settings', []);
    wp_localize_script('mmhd-dashboard-script', 'mmhdAdvancedSettings', [
        'polling_interval' => isset($mmhd_adv_settings['polling_interval']) ? absint($mmhd_adv_settings['polling_interval']) : 30,
    ]);
}

/**
 * Displays the active + my-conversation ticket counts as two separate
 * admin toolbar items (side by side). Each item is a normal WordPress
 * admin bar node, so hovering highlights it with the active Administration
 * Color Scheme's native style — no custom hover CSS needed.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
 */
function mmhd_admin_bar_active_tickets($wp_admin_bar)
{
    if (!current_user_can('mmhd_access_dashboard')) {
        return;
    }

    $counters    = mmhd_get_ticket_counters(get_current_user_id());
    $total_count = (int) $counters['counts']['open'];
    $my_count    = (int) $counters['counts']['my'];

    $badge = function ($count, $label) {
        return ' <span class="dashboard-filter-count-badge is-visible" title="' . esc_attr($label) . '">' . esc_html($count) . '</span>';
    };

    // Open the dashboard with the matching conversation filter applied.
    // The nonce lets the server pre-select the right filter button.
    $active_url = wp_nonce_url(
        admin_url('admin.php?page=mmhd-dashboard&filter=open_conversation'),
        'mmhd_filter'
    );
    $my_url = wp_nonce_url(
        admin_url('admin.php?page=mmhd-dashboard&filter=my_conversation'),
        'mmhd_filter'
    );

    $wp_admin_bar->add_node(array(
        'id'    => 'mmhd-active-tickets',
        'title' => '<span class="ab-icon"><img src="' . esc_url(MMHD_PLUGIN_URL . 'admin/assets/images/mmhd-icon.svg') . '" style="height:20px;width:20px;display:block" alt=""></span>'
            . '<span class="ab-label">' . esc_html__('Active Tickets', 'metamint-helpdesk') . '</span>'
            . $badge($total_count, esc_html__('Active conversations', 'metamint-helpdesk')),
        'href'  => $active_url,
        'meta'  => array(
            'title' => esc_html__('View active conversations', 'metamint-helpdesk'),
        ),
    ));

    $wp_admin_bar->add_node(array(
        'id'    => 'mmhd-my-tickets',
        'title' => '<span class="ab-icon"><img src="' . esc_url(MMHD_PLUGIN_URL . 'admin/assets/images/mmhd-icon.svg') . '" style="height:20px;width:20px;display:block" alt=""></span>'
            . '<span class="ab-label">' . esc_html__('My Tickets', 'metamint-helpdesk') . '</span>'
            . $badge($my_count, esc_html__('My conversations', 'metamint-helpdesk')),
        'href'  => $my_url,
        'meta'  => array(
            'title' => esc_html__('View my conversations', 'metamint-helpdesk'),
        ),
    ));

    // Badge styles must render even on pages where the dashboard stylesheet
    // is not loaded (any admin page or the frontend toolbar).
    if (!did_action('mmhd_admin_bar_ticket_badge_styles')) {
        add_action('admin_footer', 'mmhd_admin_bar_ticket_badge_styles', 1);
        add_action('wp_footer', 'mmhd_admin_bar_ticket_badge_styles', 1);
        do_action('mmhd_admin_bar_ticket_badge_styles');
    }
}
add_action('admin_bar_menu', 'mmhd_admin_bar_active_tickets', 999);

/**
 * Prints the admin toolbar badge styles once.
 */
function mmhd_admin_bar_ticket_badge_styles()
{
    echo '<style id="mmhd-adminbar-ticket-badge">'
        . '#wpadminbar .dashboard-filter-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:1.15rem;height:1.15rem;margin-left:.3rem;padding:0 .28rem;border-radius:9999px;background-color:#ef4444;color:#fff;font-size:.7rem;font-weight:600;line-height:1;vertical-align:middle}'
        . '</style>';
}
