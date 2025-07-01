<?php

// Woocommerce modifications
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    require_once('robodesk-woocommerce.php');
}
// for comments
require_once('robodesk-comments.php');

// Function to send notification email
function robodesk_send_ticket_update_email($ticket_id, $comment_content, $update_type)
{
    $ticket = get_post($ticket_id);
    if (!$ticket) return;
    $customer = get_user_by('id', $ticket->post_author);
    if (!$customer) return;
    $ticket_title = get_the_title($ticket_id);
    $ticket_link = get_permalink($ticket_id);

    $to = $customer->user_email;
    $subject = sprintf('[Robodesk] Ticket #%d Updated', $ticket_id);

    $message = sprintf(
        "Hello %s,\n\nYour ticket #%d (%s) has been updated.\n\n",
        $customer->display_name,
        $ticket_id,
        $ticket_title
    );

    if ($update_type == 'status') {
        $message .= "The status has been updated.\n\n";
    } elseif ($update_type == 'priority') {
        $message .= "The priority has been updated.\n\n";
    } elseif ($update_type == 'comment') {
        $message .= "A new comment has been added:\n\n";
        $message .= $comment_content . "\n\n";
    }

    $message .= "View the ticket here: " . $ticket_link . "\n\nThank you,\nThe Robodesk Team";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}

global $wpdb;
$robodesk_notifications_table = $wpdb->prefix . 'robodesk_notifications';

// @utsav sir code don remove now
// Create notification table on plugin activation
// register_activation_hook(__FILE__, 'robodesk_create_notifications_table');
// function robodesk_create_notifications_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'robodesk_notifications';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id mediumint(9) NOT NULL AUTO_INCREMENT,
//         notification_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
//         user_id bigint(20) unsigned NOT NULL,
//         message text NOT NULL,
//         ticket_id bigint(20) unsigned,
//         status varchar(20) DEFAULT 'unread' NOT NULL,
//         created_by bigint(20) unsigned NOT NULL,
//         PRIMARY KEY  (id)
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// } 
//@utsav sir code do not remove now
// function robodesk_create_ticket_notes_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'robodesk_ticket_notes';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         ticket_id BIGINT(20) UNSIGNED NOT NULL,
//         user_id BIGINT(20) UNSIGNED NOT NULL,
//         note_content LONGTEXT NOT NULL,
//         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
//         is_private TINYINT(1) NOT NULL DEFAULT 0,  -- Added is_private column
//         type VARCHAR(255) NOT NULL DEFAULT 'user_note', -- Added type column
//         status VARCHAR(20) NOT NULL DEFAULT 'unread',
//         PRIMARY KEY (id),
//         KEY ticket_id (ticket_id),
//         KEY user_id (user_id),
//         CONSTRAINT fk_robodesk_ticket_notes_faq FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
//         CONSTRAINT fk_robodesk_ticket_notes_user FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

//     dbDelta($sql);

// }
// register_activation_hook(__FILE__, 'robodesk_create_ticket_notes_table');

//needed to do this coz itz not working on plugin activation hook
function robodesk_maybe_create_ticket_notes_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_ticket_notes';


    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            note_content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_private TINYINT(1) NOT NULL DEFAULT 0,
            type VARCHAR(255) NOT NULL DEFAULT 'user_note',
            status VARCHAR(20) NOT NULL DEFAULT 'unread',
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY user_id (user_id),
            CONSTRAINT fk_robodesk_ticket_notes_faq FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
            CONSTRAINT fk_robodesk_ticket_notes_user FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Call the function on WordPress initialization
add_action('init', 'robodesk_maybe_create_ticket_notes_table');

function robodesk_maybe_create_notifications_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            notification_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            message text NOT NULL,
            ticket_id bigint(20) unsigned,
            status varchar(20) DEFAULT 'unread' NOT NULL,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Call the function on WordPress initialization
add_action('init', 'robodesk_maybe_create_notifications_table');



// Function to create a notification
function robodesk_create_notification($user_id, $message, $ticket_id, $created_by)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';

    $wpdb->insert(
        $table_name,
        array(
            'notification_date' => current_time('mysql'),
            'user_id' => $user_id,
            'message' => $message,
            'ticket_id' => $ticket_id,
            'status' => 'unread',
            'created_by' => $created_by,
        )
    );
}

// Function to get notification count
function robodesk_get_notification_count($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'unread'", $user_id));
    return $count;
}

// Function to get notifications
function robodesk_get_notifications($user_id, $limit = 10)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_notifications';
    $notifications = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY notification_date DESC LIMIT %d", $user_id, $limit));

    foreach ($notifications as $notification) {
        $wpdb->update(
            $table_name,
            array(
                'status' => 'read',
            ),
            array('id' => $notification->id)
        );
    }

    return $notifications;
}


