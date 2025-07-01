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
        'show_in_rest'       => true,
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
        'publicly_queryable' => true,
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
        <option value="archived" <?php selected($value, 'archived'); ?>>Archived</option>
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

//Adding Ticket Status on  admin_column
add_filter('manage_robodesk_ticket_posts_columns', 'add_robodesk_ticket_status_column');

function add_robodesk_ticket_status_column($columns) {
    $columns['_robodesk_ticket_status'] = 'Ticket Status';
    return $columns;
}

add_action('manage_robodesk_ticket_posts_custom_column', 'fill_robodesk_ticket_status_column', 10, 2);

function fill_robodesk_ticket_status_column($column, $post_id) {
    if ($column == '_robodesk_ticket_status') {
        $status = get_post_meta($post_id, '_robodesk_ticket_status', true);
        echo esc_html($status);
    }
}

add_filter('manage_edit-robodesk_ticket_sortable_columns', 'make_robodesk_ticket_status_column_sortable');

function make_robodesk_ticket_status_column_sortable($columns) {
    $columns['_robodesk_ticket_status'] = '_robodesk_ticket_status';
    return $columns;
}

add_action('pre_get_posts', 'sort_robodesk_ticket_status_column');

function sort_robodesk_ticket_status_column($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') == '_robodesk_ticket_status') {
        $query->set('meta_key', '_robodesk_ticket_status');
        $query->set('orderby', 'meta_value');
    }
}

// Add this code to your theme's functions.php file or a custom plugin

// Step 1: Register the term meta for 'robodesk_ticket_priority'
function register_ticket_priority_color_meta() {
    register_term_meta('robodesk_ticket_priority', 'priority_color', array(
        'type' => 'string',
        'description' => 'Color for the ticket priority',
        'single' => true,
        'show_in_rest' => true, // Optional: Expose in REST API
    ));
}
add_action('init', 'register_ticket_priority_color_meta');

// Step 2a: Add color picker field to "Add New Term" form
function add_color_picker_to_new_term($taxonomy) {
    if ($taxonomy === 'robodesk_ticket_priority') {
        ?>
        <div class="form-field term-colorpicker-wrap">
            <label for="priority_color">Priority Color</label>
            <input type="text" name="priority_color" id="priority_color" value="#ffffff" class="color-picker-field" />
            <p>Select a color for this priority.</p>
        </div>
        <?php
    }
}
add_action('robodesk_ticket_priority_add_form_fields', 'add_color_picker_to_new_term', 10, 1);

// Step 2b: Add color picker field to "Edit Term" form
function add_color_picker_to_edit_term($term) {
    $color = get_term_meta($term->term_id, 'priority_color', true);
    $color = !empty($color) ? $color : '#ffffff'; // Default to white if no color is set
    
    // var_dump($color);
    ?>
    <tr class="form-field term-colorpicker-wrap">
        <th scope="row"><label for="priority_color">Priority Color</label></th>
        <td>
            <input type="text" name="priority_color" id="priority_color" value="<?php echo esc_attr($color); ?>" class="color-picker-field" />
            <p class="description">Select a color for this priority.</p>
        </td>
    </tr>
    <?php
}
add_action('robodesk_ticket_priority_edit_form_fields', 'add_color_picker_to_edit_term', 10, 1);

// Step 3: Enqueue WordPress color picker scripts and styles
function enqueue_color_picker_scripts($taxonomy) {
    // Only load on the edit tags screen for 'robodesk_ticket_priority'
    $screen = get_current_screen();
    if ($screen->id === 'edit-robodesk_ticket_priority') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    
        add_action('admin_footer', 'initialize_color_picker');
    }
}
add_action('admin_enqueue_scripts', 'enqueue_color_picker_scripts');

function initialize_color_picker() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.color-picker-field').wpColorPicker();
        });
    </script>
    <?php
}

// Step 4: Save the color value when the term is created or updated
function save_priority_color_meta($term_id) {
    if (isset($_POST['priority_color'])) {
        $color = sanitize_hex_color($_POST['priority_color']); // Sanitize the hex color
        if ($color) {
            update_term_meta($term_id, 'priority_color', $color);
        } else {
            delete_term_meta($term_id, 'priority_color');
        }
    }
}
add_action('created_robodesk_ticket_priority', 'save_priority_color_meta');
add_action('edited_robodesk_ticket_priority', 'save_priority_color_meta');

