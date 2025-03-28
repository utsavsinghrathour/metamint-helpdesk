<?php
// In public/includes/robodesk-shortcodes.php


add_shortcode('robodesk_ticket_form', 'robodesk_ticket_form_shortcode');
function robodesk_ticket_form_shortcode()
{
    if (is_user_logged_in()) {
        ob_start();
?>
        <div class="robodesk-ticket-form robodesk-container">
            <h2>Submit a Ticket</h2>
            <form method="post" id="robodesk_submit_ticket">
                <label for="ticket_title">Title:</label>
                <input type="text" name="ticket_title" id="ticket_title" required><br><br>

                <label for="ticket_content">Description:</label>
                <textarea name="ticket_content" id="ticket_content" rows="5" required></textarea><br><br>

                <label for="ticket_origin">Ticket Origin:</label>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'robodesk_ticket_origin',
                    'name' => 'ticket_origin',
                    'show_option_none' => 'Ticket Origin',
                    'hierarchical' => true,
                ));
                ?><br><br>

                <label for="ticket_priority">Priority:</label>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'robodesk_ticket_priority',
                    'name' => 'ticket_priority',
                    'show_option_none' => 'Select Priority',
                    'hierarchical' => true,
                ));
                ?><br><br>

                <input type="submit" value="Submit Ticket">
            </form>
        </div>
    <?php
        if (isset($_POST['ticket_title']) && isset($_POST['ticket_content']) && isset($_POST['ticket_origin']) && isset($_POST['ticket_priority'])) {
            $ticket_title = sanitize_text_field($_POST['ticket_title']);
            $ticket_content = sanitize_textarea_field($_POST['ticket_content']);
            $ticket_origin = (int)$_POST['ticket_origin'];
            $ticket_priority = (int)$_POST['ticket_priority'];
            $user_id = get_current_user_id();

            $post_id = wp_insert_post(array(
                'post_title'    => $ticket_title,
                'post_content'  => $ticket_content,
                'post_status'   => 'publish',
                'post_type'     => 'robodesk_ticket',
                'post_author'   => $user_id,
            ));

            if ($post_id) {
                wp_set_object_terms($post_id, $ticket_origin, 'robodesk_ticket_origin');
                wp_set_object_terms($post_id, $ticket_priority, 'robodesk_ticket_priority');
                echo "<div class='robodesk-container'><p>Ticket submitted successfully!</p></div>";
            } else {
                echo "<div class='robodesk-container'><p>Error submitting ticket.</p></div>";
            }
        }

        return ob_get_clean();
    } else {
        return '<div class="robodesk-container"><p>Please <a href="' . wp_login_url(get_permalink('login')) . '">login</a> to submit a ticket.</p></div>';
    }
}

function order_posts_by_term_meta($clauses, $query)
{
    // Check if our custom query parameter is set
    if (!$query->get('order_by_tax_priority')) {
        return $clauses;
    }

    global $wpdb;

    // Join necessary tables to access term meta
    $clauses['join'] .= "
        LEFT JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->termmeta} AS tm ON tt.term_id = tm.term_id
    ";

    // Specify the taxonomy and meta key
    $clauses['where'] .= " AND tt.taxonomy = 'robodesk_ticket_priority'";
    $clauses['where'] .= " AND tm.meta_key = 'taxonomy_order'";

    // Group by post ID to handle multiple terms (if any)
    $clauses['groupby'] = "{$wpdb->posts}.ID";

    // Calculate term_order using the minimum meta_value (or 9999 if no term)
    $clauses['fields'] .= ", COALESCE(MIN(CAST(tm.meta_value AS UNSIGNED)), 9999) as term_order";

    // Order by the computed term_order and post_date (latest first)

    $clauses['orderby'] = "term_order ASC, {$wpdb->posts}.post_date {$query->get('order')}";
    return $clauses;
}
add_filter('posts_clauses', 'order_posts_by_term_meta', 10, 2);


