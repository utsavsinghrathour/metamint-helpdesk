<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register Ticket Post Type and Taxonomies
add_action('init', 'mmhd_register_ticket_post_type');
function mmhd_register_ticket_post_type()
{
    $ticket_labels = array(
        'name'                  => _x('Metamint Helpdesk Tickets', 'post type general name', 'mmhd'),
        'singular_name'         => _x('Metamint Helpdesk Ticket', 'post type singular name', 'mmhd'),
        'menu_name'             => __('Tickets', 'mmhd'),
        'name_admin_bar'        => __('Ticket', 'mmhd'),
        'add_new'               => __('Add New', 'mmhd'),
        'add_new_item'          => __('Add New Ticket', 'mmhd'),
        'new_item'              => __('New Ticket', 'mmhd'),
        'edit_item'             => __('Edit Ticket', 'mmhd'),
        'view_item'             => __('View Ticket', 'mmhd'),
        'all_items'             => __('All Tickets', 'mmhd'),
        'search_items'          => __('Search Tickets', 'mmhd'),
        'parent_item_colon'     => __('Parent Tickets:', 'mmhd'),
        'not_found'             => __('No tickets found.', 'mmhd'),
        'not_found_in_trash'    => __('No tickets found in Trash.', 'mmhd'),
        'featured_image'        => __('Ticket Featured Image', 'mmhd'),
        'set_featured_image'    => __('Set featured image', 'mmhd'),
        'remove_featured_image' => __('Remove featured image', 'mmhd'),
        'use_featured_image'    => __('Use as featured image', 'mmhd'),
        'archives'              => __('Ticket Archives', 'mmhd'),
        'insert_into_item'      => __('Insert into Ticket', 'mmhd'),
        'uploaded_to_this_item' => __('Uploaded to this Ticket', 'mmhd'),
        'filter_items_list'     => __('Filter Tickets list', 'mmhd'),
        'items_list_navigation' => __('Tickets list navigation', 'mmhd'),
        'items_list'            => __('Tickets list', 'mmhd'),
    );


    $ticket_args = array(
        'labels'             => $ticket_labels,
        'public'             => true,
        'has_archive'        => true,
        'publicly_queryable' => is_user_logged_in(), // Only logged in users can view tickets
        'show_ui'            => true,
        'show_in_menu'       => false,
        'show_in_rest'       => true,
        // 'rest_base' => 'mmhd_tickets',
        'query_var'          => true,
        'rewrite'            => array('slug' => 'mmhd-tickets'),
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'comments', 'custom-fields'),
        'taxonomies'         => array('mmhd_ticket_priority'),
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

    register_post_type('mmhd_ticket', $ticket_args);

    // Ticket Origin Taxonomy
    $ticket_origin_labels = array(
        'name'              => _x('Ticket Origins', 'taxonomy general name', 'mmhd'),
        'singular_name'     => _x('Ticket Origin', 'taxonomy singular name', 'mmhd'),
        'search_items'      => __('Search Ticket Origins', 'mmhd'),
        'all_items'         => __('All Ticket Origins', 'mmhd'),
        'parent_item'       => __('Parent Ticket Origin', 'mmhd'),
        'parent_item_colon' => __('Parent Ticket Origin:', 'mmhd'),
        'edit_item'         => __('Edit Ticket Origin', 'mmhd'),
        'update_item'       => __('Update Ticket Origin', 'mmhd'),
        'add_new_item'      => __('Add New Ticket Origin', 'mmhd'),
        'new_item_name'     => __('New Ticket Origin Name', 'mmhd'),
        'menu_name'         => __('Ticket Origins', 'mmhd'),
    );

    $ticket_origin_args = array(
        'hierarchical'      => true,
        'labels'            => $ticket_origin_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'ticket-origin'),
    );

    register_taxonomy('mmhd_ticket_origin', 'mmhd_ticket', $ticket_origin_args);

    // Ticket Priority Taxonomy
    $ticket_priority_labels = array(
        'name'              => _x('Ticket Priorities', 'taxonomy general name', 'mmhd'),
        'singular_name'     => _x('Ticket Priority', 'taxonomy singular name', 'mmhd'),
        'search_items'      => __('Search Ticket Priorities', 'mmhd'),
        'all_items'         => __('All Ticket Priorities', 'mmhd'),
        'parent_item'       => __('Parent Ticket Priority', 'mmhd'),
        'parent_item_colon' => __('Parent Ticket Priority:', 'mmhd'),
        'edit_item'         => __('Edit Ticket Priority', 'mmhd'),
        'update_item'       => __('Update Ticket Priority', 'mmhd'),
        'add_new_item'      => __('Add New Ticket Priority', 'mmhd'),
        'new_item_name'     => __('New Ticket Priority Name', 'mmhd'),
        'menu_name'         => __('Ticket Priorities', 'mmhd'),
    );

    $ticket_priority_args = array(
        'hierarchical'      => true,
        'labels'            => $ticket_priority_labels,
        'publicly_queryable' => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'sort'              => true,
        'rewrite'           => array('slug' => 'ticket-priority'),
    );

    register_taxonomy('mmhd_ticket_priority', 'mmhd_ticket', $ticket_priority_args);

    // Ticket Category Taxonomy
    $ticket_category_labels = [
        'name'              => _x('Categories', 'taxonomy general name', 'mmhd'),
        'singular_name'     => _x('Category', 'taxonomy singular name', 'mmhd'),
        'search_items'      => __('Search Categories', 'mmhd'),
        'all_items'         => __('All Categories', 'mmhd'),
        'parent_item'       => __('Parent Category', 'mmhd'),
        'parent_item_colon' => __('Parent Category:', 'mmhd'),
        'edit_item'         => __('Edit Category', 'mmhd'),
        'update_item'       => __('Update Category', 'mmhd'),
        'add_new_item'      => __('Add New Category', 'mmhd'),
        'new_item_name'     => __('New Category Name', 'mmhd'),
        'menu_name'         => __('Category', 'mmhd'),
    ];


    $ticket_category_args = array(
        'hierarchical'      => true,
        'labels'            => $ticket_category_labels,
        'publicly_queryable' => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'sort'              => true,
        'rewrite'           => array('slug' => 'ticket-category'),
    );

    register_taxonomy('mmhd_ticket_category', array('mmhd_ticket'), $ticket_category_args);
}

