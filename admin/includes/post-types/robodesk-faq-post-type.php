<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Register FAQ Post Type and Taxonomy
add_action('init', 'robodesk_register_faq_post_type');
function robodesk_register_faq_post_type()
{
    $faq_labels = array(
        'name'                  => _x('FAQs', 'post type general name', 'robodesk'),
        'singular_name'         => _x('FAQ', 'post type singular name', 'robodesk'),
        'menu_name'             => __('FAQs', 'robodesk'),
        'name_admin_bar'        => __('FAQ', 'robodesk'),
        'add_new'               => __('Add New', 'robodesk'),
        'add_new_item'          => __('Add New FAQ', 'robodesk'),
        'new_item'              => __('New FAQ', 'robodesk'),
        'edit_item'             => __('Edit FAQ', 'robodesk'),
        'view_item'             => __('View FAQ', 'robodesk'),
        'all_items'             => __('All FAQs', 'robodesk'),
        'search_items'          => __('Search FAQs', 'robodesk'),
        'parent_item_colon'     => __('Parent FAQs:', 'robodesk'),
        'not_found'             => __('No FAQs found.', 'robodesk'),
        'not_found_in_trash'    => __('No FAQs found in Trash.', 'robodesk'),
        'featured_image'        => __('FAQ Featured Image', 'robodesk'),
        'set_featured_image'    => __('Set featured image', 'robodesk'),
        'remove_featured_image' => __('Remove featured image', 'robodesk'),
        'use_featured_image'    => __('Use as featured image', 'robodesk'),
        'archives'              => __('FAQ Archives', 'robodesk'),
        'insert_into_item'      => __('Insert into FAQ', 'robodesk'),
        'uploaded_to_this_item' => __('Uploaded to this FAQ', 'robodesk'),
        'filter_items_list'     => __('Filter FAQs list', 'robodesk'),
        'items_list_navigation' => __('FAQs list navigation', 'robodesk'),
        'items_list'            => __('FAQs list', 'robodesk'),
    );

    $faq_args = array(
        'labels'             => $faq_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'faqs'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'excerpt', 'author', 'custom-fields', 'thumbnail'),
    );

    register_post_type('robodesk_faq', $faq_args);



    // FAQ Category Taxonomy
    $faq_category_labels = array(
        'name'                       => _x('FAQ Categories', 'taxonomy general name', 'robodesk'),
        'singular_name'              => _x('FAQ Category', 'taxonomy singular name', 'robodesk'),
        'search_items'               => __('Search FAQ Categories', 'robodesk'),
        'all_items'                  => __('All FAQ Categories', 'robodesk'),
        'parent_item'                => __('Parent FAQ Category', 'robodesk'),
        'parent_item_colon'          => __('Parent FAQ Category:', 'robodesk'),
        'edit_item'                  => __('Edit FAQ Category', 'robodesk'),
        'update_item'                => __('Update FAQ Category', 'robodesk'),
        'add_new_item'               => __('Add New FAQ Category', 'robodesk'),
        'new_item_name'              => __('New FAQ Category Name', 'robodesk'),
        'separate_items_with_commas' => __('Separate FAQ Categories with commas', 'robodesk'),
        'add_or_remove_items'        => __('Add or remove FAQ Categories', 'robodesk'),
        'choose_from_most_used'      => __('Choose from the most used FAQ Categories', 'robodesk'),
        'not_found'                  => __('No FAQ Categories found.', 'robodesk'),
        'menu_name'                  => __('FAQ Categories', 'robodesk'),
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
function robodesk_add_featured_metabox()
{
    add_meta_box(
        'robodesk_featured_metabox',
        __('Featured?', 'robodesk'),
        'robodesk_render_featured_metabox',
        'robodesk_faq',
        'side',
        'default'
    );
}

// Render the Featured metabox
function robodesk_render_featured_metabox($post)
{
    // Get the current value
    $featured = get_post_meta($post->ID, '_robodesk_featured', true);

    // Output the nonce field
    wp_nonce_field('robodesk_featured_metabox', 'robodesk_featured_metabox_nonce');

    // Output the checkbox
?>
    <label for="robodesk_featured">
        <input type="checkbox" id="robodesk_featured" name="robodesk_featured" value="true"
            <?php checked($featured, 'true'); ?> />
        <?php _e('Mark as featured', 'robodesk'); ?>
    </label>
<?php
}

// Save the Featured metabox data
add_action('save_post', 'robodesk_save_featured_metabox');
function robodesk_save_featured_metabox($post_id)
{
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
function robodesk_filter_faqs_by_featured($args, $request)
{

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


register_rest_field('robodesk_faq', 'author_name', array(
    'get_callback' => function ($post_arr) {
        $post_id = $post_arr['id'];
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        return $author ? $author->display_name : false;
    },
));


add_filter('rest_prepare_robodesk_faq', function ($response, $post, $request) {
    $author_id = $post->post_author;

    $author_info = array(
        'display_name' => get_the_author_meta('display_name', $author_id),
        'gravatar_url' => get_avatar_url($author_id, array('size' => 96)),
        'total_posts' => count_user_posts($author_id, 'robodesk_faq')

    );

    $response->data['author_info'] = $author_info;

    $response->data['featured'] =  get_post_meta($post->ID, '_robodesk_featured', true) ?? false;

    return $response;
}, 10, 3);
