<?php


// Register Ticket Post Type and Taxonomies
add_action('init', 'robodesk_register_ticket_post_type');
function robodesk_register_ticket_post_type()
{
    $ticket_labels = array(
        'name'                  => _x('Robodesk Tickets', 'post type general name', 'robodesk-support-ticket'),
        'singular_name'         => _x('Robodesk Ticket', 'post type singular name', 'robodesk-support-ticket'),
        'menu_name'             => __('Tickets', 'robodesk-support-ticket'),
        'name_admin_bar'        => __('Ticket', 'robodesk-support-ticket'),
        'add_new'               => __('Add New', 'robodesk-support-ticket'),
        'add_new_item'          => __('Add New Ticket', 'robodesk-support-ticket'),
        'new_item'              => __('New Ticket', 'robodesk-support-ticket'),
        'edit_item'             => __('Edit Ticket', 'robodesk-support-ticket'),
        'view_item'             => __('View Ticket', 'robodesk-support-ticket'),
        'all_items'             => __('All Tickets', 'robodesk-support-ticket'),
        'search_items'          => __('Search Tickets', 'robodesk-support-ticket'),
        'parent_item_colon'     => __('Parent Tickets:', 'robodesk-support-ticket'),
        'not_found'             => __('No tickets found.', 'robodesk-support-ticket'),
        'not_found_in_trash'    => __('No tickets found in Trash.', 'robodesk-support-ticket'),
        'featured_image'        => __('Ticket Featured Image', 'robodesk-support-ticket'),
        'set_featured_image'    => __('Set featured image', 'robodesk-support-ticket'),
        'remove_featured_image' => __('Remove featured image', 'robodesk-support-ticket'),
        'use_featured_image'    => __('Use as featured image', 'robodesk-support-ticket'),
        'archives'              => __('Ticket Archives', 'robodesk-support-ticket'),
        'insert_into_item'      => __('Insert into Ticket', 'robodesk-support-ticket'),
        'uploaded_to_this_item' => __('Uploaded to this Ticket', 'robodesk-support-ticket'),
        'filter_items_list'     => __('Filter Tickets list', 'robodesk-support-ticket'),
        'items_list_navigation' => __('Tickets list navigation', 'robodesk-support-ticket'),
        'items_list'            => __('Tickets list', 'robodesk-support-ticket'),
    );


    $ticket_args = array(
        'labels'             => $ticket_labels,
        'public'             => true,
        'has_archive'        => true,
        'publicly_queryable' => is_user_logged_in(), // Only logged in users can view tickets
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'robodesk-tickets'),
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'comments', 'custom-fields'),
        'taxonomies'         => array('robodesk_ticket_priority'),
        'capabilities' => array(
            'create_posts' => 'create_tickets',
            'edit_posts' => 'edit_tickets',
            'edit_others_posts' => 'edit_others_tickets',
            'publish_posts' => 'publish_tickets',
            'read_private_posts' => 'read_private_tickets',
            'read_post' => 'read_ticket',
            'delete_post' => 'delete_ticket',
        ),
        'map_meta_cap' => true, // This is CRUCIAL
    );

    register_post_type('robodesk_ticket', $ticket_args);

    // Ticket Origin Taxonomy
    $ticket_origin_labels = array(
        'name'              => _x('Ticket Origins', 'taxonomy general name', 'robodesk-support-ticket'),
        'singular_name'     => _x('Ticket Origin', 'taxonomy singular name', 'robodesk-support-ticket'),
        'search_items'      => __('Search Ticket Origins', 'robodesk-support-ticket'),
        'all_items'         => __('All Ticket Origins', 'robodesk-support-ticket'),
        'parent_item'       => __('Parent Ticket Origin', 'robodesk-support-ticket'),
        'parent_item_colon' => __('Parent Ticket Origin:', 'robodesk-support-ticket'),
        'edit_item'         => __('Edit Ticket Origin', 'robodesk-support-ticket'),
        'update_item'       => __('Update Ticket Origin', 'robodesk-support-ticket'),
        'add_new_item'      => __('Add New Ticket Origin', 'robodesk-support-ticket'),
        'new_item_name'     => __('New Ticket Origin Name', 'robodesk-support-ticket'),
        'menu_name'         => __('Ticket Origins', 'robodesk-support-ticket'),
    );

    $ticket_origin_args = array(
        'hierarchical'      => true,
        'labels'            => $ticket_origin_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'ticket-origin'),
    );

    register_taxonomy('robodesk_ticket_origin', 'robodesk_ticket', $ticket_origin_args);

    // Ticket Priority Taxonomy
    $ticket_priority_labels = array(
        'name'              => _x('Ticket Priorities', 'taxonomy general name', 'robodesk-support-ticket'),
        'singular_name'     => _x('Ticket Priority', 'taxonomy singular name', 'robodesk-support-ticket'),
        'search_items'      => __('Search Ticket Priorities', 'robodesk-support-ticket'),
        'all_items'         => __('All Ticket Priorities', 'robodesk-support-ticket'),
        'parent_item'       => __('Parent Ticket Priority', 'robodesk-support-ticket'),
        'parent_item_colon' => __('Parent Ticket Priority:', 'robodesk-support-ticket'),
        'edit_item'         => __('Edit Ticket Priority', 'robodesk-support-ticket'),
        'update_item'       => __('Update Ticket Priority', 'robodesk-support-ticket'),
        'add_new_item'      => __('Add New Ticket Priority', 'robodesk-support-ticket'),
        'new_item_name'     => __('New Ticket Priority Name', 'robodesk-support-ticket'),
        'menu_name'         => __('Ticket Priorities', 'robodesk-support-ticket'),
    );

    $ticket_priority_args = array(
        'hierarchical'      => true,
        'labels'            => $ticket_priority_labels,
        'publicly_queryable'=> true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'sort'              => true,
        'rewrite'           => array('slug' => 'ticket-priority'),
    );

    register_taxonomy('robodesk_ticket_priority', array('robodesk_ticket'), $ticket_priority_args);
}

// Meta Box for Ticket Status (Unchanged)
add_action('add_meta_boxes', 'robodesk_add_status_meta_box');
function robodesk_add_status_meta_box($post)
{
    add_meta_box('robodesk_ticket_status', 'Ticket Status', 'robodesk_status_meta_box_callback', 'robodesk_ticket', 'side', 'high');
}

function robodesk_status_meta_box_callback($post)
{
    wp_nonce_field('robodesk_ticket_status_nonce', 'robodesk_ticket_status_nonce');
    $value = get_post_meta($post->ID, '_robodesk_ticket_status', true);
?>
    <select name="robodesk_ticket_status" id="robodesk_ticket_status">
        <option value="open" <?php selected($value, 'open'); ?>>Open</option>
        <option value="in-progress" <?php selected($value, 'in-progress'); ?>>In Progress</option>
        <option value="closed" <?php selected($value, 'closed'); ?>>Closed</option>
    </select>
<?php
}

add_action('save_post', 'robodesk_save_status_meta_box_data');
function robodesk_save_status_meta_box_data($post_id)
{
    if (! isset($_POST['robodesk_ticket_status_nonce']) || ! wp_verify_nonce($_POST['robodesk_ticket_status_nonce'], 'robodesk_ticket_status_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'robodesk_ticket' == $_POST['post_type']) {
        if (! current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (isset($_POST['robodesk_ticket_status'])) {
        update_post_meta($post_id, '_robodesk_ticket_status', sanitize_text_field($_POST['robodesk_ticket_status']));
    }
}
?>