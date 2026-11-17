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
$mmhd_stat_trends   = isset($mmhd_counters['trends']) && is_array($mmhd_counters['trends'])
    ? $mmhd_counters['trends']
    : mmhd_compute_ticket_stat_trends([]);

$mmhd_stat_cards = [
    'active' => [
        'id'       => 'open-tickets',
        'label'    => __('Active Conversations', 'metamint-helpdesk'),
        'caption'  => __('All time', 'metamint-helpdesk'),
        'value'    => (int) $mmhd_status_counts['active'],
        'icon'     => 'fas fa-headset',
        'modifier' => 'blue',
        'accent'   => '#3b82f6',
        'unit'     => __('active', 'metamint-helpdesk'),
    ],
    'inProgress' => [
        'id'       => 'in-progress-tickets',
        'label'    => __('In Progress', 'metamint-helpdesk'),
        'caption'  => __('All time', 'metamint-helpdesk'),
        'value'    => (int) $mmhd_status_counts['inProgress'],
        'icon'     => 'fas fa-clock',
        'modifier' => 'orange',
        'accent'   => '#f59e0b',
        'unit'     => __('in progress', 'metamint-helpdesk'),
    ],
    'total30' => [
        'id'       => 'total-tickets',
        'label'    => __('Total Conversations', 'metamint-helpdesk'),
        'caption'  => __('Last 30 days', 'metamint-helpdesk'),
        'value'    => (int) $mmhd_status_counts['total30'],
        'icon'     => 'fas fa-comments',
        'modifier' => 'green',
        'accent'   => '#10b981',
        'unit'     => __('created', 'metamint-helpdesk'),
    ],
    'closed30' => [
        'id'       => 'closed-tickets',
        'label'    => __('Closed', 'metamint-helpdesk'),
        'caption'  => __('Last 30 days', 'metamint-helpdesk'),
        'value'    => (int) $mmhd_status_counts['closed30'],
        'icon'     => 'fas fa-check-circle',
        'modifier' => 'purple',
        'accent'   => '#8b5cf6',
        'unit'     => __('closed', 'metamint-helpdesk'),
    ],
];
?>
<div id="dashboard-page" class="page-content<?php echo esc_attr($mmhd_dashboard_hidden); ?>">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 mmhd-dashboard-stats">
        <?php foreach ($mmhd_stat_cards as $mmhd_card_key => $mmhd_card) :
            $mmhd_trend       = isset($mmhd_stat_trends[$mmhd_card_key]) ? $mmhd_stat_trends[$mmhd_card_key] : ['series' => [], 'change' => 0];
            $mmhd_series      = isset($mmhd_trend['series']) && is_array($mmhd_trend['series']) ? array_values($mmhd_trend['series']) : [];
            $mmhd_dates       = isset($mmhd_trend['dates']) && is_array($mmhd_trend['dates']) ? array_values($mmhd_trend['dates']) : [];
            $mmhd_paths       = mmhd_get_sparkline_paths($mmhd_series);
            $mmhd_last        = isset($mmhd_paths['last']) ? $mmhd_paths['last'] : [96, 18];
            $mmhd_change      = array_key_exists('change', $mmhd_trend) ? $mmhd_trend['change'] : 0;
            $mmhd_has_percent = is_numeric($mmhd_change);
            $mmhd_change_int  = $mmhd_has_percent ? (int) $mmhd_change : 0;
            $mmhd_trend_text  = $mmhd_has_percent
                ? (($mmhd_change_int > 0 ? '+' : '') . $mmhd_change_int . '%')
                : __('New', 'metamint-helpdesk');
            $mmhd_direction   = isset($mmhd_trend['direction'])
                ? $mmhd_trend['direction']
                : ($mmhd_change_int > 0 ? 'up' : ($mmhd_change_int < 0 ? 'down' : 'flat'));
            $mmhd_gradient    = 'mmhd-spark-' . strtolower($mmhd_card_key);
            $mmhd_aria_label  = sprintf(
                /* translators: 1: stat card label, 2: trend label. */
                __('%1$s: sparkline for the last 14 days. Trend %2$s versus the previous 7 days.', 'metamint-helpdesk'),
                $mmhd_card['label'],
                $mmhd_trend_text
            );
        ?>
            <div class="mmhd-dashboard-stat-card mmhd-stat-card--<?php echo esc_attr($mmhd_card['modifier']); ?>">
                <div class="mmhd-stat-card__header">
                    <span class="mmhd-stat-card__icon" aria-hidden="true">
                        <i class="<?php echo esc_attr($mmhd_card['icon']); ?>"></i>
                    </span>
                    <span class="mmhd-stat-card__trend is-<?php echo esc_attr($mmhd_direction); ?>"
                        data-mmhd-stat-trend="<?php echo esc_attr($mmhd_card_key); ?>"
                        data-mmhd-trend-new="<?php echo esc_attr__('New', 'metamint-helpdesk'); ?>"
                        title="<?php esc_attr_e('Last 7 days vs previous 7 days', 'metamint-helpdesk'); ?>">
                        <i class="fas <?php echo esc_attr($mmhd_direction === 'up' ? 'fa-arrow-trend-up' : ($mmhd_direction === 'down' ? 'fa-arrow-trend-down' : 'fa-minus')); ?>" aria-hidden="true"></i>
                        <span><?php echo esc_html($mmhd_trend_text); ?></span>
                    </span>
                </div>
                <div class="mmhd-stat-card__body">
                    <span class="mmhd-stat-card__label"><?php echo esc_html($mmhd_card['label']); ?></span>
                    <span class="mmhd-stat-card__value" id="<?php echo esc_attr($mmhd_card['id']); ?>"><?php echo esc_html($mmhd_card['value']); ?></span>
                    <span class="mmhd-stat-card__caption"><?php echo esc_html($mmhd_card['caption']); ?></span>
                </div>
                <div class="mmhd-stat-card__chart"
                    data-mmhd-sparkline-chart="<?php echo esc_attr($mmhd_card_key); ?>"
                    data-mmhd-sparkline-series="<?php echo esc_attr(wp_json_encode($mmhd_series)); ?>"
                    data-mmhd-sparkline-dates="<?php echo esc_attr(wp_json_encode($mmhd_dates)); ?>"
                    data-mmhd-sparkline-unit="<?php echo esc_attr($mmhd_card['unit']); ?>"
                    data-mmhd-sparkline-today="<?php echo esc_attr(wp_date('Y-m-d')); ?>"
                    data-mmhd-sparkline-label-today="<?php echo esc_attr__('Today', 'metamint-helpdesk'); ?>"
                    data-mmhd-sparkline-label-yesterday="<?php echo esc_attr__('Yesterday', 'metamint-helpdesk'); ?>"
                    data-mmhd-sparkline-label-template="<?php
                        /* translators: 1: sparkline label, 2: trend direction (up or down). */
                        echo esc_attr(__('%1$s: sparkline for the last 14 days. Trend %2$s versus the previous 7 days.', 'metamint-helpdesk'));
                    ?>"
                    tabindex="0"
                    role="img"
                    aria-label="<?php echo esc_attr($mmhd_aria_label); ?>">
                    <svg viewBox="0 0 100 36" preserveAspectRatio="none" focusable="false" aria-hidden="true">
                        <defs>
                            <linearGradient id="<?php echo esc_attr($mmhd_gradient); ?>" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="0.42" />
                                <stop offset="55%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="0.14" />
                                <stop offset="100%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="<?php echo esc_attr($mmhd_gradient); ?>-line" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="0.4" />
                                <stop offset="55%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="0.88" />
                                <stop offset="100%" stop-color="<?php echo esc_attr($mmhd_card['accent']); ?>" stop-opacity="1" />
                            </linearGradient>
                        </defs>
                        <path class="mmhd-stat-card__chart-area"
                            data-mmhd-sparkline-area="<?php echo esc_attr($mmhd_card_key); ?>"
                            fill="url(#<?php echo esc_attr($mmhd_gradient); ?>)"
                            d="<?php echo esc_attr($mmhd_paths['area']); ?>"></path>
                        <path class="mmhd-stat-card__chart-line"
                            data-mmhd-sparkline-line="<?php echo esc_attr($mmhd_card_key); ?>"
                            fill="none"
                            stroke="url(#<?php echo esc_attr($mmhd_gradient); ?>-line)"
                            d="<?php echo esc_attr($mmhd_paths['line']); ?>"></path>
                    </svg>
                    <span class="mmhd-stat-card__chart-dot"
                        data-mmhd-sparkline-dot="<?php echo esc_attr($mmhd_card_key); ?>"
                        style="left:<?php echo esc_attr(round($mmhd_last[0], 2)); ?>%;top:<?php echo esc_attr(round(($mmhd_last[1] / 36) * 100, 2)); ?>%"></span>
                    <span class="mmhd-stat-card__chart-guide"
                        data-mmhd-sparkline-guide="<?php echo esc_attr($mmhd_card_key); ?>"
                        aria-hidden="true"></span>
                    <span class="mmhd-stat-card__chart-point"
                        data-mmhd-sparkline-point="<?php echo esc_attr($mmhd_card_key); ?>"
                        aria-hidden="true"></span>
                    <span class="mmhd-stat-card__chart-tip"
                        data-mmhd-sparkline-tip="<?php echo esc_attr($mmhd_card_key); ?>"
                        aria-hidden="true">
                        <span class="mmhd-stat-card__chart-tip-date" data-mmhd-sparkline-tip-date></span>
                        <span class="mmhd-stat-card__chart-tip-value" data-mmhd-sparkline-tip-value></span>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
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
            <table class="min-w-full divide-y divide-gray-200 mmhd-dashboard-table">
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
        <div id="dashboard-pagination" class="py-4 px-6"></div>
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
        <div class="mmhd-dashboard-tickets">
            <div class="flex justify-between items-center p-2 sm:p-6">
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
    <div class="bg-white shadow rounded-lg mmhd-dashboard-quick-actions">
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