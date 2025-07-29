<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Defining JWT secret keys
if (!defined('JWT_AUTH_SECRET_KEY')) {
    define('JWT_AUTH_SECRET_KEY', 'robodesk-secret');
}

/**
 * Generate JWT token for a given WordPress user.
 *
 * @param WP_User|int $user WP_User object or user ID.
 * @param int $expiration_in_days Number of days the token will be valid (default: 7).
 * @return string|WP_Error JWT token string on success, WP_Error on failure.
 */
function robodesk_generate_token($user, $expiration_in_days = 7)
{
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }

    if (!($user instanceof WP_User)) {
        return new WP_Error('invalid_user', 'Invalid user object.');
    }

    // Generate or retrieve the secret key
    $secret_key = defined('JWT_AUTH_SECRET_KEY')
        ? JWT_AUTH_SECRET_KEY
        : wp_generate_password(64, true, true);

    // Set issued and expiration timestamps
    $issued_at = time();
    $expire = $issued_at + (DAY_IN_SECONDS * $expiration_in_days);

    // Token payload
    $token = array(
        'iss'  => get_site_url(),
        'iat'  => $issued_at,
        'exp'  => $expire,
        'data' => array(
            'user_id'    => $user->ID,
            'user_email' => $user->user_email,
        ),
    );

    try {
        $jwt = JWT::encode($token, $secret_key, 'HS256');
        return $jwt;
    } catch (Exception $e) {
        return new WP_Error('jwt_generation_failed', 'Failed to generate JWT: ' . $e->getMessage());
    }
}



/* 
The Hook to allow  headers to the response to allow Cross-Origin Resource Sharing (CORS) for 
the REST API requests. CORS is a security feature implemented by webbrowsers to restrict web 
pages from making requests to a different domain than the one that served
the original page. 
*/
add_filter('rest_pre_serve_request', function ($value, $result, $request, $server) {


    header('Access-Control-Allow-Origin: ' . esc_url_raw(site_url()));
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');


    return $value;
}, 10, 4);

// add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
//         if (strpos($request->get_route(), '/comments-stream/') !== false) {
//             header('Access-Control-Allow-Origin: *');
//             header('Access-Control-Allow-Methods: GET');
//             header('Access-Control-Allow-Headers: Content-Type, Cache-Control');
//         }
//         return $served;
//     }, 10, 4);


/* Public Rest Api registration hooks */

add_action('rest_api_init', callback: function () {

    register_rest_route('robodesk/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'secure_login_endpoint',
        'permission_callback' => '__return_true', // Public access for login
    ));

    register_rest_route('custom/v1', '/protected', array(
        'methods' => 'GET',
        'callback' => 'protected_endpoint',
        'permission_callback' => 'robodesk_validate_token',
    ));

    // Robodesk Registration Rest Api Registration
    register_rest_route('robodesk/v1', '/signup', array(
        'methods' => 'POST',
        'callback' => 'robodesk_user_signup',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('robodesk/chat/v1', '/tickets', array(
        'methods'  => 'POST',
        'callback' => 'robodesk_handle_tickets',
        'permission_callback' => '__return_true',
    ));


    // register_rest_route('robodesk/v1', '/comments-stream/(?P<post_id>\d+)', array(
    //     'methods' => 'GET',
    //     'callback' => 'robodesk_stream_comments',
    //     'permission_callback' => 'robodesk_check_sse_permissions',
    //     'args' => array(
    //         'post_id' => array(
    //             'validate_callback' => function($param, $request, $key) {
    //                 return is_numeric($param);
    //             }
    //         ),
    //         'last_comment_id' => array(
    //             'default' => 0,
    //             'validate_callback' => function($param, $request, $key) {
    //                 return is_numeric($param);
    //             }
    //         ),
    //     ),
    // ));

    // register_rest_route('robodesk/v1', '/comments-stream/(?P<post_id>\d+)(?:/(?P<last_comment_id>\d+))?', array(
    // 'methods' => 'GET',
    // 'callback' => 'robodesk_stream_comments_a',
    // 'permission_callback' => '__return_true',
    // 'args' => array(
    //     'post_id' => array(
    //         'validate_callback' => function($param) {
    //             return true;
    //         }
    //     ),
    //     'last_comment_id' => array(
    //         'validate_callback' => function($param) {
    //             return true;
    //         }
    //     ),
    // ),
    // ));

    register_rest_route('robodesk/v1', '/messaging/(?P<post_id>\d+)(?:/(?P<last_comment_id>\d+))?(?:/(?P<user_id>\d+))?', array(
        'methods' => 'GET',
        'callback' => 'robodesk_live_comments',
        'permission_callback' => '__return_true',
        'args' => array(
            'post_id' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                }
            ),
            'last_comment_id' => array(
                'validate_callback' => function ($param) {
                    return empty($param) || is_numeric($param);
                }
            ),
            'user_id' => array(
                'validate_callback' => function ($param) {
                    return empty($param) || is_numeric($param);
                }
            ),
        ),
    ));
});


