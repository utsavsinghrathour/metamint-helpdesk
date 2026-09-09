<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<?php
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$mmhd_current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
$mmhd_nt_hidden = ($mmhd_current_tab === 'new-ticket') ? '' : ' hidden';
?>
<div id="new-ticket-page" class="page-content<?php echo esc_attr($mmhd_nt_hidden); ?>">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6"><?php esc_html_e('Create New Conversation', 'metamint-helpdesk'); ?>
            </h3>

            <form id="mmhd-new-ticket-form" class="space-y-6">
                <input type="hidden" name="nonce"
                    value="<?php echo esc_attr(wp_create_nonce('mmhd_create_ticket_action')); ?>">
                <input type="hidden" name="mmhd_create_ticket_nonce"
                    value="<?php echo esc_attr(wp_create_nonce('mmhd_create_ticket_action')); ?>">
                <input type="hidden" name="ticket_source" value="Form"> <!-- Added for mmhd_ticket_origin -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rb-form-group rb-relative">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Customer Name', 'metamint-helpdesk'); ?>
                            *</label>
                        <input type="text" id="customer-name" name="customer_name" required autocomplete="off"
                            class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div id="customer-name-suggestions"
                            class="bg-white border border-gray-300 mt-1 hidden relative z-10 w-full max-h-40 overflow-auto rounded-md shadow">
                        </div>
                    </div>

                    <div class="rb-form-group rb-relative">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Customer Email', 'metamint-helpdesk'); ?>
                            *</label>
                        <input type="email" id="customer-email" name="customer_email" required autocomplete="off"
                            class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div id="customer-email-suggestions"
                            class="bg-white border border-gray-300 mt-1 hidden absolute z-10 w-full max-h-40 overflow-auto rounded-md shadow">
                        </div>
                    </div>

                </div>

                <div class="rb-form-group">
                    <label
                        class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Title', 'metamint-helpdesk'); ?>
                        *</label>
                    <input type="text" id="ticket-title" name="ticket_title" required
                        class="styled-input block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rb-form-group">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Priority', 'metamint-helpdesk'); ?>
                            *</label>
                        <select id="ticket-priority" name="ticket_priority" required
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value=""><?php esc_html_e('Select Priority', 'metamint-helpdesk'); ?></option>
                            <?php
                            $mmhd_ticket_priorities = get_terms([
                                'taxonomy' => 'mmhd_ticket_priority',
                                'hide_empty' => false,
                            ]);
                            foreach ($mmhd_ticket_priorities as $mmhd_ticket_priority) {
                                echo '<option value="' . esc_attr($mmhd_ticket_priority->name) . '">' . esc_html($mmhd_ticket_priority->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="rb-form-group">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Department', 'metamint-helpdesk'); ?>
                        </label>
                        <select id="ticket-category" name="ticket_category"
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value=""><?php esc_html_e('Select Department', 'metamint-helpdesk'); ?></option>
                            <?php
                            $mmhd_default_category_id = get_option('mmhd_default_ticket_category', '');
                            if (!empty($mmhd_default_category_id)) {
                                $mmhd_default_category = get_term($mmhd_default_category_id, 'mmhd_ticket_category');
                                if ($mmhd_default_category && !is_wp_error($mmhd_default_category)) {
                                    echo '<option value="' . esc_attr($mmhd_default_category->term_id) . '" selected>' . esc_html($mmhd_default_category->name) . '</option>';
                                }
                            }
                            ?>
                            <?php
                            $mmhd_categories = get_terms([
                                'taxonomy' => 'mmhd_ticket_category',
                                'hide_empty' => false,
                            ]);

                            foreach ($mmhd_categories as $mmhd_category) {
                                echo '<option value="' . esc_attr($mmhd_category->term_id) . '">' . esc_html($mmhd_category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="rb-form-group">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Assign to', 'metamint-helpdesk'); ?>
                            *</label>
                        <select id="ticket-assignee" name="ticket_assignee" required
                            class="styled-select block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <?php
                            $mmhd_default_agent = get_option('mmhd_default_agent');
                            ?>
                            <option value="" <?php echo empty($mmhd_default_agent) ? 'selected' : ''; ?>>
                                <?php esc_html_e('Select Agent', 'metamint-helpdesk'); ?>
                            </option>
                            <?php
                            $mmhd_agents = get_users([
                                'role__in' => ['mmhd_support_staff', 'mmhd_support_manager', 'author', 'editor', 'administrator'],
                                'fields' => ['ID', 'display_name'],
                            ]);
                            ?>
                            <?php foreach ($mmhd_agents as $mmhd_agent): ?>
                            <?php
                                $mmhd_agent_value = (int) $mmhd_agent->ID;
                                $mmhd_is_selected = (string) $mmhd_default_agent === (string) $mmhd_agent_value ||
                                    (string) $mmhd_default_agent === (string) $mmhd_agent->display_name;
                                ?>
                            <option value="<?php echo esc_attr($mmhd_agent_value); ?>"
                                <?php selected($mmhd_is_selected, true); ?>>
                                <?php echo esc_html($mmhd_agent->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="rb-form-group">
                    <label
                        class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Description', 'metamint-helpdesk'); ?>
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
                        <?php esc_html_e('Cancel', 'metamint-helpdesk'); ?>
                    </button>
                    <button type="submit" id="mmhd-create-ticket"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <?php esc_html_e('Create Conversation', 'metamint-helpdesk'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>