<?php
// In public/includes/mmhd-notes.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// This file handles adding notes to tickets.

add_action('wp_ajax_mmhd_add_note', 'mmhd_add_note_callback');
function mmhd_add_note_callback()
{
    check_ajax_referer('mmhd_add_note_nonce', 'nonce');

    $ticket_id = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
    $note_content = isset($_POST['note_content']) ? sanitize_textarea_field(wp_unslash($_POST['note_content'])) : '';

    $ticket = get_post($ticket_id);
    if (!$ticket || $ticket->post_type !== 'mmhd_ticket') {
        wp_send_json_error(array('message' => 'Invalid ticket.'));
    }

    $current_user_id = get_current_user_id();
    $customer_user_id = 0;
    if (function_exists('mmhd_get_ticket_customer_user_id')) {
        $customer_user_id = (int) mmhd_get_ticket_customer_user_id($ticket_id);
    }

    $is_owner = $current_user_id > 0 && ((int) $ticket->post_author === $current_user_id || ($customer_user_id > 0 && $customer_user_id === $current_user_id));

    if (!current_user_can('mmhd_access_dashboard') && !current_user_can('manage_options') && !$is_owner) {
        wp_send_json_error(array('message' => 'You are not authorized to add notes to this ticket.'));
    }
    $user = wp_get_current_user();
    $comment_data = array(
        'comment_post_ID' => $ticket_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content' => $note_content,
        'comment_type' => 'mmhd_note', // Custom comment type for notes
        'comment_parent' => 0,
        'user_id' => $user->ID,
        'comment_date' => current_time('mysql'),
        'comment_date_gmt' => gmdate('Y-m-d H:i:s'),
        'comment_approved' => 1,
    );

    $comment_id = wp_insert_comment($comment_data);

    if ($comment_id) {
        wp_send_json_success(array('note_id' => $comment_id, 'note_content' => $note_content, 'user' => $user->display_name));
    } else {
        wp_send_json_error(array('message' => 'Error adding note.'));
    }

    wp_die();
}

// Function to display notes on the ticket page
function mmhd_display_notes($ticket_id)
{
    $args = array(
        'post_id' => $ticket_id,
        'status' => 'approve',
        'type' => 'mmhd_note', // Only get mmhd_note comments
        'order' => 'ASC',
    );
    $comments = get_comments($args);

    if ($comments) {
        echo '<div class="mmhd-ticket-notes">';
        echo '<h3>Ticket Notes</h3>';
        echo '<ul>';
        foreach ($comments as $comment) {
            echo '<li>';
            echo '<p><strong>' . esc_html($comment->comment_author) . '</strong> on ' . esc_html(mysql2date(get_option('date_format'), $comment->comment_date)) . ':</p>';
            echo '<p>' . esc_html($comment->comment_content) . '</p>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Shortcode to display the note form
add_shortcode('mmhd_note_form', 'mmhd_note_form_shortcode');
function mmhd_note_form_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'ticket_id' => 0
    ), $atts);

    $ticket_id = (int) $atts['ticket_id'];

    if (!current_user_can('read_post', $ticket_id)) return;

    ob_start();
?>
    <div class="mmhd-note-form">
        <h3>Add a Note</h3>
        <textarea id="mmhd_note_content" rows="4" cols="50"></textarea><br>
        <input type="hidden" id="mmhd_note_ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
        <button id="mmhd_add_note_button">Add Note</button>
        <input type="hidden" id="mmhd_add_note_nonce" value="<?php echo esc_attr(wp_create_nonce('mmhd_add_note_nonce')); ?>">
    </div>
<?php
    return ob_get_clean();
}