function robodesk_live_comments($request)
{
    $ticket_id = $request->get_param('post_id');
    $last_comment_id = $request->get_param('last_comment_id');
    $loggedin_user_id = $request->get_param('user_id');
    if (get_post_type($ticket_id) !== 'robodesk_ticket') return;

    $last_admin_reply_id =  get_post_meta($ticket_id, 'last_admin_comment_id', true);

    header('Content-Type: text/event-stream');
    // header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    header('X-Accel-Buffering: no');

    // Disable output buffering and compression
    if (ob_get_level()) ob_end_clean();

    // Disable WordPress output compression
    ini_set('zlib.output_compression', 0);
    ini_set('output_buffering', 0);

    // Set execution limits
    set_time_limit(0);
    ignore_user_abort(false);

    //Handshake
    echo "event: handshake\n";
    echo "data: " . json_encode(array(
        'status' => 'connected',
        'timestamp' => time(),
        'post_id' => $ticket_id,
        'last_comment_id' => $loggedin_user_id
    )) . "\n\n";
    flush();

    //checking the admin id 
    if ($last_admin_reply_id > $last_comment_id) {
        $args = array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'comment__in' => range($last_comment_id + 1, $last_admin_reply_id),
            'orderby' => 'comment_ID',
            'order' => 'ASC',
        );
        
        $comments = get_comments($args);
        $new_admin_comments = array();

        foreach ($comments as $comment) {
            $user = get_userdata($comment->user_id);
            if ($user && in_array('administrator', $user->roles)) {
                $new_admin_comments[] = array(
                    'id' => (int) $comment->comment_ID,
                    'content' => array(
                        'rendered' => wpautop($comment->comment_content)
                    ),
                    'date' => $comment->comment_date,
                    'author_data' => array(
                        'id' => (int) $comment->user_id
                    ),
                    'author_name' => $comment->display_name ?: $comment->comment_author,
                    'author_image' => get_avatar_url($comment->user_id ?: $comment->comment_author_email, array('size' => 32)),
                    'time_ago' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ago',
                    'author_id' => (int) $comment->user_id,
                    'post_author_id' => (int) $ticket_id ? get_post_field('post_author', $ticket_id) : 0,   
                );
                 
            }
        }

        if (!empty($new_admin_comments)) {
            foreach ($new_admin_comments as $comment) {
                echo "event: new_comment\n";
                echo "data: " . json_encode($comment) . "\n\n";
                flush();
            }
        } else {
            echo "event: disconnect\n";
            echo "data: " . json_encode(array('reason' => 'timeout')) . "\n\n";
            flush();
        }
    }


    echo "retry: 5000\n"; // Retry after 5 seconds
    flush();
    exit;
}

// function robodesk_stream_


