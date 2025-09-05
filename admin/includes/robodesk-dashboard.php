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
