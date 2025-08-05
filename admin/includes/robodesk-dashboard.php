<?php
// File: robodesk_dashboard_callback.php (e.g., inside a WordPress plugin or theme file)

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

    // Pass current user info to JS
    echo '<script>
        const robodeskCurrentUser = {
            id: ' . intval($current_user_id) . ',
            name: ' . json_encode($current_user_name) . '
        };
    </script>';

    // Debugging line to check current user ID and name
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
                                <span class="ml-2 text-xl font-bold text-gray-900">Robodesk</span>
                            </button>
                        </div>
                        <div class="hidden md:ml-10 md:flex md:space-x-8">
                            <button onclick="showPage('dashboard')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="dashboard">
                                <i class="fas fa-home mr-2"></i>
                                Dashboard
                            </button>
                            <button onclick="showPage('tickets')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="tickets">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                Tickets
                            </button>
                            <button onclick="showPage('new-ticket')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="new-ticket">
                                <i class="fas fa-plus mr-2"></i>
                                New Ticket
                            </button>
                            <button onclick="showPage('settings')" class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" data-page="settings">
                                <i class="fas fa-cog mr-2"></i>
                                Settings
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
            <div id="dashboard-page" class="page-content">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-bar text-gray-400 text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Tickets</dt>
                                        <dd class="text-lg font-medium text-gray-900" id="total-tickets"><?php
                                                                                                            $total_tickets = wp_count_posts('robodesk_ticket')->publish;
                                                                                                            echo $total_tickets;
                                                                                                            ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                                        <dd class="text-lg font-medium text-gray-900" id="open-tickets"><?php
                                                                                                        $args = array(
                                                                                                            'post_type' => 'robodesk_ticket',
                                                                                                            'post_status' => 'publish', // Adjust if you need other statuses like 'draft'
                                                                                                            'meta_query' => array(
                                                                                                                array(
                                                                                                                    'key' => '_robodesk_ticket_status',
                                                                                                                    'value' => 'open',
                                                                                                                    'compare' => '='
                                                                                                                )
                                                                                                            ),
                                                                                                            'posts_per_page' => -1, // Retrieve all matching posts
                                                                                                        );
                                                                                                        $query = new WP_Query($args);
                                                                                                        $open_tickets = $query->found_posts;
                                                                                                        echo $open_tickets;
                                                                                                        ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-yellow-400 text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                        <dd class="text-lg font-medium text-gray-900" id="in-progress-tickets"><?php
                                                                                                                $args = array(
                                                                                                                    'post_type' => 'robodesk_ticket',
                                                                                                                    'post_status' => 'publish', // Adjust if you need other statuses like 'draft'
                                                                                                                    'meta_query' => array(
                                                                                                                        array(
                                                                                                                            'key' => '_robodesk_ticket_status',
                                                                                                                            'value' => 'in-progress',
                                                                                                                            'compare' => '='
                                                                                                                        )
                                                                                                                    ),
                                                                                                                    'posts_per_page' => -1, // Retrieve all matching posts
                                                                                                                );
                                                                                                                $query = new WP_Query($args);
                                                                                                                $in_progress_tickets = $query->found_posts;
                                                                                                                echo $in_progress_tickets;
                                                                                                                ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Closed</dt>
                                        <dd class="text-lg font-medium text-gray-900" id="closed-tickets"><?php
                                                                                                            $args = array(
                                                                                                                'post_type' => 'robodesk_ticket',
                                                                                                                'post_status' => 'publish', // Adjust if you need other statuses like 'draft'
                                                                                                                'meta_query' => array(
                                                                                                                    array(
                                                                                                                        'key' => '_robodesk_ticket_status',
                                                                                                                        'value' => 'closed',
                                                                                                                        'compare' => '='
                                                                                                                    )
                                                                                                                ),
                                                                                                                'posts_per_page' => -1, // Retrieve all matching posts
                                                                                                            );
                                                                                                            $query = new WP_Query($args);
                                                                                                            $closed_tickets = $query->found_posts;
                                                                                                            echo $closed_tickets;
                                                                                                            ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Filter -->
                <?php
                // Function to load tickets based on filter

                function load_dashboard_tickets($filter = 'my_conversation')
                {
                    // Sanitize filter input
                    $filter = in_array($filter, ['my_conversation', 'unassigned_conversation', 'open_conversation', 'chat_conversation'])
                        ? $filter
                        : 'my_conversation';

                    // Initialize tickets array
                    $tickets = [];

                    // WP_Query arguments to fetch ALL tickets
                    $args = [
                        'post_type' => 'robodesk_ticket',
                        'post_status' => 'publish',
                        'posts_per_page' => -1, // Fetch all tickets
                        'orderby' => 'modified',
                        'order' => 'DESC',
                        'no_found_rows' => true, // Skip pagination calculations
                        'update_post_meta_cache' => false, // Optimize performance
                        'update_post_term_cache' => false, // Optimize performance
                    ];

                    $query = new WP_Query($args);

                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            $ticket_id = get_the_ID();

                            $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'N/A';
                            $ticket_origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true) ?: 'N/A';
                            $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
                            $author_image = get_avatar_url(get_the_author_meta('ID'), ['size' => 32]);

                            $ticket_priority = $priority_terms && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'N/A';
                            $updated_at = get_the_modified_date('Y-m-d H:i:s', $ticket_id);
                            $created_at = get_the_date('Y-m-d H:i:s', $ticket_id);
                            $ticket_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_category');

                            $ticket_category = '';
                            if (!is_wp_error($ticket_terms) && !empty($ticket_terms)) {
                                $ticket_category = [
                                    'name' => $ticket_terms[0]->name,
                                    'id'   => $ticket_terms[0]->term_id,
                                ];
                            }

                            // Fetch replies using comments
                            $replies = get_comments(['post_id' => $ticket_id]);
                            $replies_count = count($replies);
                            $replies = array_map(function ($reply) {
                                return [
                                    'id' => $reply->comment_ID,
                                    'author' => $reply->comment_author,
                                    'content' => $reply->comment_content,
                                    'date' => $reply->comment_date,
                                    'timestamp' => $reply->comment_date,
                                ];
                            }, $replies);

                            $tickets[] = [
                                'id' => $ticket_id,
                                'title' => get_the_title(),
                                'customer' => get_the_author(),
                                'email' => get_the_author_meta('user_email'),
                                'author_id' => get_the_author_meta('ID'),
                                'category' => $ticket_category,
                                'source' => $ticket_origin,
                                'status' => $ticket_status,
                                'author_image' => $author_image,
                                'created' => $created_at,
                                'priority' => $ticket_priority,
                                'updated' => $updated_at,
                                'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'N/A',
                                'replies' => $replies,
                                'replies_count' => $replies_count,
                            ];
                        }
                        wp_reset_postdata();
                    }

                    // Get current user info for filtering
                    $current_user = wp_get_current_user();
                    $current_user_name = $current_user->display_name ?: $current_user->user_login;

                    // Filter tickets based on the provided filter
                    $filtered_tickets = array_filter($tickets, function ($t) use ($current_user_name, $filter) {
                        switch ($filter) {
                            case 'my_conversation':
                                return $t['assignee'] === $current_user_name;
                            case 'unassigned_conversation':
                                return $t['assignee'] === 'N/A';
                            case 'open_conversation':
                                return in_array($t['status'], ['open', 'in-progress']);
                            case 'chat_conversation':
                                return $t['source'] === 'chat';
                            default:
                                'my_conversation';
                                // Default case to handle unexpected filters
                                return $t['assignee'] === $current_user_name;
                        }
                    });

                    // Sort tickets by updated date (newest first)
                    usort($filtered_tickets, function ($a, $b) {
                        return strtotime($b['updated']) - strtotime($a['updated']);
                    });

                    // Helper functions
                    if (!function_exists('get_status_class')) {
                        function get_status_class($status)
                        {
                            $classes = [
                                'open' => 'status-open',
                                'in-progress' => 'status-in-progress',
                                'closed' => 'status-closed',
                            ];
                            return $classes[$status] ?? 'status-default';
                        }
                    }

                    if (!function_exists('get_priority_class')) {
                        function get_priority_class($priority)
                        {
                            $classes = [
                                'low' => 'priority-low',
                                'medium' => 'priority-medium',
                                'high' => 'priority-high',
                            ];
                            return $classes[strtolower($priority)] ?? 'priority-default';
                        }
                    }

                    if (!function_exists('format_date')) {
                        function format_date($date)
                        {
                            return date('M j, Y H:i', strtotime($date));
                        }
                    }

                    /**
                     * Returns a blinking class for chat tickets based on last reply time and author.
                     * Only applies to chat tickets with open/in-progress status.
                     *
                     * @param array $ticket
                     * @return string
                     */
                    function get_blinking_class($ticket)
                    {
                        // Only for chat tickets with open/in-progress status
                        if (
                            empty($ticket['source']) || strtolower($ticket['source']) !== 'chat' ||
                            empty($ticket['status']) || !in_array($ticket['status'], ['open', 'in-progress'])
                        ) {
                            return '';
                        }

                        $replies = isset($ticket['replies']) && is_array($ticket['replies']) ? $ticket['replies'] : [];
                        $last_reply = !empty($replies) ? end($replies) : null;

                        // If no replies, just green
                        if (!$last_reply) {
                            return 'blinking-green';
                        }

                        // Only consider if last reply is from customer
                        $is_customer = false;
                        if (isset($last_reply['type']) && $last_reply['type'] === 'customer') {
                            $is_customer = true;
                        } elseif (isset($last_reply['author_type']) && $last_reply['author_type'] === 'customer') {
                            $is_customer = true;
                        }
                        if (!$is_customer) {
                            return 'blinking-green';
                        }

                        // Calculate minutes since last reply
                        $last_reply_time = null;
                        if (!empty($last_reply['timestamp'])) {
                            $last_reply_time = strtotime($last_reply['timestamp']);
                        } elseif (!empty($last_reply['date'])) {
                            $last_reply_time = strtotime($last_reply['date']);
                        } elseif (!empty($ticket['updated'])) {
                            $last_reply_time = strtotime($ticket['updated']);
                        }
                        if (!$last_reply_time) {
                            return 'blinking-green';
                        }

                        $now = time();
                        $diff_minutes = ($now - $last_reply_time) / 60;

                        if ($diff_minutes <= 30) {
                            return 'blinking-red';
                        } elseif ($diff_minutes > 3 && $diff_minutes <= 10) {
                            return 'blinking-yellow';
                        } else {
                            return 'blinking-green';
                        }
                    }



                    // Generate table HTML
                    ob_start();
                ?>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Reply</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="dashboard-tickets-table">
                                <?php if (empty($filtered_tickets)) : ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            No tickets found for this filter.
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($filtered_tickets as $ticket) : ?>
                                        <tr class="table-row cursor-pointer" data-ticketid="<?php echo esc_attr($ticket['id']); ?>" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php $blinking_class = get_blinking_class($ticket); ?>
                                                <div class="text-sm font-medium text-gray-900<?php echo $blinking_class ? ' ' . esc_attr($blinking_class) : ''; ?>">
                                                    #<?php echo esc_html($ticket['id']); ?>
                                                    <?php if ($blinking_class): ?>
                                                        <span>&bull;</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['title']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if (!empty($ticket['replies'])) : ?>
                                                    <?php
                                                    $last_reply = end($ticket['replies']);
                                                    $last_reply_author = $last_reply['author'] ?? 'N/A';
                                                    $last_reply_date = $last_reply['date'] ?? $ticket['updated'];
                                                    $time_diff = human_time_diff(strtotime($last_reply_date), current_time('timestamp')) . ' ago';
                                                    ?>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo esc_html($last_reply_author); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo esc_html($time_diff); ?></div>
                                                <?php else : ?>
                                                    <div class="text-sm text-gray-500">No replies yet</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($ticket['customer']); ?></div>
                                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($ticket['source']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php echo esc_attr(get_status_class($ticket['status'])); ?> editable-status"
                                                    onclick="event.stopPropagation(); editStatus(<?php echo esc_js($ticket['id']); ?>, this)">
                                                    <?php echo esc_html(str_replace('-', ' ', $ticket['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php echo esc_attr(get_priority_class($ticket['priority'])); ?> editable-priority"
                                                    onclick="event.stopPropagation(); editPriority(<?php echo esc_js($ticket['id']); ?>, this)">
                                                    <?php echo esc_html($ticket['priority']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo esc_html(format_date($ticket['updated'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                    return ob_get_clean();
                }

                // Get filter from query parameter or default to 'my_conversation'
                $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'my_conversation';
                ?>

                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Ticket Overview</h3>
                            <div class="flex space-x-2">
                                <button onclick="filterDashboardTickets('my_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'my_conversation' ? 'active' : ''; ?>" data-filter="my_conversation">MY Conversations</button>

                                <button onclick="filterDashboardTickets('unassigned_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'unassigned_conversation' ? 'active' : ''; ?>" data-filter="unassigned_conversation">Unassigned Conversations</button>

                                <button onclick="filterDashboardTickets('open_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'open_conversation' ? 'active' : ''; ?>" data-filter="open_conversation">Open Conversations</button>

                                <button onclick="filterDashboardTickets('chat_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'chat_conversation' ? 'active' : ''; ?>" data-filter="chat_conversation">Chat Conversations</button>

                            </div>
                        </div>
                        <?php echo load_dashboard_tickets($filter); ?>
                    </div>
                </div>

                <!------------------------------------ Quick Actions ------------------------------------>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button onclick="showPage('new-ticket')" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-left transition-colors">
                                <i class="fas fa-plus text-blue-600 text-xl mb-2"></i>
                                <div class="text-sm font-medium text-blue-900">Create New Ticket</div>
                                <div class="text-xs text-blue-700">Add a new support ticket</div>
                            </button>
                            <button onclick="window.location.href='<?php echo admin_url('users.php?role=robodesk_support_customer'); ?>'" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-left transition-colors">
                                <i class="fas fa-users text-green-600 text-xl mb-2"></i>
                                <div class="text-sm font-medium text-green-900">Manage Customers</div>
                                <div class="text-xs text-green-700">View and edit customer information</div>
                            </button>

                            <button onclick="showPage('settings')" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-left transition-colors">
                                <i class="fas fa-cog text-purple-600 text-xl mb-2"></i>
                                <div class="text-sm font-medium text-purple-900">System Settings</div>
                                <div class="text-xs text-purple-700">Configure system preferences</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!---------------------------------- New Ticket Page ---------------------------------->
            <div id="new-ticket-page" class="page-content hidden">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Create New Ticket</h3>

                        <form id="robodesk-new-ticket-form" class="space-y-6">
                            <input type="hidden" name="robodesk_create_ticket_nonce" value="<?php echo wp_create_nonce('robodesk_create_ticket_action'); ?>">
                            <input type="hidden" name="ticket_source" value="Form"> <!-- Added for robodesk_ticket_origin -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                                    <input type="text" id="customer-name" name="customer_name" required autocomplete="off"
                                        class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <div id="customer-name-suggestions" class="bg-white border border-gray-300 mt-1 hidden absolute z-10 w-full max-h-40 overflow-auto rounded-md shadow"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Email *</label>
                                    <input type="email" id="customer-email" name="customer_email" required autocomplete="off"
                                        class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <div id="customer-email-suggestions" class="bg-white border border-gray-300 mt-1 hidden absolute z-10 w-full max-h-40 overflow-auto rounded-md shadow"></div>
                                </div>

                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Title *</label>
                                <input type="text" id="ticket-title" name="ticket_title" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                                    <select id="ticket-priority" name="ticket_priority" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <!-- <option value="">Select Priority</option> -->
                                        <?php
                                        $ticket_priorities = get_terms([
                                            'taxonomy' => 'robodesk_ticket_priority',
                                            'hide_empty' => false,
                                        ]);
                                        foreach ($ticket_priorities as $ticket_priority) {
                                            echo '<option value="' . esc_attr($ticket_priority->name) . '">' . esc_html($ticket_priority->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                    <select id="ticket-category" name="ticket_category" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <!-- <option value="">Select Category</option> -->
                                        <?php
                                        $categories = get_terms([
                                            'taxonomy' => 'robodesk_ticket_category',
                                            'hide_empty' => false,
                                        ]);

                                        foreach ($categories as $category) {
                                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign to *</label>
                                    <select id="ticket-assignee" name="ticket_assignee" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <?php
                                        $default_agent = get_option('robodesk_default_agent');
                                        // Get users by role with display_name only
                                        $agents = get_users([
                                            'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                            'fields'   => ['display_name'],
                                        ]);
                                        // Convert to simple array of display names
                                        $agent_names = wp_list_pluck($agents, 'display_name');
                                        ?>
                                        <?php foreach ($agent_names as $agent): ?>
                                            <option value="<?php echo esc_attr($agent); ?>" <?php selected($default_agent, $agent); ?>>
                                                <?php echo esc_html($agent); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Description *</label>
                                <textarea id="ticket-description" name="ticket_description" rows="6" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Describe the issue or request..."></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="showPage('dashboard')" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </button>
                                <button type="submit" id="robodesk-create-ticket" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Create Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!------------------------------------- Tickets Page ------------------------------------->
            <div id="tickets-page" class="page-content hidden">
                <!-- Search and Filter Bar -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Tickets</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" id="search-input" placeholder="Search tickets..." class="styled-input block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search by Customer</label>
                                <input type="text" id="customer-filter" placeholder="Customer name..." class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search by Agent</label>
                                <input type="text" id="agent-filter" placeholder="Agent name..." class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                                <select id="status-filter" class="styled-select block w-full px-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                                    <option value="all">All Status</option>
                                    <option value="open">Open</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="filterTickets()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-filter mr-2"></i>
                                    Filter
                                </button>
                                <button onclick="showPage('new-ticket')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-2"></i>
                                    New Ticket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!----------------------------- Tickets Table ----------------------------->
                <?php
                // Get the current page number from URL parameter
                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $posts_per_page = 10; // Number of tickets per page
                $offset = ($current_page - 1) * $posts_per_page;

                // Get total count first
                $total_args = array(
                    'post_type' => 'robodesk_ticket',
                    'post_status' => 'publish',
                    'posts_per_page' => -1, // Get all posts for counting
                    'fields' => 'ids' // Only get IDs for performance
                );
                $total_query = new WP_Query($total_args);
                $total_posts = $total_query->found_posts;
                $total_pages = ceil($total_posts / $posts_per_page);

                // Main query with proper pagination
                $args = array(
                    'post_type' => 'robodesk_ticket',
                    'post_status' => 'publish',
                    'posts_per_page' => $posts_per_page,
                    'offset' => $offset,
                    'orderby' => 'modified',
                    'order' => 'DESC'
                );
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

                // Function to generate pagination URL
                function get_pagination_url($page)
                {
                    $current_url = admin_url('admin.php');
                    $params = $_GET;
                    $params['paged'] = $page;
                    return add_query_arg($params, $current_url);
                }
                ?>

                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tickets-table">
                            <!-- JavaScript for tickets -->
                            <script>
                                const tickets = <?php echo wp_json_encode($tickets); ?>;
                            </script>
                            <?php if (!empty($tickets)) : ?>
                                <?php foreach ($tickets as $ticket) : ?>
                                    <tr class="table-row cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">#<?php echo esc_html($ticket['id']); ?></div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['title']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo esc_html($ticket['customer']); ?></div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge <?php echo esc_attr(get_status_class($ticket['status'])); ?>">
                                                <?php echo esc_html(str_replace('-', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge <?php echo esc_attr(get_priority_class($ticket['priority'])); ?>">
                                                <?php echo esc_html($ticket['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo esc_html($ticket['assignee']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo esc_html(format_date($ticket['updated'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No tickets found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Pagination for Plugin Dashboard -->
                <?php if ($total_pages > 1) : ?>
                    <div class="mt-4">
                        <nav class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <!-- Mobile pagination -->
                                <?php if ($current_page > 1) : ?>
                                    <a href="<?php echo esc_url(get_pagination_url($current_page - 1)); ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php else : ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                        Previous
                                    </span>
                                <?php endif; ?>

                                <?php if ($current_page < $total_pages) : ?>
                                    <a href="<?php echo esc_url(get_pagination_url($current_page + 1)); ?>"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php else : ?>
                                    <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                        Next
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing <span class="font-medium"><?php echo (($current_page - 1) * $posts_per_page) + 1; ?></span>
                                        to <span class="font-medium"><?php echo min($current_page * $posts_per_page, $total_posts); ?></span>
                                        of <span class="font-medium"><?php echo $total_posts; ?></span> results
                                    </p>
                                </div>

                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <!-- Previous Page Link -->
                                        <?php if ($current_page > 1) : ?>
                                            <a href="<?php echo esc_url(get_pagination_url($current_page - 1)); ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php else : ?>
                                            <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        $start_page = max(1, $current_page - 2);
                                        $end_page = min($total_pages, $current_page + 2);

                                        // Show first page if we're not close to it
                                        if ($start_page > 1) : ?>
                                            <a href="<?php echo esc_url(get_pagination_url(1)); ?>"
                                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                1
                                            </a>
                                            <?php if ($start_page > 2) : ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                            <?php if ($i == $current_page) : ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                                    <?php echo $i; ?>
                                                </span>
                                            <?php else : ?>
                                                <a href="<?php echo esc_url(get_pagination_url($i)); ?>"
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <!-- Show last page if we're not close to it -->
                                        <?php if ($end_page < $total_pages) : ?>
                                            <?php if ($end_page < $total_pages - 1) : ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url(get_pagination_url($total_pages)); ?>"
                                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Next Page Link -->
                                        <?php if ($current_page < $total_pages) : ?>
                                            <a href="<?php echo esc_url(get_pagination_url($current_page + 1)); ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php else : ?>
                                            <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>

            <!------------------------------- Ticket Detail Page ----------------------->
            <div id="ticket-detail-page" class="page-content hidden">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-900" id="ticket-detail-title">Ticket Details</h3>
                            <button onclick="showPage('tickets')" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Tickets
                            </button>

                        </div>

                        <!-- Comprehensive Ticket Info -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                            <!-- Customer Info -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Customer Information</h4>
                                <div id="customer-info" class="space-y-2">
                                    <!-- Customer info will be populated by JavaScript -->
                                </div>
                                <div class="mt-4">
                                    <button onclick="showCustomerTickets()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View All Customer Tickets
                                    </button>
                                </div>
                            </div>

                            <!-- Ticket Status & Priority -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Ticket Status</h4>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select onchange="updateTicketStatus(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-status-select">

                                            <?php
                                            $ticket_id = get_the_ID(); // Get the current ticket ID
                                            $currentTicket = get_post($ticket_id); // Assuming $ticket_id is available

                                            // get the current ticket status
                                            $current_ticket_status = get_post_meta($currentTicket->ID, '_robodesk_ticket_status', true);
                                            $statuses = array('open', 'in-progress', 'closed');
                                            foreach ($statuses as $status) {
                                                $selected = ($status === $current_ticket_status) ? 'selected' : '';
                                                echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                        <select onchange="updateTicketPriority(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-priority-select">
                                            <?php
                                            $priorities = get_terms([
                                                'taxonomy'   => 'robodesk_ticket_priority',
                                                'hide_empty' => false,
                                            ]);

                                            if (!is_wp_error($priorities)) {
                                                foreach ($priorities as $priority) {
                                                    echo '<option value="' . esc_attr($priority->name) . '">' . esc_html($priority->name) . '</option>';
                                                }
                                            }

                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                                        <select onchange="updateTicketAssignee(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-assignee-select">
                                            <?php
                                            $default_agent = get_option('robodesk_default_agent');
                                            var_dump($default_agent);

                                            // Get users by role with display_name only
                                            $agents = get_users([
                                                'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                                'fields'   => ['display_name'],
                                            ]);

                                            // Convert to simple array of display names
                                            $agent_names = wp_list_pluck($agents, 'display_name');
                                            ?>

                                            <?php foreach ($agent_names as $agent): ?>
                                                <option value="<?php echo esc_attr($agent); ?>" <?php selected($default_agent, $agent); ?>>
                                                    <?php echo esc_html($agent); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <!-- Ticket Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Ticket Details</h4>
                                <div id="ticket-meta-info" class="space-y-2">
                                    <!-- Ticket meta info will be populated by JavaScript -->
                                    <!-- have been applied from js -->


                                </div>
                            </div>
                        </div>

                        <!--------------------------- Customer Tickets Modal --------------------------->
                        <div id="customer-tickets-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-900" id="customer-tickets-title">Customer Tickets</h3>
                                    <button onclick="hideCustomerTickets()" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div id="customer-tickets-stats" class="grid grid-cols-3 gap-4 mb-4">
                                    <!-- Customer ticket stats will be populated by JavaScript -->
                                </div>
                                <div id="customer-tickets-list" class="max-h-64 overflow-y-auto">
                                    <!-- Customer tickets list will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8">
                                <button onclick="showTicketTab('replies')" class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="replies">
                                    Replies (<span id="replies-count">0</span>)
                                </button>
                                <button onclick="showTicketTab('notes')" class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="notes">
                                    Internal Notes (<span id="notes-count">0</span>)
                                </button>
                            </nav>
                        </div>

                        <!--------------------------------- Tab Content ---------------------->
                        <div class="mt-6">
                            <div id="replies-tab" class="ticket-tab-content">
                                <div id="replies-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                                    <!-- Replies will be populated by JavaScript -->
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Reply</label>
                                    <div class="editor-toolbar mb-2">
                                        <button type="button" onclick="formatText('bold', 'new-reply')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                        <button type="button" onclick="formatText('italic', 'new-reply')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                        <button type="button" onclick="formatText('underline', 'new-reply')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                        <button type="button" onclick="formatText('insertUnorderedList', 'new-reply')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                        <button type="button" onclick="formatText('insertOrderedList', 'new-reply')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                    </div>
                                    <div id="new-reply" class="editor-content placeholder" contenteditable="true"
                                        style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                                        data-placeholder="Type your reply here..." aria-label="Reply input"></div>
                                    <button onclick="addReply()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Send Reply
                                    </button>

                                    <button onclick="closeTicket()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 float-right">Close Ticket</button>
                                </div>
                            </div>

                            <div id="notes-tab" class="ticket-tab-content hidden">
                                <div id="notes-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                                    <!-- Notes will be populated by JavaScript -->
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Internal Note</label>
                                    <div class="editor-toolbar mb-2">
                                        <button type="button" onclick="formatText('bold', 'new-note')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                        <button type="button" onclick="formatText('italic', 'new-note')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                        <button type="button" onclick="formatText('underline', 'new-note')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                        <button type="button" onclick="formatText('insertUnorderedList', 'new-note')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                        <button type="button" onclick="formatText('insertOrderedList', 'new-note')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                    </div>
                                    <div id="new-note" class="editor-content placeholder" contenteditable="true"
                                        style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                                        data-placeholder="Type your internal note here..."></div>
                                    <button onclick="addNote()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        Add Note
                                    </button>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!---------------------- Settings Page ---------------------->
            <div id="settings-page" class="page-content hidden">
                <div class="bg-white shadow rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="showSettingsTab('basic')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="basic">
                                Basic Settings
                            </button>
                            <button onclick="showSettingsTab('imap')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="imap">
                                IMAP Settings
                            </button>
                            <button onclick="showSettingsTab('templates')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="templates">
                                Email Templates
                            </button>
                            <button onclick="showSettingsTab('fetch')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="fetch">
                                Fetch Tickets
                            </button>
                            <button onclick="showSettingsTab('advanced')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="advanced">
                                Advanced
                            </button>
                        </nav>
                    </div>
                    <!------------------------------------------ overall setting page with all the tabs---------------------------------------->
                    <div class="p-6">
                        <!------------------------------Basic Settings Tab --------------------------------->

                        <div id="basic-tab" class="settings-tab-content">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Settings</h3>
                            <form id="robodesk-basic-settings-form" class="space-y-6">
                                <input type="hidden" name="robodesk_basic_settings_nonce" value="<?php echo wp_create_nonce('robodesk_basic_settings_action'); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                    <div>
                                        <label for="site_name" class="block text-sm font-medium text-gray-700">Site Name</label>
                                        <input type="text" name="site_name" id="site_name" value="<?php echo esc_attr(get_option('robodesk_site_name', 'Robodesk Support')); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="admin_email" class="block text-sm font-medium text-gray-700">Admin Email</label>
                                        <input type="email" name="admin_email" id="admin_email" value="<?php echo esc_attr(get_option('robodesk_admin_email', 'admin@example.com')); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                        <select name="timezone" id="timezone" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <?php
                                            $saved_timezone = get_option('robodesk_timezone', 'UTC');
                                            $timezones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'GMT'];
                                            foreach ($timezones as $tz) {
                                                echo '<option value="' . esc_attr($tz) . '"' . selected($saved_timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <!----------------------------- Image Fields ------------------------------>
                                    <div>
                                        <label for="popover_top_icon" class="block text-sm font-medium text-gray-700">Pop over Top Icon/Image</label>
                                        <input type="file" accept="image/*" name="popover_top_icon" id="popover_top_icon" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <div class="mt-2 flex items-center gap-2">
                                            <img id="popover-top-icon-preview" src="<?php echo esc_url(get_option('robodesk_popover_top_icon', '')); ?>" alt="Pop over Top Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                            <button type="button" onclick="removeImage('popover-top-icon-preview', 'robodesk_popover_top_icon')" class="text-red-600 hover:text-red-800 text-sm <?php echo esc_url(get_option('robodesk_popover_top_icon', '')) ? '' : 'hidden'; ?>">Remove</button>
                                        </div>
                                        <input type="hidden" name="robodesk_popover_top_icon" id="robodesk_popover_top_icon" value="<?php echo esc_attr(get_option('robodesk_popover_top_icon', '')); ?>">
                                    </div>
                                    <div>
                                        <div class="mt-4">
                                            <label for="theme_color" class="block text-sm font-medium text-gray-700">Theme Color</label>
                                            <input type="color" name="theme_color" id="theme_color" value="<?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>" class="styled-input mt-1 block w-10 h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <div class="mt-2">
                                                <div id="theme-color-preview" class="hidden w-16 h-8 rounded-md border border-gray-300" style="background-color: <?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="popover_icon" class="block text-sm font-medium text-gray-700">Pop over Icon/Image</label>
                                        <input type="file" accept="image/*" name="popover_icon" id="popover_icon" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <div class="mt-2 flex items-center gap-2">
                                            <img id="popover-icon-preview" src="<?php echo esc_url(get_option('robodesk_popover_icon', '')); ?>" alt="Pop over Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                            <button type="button" onclick="removeImage('popover-icon-preview', 'robodesk_popover_icon')" class="text-red-600 hover:text-red-800 text-sm <?php echo esc_url(get_option('robodesk_popover_icon', '')) ? '' : 'hidden'; ?>">Remove</button>
                                        </div>
                                        <input type="hidden" name="robodesk_popover_icon" id="robodesk_popover_icon" value="<?php echo esc_attr(get_option('robodesk_popover_icon', '')); ?>">
                                    </div>
                                    <div>
                                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                                        <select name="language" id="language" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <?php
                                            $saved_language = get_option('robodesk_language', 'en');
                                            $languages = ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German'];
                                            foreach ($languages as $code => $name) {
                                                echo '<option value="' . esc_attr($code) . '"' . selected($saved_language, $code, false) . '>' . esc_html($name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="logo" class="block text-sm font-medium text-gray-700">Logo</label>
                                        <input type="file" accept="image/*" name="logo" id="logo" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <div class="mt-2 flex items-center gap-2">
                                            <img id="logo-preview" src="<?php echo esc_url(get_option('robodesk_logo', '')); ?>" alt="Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                            <button type="button" onclick="removeImage('logo-preview', 'robodesk_logo')" class="text-red-600 hover:text-red-800 text-sm <?php echo esc_url(get_option('robodesk_logo', '')) ? '' : 'hidden'; ?>">Remove</button>
                                        </div>
                                        <input type="hidden" name="robodesk_logo" id="robodesk_logo" value="<?php echo esc_attr(get_option('robodesk_logo', '')); ?>">
                                    </div>
                                    <div>
                                        <label for="wide_logo" class="block text-sm font-medium text-gray-700">Wide Logo</label>
                                        <input type="file" accept="image/*" name="wide_logo" id="wide_logo" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <div class="mt-2 flex items-center gap-2">
                                            <img id="wide-logo-preview" src="<?php echo esc_url(get_option('robodesk_wide_logo', '')); ?>" alt="Wide Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                            <button type="button" onclick="removeImage('wide-logo-preview', 'robodesk_wide_logo')" class="text-red-600 hover:text-red-800 text-sm <?php echo esc_url(get_option('robodesk_wide_logo', '')) ? '' : 'hidden'; ?>">Remove</button>
                                        </div>
                                        <input type="hidden" name="robodesk_wide_logo" id="robodesk_wide_logo" value="<?php echo esc_attr(get_option('robodesk_wide_logo', '')); ?>">
                                    </div>
                                    <!-- adding a notification sound for chat -->
                                    <div>
                                        <label for="notification_sound" class="block text-sm font-medium text-gray-700">Notification Sound</label>
                                        <select name="robodesk_notification_sound" id="notification_sound" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <?php
                                            $audio_files = [
                                                'Sample 1' => plugin_dir_url(__FILE__) . 'audio/1.mp3',
                                                'Sample 2' => plugin_dir_url(__FILE__) . 'audio/2.mp3',
                                                'Sample 3' => plugin_dir_url(__FILE__) . 'audio/3.mp3',
                                                'Sample 4' => plugin_dir_url(__FILE__) . 'audio/4.mp3',
                                                'Sample 5' => plugin_dir_url(__FILE__) . 'audio/5.mp3',
                                                'Sample 6' => plugin_dir_url(__FILE__) . 'audio/6.mp3',
                                                'Sample 7' => plugin_dir_url(__FILE__) . 'audio/7.mp3',
                                                'Sample 8' => plugin_dir_url(__FILE__) . 'audio/8.mp3',
                                                'Sample 9' => plugin_dir_url(__FILE__) . 'audio/9.mp3',
                                                'Sample 10' => plugin_dir_url(__FILE__) . 'audio/10.mp3',
                                                'Sample 11' => plugin_dir_url(__FILE__) . 'audio/ding.mp3',
                                                'Sample 12' => plugin_dir_url(__FILE__) . 'audio/chime.mp3',
                                                'Sample 13' => plugin_dir_url(__FILE__) . 'audio/notify.mp3',
                                                'Sample 14' => plugin_dir_url(__FILE__) . 'audio/alert.mp3',
                                            ];
                                            $selected_sound = get_option('robodesk_notification_sound', '');
                                            foreach ($audio_files as $label => $url) {
                                                echo '<option value="' . esc_url($url) . '" ' . selected($selected_sound, $url, false) . '>' . ucfirst($label) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <div class="mt-2 flex items-center gap-2">
                                            <audio id="notification-sound-preview" controls class="<?php echo $selected_sound ? '' : 'hidden'; ?>">
                                                <source src="<?php echo esc_url($selected_sound); ?>" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                            <button type="button" onclick="removeAudio('notification-sound-preview', 'robodesk_notification_sound')" class="text-red-600 hover:text-red-800 text-sm <?php echo $selected_sound ? '' : 'hidden'; ?>">Remove</button>
                                        </div>
                                        <input type="hidden" name="robodesk_notification_sound" id="robodesk_notification_sound" value="<?php echo esc_attr($selected_sound); ?>">
                                    </div>
                                    <!-- end of notification sound -->
                                    <!-- adding a default category of ticket -->
                                    <div class="mt-4">
                                        <label for="robodesk_default_ticket_category" class="block text-sm font-medium text-gray-700">Default Ticket Category</label>
                                        <select name="robodesk_default_ticket_category" id="robodesk_default_ticket_category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select a category</option>
                                            <?php
                                            $selected_category = get_option('robodesk_default_ticket_category', '');
                                            $categories = get_terms(array(
                                                'taxonomy'   => 'robodesk_ticket_category',
                                                'hide_empty' => false,
                                            ));
                                            foreach ($categories as $category) {
                                                echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($selected_category, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <hr class="my-6 border-gray-200">
                                <button type="button" id="save-basic-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Settings
                                </button>
                            </form>
                        </div>

                        <!----------------------- IMAP Settings load saved settings ------------------->
                        <?php
                        // Load saved settings
                        $settings = get_option('robodesk_imap_settings', array(
                            'hostname' => 'email.example.com',
                            'port'     => 993,
                            'username' => '',
                            'password' => '',
                            'auth'     => true,

                        ));

                        $hostname = $settings['hostname'];
                        $port     = $settings['port'];
                        $username = $settings['username'];
                        $password = $settings['password'];
                        $auth     = isset($settings['auth']) ? $settings['auth'] : true;

                        ?>

                        <!--------------------------- IMAP Settings Tab ---------------------------------------->
                        <div id="imap-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">IMAP Settings</h3>
                            <form method="post" action="" id="robodesk-imap-form" class="space-y-6">
                                <?php wp_nonce_field('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce'); ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="robodesk_imap_hostname" class="block text-sm font-medium text-gray-700">IMAP Host</label>
                                        <input type="text" name="robodesk_imap_hostname" id="robodesk_imap_hostname"
                                            value="<?php echo esc_attr($hostname); ?>"
                                            class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="robodesk_imap_port" class="block text-sm font-medium text-gray-700">Port</label>
                                        <input type="number" name="robodesk_imap_port" id="robodesk_imap_port"
                                            value="<?php echo esc_attr($port); ?>"
                                            class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="robodesk_imap_username" class="block text-sm font-medium text-gray-700">Username</label>
                                        <input type="email" name="robodesk_imap_username" id="robodesk_imap_username"
                                            value="<?php echo esc_attr($username); ?>"
                                            class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="robodesk_imap_password" class="block text-sm font-medium text-gray-700">Password</label>
                                        <input type="password" name="robodesk_imap_password" id="robodesk_imap_password"
                                            value="<?php echo esc_attr($password); ?>"
                                            class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="robodesk_imap_auth" class="block text-sm font-medium text-gray-700">Encryption</label>
                                        <select name="robodesk_imap_auth" id="robodesk_imap_auth"
                                            class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="1" <?php selected($auth, true); ?>>SSL</option>
                                            <option value="0" <?php selected($auth, false); ?>>TLS</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex gap-4">
                                    <button type="button" id="robodesk-imap-test" onclick="testConnection()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Test Connection
                                    </button>
                                </div>
                                <hr class="my-6 border-gray-200">
                                <div>
                                    <button type="button" id="save-imap-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>


                        <!------------------------------------------ Email Templates Tab --------------------------->

                        <div id="templates-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Email Templates</h3>

                            <div class="bg-[#ebedf5] rounded-lg p-6 text-gray-800 shadow-sm">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-base font-semibold text-gray-900">Customize the email templates with Variables</h2>
                                    <button onclick="toggleAccordion()" id="toggle-btn"
                                        class="text-sm text-blue-600 hover:underline focus:outline-none">
                                        Show
                                    </button>
                                </div>

                                <div id="accordion-content" class="mt-4 text-sm text-gray-700 leading-relaxed space-y-4 hidden">
                                    <p>You can use the following variables in your templates:</p>
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_id}</code> – The ID of the ticket</li>
                                        <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_author}</code> – The name of the ticket author</li>
                                        <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_link}</code> – The link to the ticket</li>
                                        <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_title}</code> – The title of the ticket</li>
                                        <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_status}</code> – The status of the ticket</li>
                                    </ul>
                                </div>
                            </div>


                            <form id="robodesk_email_template_save" class="space-y-6">
                                <input type="hidden" name="robodesk_email_template_nonce" value="<?php echo wp_create_nonce('robodesk_email_template_action'); ?>">
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">New Ticket Notification</label>
                                        <div class="editor-toolbar mb-2">
                                            <button type="button" onclick="formatText('bold', 'new-ticket-template')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                            <button type="button" onclick="formatText('italic', 'new-ticket-template')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                            <button type="button" onclick="formatText('underline', 'new-ticket-template')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                            <button type="button" onclick="formatText('insertUnorderedList', 'new-ticket-template')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" onclick="formatText('insertOrderedList', 'new-ticket-template')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                        </div>
                                        <div id="new-ticket-template" class="editor-content" contenteditable="true"><?php echo wp_kses_post(get_option('robodesk_email_template_new-ticket', 'Thank you for contacting us. We have received your ticket and will respond soon.')); ?></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Closed</label>
                                        <div class="editor-toolbar mb-2">
                                            <button type="button" onclick="formatText('bold', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                            <button type="button" onclick="formatText('italic', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                            <button type="button" onclick="formatText('underline', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                            <button type="button" onclick="formatText('insertUnorderedList', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" onclick="formatText('insertOrderedList', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                        </div>
                                        <div id="ticket-closed-template" class="editor-content" contenteditable="true"><?php echo wp_kses_post(get_option('robodesk_email_template_ticket-closed', 'Your ticket has been closed. Please let us know if you need further assistance.')); ?></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Auto Reply</label>
                                        <div class="editor-toolbar mb-2">
                                            <button type="button" onclick="formatText('bold', 'auto-reply-template')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                            <button type="button" onclick="formatText('italic', 'auto-reply-template')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                            <button type="button" onclick="formatText('underline', 'auto-reply-template')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                            <button type="button" onclick="formatText('insertUnorderedList', 'auto-reply-template')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" onclick="formatText('insertOrderedList', 'auto-reply-template')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                        </div>
                                        <div id="auto-reply-template" class="editor-content" contenteditable="true"><?php echo wp_kses_post(get_option('robodesk_email_template_auto-reply', 'This is an automated response. We will get back to you within 24 hours.')); ?></div>
                                    </div>
                                </div>
                                <hr class="my-6 border-gray-200">
                                <button type="button" id="robodesk-save-templates" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Templates
                                </button>
                            </form>
                        </div>
                        <!------------------------------ Fetch Tickets Tab --------------------------------------->
                        <div id="fetch-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Fetch Tickets</h3>
                            <form id="robodesk-fetch-tickets-form" class="space-y-6">
                                <input type="hidden" name="robodesk_ticket_assignment_nonce" value="<?php echo wp_create_nonce('robodesk_ticket_assignment_action'); ?>">
                                <div class="space-y-6">
                                    <p class="text-sm text-gray-600">Use this feature to fetch new emails from your configured IMAP account and convert them into tickets and replies.</p>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h4 class="font-medium text-gray-900 mb-2">Email Fetching Settings</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Fetch Interval (minutes)</label>
                                                <input type="number" name="fetch_interval" value="<?php echo esc_attr(get_option('robodesk_fetch_interval', 15)); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" min="1" max="1440" required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Auto-assign to</label>
                                                <select name="default_agent" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                                    <!-- default options -->
                                                    <?php
                                                    $default_agent = get_option('robodesk_default_agent');

                                                    // Get users by role with display_name only
                                                    $agents = get_users([
                                                        'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                                        'fields'   => ['display_name'],
                                                    ]);

                                                    // Convert to simple array of display names
                                                    $agent_names = wp_list_pluck($agents, 'display_name');
                                                    ?>

                                                    <?php foreach ($agent_names as $agent): ?>
                                                        <option value="<?php echo esc_attr($agent); ?>" <?php selected($default_agent, $agent); ?>>
                                                            <?php echo esc_html($agent); ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                    <?php if (!in_array($default_agent, $agent_names) && !empty($default_agent)): ?>
                                                        <option value="<?php echo esc_attr($default_agent); ?>" selected>
                                                            <?php echo esc_html($default_agent); ?>
                                                        </option>
                                                    <?php endif; ?>


                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-4">
                                        <button id="save-ticket-assignment-settings" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-cog mr-2"></i>
                                            Save Auto-Fetch
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="flex space-x-4 mt-6">
                                <button onclick="fetchEmails()" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Fetch Emails Now
                                </button>
                            </div>
                            <div id="fetch-results" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span id="fetch-message" class="text-blue-800"></span>
                                </div>
                            </div>

                        </div>

                        <!--------------------------- Advanced Settings Tab -------------------------------->


                        <?php
                        $settings = get_option('robodesk_advanced_settings', [
                            'enable_auto_assign' => false,
                            'enable_notifications' => true,
                            'enable_customer_portal' => true,
                            'max_file_upload_size' => 10
                        ]);
                        ?>
                        <div id="advanced-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Advanced Settings</h3>
                            <form id="advanced-settings-form" class="space-y-6">
                                <input type="hidden" name="robodesk_advanced_settings_nonce" value="<?php echo wp_create_nonce('robodesk_advanced_settings_action'); ?>">
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input id="enable-auto-assign" type="checkbox" <?php checked($settings['enable_auto_assign'], true); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="enable-auto-assign" class="ml-2 block text-sm text-gray-900">
                                            Enable auto-assignment of tickets
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="enable-notifications" type="checkbox" <?php checked($settings['enable_notifications'], true); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="enable-notifications" class="ml-2 block text-sm text-gray-900">
                                            Enable email notifications
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="enable-customer-portal" type="checkbox" <?php checked($settings['enable_customer_portal'], true); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="enable-customer-portal" class="ml-2 block text-sm text-gray-900">
                                            Enable customer portal
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Maximum file upload size (MB)</label>
                                        <input type="number" name="max_file_upload_size" value="<?php echo esc_attr($settings['max_file_upload_size']); ?>" min="1" max="100" class="styled-input mt-1 block w-32 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <hr class="my-6 border-gray-200">
                                <button type="button" id="save-advanced-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Advanced Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
