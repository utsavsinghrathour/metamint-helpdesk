<?php 
/**
 * Modifications Related to comments hooks, functions etc
 */


//Tiny mce editor for comments
 add_filter('comment_form_defaults', 'robodesk_add_tinymce_to_comments');

 function robodesk_add_tinymce_to_comments($defaults) {
     ob_start();
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
 
 
 function custom_comment_form_dropdown($fields) {
    $dropdown = '<p class="comment-form-dropdown">
        <label for="comment_reason">Reason for Comment</label>
        <select name="comment_reason" id="comment_reason" required>
            <option value="">Select a reason</option>
            <option value="feedback">Feedback</option>
            <option value="question">Question</option>
            <option value="other">Other</option>
        </select>
    </p>';

    $fields['comment_reason'] = $dropdown;

    return $fields;
}
add_filter('comment_form_default_fields', 'custom_comment_form_dropdown');

// Save dropdown value as comment meta
function save_comment_dropdown_meta($comment_id) {
    if (isset($_POST['comment_reason'])) {
        $reason = sanitize_text_field($_POST['comment_reason']);
        add_comment_meta($comment_id, 'comment_reason', $reason);
    }
}
add_action('comment_post', 'save_comment_dropdown_meta');

// Display dropdown value in the comment admin section
function show_comment_reason_meta($comment) {
    $reason = get_comment_meta($comment->comment_ID, 'comment_reason', true);
    if ($reason) {
        echo '<p><strong>Reason for Comment:</strong> ' . esc_html($reason) . '</p>';
    }
}
add_filter('comment_text', 'show_comment_reason_meta');

 
 /** Disable single page for robodesk ticket */
 function remove_cpt_single_rewrite() {
 
     global $wp_post_types;
     if (isset($wp_post_types['robodesk_ticket'])) {
         $wp_post_types['robodesk_ticket']->publicly_queryable = false;
         $wp_post_types['robodesk_ticket']->query_var = false;
     }
     
     // flush_rewrite_rules();
 }
 add_action('init', 'remove_cpt_single_rewrite', 20);

?>