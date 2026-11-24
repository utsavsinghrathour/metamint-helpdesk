<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$mmhd_statuses = array('open', 'in-progress', 'closed');
$mmhd_priorities = get_terms([
    'taxonomy' => 'mmhd_ticket_priority',
    'hide_empty' => false,
]);
$mmhd_default_agent = get_option('mmhd_default_agent');
$mmhd_agents = get_users([
    'role__in' => ['mmhd_support_staff', 'mmhd_support_manager', 'author', 'editor', 'administrator'],
    'fields' => ['ID', 'display_name'],
]);

$mmhd_terms = get_terms([
    'taxonomy' => 'mmhd_ticket_category',
    'hide_empty' => false,
]);
if (is_wp_error($mmhd_terms) || !is_array($mmhd_terms)) {
    $mmhd_terms = [];
}

// Users eligible to receive a transferred ticket (all registered users)
$mmhd_transfer_users = get_users([
    'fields' => ['ID', 'display_name', 'user_email'],
    'number' => 100,
    'orderby' => 'display_name',
    'order'  => 'ASC',
]);
if (is_wp_error($mmhd_transfer_users) || !is_array($mmhd_transfer_users)) {
    $mmhd_transfer_users = [];
}

$mmhd_default_category = get_option('mmhd_default_ticket_category', 'General');
$mmhd_default_category_obj = null;
foreach ($mmhd_terms as $term) {
    if ($term->name === $mmhd_default_category || $term->slug === $mmhd_default_category) {
        $mmhd_default_category_obj = $term;
        break;
    }
}
?>

