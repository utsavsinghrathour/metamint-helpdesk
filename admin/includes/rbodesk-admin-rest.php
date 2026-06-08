<?php
// phpcs:disable WordPress.DB.SlowDBQuery


// http: //localhost:10135/wp-json/robodesk/v1/adminstream/2166/2704/142
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('rest_api_init',  function () {
    register_rest_route('robodesk/v1', '/adminstream/(?P<post_id>\d+)(?:/(?P<last_comment_id>\d+))?(?:/(?P<user_id>\d+))?', array(
        'methods' => 'GET',
        'callback' => 'robodesk_live_admin_comments',
        'permission_callback' => '__return_true',
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
});



function robodesk_live_admin_comments($request)
{
    $ticket_id = $request->get_param('post_id');
    $last_comment_client = (int) $request->get_param('last_comment_id'); //from frontend 
    $loggedin_user_id = $request->get_param('user_id');
    if (get_post_type($ticket_id) !== 'robodesk_ticket') return;

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    header('X-Accel-Buffering: no');

    // Disable output buffering and compression
    if (ob_get_level()) ob_end_clean();

    // Disable WordPress output compression
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    ini_set('zlib.output_compression', 0);
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    ini_set('output_buffering', 0);

    // Set execution limits
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    set_time_limit(0);
    ignore_user_abort(false);


    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    $last_comment_server = get_comments([
        'post_id'      => $ticket_id,
        'number'       => 1,
        'orderby'      => 'comment_date',
        'order'        => 'DESC',
        'status'       => 'approve',
        'fields'       => 'ids',
        'type'         => 'ticket_replies',
        'meta_query'   => [
            [
                'key'     => 'admin_comment',
                'value'   => 'no',
                'compare' => '='
            ]
        ]

    ]);


    //the actual last comment from the server
    //last_comment_client is the variable received from client
    $last_comment_server = !empty($last_comment_server) ? (int) $last_comment_server[0] : false;


    //checking the admin id 
    if ($last_comment_server && $last_comment_server > $last_comment_client) {
        $args = array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'comment__in' => range($last_comment_client + 1, $last_comment_server),
            'orderby' => 'comment_date',
            'order' => 'ASC',
            'type' => 'ticket_replies',

            'meta_query'   => [
                [
                    'key'     => 'admin_comment',
                    'value'   => 'no',
                    'compare' => '='
                ]
            ]
        );


        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $comments = get_comments($args);
        $new_admin_comments = array();

        foreach ($comments as $comment) {
            // Determine user type
            $user = get_userdata($comment->user_id);
            $type = 'customer';
            $role = 'Customer';
            $is_bot_response = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;

            if ($is_bot_response) {
                $type = 'agent';
                $role = 'AI BOT';
            } elseif ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles) || in_array('robodesk_agent', $roles)) {
                    $type = 'agent';
                    $role = 'Agent';
                }
            }

            // Get comment meta
            $is_action = get_comment_meta($comment->comment_ID, 'is_action', true);
            $comment_meta = [
                'is_action' => $is_action ? true : false,
                'is_bot_response' => $is_bot_response,
            ];

            $new_admin_comments[] = [
                'id'        => $comment->comment_ID,
                'author'    => $is_bot_response && function_exists('robodesk_get_bot_display_name') ? robodesk_get_bot_display_name() : $comment->comment_author,
                'message'   => $comment->comment_content,
                'timestamp' => $comment->comment_date,
                'type'      => $type,
                'role'      => $role,
                'is_bot_response' => $is_bot_response,
                'comment_meta' => $comment_meta,
            ];
        }

        if (!empty($new_admin_comments)) {
            foreach ($new_admin_comments as $comment) {
                echo "event: new_admin_comment\n";
                echo "data: " . json_encode($comment) . "\n\n";
                flush();
            }
            update_post_meta($ticket_id, 'rbd_all_seen', 'yes');
        } else {
            echo "event: disconnect\n";
            echo "data: " . json_encode(array('reason' => 'timeout')) . "\n\n";
            flush();
        }
    }


    echo "retry: 5000\n";
    flush();
    exit;
}





// // Get ticket replies (comments)
// $comments = get_comments([
//     'post_id' => $ticket_id,
//     'order'   => 'ASC',
//     'type'    => 'ticket_replies',
//     'is_robodesk_reply' => true,
// ]);

// $replies = [];
// foreach ($comments as $comment) {
//     // Determine user type
//     $user = get_userdata($comment->user_id);
//     $type = 'customer';

//     if ($user) {
//         $roles = $user->roles;
//         if (in_array('administrator', $roles) || in_array('robodesk_agent', $roles)) {
//             $type = 'agent';
//         }
//     }

//     // Get comment meta
//     $is_action = get_comment_meta($comment->comment_ID, 'is_action', true);
//     $comment_meta = [
//         'is_action' => $is_action ? true : false, // Ensure boolean value
//     ];

//     $replies[] = [
//         'id'        => $comment->comment_ID,
//         'author'    => $comment->comment_author,
//         'message'   => $comment->comment_content,
//         'timestamp' => $comment->comment_date,
//         'type'      => $type,
//         'comment_meta' => $comment_meta, // Include comment_meta object
//     ];
// }
