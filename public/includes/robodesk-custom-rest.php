<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$robodesk_jwt_file = ROBODESK_PLUGIN_PATH . 'public/includes/php-jwt/src/JWT.php';
$robodesk_key_file = ROBODESK_PLUGIN_PATH . 'public/includes/php-jwt/src/Key.php';

if (file_exists($robodesk_jwt_file) && file_exists($robodesk_key_file)) {
    require_once $robodesk_jwt_file;
    require_once $robodesk_key_file;
} else {
    if (!function_exists('robodesk_missing_jwt_library_notice')) {
        function robodesk_missing_jwt_library_notice()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            echo '<div class="notice notice-error"><p><strong>Robodesk:</strong> JWT library files are missing. Please reinstall the plugin package.</p></div>';
        }
    }

    add_action('admin_notices', 'robodesk_missing_jwt_library_notice');
    return;
}


use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

/**
 * Get the JWT secret key.
 *
 * Uses a dedicated constant when defined, otherwise derives a site-unique
 * secret via wp_hash() instead of directly using WordPress auth constants.
 *
 * @return string
 */
function robodesk_get_jwt_secret_key()
{
    if (defined('ROBODESK_JWT_SECRET') && ROBODESK_JWT_SECRET !== '') {
        return ROBODESK_JWT_SECRET;
    }

    return wp_hash('robodesk-jwt-secret');
}

/**
 * Write a debug message to the WP debug log when logging is enabled.
 *
 * @param string $message Log message.
 * @return void
 */
function robodesk_log_debug($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('[Robodesk] ' . $message);
    }
}

/**
 * Get user profile picture URL
 * Prioritizes custom wp_user_avatar over Gravatar
 * 
 * @param int $user_id User ID
 * @param array $args Avatar arguments (size, etc)
 * @return string Avatar URL
 */
function robodesk_get_user_avatar_url($user_id, $args = array())
{
    $avatar_id = get_user_meta($user_id, 'wp_user_avatar', true);

    // If user has a custom avatar, return its URL
    if ($avatar_id) {
        $avatar_url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
        if ($avatar_url) {
            return $avatar_url;
        }
    }

    // Fall back to WordPress default avatar (Gravatar)
    return get_avatar_url($user_id, $args);
}

/**
 * Preferred avatar getter for comments and tickets.
 * This will use `robodesk_get_avatar_url` if available (which checks support profile image meta),
 * otherwise falls back to `robodesk_get_user_avatar_url`.
 */
function robodesk_get_comment_avatar($user_id, $size = 32)
{
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return '';
    }

    if (function_exists('robodesk_get_avatar_url')) {
        return robodesk_get_avatar_url($user_id, $size);
    }

    return robodesk_get_user_avatar_url($user_id, array('size' => $size));
}

// Add author_image field to WordPress REST API comments
add_action('rest_api_init', function () {
    register_rest_field('comment', 'author_image', array(
        'get_callback' => function ($comment) {
            if (is_array($comment)) {
                $user_id = isset($comment['user_id']) ? intval($comment['user_id']) : 0;
                $author_email = $comment['author_email'] ?? '';
            } else {
                $user_id = isset($comment->user_id) ? intval($comment->user_id) : 0;
                $author_email = $comment->comment_author_email ?? '';
            }

            $comment_id = is_array($comment) ? ($comment['id'] ?? 0) : ($comment->comment_ID ?? 0);
            if ($comment_id && function_exists('robodesk_is_bot_comment') && robodesk_is_bot_comment($comment_id)) {
                return function_exists('robodesk_get_bot_avatar_url') ? robodesk_get_bot_avatar_url(32) : get_avatar_url('', array('size' => 32, 'default' => 'mystery', 'force_default' => true));
            }

            if (function_exists('robodesk_get_comment_avatar')) {
                $avatar = robodesk_get_comment_avatar($user_id, 32);
                if (!empty($avatar)) {
                    return $avatar;
                }
            }

            if (!empty($author_email)) {
                return get_avatar_url($author_email, array('size' => 32));
            }

            return '';
        },
        'schema' => array(
            'description' => 'URL of the comment author\'s avatar image',
            'type' => 'string',
            'context' => array('view', 'edit'),
        ),
    ));

    // Expose whether this comment is a bot response so client can render appropriate avatar
    register_rest_field('comment', 'is_bot_response', array(
        'get_callback' => function ($comment) {
            if (is_array($comment)) {
                $comment_id = $comment['id'] ?? 0;
            } else {
                $comment_id = $comment->comment_ID ?? 0;
            }
            $val = get_comment_meta($comment_id, 'is_bot_response', true);
            if ($val === '1' || $val === 1 || $val === true) {
                return true;
            }
            return false;
        },
        'schema' => array(
            'description' => 'Flag indicating this reply came from the bot',
            'type' => 'boolean',
            'context' => array('view', 'edit'),
        ),
    ));
});

// function robodesk_allow_image_in_comments( $allowed_tags ) {
//     $allowed_tags['img'] = array(
//         'src' => array(),
//         'alt' => array(),
//         'class' => array(),
//         'width' => array(),
//         'height' => array()
//     );
//     return $allowed_tags;
// }
// add_filter( 'wp_kses_allowed_html', 'robodesk_allow_image_in_comments', 10, 1 );


// function handle_base64_image_upload($base64_data) {
//     // Extract the image data


//     if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $matches)) {


//         $image_type = $matches[1]; // png, jpg, etc.
//         $image_data = substr($base64_data, strpos($base64_data, ',') + 1);
//         $image_data = base64_decode($image_data);

//         if (!$image_data) {
//             return new WP_Error('invalid_image', 'Invalid base64 image data');
//         }

//         $upload_dir = wp_upload_dir();
//         $filename = 'ticket-' . time() . '.' . $image_type;
//         $file_path = $upload_dir['path'] . '/' . $filename;

//         if (file_put_contents($file_path, $image_data) === false) {
//             return new WP_Error('file_error', 'Could not save image file');
//         }

//         // Prepare file data for WordPress
//         $filetype = wp_check_filetype($filename, null);
//         $attachment = array(
//             'post_mime_type' => $filetype['type'],
//             'post_title' => sanitize_file_name($filename),
//             'post_content' => '',
//             'post_status' => 'inherit'
//         );

//         // Insert attachment
//         $attach_id = wp_insert_attachment($attachment, $file_path);

//         // Generate metadata and thumbnails
//         require_once(ABSPATH . 'wp-admin/includes/image.php');
//         $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
//         wp_update_attachment_metadata($attach_id, $attach_data);

//         return $attach_id;
//     }

