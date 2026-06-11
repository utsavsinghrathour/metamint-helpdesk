<?php
// phpcs:disable WordPress.DB.SlowDBQuery
// robodesk-email-to-ticket
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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

    echo '<table class="wp-list-table widefat fixed striped  ">';
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

// Basic email cleaning function
function robodesk_clean_email_message($message)
{
    if (empty($message)) {
        return '';
    }

    // 1. If it's HTML, try to cut off the email thread at common HTML reply structures first
    if (strpos($message, '<') !== false) {
        $html_split_patterns = array(
            '/<blockquote/i',
            '/<div\s+class=["\']gmail_quote["\']/i',
            '/<div\s+id=["\']gmail_quote["\']/i',
            '/<div\s+class=["\']outlook_signature["\']/i',
            '/<div\s+class=["\']email-quote["\']/i',
            '/<hr\s+[^>]*id=["\'](?:stopSpelling|divRplyFwdMsg)["\']/i', // Outlook / Webmail horizontal rules
        );
        foreach ($html_split_patterns as $pattern) {
            $parts = preg_split($pattern, $message, 2);
            if (count($parts) > 1) {
                $message = $parts[0];
            }
        }
    }

    // 2. Remove common email reply headers in plain text or mixed text
    $split_patterns = array(
        '/[\r\n]+\s*On\s+(?:(?!\bOn\b).){1,250}?\s+wrote:\s*/i', // On ... wrote:
        '/[\r\n]+\s*-+\s*Original Message\s*-+/i',             // -----Original Message-----
        '/[\r\n]+\s*From:\s+.*[\r\n]+\s*(?:Sent|Date):\s+/i',    // Outlook style: From: ... Sent: ...
        '/[\r\n]+\s*_{32,}/',                                    // Divider lines (32+ underscores)
        '/[\r\n]+\s*-{10,}/',                                    // Divider lines (10+ dashes)
    );
    foreach ($split_patterns as $pattern) {
        $parts = preg_split($pattern, $message, 2);
        if (count($parts) > 1) {
            $message = $parts[0];
        }
    }

    // 3. Remove lines starting with ">" (quoted text)
    $message = preg_replace('/^>.*$/m', '', $message);

    // 4. Strip HTML tags
    $message = wp_strip_all_tags($message);

    // 5. Replace multiple horizontal spaces/tabs with a single space
    $message = preg_replace('/\h+/', ' ', $message);

    // 6. Replace multiple vertical whitespace/newlines with a single newline
    $message = preg_replace('/\v+/', "\n", $message);

    // 7. Clean up single-line On ... wrote: prefix if it remained at the very end of the text
    $message = preg_replace('/On\s.*wrote:\s*$/i', '', $message);

    return trim($message);
}

