<?php
// robodesk-email-to-ticket
// Function to display the email to ticket

function robodesk_fetch_emails_callback()
{
    echo '<h1>Email To Ticket</h1>';
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
    // Email configuration details
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

            if (empty($message_id)) {
                continue;
            }

            // Prevent duplicates (tickets that have already been fetched)
            $existing_ticket = get_posts([
                'post_type' => 'robodesk_ticket',
                'meta_key' => 'email_message_id',
                'meta_value' => $message_id
            ]);

            // If the email is already fetched (ticket exists), check if it's a reply and skip if not
            if (!empty($existing_ticket)) {
                // This is a reply, so we fetch it
                if (empty($in_reply_to) && empty($references)) {
                    continue; // No reply, skip this email
                }
            }

            // Prevent duplicate emails (if ticket doesn't exist)
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

            // Find the original post for replies
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

                continue; // Skip creating a new post for replies
            }

            // If no post exists, create a new one (for first-time emails)
            $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            $user = get_user_by('email', $useremail);
            if (!$user) {
                $user_id = wp_insert_user(array(
                    'user_login' => explode('@', $useremail)[0],
                    'user_email' => $useremail,
                    'user_pass' => wp_generate_password(),
                    'role' => 'robodesk_support_customer',
                ));
            } else {
                $user_id = $user->ID;
            }

            // Ensure the "Email" term exists in the taxonomy
            $term_email = term_exists('Email', 'robodesk_ticket_origin');
            $term_priority = term_exists('Medium', 'robodesk_ticket_priority');

            if (!$term_email) {
                $term_email = wp_insert_term('Email', 'robodesk_ticket_origin');
            }

            if (!$term_priority) {
                $term_priority = wp_insert_term('Medium', 'robodesk_ticket_priority');
            }

            $term_email_id = (!is_wp_error($term_email) && isset($term_email['term_id'])) ? $term_email['term_id'] : null;
            $term_priority_id = (!is_wp_error($term_priority) && isset($term_priority['term_id'])) ? $term_priority['term_id'] : null;

            // Create new post With taxonomy Email
            $post_id = wp_insert_post([
                'post_title'   => $subject,
                'post_content' => $message,
                'post_date'    => $date,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_type'    => 'robodesk_ticket',
            ]);

            if ($post_id && $term_email_id) {
                wp_set_post_terms($post_id, [$term_email_id], 'robodesk_ticket_origin', false);
            }

            if ($post_id && $term_priority_id) {
                wp_set_post_terms($post_id, [$term_priority_id], 'robodesk_ticket_priority', false);
            }

            update_post_meta($post_id, '_robodesk_ticket_status', 'open');

            if (!is_wp_error($post_id)) {
                add_post_meta($post_id, 'email_message_id', $message_id);
                add_post_meta($post_id, 'email_from', $from);
            }
        }
    }
    imap_close($email);
}

// Post comments as email replies
function robodesk_reply_email($comment_id, $comment_approved)
{
    if ($comment_approved) {
        $comment = get_comment($comment_id); // Get the comment object
        $post = get_post($comment->comment_post_ID); // Get the post object
        $email_from = get_post_meta($post->ID, 'email_from', true); // Get the email from the post meta
        $email_message_id = get_post_meta($post->ID, 'email_message_id', true); // Get the email message id from the post meta

        preg_match('/<(.*?)>/', $email_from, $matches); // Extract email from angle brackets
        $pure_email = $matches[1] ?? $email_from;       // Get email without angle brackets

        // Validate email
        if (!filter_var($pure_email, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid sender email: ' . $pure_email);
            return;
        }

        $reply_to = $pure_email;
        $subject = 'Re: ' . $post->post_title;
        $comment_content = $comment->comment_content;
        $reply_message = "On " . date("Y-m-d H:i:s", strtotime($post->post_date)) . ", $email_from wrote:\n\n" . $comment_content;

        $headers = [
            'From: Robodesk Support <pipetest@wplocatepress.com>',
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