//     return false;
// }

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

    $secret_key = robodesk_get_jwt_secret_key();
    $issued_at = time();
    $expire = $issued_at + (DAY_IN_SECONDS * $expiration_in_days);

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
        return JWT::encode($token, $secret_key, 'HS256');
    } catch (Exception $e) {
        robodesk_log_debug('JWT generation failed for user ' . $user->ID . ': ' . $e->getMessage());
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


function robodesk_can_view_ticket(WP_REST_Request $request)
{
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = (int) $request->get_param('user_id');
    $post_id = (int) $request->get_param('post_id');

    if ($user_id !== get_current_user_id()) {
        return false;
    }

    // post_id=0 means streaming all tickets (no specific ticket)
    if ($post_id === 0) {
        return true;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'robodesk_ticket') {
        return false;
    }

    if ((int) $post->post_author === $user_id) {
        return true;
    }

    return current_user_can('robodesk_access_dashboard');
}

/* Public Rest Api registration hooks */

add_action('rest_api_init', function () {

    register_rest_route('robodesk/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'robodesk_secure_login_endpoint',
        'permission_callback' => '__return_true', // Public access for login
    ));

    register_rest_route('robodesk/v1', '/validate-token', array(
        'methods' => 'GET',
        'callback' => 'robodesk_validate_token',
        'permission_callback' => '__return_true', // Auth handled by callback via Authorization header
    ));

    // Robodesk Registration Rest Api Registration
    register_rest_route('robodesk/v1', '/signup', array(
        'methods' => 'POST',
        'callback' => 'robodesk_user_signup',
        'permission_callback' => '__return_true', // Public registration endpoint
    ));

    register_rest_route('robodesk/chat/v1', '/tickets', array(
        'methods' => 'POST',
        'callback' => 'robodesk_handle_tickets',
        'permission_callback' => '__return_true', // Intentionally public webhook endpoint for external chat service. Callback validates JSON payload structure and event type before processing. All data is sanitized before storage. Chat service can only act on events it sends; no session/auth tokens are exposed. A sister endpoint (chat/v1/tickets in robodesk-ticket-post-type.php) uses HMAC verification for the same purpose.
    ));

    register_rest_route('robodesk/selected', '/staff', array(
        'methods' => 'GET',
        'callback' => 'robodesk_handle_selected_staff',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    register_rest_route('robodesk-bot/v1', '/wa-settings', array(
        'methods' => 'GET',
        'callback' => 'robodesk_handle_wa_settings',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('robodesk/v1', '/messaging/(?P<post_id>\d+)(?:/(?P<last_comment_id>\d+))?(?:/(?P<user_id>\d+))?', array(
        'methods' => 'GET',
        'callback' => 'robodesk_live_comments',
        'permission_callback' => 'robodesk_can_view_ticket',
        'args' => array(
            'post_id' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                }
            ),
            'last_comment_id' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                }
            ),
            'user_id' => array(
                'validate_callback' => function ($param) {
                    return empty($param) || is_numeric($param);
                }
            ),
        ),
    ));

    register_rest_route(
        'robodesk/v1',
        '/stream/(?P<type>[^/]+)/(?P<post_id>\d+)(?:/(?P<last_id>\d+))?(?:/(?P<user_id>\d+))?',
        array(
            array(
                'methods' => 'GET',
                'callback' => 'robodesk_stream',
                'permission_callback' => function (WP_REST_Request $request) {
                    if (!is_user_logged_in()) {
                        return false;
                    }
                    $user_id = (int) $request->get_param('user_id');
                    if ($user_id > 0 && $user_id !== get_current_user_id()) {
                        return false;
                    }
                    $type = $request->get_param('type');
                    if ($type === 'tickets' || $type === 'comments') {
                        return robodesk_can_view_ticket($request);
                    }
                    return true;
                },
                'args' => array(
                    'type' => array(
                        'validate_callback' => function ($param) {
                            return in_array($param, ['comments', 'tickets', 'notifications'], true);
                        },
                    ),
                    //tikcet id
                    'post_id' => array(
                        'validate_callback' => function ($param) {
                            return empty($param) || is_numeric($param);
                        },
                    ),

                    //first id of the ticket and last id of comments
                    'last_id' => array(
                        'validate_callback' => function ($param) {
                            return empty($param) || is_numeric($param);
                        },
                    ),
                    //logged in user id
                    'user_id' => array(
                        'validate_callback' => function ($param) {
                            return empty($param) || is_numeric($param);
                        },
                    ),
                ),
            ),
        )
    );
});



function robodesk_stream($request)
{
    $type = $request->get_param('type');
    $user_id = $request->get_param('user_id');
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: ' . esc_url(home_url()));
    header('Access-Control-Allow-Headers: Cache-Control');
    header('X-Accel-Buffering: no');

    if (function_exists('set_time_limit')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        set_time_limit(0);
    }

    if (function_exists('session_write_close')) {
        session_write_close();
    }

    if (ob_get_level())
        ob_end_clean();

    if (function_exists('ini_set')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('zlib.output_compression', 0);
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('output_buffering', 0);
    }

    if (function_exists('set_time_limit')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        set_time_limit(0);
    }
    if (function_exists('ignore_user_abort')) {
        ignore_user_abort(false);
    }

    if (connection_aborted())
        return;

    switch ($type) {
        case ('comments'):

            $ticket_id = (int) $request->get_param('post_id');
            if ($ticket_id > 0) {
                $last_comment_client = (int) $request->get_param('last_id');
                $last_seen_comment_id = $last_comment_client;

                echo "retry: 3000\n\n";
                flush();

                // Keep this endpoint short-lived. Browser EventSource reconnects
                // automatically, avoiding proxy/PHP-FPM 502s from long REST requests.
                wp_cache_delete('last_changed', 'comment_queries');
                clean_post_cache($ticket_id);

                $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true);

                $new_conversations = robodesk_live_conversations($ticket_id, $last_seen_comment_id, $user_id);
                if (!empty($new_conversations)) {
                    foreach ($new_conversations as $c) {
                        $comment_id = isset($c['id']) ? (int) $c['id'] : 0;
                        if ($comment_id > $last_seen_comment_id) {
                            $last_seen_comment_id = $comment_id;
                        }

                        echo "event: new_comment\n";
                        echo "data: " . wp_json_encode($c) . "\n\n";
                        flush();
                    }
                    update_post_meta($ticket_id, 'rbd_all_seen', 'yes');
                }

                $my_own_conversations = robodesk_my_own_conversations($ticket_id, $last_seen_comment_id);
                if (!empty($my_own_conversations)) {
                    foreach ($my_own_conversations as $moc) {
                        $comment_id = isset($moc['id']) ? (int) $moc['id'] : 0;
                        if ($comment_id > $last_seen_comment_id) {
                            $last_seen_comment_id = $comment_id;
                        }

                        echo "event: new_mine_comment\n";
                        echo "data: " . wp_json_encode($moc) . "\n\n";
                        flush();
                    }
                }

                if ($ticket_status == 'closed') {
                    echo "event: ticket_closed\n";
                    echo "data: " . wp_json_encode(array('reason' => 'ticket_closed')) . "\n\n";
                    flush();
                }

                if ($ticket_status == 'open') {
                    echo "event: ticket_open\n";
                    echo "data: " . wp_json_encode(array('reason' => 'ticket_open')) . "\n\n";
                    flush();
                }

                echo ": heartbeat\n\n";
                flush();
                exit;
            }

            break;

        case ('tickets'):

            $ticket_id = (int) $request->get_param('post_id');
            if ($ticket_id == 0) {

                $last_ticket_id = (int) $request->get_param('last_id');
                $last_seen_ticket_id = $last_ticket_id;

                // Short-lived: run once and exit. Browser EventSource reconnects
                // automatically via the retry directive, avoiding long-running
                // PHP-FPM workers that exhaust the pool.
                echo "retry: 5000\n\n";
                flush();

                wp_cache_delete('last_changed', 'comment_queries');
                wp_cache_delete('last_changed', 'posts');

                $new_ticket = robodesk_get_new_tickets($last_seen_ticket_id, $user_id);
                if (!empty($new_ticket)) {
                    foreach ($new_ticket as $ticket) {
                        $nt_id = isset($ticket['id']) ? (int) $ticket['id'] : 0;
                        if ($nt_id > $last_seen_ticket_id) {
                            $last_seen_ticket_id = $nt_id;
                        }

                        echo "event: new_ticket\n";
                        echo "data: " . wp_json_encode($ticket) . "\n\n";
                        flush();
                    }
                }

                $notifications = robodesk_get_new_notifications($user_id);
                if (!empty($notifications)) {
                    echo "event: new_notification\n";
                    echo "data: " . wp_json_encode($notifications) . "\n\n";
                    flush();
                }

                echo ": heartbeat\n\n";
                flush();
                exit;
            }

            break;


        default:
            echo "event: error\n";
            echo "data: " . wp_json_encode(array('message' => 'Invalid stream type')) . "\n\n";
            flush();
            exit;
    }

    exit;
}