function mmhd_get_ticket_origin_label($ticket_id)
{
    $ticket_origin = get_post_meta($ticket_id, 'mmhd_ticket_origin', true);

    if (empty($ticket_origin)) {
        $origin_terms = wp_get_post_terms($ticket_id, 'mmhd_ticket_origin');
        if (!is_wp_error($origin_terms) && !empty($origin_terms)) {
            $ticket_origin = $origin_terms[0]->name;
        }
    }

    return $ticket_origin ?: 'Web';
}

// Meta Box for Ticket Status (Unchanged)
add_action('add_meta_boxes', 'mmhd_add_status_meta_box');
function mmhd_add_status_meta_box($post)
{
    add_meta_box('mmhd_ticket_status', 'Ticket Status', 'mmhd_status_meta_box_callback', 'mmhd_ticket', 'side', 'high');
}

function mmhd_status_meta_box_callback($post)
{
    wp_nonce_field('mmhd_ticket_status_nonce', 'mmhd_ticket_status_nonce');
    $value = get_post_meta($post->ID, '_mmhd_ticket_status', true);
?>
    <select name="mmhd_ticket_status" id="mmhd_ticket_status">
        <option value="open" <?php selected($value, 'open'); ?>>Open</option>
        <option value="in-progress" <?php selected($value, 'in-progress'); ?>>In Progress</option>
        <option value="closed" <?php selected($value, 'closed'); ?>>Closed</option>
        <option value="archived" <?php selected($value, 'archived'); ?>>Archived</option>
    </select>
    <?php
}

add_action('save_post', 'mmhd_save_status_meta_box_data');
function mmhd_save_status_meta_box_data($post_id)
{
    if (! isset($_POST['mmhd_ticket_status_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mmhd_ticket_status_nonce'])), 'mmhd_ticket_status_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'mmhd_ticket' == $_POST['post_type']) {
        if (! current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    }
}


//Adding Ticket Status on  admin_column
add_filter('manage_mmhd_ticket_posts_columns', 'mmhd_add_ticket_status_column');

function mmhd_add_ticket_status_column($columns)
{
    $columns['_mmhd_ticket_status'] = 'Ticket Status';
    return $columns;
}

add_action('manage_mmhd_ticket_posts_custom_column', 'mmhd_fill_ticket_status_column', 10, 2);

function mmhd_fill_ticket_status_column($column, $post_id)
{
    if ($column == '_mmhd_ticket_status') {
        $status = get_post_meta($post_id, '_mmhd_ticket_status', true);
        echo esc_html($status);
    }
}

