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
    // Only swap in the rich editor when viewing a robodesk ticket
    if (!isset($_GET['tid'])) {
        return $defaults;
    }

    ob_start();

    if (!is_wp_error(get_post_type(sanitize_text_field($_GET['tid'])))) {
        $value = get_post_meta(sanitize_text_field($_GET['tid']), '_robodesk_ticket_status', true);
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
    if (!isset($_GET['tid'])) {
        if (!wp_style_is('editor-buttons', 'enqueued')) {
            wp_enqueue_style('editor-buttons');
        }
    }
}


//Saving Comments status dropdown field
function robodesk_save_custom_comment_field($comment_id)
{
    if (!empty($_POST['robodesk_ticket_status_comment'])) {
        $dropdown_value = sanitize_text_field(wp_unslash($_POST['robodesk_ticket_status_comment']));
        $comment_post_id = sanitize_text_field(wp_unslash($_POST['comment_post_ID']));
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

add_action('comment_post', 'robodesk_save_custom_comment_field');

/** Disable single page for robodesk ticket */
function remove_cpt_single_rewrite()
{

    global $wp_post_types;
    if (isset($wp_post_types['robodesk_ticket'])) {
        $wp_post_types['robodesk_ticket']->publicly_queryable = false;
        $wp_post_types['robodesk_ticket']->query_var = false;
    }

    // flush_rewrite_rules();
}
add_action('init', 'remove_cpt_single_rewrite', 20);