add_shortcode('robodesk_my_tickets', 'robodesk_my_tickets_shortcode');


function robodesk_my_tickets_shortcode()
{
    global $wp;

    $user_id = get_current_user_id();

    $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;

    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';

    $obystatus = trim($orderby) == 'meta_value' ? true : false;

    $order  = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    $args = array(
        'post_type' => 'robodesk_ticket',
        'author' => $user_id,
        'posts_per_page' => 10,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order,
        'order_by_tax_priority' => true,
    );

    $selected_priority = isset($_GET['robodesk_ticket_priority']) ? sanitize_text_field($_GET['robodesk_ticket_priority']) : '';
    $selected_status = isset($_GET['robodesk_ticket_status']) ? sanitize_text_field($_GET['robodesk_ticket_status']) : '';

    if (!empty($selected_priority)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'robodesk_ticket_priority',
                'field' => 'slug',
                'terms' => $selected_priority,
            ),
        );
    }

    if (!empty($selected_status)) {
        $args['meta_query'] = array(
            array(
                'key' => '_robodesk_ticket_status',
                'value' => $selected_status,
                'compare' => '=',
            ),
        );
    }


    $query = new WP_Query($args);

    $priority_terms = get_terms(array(
        'taxonomy' => 'robodesk_ticket_priority',
        'hide_empty' => false,
    ));


    $statuses = array('open', 'in-progress', 'archived', 'closed');

    ob_start();
    ?>
    <form method="get">
        <input type="hidden" name="robodesk_page" value="my-tickets">
        <select name="robodesk_ticket_priority">
            <option value="">All Priorities</option>
            <?php if (!is_wp_error($priority_terms) && !empty($priority_terms)) : ?>
                <?php foreach ($priority_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_priority, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="robodesk_ticket_status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $status) : ?>
                <option value="<?php echo esc_attr($status); ?>" <?php selected($selected_status, $status); ?>><?php echo esc_html(ucfirst($status)); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="orderby">
            <option value="date" <?php selected($orderby, 'date'); ?>>Sort by Date</option>
            <option value="meta_value" <?php selected($orderby, 'meta_value'); ?>>Sort by Status</option>
        </select>
        <select name="order">
            <option value="DESC" <?php selected($order, 'DESC'); ?>>Descending</option>
            <option value="ASC" <?php selected($order, 'ASC'); ?>>Ascending</option>
        </select>
        <input type="submit" value="Filter">
    </form>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                    $status = get_post_meta(get_the_ID(), '_robodesk_ticket_status', true);
                    $terms = get_the_terms(get_the_ID(), 'robodesk_ticket_priority');


                    $priority_name = '';
                    if ($terms && !is_wp_error($terms)) {
                        $priority_name = $terms[0]->name;
                    }

            ?>
                    <tr>
                        <td>
                            <?php
                            $ticket_url = add_query_arg(array('robodesk_page' => 'my-tickets', 'tid' => get_the_ID()), site_url($wp->request));
                            ?>
                            <a href="<?php echo esc_url($ticket_url); ?>"><?php the_title(); ?></a>

                            <p class="ticket-date"><?php echo get_the_date('dS M Y'); ?></p>
                        </td>
                        <td><?php echo esc_html($status); ?></td>
                        <td><?php echo esc_html($priority_name); ?></td>
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
    $big = 999999999; // Need an unlikely integer
    $pagination = paginate_links(array(
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, $paged),
        'total' => $query->max_num_pages,
        'prev_text' => __('« Previous'),
        'next_text' => __('Next »'),
    ));

    if ($pagination) {
        echo '<div class="pagination">' . $pagination . '</div>';
    }
    return ob_get_clean();
}

// Shortcode for the frontend dashboard
add_shortcode('robodesk_frontend_dashboard', 'robodesk_frontend_dashboard_shortcode');

