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
        'permission_callback' => function () {
            return is_user_logged_in() && current_user_can('robodesk_access_dashboard');
        },
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

    register_rest_route('robodesk/v1', '/notification-stream', [
        'methods' => 'GET',
        'callback' => 'robodesk_notification_stream',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('robodesk/v1', '/ticket-stream/(?P<ticket_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'robodesk_ticket_stream',
        'permission_callback' => function () {
            return current_user_can('robodesk_access_dashboard');
        },
        'args' => [
            'ticket_id' => [
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
            ],
        ],
    ]);
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
    header('Access-Control-Allow-Origin: ' . esc_url(home_url()));
    header('Access-Control-Allow-Headers: Cache-Control');
    header('X-Accel-Buffering: no');

    // Disable output buffering and compression
    if (ob_get_level()) ob_end_clean();

    // Disable WordPress output compression
    if (function_exists('ini_set')) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('zlib.output_compression', 0);
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('output_buffering', 0);
    }

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
            $type = 'customer';
            $role = 'Customer';
            $is_bot_response = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;

            $saved_author_type = get_comment_meta($comment->comment_ID, 'author_type', true);

            if ($is_bot_response) {
                $type = 'agent';
                $role = 'AI BOT';
            } elseif ($saved_author_type === 'agent') {
                $type = 'agent';
                $user = $comment->user_id ? get_userdata($comment->user_id) : false;
                if ($user && !empty($user->roles)) {
                    $roles = $user->roles;
                    if (in_array('administrator', $roles)) {
                        $role = 'Administrator';
                    } elseif (in_array('robodesk_support_manager', $roles)) {
                        $role = 'Support Manager';
                    } elseif (in_array('robodesk_support_staff', $roles)) {
                        $role = 'Support Staff';
                    } elseif (in_array('robodesk_agent', $roles)) {
                        $role = 'Agent';
                    } else {
                        $role = 'Support Staff';
                    }
                } else {
                    $role = 'Support Staff';
                }
            } elseif ($saved_author_type !== 'customer') {
                // Legacy fallback: no meta — use WP role
                $user = $comment->user_id ? get_userdata($comment->user_id) : false;
                if ($user) {
                    $roles = $user->roles;
                    if (in_array('administrator', $roles) || in_array('robodesk_agent', $roles)) {
                        $type = 'agent';
                        $role = 'Agent';
                    }
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
                'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                'type'      => $type,
                'role'      => $role,
                'is_bot_response' => $is_bot_response,
                'comment_meta' => $comment_meta,
            ];
        }

        if (!empty($new_admin_comments)) {
            foreach ($new_admin_comments as $comment) {
                echo "event: new_admin_comment\n";
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


    echo "retry: 5000\n";
    flush();
    exit;
}





function robodesk_ticket_stream($request)
{
    $ticket_id = (int) $request->get_param('ticket_id');

    if (get_post_type($ticket_id) !== 'robodesk_ticket') {
        return new WP_Error('invalid_ticket', 'Invalid ticket', ['status' => 404]);
    }

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);

    set_time_limit(0);
    ignore_user_abort(false);

    $imap_settings = get_option('robodesk_imap_settings', []);
    $support_inbox = strtolower(trim($imap_settings['username'] ?? ''));

    $last_reply_id = 0;
    $last_note_id  = 0;
    $last_status   = '';
    $check_count   = 0;

    // ── Helper to build replies ──
    $build_replies = function () use ($ticket_id, $support_inbox) {
        $comments = get_comments([
            'post_id'           => $ticket_id,
            'order'             => 'ASC',
            'type'              => 'ticket_replies',
            'is_robodesk_reply' => true,
        ]);
        $replies = [];
        foreach ($comments as $comment) {
            $is_bot_response = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;
            $saved_author_type = get_comment_meta($comment->comment_ID, 'author_type', true);
            $type = 'customer';
            $role = 'Customer';

            if ($is_bot_response) {
                $type = 'agent';
                $role = 'AI BOT';
            } elseif ($saved_author_type === 'agent') {
                $type = 'agent';
                $user = $comment->user_id ? get_userdata($comment->user_id) : false;
                if ($user) {
                    $roles = $user->roles;
                    if (in_array('administrator', $roles)) {
                        $role = 'Administrator';
                    } elseif (in_array('robodesk_support_manager', $roles)) {
                        $role = 'Support Manager';
                    } elseif (in_array('robodesk_support_staff', $roles)) {
                        $role = 'Support Staff';
                    } elseif (in_array('robodesk_agent', $roles)) {
                        $role = 'Agent';
                    } elseif (in_array('editor', $roles)) {
                        $role = 'Editor';
                    } else {
                        $role = 'Support Staff';
                    }
                } else {
                    $comment_email = strtolower(trim($comment->comment_author_email));
                    $role = ($comment_email && $comment_email === $support_inbox) ? 'Support' : 'Support Staff';
                }
            } elseif ($saved_author_type === 'customer') {
                $type = 'customer';
                $role = 'Customer';
            } else {
                $user = $comment->user_id ? get_userdata($comment->user_id) : false;
                if ($user) {
                    $roles = $user->roles;
                    if (in_array('administrator', $roles)) {
                        $type = 'agent';
                        $role = 'Administrator';
                    } elseif (in_array('robodesk_support_manager', $roles)) {
                        $type = 'agent';
                        $role = 'Support Manager';
                    } elseif (in_array('robodesk_support_staff', $roles)) {
                        $type = 'agent';
                        $role = 'Support Staff';
                    } elseif (in_array('robodesk_agent', $roles)) {
                        $type = 'agent';
                        $role = 'Agent';
                    } elseif (in_array('editor', $roles)) {
                        $type = 'agent';
                        $role = 'Editor';
                    }
                }
            }

            if ($type === 'customer' && $support_inbox !== '') {
                $comment_email = strtolower(trim($comment->comment_author_email));
                if ($comment_email === $support_inbox) {
                    $type = 'agent';
                    $role = 'Support';
                }
            }

            $is_action = get_comment_meta($comment->comment_ID, 'is_action', true);
            $replies[] = [
                'id'              => $comment->comment_ID,
                'author'          => $is_bot_response && function_exists('robodesk_get_bot_display_name') ? robodesk_get_bot_display_name() : $comment->comment_author,
                'message'         => $comment->comment_content,
                'timestamp'       => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                'type'            => $type,
                'role'            => $role,
                'is_bot_response' => $is_bot_response,
                'comment_meta'    => ['is_action' => $is_action ? true : false, 'is_bot_response' => $is_bot_response],
            ];
        }
        return $replies;
    };

    // ── Helper to build notes ──
    $build_notes = function () use ($ticket_id) {
        $comments = get_comments([
            'post_id'          => $ticket_id,
            'order'            => 'ASC',
            'type'             => 'ticket_notes',
            'is_robodesk_note' => true,
        ]);
        $notes = [];
        foreach ($comments as $comment) {
            $user = get_userdata($comment->user_id);
            $type = 'agent';
            $role = 'Agent';
            if ($user) {
                $roles = $user->roles;
                if (in_array('administrator', $roles)) {
                    $role = 'Administrator';
                } elseif (in_array('robodesk_support_manager', $roles)) {
                    $role = 'Support Manager';
                } elseif (in_array('robodesk_support_staff', $roles)) {
                    $role = 'Support Staff';
                } elseif (in_array('robodesk_agent', $roles)) {
                    $role = 'Agent';
                } elseif (in_array('editor', $roles)) {
                    $role = 'Editor';
                } else {
                    $type = 'customer';
                    $role = 'Customer';
                }
            }
            $notes[] = [
                'id'        => $comment->comment_ID,
                'author'    => $comment->comment_author,
                'message'   => $comment->comment_content,
                'timestamp' => function_exists('robodesk_comment_utc_iso') ? robodesk_comment_utc_iso($comment) : $comment->comment_date,
                'type'      => $type,
                'role'      => $role,
            ];
        }
        return $notes;
    };

    // ── Send data event ──
    $send_data = function () use ($build_replies, $build_notes, $ticket_id) {
        $replies    = $build_replies();
        $notes      = $build_notes();
        $status     = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'open';
        echo "event: sync\n";
        echo "data: " . wp_json_encode([
            'replies'       => $replies,
            'replies_count' => count($replies),
            'notes'         => $notes,
            'notes_count'   => count($notes),
            'status'        => $status,
        ]) . "\n\n";
        flush();
        return [
            'last_reply_id' => !empty($replies) ? max(array_column($replies, 'id')) : 0,
            'last_note_id'  => !empty($notes) ? max(array_column($notes, 'id')) : 0,
            'last_status'   => $status,
        ];
    };

    // ── Send initial ──
    $state = $send_data();
    $last_reply_id = $state['last_reply_id'];
    $last_note_id  = $state['last_note_id'];
    $last_status   = $state['last_status'];

    // ── Short-lived SSE ──
    // Run once and exit. Browser EventSource reconnects via retry directive,
    // avoiding long-running PHP-FPM workers that exhaust the pool.
    usleep(1000000);
    $check_count++;

    wp_cache_delete('last_changed', 'comment_queries');
    clean_post_cache($ticket_id);

    $has_new = false;

    $latest_reply = get_comments([
        'post_id' => $ticket_id,
        'number'  => 1,
        'orderby' => 'comment_ID',
        'order'   => 'DESC',
        'type'    => 'ticket_replies',
        'fields'  => 'ids',
    ]);
    $current_reply_id = !empty($latest_reply) ? (int) $latest_reply[0] : 0;
    if ($current_reply_id > $last_reply_id) {
        $has_new = true;
    }

    $latest_note = get_comments([
        'post_id' => $ticket_id,
        'number'  => 1,
        'orderby' => 'comment_ID',
        'order'   => 'DESC',
        'type'    => 'ticket_notes',
        'fields'  => 'ids',
    ]);
    $current_note_id = !empty($latest_note) ? (int) $latest_note[0] : 0;
    if ($current_note_id > $last_note_id) {
        $has_new = true;
    }

    $current_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'open';
    if ($current_status !== $last_status) {
        $has_new = true;
    }

    if ($has_new) {
        $state = $send_data();
    } else {
        echo ": keepalive\n\n";
        flush();
    }

    exit;
}