// function robodesk_stream_comments_a($request)
// {
//     $ticket_id = $request->get_param('post_id');
//     $last_comment_id = $request->get_param('last_comment_id');
//     $loggedin_user_id = $request->get_param('user_id');

//     if (get_post_type($ticket_id) !== 'robodesk_ticket') return;
//     // Set headers for SSE
//     header('Content-Type: text/event-stream');
//     header('Cache-Control: no-cache');
//     header('Connection: keep-alive');
//     header('Access-Control-Allow-Origin: *');
//     header('Access-Control-Allow-Headers: Cache-Control');
//     header('X-Accel-Buffering: no');

//     // Disable output buffering and compression
//     if (ob_get_level()) ob_end_clean();

//     // Disable WordPress output compression
//     ini_set('zlib.output_compression', 0);
//     ini_set('output_buffering', 0);

//     // Set execution limits
//     set_time_limit(0);
//     ignore_user_abort(false);

//     //handshake
//     echo "event: connected\n";
//     echo "data: " . json_encode(array(
//         'status' => 'connected',
//         'timestamp' => time(),
//         'post_id' => $ticket_id,
//         'last_comment_id' => $loggedin_user_id
//     )) . "\n\n";
//     flush();



//     echo "event: disconnect\n";
//     echo "data: " . json_encode(array('reason' => 'timeout')) . "\n\n";
//     flush();
// }

// function robodesk_stream_comments($request)
// {
//     $post_id = $request->get_param('post_id');
//     $last_comment_id = $request->get_param('last_comment_id');

//     // Validate post exists
//     if (!get_post($post_id)) {
//         return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
//     }

//     // Set headers for SSE
//     header('Content-Type: text/event-stream');
//     header('Cache-Control: no-cache');
//     header('Connection: keep-alive');
//     header('Access-Control-Allow-Origin: *');
//     header('Access-Control-Allow-Headers: Cache-Control');
//     header('X-Accel-Buffering: no'); // Disable Nginx buffering

//     // Disable output buffering and compression
//     if (ob_get_level()) {
//         ob_end_clean();
//     }

//     // Disable WordPress output compression
//     ini_set('zlib.output_compression', 0);
//     ini_set('output_buffering', 0);

//     // Set execution limits
//     set_time_limit(0);
//     ignore_user_abort(false);

//     // Connection parameters
//     $start_time = time();
//     $max_execution_time = 30; // 30 seconds
//     $check_interval = 2; // Check every 2 seconds
//     $heartbeat_interval = 10; // Send heartbeat every 10 seconds
//     $last_heartbeat = time();

//     // Send initial connection confirmation
//     echo "event: connected\n";
//     echo "data: " . json_encode(array(
//         'status' => 'connected',
//         'timestamp' => time(),
//         'post_id' => $post_id,
//         'last_comment_id' => $last_comment_id
//     )) . "\n\n";
//     flush();

//     while (time() - $start_time < $max_execution_time) {
//         // Check if client disconnected
//         if (connection_aborted()) {
//             break;
//         }

//         // Check for new comments
//         $new_comments = robodesk_get_optimized_comments($post_id, $last_comment_id);

//         if (!empty($new_comments)) {
//             foreach ($new_comments as $comment) {
//                 $comment_data = format_comment_for_sse($comment, $post_id);

//                 // Send the comment
//                 echo "event: new_comment\n";
//                 echo "data: " . json_encode($comment_data) . "\n\n";
//                 flush();

//                 // Update last comment ID
//                 $last_comment_id = max($last_comment_id, $comment->comment_ID);
//             }
//         }

//         // Send heartbeat to keep connection alive
//         if (time() - $last_heartbeat >= $heartbeat_interval) {
//             echo "event: heartbeat\n";
//             echo "data: " . json_encode(array(
//                 'timestamp' => time(),
//                 'last_comment_id' => $last_comment_id
//             )) . "\n\n";
//             flush();
//             $last_heartbeat = time();
//         }