// rest api functions to get tickets 
// add_filter('rest_robodesk_ticket_query', 'robodesk_ticket_query', 10, 2);
// function robodesk_ticket_query($args, $request) {

//     //ticket_id==> id 
//     //ticket title ==> title
//     //ticket_update_date ===> ticket last updated date
//     //ticket_priority ==> taxonomy=robodesk_ticket_priority
//     //ticket_status ==>
//     //ticket_origin ==> taxonomy=robodesk_ticket_origin
//     //author ==> author display_name

// }


add_filter('rest_prepare_robodesk_ticket', 'customize_robodesk_ticket_response', 10, 3);

function customize_robodesk_ticket_response($response, $post, $request) {

    $response->data['robodesk_ticket_title'] = $response->data['title']['rendered'];

    $response->data['robodesk_ticket_updated_date'] = $response->data['modified'];

    $priority_terms = get_the_terms($post->ID, 'robodesk_ticket_priority');
    if ($priority_terms && !is_wp_error($priority_terms)) {
        $response->data['robodesk_ticket_priority'] = wp_list_pluck($priority_terms, 'name');
    } else {
        $response->data['robodesk_ticket_priority'] = [];
    }

    $ticket_status = get_post_meta($post->ID, '_robodesk_ticket_status', true);
    $response->data['robodesk_ticket_status'] = $ticket_status ? $ticket_status : '';
    $origin_terms = get_the_terms($post->ID, 'robodesk_ticket_origin');
    if ($origin_terms && !is_wp_error($origin_terms)) {
        $response->data['robodesk_ticket_origin'] = wp_list_pluck($origin_terms, 'name');
    } else {
        $response->data['robodesk_ticket_origin'] = [];
    }
    $author = get_user_by('id', $post->post_author);
    $response->data['robodesk_ticket_author'] = $author ? $author->display_name : '';
    $response->data['robodesk_ticket_author_image'] = $author ? get_avatar_url($author->ID) : '';

    return $response;
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'chat/v1', '/tickets', array(
        'methods'  => 'POST',
        'callback' => 'robodesk_handle_tickets_webhook',
        'permission_callback' => '__return_true',
    ) );
} );



