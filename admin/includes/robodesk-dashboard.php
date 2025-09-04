<?php
// Prevent direct access to the file and ensure WordPress functions are available
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure WordPress core functions are available if not running in the admin context
if (!function_exists('check_admin_referer')) {
    if (file_exists(dirname(__FILE__, 6) . '/wp-load.php')) {
        require_once dirname(__FILE__, 6) . '/wp-load.php';
    } elseif (file_exists(dirname(__FILE__, 6) . '/wp-admin/includes/admin.php')) {
        require_once dirname(__FILE__, 6) . '/wp-admin/includes/admin.php';
    }
}

/**
 * Callback function to render the Robodesk Support Ticket Dashboard
 */
function robodesk_dashboard_callback()
{
    // Check if the user has the required capability to view the dashboard
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'robodesk'));
    }

    // get the current user ID and name
    // This is useful for debugging or displaying user-specific information
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name ?: $current_user->user_login;
    $current_user_role = $current_user->roles ? $current_user->roles[0] : 'Support Staff';
    // var_dump($current_user_role); // Debugging line to check current user role

    // Pass current user info to JS
    echo '<script>
        const robodeskCurrentUser = {
            id: ' . intval($current_user_id) . ',
            name: ' . json_encode($current_user_name) . ',
            role: ' . json_encode($current_user_role) . '
        };
    </script>';

    // var_dump($current_user_id); // Debugging line to check current user ID and name
    // var_dump($current_user_name); // Debugging line to check current user name

    // Start output buffering to capture the HTML
    ob_start();