add_filter('manage_edit-mmhd_ticket_sortable_columns', 'mmhd_make_ticket_status_column_sortable');

function mmhd_make_ticket_status_column_sortable($columns)
{
    $columns['_mmhd_ticket_status'] = '_mmhd_ticket_status';
    return $columns;
}

add_action('pre_get_posts', 'mmhd_sort_ticket_status_column');

function mmhd_sort_ticket_status_column($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') == '_mmhd_ticket_status') {
        $query->set('meta_key', '_mmhd_ticket_status');
        $query->set('orderby', 'meta_value');
    }
}

// Add this code to your theme's functions.php file or a custom plugin

// Step 1: Register the term meta for 'mmhd_ticket_priority'
function mmhd_register_ticket_priority_color_meta()
{
    register_term_meta('mmhd_ticket_priority', 'priority_color', array(
        'type' => 'string',
        'description' => 'Color for the ticket priority',
        'single' => true,
        'show_in_rest' => true, // Optional: Expose in REST API
    ));
}
add_action('init', 'mmhd_register_ticket_priority_color_meta');

// Step 2a: Add color picker field to "Add New Term" form
function mmhd_add_color_picker_to_new_term($taxonomy)
{
    if ($taxonomy === 'mmhd_ticket_priority') {
    ?>
        <div class="form-field term-colorpicker-wrap">
            <label for="priority_color">Priority Color</label>
            <input type="text" name="priority_color" id="priority_color" value="#ffffff" class="color-picker-field" />
            <p>Select a color for this priority.</p>
        </div>
    <?php
    }
}
add_action('mmhd_ticket_priority_add_form_fields', 'mmhd_add_color_picker_to_new_term', 10, 1);

// Step 2b: Add color picker field to "Edit Term" form
function mmhd_add_color_picker_to_edit_term($term)
{
    $color = get_term_meta($term->term_id, 'priority_color', true);
    $color = !empty($color) ? $color : '#ffffff'; // Default to white if no color is set

    ?>
    <tr class="form-field term-colorpicker-wrap">
        <th scope="row"><label for="priority_color">Priority Color</label></th>
        <td>
            <input type="text" name="priority_color" id="priority_color" value="<?php echo esc_attr($color); ?>"
                class="color-picker-field" />
            <p class="description">Select a color for this priority.</p>
        </td>
    </tr>
<?php
}
add_action('mmhd_ticket_priority_edit_form_fields', 'mmhd_add_color_picker_to_edit_term', 10, 1);

