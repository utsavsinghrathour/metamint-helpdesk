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
    if (!current_user_can('robodesk_access_dashboard')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'robodesk'));
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

    // No output buffering needed
?>

<div class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <button onclick="showPage('dashboard')" class="text-blue-600 hover:text-blue-800">
                            <?php
                                $plugin_base_url = plugin_dir_url(dirname(__FILE__, 2));
                                $logo_url        = $plugin_base_url . 'admin/assets/images/robodesk-black.png';

                                echo '<img src="' . esc_url($logo_url) . '" alt="Company Logo" width="130" height="100">';
                                ?>
                        </button>
                    </div>
                    <div class="rbd-menu-head rbd-mobile-hidden md:ml-10 md:flex md:space-x-8">
                        <button onclick="showPage('dashboard')"
                            class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                            data-page="dashboard">
                            <i class="fas fa-home mr-2"></i>
                            <?php esc_html_e('Dashboard', 'robodesk'); ?>
                        </button>
                        <button type="button" onclick="showPage('tickets', true)"
                            class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                            data-page="tickets">
                            <i class="fas fa-ticket-alt mr-2"></i>
                            <?php esc_html_e('Conversations', 'robodesk'); ?>
                        </button>
                        <button onclick="showPage('new-ticket')"
                            class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                            data-page="new-ticket">
                            <i class="fas fa-plus mr-2"></i>
                            <?php esc_html_e('New Conversation', 'robodesk'); ?>
                        </button>
                        <button onclick="showPage('settings')"
                            class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                            data-page="settings">
                            <i class="fas fa-cog mr-2"></i>
                            <?php esc_html_e('Settings', 'robodesk'); ?>
                        </button>
                        <?php if (defined('ROBODESK_ADDON_VERSION')): ?>
                        <button onclick="showPage('addons')"
                            class="nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                            data-page="addons">
                            <i class="fas fa-puzzle-piece mr-2"></i>
                            <?php esc_html_e('Addons', 'robodesk'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <button class="rbd-nav-ham-tog rbd-desktop-hidden">
                        <i class="fa-solid fa-bars" style="color: rgb(0, 124, 186);"></i>
                    </button>
                </div>
                <div class="flex items-center relative">
                    <button id="dashboard-notification-bell" onclick="toggleNotificationDropdown()"
                        class="bg-gray-100 p-2 rounded-full text-gray-600 hover:text-gray-900 relative transition-all"
                        title="<?php esc_html_e('View notifications', 'robodesk'); ?>">
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
                                <?php esc_html_e('Recent Updates', 'robodesk'); ?>
                            </h3>
                        </div>
                        <div id="notification-list" class="overflow-y-auto max-h-80">
                            <div class="px-4 py-8 text-center text-gray-500 text-sm">
                                <i class="fas fa-bell-slash text-3xl mb-2 text-gray-400"></i>
                                <p><?php esc_html_e('No new notifications', 'robodesk'); ?></p>
                            </div>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                            <button onclick="clearAllNotifications()"
                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition-colors">
                                <?php esc_html_e('Clear All', 'robodesk'); ?>
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
            include ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-dashboard-stats.php';
            ?>

        <!---------------------------------- New Ticket Page ---------------------------------->
        <?php
            include ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-create-conversation.php';
            ?>

        <!------------------------------------- Tickets Page ------------------------------------->
        <?php
            include ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-conversations.php';
            ?>

        <!------------------------------- Ticket Detail Page ----------------------->
        <?php
            include ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-conversation-detail.php';
            ?>

        <!---------------------- Settings Page ---------------------->
        <?php
            include ROBODESK_PLUGIN_PATH . 'admin/includes/robodesk-settings.php';
            ?>

        <!---------------------- Addons Page ---------------------->
        <?php
            // Include Addons page if Robodesk Addon plugin is active
            if (defined('ROBODESK_ADDON_PLUGIN_PATH')) {
                $addons_file = ROBODESK_ADDON_PLUGIN_PATH . 'admin/includes/robodesk-addons.php';
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

<?php
}