//         // Wait before next check
//         sleep($check_interval);
//     }

//     // Send disconnect event
//     echo "event: disconnect\n";
//     echo "data: " . json_encode(array('reason' => 'timeout')) . "\n\n";
//     flush();

//     exit();
// }

// Permission check for SSE endpoint
function robodesk_check_sse_permissions($request)
{
    $post_id = $request['post_id'];
    return current_user_can('read_post', $post_id) || is_user_logged_in();
}
// Optimized function to get new comments
function robodesk_get_optimized_comments($post_id, $last_comment_id = 0)
{
    global $wpdb;

    // Use transient cache for performance
    $cache_key = "new_comments_{$post_id}_{$last_comment_id}";
    $cached_result = get_transient($cache_key);

    if ($cached_result !== false) {
        return $cached_result;
    }

    $query = $wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_email 
         FROM {$wpdb->comments} c 
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
         WHERE c.comment_post_ID = %d 
         AND c.comment_ID > %d 
         AND c.comment_approved = '1' 
         ORDER BY c.comment_date ASC 
         LIMIT 10",
        $post_id,
        $last_comment_id
    );

    $results = $wpdb->get_results($query);

    // Cache for 1 second to prevent excessive DB queries
    set_transient($cache_key, $results, 1);

    return $results;
}

// Format comment data for SSE
function format_comment_for_sse($comment, $post_id)
{
    $post = get_post($post_id);

    return array(
        'id' => (int) $comment->comment_ID,
        'content' => array(
            'rendered' => wpautop($comment->comment_content)
        ),
        'date' => $comment->comment_date,
        'author_data' => array(
            'id' => (int) $comment->user_id
        ),
        'author_name' => $comment->display_name ?: $comment->comment_author,
        'author_image' => get_avatar_url($comment->user_id ?: $comment->comment_author_email, array('size' => 32)),
        'time_ago' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ago',
        'author_id' => (int) $comment->user_id,
        'post_author_id' => (int) $post->post_author
    );
}

// Enhanced hook for comment notifications
add_action('comment_post', 'enhanced_comment_notification', 10, 3);

function enhanced_comment_notification($comment_id, $comment_approved, $commentdata)
{
    if ($comment_approved === 1) {
        $post_id = $commentdata['comment_post_ID'];

        delete_transient("new_comments_{$post_id}_*");
    }
}


// Clean up old transients periodically
add_action('wp_scheduled_delete', 'cleanup_sse_transients');

function cleanup_sse_transients()
{
    global $wpdb;

    // Delete old comment cache transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_new_comments_%' 
         OR option_name LIKE '_transient_timeout_new_comments_%'"
    );
}

function secure_login_endpoint(WP_REST_Request $request)
{


    $emailAddr = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');

    // Input validation
    if (empty($emailAddr) || empty($password)) {
        return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
    }

    // Check if username is email
    if (is_email($emailAddr)) {
        $user = get_user_by('email', $emailAddr);
        if (!$user) {
            return new WP_Error('invalid_email', 'No user found with this email.', array('status' => 404));
        }
        $username = $user->user_login;
    }

    // Authenticate user
    $creds = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => false,
    );


    // Sign in the user
    $user = wp_signon($creds, false);

    if (!is_wp_error($user)) {

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
    }


    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
    }

    // Generate JWT
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : wp_generate_password(64, true, true);
    $issued_at = time();
    $expire = $issued_at + (DAY_IN_SECONDS * 7); // Token valid for 7 days

    $token = array(
        'iss' => get_site_url(),
        'iat' => $issued_at,
        'exp' => $expire,
        'data' => array(
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
        ),
    );

    try {
        $jwt = JWT::encode($token, $secret_key, 'HS256');
        return array(
            'success' => true,
            'token' => $jwt,
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
            'user_display_name' => $user->display_name,
            'expires_at' => $expire,
        );
    } catch (Exception $e) {
        return new WP_Error('jwt_error', 'Error generating token: ' . $e->getMessage(), array('status' => 500));
    }
}