function robodesk_frontend_dashboard_shortcode()
{
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $current_page = get_query_var('robodesk_page');

        if (empty($current_page)) {
            $current_page = isset($_GET['robodesk_page']) ? $_GET['robodesk_page'] : 'dashboard';
        }

        $ticket_id = get_query_var('tid');
        if (empty($ticket_id)) {
            $ticket_id = isset($_GET['tid']) ? $_GET['tid'] : '';
        }
        // Get Ticket Counts
        $open_tickets_args = array(
            'post_type' => 'robodesk_ticket',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => '_robodesk_ticket_status',
                    'value' => 'open',
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        $open_tickets_query = new WP_Query($open_tickets_args);
        $open_tickets = $open_tickets_query->post_count;

        $total_tickets_args = array(
            'post_type' => 'robodesk_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        $total_tickets_query = new WP_Query($total_tickets_args);
        $total_tickets = $total_tickets_query->post_count;


        global $wpdb;
        $notification_table = $wpdb->prefix . 'robodesk_ticket_notes';
        $notification_count = $wpdb->get_var("SELECT COUNT(*) FROM $notification_table WHERE user_id = $user_id AND status = 'unread'");


        $menu_items = apply_filters('robodesk_frontend_dashboard_menu_items', array());

        ob_start();
    ?>
        <div class="robodesk-frontend-dashboard robodesk-container">
            <nav>
                <ul>
                    <?php foreach ($menu_items as $slug => $menu_item):
                        $active_class = ($current_page == $slug) ? 'active' : '';
                        $url = add_query_arg('robodesk_page', $slug, get_permalink());
                    ?>
                        <li><a href="<?php echo esc_url($url); ?>" class="<?php echo $active_class; ?>"><?php echo esc_html($menu_item['title']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo wp_logout_url(get_permalink()); ?>">Logout</a></li>
                </ul>
            </nav>
            <div class="robodesk-dashboard-content">
                <?php
                if (!empty($ticket_id) && get_post_type($ticket_id) == 'robodesk_ticket') {

                    echo do_shortcode('[robodesk_single_ticket id="' . $ticket_id . '"]');
                } else if (array_key_exists($current_page, $menu_items)) {
                    if ($current_page == 'dashboard') {
                        echo $menu_items[$current_page]['function']($notification_count, $open_tickets, $total_tickets);
                    } else {
                        echo $menu_items[$current_page]['function']();
                    }
                } else {
                    echo "Invalid Page";
                }
                ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    } else {
        return '<div class="robodesk-container"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your tickets.</p></div>';
    }
}
/*

function robodesk_frontend_dashboard_shortcode()
{
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $current_page = get_query_var('robodesk_page');
        if (empty($current_page)) {
            $current_page = isset($_GET['robodesk_page']) ? $_GET['robodesk_page'] : 'dashboard';
        }
        // Get ticket data
        $open_tickets = robodesk_get_open_ticket_count($user_id);
        $total_tickets = robodesk_get_total_ticket_count($user_id);
        $notification_count = robodesk_get_notification_count($user_id);
        $latest_reply = robodesk_get_latest_reply($user_id);

        ob_start();
    ?>
        <div class="robodesk-frontend-dashboard robodesk-container">
            <nav>
                <ul>
                    <li><a href="<?php echo get_permalink(); ?>" class="<?php if ($current_page == 'dashboard') echo 'active'; ?>">Dashboard</a></li>
                    <li><a href="<?php echo add_query_arg('robodesk_page', 'profile', get_permalink()); ?>" class="<?php if ($current_page == 'profile') echo 'active'; ?>">Profile</a></li>
                    <li><a href="<?php echo add_query_arg('robodesk_page', 'my-tickets', get_permalink()); ?>" class="<?php if ($current_page == 'my-tickets') echo 'active'; ?>">My Tickets</a></li>
                    <li><a href="<?php echo add_query_arg('robodesk_page', 'notifications', get_permalink()); ?>" class="<?php if ($current_page == 'notifications') echo 'active'; ?>">Notifications <?php $notification_count = robodesk_get_notification_count(get_current_user_id());
                                                                                                                                                                                                        if ($notification_count > 0) echo "<span class='robodesk-notification-count'>(" . $notification_count . ")</span>"; ?></a></li>
                    <li><a href="<?php echo wp_logout_url(get_permalink()); ?>">Logout</a></li>
                </ul>
            </nav>
            <div class="robodesk-dashboard-content">

                <?php
                if ($current_page == 'profile') {
                    robodesk_profile_content();
                } elseif ($current_page == 'my-tickets') {
                    echo do_shortcode('[robodesk_my_tickets]');
                } elseif ($current_page == 'notifications') {
                    robodesk_notifications_content();
                } else {
                    echo "<h2>Welcome to your Dashboard</h2>";

                ?>
                    <div class="robodesk-infographs">
                        <div class="robodesk-infograph">
                            <h3>Tickets Open vs. Total</h3>
                            <div class="robodesk-chart">
                                <div class="robodesk-open-bar" style="width: <?php echo ($total_tickets > 0) ? ($open_tickets / $total_tickets * 100) : 0; ?>%;">
                                    <span><?php echo $open_tickets; ?></span>
                                </div>
                                <div class="robodesk-total-bar">
                                    <span><?php echo $total_tickets; ?></span>
                                </div>
                            </div>
                            <div class="robodesk-chart-label">
                                <span>Open</span>
                                <span>Total</span>
                            </div>
                        </div>

                        <div class="robodesk-infograph">
                            <h3>Notifications</h3>
                            <div class="robodesk-notification-count-display">
                                <?php echo $notification_count; ?>
                            </div>
                        </div>

                        <?php if ($latest_reply) : ?>
                            <div class="robodesk-infograph">
                                <h3>Latest Reply</h3>
                                <p>Ticket: <a href="<?php echo esc_url(get_permalink($latest_reply['ticket_id'])); ?>"><?php echo esc_html(get_the_title($latest_reply['ticket_id'])); ?></a></p>
                                <p><?php echo esc_html(wp_trim_words($latest_reply['note_content'], 10)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    } else {
        return '<div class="robodesk-container"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to access your dashboard.</p></div>';
    }
}

*/

// In public/includes/robodesk-shortcodes.php

// ... (Previous shortcodes: robodesk_ticket_form, robodesk_my_tickets, robodesk_frontend_dashboard)

function robodesk_profile_content()
{

    $current_user = wp_get_current_user();
    $user_id = get_current_user_id();
    // Save the Edits.

    if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
        if (! isset($_POST['robodesk_profile_nonce']) || ! wp_verify_nonce($_POST['robodesk_profile_nonce'], 'robodesk_update_profile')) {
            echo '<p style="color:red;">Nonce verification failed</p>';
            return;
        }
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $userdata = array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
        $user_id_update = wp_update_user($userdata);

        if (is_wp_error($user_id_update)) {
            echo '<p style="color:red;">Error updating user data: ' . esc_html($user_id_update->get_error_message()) . '</p>';
        } else {
            echo '<p style="color:green;">Profile updated successfully!</p>';
        }
    }
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) { // Check for file and no error code for no file
        if (!isset($_POST['robodesk_profile_nonce']) || !wp_verify_nonce($_POST['robodesk_profile_nonce'], 'robodesk_update_profile')) {
            echo '<p style="color:red;">Nonce verification failed</p>';
            return;
        }

        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/svg+xml');
        $max_size = 5 * 1024 * 1024; // 5MB in bytes

        if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            echo '<p style="color:red;">Invalid file type. Only JPG, PNG, GIF, and SVG are allowed.</p>';
            return;
        }

        if ($_FILES['profile_picture']['size'] > $max_size) {
            echo '<p style="color:red;">Please make sure your profile picture is less than 5MB.</p>';
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('profile_picture', 0);

        if (is_wp_error($attachment_id)) {
            echo '<p style="color:red;">Error uploading profile picture: ' . esc_html($attachment_id->get_error_message()) . '</p>';
        } else {
            update_user_meta($user_id, 'wp_user_avatar', $attachment_id);
            echo '<p style="color:green;">Profile picture updated successfully!</p>';
        }
    }
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        if (! isset($_POST['robodesk_profile_nonce']) || ! wp_verify_nonce($_POST['robodesk_profile_nonce'], 'robodesk_update_profile')) {
            echo '<p style="color:red;">Nonce verification failed</p>';
            return;
        }
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            echo '<p style="color:red;">Passwords do not match.</p>';
        } elseif (strlen($password) < 6) {
            echo '<p style="color:red;">Password must be at least 6 characters long.</p>';
        } else {
            $user_update = wp_update_user(array('ID' => $user_id, 'user_pass' => $password));

            if (is_wp_error($user_update)) {
                echo '<p style="color:red;">Error changing password: ' . esc_html($user_update->get_error_message()) . '</p>';
            } else {
                echo '<p style="color:green;">Password changed successfully!</p>';
            }
        }
    }


    if ($current_user) {

        $avatar_id = get_user_meta($user_id, 'wp_user_avatar', true);
        $avatar_url = '';
        if ($avatar_id) {
            $avatar_url = wp_get_attachment_image_url($avatar_id, 'thumbnail'); // Get the URL
        }
    ?>
        <h2>Update Profile</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('robodesk_update_profile', 'robodesk_profile_nonce'); ?>

            <label for="first_name">First Name:</label><br>
            <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($current_user->first_name); ?>"><br><br>

            <label for="last_name">Last Name:</label><br>
            <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($current_user->last_name); ?>"><br><br>

            <label for="profile_picture">Profile Picture:</label><br>
            <?php if ($avatar_url): ?>
                <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile Picture" width="96" height="96"><br>
            <?php else:
                echo get_avatar($user_id, 96);
            endif; ?><br>
            <input type="file" name="profile_picture" id="profile_picture"><br><br>

            <label for="password">New Password:</label><br>
            <input type="password" name="password" id="password"><br><br>

            <label for="confirm_password">Confirm New Password:</label><br>
            <input type="password" name="confirm_password" id="confirm_password"><br><br>

            <input type="submit" value="Update Profile">
        </form>
    <?php
    }
}

