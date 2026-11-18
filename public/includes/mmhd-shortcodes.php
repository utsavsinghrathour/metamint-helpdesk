<?php
// phpcs:disable WordPress.DB.SlowDBQuery
// In public/includes/mmhd-shortcodes.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_shortcode('mmhd_ticket_form', 'mmhd_ticket_form_shortcode');
function mmhd_ticket_form_shortcode()
{
    if (is_user_logged_in()) {
        ob_start();
?>
<div class="mmhd-ticket-form mmhd-container">
    <h2>Submit a Ticket</h2>
        <form method="post" id="mmhd_submit_ticket">
            <label for="ticket_title">Title:</label>
            <input type="text" name="ticket_title" id="ticket_title" required><br><br>

            <label for="ticket_content">Description:</label>
            <textarea name="ticket_content" id="ticket_content" rows="5" required></textarea><br><br>

            <label for="ticket_origin">Ticket Origin:</label>
            <?php
                    wp_dropdown_categories(array(
                        'taxonomy' => 'mmhd_ticket_origin',
                        'name' => 'ticket_origin',
                        'show_option_none' => 'Ticket Origin',
                        'hierarchical' => true,
                    ));
                    ?><br><br>

            <label for="ticket_priority">Priority:</label>
            <?php
                    wp_dropdown_categories(array(
                        'taxonomy' => 'mmhd_ticket_priority',
                        'name' => 'ticket_priority',
                        'show_option_none' => 'Select Priority',
                        'hierarchical' => true,
                    ));
                    ?><br><br>

            <input type="submit" value="Submit Ticket">
            <?php wp_nonce_field('mmhd_submit_ticket_action', 'mmhd_submit_ticket_nonce'); ?>
        </form>
</div>
<?php
        if (isset($_POST['ticket_title']) && isset($_POST['ticket_content']) && isset($_POST['ticket_origin']) && isset($_POST['ticket_priority'])) {
            if (!isset($_POST['mmhd_submit_ticket_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mmhd_submit_ticket_nonce'])), 'mmhd_submit_ticket_action')) {
                echo "<div class='mmhd-container'><p>Security check failed.</p></div>";
                return ob_get_clean();
            }
            $ticket_title = sanitize_text_field(wp_unslash($_POST['ticket_title']));
            $ticket_content = sanitize_textarea_field(wp_unslash($_POST['ticket_content']));
            $ticket_origin = (int)$_POST['ticket_origin'];
            $ticket_priority = (int)$_POST['ticket_priority'];
            $user_id = get_current_user_id();

            $origin_term = term_exists($ticket_origin, 'mmhd_ticket_origin');
            if (!$origin_term) {
                $ticket_origin = 0;
            }
            $priority_term = term_exists($ticket_priority, 'mmhd_ticket_priority');
            if (!$priority_term) {
                $ticket_priority = 0;
            }

            $post_id = wp_insert_post(array(
                'post_title'    => $ticket_title,
                'post_content'  => $ticket_content,
                'post_status'   => 'publish',
                'post_type'     => 'mmhd_ticket',
                'post_author'   => $user_id,
            ));

            if ($post_id) {
                if ($ticket_origin) {
                    wp_set_object_terms($post_id, $ticket_origin, 'mmhd_ticket_origin');
                }
                if ($ticket_priority) {
                    wp_set_object_terms($post_id, $ticket_priority, 'mmhd_ticket_priority');
                }

                do_action('mmhd_after_ticket_created', $post_id, ['title' => $ticket_title, 'content' => $ticket_content, 'origin' => $ticket_origin, 'priority' => $ticket_priority], $user_id);

                echo "<div class='mmhd-container'><p>Ticket submitted successfully!</p></div>";
            } else {
                echo "<div class='mmhd-container'><p>Error submitting ticket.</p></div>";
            }
        }

        return ob_get_clean();
    } else {
        return '<div class="mmhd-container"><p>Please <a href="' . esc_url(wp_login_url(get_permalink('login'))) . '">login</a> to submit a ticket.</p></div>';
    }
}

add_shortcode('mmhd_my_tickets', 'mmhd_my_tickets_shortcode');


function mmhd_count_comments($post_type)
{
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM $wpdb->comments 
             INNER JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID 
             WHERE $wpdb->posts.post_type = %s 
             AND $wpdb->comments.comment_approved = '1'",
            $post_type
        )
    );

    return (int) $count;
}

function mmhd_my_tickets_shortcode()
{
    global $wp;

    $user_id = get_current_user_id();
    $dashboard_base_url = get_permalink(get_queried_object_id());

    if (empty($dashboard_base_url)) {
        $dashboard_base_url = home_url('/mmhd-support/');
    }

    $paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'date';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $order  = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC';

    $allowed_orderby = array('date', 'meta_value');
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'date';
    }

    $allowed_order = array('ASC', 'DESC');
    if (!in_array($order, $allowed_order, true)) {
        $order = 'DESC';
    }

    $query_orderby = array(
        'date' => $order,
        'ID' => $order,
    );

    $args = array(
        'post_type' => 'mmhd_ticket',
        'author' => $user_id,
        'posts_per_page' => 10,
        'paged' => $paged,
    );

    if ($orderby === 'meta_value') {
        $args['meta_key'] = '_mmhd_ticket_status';
        $query_orderby = array(
            'meta_value' => $order,
            'ID' => $order,
        );
    }

    $args['orderby'] = $query_orderby;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $selected_priority = isset($_GET['mmhd_ticket_priority']) ? sanitize_text_field(wp_unslash($_GET['mmhd_ticket_priority'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $selected_status = isset($_GET['mmhd_ticket_status']) ? sanitize_text_field(wp_unslash($_GET['mmhd_ticket_status'])) : 'active-tickets';

    if (!empty($selected_priority)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'mmhd_ticket_priority',
                'field' => 'slug',
                'terms' => $selected_priority,
            ),
        );
    }

    if ($selected_status == 'active-tickets') {

        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_mmhd_ticket_status',
                'value' => 'open',
                'compare' => '=',
            ),
            array(
                'key' => '_mmhd_ticket_status',
                'value' => 'in-progress',
                'compare' => '=',
            ),
        );
    } else {


        $args['meta_query'] = array(
            array(
                'key' => '_mmhd_ticket_status',
                'value' => $selected_status,
                'compare' => '=',
            ),
        );
    }


    $query = new WP_Query($args);

    $priority_terms = get_terms(array(
        'taxonomy' => 'mmhd_ticket_priority',
        'hide_empty' => false,
    ));


    // $statuses = array('active-tickets', 'open', 'in-progress', 'archived', 'closed');

    $statuses = [
        'active-tickets' => 'Active Tickets',
        'open' => 'Open',
        'in-progress' => 'In Progress',
        'archived' => 'Archived',
        'closed' => 'Closed',
    ];

    ob_start();
    ?>
