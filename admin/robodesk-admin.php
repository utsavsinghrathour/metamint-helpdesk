<?php

// Include admin-side post types
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-ticket-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-faq-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-fetch-tickets.php'; // Include the email-to-ticket function


// Add Admin Menu Pages (if needed)
// add_action('admin_menu', 'robodesk_add_admin_menu');
// function robodesk_add_admin_menu() {
// }


// Other admin related functions can go here
add_action('admin_menu', 'robodesk_add_admin_menu');
function robodesk_add_admin_menu()
{
    add_menu_page(
        'Robodesk',
        'Robodesk Support',
        'manage_options',
        'edit.php?post_type=robodesk_ticket',
        '',
        'dashicons-email-alt',
        25
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All Tickets',
        'All Tickets',
        'edit_tickets', // Use custom capability for the submenus
        'edit.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New Ticket',
        'Add New',
        'edit_tickets',
        'post-new.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Origins',
        'Ticket Origins',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_origin&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Priorities',
        'Ticket Priorities',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_priority&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New FAQ',
        'Add New FAQ',
        'edit_posts',
        'post-new.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'FAQ Categories',
        'FAQ Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_faq_category&post_type=robodesk_faq'
    );
    // making the submenu page for the email-to-ticket plugin functions to be under the robodesk menu
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Robodesk Ticket',
        'Robodesk Ticket',
        'manage_options',
        'robodesk-email-to-ticket',
        'robodesk_fetch_emails_callback'

    );
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