//For new notifications
function robodesk_get_new_notifications($user_id)
{
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    $tickets = get_posts(array(
        'post_type' => 'robodesk_ticket',
        'post_status' => 'publish',
        'author' => $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
        'posts_per_page' => 10,
        'meta_query' => array(
            array(
                'key' => 'rbd_all_seen',
                'value' => 'no',
                'compare' => '='
            )
        )
    ));

    // Return each ticket with its latest reply id so the client can detect a
    // genuinely NEW message (rather than just an already-unread one) and beep
    // for every new reply regardless of how many are still unread.
    $result = array();
    foreach ($tickets as $ticket_id) {
        $latest = get_comments(array(
            'post_id' => $ticket_id,
            'number' => 1,
            'orderby' => 'comment_ID',
            'order' => 'DESC',
            'type' => 'ticket_replies',
            'status' => 'approve',
            'fields' => 'ids',
        ));
        $result[] = array(
            'id' => (int) $ticket_id,
            'last_comment_id' => !empty($latest) ? (int) $latest[0] : 0,
        );
    }

    return $result;
}


//For new conversations
function robodesk_live_conversations($ticket_id, $last_comment_client, $loggedin_user_id)
{

    if (get_post_type($ticket_id) !== 'robodesk_ticket')
        return [];

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    $last_comment_server = get_comments([
        'post_id' => $ticket_id,
        'number' => 1,
        'orderby' => 'comment_date',
        'order' => 'DESC',
        'status' => 'approve',
        'fields' => 'ids',
        'type' => 'ticket_replies',
        'meta_query' => [
            [
                'key' => 'admin_comment',
                'value' => 'yes',
                'compare' => '='
            ]
        ]
    ]);



    $last_comment_server = !empty($last_comment_server) ? (int) $last_comment_server[0] : false;
    $new_admin_comments = array();
    //checking the admin id 
    if ($last_comment_server && $last_comment_server > $last_comment_client) {
        $args = array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'comment__in' => range($last_comment_client + 1, $last_comment_server),
            'orderby' => 'comment_date',
            'order' => 'ASC',
            'type' => 'ticket_replies',
            'meta_query' => [
                [
                    'key' => 'admin_comment',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ],
            'comment__not_in' => array($last_comment_client)
        );


        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $comments = get_comments($args);
        foreach ($comments as $comment) {
            // The meta_query above already ensures admin_comment = 'yes'.
            // No secondary role check needed — trust the stored meta.
            $new_admin_comments[] = array(
                'id' => (int) $comment->comment_ID,
                'content' => array(
                    'rendered' => wpautop($comment->comment_content)
                ),
                'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                'date_utc' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date_gmt,
                'author_data' => array(
                    'id' => (int) $comment->user_id
                ),
                'author_name' => function_exists('robodesk_get_ticket_comment_author_name') ? robodesk_get_ticket_comment_author_name($comment) : ($comment->display_name ?: $comment->comment_author),
                'author_image' => function_exists('robodesk_get_ticket_comment_avatar_url') ? robodesk_get_ticket_comment_avatar_url($comment, 32) : robodesk_get_comment_avatar($comment->user_id ?: 0, 32),
                'is_bot_response' => function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false,
                'time_ago' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ago',
                'author_id' => (int) $comment->user_id,
                'post_author_id' => (int) $ticket_id ? get_post_field('post_author', $ticket_id) : 0,
            );
        }
    }


    return $new_admin_comments;
}
//For my own conversations
function robodesk_my_own_conversations($ticket_id, $last_my_comment_client)
{

    if (get_post_type($ticket_id) !== 'robodesk_ticket')
        return [];

    //get comment of the server which is not created by admin
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    $last_my_comment_server = get_comments([
        'post_id' => $ticket_id,
        'number' => 1,
        'orderby' => 'comment_date',
        'order' => 'DESC',
        'status' => 'approve',
        'fields' => 'ids',
        'type' => 'ticket_replies',
        'meta_query' => [
            [
                'key' => 'admin_comment',
                'value' => 'no',
                'compare' => '='
            ]
        ]
    ]);

    $last_my_comment_server = !empty($last_my_comment_server) ? (int) $last_my_comment_server[0] : false;
    $new_my_comments = array();

    // var_dump($last_my_comment_server);

    if ($last_my_comment_server > $last_my_comment_client) {

        $args = array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'comment__in' => [$last_my_comment_server],
            'orderby' => 'comment_date',
            'order' => 'ASC',
            'type' => 'ticket_replies',
            'meta_query' => [
                [
                    'key' => 'admin_comment',
                    'value' => 'no',
                    'compare' => '='
                ]
            ],
            'comment__not_in' => array($last_my_comment_client)
        );

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $comments = get_comments($args);
        foreach ($comments as $comment) {
            $user = get_userdata($comment->user_id);
            if ($user) {
                $new_my_comments[] = array(
                    'id' => (int) $comment->comment_ID,
                    'content' => array(
                        'rendered' => wpautop($comment->comment_content)
                    ),
                    'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                    'date_utc' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date_gmt,
                    'author_data' => array(
                        'id' => (int) $comment->user_id
                    ),
                    'author_name' => function_exists('robodesk_get_ticket_comment_author_name') ? robodesk_get_ticket_comment_author_name($comment) : ($comment->display_name ?: $comment->comment_author),
                    'author_image' => function_exists('robodesk_get_ticket_comment_avatar_url') ? robodesk_get_ticket_comment_avatar_url($comment, 32) : robodesk_get_comment_avatar($comment->user_id ?: 0, 32),
                    'is_bot_response' => function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false,
                    'time_ago' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ago',
                    'author_id' => (int) $comment->user_id,
                    'post_author_id' => (int) $ticket_id ? get_post_field('post_author', $ticket_id) : 0,
                );
            }
        }
    }

    return $new_my_comments;
}

