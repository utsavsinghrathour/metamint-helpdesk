<?php

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
        'manage_options',
        'robodesk-dashboard',
        'robodesk_dashboard_callback',
        'dashicons-email-alt',
        25
    );

    // Dashboard (default submenu)
    add_submenu_page(
        'robodesk-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'robodesk-dashboard',
        'robodesk_dashboard_callback'
    );

    // All Tickets (linking to CPT)
    add_submenu_page(
        'robodesk-dashboard',
        'All Tickets',
        'All Tickets',
        'edit_posts',
        'edit.php?post_type=robodesk_ticket'
    );

    // // Add New Ticket
    // add_submenu_page(
    //     'robodesk-dashboard',
    //     'Add New Ticket',
    //     'Add New Ticket',
    //     'edit_posts',
    //     'post-new.php?post_type=robodesk_ticket'
    // );

    // Ticket Origins (taxonomy)
    // add_submenu_page(
    //     'robodesk-dashboard',
    //     'Ticket Origins',
    //     'Ticket Origins',
    //     'manage_categories',
    //     'edit-tags.php?taxonomy=robodesk_ticket_origin&post_type=robodesk_ticket'
    // );

    add_submenu_page(
        'robodesk-dashboard',
        'Ticket Categories',
        'Ticket Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_category&post_type=robodesk_ticket'
    );


    // Ticket Priorities (taxonomy)
    add_submenu_page(
        'robodesk-dashboard',
        'Ticket Priorities',
        'Ticket Priorities',
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

    // Fetch Emails
    add_submenu_page(
        'robodesk-dashboard',
        'Fetch Tickets',
        'Fetch Tickets',
        'manage_options',
        'robodesk-email-to-ticket',
        'robodesk_fetch_emails_callback'
    );

    // Uncomment if needed later
    /*
    add_submenu_page(
        'robodesk-dashboard',
        'Robodesk Settings for Email',
        'Email Settings',
        'manage_options',
        'robodesk_imap_settings',
        'robodesk_imap_settings_callback'
    );
    */

    // // Hidden callback page
    // add_submenu_page(
    //     null,
    //     'Email Callback',
    //     'Email Callback',
    //     'manage_options',
    //     'email-callback',
    //     'email_fetcher_callback'
    // );
}


function update_taxonomy_order()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'taxonomy_order_nonce')) {
        wp_send_json_error('Invalid nonce');
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
add_action('wp_ajax_update_taxonomy_order', 'update_taxonomy_order');

function modify_taxonomy_query($query)
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
add_action('pre_get_terms', 'modify_taxonomy_query');

function set_default_taxonomy_order($term_id, $tt_id, $taxonomy)
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
add_action('created_term', 'set_default_taxonomy_order', 10, 3);

function custom_taxonomy_order($terms, $taxonomies, $args)
{
    if (
        is_admin() &&
        in_array('robodesk_ticket_priority', $taxonomies) &&
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
add_filter('get_terms', 'custom_taxonomy_order', 10, 3);


function add_custom_comment_type($commentdata)
{
    if (get_post_type($commentdata['comment_post_ID']) === 'robodesk_ticket') {
        $commentdata['comment_type'] = 'ticket_replies';
        $commentdata['comment_content'] = wp_kses_post($commentdata['comment_content']);
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'add_custom_comment_type');


add_action('pre_get_comments', 'exclude_ticket_replies');
function exclude_ticket_replies($query)
{
    if (is_admin()) {
        $query->query_vars['type__not_in'] = array('ticket_replies');
    }
}

// Enqueue Tailwind CSS and custom styles
//if page=robodesk-dashboard then enqueue styles
if (isset($_GET['page']) && $_GET['page'] === 'robodesk-dashboard') {
    add_action('admin_enqueue_scripts', 'robodesk_enqueue_tailwind_and_styles');
}
function robodesk_enqueue_tailwind_and_styles()
{
    // Load Tailwind CDN browser runtime
    wp_enqueue_script(
        'tailwind-browser-cdn',
        'https://cdn.tailwindcss.com',
        [], // No dependencies
        null,
        false // Load in header
    );

    // Load custom CSS
    wp_enqueue_style(
        'robodesk-dashboard-styles',
        plugin_dir_url(__FILE__) . 'assets/css/robodesk-dashboard.css',
        [],
        '1.0.0'
    );

    // Enqueue custom JavaScript
    wp_enqueue_script(
        'robodesk-dashboard-script',
        plugin_dir_url(__FILE__) . 'assets/js/robodesk-dashboard.js',
        ['jquery'],
        '1.0.0',
        true // Load in footer
    );

    // Enqueue Font Awesome for icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        [],
        '6.0.0'
    );

    // Localize script with all necessary nonces
    wp_localize_script('robodesk-dashboard-script', 'robodeskAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonces' => [
            'imap_settings' => wp_create_nonce('robodesk_imap_settings_action'),
            'email_template' => wp_create_nonce('robodesk_email_template_action'),
            'advanced_settings' => wp_create_nonce('robodesk_advanced_settings_action'),
            'basic_settings' => wp_create_nonce('robodesk_basic_settings_action'),
            'fetch_emails' => wp_create_nonce('robodesk_fetch_emails_action'),
            'ticket_assignment' => wp_create_nonce('robodesk_ticket_assignment_action'),
            'create_ticket' => wp_create_nonce('robodesk_create_ticket_action'),
            'ticket_status' => wp_create_nonce('robodesk_ticket_status_action'),
            'ticket_priority' => wp_create_nonce('robodesk_ticket_priority_action'),
        ]
    ]);
}