function robodesk_credentials_page()
{
    if (is_user_logged_in()) {
        global $wpdb;
        $credentials_table = $wpdb->prefix . 'robodesk_credentials';
        $user_id = get_current_user_id();
        $credentials = $wpdb->get_results("SELECT * FROM $credentials_table WHERE user_id = $user_id ORDER BY name ASC");
        ob_start();
?>
        <div class="robodesk-container">
            <h2>Credentials Vault</h2>

            <button id="add-credential-button">Add Credential</button>

            <div id="credential-form" style="display: none;">
                <h3>Add New Credential</h3>
                <form id="robodesk-add-credential">
                    <label for="credential_name">Name:</label><br>
                    <input type="text" id="credential_name" name="credential_name" required><br><br>

                    <label for="credential_username">Username:</label><br>
                    <input type="text" id="credential_username" name="credential_username" required><br><br>

                    <label for="credential_password">Password:</label><br>
                    <input type="password" id="credential_password" name="credential_password" required><br><br>

                    <label for="credential_url">URL:</label><br>
                    <input type="text" id="credential_url" name="credential_url"><br><br>

                    <label for="credential_notes">Notes:</label><br>
                    <textarea id="credential_notes" name="credential_notes"></textarea><br><br>

                    <input type="hidden" name="action" value="robodesk_add_credential">
                    <?php wp_nonce_field('robodesk_add_credential_nonce'); ?>
                    <input type="submit" value="Add">
                </form>
            </div>
            <div class="robodesk-my-credentials">
                <h2>My Credentials</h3>
                    <?php if ($credentials): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>URL</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($credentials as $credential): ?>
                                    <tr>
                                        <td><?php echo esc_html($credential->name); ?></td>
                                        <td><?php echo esc_html($credential->username); ?></td>
                                        <td><span class="password-hidden">********</span>
                                            <?php $enc =  robodesk_encrypt($credential->password, 'robodesk'); ?>
                                            <button class="show-password" data-password="
                                    <?php echo robodesk_decrypt($credential->password, 'robodesk');
                                    ?>">Show</button>

                                        </td>

                                        <td><a href="<?php echo esc_url($credential->url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($credential->url); ?></a></td>
                                        <td><?php echo esc_html($credential->notes); ?></td>
                                        <td>
                                            <button class="delete-credential" data-credential-id="<?php echo esc_attr($credential->id); ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No credentials saved yet.</p>
                    <?php endif; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    } else {
        return '<div class="robodesk-container"><p>Please <a href="' . wp_login_url(get_permalink('login')) . '">login</a> to view your credentials.</p></div>';
    }
}

/**
 * Displaying a table to show open tickets after my-account page 
 */
add_action('woocommerce_after_my_account', 'add_custom_table_to_my_account');
function add_custom_table_to_my_account()
{

    if (is_user_logged_in()) {
        global $wp;
        $user_id = get_current_user_id();

        $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
        $args = array(
            'post_type' => 'robodesk_ticket',
            'author' => $user_id,
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_robodesk_ticket_status',
                    'value' => 'open',
                    'compare' => 'like'
                )
            )
        );

        $query = new WP_Query($args);
        $ticket_url = add_query_arg(array('robodesk_page' => 'my-tickets', 'tid' => get_the_ID()), site_url('/dashboard/'));
        $create_ticket_url = add_query_arg(array('robodesk_page' => 'create-ticket'), site_url('/dashboard/'));
        ##Aby##   
    ?>
        <h3>Get Support Ticket</h3>
        <a href="<?php echo esc_url($create_ticket_url); ?>">Create Ticket</a>
        <table style="margin-top: 30px;">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Replies</th>
                    <th>Last Reply</th>
                    <th>Status</th>
                    <th>Priority</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                        global $post;
                        $status = get_post_meta(get_the_ID(), '_robodesk_ticket_status', true);
                        $terms = get_the_terms(get_the_ID(), 'robodesk_ticket_priority');
                        $comment_count = get_comments_number(get_the_ID());
                        $priority_name = '';
                        $priority_color = false;
                        if ($terms && !is_wp_error($terms)) {
                            $priority_name = $terms[0]->name;
                            $priority_color = get_term_meta($terms[0]->term_id, 'priority_color', true);
                        }
                        $comments = get_comments(array(
                            'post_id' => get_the_ID(),
                            'number' => 1,
                            'status' => 'approve',
                            'orderby' => 'comment_date_gmt',
                            'order' => 'DESC'
                        ));

                        // var_dump($comments);



                        $last_activity = !empty($comments) ?  strtotime($comments[0]->comment_date_gmt) : get_the_modified_time('U');

                        $human_readable_time = human_time_diff($last_activity, current_time('U'));

                        $comment_author = !empty($comments) ? get_the_author_meta('display_name', $comments[0]->user_id) :
                            get_the_author_meta('display_name', $post->post_author);

                ?>
                        <tr>
                            <td>
                                <?php
                                $ticket_url = add_query_arg(array('robodesk_page' => 'my-tickets', 'tid' => get_the_ID()), site_url('/dashboard/'));
                                ?>
                                <a href="<?php echo esc_url($ticket_url); ?>"><?php the_title(); ?></a>

                                <p class="ticket-date"><?php echo get_the_date('dS M Y'); ?></p>
                            </td>
                            <td><?php echo  $comment_count; ?></td>
                            <td><?php echo $human_readable_time ? $human_readable_time . ' ago' : '-' ?><p class="ticket-comment-by">By, <?php echo $comment_author; ?></p>
                            </td>
                            <td><span class="robodesk-status-pill <?php echo strtolower($status); ?>"> <?php echo esc_html(ucwords($status)); ?></span></td>
                            <td><span data-color="<?php echo esc_attr($priority_color); ?>" class="robodesk-priority-pill <?php echo strtolower($priority_name); ?>" style="background:<?php echo esc_attr($priority_color) ?>"><?php echo esc_html($priority_name); ?></span></td>
                        </tr>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <tr>
                        <td colspan="3">No tickets found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