function robodesk_validate_token(WP_REST_Request $request)
{
    $auth_header = $request->get_header(key: 'Authorization');

    if (!$auth_header) {
        return new WP_Error('no_token', 'Authorization header missing.', array('status' => 401));
    }

    // Extract token from "Bearer <token>"
    $token = str_replace('Bearer ', '', trim($auth_header));
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : wp_generate_password(64, true, true);

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

        // Verify issuer
        if ($decoded->iss !== get_site_url()) {
            return new WP_Error('invalid_token', 'Invalid token issuer.', array('status' => 401));
        }

        // Verify expiration
        if ($decoded->exp < time()) {
            return new WP_Error('expired_token', 'Token has expired.', array('status' => 401));
        }

        // Verify user exists
        $user = get_user_by('id', $decoded->data->user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'User not found.', array('status' => 401));
        }

        // Set current user for the request
        wp_set_current_user($user->ID);
        return true;
    } catch (Exception $e) {
        return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage(), array('status' => 401));
    }
}

function protected_endpoint(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    return array(
        'success' => true,
        'message' => 'Access granted to protected endpoint.',
        'user_id' => $user->ID,
        'user_email' => $user->user_email,
    );
}


//Registers the new user
function robodesk_user_signup(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $email = sanitize_email($params['email']);

    if (empty($email)) {
        return new WP_Error(
            'invalid_data',
            __('Missing email field', 'robodesk'),
            array('status' => 400)
        );
    }

    if (email_exists($email)) {
        return new WP_Error(
            'user_exists',
            __('Email already exists', 'robodesk'),
            array('status' => 409)
        );
    }

    // Generate a random strong password
    $password = wp_generate_password(12, true, true); // 12 characters, include special characters

    // Prepare user data for wp_insert_user
    $user_data = array(
        'user_login' => $email, // Use email as username
        'user_email' => $email,
        'user_pass' => $password,
        'role' => 'subscriber', // Default role, adjust as needed
    );

    // Create user with wp_insert_user
    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        return new WP_Error(
            'user_creation_failed',
            $user_id->get_error_message(),
            array('status' => 500)
        );
    }

    $creds = array(
        'user_login'    => $email,
        'user_password' => $password,
        'remember'      => true,
    );

    // Sign in the user
    $user = wp_signon($creds, false);

    if (!is_wp_error($user)) {

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
    }

    // Send default WordPress user notification email
    wp_new_user_notification($user_id, null, 'both'); // Sends email to user and admin

    // Generate JWT token
    $token = robodesk_generate_token($user_id);

    if (is_wp_error($token)) {
        return new WP_Error(
            'token_failed',
            __('Failed to generate token', 'robodesk'),
            array('status' => 500)
        );
    }

    return new WP_REST_Response(array(
        'message' => __('User created successfully', 'robodesk'),
        'token' => $token,
        'user_id' => $user_id,
    ), 201);
}
//build arrays of ticket data to return in rest response
function build_ticket_response_data($ticket_id, $user_object, $reply_id = false)
{
    $ticket_number = 'TCKT-' . str_pad($ticket_id, 3, '0', STR_PAD_LEFT);

    $ticket_origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true) ?: 'N/A';

    $ticket_priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
    $ticket_priority = (!is_wp_error($ticket_priority_terms) && !empty($ticket_priority_terms)) ? $ticket_priority_terms[0]->name : false;

    $last_activity = get_post_time('U', true, $ticket_id);
    $human_readable_time = human_time_diff($last_activity, current_time('U'));

    return [
        'id' => $ticket_id,
        'ticket_number' => $ticket_number,
        'ticket_title' => get_the_title($ticket_id),
        'ticket_status' => get_post_meta($ticket_id, '_robodesk_ticket_status', true),
        'ticket_priority' => $ticket_priority,
        'ticket_origin' => $ticket_origin,
        'ticket_author' => $user_object->display_name,
        'ticket_author_email' => $user_object->user_email,
        'ticket_author_image' => get_avatar_url($user_object->ID),
        'ticket_time_ago' => $human_readable_time,
        'comment_id' => $reply_id,
    ];
}


