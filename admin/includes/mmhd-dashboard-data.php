<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Fetch lightweight ticket records (id, author, title, modified, status,
 * assignee, assignee_id, source) for every published ticket using bulk
 * queries. Shared by the dashboard payload and the counters function so
 * both scale to thousands of tickets without an N+1 meta query.
 *
 * @return array
 */
function mmhd_get_light_tickets()
{
    global $wpdb;

    // Fetch only ID columns for every ticket (cheap even with many tickets).
    // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $ticket_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_author, post_title, post_modified
             FROM {$wpdb->posts}
             WHERE post_type = %s AND post_status = %s
             ORDER BY post_modified DESC",
            'mmhd_ticket',
            'publish'
        )
    );

    if (empty($ticket_rows)) {
        return [];
    }

    $ids = array_map('intval', wp_list_pluck($ticket_rows, 'ID'));
    $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));

    // Bulk-fetch all relevant meta for every ticket in a single query.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
    $meta_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
             WHERE post_id IN ({$id_placeholders})
             AND meta_key IN ('_mmhd_ticket_status', 'ticket_assignee', 'ticket_assignee_id', 'mmhd_ticket_origin')",
            $ids
        )
    );

    // Bulk-fetch origin taxonomy terms (legacy fallback when meta is empty).
    $origin_term_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT tr.object_id, t.name
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = %s
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id",
            'mmhd_ticket_origin'
        )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders

    $meta_map = [];
    foreach ($meta_rows as $row) {
        $meta_map[(int) $row->post_id][$row->meta_key] = $row->meta_value;
    }

    $origin_map = [];
    foreach ($origin_term_rows as $row) {
        $origin_map[(int) $row->object_id] = $row->name;
    }

    // Build lightweight records (no replies, no author objects) for
    // filtering, counts and pagination.
    $light_tickets = [];
    foreach ($ticket_rows as $row) {
        $id = (int) $row->ID;
        $meta = isset($meta_map[$id]) ? $meta_map[$id] : [];

        $status = isset($meta['_mmhd_ticket_status']) && $meta['_mmhd_ticket_status'] !== '' ? $meta['_mmhd_ticket_status'] : 'N/A';
        $assignee = isset($meta['ticket_assignee']) && $meta['ticket_assignee'] !== '' ? $meta['ticket_assignee'] : 'N/A';
        $source = isset($meta['mmhd_ticket_origin']) && $meta['mmhd_ticket_origin'] !== '' ? $meta['mmhd_ticket_origin'] : '';
        if ($source === '' && isset($origin_map[$id])) {
            $source = $origin_map[$id];
        }
        $source = $source !== '' ? $source : 'N/A';

        $light_tickets[$id] = [
            'id' => $id,
            'author_id' => (int) $row->post_author,
            'title' => $row->post_title,
            'modified' => $row->post_modified,
            'status' => $status,
            'assignee' => $assignee,
            'assignee_id' => isset($meta['ticket_assignee_id']) ? (int) $meta['ticket_assignee_id'] : 0,
            'source' => $source,
        ];
    }

    return $light_tickets;
}

/**
 * Single source of truth for all dashboard ticket counters.
 *
 * Counts the stat card numbers (total / open / active / in-progress /
 * closed) and the conversation filter badge counts (my / unassigned /
 * open-active / chat) in one pass over the lightweight ticket records.
 * "active" (stats.active) and the "open" badge (counts.open) both mean
 * open + in-progress.
 *
 * @param array  $light_tickets Lightweight ticket records (see mmhd_get_light_tickets).
 * @param string $user_name     Display name used for the "my conversations" count.
 * @return array
 */
function mmhd_compute_ticket_counters($light_tickets, $user_name = '')
{
    $counters = [
        'stats' => [
            'total' => 0,
            'open' => 0,
            'active' => 0,
            'inProgress' => 0,
            'closed' => 0,
        ],
        'counts' => [
            'my' => 0,
            'unassigned' => 0,
            'open' => 0,
            'chat' => 0,
        ],
    ];

    if (!is_array($light_tickets)) {
        return $counters;
    }

    if ($user_name === '' && function_exists('wp_get_current_user')) {
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name ?: $current_user->user_login;
    }

    foreach ($light_tickets as $t) {
        $status = isset($t['status']) ? $t['status'] : 'N/A';
        $assignee = isset($t['assignee']) ? $t['assignee'] : 'N/A';
        $source = isset($t['source']) ? $t['source'] : 'N/A';

        $counters['stats']['total']++;
        if ($status === 'open') {
            $counters['stats']['open']++;
        } elseif ($status === 'in-progress') {
            $counters['stats']['inProgress']++;
        } elseif ($status === 'closed') {
            $counters['stats']['closed']++;
        }
        if ($status === 'open' || $status === 'in-progress') {
            $counters['stats']['active']++;
        }

        if ($user_name !== '' && $assignee === $user_name && $status !== 'closed') {
            $counters['counts']['my']++;
        }
        if ($assignee === 'N/A') {
            $counters['counts']['unassigned']++;
        }
        if ($status === 'open' || $status === 'in-progress') {
            $counters['counts']['open']++;
        }
        if ($source === 'chat' && $status !== 'closed') {
            $counters['counts']['chat']++;
        }
    }

    return $counters;
}

