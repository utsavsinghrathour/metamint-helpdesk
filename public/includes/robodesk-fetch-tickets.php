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
    error_log('Starting email fetch process');

    // Verify the custom post type exists
    if (!post_type_exists('robodesk_ticket')) {
        echo '<div class="error"><p>Custom post type "robodesk_ticket" does not exist!</p></div>';
        error_log('Custom post type "robodesk_ticket" does not exist!');
        return;
    }

    // Check if required taxonomies exist
    if (!taxonomy_exists('robodesk_ticket_origin')) {
        echo '<div class="error"><p>Taxonomy "robodesk_ticket_origin" does not exist!</p></div>';
        error_log('Taxonomy "robodesk_ticket_origin" does not exist!');
        return;
    }

    if (!taxonomy_exists('robodesk_ticket_priority')) {
        echo '<div class="error"><p>Taxonomy "robodesk_ticket_priority" does not exist!</p></div>';
        error_log('Taxonomy "robodesk_ticket_priority" does not exist!');
        return;
    }

    $last_fetched_date = get_option('robodesk_last_fetched_date', '2021-01-01 00:00:00');
    error_log('Last fetched date: ' . $last_fetched_date);

    // Convert last fetched date into IMAP format
    $since_date = date('d-M-Y', strtotime($last_fetched_date));
    error_log('IMAP since date format: ' . $since_date);

    // Email configuration details
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
    $username = 'pipetest@wplocatepress.com';
    $password = 'k342+11$c1_';

    error_log('Attempting to connect to IMAP server: ' . $hostname);

    // Connect to email with error handling
    $email = @imap_open($hostname, $username, $password);
    if (!$email) {
        $error_message = 'Cannot connect to email: ' . imap_last_error();
        error_log($error_message);
        echo '<div class="error"><p>' . esc_html($error_message) . '</p></div>';
        return;
    }

    error_log('Successfully connected to IMAP server');

    // Fetch emails received after last fetched date (both read and unread)
    $search_criteria = 'SINCE "' . $since_date . '"';
    error_log('Search criteria: ' . $search_criteria);

    $emails = imap_search($email, $search_criteria);

    if (!$emails) {
        $notice = 'No new emails found since ' . $since_date;
        error_log($notice);
        echo '<div class="notice"><p>' . esc_html($notice) . '</p></div>';
        imap_close($email);
        return;
    }

    error_log('Found ' . count($emails) . ' emails to process');

    // Sort emails oldest to newest
    sort($emails);

    $processed_count = 0;
    $skipped_count = 0;
    $reply_count = 0;
    $error_count = 0;

    foreach ($emails as $email_number) {
        error_log('Processing email #' . $email_number);

        $headerInfo = imap_headerinfo($email, $email_number);
        if (!$headerInfo) {
            error_log('Failed to get header info for email #' . $email_number);
            $error_count++;
            continue;
        }

        $message_id = $headerInfo->message_id ?? '';

        if (empty($message_id)) {
            error_log('Email #' . $email_number . ' has no message ID, skipping');
            $skipped_count++;
            continue;
        }

        $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
        error_log('Email date: ' . $date . ', Last fetched: ' . $last_fetched_date);

        // Ensure only emails received AFTER last fetched date are processed
        if (strtotime($date) <= strtotime($last_fetched_date)) {
            error_log('Skipping email #' . $email_number . ' as it\'s older than last fetch date');
            $skipped_count++;
            continue; // Skip old emails
        }

        // Prevent duplicate tickets
        $existing_ticket = get_posts([
            'post_type' => 'robodesk_ticket',
            'meta_key' => 'email_message_id',
            'meta_value' => $message_id
        ]);

        if (!empty($existing_ticket)) {
            error_log('Skipping email #' . $email_number . ' as a ticket already exists with message ID: ' . $message_id);
            $skipped_count++;
            continue; // Skip already fetched emails
        }

        $from = $headerInfo->fromaddress ?? 'Unknown Sender';
        $subject = $headerInfo->subject ?? 'No Subject';
        error_log('Email from: ' . $from . ', Subject: ' . $subject);

        // Fetch email body
        $message = imap_fetchbody($email, $email_number, 1.1);
        if (empty($message)) {
            error_log('Trying alternate body part for email #' . $email_number);
            $message = imap_fetchbody($email, $email_number, 1);
        }

        if (empty($message)) {
            error_log('Could not retrieve body for email #' . $email_number);
            $error_count++;
            continue;
        }

        $message = robodesk_clean_email_message($message);

        // Handle replies
        $in_reply_to = $headerInfo->in_reply_to ?? '';
        $references = $headerInfo->references ?? '';
        $original_post_id = null;

        if (!empty($in_reply_to) || !empty($references)) {
            error_log('Email #' . $email_number . ' appears to be a reply');
            $first_message_id = !empty($references) ? explode(' ', trim($references))[0] : $in_reply_to;
            error_log('Looking for original ticket with message ID: ' . $first_message_id);

            $original_query = new WP_Query([
                'meta_key' => 'email_message_id',
                'meta_value' => $first_message_id,
                'post_type' => 'robodesk_ticket',
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if (!empty($original_query->posts)) {
                $original_post_id = $original_query->posts[0];
                error_log('Found original ticket with ID: ' . $original_post_id);

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
                        error_log('Skipping duplicate comment for ticket ID: ' . $original_post_id);
                        break;
                    }
                }

                if (!$is_duplicate) {
                    // Insert reply as comment
                    error_log('Adding comment to ticket ID: ' . $original_post_id);
                    $comment_id = wp_insert_comment([
                        'comment_post_ID' => $original_post_id,
                        'comment_author' => $from,
                        'comment_content' => $message,
                        'comment_date' => $date,
                        'comment_approved' => 1,
                    ]);

                    if (!is_wp_error($comment_id)) {
                        add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                        error_log('Successfully added comment ID: ' . $comment_id);
                        $reply_count++;
                    } else {
                        error_log('Failed to add comment: ' . $comment_id->get_error_message());
                        $error_count++;
                    }
                } else {
                    $skipped_count++;
                }

                continue; // Skip creating a new ticket for replies
            } else {
                error_log('Could not find original ticket for reply, creating new ticket instead');
            }
        }

        // Create new ticket (if it's not a reply)
        $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
        error_log('Creating new ticket from email: ' . $useremail);

        $user = get_user_by('email', $useremail);
        if (!$user) {
            error_log('User not found, creating new user with email: ' . $useremail);

            // Check if role exists
            $roles = wp_roles();
            if (!isset($roles->roles['robodesk_support_customer'])) {
                error_log('Warning: Role "robodesk_support_customer" does not exist, using "subscriber" instead');
                $role = 'subscriber';
            } else {
                $role = 'robodesk_support_customer';
            }

            $user_id = wp_insert_user([
                'user_login' => explode('@', $useremail)[0],
                'user_email' => $useremail,
                'user_pass' => wp_generate_password(),
                'role' => $role,
            ]);

            if (is_wp_error($user_id)) {
                error_log('Failed to create user: ' . $user_id->get_error_message());
                $error_count++;
                continue;
            } else {
                error_log('Created new user with ID: ' . $user_id);
            }
        } else {
            $user_id = $user->ID;
            error_log('Using existing user with ID: ' . $user_id);
        }

        // Ensure the "Email" term exists
        $term_email = term_exists('Email', 'robodesk_ticket_origin');
        $term_priority = term_exists('Medium', 'robodesk_ticket_priority');

        if (!$term_email) {
            error_log('Creating "Email" term in robodesk_ticket_origin taxonomy');
            $term_email = wp_insert_term('Email', 'robodesk_ticket_origin');
        }

        if (!$term_priority) {
            error_log('Creating "Medium" term in robodesk_ticket_priority taxonomy');
            $term_priority = wp_insert_term('Medium', 'robodesk_ticket_priority');
        }

        $term_email_id = (!is_wp_error($term_email) && isset($term_email['term_id'])) ? $term_email['term_id'] : null;
        $term_priority_id = (!is_wp_error($term_priority) && isset($term_priority['term_id'])) ? $term_priority['term_id'] : null;

        if (is_wp_error($term_email)) {
            error_log('Error creating term "Email": ' . $term_email->get_error_message());
        }

        if (is_wp_error($term_priority)) {
            error_log('Error creating term "Medium": ' . $term_priority->get_error_message());
        }

        // Create new ticket post
        error_log('Inserting new ticket post');
        $post_id = wp_insert_post([
            'post_title'   => $subject,
            'post_content' => $message,
            'post_date'    => $date,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'robodesk_ticket', // Ensure post type is specified
        ]);

        if (is_wp_error($post_id)) {
            error_log('Failed to create ticket: ' . $post_id->get_error_message());
            $error_count++;
            continue;
        }

        error_log('Created new ticket with ID: ' . $post_id);

        if ($post_id && $term_email_id) {
            $result = wp_set_post_terms($post_id, [$term_email_id], 'robodesk_ticket_origin', false);
            if (is_wp_error($result)) {
                error_log('Failed to set origin term: ' . $result->get_error_message());
            }
        }

        if ($post_id && $term_priority_id) {
            $result = wp_set_post_terms($post_id, [$term_priority_id], 'robodesk_ticket_priority', false);
            if (is_wp_error($result)) {
                error_log('Failed to set priority term: ' . $result->get_error_message());
            }
        }

        update_post_meta($post_id, '_robodesk_ticket_status', 'open');
        add_post_meta($post_id, 'email_message_id', $message_id);
        add_post_meta($post_id, 'email_from', $from);

        $processed_count++;

        // Update last fetched date for each email to prevent issues if process fails halfway
        update_option('robodesk_last_fetched_date', $date);
    }

    imap_close($email);

    // Display summary
    $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Skipped: $skipped_count, Errors: $error_count";
    error_log($summary);
    echo '<div class="updated"><p>' . esc_html($summary) . '</p></div>';
}