function robodesk_notifications_content()
{
    $user_id = get_current_user_id();
    $notifications = robodesk_get_notifications($user_id);
    ?>
    <h2>Notifications</h2>
    <?php if ($notifications): ?>
        <ul>
            <?php foreach ($notifications as $notification): ?>
                <li><?php echo esc_html($notification->message); ?> - <small><?php echo date("F j, Y, g:i a", strtotime($notification->notification_date)); ?></small></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No notifications found.</p>
    <?php endif;
}


function robodesk_get_open_ticket_count($user_id)
{
    $args = array(
        'post_type' => 'robodesk_ticket',
        'author' => $user_id,
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_robodesk_ticket_status',
                'value' => 'open',
                'compare' => '=',
            ),
        ),
    );
    $query = new WP_Query($args);
    return $query->post_count;
}

function robodesk_get_total_ticket_count($user_id)
{
    $args = array(
        'post_type' => 'robodesk_ticket',
        'author' => $user_id,
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    return $query->post_count;
}

function robodesk_get_latest_reply($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'robodesk_ticket_notes';
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY notification_date DESC LIMIT 1", $user_id);
    $result = $wpdb->get_row($query, ARRAY_A);
    if ($result) {
        $ticket_id = $result['ticket_id'];
        return array("ticket_id" => $ticket_id, "note_content" => $result['note_content']);
    }
    return false;
}


function robodesk_frontend_dashboard_menu($items)
{
    $new_items = array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'function' => 'robodesk_dashboard_content_page',
        ),
        'profile' => array(
            'title' => 'Profile',
            'function' => 'robodesk_profile_content_page',
        ),
        'my-tickets' => array(
            'title' => 'My Tickets',
            'function' => 'robodesk_my_tickets_content_page',
        ),
        'notifications' => array(
            'title' => 'Notifications',
            'function' => 'robodesk_notifications_content_page',
        ),
        'create-ticket' => array(
            'title' => 'Create Ticket',
            'capability' => 'read',
            'menu_slug' => 'create-ticket',
            'function' => 'robodesk_create_ticket_page',
        ),
        'credentials-vault' => array(
            'title' => 'Credentials Vault',
            'capability' => 'read',
            'menu_slug' => 'credentials-vault',
            'function' => 'robodesk_credentials_page',
        ),
    );


    return $new_items;
}
add_filter('robodesk_frontend_dashboard_menu_items', 'robodesk_frontend_dashboard_menu');