// Rest Api tickets rest api callback functions
function robodesk_handle_tickets(WP_REST_Request $request)
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

    // Ensure 'event' is set
    if (!isset($data['event'])) {
        return new WP_Error(
            'missing_event',
            'Event type is missing from the payload',
            array('status' => 400)
        );
    }

    switch ($data['event']) {
        case 'ticket.created':
            return robodesk_handle_ticket_creation($data);

        case 'tickets.get':
            return robodesk_handle_tickets_get($data);

        case 'replies.receive':
            return robodesk_handle_reply_receive($data);

        case 'comments.get':
            return robodesk_handle_comments_get($data);

        default:
            return new WP_Error(
                'invalid_event',
                'Invalid event type',
                array('status' => 400)
            );
    }
}

//Creating Tikcets
function robodesk_handle_ticket_creation($data)
{
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
            '_robodesk_ticket_status' => 'open',
            'robodesk_ticket_origin' => 'chat'
        ),
    ));

    if (is_wp_error($ticket)) {
        return $ticket;
    }

    // Get user data once and cache it
    $user_object = get_cached_user_data($author);
    if (!$user_object) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $user_name = $user_object->display_name;
    $email_address = $user_object->user_email;

    // Add initial comment
    $comment_id = wp_insert_comment(array(
        'comment_post_ID' => $ticket,
        'user_id'  => $author,
        'comment_content' => $title,
        'comment_author' => $user_name,
        'comment_author_email' => $email_address,
        'comment_approved' => 1,
        'comment_type' => 'ticket_replies'
    ));

    $reply_id = !is_wp_error($comment_id) ? $comment_id : false;

    $response_data = build_ticket_response_data($ticket, $user_object, $reply_id);

    return rest_ensure_response(array(
        'status'  => 'success',
        'message' => 'Ticket created successfully',
        'data'    => $response_data
    ));
}

//get specific tickets of specific user by his id
function robodesk_handle_tickets_get($data)
{
    $user_id = sanitize_text_field($data['data']['uid'] ?? '');

    // Optimized query with specific fields
    $ticket_query = new WP_Query([
        'post_type' => 'robodesk_ticket',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'author' => $user_id,
        'fields' => 'ids',
        'no_found_rows' => true, // Skip pagination counting
        'update_post_meta_cache' => false, // Skip meta cache initially
        'update_post_term_cache' => false, // Skip term cache initially
    ]);

    if (empty($ticket_query->posts)) {
        return rest_ensure_response([
            'status' => 'success',
            'message' => 'No tickets found',
            'data' => []
        ]);
    }

    // Batch load all required data
    $ticket_ids = $ticket_query->posts;
    $tickets_data = get_tickets_batch_data($ticket_ids, $user_id);

    return rest_ensure_response([
        'status' => 'success',
        'message' => 'Tickets fetched successfully',
        'data' => $tickets_data
    ]);
}