<form method="get" class="mmhd_tickets_select_form">
    <input type="hidden" name="mmhd_page" value="my-tickets">
    <select name="mmhd_ticket_priority">
        <option value="">All Priorities</option>
        <?php if (!is_wp_error($priority_terms) && !empty($priority_terms)) : ?>
        <?php foreach ($priority_terms as $term) : ?>
        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_priority, $term->slug); ?>>
            <?php echo esc_html($term->name); ?></option>
        <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <select name="mmhd_ticket_status">
        <?php foreach ($statuses as $key => $val) : ?>
        <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_status, $key); ?>>
            <?php echo esc_html(ucfirst($val)); ?>

        </option>
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
<div class="tickets_table_wrapper">
    <table>
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

                        $last_activity = !empty($comments)
                            ? strtotime($comments[0]->comment_date_gmt)
                            : get_post_modified_time('U', true);
                        $comment_author = !empty($comments) ? get_the_author_meta('display_name', $comments[0]->user_id) :
                            get_the_author_meta('display_name', $post->post_author);

                        $human_readable_time = human_time_diff($last_activity, wp_date('U'));
                        $ticket_created_utc = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso(get_post()) : get_the_date('Y-m-d H:i:s');

                ?>
            <tr class="mmhd-table-content mmhd-ticket-table-content">
                <td>
                    <?php
                                $ticket_url = add_query_arg(array('mmhd_page' => 'my-tickets', 'tid' => get_the_ID()), $dashboard_base_url);
                                ?>
                    <a class="mmhd-table-content-title"
                        href="<?php echo esc_url($ticket_url); ?>"><?php the_title(); ?></a>

                    <p class="ticket-date">
                        <span class="ticket-num"><?php echo '#' . esc_html(get_the_ID()); ?></span> <span
                            class="dashicons-calendar"> <time class="mmhd-local-time"
                                datetime="<?php echo esc_attr($ticket_created_utc); ?>"
                                data-utc="<?php echo esc_attr($ticket_created_utc); ?>"
                                data-format="date"><?php echo esc_html(get_the_date('m/d/Y')); ?></time></span>
                    </p>
                </td>
                <td><?php echo esc_html($comment_count); ?></td>
                <td><?php echo $human_readable_time ? esc_html($human_readable_time) . ' ago' : '-' ?><p
                        class="ticket-comment-by">by <?php echo esc_html($comment_author); ?></p>
                </td>
                <!-- Status -->
                <td>
                    <span class="mmhd-status-pill <?php echo esc_attr(strtolower($status)); ?>">
                        <?php echo esc_html($statuses[$status]); ?>
                    </span>
                </td>
                <!-- Priority -->
                <td>
                    <span data-color="<?php echo esc_attr($priority_color); ?>"
                        class="mmhd-priority-pill <?php echo esc_attr(strtolower($priority_name)); ?>"
                        style="background:<?php echo esc_attr($priority_color) ?>">
                        <?php echo esc_html($priority_name); ?>
                    </span>
                </td>
            </tr>
            <?php endwhile;
                    wp_reset_postdata();
                else : ?>
            <tr>
                <td colspan="5">No tickets found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
    $big = 999999999; // Need an unlikely integer
    $pagination = paginate_links(array(
        'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format'    => '?paged=%#%',
        'current'   => max(1, $paged),
        'total'     => $query->max_num_pages,
        'prev_text' => __('« Previous', 'metamint-helpdesk'),
        'next_text' => __('Next »', 'metamint-helpdesk'),
    ));

    if ($pagination) {
        echo '<div class="pagination">' . wp_kses_post($pagination) . '</div>';
    }
    return ob_get_clean();
}

// Shortcode for the frontend dashboard
add_shortcode('mmhd_frontend_dashboard', 'mmhd_frontend_dashboard_shortcode');

if (!function_exists('mmhd_is_customer_portal_enabled')) {
    function mmhd_is_customer_portal_enabled()
    {
        $settings = get_option('mmhd_advanced_settings', array());

        if (!is_array($settings) || !array_key_exists('enable_customer_portal', $settings)) {
            return true;
        }

        return filter_var($settings['enable_customer_portal'], FILTER_VALIDATE_BOOLEAN);
    }
}