function robodesk_fetch_emails()
{
    rbd_log('Starting email fetch process');

    // Verify the custom post type exists
    if (!post_type_exists('robodesk_ticket')) {
        rbd_log('Custom post type "robodesk_ticket" does not exist!');
        return 'Error: Custom post type "robodesk_ticket" does not exist!';
    }

    // Check if required taxonomies exist
    if (!taxonomy_exists('robodesk_ticket_origin')) {
        rbd_log('Taxonomy "robodesk_ticket_origin" does not exist!');
        return 'Error: Taxonomy "robodesk_ticket_origin" does not exist!';
    }

    if (!taxonomy_exists('robodesk_ticket_priority')) {
        rbd_log('Taxonomy "robodesk_ticket_priority" does not exist!');
        return 'Error: Taxonomy "robodesk_ticket_priority" does not exist!';
    }

    // Get last fetched date

    $last_fetched_date = get_option('robodesk_last_fetched_date', '2021-01-01 00:00:00'); // Default to a past date if not set( as we are also using the ticket id to retrieve the last mail or reply    )
    rbd_log('Last fetched date: ' . $last_fetched_date);
    rbd_log('Robodesk: Last email fetched date/time: ' . $last_fetched_date);

    $last_fetched_gmt = get_gmt_from_date($last_fetched_date, 'Y-m-d H:i:s');
    $since_date = gmdate('d-M-Y H:i:s', strtotime($last_fetched_gmt));
    rbd_log('IMAP since date format: ' . $since_date);




    // // Retrieve IMAP settings from options
    $settings = robodesk_get_imap_settings();

    // Construct the IMAP connection string
    $hostname = '{' . $settings['hostname'] . ':' . $settings['port'] . '/imap/ssl}INBOX';
    $username = $settings['username'];
    $password = $settings['password'];


    rbd_log('Attempting to connect to IMAP server: ' . $hostname);

    // Connect to email with error handling
    $email = @imap_open($hostname, $username, $password);
    if (!$email) {
        $error_message = 'Cannot connect to email: ' . imap_last_error();
        rbd_log($error_message);
        return 'Error: ' . $error_message;
    }

    rbd_log('Successfully connected to IMAP server');
    // Check if the mailbox is empty
    try {
        // Fetch emails received after last fetched date
        $search_criteria = 'SINCE "' . $since_date . '"';
        rbd_log('Search criteria: ' . $search_criteria);

        $emails = imap_search($email, $search_criteria);

        if (!$emails) {
            $notice = 'No new emails found since ' . $since_date;
            rbd_log($notice);
            return $notice; // ✅ Return this message, don't echo
        }

        rbd_log('Found ' . count($emails) . ' emails to process');

        // Sort emails oldest to newest
        sort($emails);

        // Pre-fetch existing message IDs to prevent duplicates
        $existing_message_ids = get_posts([
            'post_type' => 'robodesk_ticket',
            'meta_key' => 'email_message_id',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'email_message_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        $existing_message_ids = array_map(function ($post_id) {
            return get_post_meta($post_id, 'email_message_id', true);
        }, $existing_message_ids);

        $processed_count = 0;
        $skipped_count = 0;
        $reply_count = 0;
        $error_count = 0;

        foreach ($emails as $email_number) {
            rbd_log('Processing email #' . $email_number);

            $headerInfo = imap_headerinfo($email, $email_number);
            if (!$headerInfo) {
                rbd_log('Failed to get header info for email #' . $email_number);
                $error_count++;
                continue;
            }

            $raw_headers = imap_fetchheader($email, $email_number) ?: '';
            $email_source_context = robodesk_collect_email_source_context($raw_headers);

            $message_id = $headerInfo->message_id ?? '';
            if (empty($message_id)) {
                rbd_log('Email #' . $email_number . ' has no message ID, skipping');
                $skipped_count++;
                continue;
            }

            $date_utc = gmdate("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $date = get_date_from_gmt($date_utc, 'Y-m-d H:i:s');
            rbd_log('Email date: ' . $date . ', Last fetched: ' . $last_fetched_date);

            // Skip emails older than or equal to last fetched date
            if (strtotime($date) <= strtotime($last_fetched_date)) {
                rbd_log('Skipping email #' . $email_number . ' as it\'s older than last fetch date');
                $skipped_count++;
                continue;
            }

            // Check for duplicate tickets
            if (in_array($message_id, $existing_message_ids)) {
                rbd_log('Skipping email #' . $email_number . ' as a ticket already exists with message ID: ' . $message_id);
                $skipped_count++;
                continue;
            }

            // if email is from Mail Delivery System of email Mailer-Daemon@antispam.mailspamprotection.com  skip it
            if (stripos($headerInfo->fromaddress ?? '', 'Mailer-Daemon') !== false || stripos($headerInfo->fromaddress ?? '', 'Mail Delivery System') !== false) {
                rbd_log('Skipping email #' . $email_number . ' as it is from Mail Delivery System');
                $skipped_count++;
                continue;
            }

            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            //Vr Rv <vriasuhn@gmail.com> remove the email address
            $from = preg_replace('/<.*?>/', '', $from);
            $from = trim($from);
            $useremail = '';
            if (!empty($headerInfo->from[0]->mailbox) && !empty($headerInfo->from[0]->host)) {
                $useremail = sanitize_email($headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host);
            }
            if ($useremail === '' && preg_match('/<([^>]+)>/', (string) ($headerInfo->fromaddress ?? ''), $from_email_match)) {
                $useremail = sanitize_email($from_email_match[1]);
            }
            // only showing the name part 
            $subject = $headerInfo->subject ?? 'No Subject';
            rbd_log('Email from: ' . $from . ', Subject: ' . $subject);

            // Fetch email body with robust MIME handling and transfer encoding decoding
            $structure = imap_fetchstructure($email, $email_number);
            $message = '';
            $encoding = 0;

            if ($structure->type == 1 && isset($structure->parts)) { // Multipart
                foreach ($structure->parts as $part_number => $part) {
                    if ($part->subtype == 'PLAIN') {
                        $message = imap_fetchbody($email, $email_number, $part_number + 1);
                        $encoding = $part->encoding;
                        break;
                    }
                }
            }

            if (empty($message)) {
                // Fallback to default body
                $message = imap_fetchbody($email, $email_number, 1.1);
                $encoding = $structure->parts[0]->parts[0]->encoding ?? 0;
                if (empty($message)) {
                    $message = imap_fetchbody($email, $email_number, 1);
                    $encoding = $structure->encoding ?? 0;
                }
            }

            // Decode message based on transfer encoding (3 = Base64, 4 = Quoted-Printable)
            if ($encoding == 3) {
                $message = base64_decode($message);
            } elseif ($encoding == 4) {
                $message = quoted_printable_decode($message);
            }

            if (empty($message)) {
                rbd_log('Could not retrieve body for email #' . $email_number);
                $error_count++;
                continue;
            }

            $message = robodesk_clean_email_message($message);

            // Handle replies for parent tickets
            $in_reply_to = $headerInfo->in_reply_to ?? '';
            $references = $headerInfo->references ?? '';
            $original_post_id = null;

            if (!empty($in_reply_to) || !empty($references)) {
                rbd_log('Email #' . $email_number . ' appears to be a reply');
                $reference_ids = !empty($references) ? explode(' ', trim($references)) : [$in_reply_to];
                foreach ($reference_ids as $ref_id) {
                    if (!empty($ref_id)) {
                        $original_query = new WP_Query([
                            'meta_key' => 'email_message_id',
                            'meta_value' => $ref_id,
                            'post_type' => 'robodesk_ticket',
                            'posts_per_page' => 1,
                            'fields' => 'ids',
                        ]);
                        if (!empty($original_query->posts)) {
                            $original_post_id = $original_query->posts[0];
                            rbd_log('Found original ticket with ID: ' . $original_post_id);
                            break;
                        }
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
                            rbd_log('Skipping duplicate comment for ticket ID: ' . $original_post_id);
                            break;
                        }
                    }

                    if (!$is_duplicate) {
                        rbd_log('Adding comment to ticket ID: ' . $original_post_id);
                        $comment_user_id = 0;
                        if ($useremail !== '') {
                            $reply_user = get_user_by('email', $useremail);
                            if ($reply_user && !empty($reply_user->ID)) {
                                $comment_user_id = (int) $reply_user->ID;
                            }
                        }
                        if ($comment_user_id <= 0 && function_exists('robodesk_get_ticket_customer_user_id')) {
                            $comment_user_id = (int) robodesk_get_ticket_customer_user_id($original_post_id);
                        }

                        $comment_id = wp_insert_comment([
                            'comment_post_ID' => $original_post_id,
                            'comment_author' => $from,
                            'comment_author_email' => $useremail,
                            'user_id' => $comment_user_id,
                            'comment_content' => $message,
                            'comment_date' => $date,
                            'comment_approved' => 1,
                            'comment_type' => 'ticket_replies',
                        ]);

                        if (!is_wp_error($comment_id)) {
                            add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                            add_comment_meta($comment_id, 'comment_source', 'email', true);
                            add_comment_meta($comment_id, 'author_type', 'customer', true);
                            add_comment_meta($comment_id, '_robodesk_source_ip', $email_source_context['ip'], true);
                            add_comment_meta($comment_id, '_robodesk_source_country', $email_source_context['country'], true);
                            add_comment_meta($comment_id, '_robodesk_source_browser', $email_source_context['browser'], true);
                            robodesk_store_ticket_source_meta($original_post_id, $email_source_context, $comment_id);
                            rbd_log('Successfully added comment ID: ' . $comment_id);
                            $reply_count++;
                        } else {
                            rbd_log('Failed to add comment: ' . $comment_id->get_error_message());
                            $error_count++;
                        }
                    } else {
                        $skipped_count++;
                    }

                    // Update last fetched date
                    update_option('robodesk_last_fetched_date', $date); // Update last fetched date to current email date
                    rbd_log('Robodesk: Updated last fetched email date/time to: ' . $date);
                    continue; // Skip creating a new ticket for replies
                } else {
                    rbd_log('Could not find original ticket for reply, creating new ticket instead');
                }
            }

            // Create new ticket
            rbd_log('Creating new ticket from email: ' . $useremail);

            // Check if email should be filtered before creating ticket
            if (!apply_filters('robodesk_before_create_ticket_from_email', true, [
                'from' => $useremail,
                'subject' => $subject,
                'message' => $message
            ])) {
                rbd_log('Email from ' . $useremail . ' was filtered by exclusion rules');
                $skipped_count++;
                continue;
            }

            $user = get_user_by('email', $useremail);
            if (!$user) {
                rbd_log('User not found, creating new user with email: ' . $useremail);

                // Check if role exists
                $roles = wp_roles();
                $role = isset($roles->roles['robodesk_support_customer']) ? 'robodesk_support_customer' : 'subscriber';

                // Ensure unique user_login
                $base_login = explode('@', $useremail)[0];
                $user_login = $base_login;
                $counter = 1;
                while (username_exists($user_login)) {
                    $user_login = $base_login . '_' . $counter++;
                }

                $user_id = wp_insert_user([
                    'user_login' => $user_login,
                    'user_email' => $useremail,
                    'user_pass' => wp_generate_password(),
                    'role' => $role,
                ], true);
                // send notification to the new user
                wp_send_new_user_notifications($user_id, 'user');


                if (is_wp_error($user_id)) {
                    rbd_log('Failed to create user: ' . $user_id->get_error_message());
                    $error_count++;
                    continue;
                } else {
                    rbd_log('Created new user with ID: ' . $user_id);
                }
            } else {
                $user_id = $user->ID;
                rbd_log('Using existing user with ID: ' . $user_id);
            }

            // Ensure taxonomy terms exist

            $term_email = term_exists('Email', 'robodesk_ticket_origin');
            $term_priority = term_exists('Medium', 'robodesk_ticket_priority');

            if (!$term_email) {
                rbd_log('Creating "Email" term in robodesk_ticket_origin taxonomy');
                $term_email = wp_insert_term('Email', 'robodesk_ticket_origin');
            }

            if (!$term_priority) {
                rbd_log('Creating "Medium" term in robodesk_ticket_priority taxonomy');
                $term_priority = wp_insert_term('Medium', 'robodesk_ticket_priority');
            }

            if (is_wp_error($term_email) || is_wp_error($term_priority)) {
                rbd_log('Cannot proceed without required terms');
                $error_count++;
                continue;
            }

            $term_email_id = is_array($term_email) ? $term_email['term_id'] : $term_email;
            $term_priority_id = is_array($term_priority) ? $term_priority['term_id'] : $term_priority;

            // Create new ticket post
            rbd_log('Inserting new ticket post');
            $post_id = wp_insert_post([
                'post_title'   => $subject,
                'post_content' => $message,
                'post_date'    => $date,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_type'    => 'robodesk_ticket',
                // add post meta of assignee

            ]);

            if (!is_wp_error($post_id)) {
                // Assign the ticket to a support staff member
                $assignee = auto_assign_ticket_to_support_staff(true);

                // If an assignee was found, add it as post meta
                if ($assignee) {
                    add_post_meta($post_id, 'ticket_assignee', $assignee);
                    robodesk_store_ticket_assignee_id($post_id, $assignee);
                } else {
                    // Optional: Handle case where no assignee was found (e.g., log or set default)
                    rbd_log('Robodesk: Failed to assign ticket ID ' . $post_id . ' - no support staff available.');
                }
            } else {
                // Handle error if ticket creation failed
                rbd_log('Robodesk: Failed to create ticket: ' . $post_id->get_error_message());
            }

            //if post is created successfully add a comment with email message
            if ($post_id && !is_wp_error($post_id)) {
                rbd_log('Successfully created ticket post with ID: ' . $post_id);
                // Add a comment with the email message
                $comment_id = wp_insert_comment([
                    'comment_post_ID' => $post_id,
                    'comment_author' => $from,
                    'comment_author_email' => $useremail,
                    'user_id' => $user_id,
                    'comment_content' => $message,
                    'comment_date' => $date,
                    'comment_approved' => 1,
                    'comment_type' => 'ticket_replies',
                    'is_robodesk_note'     => true, // Custom comment type for ticket replies
                ]);
                if (!is_wp_error($comment_id)) {
                    add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                    add_comment_meta($comment_id, 'comment_source', 'email', true);
                    add_comment_meta($comment_id, 'author_type', 'customer', true);
                    add_comment_meta($comment_id, 'rbd_suppress_notification', 1, true);
                    rbd_log('Added comment ID: ' . $comment_id . ' to ticket ID: ' . $post_id);
                } else {
                    rbd_log('Failed to add comment: ' . $comment_id->get_error_message());
                }
            } else {
                rbd_log('Failed to create ticket post: ' . (is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error'));
            }

            if (is_wp_error($post_id)) {
                rbd_log('Failed to create ticket: ' . $post_id->get_error_message());
                $error_count++;
                continue;
            }

            rbd_log('Created new ticket with ID: ' . $post_id);



            if ($term_priority_id) {
                $result = wp_set_post_terms($post_id, [$term_priority_id], 'robodesk_ticket_priority', false);
                if (is_wp_error($result)) {
                    rbd_log('Failed to set priority term: ' . $result->get_error_message());
                }
            }

            // Update post meta
            update_post_meta($post_id, 'robodesk_ticket_origin', 'email');
            update_post_meta($post_id, '_robodesk_ticket_status', 'open');
            add_post_meta($post_id, 'email_message_id', $message_id);
            add_post_meta($post_id, 'email_from', $from);
            robodesk_store_ticket_source_meta($post_id, $email_source_context);

            robodesk_notify_ticket_created($post_id, 'email');

            $processed_count++;

            // Update last fetched date
            update_option('robodesk_last_fetched_date', $date);
            rbd_log('Robodesk: Updated last fetched email date/time to: ' . $date);
        }

        // Display summary
        // $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Skipped: $skipped_count, Errors: $error_count";
        $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Errors: $error_count";

        rbd_log($summary);

        return $summary; // ✅ Return it

    } finally {
        // Ensure IMAP connection is closed
        if ($email) {
            imap_close($email);
        }
    }
}


// Function to display the IMAP settings page
function robodesk_imap_settings_callback()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'robodesk'));
    }

    // Handle form submission for saving settings
    if (isset($_POST['robodesk_imap_save'])) {
        check_admin_referer('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce');

        $hostname = isset($_POST['robodesk_imap_hostname']) ? sanitize_text_field(wp_unslash($_POST['robodesk_imap_hostname'])) : '';
        $port     = isset($_POST['robodesk_imap_port']) ? intval(wp_unslash($_POST['robodesk_imap_port'])) : 143;
        $username = isset($_POST['robodesk_imap_username']) ? sanitize_email(wp_unslash($_POST['robodesk_imap_username'])) : '';
        $password = isset($_POST['robodesk_imap_password']) ? sanitize_text_field(wp_unslash($_POST['robodesk_imap_password'])) : '';

        $settings = array(
            'hostname' => $hostname,
            'port'     => $port,
            'username' => $username,
            'password' => $password,
        );

        update_option('robodesk_imap_settings', $settings);

        echo '<div class="updated"><p>IMAP settings saved successfully.</p></div>';
    }

    // Handle connection test
    if (isset($_POST['robodesk_imap_test'])) {
        check_admin_referer('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce');

        $hostname_val = isset($_POST['robodesk_imap_hostname']) ? sanitize_text_field(wp_unslash($_POST['robodesk_imap_hostname'])) : '';
        $port_val     = isset($_POST['robodesk_imap_port']) ? intval(wp_unslash($_POST['robodesk_imap_port'])) : 143;
        $username     = isset($_POST['robodesk_imap_username']) ? sanitize_email(wp_unslash($_POST['robodesk_imap_username'])) : '';
        $password     = isset($_POST['robodesk_imap_password']) ? sanitize_text_field(wp_unslash($_POST['robodesk_imap_password'])) : '';

        $hostname = '{' . $hostname_val . ':' . $port_val . '/imap/ssl}INBOX';

        $mailbox = @imap_open($hostname, $username, $password);
        if ($mailbox) {
            echo '<div class="updated"><p>IMAP connection successful!</p></div>';
            imap_close($mailbox);
        } else {
            echo '<div class="error"><p>' .
                esc_html__('IMAP connection failed: ', 'robodesk') .
                esc_html(imap_last_error()) .
                '</p></div>';
        }
    }

    // Load saved settings or use provided defaults
    $settings = robodesk_get_imap_settings();
    if (empty($settings['hostname'])) {
        $settings = array(
            'hostname' => 'email.example.com',
            'port'     => 993,
            'username' => 'password',
            'password' => '',
        );
    }

    $hostname = $settings['hostname'];
    $port     = $settings['port'];
    $username = $settings['username'];
    $password = $settings['password'];
