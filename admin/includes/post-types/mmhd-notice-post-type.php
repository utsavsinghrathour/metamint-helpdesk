<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register the custom post type 'notice'
function mmhd_register_notice_post_type()
{
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
        'supports'           => array('title', 'editor', 'excerpt', 'author', 'thumbnail'),

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

add_action('init', 'mmhd_register_notice_post_type');

// Add meta boxes for 'Active?' and 'Feature?' checkboxes
function mmhd_add_notice_meta_boxes()
{
    add_meta_box(
        'notice_active',
        'Active?',
        'mmhd_notice_active_callback',
        'notice',
        'side',
        'default'
    );
    add_meta_box(
        'notice_feature',
        'Feature?',
        'mmhd_notice_feature_callback',
        'notice',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mmhd_add_notice_meta_boxes');

// Callback function for 'Active?' meta box
function mmhd_notice_active_callback($post)
{
    wp_nonce_field('save_notice_active', 'notice_active_nonce');
    $value = get_post_meta($post->ID, '_mmhd_notice_active', true);
    echo '<label><input type="checkbox" name="_mmhd_notice_active" value="true" ' . checked($value, 'true', false) . ' /> Active?</label>';
}

// Callback function for 'Feature?' meta box
function mmhd_notice_feature_callback($post)
{
    wp_nonce_field('save_notice_feature', 'notice_feature_nonce');
    $value = get_post_meta($post->ID, '_mmhd_notice_feature', true);
    echo '<label><input type="checkbox" name="_mmhd_notice_feature" value="true" ' . checked($value, 'true', false) . ' /> Feature?</label>';
}

// Add meta box for 'Action' (Open notice / Start a conversation)
function mmhd_add_notice_action_meta_box()
{
    add_meta_box(
        'notice_action_box',
        'Action',
        'mmhd_notice_action_callback',
        'notice',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mmhd_add_notice_action_meta_box');

// Callback function for 'Action' meta box
function mmhd_notice_action_callback($post)
{
    wp_nonce_field('save_notice_action', 'notice_action_nonce');
    $action = get_post_meta($post->ID, '_mmhd_notice_action', true);
    if (empty($action)) {
        $action = 'open';
    }
    $initial_message = get_post_meta($post->ID, '_mmhd_notice_initial_message', true);
    ?>
    <p class="description" style="margin-top:0;">What happens when a visitor clicks this notice?</p>
    <label style="display:block;margin-bottom:6px;">
        <input type="radio" name="_mmhd_notice_action" value="open" <?php checked($action, 'open'); ?> />
        Open the notice
    </label>
    <label style="display:block;margin-bottom:6px;">
        <input type="radio" name="_mmhd_notice_action" value="start_conversation" <?php checked($action, 'start_conversation'); ?> />
        Start a conversation
    </label>
    <div id="mmhd-notice-initial-message-wrap" style="margin-top:10px;<?php echo $action === 'start_conversation' ? '' : 'display:none;'; ?>">
        <label for="_mmhd_notice_initial_message" style="display:block;margin-bottom:4px;">
            <strong>Initial message</strong>
        </label>
        <textarea name="_mmhd_notice_initial_message" id="_mmhd_notice_initial_message" rows="3" style="width:100%;"><?php echo esc_textarea($initial_message); ?></textarea>
        <p class="description">Sent to the AI bot (or to a human conversation when the AI bot is off) when the visitor clicks this notice.</p>
    </div>
    <script>
        (function () {
            var radios = document.querySelectorAll('input[name="_mmhd_notice_action"]');
            var wrap = document.getElementById('mmhd-notice-initial-message-wrap');
            if (!radios.length || !wrap) return;
            var toggle = function () {
                var checked = document.querySelector('input[name="_mmhd_notice_action"]:checked');
                wrap.style.display = checked && checked.value === 'start_conversation' ? '' : 'none';
            };
            radios.forEach(function (r) { r.addEventListener('change', toggle); });
        })();
    </script>
    <?php
}

// Save the meta data when the post is saved
function mmhd_save_notice_meta($post_id)
{
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
    if (isset($_POST['notice_active_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['notice_active_nonce'])), 'save_notice_active')) {
        if (isset($_POST['_mmhd_notice_active']) && sanitize_text_field(wp_unslash($_POST['_mmhd_notice_active'])) === 'true') {
            update_post_meta($post_id, '_mmhd_notice_active', 'true');
        } else {
            update_post_meta($post_id, '_mmhd_notice_active', 'false');
        }
    }

    // Save 'Feature?' checkbox
    if (isset($_POST['notice_feature_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['notice_feature_nonce'])), 'save_notice_feature')) {
        if (isset($_POST['_mmhd_notice_feature']) && sanitize_text_field(wp_unslash($_POST['_mmhd_notice_feature'])) === 'true') {
            update_post_meta($post_id, '_mmhd_notice_feature', 'true');
        } else {
            update_post_meta($post_id, '_mmhd_notice_feature', 'false');
        }
    }

    // Save 'Action' radio
    if (isset($_POST['notice_action_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['notice_action_nonce'])), 'save_notice_action')) {
        $action = isset($_POST['_mmhd_notice_action']) ? sanitize_text_field(wp_unslash($_POST['_mmhd_notice_action'])) : 'open';
        if (!in_array($action, array('open', 'start_conversation'), true)) {
            $action = 'open';
        }
        update_post_meta($post_id, '_mmhd_notice_action', $action);

        $initial_message = isset($_POST['_mmhd_notice_initial_message']) ? sanitize_textarea_field(wp_unslash($_POST['_mmhd_notice_initial_message'])) : '';
        update_post_meta($post_id, '_mmhd_notice_initial_message', $initial_message);
    }
}
add_action('save_post', 'mmhd_save_notice_meta');


add_filter('rest_notice_query', 'mmhd_filter_notice_by_featured_and_active', 10, 2);
function mmhd_filter_notice_by_featured_and_active($args, $request)
{
    $meta_query = array();

    // Filter by 'featured' parameter
    if (isset($request['featured'])) {
        $featured_value = $request['featured'] === 'true' ? 'true' : 'false';
        $meta_query[] = array(
            'key'     => '_mmhd_notice_feature',
            'value'   => $featured_value,
            'compare' => '='
        );
    }

    // Filter by 'active' parameter
    if (isset($request['active'])) {
        $active_value = $request['active'] === 'true' ? 'true' : 'false';
        $meta_query[] = array(
            'key'     => '_mmhd_notice_active',
            'value'   => $active_value,
            'compare' => '='
        );
    }

    // Apply meta_query if conditions are set
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    return $args;
}

// Expose notice action + initial message through the REST API
register_rest_field('notice', 'notice_action', array(
    'get_callback' => function ($post_arr) {
        $action = get_post_meta($post_arr['id'], '_mmhd_notice_action', true);
        return empty($action) ? 'open' : $action;
    },
    'schema' => array(
        'type' => 'string',
    ),
));

register_rest_field('notice', 'initial_message', array(
    'get_callback' => function ($post_arr) {
        return (string) get_post_meta($post_arr['id'], '_mmhd_notice_initial_message', true);
    },
    'schema' => array(
        'type' => 'string',
    ),
));