function mmhd_frontend_dashboard_shortcode()
{
    if (!mmhd_is_customer_portal_enabled()) {
        return '';
    }

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $current_page = get_query_var('mmhd_page');

        if (empty($current_page)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = isset($_GET['mmhd_page']) ? sanitize_text_field(wp_unslash($_GET['mmhd_page'])) : 'dashboard';
        }

        $ticket_id = absint(get_query_var('tid'));
        if (empty($ticket_id)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $ticket_id = isset($_GET['tid']) ? absint(wp_unslash($_GET['tid'])) : 0;
        }
        // Get Ticket Counts
        $open_tickets_args = array(
            'post_type' => 'mmhd_ticket',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => '_mmhd_ticket_status',
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
            'post_type' => 'mmhd_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        $total_tickets_query = new WP_Query($total_tickets_args);
        $total_tickets = $total_tickets_query->post_count;

        $notification_count = function_exists('mmhd_get_notification_count')
            ? mmhd_get_notification_count($user_id)
            : 0;


        $menu_items = apply_filters('mmhd_frontend_dashboard_menu_items', array());
        $is_single_ticket_view = !empty($ticket_id) && get_post_type($ticket_id) == 'mmhd_ticket';
        $dashboard_page_class = $is_single_ticket_view ? 'single-ticket' : sanitize_html_class($current_page);

        ob_start();
    ?>
<div
    class="mmhd-frontend-dashboard mmhd-container mmhd-page-<?php echo esc_attr($dashboard_page_class); ?>">
    <!-- Always render both, control visibility with CSS -->
    <nav class="desktop-menu">
        <ul>
            <?php foreach ($menu_items as $slug => $menu_item):
                        $active_class = ($current_page == $slug) ? 'active' : '';
                        $url = add_query_arg('mmhd_page', $slug, get_permalink());
                        $tab_class = 'mmhd-dashboard-tab mmhd-dashboard-tab--' . sanitize_html_class($slug);
                        $icon_class = mmhd_get_dashboard_menu_icon_class($slug);
                    ?>
            <li><a href="<?php echo esc_url($url); ?>"
                    class="<?php echo esc_attr(trim($tab_class . ' ' . $active_class)); ?>"><span
                        class="dashicons <?php echo esc_attr($icon_class); ?> mmhd-dashboard-tab-icon"
                        aria-hidden="true"></span><span
                        class="mmhd-dashboard-tab-label"><?php echo esc_html($menu_item['title']); ?></span></a>
            </li>
            <?php endforeach; ?>
            <li><a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>"
                    class="mmhd-dashboard-tab mmhd-dashboard-tab--logout"><span
                        class="dashicons <?php echo esc_attr(mmhd_get_dashboard_menu_icon_class('logout')); ?> mmhd-dashboard-tab-icon"
                        aria-hidden="true"></span><span class="mmhd-dashboard-tab-label">Logout</span></a></li>
        </ul>
    </nav>
    <select id="mobile-menu" class="mobile-menu" onchange="window.location.href=this.value;">
        <?php foreach ($menu_items as $slug => $menu_item):
                    $active_class = ($current_page == $slug) ? 'selected' : '';
                    $url = add_query_arg('mmhd_page', $slug, get_permalink());
                ?>
        <option value="<?php echo esc_url($url); ?>" <?php echo esc_attr($active_class); ?>>
            <?php echo esc_html($menu_item['title']); ?>
        </option>
        <?php endforeach; ?>
        <option value="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">Logout</option>
    </select>
    <div class="mmhd-dashboard-content">
        <?php
                if ($is_single_ticket_view) {
                    echo do_shortcode('[mmhd_single_ticket id="' . $ticket_id . '"]');
                } else if (array_key_exists($current_page, $menu_items)) {
                    if ($current_page == 'dashboard') {
                        // Tab functions escape their own output internally.
                        echo wp_kses_post($menu_items[$current_page]['function']($notification_count, $open_tickets, $total_tickets));
                    } else {
                        // Tab functions escape their own output internally.
                        echo wp_kses_post($menu_items[$current_page]['function']());
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
        return '<div class="mmhd-container"><p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> to view your tickets.</p></div>';
    }
}

function mmhd_get_dashboard_menu_icon_class($slug)
{
    $icons = array(
        'dashboard' => 'dashicons-dashboard',
        'profile' => 'dashicons-admin-users',
        'my-tickets' => 'dashicons-tickets-alt',
        'notifications' => 'dashicons-bell',
        'create-ticket' => 'dashicons-plus-alt',
        'credentials-vault' => 'dashicons-lock',
        'logout' => 'dashicons-exit',
    );

    return isset($icons[$slug]) ? $icons[$slug] : 'dashicons-admin-generic';
}

/*

function mmhd_frontend_dashboard_shortcode()
{
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $current_page = get_query_var('mmhd_page');
        if (empty($current_page)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = isset($_GET['mmhd_page']) ? sanitize_key(wp_unslash($_GET['mmhd_page'])) : 'dashboard';
        }
        // Get ticket data
        $open_tickets = mmhd_get_open_ticket_count($user_id);
        $total_tickets = mmhd_get_total_ticket_count($user_id);
        $notification_count = mmhd_get_notification_count($user_id);
        $latest_reply = mmhd_get_latest_reply($user_id);

        ob_start();
    ?>
<div class="mmhd-frontend-dashboard mmhd-container">
    <nav>
        <ul>
            <li><a href="<?php echo get_permalink(); ?>"
                    class="<?php if ($current_page == 'dashboard') echo 'active'; ?>">Dashboard</a></li>
            <li><a href="<?php echo add_query_arg('mmhd_page', 'profile', get_permalink()); ?>"
                    class="<?php if ($current_page == 'profile') echo 'active'; ?>">Profile</a></li>
            <li><a href="<?php echo add_query_arg('mmhd_page', 'my-tickets', get_permalink()); ?>"
                    class="<?php if ($current_page == 'my-tickets') echo 'active'; ?>">My Tickets</a></li>
            <li><a href="<?php echo add_query_arg('mmhd_page', 'notifications', get_permalink()); ?>"
                    class="<?php if ($current_page == 'notifications') echo 'active'; ?>">Notifications
                    <?php $notification_count = mmhd_get_notification_count(get_current_user_id());
                                                                                                                                                                                                        if ($notification_count > 0) echo "<span class='mmhd-notification-count'>(" . $notification_count . ")</span>"; ?></a>
            </li>
            <li><a href="<?php echo wp_logout_url(get_permalink()); ?>">Logout</a></li>
        </ul>
    </nav>
    <div class="mmhd-dashboard-content">

        <?php
                if ($current_page == 'profile') {
                    mmhd_profile_content();
                } elseif ($current_page == 'my-tickets') {
                    echo do_shortcode('[mmhd_my_tickets]');
                } elseif ($current_page == 'notifications') {
                    mmhd_notifications_content();
                } else {
                    echo "<h2>Welcome to your Dashboard</h2>";

                ?>
        <div class="mmhd-infographs">
            <div class="mmhd-infograph">
                <h3>Tickets Open vs. Total</h3>
                <div class="mmhd-chart">
                    <div class="mmhd-open-bar"
                        style="width: <?php echo ($total_tickets > 0) ? ($open_tickets / $total_tickets * 100) : 0; ?>%;">
                        <span><?php echo $open_tickets; ?></span>
                    </div>
                    <div class="mmhd-total-bar">
                        <span><?php echo $total_tickets; ?></span>
                    </div>
                </div>
                <div class="mmhd-chart-label">
                    <span>Open</span>
                    <span>Total</span>
                </div>
            </div>

            <div class="mmhd-infograph">
                <h3>Notifications</h3>
                <div class="mmhd-notification-count-display">
                    <?php echo $notification_count; ?>
                </div>
            </div>

            <?php if ($latest_reply) : ?>
            <div class="mmhd-infograph">
                <h3>Latest Reply</h3>
                <p>Ticket: <a
                        href="<?php echo esc_url(get_permalink($latest_reply['ticket_id'])); ?>"><?php echo esc_html(get_the_title($latest_reply['ticket_id'])); ?></a>
                </p>
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
        return '<div class="mmhd-container"><p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> to access your dashboard.</p></div>';
    }
}

*/

// In public/includes/mmhd-shortcodes.php

// ... (Previous shortcodes: mmhd_ticket_form, mmhd_my_tickets, mmhd_frontend_dashboard)

function mmhd_profile_content()
{

    $current_user = wp_get_current_user();
    $user_id = get_current_user_id();

    // Validate the profile nonce once for any profile update request, then
    // reuse the result below. This keeps the nonce check impossible to bypass
    // through one of the individual blocks.
    $is_profile_submission = isset($_POST['first_name'], $_POST['last_name'])
        || (isset($_FILES['profile_picture']['error']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE)
        || !empty($_POST['password'])
        || !empty($_POST['confirm_password']);

    if ($is_profile_submission) {
        $profile_nonce = isset($_POST['mmhd_profile_nonce']) ? sanitize_text_field(wp_unslash($_POST['mmhd_profile_nonce'])) : '';
        if ($user_id <= 0 || $profile_nonce === '' || !wp_verify_nonce($profile_nonce, 'mmhd_update_profile')) {
            echo '<p style="color:red;">Nonce verification failed</p>';
            return;
        }
    }

    // Save the Edits.

    if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
        $first_name = sanitize_text_field(wp_unslash($_POST['first_name']));
        $last_name = sanitize_text_field(wp_unslash($_POST['last_name']));

        if ($first_name !== $current_user->first_name || $last_name !== $current_user->last_name) {
            $full_name = trim($first_name . ' ' . $last_name);
            $userdata = array(
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            );
            if ($full_name !== '') {
                $userdata['display_name'] = $full_name;
            }
            $user_id_update = wp_update_user($userdata);
            if (is_wp_error($user_id_update)) {
                echo '<p style="color:red;">Error updating user data: ' . esc_html($user_id_update->get_error_message()) . '</p>';
            } else {
                echo '<p style="color:green;">Profile updated successfully!</p>';
            }
        }
    }
    if (isset($_FILES['profile_picture']['error']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) { // Check for file and no error code for no file
        $max_size = 5 * 1024 * 1024; // 5MB in bytes

        $file_size = isset($_FILES['profile_picture']['size']) ? intval(wp_unslash($_FILES['profile_picture']['size'])) : 0;
        $file_name = isset($_FILES['profile_picture']['name']) ? sanitize_file_name(wp_unslash($_FILES['profile_picture']['name'])) : '';

        $allowed_types = array(
            'jpg|jpeg' => 'image/jpeg',
            'png'      => 'image/png',
            'gif'      => 'image/gif',
            'svg'      => 'image/svg+xml',
        );
        $filetype = wp_check_filetype(basename($file_name), $allowed_types);
        if (!$filetype['type']) {
            echo '<p style="color:red;">Invalid file type. Only JPG, PNG, GIF, and SVG are allowed.</p>';
            return;
        }

        if ($file_size > $max_size) {
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
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $confirm_password = isset($_POST['confirm_password']) ? wp_unslash($_POST['confirm_password']) : '';

        if ($password !== $confirm_password) {
            echo '<p style="color:red;">Passwords do not match.</p>';
        } elseif (strlen($password) < 8) {
            echo '<p style="color:red;">Password must be at least 8 characters long.</p>';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            echo '<p style="color:red;">Password must contain at least one uppercase letter.</p>';
        } elseif (!preg_match('/[a-z]/', $password)) {
            echo '<p style="color:red;">Password must contain at least one lowercase letter.</p>';
        } elseif (!preg_match('/[0-9]/', $password)) {
            echo '<p style="color:red;">Password must contain at least one number.</p>';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            echo '<p style="color:red;">Password must contain at least one special character.</p>';
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
<form method="post" enctype="multipart/form-data" class="rodobesk-update-profile-form">
    <?php wp_nonce_field('mmhd_update_profile', 'mmhd_profile_nonce'); ?>
    <div class="mmhd-form-group">
        <label for="first_name">First Name:</label><br>
        <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
    </div>
    <div class="mmhd-form-group">
        <label for="last_name">Last Name:</label><br>
        <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
    </div>
    <div class="mmhd-form-group full-width">
        <label for="profile_picture">Profile Picture:</label><br>
        <?php if ($avatar_url): ?>
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile Picture" width="96" height="96"><br>
        <?php else:
                    echo get_avatar($user_id, 96);
                endif; ?><br>
        <input type="file" name="profile_picture" id="profile_picture"><br>
        <img id="profile-preview" style="display:none; max-width:150px; max-height:150px; margin-top:8px;">
    </div>

    <div class="mmhd-form-group">
        <label for="password">New Password:</label><br>
        <span style="position:relative;display:block;">
            <input type="password" name="password" id="password" style="width:100%;padding-right:24px;">
            <span class="dashicons dashicons-visibility toggle-pw" data-target="password"
                style="position:absolute;right:2px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;line-height:1;"></span>
        </span>
        <span id="password-feedback" style="display:block;font-size:14px;"></span>
    </div>

    <div class="mmhd-form-group">
        <label for="confirm_password">Confirm New Password:</label><br>
        <span style="position:relative;display:block;">
            <input type="password" name="confirm_password" id="confirm_password" style="width:100%;padding-right:24px;">
            <span class="dashicons dashicons-visibility toggle-pw" data-target="confirm_password"
                style="position:absolute;right:5px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;line-height:1;"></span>
        </span>
        <span id="confirm-password-feedback" style="display:block;font-size:14px;"></span>
        <span id="password-match-feedback" style="display:block;font-size:14px;"></span>
    </div>

    <input type="submit" value="Update Profile" id="profile-submit">
</form>
<?php
    }
}


function mmhd_notifications_content()
{
    $user_id = get_current_user_id();
    // Use the renamed function to avoid conflict with admin AJAX handler
    if (function_exists('mmhd_get_user_notifications')) {
        $notifications = mmhd_get_user_notifications($user_id);
    } else {
        $notifications = array();
    }

    $dashboard_base_url = get_permalink(get_queried_object_id());
    if (empty($dashboard_base_url)) {
        $dashboard_base_url = home_url('/mmhd-support/');
    }

    $unread_count = 0;
    foreach ($notifications as $notification) {
        if (empty($notification->read_at)) {
            $unread_count++;
        }
    }
    ?>
<div class="mmhd-notifications-header">
    <h2>Notifications</h2>
    <?php if ($unread_count > 0): ?>
    <button id="mmhd-mark-all-notifications-read" class="mmhd-mark-all-read-btn" type="button">Mark all as
        read</button>
    <?php endif; ?>
</div>
<?php if ($notifications): ?>
<div class="mmhd-content-list mmhd-notifications-list-wrap">
    <ul class="mmhd-notifications-list">
        <?php foreach ($notifications as $notification): ?>
        <?php
                    $ticket_id = isset($notification->ticket_id) ? (int) $notification->ticket_id : 0;
                    $ticket_title = $ticket_id > 0 ? get_the_title($ticket_id) : '';
                    if ($ticket_title === '') {
                        $ticket_title = 'ticket';
                    }

                    $ticket_url = '#';
                    if ($ticket_id > 0) {
                        $ticket_url = add_query_arg(
                            array(
                                'mmhd_page' => 'my-tickets',
                                'tid' => $ticket_id,
                            ),
                            $dashboard_base_url
                        );

                        $comment_id = isset($notification->comment_id) ? (int) $notification->comment_id : 0;
                        if ($comment_id > 0) {
                            $ticket_url .= '#comment-' . $comment_id;
                        }
                    }

                    $actor_name = 'Someone';
                    if (!empty($notification->created_by)) {
                        $actor = get_userdata((int) $notification->created_by);
                        if ($actor && !empty($actor->display_name)) {
                            $actor_name = $actor->display_name;
                        }
                    }

                    $notification_text = (string) $notification->message;
                    if (($notification->type ?? '') === 'reply_added') {
                        $notification_text = sprintf('%s has replied to', $actor_name);
                    }

                    $notification_utc = function_exists('mmhd_datetime_to_utc_iso') ? mmhd_datetime_to_utc_iso($notification->notification_date) : $notification->notification_date;
                    $formatted_time = date_i18n('F j, Y, g:i a', strtotime($notification->notification_date));
                    ?>
        <?php $is_unread = empty($notification->read_at); ?>
        <li class="mmhd-notification-item <?php echo $is_unread ? 'is-unread' : 'is-read'; ?>"
            data-notification-id="<?php echo esc_attr((int) $notification->id); ?>">
            <div class="mmhd-notification-main">
                <?php echo esc_html($notification_text); ?>
                <a class="mmhd-notification-ticket-link" href="<?php echo esc_url($ticket_url); ?>"
                    data-notification-id="<?php echo esc_attr((int) $notification->id); ?>"><?php echo esc_html($ticket_title); ?></a>
            </div>
            <small class="mmhd-notification-time"><time class="mmhd-local-time"
                    datetime="<?php echo esc_attr($notification_utc); ?>"
                    data-utc="<?php echo esc_attr($notification_utc); ?>"
                    data-format="datetime"><?php echo esc_html($formatted_time); ?></time></small>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php else: ?>
<p>No notifications found.</p>
<?php endif;
}


function mmhd_get_open_ticket_count($user_id)
{
    $args = array(
        'post_type' => 'mmhd_ticket',
        'author' => $user_id,
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_mmhd_ticket_status',
                'value' => 'open',
                'compare' => '=',
            ),
        ),
    );
    $query = new WP_Query($args);
    return $query->post_count;
}

function mmhd_get_total_ticket_count($user_id)
{
    $args = array(
        'post_type' => 'mmhd_ticket',
        'author' => $user_id,
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    return $query->post_count;
}

function mmhd_get_latest_reply($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mmhd_ticket_notes';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY notification_date DESC LIMIT 1", $user_id);
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->get_row($query, ARRAY_A);
    if ($result) {
        $ticket_id = $result['ticket_id'];
        return array("ticket_id" => $ticket_id, "note_content" => $result['note_content']);
    }
    return false;
}


function mmhd_frontend_dashboard_menu($items)
{
    $new_items = array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'function' => 'mmhd_dashboard_content_page',
        ),
        'profile' => array(
            'title' => 'Profile',
            'function' => 'mmhd_profile_content_page',
        ),
        'my-tickets' => array(
            'title' => 'My Tickets',
            'function' => 'mmhd_my_tickets_content_page',
        ),
        'notifications' => array(
            'title' => 'Notifications',
            'function' => 'mmhd_notifications_content_page',
        ),
        'create-ticket' => array(
            'title' => 'Create Ticket',
            'capability' => 'read',
            'menu_slug' => 'create-ticket',
            'function' => 'mmhd_create_ticket_page',
        ),
        'credentials-vault' => array(
            'title' => 'Credentials Vault',
            'capability' => 'read',
            'menu_slug' => 'credentials-vault',
            'function' => 'mmhd_credentials_page',
        ),
    );
    return $new_items;
}
add_filter('mmhd_frontend_dashboard_menu_items', 'mmhd_frontend_dashboard_menu');

function mmhd_create_ticket_page()
{
    if (is_user_logged_in()) {

        ob_start();
    ?>
<div class="mmhd-container">
    <h2>Create New Ticket</h2>
    <form method="post" id="mmhd-create-ticket">
        <label for="ticket_title">Title:</label><br>
        <input type="text" id="ticket_title" name="ticket_title" required><br><br>

        <label for="ticket_content">Description:</label><br>
        <!-- <textarea id="ticket_content" name="ticket_content" rows="4" required></textarea><br><br> -->
        <?php
                $args = array(
                    'media_buttons' => false,
                    'textarea_name' => 'ticket_content',
                    'textarea_rows' => get_option('default_post_edit_rows', 10),
                    'quicktags'     => false,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink',
                        'toolbar2' => '',
                        'wp_autoresize_on' => true,
                        'resize' => false,
                        'menubar' => false,
                        'plugins' => 'link',
                    ),
                );
                wp_editor('', 'ticket_content', $args);
                ?>

        <label for="ticket_priority">Priority:</label><br>
        <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'mmhd_ticket_priority',
                    'name' => 'ticket_priority',
                    'show_option_none' => 'Select Priority',
                    'hierarchical' => true,
                    'hide_empty' => false,
                    'post_type' => 'mmhd_ticket'
                ));
                ?><br><br>

        <input type="hidden" name="action" value="mmhd_create_new_ticket">
        <?php wp_nonce_field('mmhd_create_ticket_nonce'); ?>
        <input type="submit" value="Create Ticket">

    </form>
    <div id="mmhd-response-message"></div>
</div>
<?php
        return ob_get_clean();
    } else {
        return '<div class="mmhd-container"><p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> to create a ticket.</p></div>';
    }
}