function robodesk_handle_tickets_webhook(WP_REST_Request $request) {
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

    // Handle ticket creation event
    if (isset($data['event']) && 'ticket.created' === $data['event']) {
        
        $title    = sanitize_text_field($data['data']['title'] ?? '');
        $content  = sanitize_textarea_field($data['data']['description'] ?? '');
        $author   = sanitize_text_field($data['data']['uid'] ?? '');

        $ticket = wp_insert_post(array(
            'post_type'   => 'robodesk_ticket',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => $author,
            'meta_input'   => array(
			    '_robodesk_ticket_status' => 'open'
			),
        ));
        
        if (!is_wp_error($ticket)) {
        
            rbd_log(sprintf(
                "Ticket #%s created with title: %s\n",
                $ticket,
                $title
            ));

            // Set taxonomy for the ticket origin
            $term_origin = term_exists('chat', 'robodesk_ticket_origin');

            if ($term_origin) {
                
                $term_id = $term_origin['term_id'];
            } else {
               
                $result = wp_insert_term(
                    'Chat',
                    'robodesk_ticket_origin',
                    array(
                        'description' => 'Chat origin tickets',
                        'slug'        => 'chat'
                    )
                );
                
              
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                } else {
                  
                    error_log('Failed to create term: ' . $result->get_error_message());
                    $term_id = null;
                }
            }
            
            // If we have a valid term ID (either from existing term or newly created one), assign it to the post
            if ($term_id) {
                wp_set_post_terms($ticket, array($term_id), 'robodesk_ticket_origin');
            }
            
            $comment_id = wp_insert_comment(array(
                'comment_post_ID' => $ticket,
                'comment_author'  => $author,
                'comment_content' => $title,
                'comment_approved' => 1,
                'comment_type' => 'ticket_replies'
            ));
            
            $reply_id = !is_wp_error($comment_id) ? $comment_id : false;
            
            $ticket_number = 'TCKT-'.str_pad($ticket, 3, '0', STR_PAD_LEFT);

            return rest_ensure_response(array(
                'status'  => 'success',
                'message' => 'Ticket created successfully',
                'data'    => 
                    array(
                            'ticket_id' => $ticket , 
                            'comment_id' =>$reply_id , 
                            'ticket_number' => $ticket_number , 
                            'ticket_title' => $title,
                            'ticket_status' => 'open',
                            'ticket_priority' => 'Medium',
                            'ticket_origin' => 'Chat',
                            'ticket_author' => get_user_by('id', $author)->display_name,
                            'ticket_author_image' => get_avatar_url($author),
                            'ticket_time_ago' => 'Just now',
                            'ticket_messages' => [],
                    )        
            ));
        }
    }
    
    //get all tickets
    if(isset($data['event']) && 'tickets.get' === $data['event']){
        $user_id   = sanitize_text_field($data['data']['uid'] ?? '');
        
        
        
        $tickets   = get_posts([
            'post_type' => 'robodesk_ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $user_id,
        ]);
        
        $formatted_tickets = [];

        foreach ($tickets as $ticket) {
            $ticket_number = 'TCKT-'.str_pad($ticket->ID, 3, '0', STR_PAD_LEFT);
            $formatted_tickets[] = [
                'id' => $ticket->ID,
                'ticket_number' => $ticket_number,
                'ticket_title' => $ticket->post_title,
                'ticket_status' => get_post_meta($ticket->ID, '_robodesk_ticket_status', true),
                'ticket_priority' => '' ,
                'ticket_origin' => '' ,
                'ticket_author' => get_user_by('id', $user_id)->display_name,
                'ticket_author_image' => get_avatar_url($user_id),
                'ticket_time_ago' => 'Just now',
                'ticket_messages' => [],
            ];
        }

        return rest_ensure_response(array(
            'status'  => 'success',
            'message' => 'Tickets fetched successfully',
            'data'    => $formatted_tickets
        ));
    }   

    // Handle ticket reply event
    if (isset($data['event']) && 'replies.receive' === $data['event']) {
        $ticket_id  = sanitize_text_field($data['data']['ticket_id'] ?? '');
        $user_id   = sanitize_text_field($data['data']['user_id'] ?? '');
        $reply     = sanitize_textarea_field($data['data']['message'] ?? '');

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

        // Add reply to ticket
        $comment_id = wp_insert_comment(array(
            'comment_post_ID' => $ticket_id,
            'comment_author'  => $user_id,
            'comment_content' => $reply,
            'comment_approved' => 1,
            'comment_type' => 'ticket_replies'
        ));
        
        $comment = get_comment($comment_id);
        
        $data = [
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author, // Display name
            'author_id' => $comment->user_id,    // User ID
            'author_image' => get_avatar_url($comment->user_id, ['size' => 96]),
            'content' => $comment->comment_content,
            'created_at' => $comment->comment_date,
        ];

        if ($comment_id) {
            rbd_log(sprintf(
                "Comment received for ticket #%s: %s\n",
                $ticket_id,
                $reply
            ));
            
            return rest_ensure_response(array(
                'status'  => 'success',
                'message' => 'Reply added successfully',
                'data'    => $data
            ));
        }
    }
    
    // Get ticket Comments
    if (isset($data['event']) && 'comments.get' === $data['event']){
        $loggedinuserid   = sanitize_text_field($data['data']['uid'] ?? '');
        $ticketID =  sanitize_text_field($data['data']['ticket_id'] ?? '');
        
        $comments = get_comments([
            'post_id' => $ticketID, 
            'order' => 'ASC', 
        ]);
        
        if(empty($comments)){
            return new WP_Error(
                400,
                'Comments Not found',
            );
        }
        $formatted_comments = [];
        foreach($comments as $comment){
            $formatted_comments[] = [
                'id' => $comment->comment_ID,
                'author' => $comment->comment_author,
                'author_id' => $comment->user_id,
                'author_image' => get_avatar_url($comment->user_id, ['size' => 96]),
                'content' => $comment->comment_content,
                'created_at' => $comment->comment_date,
            ];
        }
        return rest_ensure_response(array(
            'status'  => 'success',
            'message' => 'Comments found',
            'data'=> $formatted_comments,       
        ));
    }

    // Handle unknown events
    return new WP_Error(
        'invalid_event',
        'Invalid event type',
        array('status' => 400)
    );
}

add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
    if ($post_type === 'robodesk_ticket') {
        return false;
    }
    return $use_block_editor;
}, 10, 2);