function robodesk_create_ticket_page()
{
    if (is_user_logged_in()) {

        ob_start();
    ?>
        <div class="robodesk-container">
            <h2>Create New Ticket</h2>
            <form method="post" id="robodesk-create-ticket">
                <label for="ticket_title">Title:</label><br>
                <input type="text" id="ticket_title" name="ticket_title" required><br><br>

                <label for="ticket_content">Description:</label><br>
                <!-- <textarea id="ticket_content" name="ticket_content" rows="4" required></textarea><br><br> -->
                <?php
                $args = array(
                    'media_buttons' => false,
                    'textarea_name' => 'ticket_content',
                    'textarea_rows' => get_option('default_post_edit_rows', 10),
                    'quicktags' => false,
                );
                wp_editor(false, 'ticket_content', $args);

                ?>

                <label for="ticket_priority">Priority:</label><br>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'robodesk_ticket_priority',
                    'name' => 'ticket_priority',
                    'show_option_none' => 'Select Priority',
                    'hierarchical' => true,
                    'hide_empty' => false,
                    'post_type' => 'robodesk_ticket'
                ));
                ?><br><br>

                <input type="hidden" name="action" value="robodesk_create_new_ticket">
                <?php wp_nonce_field('robodesk_create_ticket_nonce'); ?>
                <input type="submit" value="Create Ticket">

            </form>
            <div id="robodesk-response-message"></div>
        </div>
    <?php
        return ob_get_clean();
    } else {
        return '<div class="robodesk-container"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to create a ticket.</p></div>';
    }
}



