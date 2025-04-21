<?php 


// Register the custom post type 'notice'
function robodesk_register_notice_post_type() {
    $labels = array(
        'name'               => 'Notices',
        'singular_name'      => 'Notice',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Notice',
        'edit_item'          => 'Edit Notice',
        'new_item'           => 'New Notice',
        'view_item'          => 'View Notice',
        'search_items'       => 'Search Notices',
        'not_found'          => 'No notices found',
        'not_found_in_trash' => 'No notices found in Trash',
        'menu_name'          => 'Notices',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_in_menu'       => false,
        'has_archive'        => true,
        'show_in_rest'       => true, 
        'supports'           => array('title', 'editor','excerpt' ,'author','thumbnail'),
        
    );

    register_post_type('notice', $args);
}

// 'labels'             => $faq_labels,
// 'public'             => true,
// 'publicly_queryable' => true,
// 'show_ui'            => true,
// 'show_in_menu'       => false,
// 'show_in_rest'       => true,
// 'query_var'          => true,
// 'rewrite'            => array('slug' => 'faqs'),
// 'capability_type'    => 'post',
// 'has_archive'        => true,
// 'hierarchical'       => false,
// 'menu_position'      => null,
// 'supports'           => array('title', 'editor', 'excerpt', 'author', 'custom-fields', 'thumbnail'), 

add_action('init', 'robodesk_register_notice_post_type');

// Add meta boxes for 'Active?' and 'Feature?' checkboxes
function robodesk_add_notice_meta_boxes() {
    add_meta_box(
        'notice_active',
        'Active?',
        'notice_active_callback',
        'notice',
        'side',
        'default'
    );
    add_meta_box(
        'notice_feature',
        'Feature?',
        'notice_feature_callback',
        'notice',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'robodesk_add_notice_meta_boxes');

// Callback function for 'Active?' meta box
function notice_active_callback($post) {
    wp_nonce_field('save_notice_active', 'notice_active_nonce');
    $value = get_post_meta($post->ID, '_robodesk_notice_active', true);
    echo '<label><input type="checkbox" name="_robodesk_notice_active" value="true" ' . checked($value, 'true', false) . ' /> Active?</label>';
}

// Callback function for 'Feature?' meta box
function notice_feature_callback($post) {
    wp_nonce_field('save_notice_feature', 'notice_feature_nonce');
    $value = get_post_meta($post->ID, '_robodesk_notice_feature', true);
    echo '<label><input type="checkbox" name="_robodesk_notice_feature" value="true" ' . checked($value, 'true', false) . ' /> Feature?</label>';
}

// Save the meta data when the post is saved
function save_notice_meta($post_id) {
    // Check if this is the correct post type
    if (get_post_type($post_id) !== 'notice') {
        return;
    }
    // Verify user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    // Prevent autosave from triggering
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Save 'Active?' checkbox
    if (isset($_POST['notice_active_nonce']) && wp_verify_nonce($_POST['notice_active_nonce'], 'save_notice_active')) {
        if (isset($_POST['_robodesk_notice_active']) && $_POST['_robodesk_notice_active'] === 'true') {
            update_post_meta($post_id, '_robodesk_notice_active', 'true');
        } else {
            delete_post_meta($post_id, '_robodesk_notice_active');
        }
    }

    // Save 'Feature?' checkbox
    if (isset($_POST['notice_feature_nonce']) && wp_verify_nonce($_POST['notice_feature_nonce'], 'save_notice_feature')) {
        if (isset($_POST['_robodesk_notice_feature']) && $_POST['_robodesk_notice_feature'] === 'true') {
            update_post_meta($post_id, '_robodesk_notice_feature', 'true');
        } else {
            delete_post_meta($post_id, '_robodesk_notice_feature');
        }
    }
}
add_action('save_post', 'save_notice_meta');

//Rest filters and hooks 

add_filter('rest_notice_query', 'robodesk_filter_notice_by_featured_and_active', 10, 2);
function robodesk_filter_notice_by_featured_and_active($args, $request) {
 
    if (isset($request['featured']) && $request['featured'] === 'true' && isset($request['active']) && $request['active'] === 'true') {
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key'     => '_robodesk_notice_feature',
                'value'   => 'true',
                'compare' => '='
            ),
            array(
                'key'     => '_robodesk_notice_active',
                'value'   => 'true',
                'compare' => '='
            )
        );
    }

    return $args;
}
