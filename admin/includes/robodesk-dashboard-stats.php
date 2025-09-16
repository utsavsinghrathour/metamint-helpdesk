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
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php _e('Total Conversations', 'robodesk'); ?></dt>
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
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php _e('Open Conversations', 'robodesk'); ?></dt>
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
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php _e('In Progress', 'robodesk'); ?></dt>
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
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php _e('Closed', 'robodesk'); ?></dt>
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
        $dashboard_tickets = [];

        // WP_Query arguments to fetch ALL tickets
        $args = [
            'post_type'      => 'robodesk_ticket',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            // 'meta_query'     => [
            //     [
            //         'key'     => '_robodesk_ticket_status',
            //         'value'   => 'closed',
            //         'compare' => '!=',
            //     ],
            // ],
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

                $dashboard_tickets[] = [
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
        $filtered_tickets = array_filter($dashboard_tickets, function ($t) use ($current_user_name, $filter) {
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

                //  $classes = [
                //     'low' => 'priority-low',
                //     'medium' => 'priority-medium',
                //     'high' => 'priority-high',
                // ];
                // return $classes[strtolower($priority)] ?? 'priority-default';
                // Get the term by name from robodesk_ticket_priority taxonomy
                $term = get_term_by('name', $priority, 'robodesk_ticket_priority');

                if ($term) {
                    // Get the custom class from term meta
                    $custom_class = get_term_meta($term->term_id, 'priority_color', true);
                    if (!empty($custom_class)) {
                        return $custom_class;
                    }
                }
            }
        }

        // Create priority colors object for JavaScript
        $priority_colors = [];
        $priorities = get_terms([
            'taxonomy' => 'robodesk_ticket_priority',
            'hide_empty' => false,
        ]);

        if (!is_wp_error($priorities)) {
            foreach ($priorities as $priority) {
                $color = get_term_meta($priority->term_id, 'priority_color', true);
                if (!empty($color)) {
                    $priority_colors[$priority->name] = $color;
                }
            }
        }

        // Pass priority colors to JavaScript
        echo '<script>
                        const robodeskPriorityColors = ' . json_encode($priority_colors) . ';
                    </script>';

        if (!function_exists('format_date')) {
            function format_date($date)
            {
                return date('M j, Y H:i', strtotime($date));
            }
        }


        function get_blinking_class($ticket)
        {
            // Only for chat tickets with open/in-progress status
            if (
                empty($dashboard_tickets['source']) || strtolower($dashboard_tickets['source']) !== 'chat' ||
                empty($dashboard_tickets['status']) || !in_array($dashboard_tickets['status'], ['open', 'in-progress'])
            ) {
                return '';
            }

            $replies = isset($dashboard_tickets['replies']) && is_array($dashboard_tickets['replies']) ? $dashboard_tickets['replies'] : [];
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
            } elseif (!empty($dashboard_tickets['updated'])) {
                $last_reply_time = strtotime($dashboard_tickets['updated']);
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
        <div class="overflow-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Conversation', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Last Reply', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Customer', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Source', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Status', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php _e('Priority', 'robodesk'); ?></th>
                        <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php //_e('Updated', 'robodesk'); 
                                                                                                                        ?></th> -->
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="dashboard-tickets-table">
                    <!-- Rows populated by JavaScript -->
                </tbody>
            </table>
        </div>
        <script>
            const dashboard_tickets = <?php echo wp_json_encode($dashboard_tickets); ?>;
        </script>
    <?php
        return ob_get_clean();
    }

    // Get filter from query parameter or default to 'my_conversation'
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'my_conversation';
    ?>

    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900"><?php _e('Conversation Overview', 'robodesk'); ?></h3>
                <div class="flex space-x-2">
                    <button onclick="filterDashboardTickets('my_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'my_conversation' ? 'active' : ''; ?>" data-filter="my_conversation"><?php _e('My Conversations', 'robodesk'); ?></button>

                    <button onclick="filterDashboardTickets('unassigned_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'unassigned_conversation' ? 'active' : ''; ?>" data-filter="unassigned_conversation"><?php _e('Unassigned Conversations', 'robodesk'); ?></button>

                    <button onclick="filterDashboardTickets('open_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'open_conversation' ? 'active' : ''; ?>" data-filter="open_conversation"><?php _e('Open Conversations', 'robodesk'); ?></button>

                    <button onclick="filterDashboardTickets('chat_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $filter === 'chat_conversation' ? 'active' : ''; ?>" data-filter="chat_conversation"><?php _e('Chat Conversations', 'robodesk'); ?></button>

                </div>
            </div>
            <?php echo load_dashboard_tickets($filter); ?>
        </div>
    </div>

    <!------------------------------------ Quick Actions ------------------------------------>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4"><?php _e('Quick Actions', 'robodesk'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="showPage('new-ticket')" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-plus text-blue-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-blue-900"><?php _e('Create New Conversation', 'robodesk'); ?></div>
                    <div class="text-xs text-blue-700"><?php _e('Add a new support conversation', 'robodesk'); ?></div>
                </button>
                <button onclick="window.location.href='<?php echo admin_url('users.php?role=robodesk_support_customer'); ?>'" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-users text-green-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-green-900"><?php _e('Manage Customers', 'robodesk'); ?></div>
                    <div class="text-xs text-green-700"><?php _e('View and edit customer information', 'robodesk'); ?></div>
                </button>

                <button onclick="showPage('settings')" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-cog text-purple-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-purple-900"><?php _e('System Settings', 'robodesk'); ?></div>
                    <div class="text-xs text-purple-700"><?php _e('Configure system preferences', 'robodesk'); ?></div>
                </button>
            </div>
        </div>
    </div>
</div>