?>

    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <button onclick="showPage('dashboard')" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-ticket-alt text-blue-600 text-2xl"></i>
                                <span class="ml-2 text-xl font-bold text-gray-900"><?php _e('Robodesk', 'robodesk'); ?></span>
                            </button>
                        </div>
                        <div class="hidden md:ml-10 md:flex md:space-x-8">
                            <button onclick="showPage('dashboard')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="dashboard">
                                <i class="fas fa-home mr-2"></i>
                                <?php _e('Dashboard', 'robodesk'); ?>
                            </button>
                            <button onclick="showPage('tickets')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="tickets">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                <?php _e('Conversations', 'robodesk'); ?>
                            </button>
                            <button onclick="showPage('new-ticket')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="new-ticket">
                                <i class="fas fa-plus mr-2"></i>
                                <?php _e('New Conversation', 'robodesk'); ?>
                            </button>
                            <button onclick="showPage('settings')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="settings">
                                <i class="fas fa-cog mr-2"></i>
                                <?php _e('Settings', 'robodesk'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="bg-gray-100 p-2 rounded-full text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bell"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Dashboard Page -->

            <?php
            include 'robodesk-dashboard-stats.php';
            ?>

            <!---------------------------------- New Ticket Page ---------------------------------->
            <?php
            include 'robodesk-create-conversation.php';
            ?>

            <!------------------------------------- Tickets Page ------------------------------------->
            <?php
            // Get filter parameters from URL
            $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
            $customer_filter = isset($_GET['customer']) ? sanitize_text_field($_GET['customer']) : '';
            $agent_filter = isset($_GET['agent']) ? sanitize_text_field($_GET['agent']) : '';
            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

            // Get the current page number from URL parameter
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $posts_per_page = 10; // Number of tickets per page
            $offset = ($current_page - 1) * $posts_per_page;

            // Build the base query arguments
            $args = array(
                'post_type' => 'robodesk_ticket',
                'post_status' => 'publish',
                'posts_per_page' => $posts_per_page,
                'offset' => $offset,
                'orderby' => 'modified',
                'order' => 'DESC'
            );

            // Add meta query for status filter
            if ($status_filter !== 'all') {
                $args['meta_query'][] = array(
                    'key' => '_robodesk_ticket_status',
                    'value' => $status_filter,
                    'compare' => '='
                );
            }

            // Add meta query for agent filter
            if (!empty($agent_filter)) {
                $args['meta_query'][] = array(
                    'key' => 'ticket_assignee',
                    'value' => $agent_filter,
                    'compare' => 'LIKE'
                );
            }

            // Add search functionality - check if it's a ticket ID or general search
            if (!empty($search_term)) {
                // Check if search term is numeric (potential ticket ID)
                if (is_numeric($search_term)) {
                    $args['post__in'] = array(intval($search_term));
                } else {
                    $args['s'] = $search_term;
                }
            }

            // Add author filter for customer search
            if (!empty($customer_filter)) {
                // Search for users by display name or email
                $user_query = new WP_User_Query(array(
                    'search' => '*' . $customer_filter . '*',
                    'search_columns' => array('display_name', 'user_email', 'user_login'),
                    'fields' => 'ID'
                ));

                $user_ids = $user_query->get_results();

                if (!empty($user_ids)) {
                    $args['author__in'] = $user_ids;
                } else {
                    // If no users found, set impossible condition to return no results
                    $args['author__in'] = array(0);
                }
            }

            // Set meta_query relation if multiple meta queries exist
            if (isset($args['meta_query']) && count($args['meta_query']) > 1) {
                $args['meta_query']['relation'] = 'AND';
            }

            // Get total count with same filters for pagination
            $total_args = $args;
            $total_args['posts_per_page'] = -1;
            $total_args['fields'] = 'ids';
            unset($total_args['offset']);
            $total_query = new WP_Query($total_args);
            $total_posts = $total_query->found_posts;
            $total_pages = ceil($total_posts / $posts_per_page);

            // Main query with filters
            $query = new WP_Query($args);
            $tickets = []; // Initialize tickets array

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $ticket_id = get_the_ID();
                    $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'N/A';
                    $ticket_origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true) ?: 'N/A';
                    $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
                    $author_image = get_avatar_url(get_the_author_meta('ID'), array('size' => 32));
                    $ticket_priority = $priority_terms ? $priority_terms[0]->name : 'N/A';
                    $updated_at = get_the_modified_date('Y-m-d H:i:s', $ticket_id);
                    $created_at = get_the_date('Y-m-d H:i:s', $ticket_id);
                    $ticket_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_category');
                    $ticket_category = '';
                    if (!is_wp_error($ticket_terms) && !empty($ticket_terms)) {
                        $ticket_category = [
                            'name' => $ticket_terms[0]->name,
                            'id' => $ticket_terms[0]->term_id,
                        ];
                    }

                    // Simulate replies field using comments
                    $replies = get_comments(array('post_id' => $ticket_id));
                    $replies_count = count($replies);
                    if ($replies_count > 0) {
                        $replies = array_map(function ($reply) {
                            return [
                                'id' => $reply->comment_ID,
                                'author' => $reply->comment_author,
                                'content' => $reply->comment_content,
                                'date' => $reply->comment_date
                            ];
                        }, $replies);
                    } else {
                        $replies = [];
                    }

                    $tickets[] = [
                        'id' => $ticket_id,
                        'title' => get_the_title(),
                        'customer' => get_the_author(),
                        'email' => get_the_author_meta('email'),
                        'author_id' => get_the_author_meta('ID'),
                        'source' => $ticket_origin,
                        'status' => $ticket_status,
                        'priority' => $ticket_priority,
                        'created' => $created_at,
                        'author_image' => $author_image,
                        'updated' => $updated_at,
                        'category' => $ticket_category,
                        'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'N/A',
                        'replies' => $replies_count ? $replies : [
                            'id' => 0,
                            'author' => 'N/A',
                            'content' => 'No replies yet',
                            'date' => $updated_at
                        ],
                        'replies_count' => $replies_count
                    ];
                }
            }
            wp_reset_postdata();

            // Function to generate pagination URL with current filters
            function get_pagination_url($page)
            {
                $current_url = admin_url('admin.php');
                $params = $_GET;
                $params['paged'] = $page;
                return add_query_arg($params, $current_url);
            }

            // Function to build filter URL
            function get_filter_url($new_params = array())
            {
                $current_url = admin_url('admin.php');
                $params = $_GET;

                // Remove paged parameter when filtering to start from page 1
                unset($params['paged']);

                // Merge new parameters
                $params = array_merge($params, $new_params);

                return add_query_arg($params, $current_url);
            }
            ?>

            <?php
            include 'robodesk-conversations.php';
            ?>

            <!------------------------------- Ticket Detail Page ----------------------->
            <?php
            include 'robodesk-conversation-detail.php';
            ?>

            <!---------------------- Settings Page ---------------------->
            <?php
            include 'robodesk-settings.php';
            ?>
        </main>

        <!-- Toast Notification -->
        <div id="toast" class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg hidden">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span id="toast-message"></span>
            </div>
        </div>


    </div>

<?php
    // Output the buffered content
    echo ob_get_clean();
}