// Post comments as email replies
function robodesk_reply_email($comment_id, $comment_approved)
{
    if ($comment_approved) {
        $comment = get_comment($comment_id); // Get the comment object
        $post = get_post($comment->comment_post_ID); // Get the post object

        // Get the original sender's email from post meta (the person who created the ticket)
        $email_from = get_post_meta($post->ID, 'email_from', true);
        $email_message_id = get_post_meta($post->ID, 'email_message_id', true);

        // Get the email of the comment author (the person who replied)
        $comment_author_email = $comment->comment_author_email;

        // Extract clean emails if they are inside angle brackets
        preg_match('/<(.*?)>/', $email_from, $matches);
        $pure_email_from = $matches[1] ?? $email_from;

        preg_match('/<(.*?)>/', $comment_author_email, $matches);
        $pure_email_to = $matches[1] ?? $comment_author_email;

        // Validate emails
        if (!filter_var($pure_email_from, FILTER_VALIDATE_EMAIL) || !filter_var($pure_email_to, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email addresses: From - ' . $pure_email_from . ', To - ' . $pure_email_to);
            return;
        }

        // Swap emails correctly:
        // - "To" should be the original sender (the ticket creator).
        // - "Reply-To" should be the commenter's email (the person replying).
        $to = $pure_email_from;
        $reply_to = $pure_email_to;

        $subject = 'Re: ' . $post->post_title;
        $comment_content = $comment->comment_content;

        // Format the reply email
        $reply_message = "On " . date("Y-m-d H:i:s", strtotime($post->post_date)) . ", $pure_email_from wrote:\n\n" . $comment_content;

        // Construct email headers
        $headers = [
            'From: Robodesk Support <pipetest@wplocatepress.com>',
            'To: ' . $to,
            'Reply-To: ' . $reply_to,  // Ensures replies go to the right person
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8'
        ];

        // Include threading headers if available
        if ($email_message_id) {
            $headers[] = 'References: ' . $email_message_id;
            $headers[] = 'In-Reply-To: ' . $email_message_id;
        }

        // Debug: Log headers before sending
        error_log('Email To: ' . $to);
        error_log('Reply-To: ' . $reply_to);
        error_log('Email Headers: ' . print_r($headers, true));

        // Send the email
        $mail_sent = wp_mail($to, $subject, $reply_message, $headers);

        // Log results
        if (!$mail_sent) {
            error_log('Email failed to send to ' . $to);
        } else {
            error_log('Email successfully sent to ' . $to);
        }
    }
}
add_action('comment_post', 'robodesk_reply_email', 10, 2);
