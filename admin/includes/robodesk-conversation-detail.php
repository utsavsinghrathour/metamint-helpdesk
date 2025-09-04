<div id="ticket-detail-page" class="page-content hidden">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-900" id="ticket-detail-title"><?php _e('Conversation Details', 'robodesk'); ?></h3>
                <button onclick="showPage('tickets')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php _e('Back to Conversations', 'robodesk'); ?>
                </button>

            </div>

            <!-- Comprehensive Ticket Info -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Customer Info -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-3"><?php _e('Customer Information', 'robodesk'); ?></h4>
                    <div id="customer-info" class="space-y-2">
                        <!-- Customer info will be populated by JavaScript -->
                    </div>
                    <div class="mt-4">
                        <button onclick="showCustomerTickets()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <?php _e('View All Customer Conversations', 'robodesk'); ?>
                        </button>
                    </div>
                </div>

                <!-- Ticket Status & Priority -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-3"><?php _e('Conversation Status', 'robodesk'); ?></h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('Status', 'robodesk'); ?></label>
                            <select onchange="updateTicketStatus(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-status-select">
                                <?php
                                $statuses = array('open', 'in-progress', 'closed');
                                foreach ($statuses as $status) {
                                    echo "<option value='" . esc_attr($status) . "'>" . esc_html(ucfirst(str_replace('-', ' ', $status))) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('Priority', 'robodesk'); ?></label>
                            <select onchange="updateTicketPriority(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-priority-select">
                                <?php
                                $priorities = get_terms([
                                    'taxonomy'   => 'robodesk_ticket_priority',
                                    'hide_empty' => false,
                                ]);

                                if (!is_wp_error($priorities)) {
                                    foreach ($priorities as $priority) {
                                        echo '<option value="' . esc_attr($priority->name) . '">' . esc_html($priority->name) . '</option>';
                                    }
                                }

                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php _e('Assignee', 'robodesk'); ?></label>
                            <select onchange="updateTicketAssignee(currentTicket.id, this.value)" class="styled-select block w-full border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md" id="ticket-assignee-select">
                                <?php
                                $default_agent = get_option('robodesk_default_agent');



                                // Get users by role with display_name only
                                $agents = get_users([
                                    'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                    'fields'   => ['display_name'],
                                ]);

                                // Convert to simple array of display names
                                $agent_names = wp_list_pluck($agents, 'display_name');
                                ?>

                                <?php foreach ($agent_names as $agent): ?>
                                    <option value="<?php echo esc_attr($agent); ?>" <?php selected($default_agent, $agent); ?>>
                                        <?php echo esc_html($agent); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <!-- Ticket Details -->
                <?php
                // In your PHP file (where you're already outputting the script)

                // Get ticket categories
                $terms = get_terms(array(
                    'taxonomy'   => 'robodesk_ticket_category',
                    'hide_empty' => false,
                ));

                // Get default category
                $default_category = get_option('robodesk_default_ticket_category', 'General');

                // Find the default category object from terms
                $default_category_obj = null;
                foreach ($terms as $term) {
                    if ($term->name === $default_category || $term->slug === $default_category) {
                        $default_category_obj = $term;
                        break;
                    }
                }
                ?>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-3"><?php _e('Conversation Details', 'robodesk'); ?></h4>
                    <div id="ticket-meta-info" class="space-y-2">
                        <!-- Ticket meta info will be populated by JavaScript -->
                    </div>
                </div>

                <script>
                    // Send ticket categories to JS
                    const robodeskTicketCategories = <?php echo wp_json_encode($terms); ?>;
                    const robodeskDefaultCategory = <?php echo wp_json_encode($default_category); ?>;
                    const robodeskDefaultCategoryObj = <?php echo wp_json_encode($default_category_obj); ?>;
                </script>
            </div>

            <!--------------------------- Customer Tickets Modal --------------------------->
            <div id="customer-tickets-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900" id="customer-tickets-title"><?php _e('Customer Conversations', 'robodesk'); ?></h3>
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
                        <?php _e('Replies', 'robodesk'); ?> (<span id="replies-count">0</span>)
                    </button>
                    <button onclick="showTicketTab('notes')" class="ticket-tab py-2 px-1 border-b-2 font-medium text-sm" data-tab="notes">
                        <?php _e('Internal Notes', 'robodesk'); ?> (<span id="notes-count">0</span>)
                    </button>
                </nav>
            </div>

            <!--------------------------------- Tab Content ---------------------->
            <div class="mt-6">
                <div id="replies-tab" class="ticket-tab-content">
                    <div id="replies-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                        <!-- Replies will be populated by JavaScript -->
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php _e('Add Reply', 'robodesk'); ?></label>
                        <div class="editor-toolbar mb-2">
                            <button type="button" onclick="formatText('bold', 'new-reply')" class="editor-btn"><i class="fas fa-bold"></i></button>
                            <button type="button" onclick="formatText('italic', 'new-reply')" class="editor-btn"><i class="fas fa-italic"></i></button>
                            <button type="button" onclick="formatText('underline', 'new-reply')" class="editor-btn"><i class="fas fa-underline"></i></button>
                            <button type="button" onclick="formatText('insertUnorderedList', 'new-reply')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                            <button type="button" onclick="formatText('insertOrderedList', 'new-reply')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                        </div>
                        <div id="new-reply" class="editor-content placeholder" contenteditable="true"
                            style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                            data-placeholder="<?php _e('Type your reply here...', 'robodesk'); ?>" aria-label="Reply input"></div>
                        <button id="send-reply-btn" onclick="addReply()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <?php _e('Send Reply', 'robodesk'); ?>
                        </button>

                        <button onclick="closeTicket()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 float-right"><?php _e('Close Conversation', 'robodesk'); ?></button>
                    </div>
                </div>

                <div id="notes-tab" class="ticket-tab-content hidden">
                    <div id="notes-list" class="space-y-4 max-h-96 overflow-y-auto mb-4">
                        <!-- Notes will be populated by JavaScript -->
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php _e('Add Internal Note', 'robodesk'); ?></label>
                        <div class="editor-toolbar mb-2">
                            <button type="button" onclick="formatText('bold', 'new-note')" class="editor-btn"><i class="fas fa-bold"></i></button>
                            <button type="button" onclick="formatText('italic', 'new-note')" class="editor-btn"><i class="fas fa-italic"></i></button>
                            <button type="button" onclick="formatText('underline', 'new-note')" class="editor-btn"><i class="fas fa-underline"></i></button>
                            <button type="button" onclick="formatText('insertUnorderedList', 'new-note')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                            <button type="button" onclick="formatText('insertOrderedList', 'new-note')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                        </div>
                        <div id="new-note" class="editor-content placeholder" contenteditable="true"
                            style="min-height: 100px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                            data-placeholder="<?php _e('Type your internal note here...', 'robodesk'); ?>"></div>
                        <button onclick="addNote()" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                            <?php _e('Add Note', 'robodesk'); ?>
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>