<?php
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$mmhd_current_tab    = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$mmhd_has_ticket     = isset($_GET['ticket']) ? intval($_GET['ticket']) : 0;
$mmhd_td_hidden      = ($mmhd_current_tab === 'ticket-detail' || $mmhd_has_ticket > 0) ? '' : ' hidden';
?>
<div id="ticket-detail-page" class="page-content<?php echo esc_attr($mmhd_td_hidden); ?>">
    <div class="bg-white shadow rounded-lg">
        <div class="converstaion-wrapper">


            <div class="grid grid-cols-1 lg:grid-cols-3 pb-6">
                <div class="lg:col-span-2 space-y-6 px-6">
                    <div class="mb-6 justify-between flex items-start py-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 ticket-title-80" id="ticket-detail-title">
                            <?php esc_html_e('Conversation Details', 'metamint-helpdesk'); ?>
                        </h3>
                        <button type="button" onclick="backFromTicketDetail()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?php esc_html_e('Back', 'metamint-helpdesk'); ?>
                        </button>
                    </div>
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTicketTab('replies')"
                                class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="replies">
                                <?php esc_html_e('Replies', 'metamint-helpdesk'); ?> (<span id="replies-count">0</span>)
                            </button>
                            <button onclick="showTicketTab('notes')"
                                class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="notes">
                                <?php esc_html_e('Internal Notes', 'metamint-helpdesk'); ?> (<span id="notes-count">0</span>)
                            </button>
                        </nav>
                    </div>

                    <div class="mt-6">
                        <div id="replies-tab" class="ticket-tab-content">
                            <div id="replies-list" class="space-y-3 mb-4"
                                data-audio="<?php echo esc_attr(get_option('mmhd_notification_sound')); ?>">
                                <!-- Replies will be populated by JavaScript -->
                            </div>
                            <div>
                                <div class="add-label-wrapper d-flex justify-content-between align-items-center">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php esc_html_e('Add Reply', 'metamint-helpdesk'); ?>
                                    </label>
                                    <button onclick="showVariablesPopup()"
                                        class="text-xs text-teal-600 hover:text-teal-800 font-medium" type="button">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <?php esc_html_e('Insert Variables', 'metamint-helpdesk'); ?>
                                    </button>
                                    <?php do_action('mmhd_before_reply_editor'); ?>
                                </div>
                                <div class="editor-toolbar">
                                    <button type="button" onclick="formatText('bold', 'new-reply')"
                                        class="editor-btn"><i class="fas fa-bold"></i></button>
                                    <button type="button" onclick="formatText('italic', 'new-reply')"
                                        class="editor-btn"><i class="fas fa-italic"></i></button>
                                    <button type="button" onclick="formatText('underline', 'new-reply')"
                                        class="editor-btn"><i class="fas fa-underline"></i></button>
                                    <button type="button" onclick="formatText('insertUnorderedList', 'new-reply')"
                                        class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                    <button type="button" onclick="formatText('insertOrderedList', 'new-reply')"
                                        class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                </div>
                                <div id="new-reply" class="editor-content placeholder" contenteditable="true"
                                    style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                                    data-placeholder="<?php esc_html_e('Type your reply here...', 'metamint-helpdesk'); ?>"
                                    aria-label="Reply input"></div>
                                <?php do_action('mmhd_after_reply_editor'); ?>
                                <button id="send-reply-btn" onclick="addReply()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                    <?php esc_html_e('Send Reply', 'metamint-helpdesk'); ?>
                                </button>

                                <button onclick="closeTicket()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 float-right"
                                    type="button">
                                    <?php esc_html_e('Close Conversation', 'metamint-helpdesk'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="notes-tab" class="ticket-tab-content hidden">
                            <div id="notes-list" class="space-y-4 mb-4">
                                <!-- Notes will be populated by JavaScript -->
                            </div>
                            <div>
                                <div class="add-label-wrapper d-flex justify-content-between align-items-center">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php esc_html_e('Add Internal Note', 'metamint-helpdesk'); ?>
                                    </label>
                                    <button onclick="showVariablesPopup()"
                                        class="text-xs text-teal-600 hover:text-teal-800 font-medium" type="button">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <?php esc_html_e('Insert Variables', 'metamint-helpdesk'); ?>
                                    </button>
                                </div>
                                <div class="editor-toolbar">
                                    <button type="button" onclick="formatText('bold', 'new-note')" class="editor-btn"><i
                                            class="fas fa-bold"></i></button>
                                    <button type="button" onclick="formatText('italic', 'new-note')"
                                        class="editor-btn"><i class="fas fa-italic"></i></button>
                                    <button type="button" onclick="formatText('underline', 'new-note')"
                                        class="editor-btn"><i class="fas fa-underline"></i></button>
                                    <button type="button" onclick="formatText('insertUnorderedList', 'new-note')"
                                        class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                    <button type="button" onclick="formatText('insertOrderedList', 'new-note')"
                                        class="editor-btn"><i class="fas fa-list-ol"></i></button>
                                </div>
                                <div id="new-note" class="editor-content placeholder" contenteditable="true"
                                    style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                                    data-placeholder="<?php esc_html_e('Type your internal note here...', 'metamint-helpdesk'); ?>">
                                </div>
                                <button onclick="addNote()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500"
                                    type="button">
                                    <?php esc_html_e('Add Note', 'metamint-helpdesk'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="variables-popup"
                            style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.1); z-index: 1000; width: 90%; max-width: 400px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600;">
                                    <?php esc_html_e('Template Variables', 'metamint-helpdesk'); ?>
                                </h3>
                                <button onclick="hideVariablesPopup()"
                                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;"
                                    type="button">×</button>
                            </div>
                            <div id="variables-list" style="display: grid; gap: 0.75rem;"></div>
                        </div>

                        <div id="variables-backdrop" onclick="hideVariablesPopup()"
                            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999;">
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1 sticky-sidebar">
                    <div class="space-y-6 px-6 py-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                                <?php esc_html_e('Conversation Status', 'metamint-helpdesk'); ?>
                            </h2>

                            <div class="space-y-4">
                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-status-select"><?php esc_html_e('Status', 'metamint-helpdesk'); ?></label>
                                    <select onchange="updateTicketStatus(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        id="ticket-status-select">
                                        <?php foreach ($mmhd_statuses as $status): ?>
                                            <option value="<?php echo esc_attr($status); ?>">
                                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $status))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-priority-select"><?php esc_html_e('Priority', 'metamint-helpdesk'); ?></label>
                                    <select onchange="updateTicketPriority(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        id="ticket-priority-select">
                                        <?php if (!is_wp_error($mmhd_priorities)): ?>
                                            <?php foreach ($mmhd_priorities as $mmhd_priority): ?>
                                                <option value="<?php echo esc_attr($mmhd_priority->name); ?>">
                                                    <?php echo esc_html($mmhd_priority->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-assignee-select"><?php esc_html_e('Assignee', 'metamint-helpdesk'); ?></label>
                                    <select onchange="updateTicketAssignee(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm"
                                        id="ticket-assignee-select">
                                        <?php foreach ($mmhd_agents as $mmhd_agent): ?>
                                            <?php
                                            $mmhd_agent_value = (int) $mmhd_agent->ID;
                                            $mmhd_is_selected = (string) $mmhd_default_agent === (string) $mmhd_agent_value || (string) $mmhd_default_agent === (string) $mmhd_agent->display_name;
                                            ?>
                                            <option value="<?php echo esc_attr($mmhd_agent_value); ?>"
                                                <?php selected($mmhd_is_selected, true); ?>>
                                                <?php echo esc_html($mmhd_agent->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-category-select"><?php esc_html_e('Department', 'metamint-helpdesk'); ?></label>
                                    <select id="ticket-category-select"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm">
                                        <?php if (!empty($mmhd_terms)): ?>
                                            <?php foreach ($mmhd_terms as $term): ?>
                                                <option value="<?php echo esc_attr($term->term_id); ?>"
                                                    <?php selected((string) ($mmhd_default_category_obj ? $mmhd_default_category_obj->term_id : ''), (string) $term->term_id); ?>>
                                                    <?php echo esc_html($term->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value=""><?php esc_html_e('No departments found', 'metamint-helpdesk'); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mmhd-customer-info">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Conversation Details', 'metamint-helpdesk'); ?>
                                </h2>
                            </div>

                            <div id="customer-info" class="space-y-2 mb-4">
                                <!-- Customer info will be populated by JavaScript -->
                            </div>

                            <div id="ticket-meta-info" class="space-y-2 pt-4 border-gray-100 text-sm">
                                <!-- Ticket meta info will be populated by JavaScript -->
                            </div>

                            <!-- Transfer Conversation Card -->
                            <div id="ticket-transfer-card" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <i class="fas fa-share text-teal-600"></i>
                                    <?php esc_html_e('Transfer Conversation', 'metamint-helpdesk'); ?>
                                </h3>
                                <form id="mmhd-transfer-ticket-form" method="post">
                                    <?php wp_nonce_field('mmhd_transfer_ticket_nonce', 'mmhd_transfer_ticket_nonce_field'); ?>
                                    <input type="hidden" name="ticket_id" id="mmhd-transfer-ticket-id" value="">
                                    <input type="hidden" name="action" value="mmhd_transfer_ticket">

                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            <?php esc_html_e('Transfer to existing user:', 'metamint-helpdesk'); ?>
                                        </label>
                                        <!-- Searchable user dropdown -->
                                        <div class="mmhd-transfer-user-dropdown relative w-full mb-1">
                                            <input type="text" id="mmhd-transfer-user-search"
                                                placeholder="Search users..."
                                                class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-teal-500 focus:border-teal-500 cursor-pointer">
                                            <input type="hidden" name="target_user_id" id="mmhd-transfer-user-id" value="0">
                                            <div id="mmhd-transfer-user-list"
                                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                                <div class="px-3 py-2 text-sm text-gray-500 border-b border-gray-100">
                                                    <?php esc_html_e('Select a user', 'metamint-helpdesk'); ?>
                                                </div>
                                                <?php foreach ($mmhd_transfer_users as $mmhd_tu): ?>
                                                    <div class="mmhd-transfer-user-option px-3 py-2 text-sm hover:bg-teal-50 cursor-pointer"
                                                        data-user-id="<?php echo esc_attr($mmhd_tu->ID); ?>"
                                                        data-user-email="<?php echo esc_attr($mmhd_tu->user_email); ?>">
                                                        <div class="font-medium text-gray-900 leading-tight">
                                                            <?php echo esc_html($mmhd_tu->display_name); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 leading-tight">
                                                            <?php echo esc_html($mmhd_tu->user_email); ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div id="mmhd-transfer-user-no-results"
                                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden px-3 py-2 text-sm text-gray-500">
                                                <?php esc_html_e('No users found', 'metamint-helpdesk'); ?>
                                            </div>
                                        </div>
                                        <input type="hidden" id="mmhd-transfer-selected-user" value="0">
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            <?php esc_html_e('Or transfer to new user (email):', 'metamint-helpdesk'); ?>
                                        </label>
                                        <input type="email" id="mmhd-transfer-new-email" name="new_user_email"
                                            placeholder="user@example.com"
                                            class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-teal-500 focus:border-teal-500">
                                    </div>

                                    <button type="button" id="mmhd-transfer-submit-btn"
                                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500"
                                        type="button">
                                        <?php esc_html_e('Transfer Conversation', 'metamint-helpdesk'); ?>
                                    </button>

                                    <div id="mmhd-transfer-message" class="mt-3 text-sm"></div>
                                </form>

                                <style>
                                    #mmhd-transfer-user-search {
                                        cursor: auto;
                                    }
                                    #mmhd-transfer-user-search::-ms-clear,
                                    #mmhd-transfer-user-search::-ms-reveal {
                                        display: none;
                                        width: 0;
                                        height: 0;
                                    }
                                    #mmhd-transfer-user-search::-webkit-search-cancel-button,
                                    #mmhd-transfer-user-search::-webkit-search-decoration {
                                        display: none;
                                    }
                                    #mmhd-transfer-user-list {
                                        z-index: 9999;
                                    }
                                    .mmhd-transfer-user-option {
                                        transition: background 0.15s ease;
                                        border-bottom: 1px solid #f3f4f6;
                                    }
                                    .mmhd-transfer-user-option:last-child {
                                        border-bottom: none;
                                    }
                                    .mmhd-transfer-user-option:hover {
                                        background: #eff6ff !important;
                                    }
                                    .mmhd-transfer-user-option .font-medium {
                                        color: #111827;
                                    }
                                    .mmhd-transfer-user-option .text-xs {
                                        color: #6b7280;
                                    }
                                    #mmhd-transfer-user-list .border-b {
                                        font-weight: 600;
                                        color: #6b7280;
                                        background: #f9fafb;
                                        padding: 0.75rem 1rem;
                                    }
                                </style>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="customer-tickets-modal"
                class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900" id="customer-tickets-title">
                            <?php esc_html_e('Customer Conversations', 'metamint-helpdesk'); ?>
                        </h3>
                        <button onclick="hideCustomerTickets()" class="text-gray-400 hover:text-gray-600" type="button">
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

            <?php
            add_action('admin_footer', function () use ($mmhd_terms, $mmhd_default_category, $mmhd_default_category_obj) {
                wp_add_inline_script('mmhd-dashboard-script', '
                    const mmhdTicketCategories = ' . wp_json_encode($mmhd_terms) . ';
                    const mmhdDefaultCategory = ' . wp_json_encode($mmhd_default_category) . ';
                    const mmhdDefaultCategoryObj = ' . wp_json_encode($mmhd_default_category_obj) . ';
                ', 'before');
            }, 100);
            ?>
        </div>
    </div>
</div>

<?php
add_action('admin_footer', function () {
    wp_add_inline_script('mmhd-dashboard-script', '
function showVariablesPopup() {
    const popup = document.getElementById(\'variables-popup\');
    const backdrop = document.getElementById(\'variables-backdrop\');
    popup.style.display = \'block\';
    backdrop.style.display = \'block\';
    updateVariablesDisplay();
}

function hideVariablesPopup() {
    const popup = document.getElementById(\'variables-popup\');
    const backdrop = document.getElementById(\'variables-backdrop\');
    popup.style.display = \'none\';
    backdrop.style.display = \'none\';
}

function updateVariablesDisplay() {
    if (!window.currentTicket) {
        const variablesList = document.getElementById(\'variables-list\');
        variablesList.innerHTML =
            \'<div style="padding: 1rem; color: #dc2626; text-align: center;">No ticket data available. Please reload the page.</div>\';
        return;
    }

    const ticket = window.currentTicket;
    const fullName = ticket.customer || \'\';
    const nameParts = fullName.split(\' \');
    const fname = nameParts[0] || \'\';
    const lname = nameParts.slice(1).join(\' \') || \'\';
    const email = ticket.email || \'\';
    const ticketId = ticket.id || \'\';
    const ticketStatus = ticket.status || \'\';
    const ticketTitle = ticket.title || \'\';

    const variables = [{
        var: \'fname\',
        label: \'First Name\',
        value: fname
    }, {
        var: \'lname\',
        label: \'Last Name\',
        value: lname
    }, {
        var: \'email\',
        label: \'Email\',
        value: email
    }, {
        var: \'ticket_id\',
        label: \'Ticket ID\',
        value: ticketId
    }, {
        var: \'ticket_status\',
        label: \'Ticket Status\',
        value: ticketStatus
    }, {
        var: \'ticket_title\',
        label: \'Ticket Title\',
        value: ticketTitle
    }];

    window.mmhdVariableValues = variables.reduce((acc, variable) => {
        acc[variable.var] = variable.value || \'\';
        return acc;
    }, {});

    const variablesList = document.getElementById(\'variables-list\');
    variablesList.innerHTML = \'\';

    variables.forEach(variable => {
        const displayValue = variable.value || \'(empty)\';
        const card = document.createElement(\'div\');
        card.innerHTML = `
            <div style="padding: 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background=\'#f3f4f6\'; this.style.borderColor=\'#d1d5db\';" onmouseout="this.style.background=\'#f9fafb\'; this.style.borderColor=\'#e5e7eb\';" onclick="insertVariable(\'${variable.var}\')">
                <div style="font-weight: 500; font-size: 0.875rem; color: #111827; margin-bottom: 0.25rem;">{{${variable.var}}}</div>
                <div style="font-size: 0.75rem; color: #6b7280;">Value: ${displayValue}</div>
            </div>
        `;
        variablesList.appendChild(card);
    });
}

function insertVariable(variableKey) {
    const valueMap = window.mmhdVariableValues || {};
    const resolvedValue = valueMap[variableKey] != null ? String(valueMap[variableKey]).trim() : \'\';
    const textToInsert = resolvedValue ? `${resolvedValue}\u00A0` : \'\u00A0\';

    const notesTab = document.getElementById(\'notes-tab\');
    let editorId = \'new-reply\';
    if (notesTab && !notesTab.classList.contains(\'hidden\')) {
        editorId = \'new-note\';
    }

    const editor = document.getElementById(editorId);
    if (!editor) return;

    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        if (editor.contains(range.commonAncestorContainer)) {
            const textNode = document.createTextNode(textToInsert);
            range.insertNode(textNode);
            range.setStartAfter(textNode);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            editor.appendChild(document.createTextNode(textToInsert));
        }
    } else {
        editor.appendChild(document.createTextNode(textToInsert));
    }

    hideVariablesPopup();
    editor.focus();
    setTimeout(() => placeCaretAtEnd(editor), 0);
}

function placeCaretAtEnd(element) {
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(element);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
}

document.addEventListener(\'DOMContentLoaded\', function() {
    const popoverTriggers = document.querySelectorAll(\'.mmhd-popover-trigger\');
    let activePopover = null;
    popoverTriggers.forEach(trigger => {
        trigger.addEventListener(\'mouseenter\', function() {
            const text = this.getAttribute(\'data-popover-text\');
            if (!text) return;
            if (activePopover) {
                activePopover.remove();
            }
            const popover = document.createElement(\'div\');
            popover.className = \'rb-popover\';
            popover.innerHTML =
                `<div class="arrow"></div><div class="popover-body">${text}</div>`;
            document.body.appendChild(popover);
            activePopover = popover;

            const rect = this.getBoundingClientRect();
            const top = rect.top + window.scrollY - popover.offsetHeight - 8;
            const left = rect.left + window.scrollX + (rect.width / 2) - (popover.offsetWidth /
                2);

            popover.style.top = top + \'px\';
            popover.style.left = left + \'px\';
            requestAnimationFrame(() => {
                popover.classList.add(\'show\');
            });
        });

        trigger.addEventListener(\'mouseleave\', function() {
            if (activePopover) {
                activePopover.classList.remove(\'show\');
                const popoverToRemove = activePopover;
                activePopover = null;
                setTimeout(() => {
                    if (popoverToRemove.parentNode) {
                        popoverToRemove.parentNode.removeChild(popoverToRemove);
                    }
                }, 150);
            }
        });
    });
});
    ', 'after');
});
?>