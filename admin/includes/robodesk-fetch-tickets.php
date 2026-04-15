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
    // Remove quoted replies, signatures, and excessive whitespace
    $message = preg_replace('/^>.*$/m', '', $message); // Remove lines starting with ">"
    // $message = preg_replace('/--\s*Signature.*$/s', '', $message); // Remove signature
    $message = strip_tags($message); // Remove HTML tags
    // dont remove line breaks
    $message = preg_replace('/\s+/', ' ', $message); // Replace multiple spaces with a single space
    $message = preg_replace('/\n+/', "\n", $message); // Replace multiple newlines with a single newline
    //remove On Fri, 25 Jul 2025 at 12:23, Vr Rv wrote:
    $message = preg_replace('/On\s.*wrote:\s*$/', '', $message);

    return trim($message);
}

function robodesk_fetch_emails()
{
    // Set timezone to WordPress setting
    date_default_timezone_set(wp_timezone_string());
    error_log('Starting email fetch process');

    // Verify the custom post type exists
    if (!post_type_exists('robodesk_ticket')) {
        error_log('Custom post type "robodesk_ticket" does not exist!');
        return 'Error: Custom post type "robodesk_ticket" does not exist!';
    }

    // Check if required taxonomies exist
    if (!taxonomy_exists('robodesk_ticket_origin')) {
        error_log('Taxonomy "robodesk_ticket_origin" does not exist!');
        return 'Error: Taxonomy "robodesk_ticket_origin" does not exist!';
    }

    if (!taxonomy_exists('robodesk_ticket_priority')) {
        error_log('Taxonomy "robodesk_ticket_priority" does not exist!');
        return 'Error: Taxonomy "robodesk_ticket_priority" does not exist!';
    }

    // Get last fetched date

    $last_fetched_date = get_option('robodesk_last_fetched_date', '2021-01-01 00:00:00'); // Default to a past date if not set( as we are also using the ticket id to retrieve the last mail or reply    )
    error_log('Last fetched date: ' . $last_fetched_date);
    rbd_log('Robodesk: Last email fetched date/time: ' . $last_fetched_date);

    // Convert to IMAP-compatible date with time
    $since_date = date('d-M-Y H:i:s', strtotime($last_fetched_date));
    error_log('IMAP since date format: ' . $since_date);




    // // Retrieve IMAP settings from options
    $settings = get_option('robodesk_imap_settings', array(
        'hostname' => '',
        'port'     => 993,
        'username' => '',
        'password' => '',
    ));

    // Construct the IMAP connection string
    $hostname = '{' . $settings['hostname'] . ':' . $settings['port'] . '/imap/ssl}INBOX';
    $username = $settings['username'];
    $password = $settings['password'];


    error_log('Attempting to connect to IMAP server: ' . $hostname);

    // Connect to email with error handling
    $email = @imap_open($hostname, $username, $password);
    if (!$email) {
        $error_message = 'Cannot connect to email: ' . imap_last_error();
        error_log($error_message);
        return 'Error: ' . $error_message;
    }

    error_log('Successfully connected to IMAP server');
    // Check if the mailbox is empty
    try {
        // Fetch emails received after last fetched date
        $search_criteria = 'SINCE "' . $since_date . '"';
        error_log('Search criteria: ' . $search_criteria);

        $emails = imap_search($email, $search_criteria);

        if (!$emails) {
            $notice = 'No new emails found since ' . $since_date;
            error_log($notice);
            return $notice; // ✅ Return this message, don't echo
        }

        error_log('Found ' . count($emails) . ' emails to process');

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

            // Skip emails older than or equal to last fetched date
            if (strtotime($date) <= strtotime($last_fetched_date)) {
                error_log('Skipping email #' . $email_number . ' as it\'s older than last fetch date');
                $skipped_count++;
                continue;
            }

            // Check for duplicate tickets
            if (in_array($message_id, $existing_message_ids)) {
                error_log('Skipping email #' . $email_number . ' as a ticket already exists with message ID: ' . $message_id);
                $skipped_count++;
                continue;
            }

            // if email is from Mail Delivery System of email Mailer-Daemon@antispam.mailspamprotection.com  skip it
            if (stripos($headerInfo->fromaddress ?? '', 'Mailer-Daemon') !== false || stripos($headerInfo->fromaddress ?? '', 'Mail Delivery System') !== false) {
                error_log('Skipping email #' . $email_number . ' as it is from Mail Delivery System');
                $skipped_count++;
                continue;
            }

            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            //Vr Rv <vriasuhn@gmail.com> remove the email address
            $from = preg_replace('/<.*?>/', '', $from);
            $from = trim($from);
            // only showing the name part 
            $subject = $headerInfo->subject ?? 'No Subject';
            error_log('Email from: ' . $from . ', Subject: ' . $subject);

            // Fetch email body with robust MIME handling( we assume plain text for simplicity)
            $structure = imap_fetchstructure($email, $email_number);
            $message = '';
            if ($structure->type == 1 && isset($structure->parts)) { // Multipart
                foreach ($structure->parts as $part_number => $part) {
                    if ($part->subtype == 'PLAIN') {
                        $message = imap_fetchbody($email, $email_number, $part_number + 1);
                        break;
                    }
                }
            }
            if (empty($message)) {
                // Fallback to default body
                $message = imap_fetchbody($email, $email_number, 1.1);
                if (empty($message)) {
                    $message = imap_fetchbody($email, $email_number, 1);
                }
            }

            if (empty($message)) {
                error_log('Could not retrieve body for email #' . $email_number);
                $error_count++;
                continue;
            }

            $message = robodesk_clean_email_message($message);

            // Handle replies for parent tickets
            $in_reply_to = $headerInfo->in_reply_to ?? '';
            $references = $headerInfo->references ?? '';
            $original_post_id = null;

            if (!empty($in_reply_to) || !empty($references)) {
                error_log('Email #' . $email_number . ' appears to be a reply');
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
                            error_log('Found original ticket with ID: ' . $original_post_id);
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
                            error_log('Skipping duplicate comment for ticket ID: ' . $original_post_id);
                            break;
                        }
                    }

                    if (!$is_duplicate) {
                        error_log('Adding comment to ticket ID: ' . $original_post_id);
                        $comment_id = wp_insert_comment([
                            'comment_post_ID' => $original_post_id,
                            'comment_author' => $from,
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
                            error_log('Successfully added comment ID: ' . $comment_id);
                            $reply_count++;
                        } else {
                            error_log('Failed to add comment: ' . $comment_id->get_error_message());
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
                    error_log('Could not find original ticket for reply, creating new ticket instead');
                }
            }

            // Create new ticket
            $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            error_log('Creating new ticket from email: ' . $useremail);

            // Check if email should be filtered before creating ticket
            if (!apply_filters('robodesk_before_create_ticket_from_email', true, [
                'from' => $useremail,
                'subject' => $subject,
                'message' => $message
            ])) {
                error_log('Email from ' . $useremail . ' was filtered by exclusion rules');
                $skipped_count++;
                continue;
            }

            $user = get_user_by('email', $useremail);
            if (!$user) {
                error_log('User not found, creating new user with email: ' . $useremail);

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

            // Ensure taxonomy terms exist

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

            if (is_wp_error($term_email) || is_wp_error($term_priority)) {
                error_log('Cannot proceed without required terms');
                $error_count++;
                continue;
            }

            $term_email_id = is_array($term_email) ? $term_email['term_id'] : $term_email;
            $term_priority_id = is_array($term_priority) ? $term_priority['term_id'] : $term_priority;

            // Create new ticket post
            error_log('Inserting new ticket post');
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
                $assignee = auto_assign_ticket_to_support_staff();

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
                error_log('Successfully created ticket post with ID: ' . $post_id);
                // Add a comment with the email message
                $comment_id = wp_insert_comment([
                    'comment_post_ID' => $post_id,
                    'comment_author' => $from,
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
                    error_log('Added comment ID: ' . $comment_id . ' to ticket ID: ' . $post_id);
                } else {
                    error_log('Failed to add comment: ' . $comment_id->get_error_message());
                }
            } else {
                error_log('Failed to create ticket post: ' . (is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error'));
            }

            if (is_wp_error($post_id)) {
                error_log('Failed to create ticket: ' . $post_id->get_error_message());
                $error_count++;
                continue;
            }

            error_log('Created new ticket with ID: ' . $post_id);



            if ($term_priority_id) {
                $result = wp_set_post_terms($post_id, [$term_priority_id], 'robodesk_ticket_priority', false);
                if (is_wp_error($result)) {
                    error_log('Failed to set priority term: ' . $result->get_error_message());
                }
            }

            // Update post meta
            update_post_meta($post_id, 'robodesk_ticket_origin', 'email');
            update_post_meta($post_id, '_robodesk_ticket_status', 'open');
            add_post_meta($post_id, 'email_message_id', $message_id);
            add_post_meta($post_id, 'email_from', $from);

            robodesk_notify_ticket_created($post_id, 'email');

            $processed_count++;

            // Update last fetched date
            update_option('robodesk_last_fetched_date', $date);
            rbd_log('Robodesk: Updated last fetched email date/time to: ' . $date);
        }

        // Display summary
        // $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Skipped: $skipped_count, Errors: $error_count";
        $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Errors: $error_count";

        error_log($summary);

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
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission for saving settings
    if (isset($_POST['robodesk_imap_save'])) {
        check_admin_referer('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce');

        $settings = array(
            'hostname' => sanitize_text_field($_POST['robodesk_imap_hostname']),
            'port'     => intval($_POST['robodesk_imap_port']),
            'username' => sanitize_email($_POST['robodesk_imap_username']),
            'password' => sanitize_text_field($_POST['robodesk_imap_password']),
        );

        update_option('robodesk_imap_settings', $settings);

        echo '<div class="updated"><p>IMAP settings saved successfully.</p></div>';
    }

    // Handle connection test
    if (isset($_POST['robodesk_imap_test'])) {
        check_admin_referer('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce');

        $hostname = '{' . $_POST['robodesk_imap_hostname'] . ':' . $_POST['robodesk_imap_port'] . '/imap/ssl}INBOX';
        $username = $_POST['robodesk_imap_username'];
        $password = $_POST['robodesk_imap_password'];

        $mailbox = @imap_open($hostname, $username, $password);
        if ($mailbox) {
            echo '<div class="updated"><p>IMAP connection successful!</p></div>';
            imap_close($mailbox);
        } else {
            echo '<div class="error"><p>IMAP connection failed: ' . imap_last_error() . '</p></div>';
        }
    }

    // Load saved settings or use provided defaults
    $settings = get_option('robodesk_imap_settings', array(
        'hostname' => 'email.example.com', // Default hostname
        'port'     => 993,
        'username' => 'password',
        'password' => '', // Avoid hardcoding password
    ));

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
    function rbd_log($message)
    {
        error_log("[Robodesk] $message");
    }
}