/**
 * Get all dashboard ticket counters (stat cards + filter badge counts)
 * for a given user. Single entry point used by the initial page render,
 * the AJAX refresh payload and the admin bar.
 *
 * @param int $user_id Optional user ID for the "my conversations" count. Defaults to current user.
 * @return array
 */
function mmhd_get_ticket_counters($user_id = 0)
{
    $user = get_userdata($user_id > 0 ? $user_id : get_current_user_id());
    $user_name = $user ? ($user->display_name ?: $user->user_login) : '';

    return mmhd_compute_ticket_counters(mmhd_get_light_tickets(), $user_name);
}

/**
 * Build paginated dashboard ticket data (one page of tickets) along with
 * filter badge counts and stat card numbers. Uses bulk queries so it scales
 * to thousands of tickets without an N+1 comment/meta query per ticket.
 *
 * @param string $filter   Dashboard filter key.
 * @param int    $page     Page number (1-based).
 * @param int    $per_page Tickets per page (max 50).
 * @return array
 */
function mmhd_build_dashboard_tickets_payload($filter = 'my_conversation', $page = 1, $per_page = 10)
{
    $valid_filters = ['my_conversation', 'unassigned_conversation', 'open_conversation', 'chat_conversation'];
    $filter = in_array($filter, $valid_filters, true) ? $filter : 'my_conversation';
    $page = max(1, (int) $page);
    $per_page = max(1, min(50, (int) $per_page));

    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name ?: $current_user->user_login;

    $empty_payload = [
        'tickets' => [],
        'counts' => ['my' => 0, 'unassigned' => 0, 'open' => 0, 'chat' => 0],
        'stats' => ['total' => 0, 'open' => 0, 'active' => 0, 'inProgress' => 0, 'closed' => 0],
        'total' => 0,
        'page' => $page,
        'per_page' => $per_page,
        'current_user_name' => $current_user_name,
    ];

    $light_tickets = mmhd_get_light_tickets();
    if (empty($light_tickets)) {
        return $empty_payload;
    }

    $filter_matches = function ($t) use ($filter, $current_user_name) {
        switch ($filter) {
            case 'my_conversation':
                return $t['assignee'] === $current_user_name && $t['status'] !== 'closed';
            case 'unassigned_conversation':
                return $t['assignee'] === 'N/A';
            case 'open_conversation':
                return in_array($t['status'], ['open', 'in-progress'], true);
            case 'chat_conversation':
                return $t['source'] === 'chat' && $t['status'] !== 'closed';
            default:
                return false;
        }
    };

    $filtered = array_values(array_filter($light_tickets, $filter_matches));

    $total = count($filtered);
    $total_pages = max(1, (int) ceil($total / $per_page));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $per_page;
    $page_records = array_slice($filtered, $offset, $per_page);

    $tickets = [];
    if (!empty($page_records)) {
        $tickets = mmhd_build_dashboard_ticket_records($page_records);
    }

    $counters = mmhd_compute_ticket_counters($light_tickets, $current_user_name);

    return [
        'tickets' => $tickets,
        'counts' => $counters['counts'],
        'stats' => $counters['stats'],
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'current_user_name' => $current_user_name,
    ];
}

/**
 * Build full ticket records (with replies) for a single page of tickets.
 * Comments and comment meta are fetched with bulk queries.
 *
 * @param array $light_records Lightweight ticket records (see mmhd_build_dashboard_tickets_payload).
 * @return array
 */