function robodesk_get_new_tickets($last_id_client, $user_id)
{
    if (get_post_type($last_id_client) !== 'robodesk_ticket')
        return [];

    $server_ticket_id = get_posts(array(
        'post_type' => 'robodesk_ticket',
        'posts_per_page' => 1,
        'author' => $user_id,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC'
    ))[0] ?? null;

    // If no tickets found or no newer tickets, return empty array
    if (!$server_ticket_id || $server_ticket_id <= $last_id_client) {
        return [];
    }

    // Get all tickets newer than last_id_client
    $sending_tickets_data = get_posts(array(
        'post_type' => 'robodesk_ticket',
        'post_status' => 'publish',
        'author' => $user_id,
        'post__in' => range($last_id_client + 1, $server_ticket_id),
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    $results = [];

    if (empty($sending_tickets_data)) {
        return $results;
    }

    foreach ($sending_tickets_data as $conversations) {
        $ticket_id = $conversations->ID;
        $ticket_number = 'TCKT-' . str_pad($ticket_id, 3, '0', STR_PAD_LEFT);
        $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
        $priority_name = (!is_wp_error($priority_terms) && !empty($priority_terms)) ? $priority_terms[0]->name : 'N/A';

        // Author details
        $author_id = $conversations->post_author;
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_image = robodesk_get_comment_avatar($author_id, 32);

        // Time ago
        $time_diff = human_time_diff(get_the_time('U', $ticket_id), current_time('timestamp')) . ' ago';

        // Comments
        $comments = get_comments(['post_id' => $ticket_id, 'status' => 'approve']);
        $total_comments = count($comments);
        $formatted_comments = array_map(function ($comment) {
            return [
                'id' => $comment->comment_ID,
                'author' => $comment->comment_author,
                'content' => $comment->comment_content,
                'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date
            ];
        }, $comments);

        // Last reply
        $last_reply = !empty($comments) ? $comments[0]->comment_content : false;

        $results[] = [
            'id' => $ticket_id,
            'ticketNumber' => $ticket_number,
            'title' => $conversations->post_title,
            'status' => get_post_meta($conversations->ID, '_robodesk_ticket_status', true) ?? 'open',
            'priority' => $priority_name,
            'source' => get_post_meta($conversations->ID, 'robodesk_ticket_origin', true) ?: 'N/A',
            'author' => $author_name,
            'authorImage' => $author_image,
            'timeAgo' => $time_diff,
            'totalComments' => $total_comments,
            'notifications' => 'yes',
            'comments' => $formatted_comments,
            'lastReply' => $last_reply
        ];
    }

    return $results;
}


function robodesk_live_comments($request)
{
    $ticket_id = $request->get_param('post_id');
    $last_comment_client = (int) $request->get_param('last_comment_id'); //from frontend 
    $loggedin_user_id = $request->get_param('user_id');
    if (get_post_type($ticket_id) !== 'robodesk_ticket')
        return;

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: ' . esc_url(home_url()));
    header('Access-Control-Allow-Headers: Cache-Control');
    header('X-Accel-Buffering: no');

    // Disable output buffering and compression
    if (ob_get_level())
        ob_end_clean();

    // Disable WordPress output compression
    if (function_exists('ini_set')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('zlib.output_compression', 0);
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('output_buffering', 0);
    }

    // Set execution limits
    if (function_exists('set_time_limit')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        set_time_limit(0);
    }
    if (function_exists('ignore_user_abort')) {
        ignore_user_abort(false);
    }

    $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true);

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    $last_comment_server = get_comments([
        'post_id' => $ticket_id,
        'number' => 1,
        'orderby' => 'comment_date',
        'order' => 'DESC',
        'status' => 'approve',
        'fields' => 'ids',
        'type' => 'ticket_replies',
        'meta_query' => [
            [
                'key' => 'admin_comment',
                'value' => 'yes',
                'compare' => '='
            ]
        ]
    ]);

    $last_comment_server = !empty($last_comment_server) ? (int) $last_comment_server[0] : false;

    //Handshake
    echo "event: handshake\n";
    echo "data: " . wp_json_encode(array(
        'status' => 'connected',
        'timestamp' => time(),
        'server_comment' => $last_comment_server,
        'client_comment' => $last_comment_client,
        'ranges' => range($last_comment_client, $last_comment_server)
    )) . "\n\n";
    flush();



    //checking the admin id 
    if ($last_comment_server && $last_comment_server > $last_comment_client) {
        $args = array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'comment__in' => range($last_comment_client, $last_comment_server),
            'orderby' => 'comment_date',
            'order' => 'ASC',
            'type' => 'ticket_replies',
            'meta_query' => [
                [
                    'key' => 'admin_comment',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        );


        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $comments = get_comments($args);
        $new_admin_comments = array();

        foreach ($comments as $comment) {
            $user = get_userdata($comment->user_id);
            if ($user && (
                in_array('administrator', $user->roles, true) ||
                in_array('robodesk_support_manager', $user->roles, true) ||
                in_array('robodesk_support_staff', $user->roles, true)
            )) {
                $new_admin_comments[] = array(
                    'id' => (int) $comment->comment_ID,
                    'content' => array(
                        'rendered' => wpautop($comment->comment_content)
                    ),
                    'date' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                    'date_utc' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date_gmt,
                    'author_data' => array(
                        'id' => (int) $comment->user_id
                    ),
                    'author_name' => function_exists('robodesk_get_ticket_comment_author_name') ? robodesk_get_ticket_comment_author_name($comment) : ($comment->display_name ?: $comment->comment_author),
                    'author_image' => function_exists('robodesk_get_ticket_comment_avatar_url') ? robodesk_get_ticket_comment_avatar_url($comment, 32) : robodesk_get_comment_avatar($comment->user_id ?: 0, 32),
                    'is_bot_response' => function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false,
                    'time_ago' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ago',
                    'author_id' => (int) $comment->user_id,
                    'post_author_id' => (int) $ticket_id ? get_post_field('post_author', $ticket_id) : 0,
                );
            }
        }

        if (!empty($new_admin_comments)) {
            foreach ($new_admin_comments as $comment) {

                echo "event: new_comment\n";
                echo "data: " . wp_json_encode($comment) . "\n\n";
                flush();
            }
            update_post_meta($ticket_id, 'rbd_all_seen', 'yes');
        } else {
            echo "event: disconnect\n";
            echo "data: " . wp_json_encode(array('reason' => 'timeout')) . "\n\n";
            flush();
        }
    }


    if ($ticket_status == 'closed') {
        // If the ticket is closed, send a disconnect event
        echo "event: ticket_closed\n";
        echo "data: " . wp_json_encode(array('reason' => 'ticket_closed')) . "\n\n";
        flush();
        exit;
    }


    if ($ticket_status == 'open') {

        echo "event: ticket_open\n";
        echo "data: " . wp_json_encode(array('reason' => 'ticket_open')) . "\n\n";
        flush();
    }

    echo "retry: 5000\n";
    flush();
    exit;
}


/**
 * Sets a fresh auth cookie for the user and returns a REST nonce that is
 * bound to the session token carried by that cookie. Nonces created while
 * the user has no cookie yet are bound to an empty session token and would
 * fail verification on the browser's next request.
 */
function robodesk_create_session_nonce($user_id)
{
    $expiration = time() + 30 * DAY_IN_SECONDS;
    $token = WP_Session_Tokens::get_instance($user_id)->create($expiration);

    wp_set_auth_cookie($user_id, true, '', $token);

    // Stage the logged_in cookie so wp_create_nonce() picks up the token
    // that the response cookie will carry.
    $_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);

    return wp_create_nonce('wp_rest');
}

function robodesk_secure_login_endpoint(WP_REST_Request $request)
{
    $login = $request->get_param('email');
    $password = $request->get_param('password');

    if (empty($login) || empty($password)) {
        robodesk_log_debug('Login failed: missing username/email or password.');
        return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
    }

    $ip = robodesk_get_client_ip();
    $rate_key = 'robodesk_login_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }

    $login = sanitize_text_field($login);
    $password = sanitize_text_field($password);

    if (is_email($login)) {
        $user = get_user_by('email', $login);
        if (!$user) {
            robodesk_log_debug('Login failed: no user found for email ' . $login);
            set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);
            return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
        }
        $login = $user->user_login;
    }

    $creds = array(
        'user_login' => $login,
        'user_password' => $password,
        'remember' => false,
    );

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        robodesk_log_debug('Login failed for login ' . $login . ': ' . $user->get_error_message());
        set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);
        if ($attempts + 1 >= 10) {
            return new WP_Error('rate_limited', 'Too many login attempts. Please try again later.', array('status' => 429));
        }
        return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
    }

    delete_transient($rate_key);

    wp_set_current_user($user->ID);

    $rest_nonce = robodesk_create_session_nonce($user->ID);

    $token = robodesk_generate_token($user);
    if (is_wp_error($token)) {
        return new WP_Error('jwt_error', 'Error generating token: ' . $token->get_error_message(), array('status' => 500));
    }

    return array(
        'success' => true,
        'token' => $token,
        'user_id' => $user->ID,
        'user_email' => $user->user_email,
        'user_display_name' => $user->display_name,
        'expires_at' => time() + (DAY_IN_SECONDS * 7),
        'rest_nonce' => $rest_nonce,
    );
}

