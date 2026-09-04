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
     <div class="bg-white shadow rounded-lg">
         <div class="border-b border-gray-200">
             <nav class="mmhd-settings-nav-sec flex space-x-8 px-6">
                 <button onclick="showSettingsTab('basic')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="basic">
                     <?php esc_html_e('Basic Settings', 'mmhd'); ?>
                 </button>
                 <button onclick="showSettingsTab('imap')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm"
                     data-tab="imap">
                     <?php esc_html_e('Mail Settings', 'mmhd'); ?>
                 </button>
                 <button onclick="showSettingsTab('templates')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="templates">
                     <?php esc_html_e('Email Templates', 'mmhd'); ?>
                 </button>
                 <button onclick="showSettingsTab('fetch')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="fetch">
                     <?php esc_html_e('Fetch Tickets', 'mmhd'); ?>
                 </button>
                 <button onclick="showSettingsTab('advanced')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="advanced">
                     <?php esc_html_e('Advanced', 'mmhd'); ?>
                 </button>
                 <?php
                    // Allow addons to add their settings tabs
                    $mmhd_addon_tabs = apply_filters('mmhd_settings_tabs', array());
                    foreach ($mmhd_addon_tabs as $mmhd_tab_key => $mmhd_tab_label) {
                        echo '<button onclick="showSettingsTab(\'' . esc_js($mmhd_tab_key) . '\')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="' . esc_attr($mmhd_tab_key) . '">';
                        echo wp_kses_post($mmhd_tab_label);
                        echo '</button>';
                    }
                    ?>
             </nav>
         </div>
         <!------------------------------------------ overall setting page with all the tabs---------------------------------------->
         <div class="p-6">
             <!------------------------------Basic Settings Tab --------------------------------->

             <div id="basic-tab" class="settings-tab-content">
                 <form id="mmhd-basic-settings-form" class="space-y-6">
                     <input type="hidden" name="mmhd_basic_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_basic_settings_action')); ?>">
                     <div class="space-y-8">
                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Basic', 'mmhd'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Core site details used across Metamint.', 'mmhd'); ?></p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                 <div>
                                     <label for="site_name"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Site Name', 'mmhd'); ?></label>
                                     <input type="text" name="site_name" id="site_name"
                                         value="<?php echo esc_attr(get_option('mmhd_site_name', 'Metamint Helpdesk')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                                 <div>
                                     <label for="admin_email"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Admin Email', 'mmhd'); ?></label>
                                     <input type="email" name="admin_email" id="admin_email"
                                         value="<?php echo esc_attr(get_option('mmhd_admin_email', 'admin@example.com')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                            <div class="mt-6 flex items-center">
                                <input type="checkbox" name="enable_chat" id="enable_chat" value="1" <?php checked(get_option('mmhd_enable_chat', '1'), '1'); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="enable_chat" class="ml-2 block text-sm text-gray-900 font-medium"><?php esc_html_e('Enable Chat', 'mmhd'); ?></label>
                            </div>
                            <p class="text-sm text-gray-500 ml-7"><?php esc_html_e('When disabled, the Conversations tab, "Start a conversation" buttons, and the AI bot chat are hidden from the frontend. FAQ and other tabs remain visible.', 'mmhd'); ?></p>
                            <div class="mt-6 flex items-center">
                                <input type="checkbox" name="hide_credits" id="hide_credits" value="1" <?php checked(get_option('mmhd_hide_credits', '0'), '1'); ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="hide_credits" class="ml-2 block text-sm text-gray-900 font-medium"><?php esc_html_e('Hide Credits', 'mmhd'); ?></label>
                            </div>
                            <p class="text-sm text-gray-500 ml-7"><?php esc_html_e('When checked, the "Powered by Metamint" text is hidden from the chat widget footer.', 'mmhd'); ?></p>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                            <div class="mb-5">
                                <h4 class="text-base font-semibold text-gray-900">
                                    <?php esc_html_e('Logos', 'mmhd'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Upload the visuals that define the widget and brand colors.', 'mmhd'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="popover_top_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Top Icon/Image', 'mmhd'); ?></label>
                                     <input type="file" accept="image/*" name="popover_top_icon" id="popover_top_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-top-icon-preview"
                                             src="<?php echo esc_url(get_option('mmhd_popover_top_icon', '')); ?>"
                                             alt="Pop over Top Icon Preview"
                                             class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-top-icon-preview', 'mmhd_popover_top_icon', 'popover_top_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_popover_top_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'mmhd'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_popover_top_icon"
                                         id="mmhd_popover_top_icon"
                                         value="<?php echo esc_attr(get_option('mmhd_popover_top_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="theme_color"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Theme Color', 'mmhd'); ?></label>
                                     <div class="mt-1 flex items-center gap-3">
                                         <input type="color" name="theme_color" id="theme_color"
                                             value="<?php echo esc_attr(get_option('mmhd_theme_color', '#3B82F6')); ?>"
                                             class="styled-input block w-10 h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                         <div id="theme-color-preview"
                                             class="hidden w-16 h-8 rounded-md border border-gray-300"
                                             style="background-color: <?php echo esc_attr(get_option('mmhd_theme_color', '#3B82F6')); ?>;">
                                         </div>
                                     </div>
                                 </div>
                                 <div>
                                     <label for="popover_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Icon/Image', 'mmhd'); ?></label>
                                     <input type="file" accept="image/*" name="popover_icon" id="popover_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-icon-preview"
                                             src="<?php echo esc_url(get_option('mmhd_popover_icon', '')); ?>"
                                             alt="Pop over Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-icon-preview', 'mmhd_popover_icon', 'popover_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_popover_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'mmhd'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_popover_icon" id="mmhd_popover_icon"
                                         value="<?php echo esc_attr(get_option('mmhd_popover_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Logo', 'mmhd'); ?></label>
                                     <input type="file" accept="image/*" name="logo" id="logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="logo-preview"
                                             src="<?php echo esc_url(get_option('mmhd_logo', '')); ?>"
                                             alt="Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('logo-preview', 'mmhd_logo', 'logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'mmhd'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_logo" id="mmhd_logo"
                                         value="<?php echo esc_attr(get_option('mmhd_logo', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="wide_logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Wide Logo', 'mmhd'); ?></label>
                                     <input type="file" accept="image/*" name="wide_logo" id="wide_logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="wide-logo-preview"
                                             src="<?php echo esc_url(get_option('mmhd_wide_logo', '')); ?>"
                                             alt="Wide Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('wide-logo-preview', 'mmhd_wide_logo', 'wide_logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('mmhd_wide_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'mmhd'); ?></button>
                                     </div>
                                     <input type="hidden" name="mmhd_wide_logo" id="mmhd_wide_logo"
                                         value="<?php echo esc_attr(get_option('mmhd_wide_logo', '')); ?>">
                                 </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                            <div class="mb-5">
                                <h4 class="text-base font-semibold text-gray-900">
                                    <?php esc_html_e('Defaults', 'mmhd'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the default category and notification audio.', 'mmhd'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="mmhd_default_ticket_category"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Default Ticket Categories', 'mmhd'); ?></label>
                                     <select name="mmhd_default_ticket_category"
                                         id="mmhd_default_ticket_category"
                                         class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                         <option value=""><?php esc_html_e('Select a category', 'mmhd'); ?></option>
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
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Notification Sound', 'mmhd'); ?></label>
                                     <select name="mmhd_notification_sound" id="notification_sound"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
                                             <?php esc_html_e('Your browser does not support the audio element.', 'mmhd'); ?>
                                         </audio>
                                     </div>
                                     <input type="hidden" name="mmhd_notification_sound"
                                         id="mmhd_notification_sound"
                                         value="<?php echo esc_attr($mmhd_selected_sound); ?>">
                                 </div>
                             </div>
                         </section>

                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Messages', 'mmhd'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the chat title and message copy shown across the widget.', 'mmhd'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 gap-6">
                                 <div>
                                     <label for="chat_heading"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Chat Heading', 'mmhd'); ?></label>
                                     <input type="text" name="chat_heading" id="chat_heading"
                                         value="<?php echo esc_attr(get_option('mmhd_chat_heading', 'Have Questions?')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                                 <div>
                                     <label for="welcome_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Welcome Message for the chat Bot', 'mmhd'); ?></label>
                                     <textarea name="welcome_message" id="welcome_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea(get_option('mmhd_welcome_message', 'Have any questions or need a quote? Leave us a message.')); ?></textarea>
                                 </div>
                                 <div>
                                     <label for="ticket_intro_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Ticket Intro Message', 'mmhd'); ?></label>
                                     <textarea name="ticket_intro_message" id="ticket_intro_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea(get_option('mmhd_ticket_intro_message', 'If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!')); ?></textarea>
                                 </div>
                             </div>
                         </section>
                     </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="save-basic-settings"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Settings', 'mmhd'); ?>
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
                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('IMAP Settings', 'mmhd'); ?>
                 </h3>
                 <form method="post" action="" id="mmhd-imap-form" class="space-y-6">
                     <?php wp_nonce_field('mmhd_imap_settings_action', 'mmhd_imap_settings_nonce'); ?>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                             <label for="mmhd_imap_hostname"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('IMAP Host', 'mmhd'); ?></label>
                             <input type="text" name="mmhd_imap_hostname" id="mmhd_imap_hostname"
                                 value="<?php echo esc_attr($mmhd_imap_hostname); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_imap_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Port', 'mmhd'); ?></label>
                             <input type="number" name="mmhd_imap_port" id="mmhd_imap_port"
                                 value="<?php echo esc_attr($mmhd_imap_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_imap_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Username', 'mmhd'); ?></label>
                             <input type="email" name="mmhd_imap_username" id="mmhd_imap_username"
                                 value="<?php echo esc_attr($mmhd_imap_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_imap_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Password', 'mmhd'); ?></label>
                             <input type="password" name="mmhd_imap_password" id="mmhd_imap_password"
                                 value="<?php echo esc_attr($mmhd_imap_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                          <div>
                              <label for="mmhd_imap_inbox_name"
                                  class="block text-sm font-medium text-gray-700"><?php esc_html_e('Inbox Name', 'mmhd'); ?></label>
                              <input type="text" name="mmhd_imap_inbox_name" id="mmhd_imap_inbox_name"
                                  value="<?php echo esc_attr($mmhd_imap_inbox_name); ?>"
                                  placeholder="e.g. Sales, Support, General"
                                  class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                              <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('A name to identify this inbox in the dashboard.', 'mmhd'); ?></p>
                          </div>
                          <div>
                              <label for="mmhd_imap_auth"
                                  class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'mmhd'); ?></label>
                              <select name="mmhd_imap_auth" id="mmhd_imap_auth"
                                  class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                  <option value="1" <?php selected($mmhd_imap_auth, true); ?>>
                                      <?php esc_html_e('SSL', 'mmhd'); ?></option>
                                  <option value="0" <?php selected($mmhd_imap_auth, false); ?>>
                                      <?php esc_html_e('TLS', 'mmhd'); ?></option>
                              </select>
                          </div>
                     </div>
                     <div class="mt-6 flex gap-4">
                         <button type="button" id="mmhd-imap-test" onclick="testConnection()"
                             class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Test Connection', 'mmhd'); ?>
                         </button>


                         <button type="button" id="save-imap-settings"
                             class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Save Settings', 'mmhd'); ?>
                         </button>

                     </div>

                 </form>

                 <hr class="my-8 border-gray-200">

                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('SMTP Settings', 'mmhd'); ?>
                 </h3>
                 <form method="post" action="" id="mmhd-smtp-form" class="space-y-6">
                     <?php wp_nonce_field('mmhd_smtp_settings_action', 'mmhd_smtp_settings_nonce'); ?>

                     <div class="flex items-center">
                         <input id="mmhd_smtp_enabled" type="checkbox"
                             <?php checked($mmhd_smtp_enabled, true); ?>
                             class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                         <label for="mmhd_smtp_enabled" class="ml-2 block text-sm text-gray-900">
                             <?php esc_html_e('Enable SMTP for outgoing emails', 'mmhd'); ?>
                         </label>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                             <label for="mmhd_smtp_host"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Host', 'mmhd'); ?></label>
                             <input type="text" name="mmhd_smtp_host" id="mmhd_smtp_host"
                                 value="<?php echo esc_attr($mmhd_smtp_host); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_smtp_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Port', 'mmhd'); ?></label>
                             <input type="number" name="mmhd_smtp_port" id="mmhd_smtp_port"
                                 value="<?php echo esc_attr($mmhd_smtp_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_smtp_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Username', 'mmhd'); ?></label>
                             <input type="text" name="mmhd_smtp_username" id="mmhd_smtp_username"
                                 value="<?php echo esc_attr($mmhd_smtp_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_smtp_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Password', 'mmhd'); ?></label>
                             <input type="password" name="mmhd_smtp_password" id="mmhd_smtp_password"
                                 value="<?php echo esc_attr($mmhd_smtp_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="mmhd_smtp_encryption"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'mmhd'); ?></label>
                             <select name="mmhd_smtp_encryption" id="mmhd_smtp_encryption"
                                 class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <option value="none" <?php selected($mmhd_smtp_encryption, 'none'); ?>>
                                     <?php esc_html_e('None', 'mmhd'); ?></option>
                                 <option value="ssl" <?php selected($mmhd_smtp_encryption, 'ssl'); ?>>
                                     <?php esc_html_e('SSL', 'mmhd'); ?></option>
                                 <option value="tls" <?php selected($mmhd_smtp_encryption, 'tls'); ?>>
                                     <?php esc_html_e('TLS', 'mmhd'); ?></option>
                             </select>
                         </div>
                         <div>
                             <label for="mmhd_smtp_from_email"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Email', 'mmhd'); ?></label>
                             <input type="email" name="mmhd_smtp_from_email" id="mmhd_smtp_from_email"
                                 value="<?php echo esc_attr($mmhd_smtp_from_email); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div class="md:col-span-2">
                             <label for="mmhd_smtp_from_name"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Name', 'mmhd'); ?></label>
                             <input type="text" name="mmhd_smtp_from_name" id="mmhd_smtp_from_name"
                                 value="<?php echo esc_attr($mmhd_smtp_from_name); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                     </div>

                     <div class="mt-6 flex gap-4">
                         <button type="button" id="mmhd-smtp-test"
                             class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Test SMTP Connection', 'mmhd'); ?>
                         </button>
                         <button type="button" id="save-smtp-settings"
                             class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Save SMTP Settings', 'mmhd'); ?>
                         </button>
                     </div>
                 </form>
             </div>


             <!------------------------------------------ Email Templates Tab --------------------------->

             <div id="templates-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('Email Templates', 'mmhd'); ?>
                 </h3>

                 <div class="bg-[#ebedf5] rounded-lg p-6 text-gray-800 shadow-sm">
                     <div class="flex justify-between items-center">
                         <h2 class="text-base font-semibold text-gray-900">
                             <?php esc_html_e('Customize the email templates with Variables', 'mmhd'); ?></h2>
                         <button type="button" id="mmhd-email-variables-toggle"
                             class="text-sm text-blue-600 hover:underline focus:outline-none">
                             <?php esc_html_e('Show', 'mmhd'); ?>
                         </button>
                     </div>

                     <div id="mmhd-email-variables-content"
                         class="mt-4 text-sm text-gray-700 leading-relaxed space-y-4 hidden">
                         <p><?php esc_html_e('You can use the following variables in your templates:', 'mmhd'); ?>
                         </p>
                         <ul class="list-disc pl-5 space-y-1">
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_id}</code> –
                                 <?php esc_html_e('The ID of the ticket', 'mmhd'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_author}</code> –
                                 <?php esc_html_e('The name of the ticket author', 'mmhd'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_link}</code> –
                                 <?php esc_html_e('The link to the ticket', 'mmhd'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_title}</code> –
                                 <?php esc_html_e('The title of the ticket', 'mmhd'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_status}</code> –
                                 <?php esc_html_e('The status of the ticket', 'mmhd'); ?></li>
                         </ul>
                     </div>
                 </div>


                 <form id="mmhd_email_template_save" class="space-y-6">
                     <input type="hidden" name="mmhd_email_template_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_email_template_action')); ?>">
                     <div class="space-y-6">
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('New Conversation Notification', 'mmhd'); ?></label>
                             <div class="editor-toolbar mb-2">
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
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Closed', 'mmhd'); ?></label>
                             <div class="editor-toolbar mb-2">
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
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Auto Reply', 'mmhd'); ?></label>
                             <div class="editor-toolbar mb-2">
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
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="mmhd-save-templates"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Templates', 'mmhd'); ?>
                     </button>
                 </form>
             </div>
             <!------------------------------ Fetch Tickets Tab --------------------------------------->
             <div id="fetch-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">
                     <?php esc_html_e('Fetch Conversations', 'mmhd'); ?></h3>
                 <form id="mmhd-fetch-tickets-form" class="space-y-6">
                     <input type="hidden" name="mmhd_ticket_assignment_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_ticket_assignment_action')); ?>">
                     <div class="space-y-6">
                         <p class="text-sm text-gray-600">
                             <?php esc_html_e('Use this feature to fetch new emails from your configured IMAP account and convert them into conversations and replies.', 'mmhd'); ?>
                         </p>
                         <div class="bg-gray-50 p-4 rounded-lg">
                             <h4 class="font-medium text-gray-900 mb-2">
                                 <?php esc_html_e('Email Fetching Settings', 'mmhd'); ?></h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Fetch Interval (minutes)', 'mmhd'); ?></label>
                                     <input type="number" name="fetch_interval"
                                         value="<?php echo esc_attr(get_option('mmhd_fetch_interval', 15)); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                         min="1" max="1440" required>
                                 </div>
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Auto-assign to', 'mmhd'); ?></label>
                                     <select name="default_agent"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                         <!-- default options -->
                                         <?php
                                            $mmhd_default_agent = get_option('mmhd_default_agent');
                                            ?>
                                         <option value="" <?php selected((string) $mmhd_default_agent, ''); ?>>
                                             <?php esc_html_e('Least loaded agent (auto)', 'mmhd'); ?></option>

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
                                         <?php esc_html_e('Leave this on "Least loaded agent (auto)" to assign fetched email tickets to the least-loaded agent.', 'mmhd'); ?>
                                     </p>
                                 </div>
                             </div>
                             <div class="mt-4">
                                 <label class="flex items-start space-x-2 cursor-pointer">
                                     <input type="checkbox" name="do_not_send_email_on_import" value="1"
                                         <?php checked(get_option('mmhd_do_not_send_email_on_import', 0)); ?>
                                         class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                     <span class="text-sm font-medium text-gray-700">
                                         <?php esc_html_e('Do not send email on import', 'mmhd'); ?>
                                     </span>
                                 </label>
                                 <p class="mt-1 text-xs text-gray-500">
                                     <?php esc_html_e('Suppresses all outgoing Metamint emails — new user welcome, ticket creation confirmation, reply and close notifications, password reset, and more. Enable this to import existing conversations without notifying customers, or to pause all Metamint email delivery.', 'mmhd'); ?>
                                 </p>
                             </div>
                         </div>
                         <div class="flex space-x-4">
                             <button id="save-ticket-assignment-settings"
                                 class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                 <i class="fas fa-cog mr-2"></i>
                                 <?php esc_html_e('Save Auto-Fetch', 'mmhd'); ?>
                             </button>
                         </div>
                     </div>
                 </form>

                 <div class="flex space-x-4 mt-6">
                     <button onclick="fetchEmails()"
                         class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                         <i class="fas fa-sync-alt mr-2"></i>
                         <?php esc_html_e('Fetch Emails Now', 'mmhd'); ?>
                     </button>
                 </div>
                 <div id="fetch-results" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                     <div class="flex items-center">
                         <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                         <span id="fetch-message" class="text-blue-800"></span>
                     </div>
                 </div>

             </div>

             <!--------------------------- Advanced Settings Tab -------------------------------->


             <?php
                 $mmhd_advanced_settings = wp_parse_args(get_option('mmhd_advanced_settings', []), [
                    'enable_auto_assign' => false,
                    'enable_notifications' => true,
                    'enable_customer_portal' => true,
                    'enable_signup_rate_limiting' => false,
                    'max_file_upload_size' => 10,
                    'reply_unseen_email_minutes' => 15,
                    'polling_interval' => 30,
                ]);
                ?>
             <div id="advanced-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">
                     <?php esc_html_e('Advanced Settings', 'mmhd'); ?></h3>
                 <form id="advanced-settings-form" class="space-y-6">
                     <input type="hidden" name="mmhd_advanced_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('mmhd_advanced_settings_action')); ?>">
                     <div class="space-y-4">
                         <div class="flex items-center">
                             <input id="enable-auto-assign" type="checkbox"
                                 <?php checked($mmhd_advanced_settings['enable_auto_assign'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-auto-assign" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable auto-assignment of Conversations', 'mmhd'); ?>
                             </label>
                         </div>
                         <div class="flex items-center">
                             <input id="enable-notifications" type="checkbox"
                                 <?php checked($mmhd_advanced_settings['enable_notifications'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-notifications" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable email notifications', 'mmhd'); ?>
                             </label>
                         </div>
                         <?php
                            $mmhd_reply_unseen_minutes = isset($mmhd_advanced_settings['reply_unseen_email_minutes']) ? absint($mmhd_advanced_settings['reply_unseen_email_minutes']) : 15;
                            $mmhd_reply_unseen_enabled = $mmhd_reply_unseen_minutes > 0;
                            ?>
                         <div>
                             <div class="flex items-center gap-2">
                                 <input id="enable-unseen-reply-email" type="checkbox"
                                     <?php checked($mmhd_reply_unseen_enabled, true); ?>
                                     class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                 <label for="enable-unseen-reply-email" class="block text-sm font-medium text-gray-700">
                                     <?php esc_html_e("Email the reply if the client hasn't seen it for", 'mmhd'); ?>
                                 </label>
                             </div>
                             <div id="reply-unseen-email-options" class="mt-2 flex items-center gap-2"
                                 style="<?php echo $mmhd_reply_unseen_enabled ? '' : 'display:none;'; ?>">
                                 <select name="reply_unseen_email_minutes" id="reply-unseen-email-minutes"
                                     class="styled-select block w-40 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <?php
                                        $mmhd_reply_minute_options = [5, 10, 15, 30, 60, 120];
                                        foreach ($mmhd_reply_minute_options as $mmhd_reply_minutes) {
                                            echo '<option value="' . esc_attr($mmhd_reply_minutes) . '" ' . selected($mmhd_reply_unseen_minutes, $mmhd_reply_minutes, false) . '>' . esc_html($mmhd_reply_minutes) . '</option>';
                                        }
                                        ?>
                                 </select>
                                  <span class="text-sm text-gray-600"><?php esc_html_e('minutes', 'mmhd'); ?></span>
                              </div>
                          </div>
                          <div>
                              <label for="polling-interval"
                                  class="block text-sm font-medium text-gray-700 mb-1">
                                  <?php esc_html_e('Dashboard polling interval', 'mmhd'); ?>
                              </label>
                              <p class="text-xs text-gray-500 mb-2">
                                  <?php esc_html_e('How often the dashboard checks for new replies and notifications. Shorter intervals feel more real-time but increase server load.', 'mmhd'); ?>
                              </p>
                              <select id="polling-interval" name="polling_interval"
                                  class="styled-select block w-40 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                  <?php
                                     $mmhd_polling_options = [15, 30, 45, 60, 120];
                                     foreach ($mmhd_polling_options as $mmhd_poll_sec) {
                                         echo '<option value="' . esc_attr($mmhd_poll_sec) . '" ' . selected($mmhd_advanced_settings['polling_interval'], $mmhd_poll_sec, false) . '>' . esc_html($mmhd_poll_sec) . ' ' . esc_html__('seconds', 'mmhd') . '</option>';
                                     }
                                     ?>
                              </select>
                          </div>
                          <div class="flex items-center">
                              <input id="enable-customer-portal" type="checkbox"
                                 <?php checked($mmhd_advanced_settings['enable_customer_portal'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-customer-portal" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable customer portal', 'mmhd'); ?>
                             </label>
                         </div>
                          <div>
                              <label
                                  class="block text-sm font-medium text-gray-700"><?php esc_html_e('Maximum file upload size (MB)', 'mmhd'); ?></label>
                              <input type="number" name="max_file_upload_size"
                                  value="<?php echo esc_attr($mmhd_advanced_settings['max_file_upload_size']); ?>"
                                  min="1" max="100"
                                  class="styled-input mt-1 block w-32 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                          </div>
                          <div class="flex items-start">
                              <input type="checkbox" id="enable-signup-rate-limiting" name="enable_signup_rate_limiting" value="1"
                                  <?php checked($mmhd_advanced_settings['enable_signup_rate_limiting'] ?? false, true); ?>
                                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
                              <label for="enable-signup-rate-limiting" class="ml-2 block text-sm text-gray-900">
                                  <?php esc_html_e('Enable signup rate limiting &amp; IP blocking', 'mmhd'); ?>
                              </label>
                          </div>
                          <div>
                                <label for="blocked-ips"
                                    class="block text-sm font-medium text-gray-700"><?php esc_html_e('Blocked IPs', 'mmhd'); ?></label>
                                <p class="text-xs text-gray-500 mb-2">
                                    <?php esc_html_e('One IP per line. These IPs are blocked from signing up. Add or remove IPs manually.', 'mmhd'); ?>
                                </p>
                                <textarea id="blocked-ips" name="blocked_ips" rows="5"
                                    class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"><?php
                                      $mmhd_blocked_ips = get_option('mmhd_blocked_ips', array());
                                      echo esc_textarea(implode("\n", $mmhd_blocked_ips));
                                    ?></textarea>
                            </div>
                      </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="save-advanced-settings"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Advanced Settings', 'mmhd'); ?>
                     </button>
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