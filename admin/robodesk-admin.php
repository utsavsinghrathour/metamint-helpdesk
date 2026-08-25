<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include admin-side post types
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-ticket-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-faq-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-fetch-tickets.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-notice-post-type.php';


// Other admin related functions can go here
add_action('admin_menu', 'robodesk_add_admin_menu');
function robodesk_add_admin_menu()
{
    // Top-level menu pointing to the dashboard
    add_menu_page(
        'Robodesk',
        'Robodesk',
        'robodesk_access_dashboard',
        'robodesk-dashboard',
        'robodesk_dashboard_callback',
        'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents(plugin_dir_path(__FILE__) . 'assets/images/robodesk-icon.svg')),
        30
    );

    // Dashboard (default submenu)
    add_submenu_page(
        'robodesk-dashboard',
        'Dashboard',
        'Dashboard',
        'robodesk_access_dashboard',
        'robodesk-dashboard',
        'robodesk_dashboard_callback'
    );



    add_submenu_page(
        'robodesk-dashboard',
        'Departments',
        'Departments',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_category&post_type=robodesk_ticket'
    );


    // Ticket Priorities (taxonomy)
    add_submenu_page(
        'robodesk-dashboard',
        'Priorities',
        'Priorities',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_priority&post_type=robodesk_ticket'
    );

    // FAQs list
    add_submenu_page(
        'robodesk-dashboard',
        'All FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=robodesk_faq'
    );

    // Add FAQ
    add_submenu_page(
        'robodesk-dashboard',
        'Add New FAQ',
        'Add New FAQ',
        'edit_posts',
        'post-new.php?post_type=robodesk_faq'
    );

    // FAQ Categories
    add_submenu_page(
        'robodesk-dashboard',
        'FAQ Categories',
        'FAQ Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_faq_category&post_type=robodesk_faq'
    );

    // Notices list
    add_submenu_page(
        'robodesk-dashboard',
        'All Notices',
        'All Notices',
        'edit_posts',
        'edit.php?post_type=notice'
    );

    // Add Notice
    add_submenu_page(
        'robodesk-dashboard',
        'Add New Notice',
        'Add New Notice',
        'edit_posts',
        'post-new.php?post_type=notice'
    );

    // Debug Log
    add_submenu_page(
        'robodesk-dashboard',
        'Debug Log',
        'Debug Log',
        'robodesk_access_dashboard',
        'robodesk-debug-log',
        'robodesk_debug_log_page_callback'
    );
}


function robodesk_update_taxonomy_order()
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
add_action('wp_ajax_robodesk_update_taxonomy_order', 'robodesk_update_taxonomy_order');