function robodesk_validate_token(WP_REST_Request $request)
{
    $current_user = wp_get_current_user();
    if ($current_user instanceof WP_User && $current_user->ID > 0) {
        return true;
    }

    $auth_header = $request->get_header('authorization');
    if (!$auth_header) {
        return new WP_Error('no_token', 'Authorization header missing.', array('status' => 401));
    }

    $token = str_replace('Bearer ', '', trim($auth_header));
    $secret_key = robodesk_get_jwt_secret_key();

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

        if ($decoded->iss !== get_site_url()) {
            robodesk_log_debug('Token validation failed: invalid issuer.');
            return new WP_Error('invalid_token', 'Invalid token issuer.', array('status' => 401));
        }

        if ($decoded->exp < time()) {
            robodesk_log_debug('Token validation failed: token expired.');
            return new WP_Error('expired_token', 'Token has expired.', array('status' => 401));
        }

        $user = get_user_by('id', $decoded->data->user_id);
        if (!$user) {
            robodesk_log_debug('Token validation failed: user not found for ID ' . ($decoded->data->user_id ?? 'unknown'));
            return new WP_Error('invalid_user', 'User not found.', array('status' => 401));
        }

        wp_set_current_user($user->ID);
        return true;
    } catch (Exception $e) {
        robodesk_log_debug('Token validation exception: ' . $e->getMessage());
        return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage(), array('status' => 401));
    }
}

//Registers the new user
function robodesk_user_signup(WP_REST_Request $request)
{
    if (get_option('users_can_register') != '1') {
        return new WP_Error(
            'registration_disabled',
            __('Registration is currently disabled.', 'robodesk'),
            array('status' => 403)
        );
    }

    $ip = robodesk_get_client_ip();
    $blocked = get_option('robodesk_blocked_ips', array());
    if (in_array($ip, $blocked, true)) {
        return new WP_Error(
            'ip_blocked',
            __('Your IP has been permanently blocked due to repeated abuse.', 'robodesk'),
            array('status' => 403)
        );
    }

    $rate_key = 'robodesk_signup_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    $attempts++;
    set_transient($rate_key, $attempts, 2 * HOUR_IN_SECONDS);
    if ($attempts >= 5) {
        $strike_key = 'robodesk_signup_strike_' . md5($ip);
        $has_strike = get_transient($strike_key);
        if ($has_strike !== false) {
            $blocked[] = $ip;
            update_option('robodesk_blocked_ips', $blocked, false);
            return new WP_Error(
                'ip_blocked',
                __('Your IP has been permanently blocked due to repeated abuse.', 'robodesk'),
                array('status' => 403)
            );
        }
        set_transient($strike_key, 1, DAY_IN_SECONDS);
        return new WP_Error(
            'rate_limited',
            __('Too many signup attempts. Please try again later.', 'robodesk'),
            array('status' => 429)
        );
    }

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
        'user_login' => $email,
        'user_password' => $password,
        'remember' => true,
    );

    // Sign in the user
    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        robodesk_log_debug('Signup login failed for new user ' . $email . ': ' . $user->get_error_message());
        return new WP_Error('login_failed', 'Failed to sign in newly created user.', array('status' => 500));
    }

    wp_set_current_user($user->ID);

    $rest_nonce = robodesk_create_session_nonce($user->ID);

    // Send default WordPress user notification email
    wp_new_user_notification($user_id, null, 'both'); // Sends email to user and admin

    // Generate JWT token
    $token = robodesk_generate_token($user);

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
        'rest_nonce' => $rest_nonce,
    ), 201);
}
//build arrays of ticket data to return in rest response
function robodesk_build_ticket_response_data($ticket_id, $user_object, $reply_id = false)
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
        'ticket_author_image' => robodesk_get_comment_avatar($user_object->ID, 96),
        'ticket_time_ago' => $human_readable_time,
        'comment_id' => $reply_id,
    ];
}


// Rest Api tickets rest api callback functions
function robodesk_handle_tickets(WP_REST_Request $request)
{
    $payload = $request->get_body();
    $data = json_decode($payload, true);
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

    // Trusted integration calls (external chat service) prove authenticity
    // with an HMAC signature. Browser calls from the chat widget must come
    // from the authenticated session user and are scoped to their own data.
    $signed = robodesk_chat_request_is_signed($request, $payload);
    $actor_id = get_current_user_id();

    switch ($data['event']) {
        case 'ticket.created':
            if (!$signed) {
                $uid = sanitize_text_field($data['data']['uid'] ?? '');
                if ($actor_id <= 0 || (int) $uid !== $actor_id) {
                    return new WP_Error(
                        'forbidden',
                        'You are not allowed to create tickets for another user.',
                        array('status' => 403)
                    );
                }
            }
            return robodesk_handle_ticket_creation($data);

        case 'tickets.get':
            if (!$signed) {
                $uid = sanitize_text_field($data['data']['uid'] ?? '');
                $is_staff = $actor_id > 0 && (current_user_can('robodesk_access_dashboard') || current_user_can('manage_options'));
                if ($actor_id <= 0 || ((int) $uid !== $actor_id && !$is_staff)) {
                    return new WP_Error(
                        'forbidden',
                        'You are not allowed to view these tickets.',
                        array('status' => 403)
                    );
                }
            }
            return robodesk_handle_tickets_get($data);

        case 'replies.receive':
            if (!$signed) {
                $ticket_id = sanitize_text_field($data['data']['ticket_id'] ?? '');
                $user_id = sanitize_text_field($data['data']['user_id'] ?? '');
                if ($actor_id <= 0 || (int) $user_id !== $actor_id || !robodesk_chat_user_can_access_ticket($ticket_id, $actor_id)) {
                    return new WP_Error(
                        'forbidden',
                        'You are not allowed to reply to this ticket.',
                        array('status' => 403)
                    );
                }
            }
            return robodesk_handle_reply_receive($data);

        case 'comments.get':
            if (!$signed) {
                $ticket_id = sanitize_text_field($data['data']['ticket_id'] ?? '');
                if ($actor_id <= 0 || !robodesk_chat_user_can_access_ticket($ticket_id, $actor_id)) {
                    return new WP_Error(
                        'forbidden',
                        'You are not allowed to view these comments.',
                        array('status' => 403)
                    );
                }
            }
            return robodesk_handle_comments_get($data);

        default:
            return new WP_Error(
                'invalid_event',
                'Invalid event type',
                array('status' => 400)
            );
    }
}

function robodesk_chat_request_is_signed(WP_REST_Request $request, $payload)
{
    $signature = isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])) : '';
    if ($signature === '') {
        return false;
    }

    $secret = function_exists('robodesk_get_webhook_secret') ? robodesk_get_webhook_secret() : wp_hash('robodesk-webhook-secret');

    return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
}

function robodesk_chat_user_can_access_ticket($ticket_id, $actor_id)
{
    if ($actor_id <= 0) {
        return false;
    }

    $ticket = get_post((int) $ticket_id);
    if (!$ticket || $ticket->post_type !== 'robodesk_ticket') {
        return false;
    }

    if ((int) $ticket->post_author === $actor_id) {
        return true;
    }

    $customer_user_id = (int) robodesk_get_ticket_customer_user_id($ticket->ID);
    if ($customer_user_id > 0 && $customer_user_id === $actor_id) {
        return true;
    }

    $actor = get_userdata($actor_id);
    if ($actor instanceof WP_User && ($actor->has_cap('robodesk_access_dashboard') || $actor->has_cap('manage_options'))) {
        return true;
    }

    return false;
}