function robodesk_dashboard_content_page($notification_count, $open_tickets, $total_tickets)
{

    ob_start();
    ?>
    <div class="robodesk-infographs">

        <div class="robodesk-infograph">
            <h3>Tickets</h3>
            <div class="robodesk-chart">
                <div class="robodesk-open-bar" style="width: <?php echo ($total_tickets > 0) ? ($open_tickets / $total_tickets * 100) : 0; ?>%;">
                    <span><?php echo $open_tickets; ?></span>
                </div>
                <div class="robodesk-total-bar">
                    <span><?php echo $total_tickets; ?></span>
                </div>
            </div>
            <div class="robodesk-chart-label">
                <span>Open</span>
                <span>Total</span>
            </div>
        </div>

        <div class="robodesk-infograph">
            <h3>Notifications</h3>
            <div class="robodesk-notification-count-display">
                <?php echo $notification_count; ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}


// Profile Content Page
function robodesk_profile_content_page()
{
    // ... profile page content
    ob_start();

    robodesk_profile_content();
    return ob_get_clean();
}


function robodesk_my_tickets_content_page()
{
    // ... my tickets page content


    return robodesk_my_tickets_shortcode();
}


function robodesk_notifications_content_page()
{
    // ... notifications page content
    ob_start();
?>
    <div>Notifications Page Content</div>
<?php
    robodesk_notifications_content();
    return ob_get_clean();
}

// Add query vars
function robodesk_add_query_vars($vars)
{
    $vars[] = 'robodesk_ticket_id';
    $vars[] = 'robodesk_page';
    return $vars;
}
add_filter('query_vars', 'robodesk_add_query_vars');