function robodesk_modify_taxonomy_query($query)
{
    if (
        is_admin() &&
        isset($query->query_vars['taxonomy']) &&
        $query->query_vars['taxonomy'] === 'robodesk_ticket_priority'
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
add_action('pre_get_terms', 'robodesk_modify_taxonomy_query');

function robodesk_set_default_taxonomy_order($term_id, $tt_id, $taxonomy)
{
    if ($taxonomy === 'robodesk_ticket_priority') {
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
add_action('created_term', 'robodesk_set_default_taxonomy_order', 10, 3);

function robodesk_custom_taxonomy_order($terms, $taxonomies, $args)
{
    if (
        is_admin() &&
        in_array('robodesk_ticket_priority', $taxonomies) &&
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
add_filter('get_terms', 'robodesk_custom_taxonomy_order', 10, 3);


function robodesk_add_custom_comment_type($commentdata)
{
    if (get_post_type($commentdata['comment_post_ID']) === 'robodesk_ticket') {
        $commentdata['comment_type'] = 'ticket_replies';
        $commentdata['comment_content'] = wp_kses_post($commentdata['comment_content']);
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'robodesk_add_custom_comment_type');



// Enqueue Tailwind CSS and custom styles
//if page=robodesk-dashboard then enqueue styles
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (isset($_GET['page']) && $_GET['page'] === 'robodesk-dashboard') {
    add_action('admin_enqueue_scripts', 'robodesk_enqueue_tailwind_and_styles');
}
function robodesk_enqueue_tailwind_and_styles()
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
        'robodesk-dashboard-styles',
        plugin_dir_url(__FILE__) . 'assets/css/robodesk-dashboard.css',
        [],
        '1.0.0'
    );
    wp_add_inline_style('robodesk-dashboard-styles', '.hidden { display: none; }');

    wp_enqueue_script(
        'robodesk-dashboard-script',
        plugin_dir_url(__FILE__) . 'assets/js/robodesk-dashboard.js',
        ['jquery'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/robodesk-dashboard.js'),
        true
    );

    // Note: Slack and Discord integration scripts have been moved to the robodesk-addon plugin
    // They will be enqueued automatically when the respective addons are activated

    wp_enqueue_style(
        'font-awesome',
        plugin_dir_url(__FILE__) . 'assets/css/font-awesome.min.css',
        [],
        '6.0.0'
    );

    // Localize script with all necessary nonces
    wp_localize_script('robodesk-dashboard-script', 'robodeskAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajax_url' => admin_url('admin-ajax.php'), // Backward compatibility
        'rest_url' => rest_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'nonces' => [
            'imap_settings' => wp_create_nonce('robodesk_imap_settings_action'),
            'email_template' => wp_create_nonce('robodesk_email_template_action'),
            'advanced_settings' => wp_create_nonce('robodesk_advanced_settings_action'),
            'basic_settings' => wp_create_nonce('robodesk_basic_settings_action'),
            'user_lookup' => wp_create_nonce('robodesk_user_lookup_action'),
            'fetch_emails' => wp_create_nonce('robodesk_fetch_emails_action'),
            'ticket_assignment' => wp_create_nonce('robodesk_ticket_assignment_action'),
            'create_ticket' => wp_create_nonce('robodesk_create_ticket_action'),
            'get_ticket_detail' => wp_create_nonce('robodesk_get_ticket_detail_action'),
            'ticket_status' => wp_create_nonce('robodesk_ticket_status_action'),
            'ticket_priority' => wp_create_nonce('robodesk_ticket_priority_action'),
            'ticket_assignment' => wp_create_nonce('robodesk_ticket_assignment_action'),
            'add_ticket_reply' => wp_create_nonce('robodesk_add_ticket_reply_action'),
            'get_ticket_replies' => wp_create_nonce('robodesk_get_ticket_replies_action'),
            'add_ticket_note' => wp_create_nonce('robodesk_add_ticket_note_action'),
            'get_ticket_notes' => wp_create_nonce('robodesk_get_ticket_notes_action'),
            'get_ticket_replies_and_notes' => wp_create_nonce('robodesk_get_ticket_replies_and_notes_action'),
            'fetch_emails' => wp_create_nonce('robodesk_fetch_emails_action'),
            // Slack Integration Nonces
            'slack_status' => wp_create_nonce('robodesk_slack_status_action'),
            'slack_connect' => wp_create_nonce('robodesk_slack_connect_action'),
            'slack_disconnect' => wp_create_nonce('robodesk_slack_disconnect_action'),
            'slack_save_credentials' => wp_create_nonce('robodesk_slack_credentials_action'),
            'slack_get_credentials' => wp_create_nonce('robodesk_slack_get_credentials_action'),
            'slack_save_settings' => wp_create_nonce('robodesk_slack_settings_action'),
            'slack_workspace' => wp_create_nonce('robodesk_slack_workspace_action'),
            'slack_send_message' => wp_create_nonce('robodesk_slack_send_message_action'),
            'slack_send_ticket' => wp_create_nonce('robodesk_slack_send_ticket_action'),
            'slack_send_reply' => wp_create_nonce('robodesk_slack_send_reply_action'),
            'slack_fetch_messages' => wp_create_nonce('robodesk_slack_fetch_messages_action'),
            'slack_test' => wp_create_nonce('robodesk_slack_test_action'),
            // Discord Integration Nonces
            'discord_save_webhook' => wp_create_nonce('robodesk_discord_save_webhook'),
            'discord_get_webhook' => wp_create_nonce('robodesk_discord_get_webhook'),
            'discord_test_connection' => wp_create_nonce('robodesk_discord_test_connection'),
            'discord_disconnect' => wp_create_nonce('robodesk_discord_disconnect'),
            'discord_save_settings' => wp_create_nonce('robodesk_discord_save_settings'),
            'discord_send_test_message' => wp_create_nonce('robodesk_discord_send_test_message'),
            'close_ticket' => wp_create_nonce('robodesk_close_ticket_action'),
            'update_ticket_category' => wp_create_nonce('robodesk_update_ticket_category_action'),
            'bulk_action' => wp_create_nonce('robodesk_bulk_action'),
            'refresh_dashboard_tickets' => wp_create_nonce('robodesk_refresh_dashboard_tickets_action'),
            'customer_tickets' => wp_create_nonce('robodesk_customer_tickets_action'),
            'notifications' => wp_create_nonce('robodesk_notifications_action'),

        ]
    ]);

    // Pass notification sound URL to JavaScript
    wp_localize_script('robodesk-dashboard-script', 'robodeskNotificationSound', get_option('robodesk_notification_sound', ''));

    // Pass advanced settings to JavaScript
    $robodesk_adv_settings = get_option('robodesk_advanced_settings', []);
    wp_localize_script('robodesk-dashboard-script', 'robodeskAdvancedSettings', [
        'polling_interval' => isset($robodesk_adv_settings['polling_interval']) ? absint($robodesk_adv_settings['polling_interval']) : 30,
    ]);
}

/**
 * Displays the active + my-conversation ticket counts side by side on the
 * WordPress admin toolbar using the same badge styling as the dashboard
 * filter counts.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
 */
function robodesk_admin_bar_active_tickets($wp_admin_bar)
{
    if (!current_user_can('robodesk_access_dashboard')) {
        return;
    }

    $counters    = robodesk_get_ticket_counters(get_current_user_id());
    $total_count = (int) $counters['counts']['open'];
    $my_count    = (int) $counters['counts']['my'];

    $badge = function ($count, $label) {
        return ' <span class="dashboard-filter-count-badge is-visible" title="' . esc_attr($label) . '">' . esc_html($count) . '</span>';
    };

    // Open the dashboard with the matching conversation filter applied.
    // The nonce lets the server pre-select the right filter button.
    $active_url = wp_nonce_url(
        admin_url('admin.php?page=robodesk-dashboard&filter=open_conversation'),
        'robodesk_filter'
    );
    $my_url = wp_nonce_url(
        admin_url('admin.php?page=robodesk-dashboard&filter=my_conversation'),
        'robodesk_filter'
    );

    $wp_admin_bar->add_node(array(
        'id'    => 'robodesk-active-tickets',
        'title' => '<span class="ab-icon dashicons dashicons-email-alt"></span>'
            . '<span class="robodesk-adminbar-tickets"><a href="' . esc_url($active_url) . '">'
            . esc_html__('Active Tickets', 'robodesk')
            . $badge($total_count, esc_html__('Active conversations', 'robodesk'))
            . '</a></span>'
            . '<span class="robodesk-adminbar-tickets"><a href="' . esc_url($my_url) . '">'
            . esc_html__('My Tickets', 'robodesk')
            . $badge($my_count, esc_html__('My conversations', 'robodesk'))
            . '</a></span>',
        'meta'  => array(
            'title' => esc_html__('View Conversations', 'robodesk'),
        ),
    ));

    // Badge styles must render even on pages where the dashboard stylesheet
    // is not loaded (any admin page or the frontend toolbar).
    if (!did_action('robodesk_admin_bar_ticket_badge_styles')) {
        add_action('admin_footer', 'robodesk_admin_bar_ticket_badge_styles', 1);
        add_action('wp_footer', 'robodesk_admin_bar_ticket_badge_styles', 1);
        do_action('robodesk_admin_bar_ticket_badge_styles');
    }
}
add_action('admin_bar_menu', 'robodesk_admin_bar_active_tickets', 999);

/**
 * Prints the admin toolbar badge styles once.
 */
function robodesk_admin_bar_ticket_badge_styles()
{
    echo '<style id="robodesk-adminbar-ticket-badge">'
        . '#wp-admin-bar-robodesk-active-tickets{display:inline-block}'
        . '#wp-admin-bar-robodesk-active-tickets>.ab-item{display:flex;align-items:center;height:auto;padding:0 .35rem}'
        . '#wp-admin-bar-robodesk-active-tickets:hover>.ab-item,#wp-admin-bar-robodesk-active-tickets>.ab-item:hover{background:transparent!important;color:inherit!important}'
        . '#wp-admin-bar-robodesk-active-tickets:hover .ab-icon:before{color:inherit!important}'
        . '#wp-admin-bar-robodesk-active-tickets .robodesk-adminbar-tickets{display:inline-flex;align-items:center;margin-right:.35rem}'
        . '#wp-admin-bar-robodesk-active-tickets .robodesk-adminbar-tickets:last-child{margin-right:0}'
        . '#wp-admin-bar-robodesk-active-tickets .robodesk-adminbar-tickets a{display:inline-flex;align-items:center;color:inherit;text-decoration:none;padding:0 .5rem;border-radius:3px;line-height:28px}'
        . '#wp-admin-bar-robodesk-active-tickets .robodesk-adminbar-tickets a:hover{background:#2c3338;color:#72aee6}'
        . '#wp-admin-bar-robodesk-active-tickets .dashboard-filter-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:1.15rem;height:1.15rem;margin-left:.3rem;padding:0 .28rem;border-radius:9999px;background-color:#ef4444;color:#fff;font-size:.7rem;font-weight:600;line-height:1;vertical-align:middle}'
        . '</style>';
}
