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

    // Start output buffering to capture the HTML
    ob_start();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Robodesk - Support Ticket Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <!-- <link rel="stylesheet" href="styles.css"> -->
    </head>

    <body class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <i class="fas fa-ticket-alt text-blue-600 text-2xl"></i>
                            <span class="ml-2 text-xl font-bold text-gray-900">Robodesk</span>
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
                                        <dd class="text-lg font-medium text-gray-900" id="total-tickets">3</dd>
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
                                        <dd class="text-lg font-medium text-gray-900" id="open-tickets">1</dd>
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
                                        <dd class="text-lg font-medium text-gray-900" id="in-progress-tickets">1</dd>
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
                                        <dt class="text-sm font-medium text-gray-500 truncate">Resolved</dt>
                                        <dd class="text-lg font-medium text-gray-900" id="resolved-tickets">1</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Filter -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Ticket Overview</h3>
                            <div class="flex space-x-2">
                                <button onclick="filterDashboardTickets('recent')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border active" data-filter="recent">Recent Tickets</button>
                                <button onclick="filterDashboardTickets('replies')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border" data-filter="replies">Recent Replies</button>
                                <button onclick="filterDashboardTickets('waiting')" class="dashboard-filter-btn px-3 py-1 text-sm rounded-md border" data-filter="waiting">Waiting Reply</button>
                            </div>
                        </div>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="dashboard-tickets-table">
                                    <!-- Dashboard tickets will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button onclick="showPage('new-ticket')" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-left transition-colors">
                                <i class="fas fa-plus text-blue-600 text-xl mb-2"></i>
                                <div class="text-sm font-medium text-blue-900">Create New Ticket</div>
                                <div class="text-xs text-blue-700">Add a new support ticket</div>
                            </button>
                            <button class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-left transition-colors">
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

            <!-- New Ticket Page -->
            <div id="new-ticket-page" class="page-content hidden">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Create New Ticket</h3>

                        <form id="new-ticket-form" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                                    <input type="text" id="customer-name" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Email *</label>
                                    <input type="email" id="customer-email" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Title *</label>
                                <input type="text" id="ticket-title" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                                    <select id="ticket-priority" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Priority</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                    <select id="ticket-category" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Category</option>
                                        <option value="Technical">Technical</option>
                                        <option value="Billing">Billing</option>
                                        <option value="General">General</option>
                                        <option value="Feature Request">Feature Request</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign to *</label>
                                    <select id="ticket-assignee" required class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Agent</option>
                                        <option value="Support Team">Support Team</option>
                                        <option value="Tech Support">Tech Support</option>
                                        <option value="Design Team">Design Team</option>
                                        <option value="Sales Team">Sales Team</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Description *</label>
                                <textarea id="ticket-description" rows="6" required class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Describe the issue or request..."></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="showPage('dashboard')" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Create Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tickets Page -->
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
                                    <option value="resolved">Resolved</option>
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

                <!-- Tickets Table -->
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tickets-table">
                            <!-- Tickets will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ticket Detail Page -->
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
                                            <option value="open">Open</option>
                                            <option value="in-progress">In Progress</option>
                                            <option value="resolved">Resolved</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                        <select onchange="updateTicketPriority(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-priority-select">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                                        <select onchange="updateTicketAssignee(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-assignee-select">
                                            <option value="Support Team">Support Team</option>
                                            <option value="Tech Support">Tech Support</option>
                                            <option value="Design Team">Design Team</option>
                                            <option value="Sales Team">Sales Team</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Ticket Details</h4>
                                <div id="ticket-meta-info" class="space-y-2">
                                    <!-- Ticket meta info will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Customer Tickets Modal -->
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

                        <!-- Tab Content -->
                        <div class="mt-6">
                            <div id="replies-tab" class="ticket-tab-content">
                                <div id="replies-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                                    <!-- Replies will be populated by JavaScript -->
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Reply</label>
                                    <textarea id="new-reply" rows="4" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full border-gray-300 rounded-md" placeholder="Type your reply here..."></textarea>
                                    <button onclick="addReply()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Send Reply
                                    </button>
                                </div>
                            </div>

                            <div id="notes-tab" class="ticket-tab-content hidden">
                                <div id="notes-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                                    <!-- Notes will be populated by JavaScript -->
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Internal Note</label>
                                    <textarea id="new-note" rows="4" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full border-gray-300 rounded-md" placeholder="Add an internal note..."></textarea>
                                    <button onclick="addNote()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        Add Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Page -->
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

                    <div class="p-6">
                        <!-- Basic Settings Tab -->
                        <div id="basic-tab" class="settings-tab-content">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Site Name</label>
                                    <input type="text" value="Robodesk Support" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                                    <input type="email" value="admin@example.com" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Timezone</label>
                                    <select class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="UTC">UTC</option>
                                        <option value="EST">Eastern Time</option>
                                        <option value="PST">Pacific Time</option>
                                        <option value="GMT">GMT</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Language</label>
                                    <select class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="en">English</option>
                                        <option value="es">Spanish</option>
                                        <option value="fr">French</option>
                                        <option value="de">German</option>
                                    </select>
                                </div>
                            </div>
                            <hr class="my-6 border-gray-200">
                            <button type="button" id="save-basic-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Save Settings
                            </button>
                        </div>

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

                        <!-- IMAP Settings Tab -->
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


                        <!-- Email Templates Tab -->
                        <div id="templates-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Email Templates</h3>
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
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Resolved</label>
                                        <div class="editor-toolbar mb-2">
                                            <button type="button" onclick="formatText('bold', 'ticket-resolved-template')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                            <button type="button" onclick="formatText('italic', 'ticket-resolved-template')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                            <button type="button" onclick="formatText('underline', 'ticket-resolved-template')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                            <button type="button" onclick="formatText('insertUnorderedList', 'ticket-resolved-template')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" onclick="formatText('insertOrderedList', 'ticket-resolved-template')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                        </div>
                                        <div id="ticket-resolved-template" class="editor-content" contenteditable="true"><?php echo wp_kses_post(get_option('robodesk_email_template_ticket-resolved', 'Your ticket has been resolved. Please let us know if you need further assistance.')); ?></div>
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
                        <!-- Fetch Tickets Tab -->
                        <div id="fetch-tab" class="settings-tab-content hidden">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Fetch Tickets</h3>
                            <div class="space-y-6">
                                <p class="text-sm text-gray-600">Use this feature to fetch new emails from your configured IMAP account and convert them into tickets and replies.</p>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-2">Email Fetching Settings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fetch Interval (minutes)</label>
                                            <input type="number" value="15" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Auto-assign to</label>
                                            <select class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="support">Support Team</option>
                                                <option value="tech">Tech Support</option>
                                                <option value="sales">Sales Team</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-4">
                                    <button onclick="fetchEmails()" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-sync-alt mr-2"></i>
                                        Fetch Emails Now
                                    </button>
                                    <button class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        <i class="fas fa-cog mr-2"></i>
                                        Configure Auto-Fetch
                                    </button>
                                </div>
                                <div id="fetch-results" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        <span id="fetch-message" class="text-blue-800"></span>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-6 border-gray-200">
                            <button type="button" id="save-fetch-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Save Fetch Settings
                            </button>
                        </div>

                        <!-- Advanced Settings Tab -->


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

                        <!-- <div class="mt-8 pt-6 border-t border-gray-200">
                            <button onclick="saveSettings()" id="save-settings-btn" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save Settings
                            </button>
                        </div> -->
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

        <!-- <script src="script.js"></script> -->
    </body>

    </html>


<?php
    // Output the buffered content
    echo ob_get_clean();
}