function robodesk_single_ticket_shortcode($atts)
{

    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    $ticket_id = intval($atts['id']);


    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'robodesk_ticket') {
        return '<div class="robodesk-container"><p>Invalid ticket ID.</p></div>';
    }

    $ticket = get_post($ticket_id);
    $status = get_post_meta($ticket_id, '_robodesk_ticket_status', true);
    $priority_terms = wp_get_object_terms($ticket_id, 'robodesk_ticket_priority');
    $priority = !empty($priority_terms) && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'N/A';
    $created_date = get_the_date('Y-m-d H:i:s', $ticket);
    $updated_date = get_the_modified_date('Y-m-d H:i:s', $ticket);
    $time_ago = human_time_diff(get_the_modified_time('U', $ticket), current_time('timestamp'));
    $time_ago_class = '';

    $current_user = wp_get_current_user();
    $caps = $current_user->caps;

    $allowed_roles = array('administrator', 'editor', 'support_agent', 'support_manager');

    $show_private_checkbox = array_intersect($allowed_roles, $current_user->roles);

    if (strtotime($updated_date) < strtotime('-30 hours')) {
        $time_ago_class = 'robodesk-time-ago-over-30';
    }

    global $post, $withcomments;
    $original_post = $post;
    $post = $ticket;
    $withcomments = true;
    ob_start();