function robodesk_handle_selected_staff(WP_REST_Request $request)
{
    // Prefer the modern option key; fallback to legacy one for compatibility
    $selected_staff_id = get_option('robodesk_default_agent', '');
    if (empty($selected_staff_id)) {
        $selected_staff_id = get_option('robodesk_default_staff', '');
    }

    $staff_user = null;

    if (!empty($selected_staff_id)) {
        $staff_user = get_userdata($selected_staff_id);

        if (!$staff_user) {
            $eligible_staff = get_users(array(
                'role__in' => array('robodesk_support_staff', 'robodesk_support_manager', 'administrator', 'author', 'editor'),
                'fields' => array('ID', 'display_name', 'user_login')
            ));

            foreach ($eligible_staff as $staff) {
                $matches_configured_staff = ((string) $staff->ID === (string) $selected_staff_id) ||
                    ((string) $staff->display_name === (string) $selected_staff_id) ||
                    ((string) $staff->user_login === (string) $selected_staff_id);

                if ($matches_configured_staff) {
                    $staff_user = $staff;
                    break;
                }
            }
        }
    }

    // If the configured staff cannot be resolved, attempt to select the least-loaded support staff dynamically.
    if (!$staff_user) {
        $assigned_name = null;
        if (function_exists('auto_assign_ticket_to_support_staff')) {
            // This returns a display name for the chosen staff member
            $assigned_name = auto_assign_ticket_to_support_staff(false, false);
        }

        if (!empty($assigned_name)) {
            $found = get_user_by('display_name', $assigned_name);
            if (!$found) {
                $found = get_user_by('login', $assigned_name);
            }
            if ($found) {
                $staff_user = $found;
                $selected_staff_id = $found->ID;
            }
        }
    }

    if (!$staff_user) {
        $staff_user = get_userdata($selected_staff_id);
    }

    if (!$staff_user) {
        return new WP_REST_Response(array(
            'staff_display_name' => '',
            'staff_user_image' => '',
            'staff_user_email' => '',
            'staff_user_id' => 0,
        ), 200);
    }

    $viewer_is_staff = is_user_logged_in() && (current_user_can('robodesk_access_dashboard') || current_user_can('manage_options'));
    $staff_email = $staff_user->user_email;
    if (!$viewer_is_staff && is_email($staff_email)) {
        $staff_email = preg_replace('/(^.{1,2}).+(@.+)$/', '$1***$2', $staff_email);
    }

    return new WP_REST_Response(array(
        'staff_display_name' => $staff_user->display_name,
        'staff_user_image' => robodesk_get_comment_avatar($staff_user->ID, 96),
        'staff_user_email' => $staff_email,
        'staff_user_id' => $staff_user->ID,
    ), 200);
}

/**
 * Handle WhatsApp settings REST endpoint
 * Returns WhatsApp phone number if available
 */
function robodesk_handle_wa_settings(WP_REST_Request $request)
{
    $wa_settings = get_option('robodesk_whatsapp_credentials', []);
    return new WP_REST_Response([
        'phoneNumber' => $wa_settings['wa_number'] ?? '',
    ], 200);
}

// Handles ticket creation event need to remove this later
function robodesk_handle_ticket_creation($data)
{
    $title = sanitize_text_field($data['data']['title'] ?? '');
    $content = sanitize_textarea_field($data['data']['description'] ?? '');
    $author = sanitize_text_field($data['data']['uid'] ?? '');
    $timestamp = $data['data']['timestamp'] ?? null;

    $ticket = wp_insert_post(array(
        'post_type' => 'robodesk_ticket',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_author' => $author,
        'meta_input' => array(
            '_robodesk_ticket_status' => 'open',
            'robodesk_ticket_origin' => 'chat'
        ),
    ));

    if (is_wp_error($ticket)) {
        return $ticket;
    }

    // Check and create the priority term 'high' if it does not exist
    $term_name = 'High';
    $taxonomy = 'robodesk_ticket_priority';

    // Check if the term exists, and get or create it
    $term = term_exists($term_name, $taxonomy);

    rbd_log(sprintf(
        "Checking for term '%s' in taxonomy '%s': %s\n",
        $term_name,
        $taxonomy,
        $term ? 'exists' : 'does not exist'
    ));
    if (!$term) {
        $term = wp_insert_term($term_name, $taxonomy);
        rbd_log(sprintf(
            "Created new term '%s' in taxonomy '%s' with ID %d\n",
            $term_name,
            $taxonomy,
            $term['term_id']
        ));
    }

    // Get the term ID safely
    $term_id = is_array($term) ? $term['term_id'] : $term;

    // Now assign the term to the post using its ID
    wp_set_object_terms($ticket, (int) $term_id, $taxonomy);

    // Get user data once and cache it
    $user_object = robodesk_get_cached_user_data($author);
    if (!$user_object) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $user_name = $user_object->display_name;
    $email_address = $user_object->user_email;

    if ($email_address) {
        update_post_meta($ticket, '_robodesk_customer_email', $email_address);
    }

    // Add initial comment
    $comment_data = array(
        'comment_post_ID' => $ticket,
        'user_id' => $author,
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
        $author_type = robodesk_get_author_type($author);
        add_comment_meta($comment_id, 'comment_source', 'chat', true);
        add_comment_meta($comment_id, 'author_type', $author_type, true);
        add_comment_meta($comment_id, 'is_bot_response', '0', true);
        add_comment_meta($comment_id, 'admin_comment', $author_type === 'agent' ? 'yes' : 'no', true);
        add_comment_meta($comment_id, 'rbd_suppress_notification', 1, true);
    }

    $reply_id = !is_wp_error($comment_id) ? $comment_id : false;

    $response_data = robodesk_build_ticket_response_data($ticket, $user_object, $reply_id);

    // Auto-assign if enabled in Advanced Settings
    $settings = get_option('robodesk_advanced_settings', []);
    if (!empty($settings['enable_auto_assign'])) {
        $assigned = auto_assign_ticket_to_support_staff();
        update_post_meta($ticket, 'ticket_assignee', $assigned);
        robodesk_store_ticket_assignee_id($ticket, $assigned);
    }

    robodesk_notify_ticket_created($ticket, 'chat');

    do_action('robodesk_after_ticket_created', $ticket, $data, $author);

    return rest_ensure_response(array(
        'status' => 'success',
        'message' => 'Ticket created successfully',
        'data' => $response_data
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
    $tickets_data = robodesk_get_tickets_batch_data($ticket_ids, $user_id);

    return rest_ensure_response([
        'status' => 'success',
        'message' => 'Tickets fetched successfully',
        'data' => $tickets_data
    ]);
}


//get all tickets data by using ticket id and user id 
function robodesk_get_tickets_batch_data($ticket_ids, $user_id)
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
    $user_object = robodesk_get_cached_user_data($user_id);
    $user_name = $user_object ? $user_object->display_name : 'Unknown';
    $avatar_url = robodesk_get_comment_avatar($user_id, 32);

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
    $ticket_id = sanitize_text_field($data['data']['ticket_id'] ?? '');
    $user_id = sanitize_text_field($data['data']['user_id'] ?? '');
    $reply = sanitize_textarea_field($data['data']['message'] ?? '');
    $is_bot_response = isset($data['data']['is_bot_response']) ? (bool) $data['data']['is_bot_response'] : false;

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

    $user_object = robodesk_get_cached_user_data($user_id);
    if (!$user_object) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $base64_image = $data['data']['image'] ?? '';
    $attachment_url = '';
    if ($base64_image && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $base64_image, $matches)) {
        $image_type = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $image_data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_data = base64_decode($image_data, true);
        if ($image_data !== false && @getimagesizefromstring($image_data) !== false) {
            $upload_dir = wp_upload_dir();
            $filename = 'ticket-' . $ticket_id . '-' . time() . '.' . $image_type;
            $file_path = $upload_dir['path'] . '/' . $filename;
            if (file_put_contents($file_path, $image_data) !== false) {
                $filetype = wp_check_filetype($filename, null);
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit',
                );
                $attach_id = wp_insert_attachment($attachment, $file_path, $ticket_id);
                if (!is_wp_error($attach_id)) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    $attachment_url = wp_get_attachment_url($attach_id);
                    $image_html = '<a href="' . esc_url($attachment_url) . '" target="_blank"><img src="' . esc_url($attachment_url) . '" alt="" class="rd-comment-img" /></a>';
                    $reply .= '<br>' . $image_html;
                }
            }
        }
    }

    $comment_data = array(
        'comment_post_ID' => $ticket_id,
        'comment_author' => $user_object->display_name,
        'comment_author_email' => $user_object->user_email,
        'user_id' => $user_id,
        'comment_content' => $reply,
        'comment_approved' => 1,
        'comment_type' => 'ticket_replies'
    );

    $timestamp = $data['data']['timestamp'] ?? null;
    if ($timestamp) {
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $mysql_date = date('Y-m-d H:i:s', strtotime($timestamp));
        $comment_data['comment_date'] = $mysql_date;
        $comment_data['comment_date_gmt'] = get_gmt_from_date($mysql_date);
    }

    // Add reply to ticket
    $comment_id = wp_insert_comment($comment_data);

    if (!$comment_id) {
        return new WP_Error('comment_failed', 'Failed to add reply', array('status' => 500));
    }

    $author_type = robodesk_get_author_type($user_id);
    add_comment_meta($comment_id, 'comment_source', 'chat', true);
    add_comment_meta($comment_id, 'author_type', $author_type, true);
    add_comment_meta($comment_id, 'is_bot_response', $is_bot_response ? '1' : '0', true);
    add_comment_meta($comment_id, 'admin_comment', $author_type === 'agent' ? 'yes' : 'no', true);

    // Trigger action for integrations (Slack, Discord, etc.)
    do_action('robodesk_after_reply_added', $ticket_id, $comment_id, $reply, $user_object);



    $comment = get_comment($comment_id);
    $response_data = [
        'id' => $comment->comment_ID,
        'author' => function_exists('robodesk_get_ticket_comment_author_name') ? robodesk_get_ticket_comment_author_name($comment) : $comment->comment_author,
        'author_id' => $comment->user_id,
        'author_image' => function_exists('robodesk_get_ticket_comment_avatar_url') ? robodesk_get_ticket_comment_avatar_url($comment, 96) : robodesk_get_comment_avatar($comment->user_id, 96),
        'content' => $comment->comment_content,
        'created_at' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
        'is_bot_response' => $is_bot_response,
        'attachment_url' => $attachment_url,
    ];

    rbd_log(sprintf(
        "Comment received for ticket #%s: %s\n",
        $ticket_id,
        $reply
    ));



    return rest_ensure_response(array(
        'status' => 'success',
        'message' => 'Reply added successfully',
        'data' => $response_data
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
            'author' => function_exists('robodesk_get_ticket_comment_author_name') ? robodesk_get_ticket_comment_author_name($comment) : $comment->comment_author,
            'author_id' => $comment->user_id,
            'author_image' => function_exists('robodesk_get_ticket_comment_avatar_url') ? robodesk_get_ticket_comment_avatar_url($comment, 96) : robodesk_get_comment_avatar($comment->user_id, 96),
            'content' => $comment->comment_content,
            'created_at' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
            'is_bot_response' => function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false,
        ];
    }, $comments);

    return rest_ensure_response([
        'status' => 'success',
        'message' => 'Comments found',
        'data' => $formatted_comments,
    ]);
}