<?php
    }
}

// Ajax handler for testing credential for IMAP connection
// This function will be called via AJAX to test the IMAP connection
add_action('wp_ajax_robodesk_imap_test_connection', 'robodesk_imap_test_connection');
function robodesk_imap_test_connection()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_imap_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (empty($_POST['robodesk_imap_hostname']) || empty($_POST['robodesk_imap_port']) || empty($_POST['robodesk_imap_username']) || empty($_POST['robodesk_imap_password'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    // Check IMAP extension
    if (!function_exists('imap_open')) {
        wp_send_json_error(['message' => 'IMAP extension is not enabled on the server']);
        exit;
    }

    $auth_type = ($_POST['robodesk_imap_auth'] == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field($_POST['robodesk_imap_hostname']) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';
    $hostname_view = ($_POST['robodesk_imap_hostname']);
    $username = sanitize_text_field($_POST['robodesk_imap_username']);
    $password = $_POST['robodesk_imap_password'];

    // Clear previous errors
    imap_errors();

    $mailbox = @imap_open($hostname, $username, $password);

    if ($mailbox) {
        $message = ' IMAP connection successful!';
        imap_close($mailbox);
        wp_send_json_success(['message' => $message]);
    } else {
        $error = imap_last_error();
        $message = ' IMAP connection failed: ' . esc_html($error);
        wp_send_json_error(['message' => $message]);
    }
}

// Enqueue script and localize
function new_robodesk_enqueue_scripts()
{
    //if page page=robodesk-dashboard 
    if (!isset($_GET['page']) || $_GET['page'] !== 'robodesk-dashboard') {
        return;
    }
    wp_enqueue_script('robodesk-script', plugins_url('public/assets/js/robodesk-dashboard.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('robodesk-script', 'robodesk_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('robodesk_imap_settings_action')
    ));
}
add_action('admin_enqueue_scripts', 'robodesk_enqueue_scripts');


//function to save the credentials

add_action('wp_ajax_robodesk_imap_save_settings', 'robodesk_imap_save_settings');
function robodesk_imap_save_settings()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'robodesk_imap_settings_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    // Validate inputs
    if (empty($_POST['robodesk_imap_hostname']) || empty($_POST['robodesk_imap_port']) || empty($_POST['robodesk_imap_username']) || empty($_POST['robodesk_imap_password'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
        exit;
    }

    // Check IMAP extension
    if (!function_exists('imap_open')) {
        wp_send_json_error(['message' => 'IMAP extension is not enabled on the server']);
        exit;
    }

    $auth_type = ($_POST['robodesk_imap_auth'] == '1') ? 'ssl' : 'tls';
    $hostname = '{' . sanitize_text_field($_POST['robodesk_imap_hostname']) . ':' . intval($_POST['robodesk_imap_port']) . '/imap/' . $auth_type . '/novalidate-cert}INBOX';

    $username = sanitize_text_field($_POST['robodesk_imap_username']);
    $password = $_POST['robodesk_imap_password'];

    // Clear previous errors
    imap_errors();

    $mailbox = @imap_open($hostname, $username, $password);

    if ($mailbox) {
        $message = 'IMAP connection settings saved successfully!';
        // Save settings to the database
        $settings = array(
            'hostname' => sanitize_text_field($_POST['robodesk_imap_hostname']),
            'port'     => intval($_POST['robodesk_imap_port']),
            'username' => sanitize_email($_POST['robodesk_imap_username']),
            'password' => sanitize_text_field($_POST['robodesk_imap_password']),
            'auth'     => sanitize_text_field($_POST['robodesk_imap_auth']),

        );

        update_option('robodesk_imap_settings', $settings); // Save the settings to the database
        imap_close($mailbox);
        wp_send_json_success(['message' => $message]);
    } else {
        $error = imap_last_error();
        $message = 'IMAP connection settings not saved successfully! ' . esc_html($error);
        wp_send_json_error(['message' => $message]);
    }
}
