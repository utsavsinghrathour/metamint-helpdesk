<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Woocommerce modifications
if (in_array('woocommerce/woocommerce.php', (array) get_option('active_plugins', array()))) {
    require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-woocommerce.php';
}
// for comments
require_once MMHD_PLUGIN_PATH . 'public/includes/mmhd-comments.php';

if (!function_exists('mmhd_get_support_page_url')) {
    function mmhd_get_support_page_url()
    {
        $support_page = get_page_by_path('mmhd-support', OBJECT, 'page');

        if ($support_page && !is_wp_error($support_page)) {
            $page_url = get_permalink($support_page->ID);
            if (!empty($page_url)) {
                return $page_url;
            }
        }

        return home_url('/mmhd-support/');
    }
}

// Function to send notification email
function mmhd_send_ticket_update_email($ticket_id, $comment_content, $update_type)
{
    $ticket = get_post($ticket_id);
    if (!$ticket) return;
    $customer = get_user_by('id', $ticket->post_author);
    if (!$customer) return;
    $ticket_title = get_the_title($ticket_id);
    $ticket_link = get_permalink($ticket_id);

    $to = $customer->user_email;
    $subject = sprintf('[Metamint Helpdesk] Ticket #%d Updated', $ticket_id);

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

    $message .= "View the ticket here: " . $ticket_link . "\n\nThank you,\nThe Metamint Helpdesk Team";

    $reply_to = function_exists('mmhd_get_inbox_email_for_ticket') ? mmhd_get_inbox_email_for_ticket($ticket_id) : '';
    $thread_headers = function_exists('mmhd_get_ticket_thread_headers') ? mmhd_get_ticket_thread_headers($ticket_id) : [];
    mmhd_send_email($to, $subject, $message, $thread_headers, [], $reply_to);
}

global $wpdb;
$mmhd_notifications_table = $wpdb->prefix . 'mmhd_notifications';

// @utsav sir code don remove now
// Create notification table on plugin activation
// register_activation_hook(__FILE__, 'mmhd_create_notifications_table');
// function mmhd_create_notifications_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'mmhd_notifications';
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
// function mmhd_create_ticket_notes_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'mmhd_ticket_notes';
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
//         CONSTRAINT fk_mmhd_ticket_notes_faq FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
//         CONSTRAINT fk_mmhd_ticket_notes_user FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

//     dbDelta($sql);

// }
// register_activation_hook(__FILE__, 'mmhd_create_ticket_notes_table');

//needed to do this coz itz not working on plugin activation hook
function mmhd_maybe_create_ticket_notes_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_ticket_notes';


    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table_name))) !== $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            note_content longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_private tinyint(1) NOT NULL DEFAULT 0,
            type varchar(255) NOT NULL DEFAULT 'user_note',
            status varchar(20) NOT NULL DEFAULT 'unread',
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY user_id (user_id),
            CONSTRAINT fk_mmhd_ticket_notes_faq FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
            CONSTRAINT fk_mmhd_ticket_notes_user FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Call the function on WordPress initialization
add_action('init', 'mmhd_maybe_create_ticket_notes_table');

function mmhd_get_credential_password_value($stored_password)
{
    if ($stored_password === '' || $stored_password === null) {
        return '';
    }

    $stored_password = (string) $stored_password;
    $decoded_password = base64_decode($stored_password, true);

    if ($decoded_password !== false && strpos($decoded_password, '::') !== false) {
        $decrypted_password = mmhd_decrypt($stored_password);
        if ($decrypted_password !== false) {
            return $decrypted_password;
        }

        return false;
    }

    return $stored_password;
}

function mmhd_credentials_page()
{
    if (!is_user_logged_in()) {
        return '<div class="mmhd-container"><p>Please <a href="' . esc_url(wp_login_url(get_permalink('login'))) . '">login</a> to view your credentials.</p></div>';
    }

    global $wpdb;
    $credentials_table = $wpdb->prefix . 'mmhd_credentials';
    $user_id = get_current_user_id();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $credentials = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$credentials_table} WHERE user_id = %d ORDER BY name ASC", $user_id));

    ob_start();
