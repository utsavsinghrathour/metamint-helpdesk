<?php
// Register FAQ Post Type and Taxonomy
add_action('init', 'robodesk_register_faq_post_type');
function robodesk_register_faq_post_type()
{
    $faq_labels = array(
        'name'                  => _x('FAQs', 'post type general name', 'robodesk-support-ticket'),
        'singular_name'         => _x('FAQ', 'post type singular name', 'robodesk-support-ticket'),
        'menu_name'             => __('FAQs', 'robodesk-support-ticket'),
        'name_admin_bar'        => __('FAQ', 'robodesk-support-ticket'),
        'add_new'               => __('Add New', 'robodesk-support-ticket'),
        'add_new_item'          => __('Add New FAQ', 'robodesk-support-ticket'),
        'new_item'              => __('New FAQ', 'robodesk-support-ticket'),
        'edit_item'             => __('Edit FAQ', 'robodesk-support-ticket'),
        'view_item'             => __('View FAQ', 'robodesk-support-ticket'),
        'all_items'             => __('All FAQs', 'robodesk-support-ticket'),
        'search_items'          => __('Search FAQs', 'robodesk-support-ticket'),
        'parent_item_colon'     => __('Parent FAQs:', 'robodesk-support-ticket'),
        'not_found'             => __('No FAQs found.', 'robodesk-support-ticket'),
        'not_found_in_trash'    => __('No FAQs found in Trash.', 'robodesk-support-ticket'),
        'featured_image'        => __('FAQ Featured Image', 'robodesk-support-ticket'),
        'set_featured_image'    => __('Set featured image', 'robodesk-support-ticket'),
        'remove_featured_image' => __('Remove featured image', 'robodesk-support-ticket'),
        'use_featured_image'    => __('Use as featured image', 'robodesk-support-ticket'),
        'archives'              => __('FAQ Archives', 'robodesk-support-ticket'),
        'insert_into_item'      => __('Insert into FAQ', 'robodesk-support-ticket'),
        'uploaded_to_this_item' => __('Uploaded to this FAQ', 'robodesk-support-ticket'),
        'filter_items_list'     => __('Filter FAQs list', 'robodesk-support-ticket'),
        'items_list_navigation' => __('FAQs list navigation', 'robodesk-support-ticket'),
        'items_list'            => __('FAQs list', 'robodesk-support-ticket'),
    );

    $faq_args = array(
        'labels'             => $faq_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'faqs'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'excerpt', 'custom-fields', 'thumbnail'), // Added 'thumbnail' support
    );

    register_post_type('robodesk_faq', $faq_args);



    // FAQ Category Taxonomy
    $faq_category_labels = array(
        'name'                       => _x('FAQ Categories', 'taxonomy general name', 'robodesk-support-ticket'),
        'singular_name'              => _x('FAQ Category', 'taxonomy singular name', 'robodesk-support-ticket'),
        'search_items'               => __('Search FAQ Categories', 'robodesk-support-ticket'),
        'all_items'                  => __('All FAQ Categories', 'robodesk-support-ticket'),
        'parent_item'                => __('Parent FAQ Category', 'robodesk-support-ticket'),
        'parent_item_colon'          => __('Parent FAQ Category:', 'robodesk-support-ticket'),
        'edit_item'                  => __('Edit FAQ Category', 'robodesk-support-ticket'),
        'update_item'                => __('Update FAQ Category', 'robodesk-support-ticket'),
        'add_new_item'               => __('Add New FAQ Category', 'robodesk-support-ticket'),
        'new_item_name'              => __('New FAQ Category Name', 'robodesk-support-ticket'),
        'separate_items_with_commas' => __('Separate FAQ Categories with commas', 'robodesk-support-ticket'),
        'add_or_remove_items'        => __('Add or remove FAQ Categories', 'robodesk-support-ticket'),
        'choose_from_most_used'      => __('Choose from the most used FAQ Categories', 'robodesk-support-ticket'),
        'not_found'                  => __('No FAQ Categories found.', 'robodesk-support-ticket'),
        'menu_name'                  => __('FAQ Categories', 'robodesk-support-ticket'),
    );

    $faq_category_args = array(
        'hierarchical'          => true,
        'labels'                => $faq_category_labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'faq-category'),
    );

    register_taxonomy('robodesk_faq_category', 'robodesk_faq', $faq_category_args);
}


register_post_meta('robodesk_faq', '_robodesk_featured', array(
    'type' => 'string',
    'single' => true,
    'show_in_rest' => array(
        'schema' => array(
            'type' => 'boolean',
        ),
    ),
));
// Add Featured metabox to FAQ post type
add_action('add_meta_boxes', 'robodesk_add_featured_metabox');
function robodesk_add_featured_metabox() {
    add_meta_box(
        'robodesk_featured_metabox',
        __('Featured?', 'robodesk-support-ticket'),
        'robodesk_render_featured_metabox',
        'robodesk_faq',
        'side',
        'default'
    );
}

// Render the Featured metabox
function robodesk_render_featured_metabox($post) {
    // Get the current value
    $featured = get_post_meta($post->ID, '_robodesk_featured', true);
    
    // Output the nonce field
    wp_nonce_field('robodesk_featured_metabox', 'robodesk_featured_metabox_nonce');
    
    // Output the checkbox
    ?>
    <label for="robodesk_featured">
        <input type="checkbox" id="robodesk_featured" name="robodesk_featured" value="true" <?php checked($featured, 'true'); ?> />
        <?php _e('Mark as featured', 'robodesk-support-ticket'); ?>
    </label>
    <?php
}

// Save the Featured metabox data
add_action('save_post', 'robodesk_save_featured_metabox');
function robodesk_save_featured_metabox($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['robodesk_featured_metabox_nonce'])) {
        return;
    }
    
    // Verify the nonce
    if (!wp_verify_nonce($_POST['robodesk_featured_metabox_nonce'], 'robodesk_featured_metabox')) {
        return;
    }
    
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check the post type
    if (get_post_type($post_id) !== 'robodesk_faq') {
        return;
    }
    
    // Update the meta field
    $featured = (isset($_POST['robodesk_featured']) && $_POST['robodesk_featured'] === 'true') ? 'true' : 'false';
    update_post_meta($post_id, '_robodesk_featured', $featured);
}


add_filter('rest_robodesk_faq_query', 'robodesk_filter_faqs_by_featured', 10, 2);
function robodesk_filter_faqs_by_featured($args, $request) {


    if (isset($request['featured']) && $request['featured'] === 'true') {
      
        $args['meta_query'] = array(
            array(
                'key' => '_robodesk_featured',
                'value' => 'true',
                'compare' => '='
            )
        );
      
    }
    return $args;
}