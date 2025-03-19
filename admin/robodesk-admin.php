<?php

// Include admin-side post types
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-ticket-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-faq-post-type.php';
// require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-fetch-ticket.php';


// Add Admin Menu Pages (if needed)
// add_action('admin_menu', 'robodesk_add_admin_menu');
// function robodesk_add_admin_menu() {
// }


// Other admin related functions can go here
add_action('admin_menu', 'robodesk_add_admin_menu');
function robodesk_add_admin_menu()
{
    add_menu_page(
        'Robodesk',
        'Support',
        'manage_options',
        'edit.php?post_type=robodesk_ticket',
        '',
        'dashicons-email-alt',
        25
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All Tickets',
        'All Tickets',
        'edit_tickets', // Use custom capability for the submenus
        'edit.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New Ticket',
        'Add New',
        'edit_tickets',
        'post-new.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Origins',
        'Ticket Origins',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_origin&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Priorities',
        'Ticket Priorities',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_priority&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New FAQ',
        'Add New FAQ',
        'edit_posts',
        'post-new.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'FAQ Categories',
        'FAQ Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_faq_category&post_type=robodesk_faq'
    );
    // making the submenu page for the email-to-ticket plugin functions to be under the robodesk menu
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Robodesk Ticket',
        'Robodesk Ticket',
        'manage_options',
        'robodesk-email-to-ticket',
        'robodesk_fetch_emails_callback'
    );
}

function update_taxonomy_order()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'taxonomy_order_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
        wp_send_json_error('Invalid order data');
    }

    $term_order = array_map('intval', $_POST['order']);

    foreach ($term_order as $index => $term_id) {
        if ($term_id) {
            update_term_meta($term_id, 'taxonomy_order', $index);
        }
    }

    wp_send_json_success('Order updated');
}
add_action('wp_ajax_update_taxonomy_order', 'update_taxonomy_order');

function modify_taxonomy_query($query)
{
    if (
        is_admin() &&
        isset($query->query_vars['taxonomy']) &&
        $query->query_vars['taxonomy'] === 'robodesk_ticket_priority'
    ) {
        $query->query_vars['meta_key'] = 'taxonomy_order';
        $query->query_vars['orderby'] = array(
            'meta_value_num' => 'ASC',
            'name' => 'ASC'
        );


        $query->query_vars['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => 'taxonomy_order',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'taxonomy_order',
                'compare' => 'NOT EXISTS',
                'value' => ''
            )
        );
    }
}
add_action('pre_get_terms', 'modify_taxonomy_query');


function set_default_taxonomy_order($term_id, $tt_id, $taxonomy)
{
    if ($taxonomy === 'robodesk_ticket_priority') {
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'meta_key'   => 'taxonomy_order',
            'orderby'    => 'meta_value_num',
            'order'      => 'DESC',
            'fields'     => 'ids',
            'number'     => 1
        ));

        $new_order = (!empty($terms) && get_term_meta($terms[0], 'taxonomy_order', true) !== '')
            ? (int)get_term_meta($terms[0], 'taxonomy_order', true) + 1
            : 0;
        update_term_meta($term_id, 'taxonomy_order', $new_order);
    }
}
add_action('created_term', 'set_default_taxonomy_order', 10, 3);

function custom_taxonomy_order($terms, $taxonomies, $args)
{
    if (
        is_admin() &&
        in_array('robodesk_ticket_priority', $taxonomies) &&
        !isset($_GET['orderby'])
    ) {

        usort($terms, function ($a, $b) {

            $a_id = is_object($a) ? $a->term_id : $a;
            $b_id = is_object($b) ? $b->term_id : $b;

            $a_order = get_term_meta($a_id, 'taxonomy_order', true);
            $b_order = get_term_meta($b_id, 'taxonomy_order', true);
            $a_order = $a_order !== '' ? (int)$a_order : PHP_INT_MAX;
            $b_order = $b_order !== '' ? (int)$b_order : PHP_INT_MAX;

            return $a_order - $b_order;
        });
    }
    return $terms;
}
add_filter('get_terms', 'custom_taxonomy_order', 10, 3);

// robodesk-email-to-ticket
// Function to display the email to ticket