function mmhd_dashboard_content_page($notification_count, $open_tickets, $total_tickets)
{

    ob_start();
    ?>
<div class="mmhd-infographs">

    <div class="mmhd-infograph flex-1">
        <h3 class="text-start">Tickets</h3>
        <div class="mmhd-chart">
            <div class="mmhd-open-bar"
                style="width: <?php echo esc_attr(($total_tickets > 0) ? ($open_tickets / $total_tickets * 100) : 0); ?>%;">
                <span><?php echo esc_html($open_tickets); ?></span>
            </div>
            <div class="mmhd-total-bar">
                <span><?php echo esc_html($total_tickets); ?></span>
            </div>
        </div>
        <div class="mmhd-chart-label">
            <span>Open</span>
            <span>Total</span>
        </div>
    </div>

    <div class="mmhd-infograph flex-1">
        <h3 class="text-start">Notifications</h3>
        <div class="mmhd-notification-count-display">
            <?php echo esc_html($notification_count); ?>
        </div>
    </div>
</div>
<?php
    return ob_get_clean();
}


// Profile Content Page
function mmhd_profile_content_page()
{
    // ... profile page content
    ob_start();

    mmhd_profile_content();
    return ob_get_clean();
}


function mmhd_my_tickets_content_page()
{
    // ... my tickets page content


    return mmhd_my_tickets_shortcode();
}


