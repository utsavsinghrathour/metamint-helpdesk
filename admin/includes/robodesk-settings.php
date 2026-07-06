 <?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    ?>
<?php
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
$settings_hidden = ($current_tab === 'settings') ? '' : ' hidden';
?>
 <div id="settings-page" class="page-content<?php echo $settings_hidden; ?>">
     <div class="bg-white shadow rounded-lg">
         <div class="border-b border-gray-200">
             <nav class="rbd-settings-nav-sec flex space-x-8 px-6">
                 <button onclick="showSettingsTab('basic')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="basic">
                     <?php esc_html_e('Basic Settings', 'robodesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('imap')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm"
                     data-tab="imap">
                     <?php esc_html_e('Mail Settings', 'robodesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('templates')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="templates">
                     <?php esc_html_e('Email Templates', 'robodesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('fetch')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="fetch">
                     <?php esc_html_e('Fetch Tickets', 'robodesk'); ?>
                 </button>
                 <button onclick="showSettingsTab('advanced')"
                     class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="advanced">
                     <?php esc_html_e('Advanced', 'robodesk'); ?>
                 </button>
                 <?php
                    // Allow addons to add their settings tabs
                    $robodesk_addon_tabs = apply_filters('robodesk_settings_tabs', array());
                    foreach ($robodesk_addon_tabs as $robodesk_tab_key => $robodesk_tab_label) {
                        echo '<button onclick="showSettingsTab(\'' . esc_js($robodesk_tab_key) . '\')" class="settings-tab py-4 px-1 border-b-2 font-medium text-sm" data-tab="' . esc_attr($robodesk_tab_key) . '">';
                        echo wp_kses_post($robodesk_tab_label);
                        echo '</button>';
                    }
                    ?>
             </nav>
         </div>
         <!------------------------------------------ overall setting page with all the tabs---------------------------------------->
         <div class="p-6">
             <!------------------------------Basic Settings Tab --------------------------------->

             <div id="basic-tab" class="settings-tab-content">
                 <form id="robodesk-basic-settings-form" class="space-y-6">
                     <input type="hidden" name="robodesk_basic_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('robodesk_basic_settings_action')); ?>">
                     <div class="space-y-8">
                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Basic', 'robodesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Core site details used across Robodesk.', 'robodesk'); ?></p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                 <div>
                                     <label for="site_name"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Site Name', 'robodesk'); ?></label>
                                     <input type="text" name="site_name" id="site_name"
                                         value="<?php echo esc_attr(get_option('robodesk_site_name', 'Robodesk Support')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                                 <div>
                                     <label for="admin_email"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Admin Email', 'robodesk'); ?></label>
                                     <input type="email" name="admin_email" id="admin_email"
                                         value="<?php echo esc_attr(get_option('robodesk_admin_email', 'admin@example.com')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                             </div>
                         </section>

                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Logos', 'robodesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Upload the visuals that define the widget and brand colors.', 'robodesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="popover_top_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Top Icon/Image', 'robodesk'); ?></label>
                                     <input type="file" accept="image/*" name="popover_top_icon" id="popover_top_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-top-icon-preview"
                                             src="<?php echo esc_url(get_option('robodesk_popover_top_icon', '')); ?>"
                                             alt="Pop over Top Icon Preview"
                                             class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-top-icon-preview', 'robodesk_popover_top_icon', 'popover_top_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_popover_top_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'robodesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="robodesk_popover_top_icon"
                                         id="robodesk_popover_top_icon"
                                         value="<?php echo esc_attr(get_option('robodesk_popover_top_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="theme_color"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Theme Color', 'robodesk'); ?></label>
                                     <div class="mt-1 flex items-center gap-3">
                                         <input type="color" name="theme_color" id="theme_color"
                                             value="<?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>"
                                             class="styled-input block w-10 h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                         <div id="theme-color-preview"
                                             class="hidden w-16 h-8 rounded-md border border-gray-300"
                                             style="background-color: <?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>;">
                                         </div>
                                     </div>
                                 </div>
                                 <div>
                                     <label for="popover_icon"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Pop over Icon/Image', 'robodesk'); ?></label>
                                     <input type="file" accept="image/*" name="popover_icon" id="popover_icon"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="popover-icon-preview"
                                             src="<?php echo esc_url(get_option('robodesk_popover_icon', '')); ?>"
                                             alt="Pop over Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('popover-icon-preview', 'robodesk_popover_icon', 'popover_icon')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_popover_icon', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'robodesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="robodesk_popover_icon" id="robodesk_popover_icon"
                                         value="<?php echo esc_attr(get_option('robodesk_popover_icon', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Logo', 'robodesk'); ?></label>
                                     <input type="file" accept="image/*" name="logo" id="logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="logo-preview"
                                             src="<?php echo esc_url(get_option('robodesk_logo', '')); ?>"
                                             alt="Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('logo-preview', 'robodesk_logo', 'logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'robodesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="robodesk_logo" id="robodesk_logo"
                                         value="<?php echo esc_attr(get_option('robodesk_logo', '')); ?>">
                                 </div>
                                 <div>
                                     <label for="wide_logo"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Wide Logo', 'robodesk'); ?></label>
                                     <input type="file" accept="image/*" name="wide_logo" id="wide_logo"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <div class="mt-2 flex items-center gap-2">
                                         <img id="wide-logo-preview"
                                             src="<?php echo esc_url(get_option('robodesk_wide_logo', '')); ?>"
                                             alt="Wide Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                         <button type="button"
                                             onclick="removeImage('wide-logo-preview', 'robodesk_wide_logo', 'wide_logo')"
                                             class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_wide_logo', '') ? '' : 'hidden'; ?>"><?php esc_html_e('Remove', 'robodesk'); ?></button>
                                     </div>
                                     <input type="hidden" name="robodesk_wide_logo" id="robodesk_wide_logo"
                                         value="<?php echo esc_attr(get_option('robodesk_wide_logo', '')); ?>">
                                 </div>
                             </div>
                         </section>

                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Defaults', 'robodesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the default category and notification audio.', 'robodesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div>
                                     <label for="robodesk_default_ticket_category"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Default Ticket Categories', 'robodesk'); ?></label>
                                     <select name="robodesk_default_ticket_category"
                                         id="robodesk_default_ticket_category"
                                         class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                         <option value=""><?php esc_html_e('Select a category', 'robodesk'); ?></option>
                                         <?php
                                            $robodesk_selected_category = get_option('robodesk_default_ticket_category', '');
                                            $robodesk_categories = get_terms(array(
                                                'taxonomy'   => 'robodesk_ticket_category',
                                                'hide_empty' => false,
                                            ));
                                            foreach ($robodesk_categories as $robodesk_category) {
                                                echo '<option value="' . esc_attr($robodesk_category->term_id) . '" ' . selected($robodesk_selected_category, $robodesk_category->term_id, false) . '>' . esc_html($robodesk_category->name) . '</option>';
                                            }
                                            ?>
                                     </select>
                                 </div>
                                 <div>
                                     <label for="notification_sound"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Notification Sound', 'robodesk'); ?></label>
                                     <select name="robodesk_notification_sound" id="notification_sound"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                         <?php
                                            $robodesk_audio_files = [
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
                                            $robodesk_selected_sound = get_option('robodesk_notification_sound', '');
                                            foreach ($robodesk_audio_files as $robodesk_audio_label => $robodesk_audio_url) {
                                                echo '<option value="' . esc_url($robodesk_audio_url) . '" ' . selected($robodesk_selected_sound, $robodesk_audio_url, false) . '>' . esc_html(ucfirst($robodesk_audio_label)) . '</option>';
                                            }
                                            ?>
                                     </select>
                                     <div class="mt-2 flex items-center gap-2">
                                         <audio id="notification-sound-preview" controls
                                             class="<?php echo $robodesk_selected_sound ? '' : 'hidden'; ?>">
                                             <source src="<?php echo esc_url($robodesk_selected_sound); ?>"
                                                 type="audio/mpeg">
                                             <?php esc_html_e('Your browser does not support the audio element.', 'robodesk'); ?>
                                         </audio>
                                     </div>
                                     <input type="hidden" name="robodesk_notification_sound"
                                         id="robodesk_notification_sound"
                                         value="<?php echo esc_attr($robodesk_selected_sound); ?>">
                                 </div>
                             </div>
                         </section>

                         <section class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                             <div class="mb-5">
                                 <h4 class="text-base font-semibold text-gray-900">
                                     <?php esc_html_e('Messages', 'robodesk'); ?></h4>
                                 <p class="text-sm text-gray-500">
                                     <?php esc_html_e('Set the chat title and message copy shown across the widget.', 'robodesk'); ?>
                                 </p>
                             </div>
                             <div class="grid grid-cols-1 gap-6">
                                 <div>
                                     <label for="chat_heading"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Chat Heading', 'robodesk'); ?></label>
                                     <input type="text" name="chat_heading" id="chat_heading"
                                         value="<?php echo esc_attr(get_option('robodesk_chat_heading', 'Have Questions?')); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 </div>
                                 <div>
                                     <label for="welcome_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Welcome Message for the chat Bot', 'robodesk'); ?></label>
                                     <textarea name="welcome_message" id="welcome_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea(get_option('robodesk_welcome_message', 'Have any questions or need a quote? Leave us a message.')); ?></textarea>
                                 </div>
                                 <div>
                                     <label for="ticket_intro_message"
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Ticket Intro Message', 'robodesk'); ?></label>
                                     <textarea name="ticket_intro_message" id="ticket_intro_message" rows="4"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea(get_option('robodesk_ticket_intro_message', 'If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!')); ?></textarea>
                                 </div>
                             </div>
                         </section>
                     </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="save-basic-settings"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Settings', 'robodesk'); ?>
                     </button>
                 </form>
             </div>

             <!----------------------- IMAP Settings load saved settings ------------------->
             <?php
                // Load saved settings
                $robodesk_imap_settings = get_option('robodesk_imap_settings', array(
                    'hostname' => 'email.example.com',
                    'port'     => 993,
                    'username' => '',
                    'password' => '',
                    'auth'     => true,

                ));

                $robodesk_imap_hostname = $robodesk_imap_settings['hostname'];
                $robodesk_imap_port     = $robodesk_imap_settings['port'];
                $robodesk_imap_username = $robodesk_imap_settings['username'];
                $robodesk_imap_password = $robodesk_imap_settings['password'];
                $robodesk_imap_auth     = isset($robodesk_imap_settings['auth']) ? $robodesk_imap_settings['auth'] : true;

                $robodesk_smtp_settings = get_option('robodesk_smtp_settings', array(
                    'enabled' => false,
                    'host' => '',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_email' => get_option('admin_email', ''),
                    'from_name' => get_bloginfo('name'),
                ));

                $robodesk_smtp_enabled = !empty($robodesk_smtp_settings['enabled']);
                $robodesk_smtp_host = isset($robodesk_smtp_settings['host']) ? $robodesk_smtp_settings['host'] : '';
                $robodesk_smtp_port = isset($robodesk_smtp_settings['port']) ? (int) $robodesk_smtp_settings['port'] : 587;
                $robodesk_smtp_username = isset($robodesk_smtp_settings['username']) ? $robodesk_smtp_settings['username'] : '';
                $robodesk_smtp_password = isset($robodesk_smtp_settings['password']) ? $robodesk_smtp_settings['password'] : '';
                $robodesk_smtp_encryption = isset($robodesk_smtp_settings['encryption']) ? $robodesk_smtp_settings['encryption'] : 'tls';
                $robodesk_smtp_from_email = isset($robodesk_smtp_settings['from_email']) ? $robodesk_smtp_settings['from_email'] : get_option('admin_email', '');
                $robodesk_smtp_from_name = isset($robodesk_smtp_settings['from_name']) ? $robodesk_smtp_settings['from_name'] : get_bloginfo('name');

                ?>

             <!--------------------------- IMAP Settings Tab ---------------------------------------->
             <div id="imap-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('IMAP Settings', 'robodesk'); ?>
                 </h3>
                 <form method="post" action="" id="robodesk-imap-form" class="space-y-6">
                     <?php wp_nonce_field('robodesk_imap_settings_action', 'robodesk_imap_settings_nonce'); ?>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                             <label for="robodesk_imap_hostname"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('IMAP Host', 'robodesk'); ?></label>
                             <input type="text" name="robodesk_imap_hostname" id="robodesk_imap_hostname"
                                 value="<?php echo esc_attr($robodesk_imap_hostname); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_imap_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Port', 'robodesk'); ?></label>
                             <input type="number" name="robodesk_imap_port" id="robodesk_imap_port"
                                 value="<?php echo esc_attr($robodesk_imap_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_imap_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Username', 'robodesk'); ?></label>
                             <input type="email" name="robodesk_imap_username" id="robodesk_imap_username"
                                 value="<?php echo esc_attr($robodesk_imap_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_imap_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Password', 'robodesk'); ?></label>
                             <input type="password" name="robodesk_imap_password" id="robodesk_imap_password"
                                 value="<?php echo esc_attr($robodesk_imap_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_imap_auth"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'robodesk'); ?></label>
                             <select name="robodesk_imap_auth" id="robodesk_imap_auth"
                                 class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <option value="1" <?php selected($robodesk_imap_auth, true); ?>>
                                     <?php esc_html_e('SSL', 'robodesk'); ?></option>
                                 <option value="0" <?php selected($robodesk_imap_auth, false); ?>>
                                     <?php esc_html_e('TLS', 'robodesk'); ?></option>
                             </select>
                         </div>
                     </div>
                     <div class="mt-6 flex gap-4">
                         <button type="button" id="robodesk-imap-test" onclick="testConnection()"
                             class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Test Connection', 'robodesk'); ?>
                         </button>


                         <button type="button" id="save-imap-settings"
                             class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Save Settings', 'robodesk'); ?>
                         </button>

                     </div>

                 </form>

                 <hr class="my-8 border-gray-200">

                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('SMTP Settings', 'robodesk'); ?>
                 </h3>
                 <form method="post" action="" id="robodesk-smtp-form" class="space-y-6">
                     <?php wp_nonce_field('robodesk_smtp_settings_action', 'robodesk_smtp_settings_nonce'); ?>

                     <div class="flex items-center">
                         <input id="robodesk_smtp_enabled" type="checkbox"
                             <?php checked($robodesk_smtp_enabled, true); ?>
                             class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                         <label for="robodesk_smtp_enabled" class="ml-2 block text-sm text-gray-900">
                             <?php esc_html_e('Enable SMTP for outgoing emails', 'robodesk'); ?>
                         </label>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                             <label for="robodesk_smtp_host"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Host', 'robodesk'); ?></label>
                             <input type="text" name="robodesk_smtp_host" id="robodesk_smtp_host"
                                 value="<?php echo esc_attr($robodesk_smtp_host); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_smtp_port"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Port', 'robodesk'); ?></label>
                             <input type="number" name="robodesk_smtp_port" id="robodesk_smtp_port"
                                 value="<?php echo esc_attr($robodesk_smtp_port); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_smtp_username"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Username', 'robodesk'); ?></label>
                             <input type="text" name="robodesk_smtp_username" id="robodesk_smtp_username"
                                 value="<?php echo esc_attr($robodesk_smtp_username); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_smtp_password"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('SMTP Password', 'robodesk'); ?></label>
                             <input type="password" name="robodesk_smtp_password" id="robodesk_smtp_password"
                                 value="<?php echo esc_attr($robodesk_smtp_password); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="robodesk_smtp_encryption"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('Encryption', 'robodesk'); ?></label>
                             <select name="robodesk_smtp_encryption" id="robodesk_smtp_encryption"
                                 class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <option value="none" <?php selected($robodesk_smtp_encryption, 'none'); ?>>
                                     <?php esc_html_e('None', 'robodesk'); ?></option>
                                 <option value="ssl" <?php selected($robodesk_smtp_encryption, 'ssl'); ?>>
                                     <?php esc_html_e('SSL', 'robodesk'); ?></option>
                                 <option value="tls" <?php selected($robodesk_smtp_encryption, 'tls'); ?>>
                                     <?php esc_html_e('TLS', 'robodesk'); ?></option>
                             </select>
                         </div>
                         <div>
                             <label for="robodesk_smtp_from_email"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Email', 'robodesk'); ?></label>
                             <input type="email" name="robodesk_smtp_from_email" id="robodesk_smtp_from_email"
                                 value="<?php echo esc_attr($robodesk_smtp_from_email); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div class="md:col-span-2">
                             <label for="robodesk_smtp_from_name"
                                 class="block text-sm font-medium text-gray-700"><?php esc_html_e('From Name', 'robodesk'); ?></label>
                             <input type="text" name="robodesk_smtp_from_name" id="robodesk_smtp_from_name"
                                 value="<?php echo esc_attr($robodesk_smtp_from_name); ?>"
                                 class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                     </div>

                     <div class="mt-6 flex gap-4">
                         <button type="button" id="robodesk-smtp-test"
                             class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Test SMTP Connection', 'robodesk'); ?>
                         </button>
                         <button type="button" id="save-smtp-settings"
                             class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                             <?php esc_html_e('Save SMTP Settings', 'robodesk'); ?>
                         </button>
                     </div>
                 </form>
             </div>


             <!------------------------------------------ Email Templates Tab --------------------------->

             <div id="templates-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6"><?php esc_html_e('Email Templates', 'robodesk'); ?>
                 </h3>

                 <div class="bg-[#ebedf5] rounded-lg p-6 text-gray-800 shadow-sm">
                     <div class="flex justify-between items-center">
                         <h2 class="text-base font-semibold text-gray-900">
                             <?php esc_html_e('Customize the email templates with Variables', 'robodesk'); ?></h2>
                         <button type="button" id="robodesk-email-variables-toggle"
                             class="text-sm text-blue-600 hover:underline focus:outline-none">
                             <?php esc_html_e('Show', 'robodesk'); ?>
                         </button>
                     </div>

                     <div id="robodesk-email-variables-content"
                         class="mt-4 text-sm text-gray-700 leading-relaxed space-y-4 hidden">
                         <p><?php esc_html_e('You can use the following variables in your templates:', 'robodesk'); ?>
                         </p>
                         <ul class="list-disc pl-5 space-y-1">
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_id}</code> –
                                 <?php esc_html_e('The ID of the ticket', 'robodesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_author}</code> –
                                 <?php esc_html_e('The name of the ticket author', 'robodesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_link}</code> –
                                 <?php esc_html_e('The link to the ticket', 'robodesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_title}</code> –
                                 <?php esc_html_e('The title of the ticket', 'robodesk'); ?></li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_status}</code> –
                                 <?php esc_html_e('The status of the ticket', 'robodesk'); ?></li>
                         </ul>
                     </div>
                 </div>


                 <form id="robodesk_email_template_save" class="space-y-6">
                     <input type="hidden" name="robodesk_email_template_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('robodesk_email_template_action')); ?>">
                     <div class="space-y-6">
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('New Conversation Notification', 'robodesk'); ?></label>
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
                                 <?php echo wp_kses_post(get_option('robodesk_email_template_new-ticket', 'Thank you for contacting us. We have received your ticket and will respond soon.')); ?>
                             </div>
                         </div>
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Conversation Closed', 'robodesk'); ?></label>
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
                                 <?php echo wp_kses_post(get_option('robodesk_email_template_ticket-closed', 'Your ticket has been closed. Please let us know if you need further assistance.')); ?>
                             </div>
                         </div>
                         <div>
                             <label
                                 class="block text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Auto Reply', 'robodesk'); ?></label>
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
                                 <?php echo wp_kses_post(get_option('robodesk_email_template_auto-reply', 'This is an automated response. We will get back to you within 24 hours.')); ?>
                             </div>
                         </div>
                     </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="robodesk-save-templates"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Templates', 'robodesk'); ?>
                     </button>
                 </form>
             </div>
             <!------------------------------ Fetch Tickets Tab --------------------------------------->
             <div id="fetch-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">
                     <?php esc_html_e('Fetch Conversations', 'robodesk'); ?></h3>
                 <form id="robodesk-fetch-tickets-form" class="space-y-6">
                     <input type="hidden" name="robodesk_ticket_assignment_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('robodesk_ticket_assignment_action')); ?>">
                     <div class="space-y-6">
                         <p class="text-sm text-gray-600">
                             <?php esc_html_e('Use this feature to fetch new emails from your configured IMAP account and convert them into conversations and replies.', 'robodesk'); ?>
                         </p>
                         <div class="bg-gray-50 p-4 rounded-lg">
                             <h4 class="font-medium text-gray-900 mb-2">
                                 <?php esc_html_e('Email Fetching Settings', 'robodesk'); ?></h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Fetch Interval (minutes)', 'robodesk'); ?></label>
                                     <input type="number" name="fetch_interval"
                                         value="<?php echo esc_attr(get_option('robodesk_fetch_interval', 15)); ?>"
                                         class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                         min="1" max="1440" required>
                                 </div>
                                 <div>
                                     <label
                                         class="block text-sm font-medium text-gray-700"><?php esc_html_e('Auto-assign to', 'robodesk'); ?></label>
                                     <select name="default_agent"
                                         class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                         <!-- default options -->
                                         <?php
                                            $robodesk_default_agent = get_option('robodesk_default_agent');
                                            ?>
                                         <option value="" <?php selected((string) $robodesk_default_agent, ''); ?>>
                                             <?php esc_html_e('Least loaded agent (auto)', 'robodesk'); ?></option>

                                         <?php

                                            // Get users by role with IDs for stable option values.
                                            $robodesk_agents = get_users([
                                                'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
                                                'fields'   => ['ID', 'display_name', 'user_login'],
                                            ]);
                                            ?>

                                         <?php foreach ($robodesk_agents as $robodesk_agent): ?>
                                         <?php
                                                $robodesk_agent_id = (int) $robodesk_agent->ID;
                                                $robodesk_agent_name = !empty($robodesk_agent->display_name) ? $robodesk_agent->display_name : $robodesk_agent->user_login;
                                                $robodesk_is_selected = (string) $robodesk_default_agent === (string) $robodesk_agent_id ||
                                                    (string) $robodesk_default_agent === (string) $robodesk_agent_name;
                                                ?>
                                         <option value="<?php echo esc_attr($robodesk_agent_id); ?>"
                                             <?php selected($robodesk_is_selected, true); ?>>
                                             <?php echo esc_html($robodesk_agent_name); ?>
                                         </option>
                                         <?php endforeach; ?>

                                         <?php
                                            $robodesk_default_is_in_agents = false;
                                            foreach ($robodesk_agents as $robodesk_agent) {
                                                if ((string) $robodesk_default_agent === (string) $robodesk_agent->ID || (string) $robodesk_default_agent === (string) $robodesk_agent->display_name) {
                                                    $robodesk_default_is_in_agents = true;
                                                    break;
                                                }
                                            }
                                            ?>

                                         <?php if (!$robodesk_default_is_in_agents && !empty($robodesk_default_agent)): ?>
                                         <option value="<?php echo esc_attr($robodesk_default_agent); ?>" selected>
                                             <?php echo esc_html($robodesk_default_agent); ?>
                                         </option>
                                         <?php endif; ?>


                                     </select>
                                     <p class="mt-1 text-xs text-gray-500">
                                         <?php esc_html_e('Leave this on "Least loaded agent (auto)" to assign fetched email tickets to the least-loaded agent.', 'robodesk'); ?>
                                     </p>
                                 </div>
                             </div>
                         </div>
                         <div class="flex space-x-4">
                             <button id="save-ticket-assignment-settings"
                                 class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                 <i class="fas fa-cog mr-2"></i>
                                 <?php esc_html_e('Save Auto-Fetch', 'robodesk'); ?>
                             </button>
                         </div>
                     </div>
                 </form>

                 <div class="flex space-x-4 mt-6">
                     <button onclick="fetchEmails()"
                         class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                         <i class="fas fa-sync-alt mr-2"></i>
                         <?php esc_html_e('Fetch Emails Now', 'robodesk'); ?>
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
                 $robodesk_advanced_settings = get_option('robodesk_advanced_settings', [
                    'enable_auto_assign' => false,
                    'enable_notifications' => true,
                    'enable_customer_portal' => true,
                    'max_file_upload_size' => 10,
                    'reply_unseen_email_minutes' => 15,
                    'polling_interval' => 30,
                ]);
                ?>
             <div id="advanced-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">
                     <?php esc_html_e('Advanced Settings', 'robodesk'); ?></h3>
                 <form id="advanced-settings-form" class="space-y-6">
                     <input type="hidden" name="robodesk_advanced_settings_nonce"
                         value="<?php echo esc_attr(wp_create_nonce('robodesk_advanced_settings_action')); ?>">
                     <div class="space-y-4">
                         <div class="flex items-center">
                             <input id="enable-auto-assign" type="checkbox"
                                 <?php checked($robodesk_advanced_settings['enable_auto_assign'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-auto-assign" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable auto-assignment of Conversations', 'robodesk'); ?>
                             </label>
                         </div>
                         <div class="flex items-center">
                             <input id="enable-notifications" type="checkbox"
                                 <?php checked($robodesk_advanced_settings['enable_notifications'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-notifications" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable email notifications', 'robodesk'); ?>
                             </label>
                         </div>
                         <?php
                            $robodesk_reply_unseen_minutes = isset($robodesk_advanced_settings['reply_unseen_email_minutes']) ? absint($robodesk_advanced_settings['reply_unseen_email_minutes']) : 15;
                            $robodesk_reply_unseen_enabled = $robodesk_reply_unseen_minutes > 0;
                            ?>
                         <div>
                             <div class="flex items-center gap-2">
                                 <input id="enable-unseen-reply-email" type="checkbox"
                                     <?php checked($robodesk_reply_unseen_enabled, true); ?>
                                     class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                 <label for="enable-unseen-reply-email" class="block text-sm font-medium text-gray-700">
                                     <?php esc_html_e("Email the reply if the client hasn't seen it for", 'robodesk'); ?>
                                 </label>
                             </div>
                             <div id="reply-unseen-email-options" class="mt-2 flex items-center gap-2"
                                 style="<?php echo $robodesk_reply_unseen_enabled ? '' : 'display:none;'; ?>">
                                 <select name="reply_unseen_email_minutes" id="reply-unseen-email-minutes"
                                     class="styled-select block w-40 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                     <?php
                                        $robodesk_reply_minute_options = [5, 10, 15, 30, 60, 120];
                                        foreach ($robodesk_reply_minute_options as $robodesk_reply_minutes) {
                                            echo '<option value="' . esc_attr($robodesk_reply_minutes) . '" ' . selected($robodesk_reply_unseen_minutes, $robodesk_reply_minutes, false) . '>' . esc_html($robodesk_reply_minutes) . '</option>';
                                        }
                                        ?>
                                 </select>
                                  <span class="text-sm text-gray-600"><?php esc_html_e('minutes', 'robodesk'); ?></span>
                              </div>
                          </div>
                          <div>
                              <label for="polling-interval"
                                  class="block text-sm font-medium text-gray-700 mb-1">
                                  <?php esc_html_e('Dashboard polling interval', 'robodesk'); ?>
                              </label>
                              <p class="text-xs text-gray-500 mb-2">
                                  <?php esc_html_e('How often the dashboard checks for new replies and notifications. Shorter intervals feel more real-time but increase server load.', 'robodesk'); ?>
                              </p>
                              <select id="polling-interval" name="polling_interval"
                                  class="styled-select block w-40 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                  <?php
                                     $robodesk_polling_options = [15, 30, 45, 60, 120];
                                     foreach ($robodesk_polling_options as $robodesk_poll_sec) {
                                         echo '<option value="' . esc_attr($robodesk_poll_sec) . '" ' . selected($robodesk_advanced_settings['polling_interval'], $robodesk_poll_sec, false) . '>' . esc_html($robodesk_poll_sec) . ' ' . esc_html__('seconds', 'robodesk') . '</option>';
                                     }
                                     ?>
                              </select>
                          </div>
                          <div class="flex items-center">
                              <input id="enable-customer-portal" type="checkbox"
                                 <?php checked($robodesk_advanced_settings['enable_customer_portal'], true); ?>
                                 class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                             <label for="enable-customer-portal" class="ml-2 block text-sm text-gray-900">
                                 <?php esc_html_e('Enable customer portal', 'robodesk'); ?>
                             </label>
                         </div>
                          <div>
                              <label
                                  class="block text-sm font-medium text-gray-700"><?php esc_html_e('Maximum file upload size (MB)', 'robodesk'); ?></label>
                              <input type="number" name="max_file_upload_size"
                                  value="<?php echo esc_attr($robodesk_advanced_settings['max_file_upload_size']); ?>"
                                  min="1" max="100"
                                  class="styled-input mt-1 block w-32 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                          </div>
                          <div>
                              <label for="blocked-ips"
                                  class="block text-sm font-medium text-gray-700"><?php esc_html_e('Blocked IPs', 'robodesk'); ?></label>
                              <p class="text-xs text-gray-500 mb-2">
                                  <?php esc_html_e('One IP per line. These IPs are blocked from signing up. Add or remove IPs manually.', 'robodesk'); ?>
                              </p>
                              <textarea id="blocked-ips" name="blocked_ips" rows="5"
                                  class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"><?php
                                    $robodesk_blocked_ips = get_option('robodesk_blocked_ips', array());
                                    echo esc_textarea(implode("\n", $robodesk_blocked_ips));
                                  ?></textarea>
                          </div>
                      </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="save-advanced-settings"
                         class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         <?php esc_html_e('Save Advanced Settings', 'robodesk'); ?>
                     </button>
                 </form>
             </div>

             <?php
                // Allow addons to add their settings content (Slack, Discord, etc.)
                // Addons hook into 'robodesk_settings_content' action to render their settings UI
                do_action('robodesk_settings_content');
                ?>
         </div>
     </div>
 </div>