function robodesk_fetch_emails_callback()
{
    echo '<h1>Email To Post</h1>';
    echo '<p>Click the button below to fetch Tickets</p>';
    echo '<form method="post" action="">';
    wp_nonce_field('robodesk_fetch_emails_action', 'robodesk_fetch_emails_nonce'); // Add nonce field for security
    echo '<input type="submit" name="fetch_emails" value="Fetch Tickets" class="button button-primary">';
    echo '</form>';
    // Check if the fetch emails button is clicked and nonce is valid
    if (isset($_POST['fetch_emails']) && check_admin_referer('robodesk_fetch_emails_action', 'robodesk_fetch_emails_nonce')) {
        robodesk_fetch_emails();
    }

    echo '<table class="wp-list-table widefat fixed striped ">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';

    $posts = get_posts(
        [
            'post_type' => 'robodesk_ticket',
            'meta_key' => 'email_message_id',
            'numberposts' => -1 // Get all tickets with email_message_id meta
        ]
    );
    foreach ($posts as $post) {
        echo '<tr>';
        echo '<td>' . esc_html(get_post_meta($post->ID, 'email_from', true)) . '</td>';
        echo '<td>' . esc_html($post->post_title) . '</td>';
        echo '<td>' . esc_html($post->post_date) . '</td>';
        echo '<td>' . esc_html($post->post_content) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}


// Function to clean the email message 
function robodesk_clean_email_message($message)
{
    $message = quoted_printable_decode($message);
    $message = preg_replace('/On\s.*\bwrote:[\s\S]+?(?=\n\s*--|$)/s', '', $message);
    $message = trim($message);
    return preg_replace('/(^|\n)>.*$/m', '', $message);
}

// Function to fetch emails
function robodesk_fetch_emails()
{
    // Email configuration
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
    $username = 'pipetest@wplocatepress.com';
    $password = 'k342+11$c1_';
    // Connect to email
    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());
    $emails = imap_search($email, 'ALL'); // Search for all emails

    if ($emails) {
        foreach ($emails as $email_number) {
            $headerInfo = imap_headerinfo($email, $email_number);
            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            $subject = $headerInfo->subject ?? 'No Subject';
            $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $message_id = $headerInfo->message_id ?? ''; // Unique identifier for the email
            $in_reply_to = $headerInfo->in_reply_to ?? ''; // Message ID of the email being replied to
            $references = $headerInfo->references ?? ''; // Message IDs of previous emails in the thread

            $message = imap_fetchbody($email, $email_number, 1.1);
            if (empty($message)) {
                $message = imap_fetchbody($email, $email_number, 1);
            }
            $message = robodesk_clean_email_message($message);

            if (empty($message_id)) continue;

            // Prevent duplicates
            if (get_posts(['post_type' => 'robodesk_ticket', 'meta_key' => 'email_message_id', 'meta_value' => $message_id])) {
                continue;
            }

            $original_post_id = null;
            if (!empty($references)) {
                $reference_ids = explode(' ', trim($references));
                $first_message_id = reset($reference_ids);
            } elseif (!empty($in_reply_to)) {
                $first_message_id = $in_reply_to;
            } else {
                $first_message_id = null;
            }

            // Find the original post 
            if (!empty($first_message_id)) {
                $original_query = new WP_Query([
                    'meta_key' => 'email_message_id',
                    'meta_value' => $first_message_id,
                    'post_type' => 'robodesk_ticket',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ]);
                if (!empty($original_query->posts)) {
                    $original_post_id = $original_query->posts[0];
                }
            }

            if ($original_post_id) {
                // Prevent duplicate comments
                $existing_comments = get_comments([
                    'post_id' => $original_post_id,
                    'meta_key' => 'email_message_id',
                    'meta_value' => $message_id,
                ]);

                $is_duplicate = false;
                foreach ($existing_comments as $comment) {
                    if (trim($comment->comment_content) == trim($message)) {
                        $is_duplicate = true;
                        break;
                    }
                }

                if (!$is_duplicate) {
                    // Insert comment
                    $comment_id = wp_insert_comment([
                        'comment_post_ID' => $original_post_id,
                        'comment_author' => $from,
                        'comment_content' => $message,
                        'comment_date' => $date,
                        'comment_approved' => 1,
                    ]);

                    // Add meta separately
                    if (!is_wp_error($comment_id) && $comment_id) {
                        add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                    }
                }

                continue; // Skip creating a new post
            }


            // If no post exists, create a new one (for first-time emails)
            $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            $user = get_user_by('email', $useremail);
            if (!$user) {
                $user_id = wp_insert_user(array(
                    'user_login' => explode('@', $useremail)[0],
                    'user_email' => $useremail,
                    'user_pass'  => wp_generate_password(),
                ));
            } else {
                $user_id = $user->ID;
            }

            // Create new post
            $post_id = wp_insert_post([
                'post_title' => $subject,
                'post_content' => $message,
                'post_date' => $date,
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_type' => 'robodesk_ticket'
            ]);

            if (!is_wp_error($post_id)) {
                add_post_meta($post_id, 'email_message_id', $message_id);
                add_post_meta($post_id, 'email_from', $from);
            }
        }
    }
    imap_close($email);
}

// adding a feature that allows the user to reply to the email from the post original mail from email pipetest@wplocatepress.com through comment section of the post
// imap cannot be used to send emails, so we will use wp_mail() function to send emails. email from must be pipetest@wplocatepress.com and the email to must be the post author email
// Post comments as email replies
function robodesk_reply_email($comment_id, $comment_approved)
{
    if ($comment_approved) {
        $comment = get_comment($comment_id); // Get the comment object
        $post = get_post($comment->comment_post_ID); // Get the post object
        $email_from = get_post_meta($post->ID, 'email_from', true); //  Get the email from the post meta
        $email_message_id = get_post_meta($post->ID, 'email_message_id', true); // Get the email message id from the post meta

        preg_match('/<(.*?)>/', $email_from, $matches); // Extract email from angle brackets
        $pure_email = $matches[1] ?? $email_from;       // Get email without angle brackets

        //validate email
        if (!filter_var($pure_email, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid sender email: ' . $pure_email);
            return;
        }

        $reply_to = $pure_email;
        $subject = 'Re: ' . $post->post_title;
        $comment_content = $comment->comment_content;
        $reply_message = "On " . date("Y-m-d H:i:s", strtotime($post->post_date)) . ", $email_from wrote:\n\n" . $comment_content;

        $headers = [
            'From: Email To Post <pipetest@wplocatepress.com>',
            'Reply-To: ' . $reply_to,
            'References: ' . $email_message_id,
            'In-Reply-To: ' . $email_message_id,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8'
        ];

        $mail_sent = wp_mail($reply_to, $subject, $reply_message, $headers); // Send email

        if (!$mail_sent) {
            error_log('Email failed to send to ' . $reply_to); // Log error if email fails to send
        } else {
            error_log('Email successfully sent to ' . $reply_to); // Log success if email is sent
        }
    }
}
add_action('comment_post', 'robodesk_reply_email', 10, 2); // Hook into comment_post action which is triggered when a comment is posted