?>
    <div class="robodesk-single-ticket robodesk-container">
        <h2><?php echo esc_html($ticket->post_title); ?></h2>
        <div class="robodesk-ticket-meta">
            <div class="robodesk-ticket-status-priority">
                <p><strong>Ticket id:</strong> <?php echo esc_html('#'.$ticket_id); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html($status); ?></p>
                <p><strong>Priority:</strong> <?php echo esc_html($priority); ?></p>
            </div>

            <div class="robodesk-ticket-dates">
                <p><strong>Created At:</strong> <?php echo esc_html($created_date); ?></p>
                <p class=" <?php echo $time_ago_class; ?>"><strong>Last Updated:</strong> <?php echo esc_html($updated_date) . ' (' . $time_ago . ')'; ?></p>
            </div>
            <div>
                <div class="robodesk-ticket-content">
                    <?php echo apply_filters('the_content', $ticket->post_content); ?>
                </div>
                <?php if (in_array('administrator', $current_user->roles)) : ?>
                    <div class="robodesk-ticket-actions">
                        <select id="change-ticket-status" data-ticket-id="<?php echo esc_attr($ticket_id); ?>">
                            <option value="new" <?php selected($status, 'new'); ?>>New</option>
                            <option value="in-progress" <?php selected($status, 'in-progress'); ?>>In Progress</option>
                            <option value="archived" <?php selected($status, 'archived'); ?>>Archived</option>
                            <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="robodesk-ticket-comments">
                    <?php

                    if (comments_open($ticket_id) || get_comments_number($ticket_id) > 0) {

                        $plugin_template = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/comments-ticket.php';

                        add_filter('comments_template', function ($theme_template) use ($plugin_template) {
                            if (file_exists($plugin_template)) {
                                return $plugin_template;
                            }
                            return $theme_template;
                        });

                        comments_template();
                    } else {
                        echo '<p>Comments are closed.</p>';
                    }
                    ?>
                </div>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var originalFormParent = $('#respond').parent();
                    var originalFormSibling = $('#respond').next();
                    $('.robodesk-ticket-comments #commentform').on('submit', function(e) {
                        if (typeof tinymce !== 'undefined' && tinymce.get('comment')) {
                            tinymce.triggerSave();
                            setTimeout(function() {}, 100);
                        }
                        var button = $(this).find('input[type="submit"]');
                        e.preventDefault();
                        var form = $(this);
                        var formData = new FormData(this);

                        formData.append('action', 'robodesk_submit_comment');
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            beforeSend: function() {
                                button.val('Posting...');
                                button.prop('disabled', true);
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('An error occurred while submitting the comment.');
                            },

                            complete: function() {
                                button.val('Post Comment');
                                button.prop('disabled', false);
                            }
                        });
                    });

                    $('.robodesk-ticket-comments .comment-reply-link').on('click', function(e) {
                        e.preventDefault();
                        var commentId = $(this).data('commentid');
                        $('#comment_parent').val(commentId);
                        $('#respond').insertAfter($(this).closest('.comment-body'));
                        $('#cancel-comment-reply-link').show();
                        var commentId = $(this).data('commentid');
                        var postId = $(this).data('postid');
                        var belowElement = $(this).data('belowelement');
                        var respondElement = $(this).data('respondelement');

                        if (typeof tinymce !== 'undefined' && tinymce.get('comment')) {
                            tinymce.EditorManager.execCommand('mceRemoveEditor', true, 'comment');
                        }

                        if (typeof addComment !== 'undefined' && addComment.moveForm) {
                            addComment.moveForm(belowElement, commentId, respondElement, postId);
                        }

                        if (typeof tinymce !== 'undefined') {
                            tinymce.EditorManager.execCommand('mceAddEditor', true, 'comment');
                        }
                    });

                    $('#cancel-comment-reply-link').on('click', function(e) {
                        e.preventDefault();

                        if (typeof tinymce !== 'undefined' && tinymce.get('comment')) {
                            tinymce.EditorManager.execCommand('mceRemoveEditor', true, 'comment');
                        }

                        if (originalFormSibling.length) {
                            originalFormParent.insertBefore($('#respond'), originalFormSibling);
                        } else {
                            originalFormParent.append($('#respond'));
                        }


                        $(this).hide();
                        $('#comment_parent').val('');

                        if (typeof tinymce !== 'undefined') {
                            tinymce.EditorManager.execCommand('mceAddEditor', true, 'comment');
                        }
                    });
                });
            </script>
        <?php
        return ob_get_clean();
    }
    add_shortcode('robodesk_single_ticket', 'robodesk_single_ticket_shortcode');

    function robodesk_submit_comment()
    {
        $comment = wp_handle_comment_submission(wp_unslash($_POST));

        global $wpdb;
        if (is_wp_error($comment)) {
            wp_send_json_error(array(
                'message' => $comment->get_error_message()
            ));
        } else {
            $comment_id = $comment->comment_ID;

            $wpdb->update(
                $wpdb->comments,
                array('comment_type' => 'robodesk_ticket_replies'),
                array('comment_ID' => $comment_id),
                array('%s'),
                array('%d')
            );
            wp_send_json_success();
        }
    }
    add_action('wp_ajax_robodesk_submit_comment', 'robodesk_submit_comment');
    add_action('wp_ajax_nopriv_robodesk_submit_comment', 'robodesk_submit_comment');


    //Shortcode for login form
    function robodesk_login_form_shortcode()
    {
        if (is_user_logged_in()) {
            $dashboard_page = get_page_by_path('dashboard');
            $redirect_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url();

            wp_safe_redirect($redirect_url);
            exit;
        }

        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            $error_message = '<p class="robodesk-error">Invalid username or password.</p>';
        } else {
            $error_message = '';
        }

        ob_start();
        ?>
            <div class="robodesk-login-form robodesk-container">
                <h2>Login</h2>
                <?php echo $error_message; ?>
                <form name="robodesk-login-form" id="robodesk-login-form" action="<?php echo esc_url(site_url('wp-login.php')); ?>" method="post">
                    <p>
                        <label for="user_login">Username or Email<br />
                            <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr(wp_unslash(isset($_POST['log']) ? $_POST['log'] : '')); ?>" size="20" autocapitalize="off" />
                        </label>
                    </p>
                    <p>
                        <label for="user_pass">Password<br />
                            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" />
                        </label>
                    </p>
                    <p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> Remember Me</label></p>
                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Log In" />
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>" />
                    </p>
                </form>
                <p id="nav">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Lost your password?</a>
                </p>
            </div>
        <?php
        return ob_get_clean();
    }
    add_shortcode('robodesk_login_form', 'robodesk_login_form_shortcode');


        ?>