function robodesk_get_cached_user_data($user_id)
{
    static $user_cache = [];

    if (!isset($user_cache[$user_id])) {
        $user_cache[$user_id] = get_user_by('id', $user_id);
    }

    return $user_cache[$user_id];
}


add_action('rest_api_init', function () {
    register_rest_route('robodesk/v1', '/lost-password', array(
        'methods' => 'POST',
        'callback' => 'robodesk_custom_lost_password',
        'permission_callback' => '__return_true', // Public lost-password endpoint
    ));
});

function robodesk_custom_lost_password(WP_REST_Request $request)
{
    $ip = robodesk_get_client_ip();
    $rate_key = 'robodesk_lost_password_rate_' . md5($ip);
    $attempts = get_transient($rate_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    if ($attempts >= 5) {
        return new WP_Error(
            'rate_limited',
            __('Too many password reset requests. Please try again later.', 'robodesk'),
            array('status' => 429)
        );
    }
    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    $user_login = $request->get_param('user_login');
    if (!is_string($user_login)) {
        $user_login = '';
    }
    $user_login = trim(wp_unslash($user_login));
    if (empty($user_login)) {
        return new WP_Error('invalid_input', 'Username or email is required.', array('status' => 400));
    }
    $user_login = sanitize_user($user_login);

    $cooldown_key = 'robodesk_rest_password_reset_' . md5($user_login);
    if (get_transient($cooldown_key)) {
        return new WP_Error(
            'rate_limited',
            __('A password reset link was already sent recently. Please check your email.', 'robodesk'),
            array('status' => 429)
        );
    }
    set_transient($cooldown_key, true, 10 * MINUTE_IN_SECONDS);

    $result = retrieve_password($user_login);

    if (is_wp_error($result)) {
        rbd_log('Lost password request failed for: ' . $user_login . ' — ' . $result->get_error_message());
    }

    return new WP_REST_Response(array(
        'message' => __('If the username or email exists, a password reset link has been sent.', 'robodesk')
    ), 200);
}


add_action('rest_api_init', function () {
    register_rest_route('robodesk/v1', '/send-chat-transcript', array(
        'methods' => 'POST',
        'callback' => 'robodesk_send_chat_transcript',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function robodesk_send_chat_transcript(WP_REST_Request $request)
{
    $ticket_id = intval($request->get_param('ticket_id'));
    if (!$ticket_id) {
        return new WP_Error('invalid_ticket', 'Ticket ID is required.', array('status' => 400));
    }

    $ticket = get_post($ticket_id);
    if (!$ticket || $ticket->post_type !== 'robodesk_ticket') {
        return new WP_Error('not_found', 'Ticket not found.', array('status' => 404));
    }

    $current_user_id = get_current_user_id();
    $ticket_author_id = intval($ticket->post_author);

    if ($current_user_id !== $ticket_author_id && !current_user_can('manage_options')) {
        return new WP_Error('forbidden', 'You do not own this ticket.', array('status' => 403));
    }

    $author = get_userdata($ticket_author_id);
    if (!$author || !$author->user_email) {
        return new WP_Error('no_email', 'Customer email not found.', array('status' => 400));
    }

    $to = $author->user_email;
    // translators: %s is the ticket/post title
    $subject = sprintf(__('Chat Transcript: %s', 'robodesk'), $ticket->post_title);

    $comments = get_comments(array(
        'post_id' => $ticket_id,
        'comment_type' => 'ticket_replies',
        'order' => 'ASC',
        'orderby' => 'comment_date_gmt',
        'status' => 'approve',
    ));

    $messages_html = '';

    if (empty($comments)) {
        $messages_html = '<tr><td style="padding:20px;text-align:center;color:#666;font-size:14px;">' . __('No messages in this conversation.', 'robodesk') . '</td></tr>';
    } else {
        $prev_sender_type = null;
        $prev_author_id = null;
        $prev_is_bot = null;

        foreach ($comments as $comment) {
            $is_bot = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;
            $is_customer = !$is_bot && intval($comment->user_id) === $ticket_author_id;

            $sender_name = function_exists('robodesk_get_ticket_comment_author_name')
                ? robodesk_get_ticket_comment_author_name($comment)
                : ($is_customer ? $author->display_name : $comment->comment_author);
            $sender_type = $is_customer ? 'customer' : ($is_bot ? 'bot' : 'agent');

            $is_same_sender = $prev_sender_type === $sender_type && $prev_author_id === intval($comment->user_id) && $prev_is_bot === $is_bot;

            $avatar_url = '';
            if (function_exists('robodesk_get_ticket_comment_avatar_url')) {
                $avatar_url = robodesk_get_ticket_comment_avatar_url($comment, 32);
            }
            if (!$avatar_url) {
                $avatar_email = $comment->comment_author_email ?: '';
                $avatar_url = $avatar_email ? get_avatar_url($avatar_email, array('size' => 32)) : '';
            }
            if (!$avatar_url) {
                $avatar_url = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&s=32';
            }

            $message_text = wp_kses_post($comment->comment_content);
            $time = get_comment_time(get_option('time_format'), true, $comment);
            $date = get_comment_date(get_option('date_format'), $comment);

            $bubble_align = $is_customer ? 'right' : 'left';
            $bubble_color = $is_customer ? '#007BFF' : '#FFFFFF';
            $text_color = $is_customer ? '#FFFFFF' : '#000000';
            $border = $is_customer ? 'none' : '1px solid #E0E0E0';

            $margin_top = ($is_same_sender || $prev_sender_type === null) ? '' : 'margin-top:12px;';

            if ($is_customer) {
                $messages_html .= '
            <tr>
                <td style="padding:4px 0;text-align:right;padding-right:12px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" style="display:inline-block;max-width:80%">
                        ' . (!$is_same_sender ? '<tr><td style="padding:0 0 2px 0;font-size:11px;color:#999;text-align:right">' . $sender_name . '</td></tr>' : '') . '
                        <tr>
                            <td style="background-color:' . $bubble_color . ';color:' . $text_color . ';border:' . $border . ';border-radius:10px;padding:8px 12px;font-size:14px;line-height:1.4;word-wrap:break-word">
                                ' . $message_text . '
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:2px 0 0 0;font-size:10px;color:#999;text-align:right">' . $date . ' ' . $time . '</td>
                        </tr>
                    </table>
                </td>
            </tr>';
            } else {
                $avatar_cell = $is_same_sender
                    ? '<td width="38" style="padding-right:8px;"></td>'
                    : '<td width="38" valign="top" style="padding-right:8px;"><img src="' . $avatar_url . '" alt="" width="30" height="30" style="border-radius:50%;display:block;width:30px;height:30px;" /></td>';
                $name_row = $is_same_sender
                    ? ''
                    : '<tr><td style="padding:0 0 2px 0;font-size:11px;color:#999;text-align:left;">' . $sender_name . '</td></tr>';

                $messages_html .= '
            <tr>
                <td style="padding:4px 0;text-align:left;">
                    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">
                        <tr>
                            ' . $avatar_cell . '
                            <td valign="top" style="max-width:80%;">
                                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">
                                    ' . $name_row . '
                                    <tr>
                                        <td style="background-color:' . $bubble_color . ';color:' . $text_color . ';border:' . $border . ';border-radius:10px;padding:8px 12px;font-size:14px;line-height:1.4;">
                                            ' . $message_text . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:2px 0 0 0;font-size:10px;color:#999;text-align:left;">' . $date . ' ' . $time . '</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>';
            }

            $prev_sender_type = $sender_type;
            $prev_author_id = intval($comment->user_id);
            $prev_is_bot = $is_bot;
        }
    }

    $chat_html = '
    <div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
        <div style="text-align:center;padding:20px 0 10px;border-bottom:1px solid #eee;margin-bottom:20px;">
            <h2 style="margin:0;font-size:18px;color:#333;">' . esc_html__('Chat Transcript', 'robodesk') . '</h2>
            <p style="margin:4px 0 0;font-size:13px;color:#666;">' . esc_html($ticket->post_title) . '</p>
        </div>
        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">
            ' . $messages_html . '
        </table>
    </div>';

    $site_name = get_bloginfo('name');
    $site_url = get_home_url();
    $year = date_i18n('Y');
    $theme_color = function_exists('robodesk_get_email_theme_color') ? robodesk_get_email_theme_color() : '#007BFF';
    $logo_url = function_exists('robodesk_get_email_logo_url') ? esc_url(robodesk_get_email_logo_url()) : '';

    if ($logo_url) {
        $brand_html = '<a href="' . esc_url($site_url) . '" target="_blank" rel="noopener noreferrer"><img src="' . $logo_url . '" alt="' . esc_attr($site_name) . '" style="max-height:50px;" /></a>';
    } else {
        $brand_html = '<a href="' . esc_url($site_url) . '" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#ffffff;font-size:20px;font-weight:600;">' . esc_html($site_name) . '</a>';
    }

    $full_html = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f4f4f4;">
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background-color:#f4f4f4;padding:20px 0;">
        <tr><td style="text-align:center;">
            <table role="presentation" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:8px;overflow:hidden;">
                <tr><td style="background-color:' . esc_attr($theme_color) . ';padding:20px;text-align:center;">
                    ' . $brand_html . '
                </td></tr>
                <tr><td style="padding:0;">
                    ' . $chat_html . '
                </td></tr>
                <tr><td style="background-color:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999;">
                        <a href="' . esc_url($site_url) . '" style="color:#999;">' . esc_html($site_name) . '</a><br>
                        &copy; ' . esc_html($year) . ' ' . esc_html($site_name) . '
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>';

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $reply_to = function_exists('robodesk_get_inbox_email_for_ticket') ? robodesk_get_inbox_email_for_ticket($ticket_id) : '';
    if ($reply_to) {
        $headers[] = 'Reply-To: ' . $reply_to;
    }

    $email_sent = wp_mail($to, $subject, $full_html, $headers);

    if ($email_sent) {
        return new WP_REST_Response(array('success' => true, 'message' => __('Chat transcript sent to your email.', 'robodesk')), 200);
    }

    return new WP_Error('send_failed', __('Failed to send email. Please try again.', 'robodesk'), array('status' => 500));
}

add_filter('retrieve_password_message', 'robodesk_add_param_to_reset_link', 10, 4);
function robodesk_add_param_to_reset_link($message, $key, $user_login, $user_data)
{
    if (get_transient('robodesk_rest_password_reset_' . md5($user_login))) {

        $custom_reset_url = add_query_arg(
            array(
                'key' => $key,
                'user' => rawurlencode($user_login),
                'rbd_popup' => 'true',
                'initator' => 'robodesk',
            ),
            network_site_url()
        );


        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        /* translators: %s: site name */
        $message = sprintf(__('Someone has requested a password reset for the following account on %s:', 'robodesk'), $site_name) . "\r\n\r\n";
        /* translators: %s: username */
        $message .= sprintf(__('Username: %s', 'robodesk'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'robodesk') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'robodesk') . "\r\n\r\n";
        $message .= $custom_reset_url . "\r\n\r\n";
    }

    return $message;
}