function mmhd_notifications_content_page()
{
    // ... notifications page content
    ob_start();
?>
<!-- <div>Notifications Page Content</div> -->
<?php
    mmhd_notifications_content();
    return ob_get_clean();
}

// Add query vars
function mmhd_add_query_vars($vars)
{
    $vars[] = 'mmhd_ticket_id';
    $vars[] = 'mmhd_page';
    return $vars;
}
add_filter('query_vars', 'mmhd_add_query_vars');


function mmhd_single_ticket_shortcode($atts)
{

    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    $ticket_id = intval($atts['id']);


    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        return '<div class="mmhd-container"><p>Invalid ticket ID.</p></div>';
    }

    $ticket = get_post($ticket_id);

    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'editor', 'support_agent', 'support_manager');
    $is_author = (int) $ticket->post_author === $current_user->ID;
    $is_staff = array_intersect($allowed_roles, $current_user->roles);

    if (!$is_author && empty($is_staff)) {
        return '<div class="mmhd-container"><p>You do not have permission to view this ticket.</p></div>';
    }
    $status = get_post_meta($ticket_id, '_mmhd_ticket_status', true);
    if ($status === '') {
        $status = 'open';
    }
    $priority_terms = wp_get_object_terms($ticket_id, 'mmhd_ticket_priority');

    $priority = !empty($priority_terms) && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'N/A';

    $priority_color = false;
    if (!empty($priority_terms)) {

        $priority_color = get_term_meta($priority_terms[0]->term_id, 'priority_color', true);
    }

    $created_date = get_the_date('Y-m-d H:i:s', $ticket);
    $created_utc = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso($ticket) : $created_date;

    $comments = get_comments(array(
        'post_id' => $ticket_id,
        'number' => 1,
        'status' => 'approve',
        'orderby' => 'comment_date_gmt',
        'order' => 'DESC'
    ));
    $last_activity = !empty($comments) ?  strtotime($comments[0]->comment_date_gmt) : get_the_modified_time('U', $ticket);

    $updated_date = wp_date('Y-m-d H:i:s', $last_activity);
    $updated_utc = function_exists('mmhd_datetime_to_utc_iso') ? mmhd_datetime_to_utc_iso($last_activity) : $updated_date;
    $created_display = get_the_date('m/d/Y', $ticket);
    $updated_display = wp_date('m/d/Y', $last_activity);
    $date_meta_label = 'Last Updated';
    $date_meta_display = $updated_display;
    $time_ago = human_time_diff($last_activity, current_time('timestamp'));
    $time_ago_class = '';

    foreach (array('_mmhd_due_date', 'mmhd_due_date', '_ticket_due_date', 'ticket_due_date', 'due_date') as $due_date_meta_key) {
        $due_date_value = get_post_meta($ticket_id, $due_date_meta_key, true);
        if (!empty($due_date_value)) {
            $due_timestamp = strtotime((string) $due_date_value);
            $date_meta_label = 'Due Date';
            $date_meta_display = $due_timestamp ? date_i18n('m/d/Y', $due_timestamp) : (string) $due_date_value;
            break;
        }
    }

    $origin = get_post_meta($ticket_id, 'mmhd_ticket_origin', true);
    if (empty($origin)) {
        $origin_terms = wp_get_post_terms($ticket_id, 'mmhd_ticket_origin');
        if (!is_wp_error($origin_terms) && !empty($origin_terms)) {
            $origin = $origin_terms[0]->name;
        }
    }
    $origin = $origin ?: 'Web';

    $department_terms = wp_get_post_terms($ticket_id, 'mmhd_ticket_category');
    $department = (!is_wp_error($department_terms) && !empty($department_terms)) ? $department_terms[0]->name : '';
    if ($department === '') {
        $department_terms = get_the_terms($ticket_id, 'mmhd_ticket_category');
        $department = (!is_wp_error($department_terms) && !empty($department_terms)) ? $department_terms[0]->name : '';
    }
    if ($department === '') {
        global $wpdb;
        // Fallback for frontend views where taxonomy helper caches may not include the latest admin selection.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $department = (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT t.name
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tr.object_id = %d
                AND tt.taxonomy = %s
                LIMIT 1",
                $ticket_id,
                'mmhd_ticket_category'
            )
        );
    }
    if ($department === '') {
        $default_department_id = get_option('mmhd_default_ticket_category', '');
        if (!empty($default_department_id)) {
            $default_department = get_term((int) $default_department_id, 'mmhd_ticket_category');
            if ($default_department && !is_wp_error($default_department)) {
                $department = $default_department->name;
            }
        }
    }
    $department = $department ?: 'General';

    $ticket_author = get_userdata((int) $ticket->post_author);
    $ticket_author_name = $ticket_author && !empty($ticket_author->display_name) ? $ticket_author->display_name : 'Customer';


    $human_readable_time = human_time_diff($last_activity, current_time('U'));

    //new code ends

    $current_user = wp_get_current_user();
    $caps = $current_user->caps;

    $allowed_roles = array('administrator', 'editor', 'support_agent', 'support_manager');




    $show_private_checkbox = array_intersect($allowed_roles, $current_user->roles);

    if (strtotime($updated_date) < strtotime('-30 hours')) {
        $time_ago_class = 'mmhd-time-ago-over-30';
    }

    global $post, $withcomments;
    $original_post = $post;
    $post = $ticket;
    $withcomments = true;
    ob_start();
    /**  
    
    <td><?php echo $human_readable_time ? $human_readable_time .' ago' : '-'?></td>
<td><span class="mmhd-status-pill <?php echo strtolower($status); ?>">
        <?php echo esc_html(ucwords($status)); ?></span></td>
<td><span data-color="<?php echo esc_attr($priority_color); ?>"
        class="mmhd-priority-pill <?php echo strtolower($priority_name);?>"
        style="background:<?php echo esc_attr($priority_color) ?>"><?php echo esc_html($priority_name); ?></span></td>
*/
?>
<div class="mmhd-single-ticket mmhd-container">
    <div class="mmhd-single-ticket-shell">
        <div class="mmhd-ticket-main-panel">

            <div class="mmhd-ticket-comments">
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

        <aside class="mmhd-ticket-meta-panel">

            <div class="mmhd-ticket-meta-card mmhd-ticket-meta-conversation">
                <span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
                <span>Conversation Id: <?php echo esc_html($ticket_id); ?></span>
            </div>
            <div class="mmhd-ticket-meta-card mmhd-ticket-meta-status">
                <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                <span>Status : <?php echo esc_html(ucwords(str_replace('-', ' ', $status))); ?></span>
            </div>
            <div class="mmhd-ticket-meta-card mmhd-ticket-meta-priority"
                <?php if ($priority_color) : ?>style="--mmhd-priority-color: <?php echo esc_attr($priority_color); ?>"
                <?php endif; ?>>
                <span class="dashicons dashicons-tag" aria-hidden="true"></span>
                <span>Priority : <?php echo esc_html(ucwords($priority)); ?></span>
            </div>
            <div class="mmhd-ticket-meta-card">
                <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                <span>Created at: <time class="mmhd-local-time"
                        datetime="<?php echo esc_attr($created_utc); ?>"
                        data-utc="<?php echo esc_attr($created_utc); ?>"
                        data-format="date"><?php echo esc_html($created_display); ?></time></span>
            </div>
            <div class="mmhd-ticket-meta-card">
                <span class="dashicons dashicons-calendar" aria-hidden="true"></span>
                <span><?php echo esc_html($date_meta_label); ?>:
                    <?php if ($date_meta_label === 'Last Updated') : ?>
                    <time class="mmhd-local-time" datetime="<?php echo esc_attr($updated_utc); ?>"
                        data-utc="<?php echo esc_attr($updated_utc); ?>"
                        data-format="date"><?php echo esc_html($date_meta_display); ?></time>
                    <?php else : ?>
                    <?php echo esc_html($date_meta_display); ?>
                    <?php endif; ?>
                </span>
            </div>
        </aside>
    </div>

    <div class="mmhd-ticket-meta">
    </div>