?>

    <div class="wrap">
        <h1>Robodesk IMAP Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="robodesk_imap_hostname">IMAP Hostname</label></th>
                    <td><input type="text" name="robodesk_imap_hostname" id="robodesk_imap_hostname" value="<?php echo esc_attr($hostname); ?>" class="regular-text">
                        <p class="description">Example: <code>imappro.zoho.com</code></p>
                        <p class="description">For DEV: <code>{imappro.zoho.com:{port}/imap/ssl}INBOX</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="robodesk_imap_port">IMAP Port</label></th>
                    <td><input type="number" name="robodesk_imap_port" id="robodesk_imap_port" value="<?php echo esc_attr($port); ?>" class="small-text">
                        <p class="description">Default: <code>993</code> for SSL</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="robodesk_imap_username">IMAP Username</label></th>
                    <td><input type="email" name="robodesk_imap_username" id="robodesk_imap_username" value="<?php echo esc_attr($username); ?>" class="regular-text">
                        <p class="description">Your email address , from where email will be fetched</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="robodesk_imap_password">IMAP Password</label></th>
                    <td><input type="password" name="robodesk_imap_password" id="robodesk_imap_password" value="<?php echo esc_attr($password); ?>" class="regular-text">
                        <p class="description"> Password</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="robodesk_imap_save" class="button button-primary" value="Save Settings">
                <input type="submit" name="robodesk_imap_test" class="button button-secondary" value="Test Connection">
            </p>
        </form>
    </div>

<?php
}


// now the email ticket via comment is working

// Disable default WordPress notifications
add_filter('notify_post_author', '__return_false');
add_filter('comment_notification_recipients', '__return_empty_array');
add_filter('comment_moderation_recipients', '__return_empty_array');
add_filter('send_password_change_email', '__return_false');
remove_action('after_password_reset', 'wp_password_change_notification');

// Define fallback logging function
if (!function_exists('rbd_log')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    function rbd_log($message)
    {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log("[Robodesk] $message");
    }
}
