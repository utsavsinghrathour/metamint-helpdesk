<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<?php
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$robodesk_current_tab    = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
$robodesk_has_ticket     = isset($_GET['ticket']) ? intval($_GET['ticket']) : 0;
$robodesk_dashboard_hidden = ($robodesk_current_tab !== '' && $robodesk_current_tab !== 'dashboard') || $robodesk_has_ticket > 0 ? ' hidden' : '';

/**
 * Count published tickets by status in a single grouped query.
 *
 * @return array
 */
if (!function_exists('robodesk_count_tickets_by_status')) {
    function robodesk_count_tickets_by_status()
    {
        global $wpdb;
        $counts = ['open' => 0, 'in-progress' => 0, 'closed' => 0];
        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.meta_value, COUNT(*) AS total
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_robodesk_ticket_status'
                 AND p.post_type = %s AND p.post_status = 'publish'
                 GROUP BY pm.meta_value",
                'robodesk_ticket'
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ($rows as $row) {
            if (isset($counts[$row->meta_value])) {
                $counts[$row->meta_value] = (int) $row->total;
            }
        }
        return $counts;
    }
}

$robodesk_status_counts = robodesk_count_tickets_by_status();
?>
<div id="dashboard-page" class="page-content<?php echo esc_attr($robodesk_dashboard_hidden); ?>">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex ">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-bar text-gray-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                <?php esc_html_e('Total Conversations', 'robodesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="total-tickets"><?php
                                                                                                $robodesk_total_tickets = wp_count_posts('robodesk_ticket')->publish;
                                                                                                echo esc_html($robodesk_total_tickets);
                                                                                                ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex ">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                <?php esc_html_e('Open Conversations', 'robodesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="open-tickets"><?php
                                                                                                echo esc_html($robodesk_status_counts['open']);
                                                                                                ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex ">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                <?php esc_html_e('In Progress', 'robodesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="in-progress-tickets"><?php
                                                                                                    echo esc_html($robodesk_status_counts['in-progress']);
                                                                                                    ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php esc_html_e('Closed', 'robodesk'); ?>
                            </dt>
                            <dd class="text-lg font-medium text-gray-900" id="closed-tickets"><?php
                                                                                                echo esc_html($robodesk_status_counts['closed']);
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

    // Helper functions moved outside to prevent re-declaration errors and scope issues
    if (!function_exists('robodesk_get_status_class')) {
        function robodesk_get_status_class($status)
        {
            $classes = [
                'open' => 'status-open',
                'in-progress' => 'status-in-progress',
                'closed' => 'status-closed',
            ];
            return $classes[$status] ?? 'status-default';
        }
    }

    if (!function_exists('robodesk_get_priority_class')) {
        function robodesk_get_priority_class($priority)
        {
            $term = get_term_by('name', $priority, 'robodesk_ticket_priority');

            if ($term) {
                // Get the custom class from term meta
                $custom_class = get_term_meta($term->term_id, 'priority_color', true);
                if (!empty($custom_class)) {
                    return $custom_class;
                }
            }
            return 'priority-default';
        }
    }

    if (!function_exists('robodesk_format_date')) {
        function robodesk_format_date($date)
        {
            return wp_date('M j, Y H:i', strtotime($date));
        }
    }

    function robodesk_load_dashboard_tickets($filter = 'my_conversation')
    {
        // Tickets are loaded via AJAX (robodesk_refresh_dashboard_tickets) with
        // server-side pagination, so only the table shell is rendered here.

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

        add_action('admin_footer', function () use ($priority_colors) {
            wp_add_inline_script('robodesk-dashboard-script', '
                const robodeskPriorityColors = ' . wp_json_encode($priority_colors) . ';
                const dashboard_tickets = [];
            ', 'before');
        }, 100);

        // Generate table HTML
        ob_start();
    ?>
        <div class="overflow-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Conversation', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Last Reply', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Customer', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Source', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Status', 'robodesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Priority', 'robodesk'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="dashboard-tickets-table">
                </tbody>
            </table>
        </div>
        <div id="dashboard-pagination" class="py-4"></div>
    <?php
        return ob_get_clean();
    }

    // Get filter from query parameter or default to 'my_conversation'
    if (
        isset($_GET['filter'], $_GET['_wpnonce']) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_GET['_wpnonce'])),
            'robodesk_filter'
        )
    ) {
        $robodesk_filter = sanitize_text_field(wp_unslash($_GET['filter']));
    } else {
        $robodesk_filter = 'my_conversation';
    }
    ?>

    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php esc_html_e('Conversation Overview', 'robodesk'); ?></h3>
                <div class="flex space-x-2">
                    <button onclick="filterDashboardTickets('my_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $robodesk_filter === 'my_conversation' ? 'active' : ''; ?>" data-filter="my_conversation"><?php esc_html_e('My Conversations', 'robodesk'); ?> <span id="dashboard-count-my-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('unassigned_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $robodesk_filter === 'unassigned_conversation' ? 'active' : ''; ?>" data-filter="unassigned_conversation"><?php esc_html_e('Unassigned Conversations', 'robodesk'); ?> <span id="dashboard-count-unassigned-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('open_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $robodesk_filter === 'open_conversation' ? 'active' : ''; ?>" data-filter="open_conversation"><?php esc_html_e('Open Conversations', 'robodesk'); ?> <span id="dashboard-count-open-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('chat_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $robodesk_filter === 'chat_conversation' ? 'active' : ''; ?>" data-filter="chat_conversation"><?php esc_html_e('Chat Conversations', 'robodesk'); ?> <span id="dashboard-count-chat-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                </div>
            </div>
            <?php echo robodesk_load_dashboard_tickets($robodesk_filter); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted admin template, individual values escaped within the function
            ?>
        </div>
    </div>

    <!------------------------------------ Quick Actions ------------------------------------>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4"><?php esc_html_e('Quick Actions', 'robodesk'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="showPage('new-ticket')"
                    class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-plus text-blue-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-blue-900"><?php esc_html_e('Create New Conversation', 'robodesk'); ?>
                    </div>
                    <div class="text-xs text-blue-700"><?php esc_html_e('Add a new support conversation', 'robodesk'); ?></div>
                </button>
                <button
                    onclick="window.location.href='<?php echo esc_url(admin_url('users.php?role=robodesk_support_customer')); ?>'"
                    class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-users text-green-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-green-900"><?php esc_html_e('Manage Customers', 'robodesk'); ?></div>
                    <div class="text-xs text-green-700"><?php esc_html_e('View and edit customer information', 'robodesk'); ?>
                    </div>
                </button>

                <button onclick="showPage('settings')"
                    class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-cog text-purple-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-purple-900"><?php esc_html_e('System Settings', 'robodesk'); ?></div>
                    <div class="text-xs text-purple-700"><?php esc_html_e('Configure system preferences', 'robodesk'); ?></div>
                </button>
            </div>
        </div>
    </div>
</div>