</div>
<?php
    static $single_ticket_js_enqueued = false;
    if (!$single_ticket_js_enqueued) {
        $single_ticket_js_enqueued = true;
        $ajax_url = wp_json_encode(admin_url('admin-ajax.php'));
        add_action('wp_footer', function () use ($ajax_url) {
            wp_add_inline_script('mmhd-scripts', '
jQuery(document).ready(function($) {
    var originalFormParent = $(\'#respond\').parent();
    var originalFormSibling = $(\'#respond\').next();

    function scrollToLatestReply() {
        var commentList = $(\'.mmhd-ticket-comments .comment-list\');

        if (!commentList.length) {
            return;
        }

        commentList.scrollTop(commentList[0].scrollHeight);
    }

    function hasReplyContent(content) {
        var hasMedia = /<(img|iframe|video|audio|embed|object)\b/i.test(content);
        var text = $(\'<div>\').html(content).text().replace(/\u00a0/g, \' \').trim();

        return hasMedia || text.length > 0;
    }

    setTimeout(scrollToLatestReply, 100);

    $(\'.mmhd-ticket-comments #commentform\').on(\'submit\', function(e) {
        if (typeof tinymce !== \'undefined\' && tinymce.get(\'comment\')) {
            tinymce.triggerSave();
            setTimeout(function() {}, 100);
        }
        var button = $(this).find(\'input[type="submit"]\');
        e.preventDefault();
        var form = $(this);
        var commentField = form.find(\'[name="comment"]\');
        var commentContent = commentField.val();

        if (!hasReplyContent(commentContent)) {
            alert(\'Please enter a reply before submitting.\');
            return;
        }

        var formData = new FormData(this);

        formData.append(\'action\', \'mmhd_submit_comment\');
        $.ajax({
            url: ' . $ajax_url . ',
            type: \'POST\',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                button.val(\'Posting...\');
                button.prop(\'disabled\', true);
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(\'Error: \' + response.data.message);
                }
            },
            error: function() {
                alert(\'An error occurred while submitting the comment.\');
            },

            complete: function() {
                setTimeout(function() {
                    button.val(\'Submit\');
                    button.prop(\'disabled\', false);
                }, 3000);
            }
        });
    });

    $(\'.mmhd-ticket-comments .comment-reply-link\').on(\'click\', function(e) {
        e.preventDefault();
        var commentId = $(this).data(\'commentid\');
        $(\'#comment_parent\').val(commentId);
        $(\'#respond\').insertAfter($(this).closest(\'.comment-body\'));
        $(\'#cancel-comment-reply-link\').show();
        var commentId = $(this).data(\'commentid\');
        var postId = $(this).data(\'postid\');
        var belowElement = $(this).data(\'belowelement\');
        var respondElement = $(this).data(\'respondelement\');

        if (typeof tinymce !== \'undefined\' && tinymce.get(\'comment\')) {
            tinymce.EditorManager.execCommand(\'mceRemoveEditor\', true, \'comment\');
        }

        if (typeof addComment !== \'undefined\' && addComment.moveForm) {
            addComment.moveForm(belowElement, commentId, respondElement, postId);
        }

        if (typeof tinymce !== \'undefined\') {
            tinymce.EditorManager.execCommand(\'mceAddEditor\', true, \'comment\');
        }
    });

    $(\'#cancel-comment-reply-link\').on(\'click\', function(e) {
        e.preventDefault();

        if (typeof tinymce !== \'undefined\' && tinymce.get(\'comment\')) {
            tinymce.EditorManager.execCommand(\'mceRemoveEditor\', true, \'comment\');
        }

        if (originalFormSibling.length) {
            originalFormParent.insertBefore($(\'#respond\'), originalFormSibling);
        } else {
            originalFormParent.append($(\'#respond\'));
        }


        $(this).hide();
        $(\'#comment_parent\').val(\'\');

        if (typeof tinymce !== \'undefined\') {
            tinymce.EditorManager.execCommand(\'mceAddEditor\', true, \'comment\');
        }
    });
});
            ', 'after');
        });
    }
    $output = ob_get_clean();
    $post = $original_post;
    return $output;
}
add_shortcode('mmhd_single_ticket', 'mmhd_single_ticket_shortcode');

function mmhd_reply_has_visible_content($content)
{
    $content = (string) $content;

    if (preg_match('/<(img|iframe|video|audio|embed|object)\b/i', $content)) {
        return true;
    }

    $content = str_replace('&nbsp;', ' ', $content);
    $content = html_entity_decode($content, ENT_QUOTES, get_bloginfo('charset'));
    $content = wp_strip_all_tags($content);
    $content = preg_replace('/\x{00a0}/u', ' ', $content);

    return trim((string) $content) !== '';
}

function mmhd_submit_comment()
{
    if (!isset($_POST['mmhd_reply_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mmhd_reply_nonce'])), 'mmhd_reply_action')) {
        wp_send_json_error(array(
            'message' => 'Security check failed.'
        ));
    }

    $ticket_id = isset($_POST['comment_post_ID']) ? intval($_POST['comment_post_ID']) : 0;
    if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'mmhd_ticket') {
        wp_send_json_error(array(
            'message' => 'Invalid ticket.'
        ));
    }

    $ticket = get_post($ticket_id);
    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'editor', 'mmhd_support_staff', 'mmhd_support_manager');
    $is_author = $current_user->ID > 0 && (int) $ticket->post_author === $current_user->ID;
    $is_staff = current_user_can('mmhd_access_dashboard') || current_user_can('manage_options') || (bool) array_intersect($allowed_roles, $current_user->roles);

    if (!$is_author && empty($is_staff)) {
        wp_send_json_error(array(
            'message' => 'You do not have permission to reply to this ticket.'
        ));
    }

    $reply_content = isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '';

    if (!mmhd_reply_has_visible_content($reply_content)) {
        wp_send_json_error(array(
            'message' => 'Please enter a reply before submitting.'
        ));
    }

    add_filter('wp_is_comment_flood', '__return_false');

    $comment = wp_handle_comment_submission(wp_unslash($_POST));

    remove_filter('wp_is_comment_flood', '__return_false');

    global $wpdb;
    if (is_wp_error($comment)) {
        wp_send_json_error(array(
            'message' => $comment->get_error_message()
        ));
    } else {
        $comment_id = $comment->comment_ID;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->comments,
            array('comment_type' => 'ticket_replies'),
            array('comment_ID' => $comment_id),
            array('%s'),
            array('%d')
        );
        wp_send_json_success();
    }
}
add_action('wp_ajax_mmhd_submit_comment', 'mmhd_submit_comment');
add_action('wp_ajax_nopriv_mmhd_submit_comment', 'mmhd_submit_comment');


//Shortcode for login form
function mmhd_login_form_shortcode()
{
    if (is_user_logged_in()) {
        $dashboard_page = get_page_by_path('dashboard');
        $redirect_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url();

        wp_safe_redirect($redirect_url);
        exit;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['login']) && sanitize_text_field(wp_unslash($_GET['login'])) == 'failed') {
        $error_message = '<p class="mmhd-error">Invalid username or password.</p>';
    } else {
        $error_message = '';
    }

    ob_start();
?>
<div class="mmhd-login-form mmhd-container">
    <h2>Login</h2>
    <?php
        echo wp_kses_post($error_message);
        ?>
    <form name="mmhd-login-form" id="mmhd-login-form" action="<?php echo esc_url(site_url('wp-login.php')); ?>"
        method="post">
        <p>
            <label for="user_login">Username or Email<br />
                <input type="text" name="log" id="user_login" class="input" value="<?php
                                                                                        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- login form field repopulation, nonce not applicable
                                                                                        echo esc_attr(sanitize_text_field(wp_unslash(isset($_POST['log']) ? $_POST['log'] : '')));
                                                                                        ?>" size="20"
                    autocapitalize="off" />
            </label>
        </p>
        <p>
            <label for="user_pass">Password<br />
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" />
            </label>
        </p>
        <p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme"
                    value="forever" /> Remember Me</label></p>
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
add_shortcode('mmhd_login_form', 'mmhd_login_form_shortcode');


/**
 * FAQ Shortcode
 *
 * [mmhd_faq accordion="true" category="slug"]
 *
 * Parameters:
 *   accordion - 'true' | 'false' (default: false)
 *   category  - FAQ category slug (optional; if omitted, all FAQs are shown)
 *
 * Outputs FAQs in either accordion or question-answer format and includes
 * Google FAQPage JSON-LD schema (https://schema.org/FAQPage).
 */
add_shortcode('mmhd_faq', 'mmhd_faq_shortcode');
function mmhd_faq_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'accordion' => 'false',
        'category'  => '',
    ), $atts, 'mmhd_faq');

    $accordion = filter_var($atts['accordion'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($accordion === null) {
        $accordion = ('true' === strtolower($atts['accordion']));
    }

    $category = sanitize_text_field($atts['category']);

    $args = array(
        'post_type'      => 'mmhd_faq',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    );

    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'mmhd_faq_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<div class="mmhd-container"><p>' . esc_html__('No FAQs found.', 'metamint-helpdesk') . '</p></div>';
    }

    ob_start();

    $faq_items = array();
    while ($query->have_posts()) {
        $query->the_post();
        $faq_items[] = array(
            'id'      => get_the_ID(),
            'title'   => get_the_title(),
            'content' => get_the_content(),
        );
    }
    wp_reset_postdata();

    // ------------------------------------------------------------------
    // Google FAQPage JSON-LD schema (https://developers.google.com/search/docs/appearance/structured-data/faq-page)
    // ------------------------------------------------------------------
    $schema_main_entity = array();
    foreach ($faq_items as $faq) {
        $schema_main_entity[] = array(
            '@type' => 'Question',
            'name'  => $faq['title'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => wp_strip_all_tags($faq['content']),
            ),
        );
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $schema_main_entity,
    );
    ?>
    <div class="mmhd-shortcode-faq-container mmhd-shortcode-faq-mode-<?php echo esc_attr($accordion ? 'accordion' : 'qa'); ?> <?php echo esc_attr($accordion ? 'mmhd-shortcode-faq-accordion' : 'mmhd-shortcode-faq-qa'); ?>">
        <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

        <?php if ($accordion) : ?>
            <div class="mmhd-shortcode-faq-accordion" id="mmhd-shortcode-faq-accordion">
                <?php foreach ($faq_items as $faq) : ?>
                    <div class="mmhd-shortcode-faq-item" id="mmhd-shortcode-faq-item-<?php echo absint($faq['id']); ?>">
                        <button class="mmhd-shortcode-faq-question"
                                type="button"
                                aria-expanded="false"
                                aria-controls="mmhd-shortcode-faq-answer-<?php echo absint($faq['id']); ?>">
                            <?php echo esc_html($faq['title']); ?>
                            <span class="mm-faq-icon">+</span>
                        </button>
                        <div class="mmhd-shortcode-faq-answer" id="mmhd-shortcode-faq-answer-<?php echo absint($faq['id']); ?>" hidden>
                            <?php echo apply_filters('the_content', $faq['content']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mmhd-shortcode-faq-qa">
                <?php foreach ($faq_items as $faq) : ?>
                    <div class="mmhd-shortcode-faq-qa-item">
                        <h3 class="mmhd-shortcode-faq-question"><?php echo esc_html($faq['title']); ?></h3>
                        <div class="mmhd-shortcode-faq-answer"><?php echo apply_filters('the_content', $faq['content']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $output = ob_get_clean();

    return $output;
}
