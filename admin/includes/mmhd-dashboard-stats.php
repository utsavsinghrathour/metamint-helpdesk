<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<?php
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$mmhd_current_tab    = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
$mmhd_has_ticket     = isset($_GET['ticket']) ? intval($_GET['ticket']) : 0;
$mmhd_dashboard_hidden = ($mmhd_current_tab !== '' && $mmhd_current_tab !== 'dashboard') || $mmhd_has_ticket > 0 ? ' hidden' : '';

// All dashboard counters come from the single source of truth:
// mmhd_get_ticket_counters() (see mmhd-dashboard-data.php).
$mmhd_counters      = mmhd_get_ticket_counters();
$mmhd_status_counts = $mmhd_counters['stats'];
$mmhd_total_tickets = $mmhd_counters['stats']['total'];
?>
<div id="dashboard-page" class="page-content<?php echo esc_attr($mmhd_dashboard_hidden); ?>">
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
                                <?php esc_html_e('Total Conversations', 'metamint-helpdesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="total-tickets"><?php
                                                                                                echo esc_html($mmhd_total_tickets);
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
                                <?php esc_html_e('Active Conversations', 'metamint-helpdesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="open-tickets"><?php
                                                                                            echo esc_html($mmhd_counters['stats']['active']);
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
                                <?php esc_html_e('In Progress', 'metamint-helpdesk'); ?></dt>
                            <dd class="text-lg font-medium text-gray-900" id="in-progress-tickets"><?php
                                                                                                    echo esc_html($mmhd_status_counts['inProgress']);
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
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php esc_html_e('Closed', 'metamint-helpdesk'); ?>
                            </dt>
                            <dd class="text-lg font-medium text-gray-900" id="closed-tickets"><?php
                                                                                                echo esc_html($mmhd_status_counts['closed']);
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
    if (!function_exists('mmhd_get_status_class')) {
        function mmhd_get_status_class($status)
        {
            $classes = [
                'open' => 'status-open',
                'in-progress' => 'status-in-progress',
                'closed' => 'status-closed',
            ];
            return $classes[$status] ?? 'status-default';
        }
    }

    if (!function_exists('mmhd_get_priority_class')) {
        function mmhd_get_priority_class($priority)
        {
            $term = get_term_by('name', $priority, 'mmhd_ticket_priority');

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

    if (!function_exists('mmhd_format_date')) {
        function mmhd_format_date($date)
        {
            return wp_date('M j, Y H:i', strtotime($date));
        }
    }

    function mmhd_load_dashboard_tickets($filter = 'my_conversation')
    {
        // Tickets are loaded via AJAX (mmhd_refresh_dashboard_tickets) with
        // server-side pagination, so only the table shell is rendered here.

        // Create priority colors object for JavaScript
        $priority_colors = [];
        $priorities = get_terms([
            'taxonomy' => 'mmhd_ticket_priority',
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
            wp_add_inline_script('mmhd-dashboard-script', '
                const mmhdPriorityColors = ' . wp_json_encode($priority_colors) . ';
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
                            <?php esc_html_e('Conversation', 'metamint-helpdesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Last Reply', 'metamint-helpdesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Customer', 'metamint-helpdesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Source', 'metamint-helpdesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Status', 'metamint-helpdesk'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php esc_html_e('Priority', 'metamint-helpdesk'); ?></th>
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
            'mmhd_filter'
        )
    ) {
        $mmhd_filter = sanitize_text_field(wp_unslash($_GET['filter']));
    } else {
        $mmhd_filter = 'my_conversation';
    }
    ?>

    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php esc_html_e('Conversation Overview', 'metamint-helpdesk'); ?></h3>
                <div class="flex space-x-2">
                    <button onclick="filterDashboardTickets('my_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $mmhd_filter === 'my_conversation' ? 'active' : ''; ?>" data-filter="my_conversation"><?php esc_html_e('My Conversations', 'metamint-helpdesk'); ?> <span id="dashboard-count-my-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('unassigned_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $mmhd_filter === 'unassigned_conversation' ? 'active' : ''; ?>" data-filter="unassigned_conversation"><?php esc_html_e('Unassigned Conversations', 'metamint-helpdesk'); ?> <span id="dashboard-count-unassigned-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('open_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $mmhd_filter === 'open_conversation' ? 'active' : ''; ?>" data-filter="open_conversation"><?php esc_html_e('Active Conversations', 'metamint-helpdesk'); ?> <span id="dashboard-count-open-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                    <button onclick="filterDashboardTickets('chat_conversation')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border <?php echo $mmhd_filter === 'chat_conversation' ? 'active' : ''; ?>" data-filter="chat_conversation"><?php esc_html_e('Chat Conversations', 'metamint-helpdesk'); ?> <span id="dashboard-count-chat-conversation" class="dashboard-filter-count-badge" aria-hidden="true"></span></button>

                </div>
            </div>
            <?php echo mmhd_load_dashboard_tickets($mmhd_filter); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted admin template, individual values escaped within the function
            ?>
        </div>
    </div>

    <!------------------------------------ Quick Actions ------------------------------------>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4"><?php esc_html_e('Quick Actions', 'metamint-helpdesk'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="showPage('new-ticket')"
                    class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-plus text-blue-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-blue-900"><?php esc_html_e('Create New Conversation', 'metamint-helpdesk'); ?>
                    </div>
                    <div class="text-xs text-blue-700"><?php esc_html_e('Add a new support conversation', 'metamint-helpdesk'); ?></div>
                </button>
                <button
                    onclick="window.location.href='<?php echo esc_url(admin_url('users.php?role=mmhd_support_customer')); ?>'"
                    class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-users text-green-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-green-900"><?php esc_html_e('Manage Customers', 'metamint-helpdesk'); ?></div>
                    <div class="text-xs text-green-700"><?php esc_html_e('View and edit customer information', 'metamint-helpdesk'); ?>
                    </div>
                </button>

                <button onclick="showPage('settings')"
                    class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-left transition-colors">
                    <i class="fas fa-cog text-purple-600 text-xl mb-2"></i>
                    <div class="text-sm font-medium text-purple-900"><?php esc_html_e('System Settings', 'metamint-helpdesk'); ?></div>
                    <div class="text-xs text-purple-700"><?php esc_html_e('Configure system preferences', 'metamint-helpdesk'); ?></div>
                </button>
            </div>
        </div>
    </div>
</div>