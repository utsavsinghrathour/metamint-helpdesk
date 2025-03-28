<?php

/**
 * Modifications Related to comments hooks, functions etc
 */


//Tiny mce editor for comments
add_filter('comment_form_defaults', 'robodesk_add_tinymce_to_comments');

function robodesk_add_tinymce_to_comments($defaults)
{
    ob_start();

    if (isset($_GET['tid']) && !is_wp_error(get_post_type($_GET['tid']))) {
        $value = get_post_meta(sanitize_text_field($_GET['tid']), '_robodesk_ticket_status', true);
?>
        <div class="robodesk-status-selector">
            <label for="robodesk_ticket_status_comment"><?php _e('Select Status *','robodesk') ?></label>
            <select name="robodesk_ticket_status_comment" id="robodesk_ticket_status_comment" required="true">
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
        'teeny'         => true,
        'quicktags'     => false,
        'tinymce'       => array(
            'toolbar1' => 'bold italic underline link | fullscreen',
            'toolbar2' => '',
        ),
    ));


    $defaults['comment_field'] = ob_get_clean();
    return $defaults;
}


//Saving Comments status dropdown field
function robodesk_save_custom_comment_field($comment_id) {
    if (!empty($_POST['robodesk_ticket_status_comment'])) {
        $dropdown_value = sanitize_text_field(wp_unslash($_POST['robodesk_ticket_status_comment']));
        $comment_post_id =  sanitize_text_field(wp_unslash($_POST['comment_post_ID']));
        update_post_meta($comment_post_id, '_robodesk_ticket_status', $dropdown_value);
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








//  // Add dropdown field to comment form
// function custom_comment_form_fields($fields) {
//     $fields['custom_dropdown'] = '<p class="comment-form-custom-dropdown">' .
//         '<label for="custom_dropdown">Custom Dropdown</label>' .
//         '<select id="custom_dropdown" name="custom_dropdown" required>' .
//         '<option value="">Select an option</option>' .
//         '<option value="option1">Option 1</option>' .
//         '<option value="option2">Option 2</option>' .
//         '<option value="option3">Option 3</option>' .
//         '</select></p>';

//     return $fields;
// }
// add_filter('comment_form_default_fields', 'custom_comment_form_fields');

// // Save dropdown selection
// function save_custom_comment_field($comment_id) {
//     if (!empty($_POST['custom_dropdown'])) {
//         $dropdown_value = sanitize_text_field(wp_unslash($_POST['custom_dropdown']));
//         update_comment_meta($comment_id, 'custom_dropdown', $dropdown_value);
//     }
// }
// add_action('comment_post', 'save_custom_comment_field');

// // Display dropdown selection with comments
// function display_custom_comment_field($comment_text, $comment = null) {
//     if ($comment && is_object($comment)) {
//         $custom_dropdown = get_comment_meta($comment->comment_ID, 'custom_dropdown', true);
//         if ($custom_dropdown) {
//             $comment_text .= '<p><strong>Custom Selection:</strong> ' . esc_html($custom_dropdown) . '</p>';
//         }
//     }
//     return $comment_text;
// }
// add_filter('comment_text', 'display_custom_comment_field', 10, 2);



?>