//get all tickets data by using ticket id and user id 
function get_tickets_batch_data($ticket_ids, $user_id)
{
    // Batch load all posts
    $posts = get_posts([
        'post_type' => 'robodesk_ticket',
        'include' => $ticket_ids,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    // Batch load all meta data
    $meta_data = [];
    $terms_data = [];
    foreach ($ticket_ids as $id) {
        $meta_data[$id]['status'] = get_post_meta($id, '_robodesk_ticket_status', true);
        $meta_data[$id]['origin'] = get_post_meta($id, 'robodesk_ticket_origin', true);
        $priority_terms = get_the_terms($id, 'robodesk_ticket_priority');
        $terms_data[$id] = [
            'priority' => (!is_wp_error($priority_terms) && !empty($priority_terms)) ? $priority_terms[0]->name : false,
        ];
    }


    // Get user data once
    $user_object = get_cached_user_data($user_id);
    $user_name = $user_object ? $user_object->display_name : 'Unknown';
    $avatar_url = get_avatar_url($user_id);

    // Build response array
    $formatted_tickets = [];
    foreach ($posts as $post) {
        $ticket_id = $post->ID;
        $ticket_number = 'TCKT-' . str_pad($ticket_id, 3, '0', STR_PAD_LEFT);

        $last_activity = get_post_time('U', true, $post);
        $human_readable_time = human_time_diff($last_activity, current_time('U'));

        $formatted_tickets[] = [
            'id' => $ticket_id,
            'ticket_number' => $ticket_number,
            'ticket_title' => $post->post_title,
            'ticket_status' => $meta_data[$ticket_id]['status'] ?? 'open',
            'ticket_priority' => $terms_data[$ticket_id]['priority'],
            'ticket_origin' => $meta_data[$ticket_id]['origin'],
            'ticket_author' => $user_name,
            'ticket_author_image' => $avatar_url,
            'ticket_time_ago' => $human_readable_time,
        ];
    }

    return $formatted_tickets;
}

function robodesk_handle_reply_receive($data)
{
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

    $user_object = get_cached_user_data($user_id);
    if (!$user_object) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    // Add reply to ticket
    $comment_id = wp_insert_comment(array(
        'comment_post_ID' => $ticket_id,
        'comment_author' => $user_object->display_name,
        'comment_author_email' => $user_object->user_email,
        'user_id'  => $user_id,
        'comment_content' => $reply,
        'comment_approved' => 1,
        'comment_type' => 'ticket_replies'
    ));

    if (!$comment_id) {
        return new WP_Error('comment_failed', 'Failed to add reply', array('status' => 500));
    }

    $comment = get_comment($comment_id);
    $response_data = [
        'id' => $comment->comment_ID,
        'author' => $comment->comment_author,
        'author_id' => $comment->user_id,
        'author_image' => get_avatar_url($comment->user_id, ['size' => 96]),
        'content' => $comment->comment_content,
        'created_at' => $comment->comment_date,
    ];

    rbd_log(sprintf(
        "Comment received for ticket #%s: %s\n",
        $ticket_id,
        $reply
    ));

    return rest_ensure_response(array(
        'status'  => 'success',
        'message' => 'Reply added successfully',
        'data'    => $response_data
    ));
}

function robodesk_handle_comments_get($data)
{
    $ticket_id = sanitize_text_field($data['data']['ticket_id'] ?? '');

    if (empty($ticket_id)) {
        return new WP_Error('missing_ticket_id', 'Ticket ID is required', array('status' => 400));
    }

    // Optimized comments query
    $comments = get_comments([
        'post_id' => $ticket_id,
        'order' => 'ASC',
        'type' => 'ticket_replies',
        'status' => 'approve'
    ]);

    if (empty($comments)) {
        return rest_ensure_response([
            'status' => 'success',
            'message' => 'No comments found',
            'data' => []
        ]);
    }

    // Batch process comments
    $formatted_comments = array_map(function ($comment) {
        return [
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author,
            'author_id' => $comment->user_id,
            'author_image' => get_avatar_url($comment->user_id, ['size' => 96]),
            'content' => $comment->comment_content,
            'created_at' => $comment->comment_date,
        ];
    }, $comments);

    return rest_ensure_response([
        'status'  => 'success',
        'message' => 'Comments found',
        'data' => $formatted_comments,
    ]);
}

function get_cached_user_data($user_id)
{
    static $user_cache = [];

    if (!isset($user_cache[$user_id])) {
        $user_cache[$user_id] = get_user_by('id', $user_id);
    }

    return $user_cache[$user_id];
}
