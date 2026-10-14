<?php
// Prevent direct access to the file and ensure WordPress functions are available
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Callback function to render the Metamint Helpdesk Ticket Dashboard
 */
function mmhd_dashboard_callback()
{
    // Check if the user has the required capability to view the dashboard
    if (!current_user_can('mmhd_access_dashboard')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'metamint-helpdesk'));
    }

    // get the current user ID and name
    // This is useful for debugging or displaying user-specific information
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name ?: $current_user->user_login;
    $current_user_role = $current_user->roles ? $current_user->roles[0] : 'Support Staff';
    // var_dump($current_user_role); // Debugging line to check current user role

    add_action('admin_footer', function () use ($current_user_id, $current_user_name, $current_user_role) {
        wp_add_inline_script('mmhd-dashboard-script', '
            const mmhdCurrentUser = {
                id: ' . intval($current_user_id) . ',
                name: ' . wp_json_encode($current_user_name) . ',
                role: ' . wp_json_encode($current_user_role) . '
            };
            const mmhdCanManageSettings = ' . (current_user_can('mmhd_manage_settings') ? 'true' : 'false') . ';
        ', 'before');
    }, 100);

    // var_dump($current_user_id); // Debugging line to check current user ID and name
    // var_dump($current_user_name); // Debugging line to check current user name

    // Active ticket count (open + in-progress) for the nav badge.
    // All counters come from the single source of truth.
    $mmhd_counters            = mmhd_get_ticket_counters($current_user_id);
    $mmhd_active_ticket_count = $mmhd_counters['counts']['open'];

    // No output buffering needed
?>

    <div class="bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <button onclick="showPage('dashboard')" class="bg-transparent p-0 m-0 cursor-pointer" style="background: transparent; border: none; outline: none; box-shadow: none; -webkit-appearance: none; appearance: none;">
                                <?php
                                $plugin_base_url = plugin_dir_url(dirname(__FILE__, 2));
                                $logo_url        = $plugin_base_url . 'admin/assets/images/mmhd-logo-wide.png';

                                echo '<img src="' . esc_url($logo_url) . '" alt="Company Logo" width="140" height="150" style="border: none;">';
                                ?>
                            </button>
                        </div>
                        <div class="mmhd-menu-head mmhd-mobile-hidden md:ml-10 md:flex md:space-x-8">
                            <button onclick="showPage('dashboard')"
                                class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                data-page="dashboard">
                                <i class="fas fa-home mr-2"></i>
                                <?php esc_html_e('Dashboard', 'metamint-helpdesk'); ?>
                            </button>
                            <button type="button" onclick="showPage('tickets', true)"
                                class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                data-page="tickets">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                <?php esc_html_e('Conversations', 'metamint-helpdesk'); ?>
                                <span class="dashboard-filter-count-badge is-visible"
                                    title="<?php esc_attr_e('Active conversations', 'metamint-helpdesk'); ?>"><?php echo esc_html($mmhd_active_ticket_count); ?></span>
                            </button>
                            <button onclick="showPage('new-ticket')"
                                class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                data-page="new-ticket">
                                <i class="fas fa-plus mr-2"></i>
                                <?php esc_html_e('New Conversation', 'metamint-helpdesk'); ?>
                            </button>
                            <?php if (current_user_can('mmhd_manage_settings')): ?>
                                <button onclick="showPage('settings')"
                                    class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                    data-page="settings">
                                    <i class="fas fa-cog mr-2"></i>
                                    <?php esc_html_e('Settings', 'metamint-helpdesk'); ?>
                                </button>
                            <?php endif; ?>
                            <?php if (defined('MMHD_ADDON_VERSION') && current_user_can('mmhd_manage_settings')): ?>
                                <button onclick="showPage('addons')"
                                    class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                    data-page="addons">
                                    <i class="fas fa-puzzle-piece mr-2"></i>
                                    <?php esc_html_e('Addons', 'metamint-helpdesk'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <button class="mmhd-nav-ham-tog mmhd-desktop-hidden">
                            <i class="fa-solid fa-bars" style="color: rgb(0, 124, 186);"></i>
                        </button>
                    </div>
                    <div class="flex items-center space-x-3 relative">
                        <a href="#" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm">
                            <?php esc_html_e('Leave us a review', 'metamint-helpdesk'); ?>
                        </a>

                        <button id="dashboard-notification-bell" onclick="toggleNotificationDropdown()"
                            class="bg-gray-100 p-2 rounded-full text-gray-600 hover:text-gray-900 relative transition-all"
                            title="<?php esc_html_e('View notifications', 'metamint-helpdesk'); ?>">
                            <i class="fas fa-bell"></i>
                            <span id="notification-badge"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 items-center justify-center shadow-lg"
                                style="display: none;">0</span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div id="notification-dropdown"
                            class="hidden absolute right-0 top-12 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    <?php esc_html_e('Recent Updates', 'metamint-helpdesk'); ?>
                                </h3>
                            </div>
                            <div id="notification-list" class="overflow-y-auto max-h-80">
                                <div class="px-4 py-8 text-center text-gray-500 text-sm">
                                    <i class="fas fa-bell-slash text-3xl mb-2 text-gray-400"></i>
                                    <p><?php esc_html_e('No new notifications', 'metamint-helpdesk'); ?></p>
                                </div>
                            </div>
                            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                                <button onclick="clearAllNotifications()"
                                    class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition-colors">
                                    <?php esc_html_e('Clear All', 'metamint-helpdesk'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Dashboard Page -->

            <?php
            include MMHD_PLUGIN_PATH . 'admin/includes/mmhd-dashboard-stats.php';
            ?>

            <!---------------------------------- New Ticket Page ---------------------------------->
            <?php
            include MMHD_PLUGIN_PATH . 'admin/includes/mmhd-create-conversation.php';
            ?>

            <!------------------------------------- Tickets Page ------------------------------------->
            <?php
            include MMHD_PLUGIN_PATH . 'admin/includes/mmhd-conversations.php';
            ?>

            <!------------------------------- Ticket Detail Page ----------------------->
            <?php
            include MMHD_PLUGIN_PATH . 'admin/includes/mmhd-conversation-detail.php';
            ?>

            <!---------------------- Settings Page ---------------------->
            <?php
            if (current_user_can('mmhd_manage_settings')) {
                include MMHD_PLUGIN_PATH . 'admin/includes/mmhd-settings.php';
            }
            ?>

            <!---------------------- Addons Page ---------------------->
            <?php
            // Include Addons page if Metamint Helpdesk Addon plugin is active
            if (defined('MMHD_ADDON_PLUGIN_PATH') && current_user_can('mmhd_manage_settings')) {
                $addons_file = MMHD_ADDON_PLUGIN_PATH . 'admin/includes/mmhd-addons.php';
                if (file_exists($addons_file)) {
                    include $addons_file;
                }
            }
            ?>
        </main>

        <!-- Toast Notification -->
        <div id="toast"
            class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg hidden">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span id="toast-message"></span>
            </div>
        </div>


    </div>

    <!-- Review Request Modal -->
    <div id="mmhd-review-modal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="mmhd-review-modal-title">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-star text-yellow-400 text-4xl"></i>
            </div>
            <h3 id="mmhd-review-modal-title" class="text-lg font-semibold text-gray-900 text-center mb-2">
                <?php esc_html_e('Enjoying Metamint Helpdesk?', 'metamint-helpdesk'); ?>
            </h3>
            <p class="text-sm text-gray-600 text-center mb-6">
                <?php esc_html_e('Your feedback helps us improve. Would you take a moment to leave us a review?', 'metamint-helpdesk'); ?>
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="#" id="mmhd-review-leave" target="_blank" rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md">
                    <i class="fas fa-star mr-2"></i>
                    <?php esc_html_e('Leave us a review', 'metamint-helpdesk'); ?>
                </a>
                <button type="button" id="mmhd-review-already"
                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md border border-gray-300">
                    <?php esc_html_e('Already reviewed', 'metamint-helpdesk'); ?>
                </button>
            </div>
        </div>
    </div>

<?php
}