function robodesk_notification_stream()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Not logged in', ['status' => 401]);
    }

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);

    set_time_limit(0);
    ignore_user_abort(false);

    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';
    $last_count = -1;

    $send = function () use ($wpdb, $table_name, $user_id, &$last_count) {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, ticket_id, comment_id, type, source, message, created_at
                 FROM {$table_name}
                 WHERE recipient_user_id = %d AND read_at IS NULL
                 ORDER BY created_at DESC
                 LIMIT 50",
                $user_id
            ),
            ARRAY_A
        );

        $items = [];
        foreach ($rows as $row) {
            $ticket_id = (int) $row['ticket_id'];
            if ($ticket_id > 0) {
                $current_assignee_id = (int) get_post_meta($ticket_id, 'ticket_assignee_id', true);
                if ($current_assignee_id > 0 && $current_assignee_id !== $user_id) {
                    $wpdb->delete($table_name, ['id' => (int) $row['id'], 'recipient_user_id' => $user_id], ['%d', '%d']);
                    continue;
                }
            }
            $items[] = [
                'id'        => (int) $row['id'],
                'ticketId'  => $ticket_id,
                'type'      => $row['type'],
                'source'    => $row['source'],
                'message'   => $row['message'],
                'title'     => $row['type'] === 'ticket_created' ? 'New Conversation #' . $ticket_id : 'New Reply on #' . $ticket_id,
                'assignee'  => $ticket_id > 0 ? (get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'Unassigned') : '',
                'timestamp' => function_exists('robodesk_datetime_to_utc_iso') ? robodesk_datetime_to_utc_iso($row['created_at']) : $row['created_at'],
            ];
        }

        $count = count($items);
        if ($count !== $last_count) {
            $last_count = $count;
            echo "event: notifications\n";
            echo "data: " . wp_json_encode(['items' => $items, 'count' => $count]) . "\n\n";
            flush();
        }
    };

    $send();

    // Short-lived: run once and exit. Browser EventSource reconnects.
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