// Step 3: Enqueue WordPress color picker scripts and styles
function mmhd_enqueue_color_picker_scripts($taxonomy)
{
    // Only load on the edit tags screen for 'mmhd_ticket_priority'
    $screen = get_current_screen();
    if ($screen->id === 'edit-mmhd_ticket_priority') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(\'.color-picker-field\').wpColorPicker();
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'mmhd_enqueue_color_picker_scripts');

// Step 4: Save the color value when the term is created or updated
function mmhd_save_priority_color_meta($term_id)
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (isset($_POST['priority_color'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $color = sanitize_hex_color(wp_unslash($_POST['priority_color'])); // Sanitize the hex color
        if ($color) {
            update_term_meta($term_id, 'priority_color', $color);
        } else {
            delete_term_meta($term_id, 'priority_color');
        }
    }
}
add_action('created_mmhd_ticket_priority', 'mmhd_save_priority_color_meta');
add_action('edited_mmhd_ticket_priority', 'mmhd_save_priority_color_meta');


function mmhd_get_formatted_comments($post_id)
{
    $comments = get_comments([
        'post_id' => $post_id,
        'number' => 7,
        'order' => 'DESC',
        'orderby' => 'comment_date',
        'status' => 'approve',
        'comment_type' => 'ticket_replies',
    ]);

    $formatted_comments = array_map(function ($comment) {
        $author_id = (int) $comment->user_id;
        $is_bot_response = function_exists('mmhd_is_bot_comment') ? mmhd_is_bot_comment($comment->comment_ID) : false;
        $author_name = function_exists('mmhd_get_ticket_comment_author_name')
            ? mmhd_get_ticket_comment_author_name($comment)
            : $comment->comment_author;
        $author_avatar = function_exists('mmhd_get_ticket_comment_avatar_url')
            ? mmhd_get_ticket_comment_avatar_url($comment, 32)
            : (function_exists('mmhd_get_avatar_url')
                ? mmhd_get_avatar_url($author_id, 32)
                : get_avatar_url($comment->comment_author_email, ['size' => 32]));

        $rendered_content = wpautop($comment->comment_content);
        $plain_content = wp_strip_all_tags($rendered_content);

        $comment_time_local = strtotime($comment->comment_date_gmt . ' UTC');
        $now_local = time();

        $diff = $now_local - $comment_time_local;
        $time_ago = $diff < 5 ? 'just now' : human_time_diff($comment_time_local, $now_local) . ' ago';

        return [
            'id' => (int) $comment->comment_ID,
            'post' => (int) $comment->comment_post_ID,
            'author' => $author_id,
            'author_name' => $author_name,
            'author_image' => $author_avatar,
            'is_bot_response' => $is_bot_response,
            'date' => function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date,
            'date_gmt' => function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date_gmt,
            'date_utc' => function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date_gmt,
            'content' => [
                'rendered' => $rendered_content,
                'plain' => $plain_content,
            ],
            'time_ago' => $time_ago,
            'ticket_id' => (int) $comment->comment_post_ID,
            'author_data' => [
                'id' => $author_id,
                'name' => $author_name,
                'avatar' => $author_avatar,
            ]
        ];
    }, $comments);

    return $formatted_comments;
}


add_filter('rest_mmhd_ticket_query', function ($args, $request) {
    global $wpdb;


    if (! empty($request['search'])) {
        $search_term = sanitize_text_field($request['search']);

        add_filter('posts_where', function ($where) use ($search_term, $wpdb) {
            $like = '%' . $wpdb->esc_like($search_term) . '%';
            return $where . $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s ", $like);
        });
    }


    if (! empty($request['ticket_priority'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'mmhd_ticket_priority',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($request['ticket_priority']),
        ];
    }


    if (! empty($request['ticket_status'])) {
        $statuses = array_map('sanitize_text_field', explode(',', $request['ticket_status']));
        $args['meta_query'][] = [
            'key'     => '_mmhd_ticket_status',
            'value'   => $statuses,
            'compare' => 'IN'
        ];
    }

    return $args;
}, 10, 2);



add_filter(
    'rest_prepare_mmhd_ticket',
    'mmhd_customize_ticket_response',
    10,
    3
);

function mmhd_customize_ticket_response($response, $post, $request)
{
    // Grab the array we're going to work on
    $data = $response->get_data();

    // Generate ticket number
    $ticket_id = $post->ID;
    $ticket_number = 'TCKT-' . str_pad($ticket_id, 3, '0', STR_PAD_LEFT);


    $post_timestamp = strtotime($post->post_date_gmt . ' UTC');

    // Use the last reply timestamp if available
    $formatted_comments = mmhd_get_formatted_comments($post->ID);
    if (!empty($formatted_comments)) {
        $last_comment_utc = $formatted_comments[0]['date_utc'] ?? null;
        if ($last_comment_utc) {
            $last_reply_ts = strtotime($last_comment_utc);
            if ($last_reply_ts) {
                $post_timestamp = $last_reply_ts;
            }
        }
    }

    $current_timestamp = time();

    // Get the human-readable time difference
    $time_diff = human_time_diff($post_timestamp, $current_timestamp);

    // Get priority terms
    $priority_terms = get_the_terms($post->ID, 'mmhd_ticket_priority');
    $priority_name = ($priority_terms && ! is_wp_error($priority_terms))
        ? $priority_terms[0]->name
        : 'N/A';

    // Get author info
    $author_name = '';
    $author_image = '';
    if ($author = get_userdata($post->post_author)) {
        $author_name = $author->display_name;
        $author_image = function_exists('mmhd_get_avatar_url')
            ? mmhd_get_avatar_url((int) $author->ID, 96)
            : get_avatar_url($author->ID);
    }
    $last_reply =  get_post_meta($post->ID, 'mmhd_last_reply_id', true);

    // $formatted_comments already fetched above

    $total_comments = get_comments([
        'post_id'      => $post->ID,
        'status'       => 'approve',
        'comment_type' => 'ticket_replies',
        'count'        => true,
    ]);

    $notifications = get_post_meta($post->ID, 'mmhd_all_seen', true);

    // Format the data 
    $data = [
        'id' => $ticket_id,
        'ticket_number' => $ticket_number,
        'ticket_title' => $post->post_title,
        'ticket_status' => get_post_meta($post->ID, '_mmhd_ticket_status', true) ?? 'open',
        'ticket_priority' => $priority_name,
        'ticket_origin' => get_post_meta($post->ID, 'mmhd_ticket_origin', true) ?: 'N/A',
        'ticket_author' => $author_name,
        'ticket_author_image' => $author_image,
        'ticket_time_ago' => $time_diff,
        'ticket_total_comments' => $total_comments,
        'ticket_notifications' => $notifications,
        'ticket_comments' => $formatted_comments,
        'ticket_last_reply' => !empty($last_reply) ? $last_reply : false,
    ];



    $response->set_data($data);
    return $response;
}

add_action('rest_api_init', function () {
    register_rest_route('chat/v1', '/tickets', array(
        'methods'  => 'POST',
        'callback' => 'mmhd_handle_tickets_webhook',
        'permission_callback' => '__return_true', // Webhook; auth via HMAC signature in callback
    ));
});



function mmhd_get_webhook_secret()
{
    if (defined('MMHD_WEBHOOK_SECRET') && MMHD_WEBHOOK_SECRET !== '') {
        return MMHD_WEBHOOK_SECRET;
    }

    $option = get_option('mmhd_webhook_secret', '');
    if (!empty($option)) {
        return $option;
    }

    return wp_hash('mmhd-webhook-secret');
}

function mmhd_handle_tickets_webhook(WP_REST_Request $request)
{
    $payload = $request->get_body();
    $data    = json_decode($payload, true);

    // Validate payload
    if (empty($payload) || json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error(
            'invalid_payload',
            'Invalid JSON payload',
            array('status' => 400)
        );
    }

    // HMAC verification using site-specific secret
    $signature = isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])) : '';
    $expected = hash_hmac('sha256', $payload, mmhd_get_webhook_secret());
    if (!hash_equals($expected, $signature)) {
        return new WP_Error(
            'unauthorized',
            'Invalid webhook signature',
            array('status' => 401)
        );
    }

    // Handle ticket creation event
    if (isset($data['event']) && 'ticket.created' === $data['event']) {

        $title    = sanitize_text_field($data['data']['title'] ?? '');
        $content  = sanitize_textarea_field($data['data']['description'] ?? '');
        $author   = sanitize_text_field($data['data']['uid'] ?? '');
        $timestamp = $data['data']['timestamp'] ?? null;

        $ticket_args = array(
            'post_type'   => 'mmhd_ticket',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => $author,
            'meta_input'   => array(
                '_mmhd_ticket_status' => 'open',
                'mmhd_ticket_origin' => 'chat',
            ),
            'tax_input' => array(
                'mmhd_ticket_priority' => array('medium'), // Default priority
            )
        );

        if (!empty($created_at)) {
            $timestamp = strtotime($created_at);
            if ($timestamp) {
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $ticket_args['post_date'] = date('Y-m-d H:i:s', $timestamp);
                $ticket_args['post_date_gmt'] = gmdate('Y-m-d H:i:s', $timestamp);
            }
        }

        $ticket = wp_insert_post($ticket_args);

        if (!is_wp_error($ticket)) {

            delete_user_meta($author, 'mmhd_openai_conversation_id');
            wp_set_object_terms($ticket, 'medium', 'mmhd_ticket_priority', false);

            $assigned = false;
            $settings = get_option('mmhd_advanced_settings', []);
            if (!empty($settings['enable_auto_assign'])) {
                $assigned = auto_assign_ticket_to_support_staff();
            }

            update_post_meta($ticket, 'ticket_assignee', $assigned);
            mmhd_store_ticket_assignee_id($ticket, $assigned);

            $user_object =  get_user_by('id', $author);
            $user_name = $user_object->display_name;
            $email_address = $user_object->user_email;    //

            if ($email_address) {
                update_post_meta($ticket, '_mmhd_customer_email', $email_address);
            }

            $comment_data = array(
                'comment_post_ID' => $ticket,
                'user_id'  => $author,
                'comment_content' => $title,
                'comment_author' => $user_name,
                'comment_author_email' => $email_address,
                'comment_approved' => 1,
                'comment_type' => 'ticket_replies'
            );

            if ($timestamp) {
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $mysql_date = date('Y-m-d H:i:s', strtotime($timestamp));
                $comment_data['comment_date'] = $mysql_date;
                $comment_data['comment_date_gmt'] = get_gmt_from_date($mysql_date);
            }

            $comment_id = wp_insert_comment($comment_data);

            if (!is_wp_error($comment_id)) {
                add_comment_meta($comment_id, 'comment_source', 'chat', true);
                add_comment_meta($comment_id, 'author_type', mmhd_get_author_type($author), true);
                add_comment_meta($comment_id, 'is_bot_response', '0', true);
                add_comment_meta($comment_id, 'mmhd_suppress_notification', 1, true);
            }

            $reply_id = !is_wp_error($comment_id) ? $comment_id : false;
            $ticket_number = 'TCKT-' . str_pad($ticket, 3, '0', STR_PAD_LEFT);
            $ticket_origin = get_post_meta($ticket, 'mmhd_ticket_origin', true) ?: 'N/A';
            $ticket_priority_terms = get_the_terms($ticket, 'mmhd_ticket_priority');
            $ticket_priority =  !is_wp_error($ticket_priority_terms) ? $ticket_priority_terms[0]->name : false;
            $last_activity = get_post_time('U', true); // Get post creation time in UTC timestamp
            $human_readable_time = human_time_diff($last_activity, current_time('U'));

            mmhd_notify_ticket_created($ticket, 'chat');

            do_action('mmhd_after_ticket_created', $ticket, $ticket_args, $author);

            return rest_ensure_response(array(
                'status'  => 'success',
                'message' => 'Ticket created successfully',
                'data'    =>
                array(
                    'id' => $ticket,
                    'ticket_number' => $ticket_number,
                    'ticket_title' => get_the_title($ticket),
                    'ticket_status' => get_post_meta($ticket, '_mmhd_ticket_status', true),
                    'ticket_priority' => $ticket_priority,
                    'ticket_origin' => $ticket_origin,
                    'ticket_author' => $user_name,
                    'ticket_author_email' => $email_address,
                    'ticket_author_image' => function_exists('mmhd_get_avatar_url') ? mmhd_get_avatar_url((int) $author, 96) : get_avatar_url($author),
                    'ticket_time_ago' => $human_readable_time,
                    'comment_id' => $reply_id,
                )
            ));
        }
    }

    //get all ticket
    if (isset($data['event']) && 'tickets.get' === $data['event']) {
        $user_id = sanitize_text_field($data['data']['uid'] ?? '');

        $ticket_query = new WP_Query([
            'post_type' => 'mmhd_ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $user_id,
        ]);

        $formatted_tickets = [];

        if ($ticket_query->have_posts()) {
            while ($ticket_query->have_posts()) {
                $ticket_query->the_post();
                $ticket_id = get_the_ID();
                $ticket_number = 'TCKT-' . str_pad($ticket_id, 3, '0', STR_PAD_LEFT);
                $ticket_origin_terms = get_the_terms($ticket_id, 'mmhd_ticket_origin');
                $ticket_origin =  $ticket_origin = get_post_meta($ticket_id, 'mmhd_ticket_origin', true) ?: 'N/A';
                $ticket_priority_terms = get_the_terms($ticket_id, 'mmhd_ticket_priority');
                $ticket_priority =  !is_wp_error($ticket_priority_terms) ? $ticket_priority_terms[0]->name : false;

                $last_activity = get_post_time('U', true); // Get post creation time in UTC timestamp
                $human_readable_time = human_time_diff($last_activity, current_time('U')); // Calculate human-readable time difference
                $formatted_tickets[] = [
                    'id' => $ticket_id,
                    'ticket_number' => $ticket_number,
                    'ticket_title' => get_the_title(),
                    'ticket_status' => get_post_meta($ticket_id, '_mmhd_ticket_status', true),
                    'ticket_priority' => $ticket_priority,
                    'ticket_origin' => $ticket_origin,
                    'ticket_author' => get_user_by('id', $user_id)->display_name,
                    'ticket_author_image' => function_exists('mmhd_get_avatar_url') ? mmhd_get_avatar_url((int) $user_id, 96) : get_avatar_url($user_id),
                    'ticket_time_ago' => $human_readable_time,

                ];
            }
            wp_reset_postdata(); // Reset the global post object
        }

        return rest_ensure_response([
            'status' => 'success',
            'message' => 'Tickets fetched successfully',
            'data' => $formatted_tickets
        ]);
    }

    // Handle ticket reply event ...save comments
    if (isset($data['event']) && 'replies.receive' === $data['event']) {
        $ticket_id  = sanitize_text_field($data['data']['ticket_id'] ?? '');
        $user_id   = sanitize_text_field($data['data']['user_id'] ?? '');
        $reply     = sanitize_textarea_field($data['data']['message'] ?? '');
        $base64_image = $data['data']['image'] ?? '';
        $is_bot_response = isset($data['data']['is_bot_response']) ? (bool) $data['data']['is_bot_response'] : false;
        $timestamp = $data['data']['timestamp'] ?? null;
        $user_object =  get_user_by('id', $user_id);
        $user_name = $user_object->display_name;
        $email_address = $user_object->user_email;

        if (empty($ticket_id)) {
            return new WP_Error(
                'missing_ticket_id',
                'Ticket ID is required',
                array('status' => 400)
            );
        }

        $ticket = get_post($ticket_id);
        if (!$ticket) {
            return new WP_Error(
                'ticket_not_found',
                'Ticket not found',
                array('status' => 404)
            );
        }

        $attachment_id = 0;
        $attachment_url = '';
        $content = $reply;
        if (!empty($base64_image)) {
            $attachment_id = mmhd_base64_image_upload($base64_image);
            $attachment_url = wp_get_attachment_url($attachment_id);
            $content = $reply . '<br/><a href="' . $attachment_url . '" target="_blank"><img alt="' . get_the_title($ticket_id) . '" class="mmhd-comment-img"  src="' . $attachment_url . '"></a>';
        }

        $created_at = isset($data['data']['created_at']) ? sanitize_text_field($data['data']['created_at']) : '';

        // Add reply to ticket
        $comment_data = array(
            'comment_post_ID' => $ticket_id,
            'comment_author' => $user_name,
            'comment_author_email' => $email_address,
            'user_id'  => $user_id,
            'comment_content' => $content,
            'comment_approved' => 1,
            'comment_type' => 'ticket_replies'
        );

        if ($timestamp) {
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $mysql_date = date('Y-m-d H:i:s', strtotime($timestamp));
            $comment_data['comment_date'] = $mysql_date;
            $comment_data['comment_date_gmt'] = get_gmt_from_date($mysql_date);
        }

        $comment_id = wp_insert_comment($comment_data);


        if ($comment_id) {
            add_comment_meta($comment_id, 'comment_source', 'chat', true);
            add_comment_meta($comment_id, 'author_type', mmhd_get_author_type($user_id), true);
            add_comment_meta($comment_id, 'is_bot_response', $is_bot_response ? '1' : '0', true);
            // Trigger action for integrations (Slack, Discord, etc.)
            do_action('mmhd_after_reply_added', $ticket_id, $comment_id, $reply, $user_object);

            // update_user_meta($user_id, 'mmhd_all_seen', 'yes');

            $comment = get_comment($comment_id);
            $data = [
                'id' => $comment->comment_ID,
                'author' => function_exists('mmhd_get_ticket_comment_author_name') ? mmhd_get_ticket_comment_author_name($comment) : $comment->comment_author, // Display name
                'author_id' => $comment->user_id,    // User ID
                'author_image' => function_exists('mmhd_get_ticket_comment_avatar_url') ? mmhd_get_ticket_comment_avatar_url($comment, 96) : (function_exists('mmhd_get_avatar_url') ? mmhd_get_avatar_url((int) $comment->user_id, 96) : get_avatar_url($comment->user_id, ['size' => 96])),
                'content' => $comment->comment_content,
                'created_at' => function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date,
                'is_bot_response' => $is_bot_response,

            ];
            mmhd_log(sprintf(
                "Comment received for ticket #%s: %s\n",
                $ticket_id,
                $reply
            ));
            update_post_meta($ticket_id, 'mmhd_all_seen', 'yes');
            return rest_ensure_response(array(
                'status'  => 'success',
                'message' => 'Reply added successfully',
                'data'    => $data
            ));
        }
    }

    // Get ticket Comments
    if (isset($data['event']) && 'comments.get' === $data['event']) {
        $loggedinuserid   = sanitize_text_field($data['data']['uid'] ?? '');
        $ticketID =  sanitize_text_field($data['data']['ticket_id'] ?? '');

        $comments = get_comments([
            'post_id' => $ticketID,
            'order' => 'ASC',
        ]);

        if (empty($comments)) {
            return new WP_Error(
                400,
                'Comments Not found',
            );
        }
        $formatted_comments = [];
        foreach ($comments as $comment) {
            $author_image = function_exists('mmhd_get_ticket_comment_avatar_url') ? mmhd_get_ticket_comment_avatar_url($comment, 96) : (function_exists('mmhd_get_avatar_url') ? mmhd_get_avatar_url((int) $comment->user_id, 96) : get_avatar_url($comment->user_id, ['size' => 96]));
            $formatted_comments[] = [
                'id' => $comment->comment_ID,
                'author' => function_exists('mmhd_get_ticket_comment_author_name') ? mmhd_get_ticket_comment_author_name($comment) : $comment->comment_author,
                'author_id' => $comment->user_id,
                'author_image' => $author_image,
                'content' => $comment->comment_content,
                'created_at' => function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : $comment->comment_date,
                'is_bot_response' => function_exists('mmhd_is_bot_comment') ? mmhd_is_bot_comment($comment->comment_ID) : false,
            ];
        }
        return rest_ensure_response(array(
            'status'  => 'success',
            'message' => 'Comments found',
            'data' => $formatted_comments,
        ));
    }

    // Handle unknown events
    return new WP_Error(
        'invalid_event',
        'Invalid event type',
        array('status' => 400)
    );
}