function mmhd_build_dashboard_ticket_records($light_records)
{
    global $wpdb;

    $ids = wp_list_pluck($light_records, 'id');

    // Bulk-fetch comments for all page tickets in a single query.
    $comments = get_comments([
        'post__in' => $ids,
        'status' => 'approve',
    ]);
    $comments_by_post = [];
    foreach ($comments as $comment) {
        $comments_by_post[(int) $comment->comment_post_ID][] = $comment;
    }

    // Bulk-fetch comment author types in a single query.
    $comment_ids = wp_list_pluck($comments, 'comment_ID');
    $author_type_map = [];
    if (!empty($comment_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
        $author_type_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT comment_id, meta_value FROM {$wpdb->commentmeta}
                 WHERE comment_id IN ({$id_placeholders}) AND meta_key = 'author_type'",
                $comment_ids
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
        foreach ($author_type_rows as $row) {
            $author_type_map[(int) $row->comment_id] = $row->meta_value;
        }
    }

    $records = [];
    foreach ($light_records as $light) {
        $ticket_id = (int) $light['id'];
        $post = get_post($ticket_id);
        if (!$post) {
            continue;
        }

        $author_id = $light['author_id'];
        $author = $author_id > 0 ? get_userdata($author_id) : null;
        $customer_name = $author ? ($author->display_name ?: $author->user_login) : '';
        $customer_email = $author ? $author->user_email : '';
        $user_location = $author_id > 0 ? get_user_meta($author_id, 'mmhd_user_location', true) : '';
        $author_image = function_exists('mmhd_get_avatar_url')
            ? mmhd_get_avatar_url($author_id, 32)
            : get_avatar_url($author_id, ['size' => 32]);

        $priority_terms = get_the_terms($ticket_id, 'mmhd_ticket_priority');
        $ticket_priority = $priority_terms && !is_wp_error($priority_terms) && !empty($priority_terms) ? $priority_terms[0]->name : 'N/A';

        $category_terms = get_the_terms($ticket_id, 'mmhd_ticket_category');
        $ticket_category = '';
        if (!is_wp_error($category_terms) && !empty($category_terms)) {
            $ticket_category = [
                'name' => $category_terms[0]->name,
                'id' => $category_terms[0]->term_id,
            ];
        }

        $updated_at = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso($post, true) : $post->post_modified;
        $created_at = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso($post) : $post->post_date;

        $ticket_source_meta = function_exists('mmhd_get_ticket_source_meta') ? mmhd_get_ticket_source_meta($ticket_id) : array('ip' => '', 'country' => '', 'browser' => '');

        $ticket_comments = isset($comments_by_post[$ticket_id]) ? $comments_by_post[$ticket_id] : [];
        $replies = [];
        foreach ($ticket_comments as $reply) {
            $author_type = isset($author_type_map[(int) $reply->comment_ID]) ? $author_type_map[(int) $reply->comment_ID] : 'agent';
            $reply_date = function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($reply) : $reply->comment_date;
            $replies[] = [
                'id' => (int) $reply->comment_ID,
                'author' => $reply->comment_author,
                'message' => $reply->comment_content,
                'content' => $reply->comment_content,
                'date' => $reply_date,
                'timestamp' => $reply_date,
                'type' => $author_type,
                'author_type' => $author_type,
            ];
        }

        $records[] = [
            'id' => $ticket_id,
            'title' => $post->post_title,
            'customer' => $customer_name,
            'email' => $customer_email,
            'author_id' => $author_id,
            'user_location' => $user_location,
            'category' => $ticket_category,
            'source' => $light['source'],
            'source_ip' => $ticket_source_meta['ip'],
            'source_country' => $ticket_source_meta['country'],
            'source_browser' => $ticket_source_meta['browser'],
            'status' => $light['status'],
            'author_image' => $author_image,
            'created' => $created_at,
            'priority' => $ticket_priority,
            'updated' => $updated_at,
            'assignee' => $light['assignee'],
            'assignee_id' => $light['assignee_id'],
            'replies' => $replies,
            'replies_count' => count($replies),
        ];
    }

    return $records;
}

// AJAX handler for refreshing dashboard tickets (server-side pagination)
add_action('wp_ajax_mmhd_refresh_dashboard_tickets', 'mmhd_refresh_dashboard_tickets');
function mmhd_refresh_dashboard_tickets()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mmhd_refresh_dashboard_tickets_action')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }

    // Check user capabilities
    if (!current_user_can('mmhd_access_dashboard')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $filter = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : 'my_conversation';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

    wp_send_json_success(mmhd_build_dashboard_tickets_payload($filter, $page, $per_page));
}

// AJAX handler for fetching all tickets belonging to one customer (modal)
add_action('wp_ajax_mmhd_get_customer_tickets', 'mmhd_get_customer_tickets');
function mmhd_get_customer_tickets()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mmhd_customer_tickets_action')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }

    // Check user capabilities
    if (!current_user_can('mmhd_access_dashboard')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    $author_id = isset($_POST['author_id']) ? intval($_POST['author_id']) : 0;
    if ($author_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid customer.'));
    }

    $ticket_ids = get_posts([
        'post_type' => 'mmhd_ticket',
        'post_status' => 'publish',
        'author' => $author_id,
        'posts_per_page' => -1,
        'orderby' => 'modified',
        'order' => 'DESC',
        'fields' => 'ids',
    ]);

    $tickets = [];
    foreach ($ticket_ids as $ticket_id) {
        $post = get_post($ticket_id);
        if (!$post) {
            continue;
        }
        $priority_terms = get_the_terms($ticket_id, 'mmhd_ticket_priority');
        $tickets[] = [
            'id' => (int) $ticket_id,
            'title' => $post->post_title,
            'status' => get_post_meta($ticket_id, '_mmhd_ticket_status', true) ?: 'N/A',
            'priority' => $priority_terms && !is_wp_error($priority_terms) && !empty($priority_terms) ? $priority_terms[0]->name : 'N/A',
            'created' => function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso($post) : $post->post_date,
            'customer' => get_the_author_meta('display_name', $author_id) ?: get_the_author_meta('user_login', $author_id),
        ];
    }

    wp_send_json_success(['tickets' => $tickets]);
}
