<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Register FAQ Post Type and Taxonomy
add_action('init', 'mmhd_register_faq_post_type');
function mmhd_register_faq_post_type()
{
    $faq_labels = array(
        'name'                  => _x('FAQs', 'post type general name', 'metamint-helpdesk'),
        'singular_name'         => _x('FAQ', 'post type singular name', 'metamint-helpdesk'),
        'menu_name'             => __('FAQs', 'metamint-helpdesk'),
        'name_admin_bar'        => __('FAQ', 'metamint-helpdesk'),
        'add_new'               => __('Add New', 'metamint-helpdesk'),
        'add_new_item'          => __('Add New FAQ', 'metamint-helpdesk'),
        'new_item'              => __('New FAQ', 'metamint-helpdesk'),
        'edit_item'             => __('Edit FAQ', 'metamint-helpdesk'),
        'view_item'             => __('View FAQ', 'metamint-helpdesk'),
        'all_items'             => __('All FAQs', 'metamint-helpdesk'),
        'search_items'          => __('Search FAQs', 'metamint-helpdesk'),
        'parent_item_colon'     => __('Parent FAQs:', 'metamint-helpdesk'),
        'not_found'             => __('No FAQs found.', 'metamint-helpdesk'),
        'not_found_in_trash'    => __('No FAQs found in Trash.', 'metamint-helpdesk'),
        'featured_image'        => __('FAQ Featured Image', 'metamint-helpdesk'),
        'set_featured_image'    => __('Set featured image', 'metamint-helpdesk'),
        'remove_featured_image' => __('Remove featured image', 'metamint-helpdesk'),
        'use_featured_image'    => __('Use as featured image', 'metamint-helpdesk'),
        'archives'              => __('FAQ Archives', 'metamint-helpdesk'),
        'insert_into_item'      => __('Insert into FAQ', 'metamint-helpdesk'),
        'uploaded_to_this_item' => __('Uploaded to this FAQ', 'metamint-helpdesk'),
        'filter_items_list'     => __('Filter FAQs list', 'metamint-helpdesk'),
        'items_list_navigation' => __('FAQs list navigation', 'metamint-helpdesk'),
        'items_list'            => __('FAQs list', 'metamint-helpdesk'),
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

    register_post_type('mmhd_faq', $faq_args);



    // FAQ Category Taxonomy
    $faq_category_labels = array(
        'name'                       => _x('FAQ Categories', 'taxonomy general name', 'metamint-helpdesk'),
        'singular_name'              => _x('FAQ Category', 'taxonomy singular name', 'metamint-helpdesk'),
        'search_items'               => __('Search FAQ Categories', 'metamint-helpdesk'),
        'all_items'                  => __('All FAQ Categories', 'metamint-helpdesk'),
        'parent_item'                => __('Parent FAQ Category', 'metamint-helpdesk'),
        'parent_item_colon'          => __('Parent FAQ Category:', 'metamint-helpdesk'),
        'edit_item'                  => __('Edit FAQ Category', 'metamint-helpdesk'),
        'update_item'                => __('Update FAQ Category', 'metamint-helpdesk'),
        'add_new_item'               => __('Add New FAQ Category', 'metamint-helpdesk'),
        'new_item_name'              => __('New FAQ Category Name', 'metamint-helpdesk'),
        'separate_items_with_commas' => __('Separate FAQ Categories with commas', 'metamint-helpdesk'),
        'add_or_remove_items'        => __('Add or remove FAQ Categories', 'metamint-helpdesk'),
        'choose_from_most_used'      => __('Choose from the most used FAQ Categories', 'metamint-helpdesk'),
        'not_found'                  => __('No FAQ Categories found.', 'metamint-helpdesk'),
        'menu_name'                  => __('FAQ Categories', 'metamint-helpdesk'),
    );

    $faq_category_args = array(
        'hierarchical'          => true,
        'labels'                => $faq_category_labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'faq-category'),
    );

    register_taxonomy('mmhd_faq_category', 'mmhd_faq', $faq_category_args);
}


register_post_meta('mmhd_faq', '_mmhd_featured', array(
    'type' => 'string',
    'single' => true,
    'show_in_rest' => array(
        'schema' => array(
            'type' => 'boolean',
        ),
    ),
));
// Add Featured metabox to FAQ post type
add_action('add_meta_boxes', 'mmhd_add_featured_metabox');
function mmhd_add_featured_metabox()
{
    add_meta_box(
        'mmhd_featured_metabox',
        __('Featured?', 'metamint-helpdesk'),
        'mmhd_render_featured_metabox',
        'mmhd_faq',
        'side',
        'default'
    );
}

// Render the Featured metabox
function mmhd_render_featured_metabox($post)
{
    // Get the current value
    $featured = get_post_meta($post->ID, '_mmhd_featured', true);

    // Output the nonce field
    wp_nonce_field('mmhd_featured_metabox', 'mmhd_featured_metabox_nonce');

    // Output the checkbox
?>
    <label for="mmhd_featured">
        <input type="checkbox" id="mmhd_featured" name="mmhd_featured" value="true"
            <?php checked($featured, 'true'); ?> />
        <?php esc_html_e('Mark as featured', 'metamint-helpdesk'); ?>
    </label>
<?php
}

// Save the Featured metabox data
add_action('save_post', 'mmhd_save_featured_metabox');
function mmhd_save_featured_metabox($post_id)
{
    // Check if our nonce is set
    if (!isset($_POST['mmhd_featured_metabox_nonce'])) {
        return;
    }

    // Verify the nonce
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mmhd_featured_metabox_nonce'])), 'mmhd_featured_metabox')) {
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
    if (get_post_type($post_id) !== 'mmhd_faq') {
        return;
    }

    // Update the meta field
    $featured = (isset($_POST['mmhd_featured']) && $_POST['mmhd_featured'] === 'true') ? 'true' : 'false';
    update_post_meta($post_id, '_mmhd_featured', $featured);
}



add_filter('rest_mmhd_faq_query', 'mmhd_filter_faqs_by_featured', 10, 2);
function mmhd_filter_faqs_by_featured($args, $request)
{

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    if (isset($request['featured']) && $request['featured'] === 'true') {
        $args['meta_query'] = array(
            array(
                'key' => '_mmhd_featured',
                'value' => 'true',
                'compare' => '='
            )
        );
    }
    return $args;
}


register_rest_field('mmhd_faq', 'author_name', array(
    'get_callback' => function ($post_arr) {
        $post_id = $post_arr['id'];
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        return $author ? $author->display_name : false;
    },
));


add_filter('rest_prepare_mmhd_faq', function ($response, $post, $request) {
    $author_id = $post->post_author;

    $author_info = array(
        'display_name' => get_the_author_meta('display_name', $author_id),
        'gravatar_url' => get_avatar_url($author_id, array('size' => 96)),
        'total_posts' => count_user_posts($author_id, 'mmhd_faq')

    );

    $response->data['author_info'] = $author_info;

    $response->data['featured'] =  get_post_meta($post->ID, '_mmhd_featured', true) ?? false;

    return $response;
}, 10, 3);
