<?php

/**
 * Modifications Related to comments hooks, functions etc
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_filter('comment_form_defaults', 'robodesk_add_tinymce_to_comments');

function robodesk_add_tinymce_to_comments($defaults)
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!isset($_GET['tid'])) {
        return $defaults;
    }

    ob_start();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $tid = sanitize_text_field(wp_unslash($_GET['tid']));
    if (!is_wp_error(get_post_type($tid))) {
        $value = get_post_meta($tid, '_robodesk_ticket_status', true);
?>
        <div class="robodesk-status-selector">
            <label for="robodesk_ticket_status_comment"><?php esc_html_e('Select Status *', 'robodesk') ?></label>
            <select name="robodesk_ticket_status_comment" id="robodesk_ticket_status_comment" required>
                <option value="open" <?php selected($value, 'new'); ?>>New</option>
                <option value="open" <?php selected($value, 'open'); ?>>Open</option>
                <option value="in-progress" <?php selected($value, 'in-progress'); ?>>In Progress</option>
                <option value="closed" <?php selected($value, 'closed'); ?>>Closed</option>
            </select>
        </div>
<?php
    }

    wp_editor('', 'comment', array(
        'media_buttons' => false,
        'textarea_rows' => 10,
        'teeny' => true,
        'quicktags' => false,
        'tinymce' => array(
            'toolbar1' => 'bold italic underline link | fullscreen',
            'toolbar2' => '',
        ),
    ));

    $defaults['comment_field'] = ob_get_clean();
    return $defaults;
}

add_action('wp_enqueue_scripts', 'robodesk_restore_editor_styles', 99);
function robodesk_restore_editor_styles()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!isset($_GET['tid'])) {
        if (!wp_style_is('editor-buttons', 'enqueued')) {
            wp_enqueue_style('editor-buttons');
        }
    }
}


//Saving Comments status dropdown field
function robodesk_save_custom_comment_field($comment_id)
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (!empty($_POST['robodesk_ticket_status_comment'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $dropdown_value = sanitize_text_field(wp_unslash($_POST['robodesk_ticket_status_comment']));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $comment_post_id = isset($_POST['comment_post_ID']) ? sanitize_text_field(wp_unslash($_POST['comment_post_ID'])) : '';
        
        if ($comment_post_id) {
            $old_status = (string) get_post_meta($comment_post_id, '_robodesk_ticket_status', true);
            update_post_meta($comment_post_id, '_robodesk_ticket_status', $dropdown_value);

        if ($old_status !== $dropdown_value && function_exists('robodesk_notify_status_changed')) {
            $comment = get_comment($comment_id);
            $source = is_admin() ? 'admin' : 'chat';
            if ($comment && !is_wp_error($comment)) {
                $comment_source = (string) get_comment_meta($comment_id, 'comment_source', true);
                if ($comment_source !== '') {
                    $source = $comment_source;
                }
            }

            robodesk_notify_status_changed($comment_post_id, $dropdown_value, get_current_user_id(), $source);
        }
        }
    }
}

add_action('comment_post', 'robodesk_save_custom_comment_field');

/** Disable single page for robodesk ticket */
function robodesk_remove_cpt_single_rewrite()
{

    global $wp_post_types;
    if (isset($wp_post_types['robodesk_ticket'])) {
        $wp_post_types['robodesk_ticket']->publicly_queryable = false;
        $wp_post_types['robodesk_ticket']->query_var = false;
    }

    // flush_rewrite_rules();
}
add_action('init', 'robodesk_remove_cpt_single_rewrite', 20);



/**
 * Exclude 'ticket_replies' comment type from the WP Admin comments list.
 */

// 1. Filter the query that powers edit-comments.php
add_action( 'pre_get_comments', function( WP_Comment_Query $query ) {
    global $pagenow;

    if ( ! is_admin() || $pagenow !== 'edit-comments.php' ) {
        return;
    }

    $excluded_types = [ 'ticket_replies', 'ticket_notes' ];
    $types = $query->query_vars['type__not_in'] ?? [];

    if ( ! is_array( $types ) ) {
        $types = [ $types ];
    }

    foreach ( $excluded_types as $type ) {
        if ( ! in_array( $type, $types, true ) ) {
            $types[] = $type;
        }
    }

    $query->query_vars['type__not_in'] = $types;
} );

// 2. Exclude from the counts shown in the status tabs (All / Pending / Approved etc.)
add_filter( 'wp_count_comments', function( $counts, $post_id ) {
    global $pagenow;

    if ( ! is_admin() || $pagenow !== 'edit-comments.php' ) {
        return $counts;
    }

    global $wpdb;

    $excluded_types = [ 'ticket_replies', 'ticket_notes' ];
    $placeholders   = implode( ', ', array_fill( 0, count( $excluded_types ), '%s' ) );

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT comment_approved, COUNT(*) AS num_comments
             FROM {$wpdb->comments}
             WHERE comment_type NOT IN ($placeholders)
             GROUP BY comment_approved",
            ...$excluded_types
        ),
        ARRAY_A
    );

    $approved = $pending = $spam = $trash = $post_trash = 0;

    foreach ( $rows as $row ) {
        switch ( $row['comment_approved'] ) {
            case '1':            $approved   += $row['num_comments']; break;
            case '0':            $pending    += $row['num_comments']; break;
            case 'spam':         $spam       += $row['num_comments']; break;
            case 'trash':        $trash      += $row['num_comments']; break;
            case 'post-trashed': $post_trash += $row['num_comments']; break;
        }
    }

    return (object) [
        'approved'       => $approved,
        'moderated'      => $pending,
        'spam'           => $spam,
        'trash'          => $trash,
        'post-trashed'   => $post_trash,
        'total_comments' => $approved + $pending + $spam + $trash + $post_trash,
        'all'            => $approved + $pending,
    ];
}, 10, 2 );