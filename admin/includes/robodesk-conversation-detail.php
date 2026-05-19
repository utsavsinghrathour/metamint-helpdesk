<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$robodesk_statuses = array('open', 'in-progress', 'closed');
$robodesk_priorities = get_terms([
    'taxonomy' => 'robodesk_ticket_priority',
    'hide_empty' => false,
]);
$robodesk_default_agent = get_option('robodesk_default_agent');
$robodesk_agents = get_users([
    'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
    'fields' => ['ID', 'display_name'],
]);

$robodesk_terms = get_terms([
    'taxonomy' => 'robodesk_ticket_category',
    'hide_empty' => false,
]);
if (is_wp_error($robodesk_terms) || !is_array($robodesk_terms)) {
    $robodesk_terms = [];
}

$robodesk_default_category = get_option('robodesk_default_ticket_category', 'General');
$robodesk_default_category_obj = null;
foreach ($robodesk_terms as $term) {
    if ($term->name === $robodesk_default_category || $term->slug === $robodesk_default_category) {
        $robodesk_default_category_obj = $term;
        break;
    }
}
?>

<div id="ticket-detail-page" class="page-content hidden">
    <div class="bg-white shadow rounded-lg">
        <div class="converstaion-wrapper">


            <div class="grid grid-cols-1 lg:grid-cols-3 pb-6">
                <div class="lg:col-span-2 space-y-6 px-6">
                    <div class="mb-6 justify-between flex items-start py-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 ticket-title-80" id="ticket-detail-title">
                            <?php esc_html_e('Conversation Details', 'robodesk'); ?>
                        </h3>
                        <button type="button" onclick="showPage('tickets', true)"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?php esc_html_e('Back', 'robodesk'); ?>
                        </button>
                    </div>
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTicketTab('replies')"
                                class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="replies">
                                <?php esc_html_e('Replies', 'robodesk'); ?> (<span id="replies-count">0</span>)
                            </button>
                            <button onclick="showTicketTab('notes')"
                                class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="notes">
                                <?php esc_html_e('Internal Notes', 'robodesk'); ?> (<span id="notes-count">0</span>)
                            </button>
                        </nav>
                    </div>

                    <div class="mt-6">
                        <div id="replies-tab" class="ticket-tab-content">
                            <div id="replies-list" class="space-y-3 mb-4"
                                data-audio="<?php echo esc_attr(get_option('robodesk_notification_sound')); ?>">
                                <!-- Replies will be populated by JavaScript -->
                            </div>
                            <div>
                                <div class="add-label-wrapper d-flex justify-content-between align-items-center">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php esc_html_e('Add Reply', 'robodesk'); ?>
                                    </label>
                                    <button onclick="showVariablesPopup()"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium" type="button">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <?php esc_html_e('Insert Variables', 'robodesk'); ?>
                                    </button>
                                    <?php do_action('robodesk_before_reply_editor'); ?>
                                </div>
                                <div class="editor-toolbar mb-2">
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
                                    data-placeholder="<?php esc_html_e('Type your reply here...', 'robodesk'); ?>"
                                    aria-label="Reply input"></div>
                                <?php do_action('robodesk_after_reply_editor'); ?>
                                <button id="send-reply-btn" onclick="addReply()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <?php esc_html_e('Send Reply', 'robodesk'); ?>
                                </button>

                                <button onclick="closeTicket()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 float-right"
                                    type="button">
                                    <?php esc_html_e('Close Conversation', 'robodesk'); ?>
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
                                        <?php esc_html_e('Add Internal Note', 'robodesk'); ?>
                                    </label>
                                    <button onclick="showVariablesPopup()"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium" type="button">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <?php esc_html_e('Insert Variables', 'robodesk'); ?>
                                    </button>
                                </div>
                                <div class="editor-toolbar mb-2">
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
                                    data-placeholder="<?php esc_html_e('Type your internal note here...', 'robodesk'); ?>">
                                </div>
                                <button onclick="addNote()"
                                    class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                    type="button">
                                    <?php esc_html_e('Add Note', 'robodesk'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="variables-popup"
                            style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.1); z-index: 1000; width: 90%; max-width: 400px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600;">
                                    <?php esc_html_e('Template Variables', 'robodesk'); ?>
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
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                                <?php esc_html_e('Conversation Status', 'robodesk'); ?>
                            </h2>

                            <div class="space-y-4">
                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-status-select"><?php esc_html_e('Status', 'robodesk'); ?></label>
                                    <select onchange="updateTicketStatus(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        id="ticket-status-select">
                                        <?php foreach ($robodesk_statuses as $status): ?>
                                            <option value="<?php echo esc_attr($status); ?>">
                                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $status))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-priority-select"><?php esc_html_e('Priority', 'robodesk'); ?></label>
                                    <select onchange="updateTicketPriority(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        id="ticket-priority-select">
                                        <?php if (!is_wp_error($robodesk_priorities)): ?>
                                            <?php foreach ($robodesk_priorities as $robodesk_priority): ?>
                                                <option value="<?php echo esc_attr($robodesk_priority->name); ?>">
                                                    <?php echo esc_html($robodesk_priority->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-assignee-select"><?php esc_html_e('Assignee', 'robodesk'); ?></label>
                                    <select onchange="updateTicketAssignee(currentTicket.id, this.value)"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                        id="ticket-assignee-select">
                                        <?php foreach ($robodesk_agents as $robodesk_agent): ?>
                                            <?php
                                            $robodesk_agent_value = (int) $robodesk_agent->ID;
                                            $robodesk_is_selected = (string) $robodesk_default_agent === (string) $robodesk_agent_value || (string) $robodesk_default_agent === (string) $robodesk_agent->display_name;
                                            ?>
                                            <option value="<?php echo esc_attr($robodesk_agent_value); ?>" <?php selected($robodesk_is_selected, true); ?>>
                                                <?php echo esc_html($robodesk_agent->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="add-label-wrapper gap-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"
                                        for="ticket-category-select"><?php esc_html_e('Department', 'robodesk'); ?></label>
                                    <select id="ticket-category-select"
                                        class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                        <?php if (!empty($robodesk_terms)): ?>
                                            <?php foreach ($robodesk_terms as $term): ?>
                                                <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected((string) ($robodesk_default_category_obj ? $robodesk_default_category_obj->term_id : ''), (string) $term->term_id); ?>>
                                                    <?php echo esc_html($term->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value=""><?php esc_html_e('No departments found', 'robodesk'); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Conversation Details', 'robodesk'); ?>
                                </h2>
                            </div>

                            <div id="customer-info" class="space-y-2 mb-4">
                                <!-- Customer info will be populated by JavaScript -->
                            </div>

                            <div id="ticket-meta-info" class="space-y-2 pt-4 border-gray-100 text-sm">
                                <!-- Ticket meta info will be populated by JavaScript -->
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
                            <?php esc_html_e('Customer Conversations', 'robodesk'); ?>
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

            <script>
                const robodeskTicketCategories = <?php echo wp_json_encode($robodesk_terms); ?>;
                const robodeskDefaultCategory = <?php echo wp_json_encode($robodesk_default_category); ?>;
                const robodeskDefaultCategoryObj = <?php echo wp_json_encode($robodesk_default_category_obj); ?>;
            </script>
        </div>
    </div>
</div>

<script>
    function showVariablesPopup() {
        const popup = document.getElementById('variables-popup');
        const backdrop = document.getElementById('variables-backdrop');
        popup.style.display = 'block';
        backdrop.style.display = 'block';
        updateVariablesDisplay();
    }

    function hideVariablesPopup() {
        const popup = document.getElementById('variables-popup');
        const backdrop = document.getElementById('variables-backdrop');
        popup.style.display = 'none';
        backdrop.style.display = 'none';
    }

    function updateVariablesDisplay() {
        if (!window.currentTicket) {
            const variablesList = document.getElementById('variables-list');
            variablesList.innerHTML = '<div style="padding: 1rem; color: #dc2626; text-align: center;">No ticket data available. Please reload the page.</div>';
            return;
        }

        const ticket = window.currentTicket;
        const fullName = ticket.customer || '';
        const nameParts = fullName.split(' ');
        const fname = nameParts[0] || '';
        const lname = nameParts.slice(1).join(' ') || '';
        const email = ticket.email || '';
        const ticketId = ticket.id || '';
        const ticketStatus = ticket.status || '';
        const ticketTitle = ticket.title || '';

        const variables = [{
            var: 'fname',
            label: 'First Name',
            value: fname
        }, {
            var: 'lname',
            label: 'Last Name',
            value: lname
        }, {
            var: 'email',
            label: 'Email',
            value: email
        }, {
            var: 'ticket_id',
            label: 'Ticket ID',
            value: ticketId
        }, {
            var: 'ticket_status',
            label: 'Ticket Status',
            value: ticketStatus
        }, {
            var: 'ticket_title',
            label: 'Ticket Title',
            value: ticketTitle
        }];

        window.robodeskVariableValues = variables.reduce((acc, variable) => {
            acc[variable.var] = variable.value || '';
            return acc;
        }, {});

        const variablesList = document.getElementById('variables-list');
        variablesList.innerHTML = '';

        variables.forEach(variable => {
            const displayValue = variable.value || '(empty)';
            const card = document.createElement('div');
            card.innerHTML = `
            <div style="padding: 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db';" onmouseout="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb';" onclick="insertVariable('${variable.var}')">
                <div style="font-weight: 500; font-size: 0.875rem; color: #111827; margin-bottom: 0.25rem;">{{${variable.var}}}</div>
                <div style="font-size: 0.75rem; color: #6b7280;">Value: ${displayValue}</div>
            </div>
        `;
            variablesList.appendChild(card);
        });
    }

    function insertVariable(variableKey) {
        const valueMap = window.robodeskVariableValues || {};
        const resolvedValue = valueMap[variableKey] != null ? String(valueMap[variableKey]).trim() : '';
        const textToInsert = resolvedValue ? `${resolvedValue}\u00A0` : '\u00A0';

        const notesTab = document.getElementById('notes-tab');
        let editorId = 'new-reply';
        if (notesTab && !notesTab.classList.contains('hidden')) {
            editorId = 'new-note';
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

    document.addEventListener('DOMContentLoaded', function () {
        const popoverTriggers = document.querySelectorAll('.robodesk-popover-trigger');
        let activePopover = null;
        popoverTriggers.forEach(trigger => {
            trigger.addEventListener('mouseenter', function () {
                const text = this.getAttribute('data-popover-text');
                if (!text) return;
                if (activePopover) {
                    activePopover.remove();
                }
                const popover = document.createElement('div');
                popover.className = 'rb-popover';
                popover.innerHTML = `<div class="arrow"></div><div class="popover-body">${text}</div>`;
                document.body.appendChild(popover);
                activePopover = popover;

                const rect = this.getBoundingClientRect();
                const top = rect.top + window.scrollY - popover.offsetHeight - 8;
                const left = rect.left + window.scrollX + (rect.width / 2) - (popover.offsetWidth / 2);

                popover.style.top = top + 'px';
                popover.style.left = left + 'px';
                requestAnimationFrame(() => {
                    popover.classList.add('show');
                });
            });

            trigger.addEventListener('mouseleave', function () {
                if (activePopover) {
                    activePopover.classList.remove('show');
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
</script>