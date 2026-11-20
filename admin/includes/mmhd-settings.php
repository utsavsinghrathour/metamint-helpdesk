 <?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    ?>
 <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $mmhd_current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
    $mmhd_settings_hidden = ($mmhd_current_tab === 'settings') ? '' : ' hidden';
    ?>
 <div id="settings-page" class="page-content<?php echo esc_attr($mmhd_settings_hidden); ?>">
     <div class="bg-white shadow rounded-lg mmhd-settings-container-wrapper px-4 py-6 sm:px-6">
         <div class="mmhd-settings-nav-wrapper">
             <nav class="mmhd-settings-nav-sec flex space-x-8">
                 <button onclick="showSettingsTab('basic')"
                     class="settings-tab py-2 px-1 font-medium text-sm" data-tab="basic">
                     <i class="fa-solid fa-sliders"></i>
                     <?php esc_html_e('Basic Settings', 'metamint-helpdesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('imap')" class="settings-tab py-2 px-1 font-medium text-sm"
                     data-tab="imap">
                     <i class="fa-regular fa-envelope-open"></i>
                     <?php esc_html_e('Mail Settings', 'metamint-helpdesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('templates')"
                     class="settings-tab py-2 px-1 font-medium text-sm" data-tab="templates">
                     <i class="fa-regular fa-envelope"></i>
                     <?php esc_html_e('Email Templates', 'metamint-helpdesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('fetch')"
                     class="settings-tab py-2 px-1 font-medium text-sm" data-tab="fetch">
                     <i class="fa-solid fa-ticket"></i>
                     <?php esc_html_e('Fetch Tickets', 'metamint-helpdesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('advanced')"
                     class="settings-tab py-2 px-1 font-medium text-sm" data-tab="advanced">
                     <i class="fa-solid fa-gear"></i>
                     <?php esc_html_e('Advanced', 'metamint-helpdesk'); ?>
                 </button>
                 <?php
                    // Allow addons to add their settings tabs
                    $mmhd_addon_tabs = apply_filters('mmhd_settings_tabs', array());
                    foreach ($mmhd_addon_tabs as $mmhd_tab_key => $mmhd_tab_label) {
                        echo '<button onclick="showSettingsTab(\'' . esc_js($mmhd_tab_key) . '\')" class="settings-tab py-2 px-1 font-medium text-sm" data-tab="' . esc_attr($mmhd_tab_key) . '">';
                        echo wp_kses_post($mmhd_tab_label);
                        echo '</button>';
                    }
                    ?>
             </nav>
         </div>
         <!------------------------------------------ overall setting page with all the tabs---------------------------------------->
         <div class="mmhd-settings-content-wrapper">
             <!------------------------------Basic Settings Tab --------------------------------->

             <div id="basic-tab" class="settings-tab-content">
                 <form id="mmhd-basic-settings-form" class="space-y-6">
                     <input type="hidden" name="mmhd_basic_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_basic_settings_action')); ?>">
                     <div class="mmhd-settings-grid grid grid-cols-1 gap-6">
                         <section class="border border-gray-200 rounded-xl bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Basic', 'metamint-helpdesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Core site details used across Metamint Helpdesk.', 'metamint-helpdesk'); ?></p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="site_name"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Site Name', 'metamint-helpdesk'); ?></label>
                                     <input type="text" name="site_name" id="site_name"
                                         value="<?php echo esc_attr(get_option('mmhd_site_name', 'Metamint Helpdesk')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                 </div>
                                 <div>
                                     <label for="admin_email"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Admin Email', 'metamint-helpdesk'); ?></label>
                                     <input type="email" name="admin_email" id="admin_email"
                                         value="<?php echo esc_attr(get_option('mmhd_admin_email', 'admin@example.com')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                 </div>
                                 <div class="mmhd-enable-chat">
                                     <div class="flex items-center">
                                         <input type="checkbox" name="enable_chat" id="enable_chat" value="1" <?php checked(get_option('mmhd_enable_chat', '1'), '1'); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                         <label for="enable_chat" class="ml-2 block text-sm text-gray-900 font-medium"><?php esc_html_e('Enable Chat', 'metamint-helpdesk'); ?></label>
                                     </div>
                                     <p class="text-sm text-gray-500 ml-7"><?php esc_html_e('When disabled, the Conversations tab, "Start a conversation" buttons, and the AI bot chat are hidden from the frontend. FAQ and other tabs remain visible.', 'metamint-helpdesk'); ?></p>

                                 </div>
                                 <div class="mmhd-show-credits">
                                     <div class="flex items-center">
                                         <input type="checkbox" name="show_credits" id="show_credits" value="1" <?php checked(get_option('mmhd_show_credits', '0'), '1'); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                         <label for="show_credits" class="ml-2 block text-sm text-gray-900 font-medium"><?php esc_html_e('Show "Powered by Metamint Helpdesk" credit', 'metamint-helpdesk'); ?></label>
                                     </div>
                                     <p class="text-sm text-gray-500 ml-7"><?php esc_html_e('Off by default. When enabled, a "Powered by Metamint Helpdesk" credit is displayed in the chat widget footer.', 'metamint-helpdesk'); ?></p>

                                 </div>
                             </div>
                         </section>

                         <section class="border border-gray-200 rounded-xl bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Logos', 'metamint-helpdesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Upload the visuals that define the widget and brand colors.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="popover_top_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Top Icon/Image', 'metamint-helpdesk'); ?></label>
                                     <input type="file" accept="image/*" name="popover_top_icon" id="popover_top_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-top-icon-preview"
                                             src="<?php echo esc_url(get_option('mmhd_popover_top_icon', '')); ?>"
                                             alt="Pop over Top Icon Preview"
                                             class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-top-icon-preview', 'mmhd_popover_top_icon', 'popover_top_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_popover_top_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'metamint-helpdesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_popover_top_icon"
                                         id="mmhd_popover_top_icon"
                                         value="<?php echo esc_attr(get_option('mmhd_popover_top_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="theme_color"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Theme Color', 'metamint-helpdesk'); ?></label>
                                     <div class="mt-1 flex items-center gap-3">
                                         <input type="color" name="theme_color" id="theme_color"
                                             value="<?php echo esc_attr(get_option('mmhd_theme_color', '#0f7173')); ?>"
                                             class="styled-input block w-10 h-10 border-gray-300 rounded-md shadow-sm">
                                         <div id="theme-color-preview"
                                             class="hidden w-16 h-8 rounded-md border border-gray-300"
                                             style="background-color: <?php echo esc_attr(get_option('mmhd_theme_color', '#0f7173')); ?>;">
                                         </div>
                                     </div>
                                 </div>
                                 <div>
                                     <label for="popover_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Icon/Image', 'metamint-helpdesk'); ?></label>
                                     <input type="file" accept="image/*" name="popover_icon" id="popover_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-icon-preview"
                                             src="<?php echo esc_url(get_option('mmhd_popover_icon', '')); ?>"
                                             alt="Pop over Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-icon-preview', 'mmhd_popover_icon', 'popover_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_popover_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'metamint-helpdesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_popover_icon" id="mmhd_popover_icon"
                                         value="<?php echo esc_attr(get_option('mmhd_popover_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Logo', 'metamint-helpdesk'); ?></label>
                                     <input type="file" accept="image/*" name="logo" id="logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="logo-preview"
                                             src="<?php echo esc_url(get_option('mmhd_logo', '')); ?>"
                                             alt="Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('logo-preview', 'mmhd_logo', 'logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'metamint-helpdesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_logo" id="mmhd_logo"
                                         value="<?php echo esc_attr(get_option('mmhd_logo', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="wide_logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Wide Logo', 'metamint-helpdesk'); ?></label>
                                     <input type="file" accept="image/*" name="wide_logo" id="wide_logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="wide-logo-preview"
                                             src="<?php echo esc_url(get_option('mmhd_wide_logo', '')); ?>"
                                             alt="Wide Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('wide-logo-preview', 'mmhd_wide_logo', 'wide_logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_wide_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'metamint-helpdesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_wide_logo" id="mmhd_wide_logo"
                                         value="<?php echo esc_attr(get_option('mmhd_wide_logo', '')); ?>">
                                 </div>
                             </div>
                         </section>

                         <section class="border border-gray-200 rounded-xl bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Defaults', 'metamint-helpdesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the default category and notification audio.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="mmhd_default_ticket_category"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Default Ticket Categories', 'metamint-helpdesk'); ?></label>
                                     <select name="mmhd_default_ticket_category"
                                         id="mmhd_default_ticket_category"
                                         class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                         <option value=""><?php esc_html_e('Select a category', 'metamint-helpdesk'); ?></option>
                                         <?php
                                            $mmhd_selected_category = get_option('mmhd_default_ticket_category', '');
                                            $mmhd_categories = get_terms(array(
                                                'taxonomy'   => 'mmhd_ticket_category',
                                                'hide_empty' => false,
                                            ));
                                            foreach ($mmhd_categories as $mmhd_category) {
                                                echo '<option value="' . esc_attr($mmhd_category->term_id) . '" ' . selected($mmhd_selected_category, $mmhd_category->term_id, false) . '>' . esc_html($mmhd_category->name) . '</option>';
                                            }
                                            ?>
                                     </select>
                                 </div>
                                 <div>
                                     <label for="notification_sound"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Notification Sound', 'metamint-helpdesk'); ?></label>
                                     <select name="mmhd_notification_sound" id="notification_sound"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                         <?php
                                            $mmhd_audio_files = [
                                                'Sample 1' => plugin_dir_url(__FILE__) . 'audio/1.mp3',
                                                'Sample 2' => plugin_dir_url(__FILE__) . 'audio/2.mp3',
                                                'Sample 3' => plugin_dir_url(__FILE__) . 'audio/3.mp3',
                                                'Sample 4' => plugin_dir_url(__FILE__) . 'audio/4.mp3',
                                                'Sample 5' => plugin_dir_url(__FILE__) . 'audio/5.mp3',
                                                'Sample 6' => plugin_dir_url(__FILE__) . 'audio/6.mp3',
                                                'Sample 7' => plugin_dir_url(__FILE__) . 'audio/7.mp3',
                                                'Sample 8' => plugin_dir_url(__FILE__) . 'audio/8.mp3',
                                                'Sample 9' => plugin_dir_url(__FILE__) . 'audio/9.mp3',
                                                'Sample 10' => plugin_dir_url(__FILE__) . 'audio/10.mp3',
                                                'Sample 11' => plugin_dir_url(__FILE__) . 'audio/ding.mp3',
                                                'Sample 12' => plugin_dir_url(__FILE__) . 'audio/chime.mp3',
                                                'Sample 13' => plugin_dir_url(__FILE__) . 'audio/notify.mp3',
                                                'Sample 14' => plugin_dir_url(__FILE__) . 'audio/alert.mp3',
                                            ];
                                            $mmhd_selected_sound = get_option('mmhd_notification_sound', '');
                                            foreach ($mmhd_audio_files as $mmhd_audio_label => $mmhd_audio_url) {
                                                echo '<option value="' . esc_url($mmhd_audio_url) . '" ' . selected($mmhd_selected_sound, $mmhd_audio_url, false) . '>' . esc_html(ucfirst($mmhd_audio_label)) . '</option>';
                                            }
                                            ?>
                                     </select>
                                     <div class="mt-2 flex items-center gap-2">
                                         <audio id="notification-sound-preview" controls
                                             class="<?php echo $mmhd_selected_sound ? '' : 'hidden'; ?>">
                                             <source src="<?php echo esc_url($mmhd_selected_sound); ?>"
                                                 type="audio/mpeg">
                                             <?php esc_html_e('Your browser does not support the audio element.', 'metamint-helpdesk'); ?>
                                         </audio>
                                     </div>
                                     <input type="hidden" name="mmhd_notification_sound"
                                         id="mmhd_notification_sound"
                                         value="<?php echo esc_attr($mmhd_selected_sound); ?>">
                                 </div>
                             </div>
                         </section>

                         <section class="border border-gray-200 rounded-xl bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Messages', 'metamint-helpdesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the chat title and message copy shown across the widget.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 gap-6">
                                 <div>
                                     <label for="chat_heading"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Chat Heading', 'metamint-helpdesk'); ?></label>
                                     <input type="text" name="chat_heading" id="chat_heading"
                                         value="<?php echo esc_attr(get_option('mmhd_chat_heading', 'Have Questions?')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                 </div>
                                 <div>
                                     <label for="welcome_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Welcome Message for the chat Bot', 'metamint-helpdesk'); ?></label>
                                     <textarea name="welcome_message" id="welcome_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm"><?php echo esc_textarea(get_option('mmhd_welcome_message', 'Have any questions or need a quote? Leave us a message.')); ?></textarea>
                                 </div>
                                 <div>
                                     <label for="ticket_intro_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Ticket Intro Message', 'metamint-helpdesk'); ?></label>
                                     <textarea name="ticket_intro_message" id="ticket_intro_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm"><?php echo esc_textarea(get_option('mmhd_ticket_intro_message', 'If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!')); ?></textarea>
                                 </div>
                             </div>
                         </section>
                     </div>
                     <!-- <hr class="my-6 border-gray-200"> -->
                     <button type="button" id="save-basic-settings"
                         class="mt-6 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                         <?php esc_html_e('Save Settings', 'metamint-helpdesk'); ?>
                     </button>
                 </form>
             </div>

             <!----------------------- IMAP Settings load saved settings ------------------->
             <?php
                // Load saved settings (merge defaults so a partial/empty saved
                // value never produces undefined-index warnings)
                $mmhd_imap_defaults = array(
                    'hostname' => 'email.example.com',
                    'port'     => 993,
                    'username' => '',
                    'password' => '',
                    'auth'     => true,
                    'inbox_name' => '',
                );

                $mmhd_imap_settings = wp_parse_args((array) get_option('mmhd_imap_settings', array()), $mmhd_imap_defaults);

                $mmhd_imap_hostname   = $mmhd_imap_settings['hostname'];
                $mmhd_imap_port       = $mmhd_imap_settings['port'];
                $mmhd_imap_username   = $mmhd_imap_settings['username'];
                $mmhd_imap_password   = $mmhd_imap_settings['password'];
                $mmhd_imap_auth       = $mmhd_imap_settings['auth'];
                $mmhd_imap_inbox_name = $mmhd_imap_settings['inbox_name'];

                $mmhd_smtp_settings = get_option('mmhd_smtp_settings', array(
                    'enabled' => false,
                    'host' => '',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_email' => get_option('admin_email', ''),
                    'from_name' => get_bloginfo('name'),
                ));

                $mmhd_smtp_enabled = !empty($mmhd_smtp_settings['enabled']);
                $mmhd_smtp_host = isset($mmhd_smtp_settings['host']) ? $mmhd_smtp_settings['host'] : '';
                $mmhd_smtp_port = isset($mmhd_smtp_settings['port']) ? (int) $mmhd_smtp_settings['port'] : 587;
                $mmhd_smtp_username = isset($mmhd_smtp_settings['username']) ? $mmhd_smtp_settings['username'] : '';
                $mmhd_smtp_password = isset($mmhd_smtp_settings['password']) ? $mmhd_smtp_settings['password'] : '';
                $mmhd_smtp_encryption = isset($mmhd_smtp_settings['encryption']) ? $mmhd_smtp_settings['encryption'] : 'tls';
                $mmhd_smtp_from_email = isset($mmhd_smtp_settings['from_email']) ? $mmhd_smtp_settings['from_email'] : get_option('admin_email', '');
                $mmhd_smtp_from_name = isset($mmhd_smtp_settings['from_name']) ? $mmhd_smtp_settings['from_name'] : get_bloginfo('name');

                ?>

             <!--------------------------- IMAP Settings Tab ---------------------------------------->
             <div id="imap-tab" class="settings-tab-content hidden">
                 <h3 class="mmhd-adv__title mb-2"><?php esc_html_e('IMAP Settings', 'metamint-helpdesk'); ?>
                 </h3>
                 <form method="post" action="" id="mmhd-imap-form" class="space-y-6">
                     <?php wp_nonce_field('mmhd_imap_settings_action', 'mmhd_imap_settings_nonce'); ?>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                             <label for="mmhd_imap_hostname"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('IMAP Host', 'metamint-helpdesk'); ?></label>
                             <input type="text" name="mmhd_imap_hostname" id="mmhd_imap_hostname"
                                 value="<?php echo esc_attr($mmhd_imap_hostname); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_imap_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Port', 'metamint-helpdesk'); ?></label>
                             <input type="number" name="mmhd_imap_port" id="mmhd_imap_port"
                                 value="<?php echo esc_attr($mmhd_imap_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_imap_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Username', 'metamint-helpdesk'); ?></label>
                             <input type="email" name="mmhd_imap_username" id="mmhd_imap_username"
                                 value="<?php echo esc_attr($mmhd_imap_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_imap_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Password', 'metamint-helpdesk'); ?></label>
                             <input type="password" name="mmhd_imap_password" id="mmhd_imap_password"
                                 value="<?php echo esc_attr($mmhd_imap_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_imap_inbox_name"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Inbox Name', 'metamint-helpdesk'); ?></label>
                             <input type="text" name="mmhd_imap_inbox_name" id="mmhd_imap_inbox_name"
                                 value="<?php echo esc_attr($mmhd_imap_inbox_name); ?>"
                                 placeholder="e.g. Sales, Support, General"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                             <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('A name to identify this inbox in the dashboard.', 'metamint-helpdesk'); ?></p>
                         </div>
                         <div>
                             <label for="mmhd_imap_auth"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'metamint-helpdesk'); ?></label>
                             <select name="mmhd_imap_auth" id="mmhd_imap_auth"
                                 class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                 <option value="1" <?php selected($mmhd_imap_auth, true); ?>>
                                     <?php esc_html_e('SSL', 'metamint-helpdesk'); ?></option>
                                 <option value="0" <?php selected($mmhd_imap_auth, false); ?>>
                                     <?php esc_html_e('TLS', 'metamint-helpdesk'); ?></option>
                             </select>
                         </div>
                     </div>
                     <div class="mmhd-imap-buttons-mail-settings mt-1 flex gap-4">
                         <button type="button" id="mmhd-imap-test" onclick="testConnection()"
                             class="bg-teal-600 text-white px-4 py-2 rounded-md">
                             <?php esc_html_e('Test Connection', 'metamint-helpdesk'); ?>
                         </button>


                         <button type="button" id="save-imap-settings"
                             class="border border-teal-600 text-teal-600 px-4 py-2 rounded-md hover:bg-teal-600 hover:text-white">
                             <?php esc_html_e('Save Settings', 'metamint-helpdesk'); ?>
                         </button>

                     </div>

                 </form>

                 <!-- <hr class="my-8 border-gray-200"> -->

                 <h3 class="mmhd-adv__title text-gray-900 mb-2"><?php esc_html_e('SMTP Settings', 'metamint-helpdesk'); ?>
                 </h3>
                 <form method="post" action="" id="mmhd-smtp-form" class="space-y-6">
                     <?php wp_nonce_field('mmhd_smtp_settings_action', 'mmhd_smtp_settings_nonce'); ?>

                     <div class="flex items-center">
                         <input id="mmhd_smtp_enabled" type="checkbox"
                             <?php checked($mmhd_smtp_enabled, true); ?>
                             class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                         <label for="mmhd_smtp_enabled" class="ml-2 block text-sm text-gray-900">
                             <?php esc_html_e('Enable SMTP for outgoing emails', 'metamint-helpdesk'); ?>
                         </label>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                             <label for="mmhd_smtp_host"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Host', 'metamint-helpdesk'); ?></label>
                             <input type="text" name="mmhd_smtp_host" id="mmhd_smtp_host"
                                 value="<?php echo esc_attr($mmhd_smtp_host); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_smtp_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Port', 'metamint-helpdesk'); ?></label>
                             <input type="number" name="mmhd_smtp_port" id="mmhd_smtp_port"
                                 value="<?php echo esc_attr($mmhd_smtp_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_smtp_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Username', 'metamint-helpdesk'); ?></label>
                             <input type="text" name="mmhd_smtp_username" id="mmhd_smtp_username"
                                 value="<?php echo esc_attr($mmhd_smtp_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm ">
                         </div>
                         <div>
                             <label for="mmhd_smtp_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Password', 'metamint-helpdesk'); ?></label>
                             <input type="password" name="mmhd_smtp_password" id="mmhd_smtp_password"
                                 value="<?php echo esc_attr($mmhd_smtp_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div>
                             <label for="mmhd_smtp_encryption"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'metamint-helpdesk'); ?></label>
                             <select name="mmhd_smtp_encryption" id="mmhd_smtp_encryption"
                                 class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                 <option value="none" <?php selected($mmhd_smtp_encryption, 'none'); ?>>
                                     <?php esc_html_e('None', 'metamint-helpdesk'); ?></option>
                                 <option value="ssl" <?php selected($mmhd_smtp_encryption, 'ssl'); ?>>
                                     <?php esc_html_e('SSL', 'metamint-helpdesk'); ?></option>
                                 <option value="tls" <?php selected($mmhd_smtp_encryption, 'tls'); ?>>
                                     <?php esc_html_e('TLS', 'metamint-helpdesk'); ?></option>
                             </select>
                         </div>
                         <div>
                             <label for="mmhd_smtp_from_email"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Email', 'metamint-helpdesk'); ?></label>
                             <input type="email" name="mmhd_smtp_from_email" id="mmhd_smtp_from_email"
                                 value="<?php echo esc_attr($mmhd_smtp_from_email); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                         <div class="md:col-span-2">
                             <label for="mmhd_smtp_from_name"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Name', 'metamint-helpdesk'); ?></label>
                             <input type="text" name="mmhd_smtp_from_name" id="mmhd_smtp_from_name"
                                 value="<?php echo esc_attr($mmhd_smtp_from_name); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                         </div>
                     </div>

                     <div class="mt-6 flex gap-4">
                         <button type="button" id="mmhd-smtp-test"
                             class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Test SMTP Connection', 'metamint-helpdesk'); ?>
                         </button>
                         <button type="button" id="save-smtp-settings"
                             class="border border-teal-600 text-teal-600 px-4 py-2 rounded-md hover:bg-teal-600 hover:text-white">
                             <?php esc_html_e('Save SMTP Settings', 'metamint-helpdesk'); ?>
                         </button>
                     </div>
                 </form>
             </div>


             <!------------------------------------------ Email Templates Tab --------------------------->

             <div id="templates-tab" class="settings-tab-content hidden">
                 <h3 class="mmhd-adv__title text-gray-900 mb-2"><?php esc_html_e('Email Templates', 'metamint-helpdesk'); ?>
                 </h3>

                 <div class="bg-[#ebedf5] rounded-lg p-6 text-gray-800 shadow-sm mmhd-email-variables-container">
                     <div class="flex justify-between items-center">
                         <h2 class="text-base font-semibold text-gray-900">
                             <?php esc_html_e('Customize the email templates with Variables', 'metamint-helpdesk'); ?></h2>
                         <button type="button" id="mmhd-email-variables-toggle"
                             class="text-sm text-teal-600 hover:underline focus:outline-none">
                             <?php esc_html_e('Show', 'metamint-helpdesk'); ?>
                         </button>
                     </div>

                     <div id="mmhd-email-variables-content"
                         class="mt-4 text-sm text-gray-700 leading-relaxed space-y-4 hidden">
                         <p><?php esc_html_e('You can use the following variables in your templates:', 'metamint-helpdesk'); ?>
                         </p>
                         <ul class="list-disc pl-5 space-y-1">
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_id}</code> –
                                 <?php esc_html_e('The ID of the ticket', 'metamint-helpdesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_author}</code> –
                                 <?php esc_html_e('The name of the ticket author', 'metamint-helpdesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_link}</code> –
                                 <?php esc_html_e('The link to the ticket', 'metamint-helpdesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_title}</code> –
                                 <?php esc_html_e('The title of the ticket', 'metamint-helpdesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_status}</code> –
                                 <?php esc_html_e('The status of the ticket', 'metamint-helpdesk'); ?></li>
                         </ul>
                     </div>
                 </div>


                 <form id="mmhd_email_template_save" class="space-y-6">
                     <input type="hidden" name="mmhd_email_template_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_email_template_action')); ?>">
                     <div class="space-y-6">
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('New Conversation Notification', 'metamint-helpdesk'); ?></label>
                             <div class="editor-toolbar">
                                 <button type="button" onclick="formatText('bold', 'new-ticket-template')"
                                     class="editor-btn"><i class="fas fa-bold"></i></button>
                                 <button type="button" onclick="formatText('italic', 'new-ticket-template')"
                                     class="editor-btn"><i class="fas fa-italic"></i></button>
                                 <button type="button" onclick="formatText('underline', 'new-ticket-template')"
                                     class="editor-btn"><i class="fas fa-underline"></i></button>
                                 <button type="button"
                                     onclick="formatText('insertUnorderedList', 'new-ticket-template')"
                                     class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                 <button type="button" onclick="formatText('insertOrderedList', 'new-ticket-template')"
                                     class="editor-btn"><i class="fas fa-list-ol"></i></button>
                             </div>
                             <div id="new-ticket-template" class="editor-content" contenteditable="true">
                                 <?php echo wp_kses_post(get_option('mmhd_email_template_new-ticket', 'Thank you for contacting us. We have received your ticket and will respond soon.')); ?>
                             </div>
                         </div>
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Closed', 'metamint-helpdesk'); ?></label>
                             <div class="editor-toolbar">
                                 <button type="button" onclick="formatText('bold', 'ticket-closed-template')"
                                     class="editor-btn"><i class="fas fa-bold"></i></button>
                                 <button type="button" onclick="formatText('italic', 'ticket-closed-template')"
                                     class="editor-btn"><i class="fas fa-italic"></i></button>
                                 <button type="button" onclick="formatText('underline', 'ticket-closed-template')"
                                     class="editor-btn"><i class="fas fa-underline"></i></button>
                                 <button type="button"
                                     onclick="formatText('insertUnorderedList', 'ticket-closed-template')"
                                     class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                 <button type="button"
                                     onclick="formatText('insertOrderedList', 'ticket-closed-template')"
                                     class="editor-btn"><i class="fas fa-list-ol"></i></button>
                             </div>
                             <div id="ticket-closed-template" class="editor-content" contenteditable="true">
                                 <?php echo wp_kses_post(get_option('mmhd_email_template_ticket-closed', 'Your ticket has been closed. Please let us know if you need further assistance.')); ?>
                             </div>
                         </div>
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Auto Reply', 'metamint-helpdesk'); ?></label>
                             <div class="editor-toolbar">
                                 <button type="button" onclick="formatText('bold', 'auto-reply-template')"
                                     class="editor-btn"><i class="fas fa-bold"></i></button>
                                 <button type="button" onclick="formatText('italic', 'auto-reply-template')"
                                     class="editor-btn"><i class="fas fa-italic"></i></button>
                                 <button type="button" onclick="formatText('underline', 'auto-reply-template')"
                                     class="editor-btn"><i class="fas fa-underline"></i></button>
                                 <button type="button"
                                     onclick="formatText('insertUnorderedList', 'auto-reply-template')"
                                     class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                 <button type="button" onclick="formatText('insertOrderedList', 'auto-reply-template')"
                                     class="editor-btn"><i class="fas fa-list-ol"></i></button>
                             </div>
                             <div id="auto-reply-template" class="editor-content" contenteditable="true">
                                 <?php echo wp_kses_post(get_option('mmhd_email_template_auto-reply', 'This is an automated response. We will get back to you within 24 hours.')); ?>
                             </div>
                         </div>
                     </div>
                     <!-- <hr class="my-6 border-gray-200"> -->
                     <button type="button" id="mmhd-save-templates"
                         class="mt-6 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                         <?php esc_html_e('Save Templates', 'metamint-helpdesk'); ?>
                     </button>
                 </form>
             </div>
             <!------------------------------ Fetch Tickets Tab --------------------------------------->
             <div id="fetch-tab" class="settings-tab-content hidden">
                 <h3 class="mmhd-adv__title text-gray-900 mb-2">
                     <?php esc_html_e('Fetch Conversations', 'metamint-helpdesk'); ?></h3>
                 <form id="mmhd-fetch-tickets-form" class="space-y-6">
                     <input type="hidden" name="mmhd_ticket_assignment_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_ticket_assignment_action')); ?>">
                     <div class="mmhd-fetch-tickets-settings">
                         <p class="text-sm text-gray-600 mb-4">
                             <?php esc_html_e('Use this feature to fetch new emails from your configured IMAP account and convert them into conversations and replies.', 'metamint-helpdesk'); ?>
                         </p>
                         <div class="border border-gray-300 bg-gray-50 p-4 rounded-lg mb-4">
                             <h4 class="font-medium text-gray-900 mb-2">
                                 <?php esc_html_e('Email Fetching Settings', 'metamint-helpdesk'); ?></h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Fetch Interval (minutes)', 'metamint-helpdesk'); ?></label>
                                     <input type="number" name="fetch_interval"
                                         value="<?php echo esc_attr(get_option('mmhd_fetch_interval', 15)); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                         min="1" max="1440" required>
                                 </div>
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Auto-assign to', 'metamint-helpdesk'); ?></label>
                                     <select name="default_agent"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                         <!-- default options -->
                                         <?php
                                            $mmhd_default_agent = get_option('mmhd_default_agent');
                                            ?>
                                         <option value="" <?php selected((string) $mmhd_default_agent, ''); ?>>
                                             <?php esc_html_e('Least loaded agent (auto)', 'metamint-helpdesk'); ?></option>

                                         <?php

                                            // Get users by role with IDs for stable option values.
                                            $mmhd_agents = get_users([
                                                'role__in' => ['mmhd_support_staff', 'mmhd_support_manager', 'author', 'editor', 'administrator'],
                                                'fields'   => ['ID', 'display_name', 'user_login'],
                                            ]);
                                            ?>

                                         <?php foreach ($mmhd_agents as $mmhd_agent): ?>
                                             <?php
                                                $mmhd_agent_id = (int) $mmhd_agent->ID;
                                                $mmhd_agent_name = !empty($mmhd_agent->display_name) ? $mmhd_agent->display_name : $mmhd_agent->user_login;
                                                $mmhd_is_selected = (string) $mmhd_default_agent === (string) $mmhd_agent_id ||
                                                    (string) $mmhd_default_agent === (string) $mmhd_agent_name;
                                                ?>
                                             <option value="<?php echo esc_attr($mmhd_agent_id); ?>"
                                                 <?php selected($mmhd_is_selected, true); ?>>
                                                 <?php echo esc_html($mmhd_agent_name); ?>
                                             </option>
                                         <?php endforeach; ?>

                                         <?php
                                            $mmhd_default_is_in_agents = false;
                                            foreach ($mmhd_agents as $mmhd_agent) {
                                                if ((string) $mmhd_default_agent === (string) $mmhd_agent->ID || (string) $mmhd_default_agent === (string) $mmhd_agent->display_name) {
                                                    $mmhd_default_is_in_agents = true;
                                                    break;
                                                }
                                            }
                                            ?>

                                         <?php if (!$mmhd_default_is_in_agents && !empty($mmhd_default_agent)): ?>
                                             <option value="<?php echo esc_attr($mmhd_default_agent); ?>" selected>
                                                 <?php echo esc_html($mmhd_default_agent); ?>
                                             </option>
                                         <?php endif; ?>


                                     </select>
                                     <p class="mt-1 text-xs text-gray-500">
                                         <?php esc_html_e('Leave this on "Least loaded agent (auto)" to assign fetched email tickets to the least-loaded agent.', 'metamint-helpdesk'); ?>
                                     </p>
                                 </div>
                             </div>
                             <div class="mt-4">
                                 <label class="flex items-start space-x-2 cursor-pointer">
                                     <input type="checkbox" name="do_not_send_email_on_import" value="1"
                                         <?php checked(get_option('mmhd_do_not_send_email_on_import', 0)); ?>
                                         class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                     <span class="text-sm font-medium text-gray-700">
                                         <?php esc_html_e('Do not send email on import', 'metamint-helpdesk'); ?>
                                     </span>
                                 </label>
                                 <p class="mt-1 text-xs text-gray-500">
                                     <?php esc_html_e('Suppresses all outgoing Metamint Helpdesk emails — new user welcome, ticket creation confirmation, reply and close notifications, password reset, and more. Enable this to import existing conversations without notifying customers, or to pause all Metamint Helpdesk email delivery.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                         </div>
                         <div class="flex space-x-4">
                             <button id="save-ticket-assignment-settings"
                                 class="bg-teal-600 text-white px-6 py-2 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                 <i class="fas fa-cog mr-2"></i>
                                 <?php esc_html_e('Save Auto-Fetch', 'metamint-helpdesk'); ?>
                             </button>
                         </div>
                     </div>
                 </form>

                 <div class="flex space-x-4 mt-6 mmhd-fetch-tickets-button-container">
                     <button onclick="fetchEmails()"
                         class="text-teal-600 border border-teal-600 px-6 py-2 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                         <i class="fas fa-sync-alt mr-2"></i>
                         <?php esc_html_e('Fetch Emails Now', 'metamint-helpdesk'); ?>
                     </button>
                 </div>
                 <div id="fetch-results" class="hidden bg-teal-50 border border-teal-600 rounded-lg p-4 mt-4">
                     <div class="flex items-center">
                         <i class="fas fa-info-circle text-teal-600 mr-2"></i>
                         <span id="fetch-message" class="text-teal-600"></span>
                     </div>
                 </div>

             </div>

             <!--------------------------- Advanced Settings Tab -------------------------------->


             <?php
                $mmhd_advanced_settings = wp_parse_args(get_option('mmhd_advanced_settings', []), [
                    'enable_auto_assign' => false,
                    'enable_department_auto_assign' => false,
                    'enable_notifications' => true,
                    'enable_customer_portal' => true,
                    'enable_signup_rate_limiting' => false,
                    'max_file_upload_size' => 10,
                    'reply_unseen_email_minutes' => 15,
                    'polling_interval' => 30,
                    'auto_close_chat_minutes' => 30,
                    'auto_close_ticket_days' => 0,
                ]);
                ?>
             <?php
                $mmhd_reply_unseen_minutes = isset($mmhd_advanced_settings['reply_unseen_email_minutes']) ? absint($mmhd_advanced_settings['reply_unseen_email_minutes']) : 15;
                $mmhd_reply_unseen_enabled = $mmhd_reply_unseen_minutes > 0;

                $mmhd_auto_close_chat_minutes = isset($mmhd_advanced_settings['auto_close_chat_minutes']) ? absint($mmhd_advanced_settings['auto_close_chat_minutes']) : 30;
                $mmhd_auto_close_chat_enabled = $mmhd_auto_close_chat_minutes > 0;

                $mmhd_auto_close_ticket_days = isset($mmhd_advanced_settings['auto_close_ticket_days']) ? absint($mmhd_advanced_settings['auto_close_ticket_days']) : 0;
                $mmhd_auto_close_ticket_enabled = $mmhd_auto_close_ticket_days > 0;
                $mmhd_auto_close_ticket_days_value = $mmhd_auto_close_ticket_enabled ? $mmhd_auto_close_ticket_days : 7;

                $mmhd_reply_minute_options = [5, 10, 15, 30, 60, 120];
                $mmhd_polling_options      = [15, 30, 45, 60, 120];
                $mmhd_blocked_ips          = get_option('mmhd_blocked_ips', array());
                ?>
             <div id="advanced-tab" class="settings-tab-content mmhd-adv hidden">
                 <h3 class="mmhd-adv__title"><?php esc_html_e('Advanced Settings', 'metamint-helpdesk'); ?></h3>

                 <form id="advanced-settings-form" class="mmhd-adv__form">
                     <input type="hidden" name="mmhd_advanced_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_advanced_settings_action')); ?>">

                     <!-- Notifications & assignment -->
                     <section class="mmhd-adv__card" aria-labelledby="mmhd-adv-h-notifications">
                         <h4 class="mmhd-adv__card-title" id="mmhd-adv-h-notifications">
                             <?php esc_html_e('Notifications & assignment', 'metamint-helpdesk'); ?>
                         </h4>

                         <div class="mmhd-adv__row">
                             <label for="enable-auto-assign" class="mmhd-adv__label">
                                 <?php esc_html_e('Enable auto-assignment of Conversations', 'metamint-helpdesk'); ?>
                             </label>
                             <input id="enable-auto-assign" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_advanced_settings['enable_auto_assign'], true); ?>>
                         </div>

                         <div class="mmhd-adv__row">
                             <div class="mmhd-adv__text">
                                 <label for="enable-department-auto-assign" class="mmhd-adv__label">
                                     <?php esc_html_e('Auto-assign unassigned Conversations by department', 'metamint-helpdesk'); ?>
                                 </label>
                                 <p class="mmhd-adv__hint">
                                     <?php esc_html_e('Unassigned conversations are routed to the least-busy support agent (Support Staff, Support Manager, Author, Editor, or Administrator) whose profile lists the same department. If nobody matches, the conversation stays unassigned.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <input id="enable-department-auto-assign" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_advanced_settings['enable_department_auto_assign'], true); ?>>
                         </div>

                         <div class="mmhd-adv__row">
                             <label for="enable-notifications" class="mmhd-adv__label">
                                 <?php esc_html_e('Enable email notifications', 'metamint-helpdesk'); ?>
                             </label>
                             <input id="enable-notifications" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_advanced_settings['enable_notifications'], true); ?>>
                         </div>

                         <div class="mmhd-adv__row">
                             <label for="enable-unseen-reply-email" class="mmhd-adv__label">
                                 <?php esc_html_e("Email the reply if the client hasn't seen it for", 'metamint-helpdesk'); ?>
                             </label>
                             <input id="enable-unseen-reply-email" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_reply_unseen_enabled, true); ?>>
                         </div>
                         <div id="reply-unseen-email-options" class="mmhd-adv__sub mmhd-adv__inline"
                             style="<?php echo $mmhd_reply_unseen_enabled ? '' : 'display:none;'; ?>">
                             <div class="mmhd-adv__select-wrap mmhd-adv__select-wrap--sm">
                                 <select name="reply_unseen_email_minutes" id="reply-unseen-email-minutes" class="mmhd-adv__select"
                                     aria-label="<?php esc_attr_e('Minutes before the reply is emailed', 'metamint-helpdesk'); ?>">
                                     <?php
                                        foreach ($mmhd_reply_minute_options as $mmhd_reply_minutes) {
                                            echo '<option value="' . esc_attr($mmhd_reply_minutes) . '" ' . selected($mmhd_reply_unseen_minutes, $mmhd_reply_minutes, false) . '>' . esc_html($mmhd_reply_minutes) . '</option>';
                                        }
                                        ?>
                                 </select>
                             </div>
                             <span class="mmhd-adv__suffix"><?php esc_html_e('minutes', 'metamint-helpdesk'); ?></span>
                         </div>
                     </section>

                     <!-- Dashboard & customer portal -->
                     <section class="mmhd-adv__card" aria-labelledby="mmhd-adv-h-dashboard">
                         <h4 class="mmhd-adv__card-title" id="mmhd-adv-h-dashboard">
                             <?php esc_html_e('Dashboard & customer portal', 'metamint-helpdesk'); ?>
                         </h4>

                         <div class="mmhd-adv__row">
                             <div class="mmhd-adv__text">
                                 <label for="polling-interval" class="mmhd-adv__label">
                                     <?php esc_html_e('Dashboard polling interval', 'metamint-helpdesk'); ?>
                                 </label>
                                 <p class="mmhd-adv__hint">
                                     <?php esc_html_e('How often the dashboard checks for new replies and notifications. Shorter intervals feel more real-time but increase server load.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <div class="mmhd-adv__select-wrap mmhd-adv__select-wrap--md">
                                 <select id="polling-interval" name="polling_interval" class="mmhd-adv__select">
                                     <?php
                                        foreach ($mmhd_polling_options as $mmhd_poll_sec) {
                                            echo '<option value="' . esc_attr($mmhd_poll_sec) . '" ' . selected($mmhd_advanced_settings['polling_interval'], $mmhd_poll_sec, false) . '>' . esc_html($mmhd_poll_sec) . ' ' . esc_html__('seconds', 'metamint-helpdesk') . '</option>';
                                        }
                                        ?>
                                 </select>
                             </div>
                         </div>

                         <div class="mmhd-adv__row">
                             <label for="enable-customer-portal" class="mmhd-adv__label">
                                 <?php esc_html_e('Enable customer portal', 'metamint-helpdesk'); ?>
                             </label>
                             <input id="enable-customer-portal" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_advanced_settings['enable_customer_portal'], true); ?>>
                         </div>

                         <div class="mmhd-adv__row">
                             <label for="max-file-upload-size" class="mmhd-adv__label">
                                 <?php esc_html_e('Maximum file upload size (MB)', 'metamint-helpdesk'); ?>
                             </label>
                             <div class="mmhd-adv__inline">
                                 <input type="number" name="max_file_upload_size" id="max-file-upload-size"
                                     value="<?php echo esc_attr($mmhd_advanced_settings['max_file_upload_size']); ?>"
                                     min="1" max="100" class="mmhd-adv__input mmhd-adv__input--sm">
                                 <span class="mmhd-adv__suffix"><?php esc_html_e('MB', 'metamint-helpdesk'); ?></span>
                             </div>
                         </div>
                     </section>

                     <!-- Security -->
                     <section class="mmhd-adv__card" aria-labelledby="mmhd-adv-h-security">
                         <h4 class="mmhd-adv__card-title" id="mmhd-adv-h-security">
                             <?php esc_html_e('Security', 'metamint-helpdesk'); ?>
                         </h4>

                         <div class="mmhd-adv__row">
                             <label for="enable-signup-rate-limiting" class="mmhd-adv__label">
                                 <?php esc_html_e('Enable signup rate limiting &amp; IP blocking', 'metamint-helpdesk'); ?>
                             </label>
                             <input type="checkbox" role="switch" id="enable-signup-rate-limiting" name="enable_signup_rate_limiting" value="1"
                                 class="mmhd-adv__switch"
                                 <?php checked($mmhd_advanced_settings['enable_signup_rate_limiting'] ?? false, true); ?>>
                         </div>
                         <div class="mmhd-adv__sub mmhd-adv__sub--plain">
                             <label for="blocked-ips" class="mmhd-adv__label">
                                 <?php esc_html_e('Blocked IPs', 'metamint-helpdesk'); ?>
                             </label>
                             <p class="mmhd-adv__hint">
                                 <?php esc_html_e('One IP per line. These IPs are blocked from signing up. Add or remove IPs manually.', 'metamint-helpdesk'); ?>
                             </p>
                             <textarea id="blocked-ips" name="blocked_ips" rows="5"
                                 class="mmhd-adv__textarea"><?php echo esc_textarea(implode("\n", $mmhd_blocked_ips)); ?></textarea>
                         </div>
                     </section>

                     <!-- Auto-close -->
                     <section class="mmhd-adv__card" aria-labelledby="mmhd-adv-h-autoclose">
                         <h4 class="mmhd-adv__card-title" id="mmhd-adv-h-autoclose">
                             <?php esc_html_e('Auto-close', 'metamint-helpdesk'); ?>
                         </h4>

                         <div class="mmhd-adv__row">
                             <label for="enable-auto-close-chat" class="mmhd-adv__label">
                                 <?php esc_html_e('Close chat conversations automatically', 'metamint-helpdesk'); ?>
                             </label>
                             <input id="enable-auto-close-chat" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_auto_close_chat_enabled, true); ?>>
                         </div>
                         <div class="mmhd-adv__sub">
                             <div id="auto-close-chat-options" class="mmhd-adv__inline"
                                 style="<?php echo $mmhd_auto_close_chat_enabled ? '' : 'display:none;'; ?>">
                                 <input type="number" name="auto_close_chat_minutes" id="auto-close-chat-minutes"
                                     min="1" max="1440"
                                     value="<?php echo esc_attr($mmhd_auto_close_chat_minutes); ?>"
                                     class="mmhd-adv__input mmhd-adv__input--md"
                                     aria-label="<?php esc_attr_e('Minutes before closing chat conversations', 'metamint-helpdesk'); ?>">
                                 <span class="mmhd-adv__suffix"><?php esc_html_e('minutes', 'metamint-helpdesk'); ?></span>
                             </div>
                             <p class="mmhd-adv__hint mmhd-adv__hint--wide">
                                 <?php esc_html_e('When the last reply on a chat conversation is from a Support Staff, Manager, or Admin, the conversation is closed automatically after this delay if the customer does not respond.', 'metamint-helpdesk'); ?>
                             </p>
                         </div>

                         <div class="mmhd-adv__row mmhd-adv__row--top">
                             <div class="mmhd-adv__text">
                                 <label for="enable-auto-close-ticket" class="mmhd-adv__label">
                                     <?php esc_html_e('Auto-close tickets after no customer reply for', 'metamint-helpdesk'); ?>
                                 </label>
                                 <p class="mmhd-adv__hint mmhd-adv__hint--wide">
                                     <?php esc_html_e('Applies to tickets from every source. The timer starts when the last reply is from Support Staff, a Manager, or an Admin (not from the end user). Tickets where the end user replied last are not closed. Tickets with no replies use the ticket creation date. When a ticket is closed automatically, the "Conversation Closed" email template is added to the ticket as a public comment.', 'metamint-helpdesk'); ?>
                                 </p>
                             </div>
                             <input id="enable-auto-close-ticket" type="checkbox" role="switch" class="mmhd-adv__switch"
                                 <?php checked($mmhd_auto_close_ticket_enabled, true); ?>>
                         </div>
                         <div id="auto-close-ticket-options" class="mmhd-adv__sub mmhd-adv__inline"
                             style="<?php echo $mmhd_auto_close_ticket_enabled ? '' : 'display:none;'; ?>">
                             <input type="number" name="auto_close_ticket_days" id="auto-close-ticket-days"
                                 min="1" max="365"
                                 value="<?php echo esc_attr($mmhd_auto_close_ticket_days_value); ?>"
                                 class="mmhd-adv__input mmhd-adv__input--md"
                                 aria-label="<?php esc_attr_e('Days before auto-closing tickets', 'metamint-helpdesk'); ?>">
                             <span class="mmhd-adv__suffix"><?php esc_html_e('days', 'metamint-helpdesk'); ?></span>
                         </div>
                     </section>

                     <div class="mmhd-adv__actions">
                         <button type="button" id="save-advanced-settings" class="mmhd-adv__save border bg-teal-600 border-teal-600 bg-teal-600 text-white px-6 py-2 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                             <?php esc_html_e('Save Advanced Settings', 'metamint-helpdesk'); ?>
                         </button>
                     </div>
                 </form>
             </div>
             <?php
                // Allow addons to add their settings content (Slack, Discord, etc.)
                // Addons hook into 'mmhd_settings_content' action to render their settings UI
                do_action('mmhd_settings_content');
                ?>
         </div>
     </div>
 </div>