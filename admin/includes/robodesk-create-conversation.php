<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div id="new-ticket-page" class="page-content hidden">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6"><?php esc_html_e('Create New Conversation', 'robodesk'); ?></h3>

            <form id="robodesk-new-ticket-form" class="space-y-6">
                <input type="hidden" name="nonce"
                    value="<?php echo esc_attr(wp_create_nonce('robodesk_create_ticket_action')); ?>">
                <input type="hidden" name="robodesk_create_ticket_nonce"
                    value="<?php echo esc_attr(wp_create_nonce('robodesk_create_ticket_action')); ?>">
                <input type="hidden" name="ticket_source" value="Form"> <!-- Added for robodesk_ticket_origin -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Customer Name', 'robodesk'); ?>
                            *</label>
                        <input type="text" id="customer-name" name="customer_name" required autocomplete="off"
                            class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div id="customer-name-suggestions"
                            class="bg-white border border-gray-300 mt-1 hidden relative z-10 w-full max-h-40 overflow-auto rounded-md shadow">
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Customer Email', 'robodesk'); ?>
                            *</label>
                        <input type="email" id="customer-email" name="customer_email" required autocomplete="off"
                            class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div id="customer-email-suggestions"
                            class="bg-white border border-gray-300 mt-1 hidden absolute z-10 w-full max-h-40 overflow-auto rounded-md shadow">
                        </div>
                    </div>

                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Title', 'robodesk'); ?>
                        *</label>
                    <input type="text" id="ticket-title" name="ticket_title" required
                        class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Priority', 'robodesk'); ?>
                            *</label>
                        <select id="ticket-priority" name="ticket_priority" required
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value=""><?php esc_html_e('Select Priority', 'robodesk'); ?></option>
                            <?php
                            $robodesk_ticket_priorities = get_terms([
                                'taxonomy' => 'robodesk_ticket_priority',
                                'hide_empty' => false,
                            ]);
                            foreach ($robodesk_ticket_priorities as $robodesk_ticket_priority) {
                                echo '<option value="' . esc_attr($robodesk_ticket_priority->name) . '">' . esc_html($robodesk_ticket_priority->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Department', 'robodesk'); ?>
                        </label>
                        <select id="ticket-category" name="ticket_category"
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value=""><?php esc_html_e('Select Department', 'robodesk'); ?></option>
                            <?php
                            $robodesk_default_category_id = get_option('robodesk_default_ticket_category', '');
                            if (!empty($robodesk_default_category_id)) {
                                $robodesk_default_category = get_term($robodesk_default_category_id, 'robodesk_ticket_category');
                                if ($robodesk_default_category && !is_wp_error($robodesk_default_category)) {
                                    echo '<option value="' . esc_attr($robodesk_default_category->term_id) . '" selected>' . esc_html($robodesk_default_category->name) . '</option>';
                                }
                            }
                            ?>
                            <?php
                            $robodesk_categories = get_terms([
                                'taxonomy' => 'robodesk_ticket_category',
                                'hide_empty' => false,
                            ]);

                            foreach ($robodesk_categories as $robodesk_category) {
                                echo '<option value="' . esc_attr($robodesk_category->term_id) . '">' . esc_html($robodesk_category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Assign to', 'robodesk'); ?>
                            *</label>
                        <select id="ticket-assignee" name="ticket_assignee" required
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <?php
                            $robodesk_default_agent = get_option('robodesk_default_agent');
                            ?>
                            <option value="" <?php echo empty($robodesk_default_agent) ? 'selected' : ''; ?>>
                                <?php esc_html_e('Select Agent', 'robodesk'); ?>
                            </option>
                            <?php
                            $robodesk_agents = get_users([
                                'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                'fields' => ['ID', 'display_name'],
                            ]);
                            ?>
                            <?php foreach ($robodesk_agents as $robodesk_agent): ?>
                                <?php
                                $robodesk_agent_value = (int) $robodesk_agent->ID;
                                $robodesk_is_selected = (string) $robodesk_default_agent === (string) $robodesk_agent_value ||
                                    (string) $robodesk_default_agent === (string) $robodesk_agent->display_name;
                                ?>
                                <option value="<?php echo esc_attr($robodesk_agent_value); ?>" <?php selected($robodesk_is_selected, true); ?>>
                                    <?php echo esc_html($robodesk_agent->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Description', 'robodesk'); ?>
                        *</label>
                    <div class="editor-toolbar mb-2">
                        <button type="button" onclick="formatText('bold', 'ticket-description')" class="editor-btn"><i
                                class="fas fa-bold"></i></button>
                        <button type="button" onclick="formatText('italic', 'ticket-description')" class="editor-btn"><i
                                class="fas fa-italic"></i></button>
                        <button type="button" onclick="formatText('underline', 'ticket-description')"
                            class="editor-btn"><i class="fas fa-underline"></i></button>
                        <button type="button" onclick="formatText('insertUnorderedList', 'ticket-description')"
                            class="editor-btn"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="formatText('insertOrderedList', 'ticket-description')"
                            class="editor-btn"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <div id="ticket-description" class="editor-content placeholder" contenteditable="true"
                        style="min-height: 150px; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem;"
                        data-placeholder="Describe the issue or request..." aria-label="Ticket description input"></div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="showPage('dashboard')"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <?php esc_html_e('Cancel', 'robodesk'); ?>
                    </button>
                    <button type="submit" id="robodesk-create-ticket"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <?php esc_html_e('Create Conversation', 'robodesk'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>