add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
    if ($post_type === 'mmhd_ticket') {
        return false;
    }
    return $use_block_editor;
}, 10, 2);

function mmhd_user_data_to_session_storage()
{
    $user_data = [
        'isLoggedIn'     => is_user_logged_in(),
        'loggedInUserId' => is_user_logged_in() ? get_current_user_id() : '',
    ];

    wp_add_inline_script('jquery', '
        if (typeof(Storage) !== "undefined") {
            var userData = ' . wp_json_encode($user_data) . ';
            sessionStorage.setItem(\'wpUserData\', JSON.stringify(userData));
        }
    ');
}
add_action('wp_enqueue_scripts', 'mmhd_user_data_to_session_storage');
add_action('admin_enqueue_scripts', 'mmhd_user_data_to_session_storage');


function mmhd_is_ticket_kses_context()
{
    $possible_post_ids = [];

    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (isset($_POST['ticket_id'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $possible_post_ids[] = absint($_POST['ticket_id']);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (isset($_POST['comment_post_ID'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $possible_post_ids[] = absint($_POST['comment_post_ID']);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_REQUEST['post_id'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $possible_post_ids[] = absint($_REQUEST['post_id']);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_REQUEST['ticket_id'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $possible_post_ids[] = absint($_REQUEST['ticket_id']);
    }

    $possible_post_ids = array_filter(array_unique($possible_post_ids));

    foreach ($possible_post_ids as $post_id) {
        if ($post_id > 0 && get_post_type($post_id) === 'mmhd_ticket') {
            return true;
        }
    }

    return false;
}

function mmhd_allow_image_in_comments($allowed_tags, $context)
{
    if (!mmhd_is_ticket_kses_context()) {
        return $allowed_tags;
    }

    $img_attributes = array(
        'src' => array(),
        'alt' => array(),
        'class' => array(),
        'width' => array(),
        'height' => array()
    );

    if (!isset($allowed_tags['img']) || !is_array($allowed_tags['img'])) {
        $allowed_tags['img'] = [];
    }

    $allowed_tags['img'] = array_merge($allowed_tags['img'], $img_attributes);

    // Allow <a> tag with common safe attributes
    $anchor_attributes = array(
        'href'   => array(),
        'title'  => array(),
        'target' => array(),
        'rel'    => array(),
        'class'  => array(),
        'style'  => array(),
    );

    if (!isset($allowed_tags['a']) || !is_array($allowed_tags['a'])) {
        $allowed_tags['a'] = [];
    }

    $allowed_tags['a'] = array_merge($allowed_tags['a'], $anchor_attributes);

    return $allowed_tags;
}
add_filter('wp_kses_allowed_html', 'mmhd_allow_image_in_comments', 10, 2);


function mmhd_base64_image_upload($base64_data)
{
    // Extract the image data


    if (preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $base64_data, $matches)) {

        $image_type = $matches[1] === 'jpeg' ? 'jpg' : $matches[1]; // png, jpg, etc.
        $image_data = substr($base64_data, strpos($base64_data, ',') + 1);
        $image_data = base64_decode($image_data);

        if (!$image_data || @getimagesizefromstring($image_data) === false) {
            return new WP_Error('invalid_image', 'Invalid image data');
        }

        $upload_dir = wp_upload_dir();
        $filename = 'ticket-' . time() . '.' . $image_type;
        $file_path = $upload_dir['path'] . '/' . $filename;

        if (file_put_contents($file_path, $image_data) === false) {
            return new WP_Error('file_error', 'Could not save image file');
        }

        // Prepare file data for WordPress
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $file_path);

        // Generate metadata and thumbnails
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    return false;
}