?>
<div class="mmhd-container">
    <h2>Credentials Vault</h2>

    <button id="add-credential-button">Add Credential</button>

    <div class="mmhd-my-credentials">
        <h2>My Credentials</h2>
        <?php if ($credentials): ?>
        <div class="credential-table-wrapper">
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
                    <tr class="mmhd-table-content">
                        <td>
                            <span class="credential-cell-wrap">
                                <span class="credential-name-text"><?php echo esc_html($credential->name); ?></span>
                                <button type="button" class="copy-credential" data-copy-value="<?php echo esc_attr($credential->name); ?>" title="Copy Name">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </span>
                        </td>
                        <td>
                            <span class="credential-cell-wrap">
                                <span class="credential-username-text"><?php echo esc_html($credential->username); ?></span>
                                <button type="button" class="copy-credential" data-copy-value="<?php echo esc_attr($credential->username); ?>" title="Copy Username">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </span>
                        </td>
                        <td>
                            <span class="credential-cell-wrap">
                                <span class="password-hidden">********</span>
                                <button type="button" class="show-password"
                                    data-credential-id="<?php echo esc_attr($credential->id); ?>" title="Show Password">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="copy-password"
                                    data-credential-id="<?php echo esc_attr($credential->id); ?>" title="Copy Password">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </span>
                        </td>
                        <td>
                            <span class="credential-cell-wrap">
                                <?php if (!empty($credential->url)): ?>
                                <a href="<?php echo esc_url($credential->url); ?>" target="_blank"
                                    rel="noopener noreferrer" class="credential-url-link" title="<?php echo esc_url($credential->url); ?>">
                                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                                </a>
                                <button type="button" class="copy-credential" data-copy-value="<?php echo esc_url($credential->url); ?>" title="<?php echo esc_url($credential->url); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                                <?php else: ?>
                                <span class="credential-url-text">—</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($credential->notes); ?></td>
                        <td>
                            <button class="delete-credential"
                                data-credential-id="<?php echo esc_attr($credential->id); ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p>No credentials saved yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php
    return ob_get_clean();
}

/**
 * Displaying a table to show open tickets after my-account page 
 */
add_action('woocommerce_after_my_account', 'mmhd_add_custom_table_to_my_account');
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function mmhd_add_custom_table_to_my_account()
{

    if (is_user_logged_in()) {
        global $wp;
        $user_id = get_current_user_id();

        $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
        $args = array(
            'post_type' => 'mmhd_ticket',
            'author' => $user_id,
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_mmhd_ticket_status',
                    'value' => 'open',
                    'compare' => 'like'
                )
            )
        );

        $query = new WP_Query($args);
        $dashboard_base_url = home_url('/mmhd-support/');
        $ticket_url = add_query_arg(array('mmhd_page' => 'my-tickets', 'tid' => get_the_ID()), $dashboard_base_url);
        $create_ticket_url = add_query_arg(array('mmhd_page' => 'create-ticket'), $dashboard_base_url);
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
                        $status = get_post_meta(get_the_ID(), '_mmhd_ticket_status', true);
                        $terms = get_the_terms(get_the_ID(), 'mmhd_ticket_priority');
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





                        $last_activity = !empty($comments) ?  strtotime($comments[0]->comment_date_gmt) : get_the_modified_time('U');

                        $human_readable_time = human_time_diff($last_activity, current_time('U'));

                        $comment_author = !empty($comments) ? get_the_author_meta('display_name', $comments[0]->user_id) :
                            get_the_author_meta('display_name', $post->post_author);

                ?>
        <tr>
            <td>
                <?php
                                $ticket_url = add_query_arg(array('mmhd_page' => 'my-tickets', 'tid' => get_the_ID()), home_url('/mmhd-support/'));
                                ?>
                <a href="<?php echo esc_url($ticket_url); ?>"><?php the_title(); ?></a>

                <?php $my_ticket_utc = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso(get_post()) : get_the_date('Y-m-d H:i:s'); ?>
                <p class="ticket-date"><time class="mmhd-local-time"
                  datetime="<?php echo esc_attr($my_ticket_utc); ?>"
                  data-utc="<?php echo esc_attr($my_ticket_utc); ?>"
                  data-format="date"><?php echo esc_html(get_the_date('dS M Y')); ?></time></p>
            </td>
            <td><?php echo esc_html($comment_count); ?></td>
            <td><?php echo esc_html($human_readable_time ? $human_readable_time . ' ago' : '-'); ?><p
                    class="ticket-comment-by">By, <?php echo esc_html($comment_author); ?></p>
            </td>
            <td><span class="mmhd-status-pill <?php echo esc_attr(strtolower($status)); ?>">
                    <?php echo esc_html(ucwords($status)); ?></span></td>
            <td><span data-color="<?php echo esc_attr($priority_color); ?>"
                    class="mmhd-priority-pill <?php echo esc_attr(strtolower($priority_name)); ?>"
                    style="background:<?php echo esc_attr($priority_color); ?>"><?php echo esc_html($priority_name); ?></span>
            </td>
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
// Function to get notifications
function mmhd_get_user_notifications($user_id, $limit = 20)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_notifications';

    // Check if table exists to avoid errors on fresh installs before init hook runs
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table_name))) != $table_name) {
        return array();
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $notifications = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mmhd_notifications WHERE recipient_user_id = %d ORDER BY notification_date DESC LIMIT %d",
            $user_id,
            $limit
        )
    );

    return $notifications;
}

function mmhd_get_notification_count($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_notifications';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table_name))) != $table_name) {
        return 0;
    }

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE recipient_user_id = %d AND read_at IS NULL",
            $user_id
        )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
