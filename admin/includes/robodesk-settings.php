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
         <!------------------------------------------ overall setting page with all the tabs---------------------------------------->
         <div class="p-6">
             <!------------------------------Basic Settings Tab --------------------------------->

             <div id="basic-tab" class="settings-tab-content">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Settings</h3>
                 <form id="robodesk-basic-settings-form" class="space-y-6">
                     <input type="hidden" name="robodesk_basic_settings_nonce" value="<?php echo wp_create_nonce('robodesk_basic_settings_action'); ?>">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                         <div>
                             <label for="site_name" class="block text-sm font-medium text-gray-700">Site Name</label>
                             <input type="text" name="site_name" id="site_name" value="<?php echo esc_attr(get_option('robodesk_site_name', 'Robodesk Support')); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="admin_email" class="block text-sm font-medium text-gray-700">Admin Email</label>
                             <input type="email" name="admin_email" id="admin_email" value="<?php echo esc_attr(get_option('robodesk_admin_email', 'admin@example.com')); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                         </div>
                         <div>
                             <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                             <select name="timezone" id="timezone" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <?php
                                    $saved_timezone = get_option('robodesk_timezone', 'UTC');
                                    $timezones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'GMT'];
                                    foreach ($timezones as $tz) {
                                        echo '<option value="' . esc_attr($tz) . '"' . selected($saved_timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
                                    }
                                    ?>
                             </select>
                         </div>
                         <!----------------------------- Image Fields ------------------------------>
                         <div>
                             <label for="popover_top_icon" class="block text-sm font-medium text-gray-700">Pop over Top Icon/Image</label>
                             <input type="file" accept="image/*" name="popover_top_icon" id="popover_top_icon" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                             <div class="mt-2 flex items-center gap-2">
                                 <img id="popover-top-icon-preview" src="<?php echo esc_url(get_option('robodesk_popover_top_icon', '')); ?>" alt="Pop over Top Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                 <button type="button" onclick="removeImage('popover-top-icon-preview', 'robodesk_popover_top_icon', 'popover_top_icon')" class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_popover_top_icon', '') ? '' : 'hidden'; ?>">Remove</button>
                             </div>
                             <input type="hidden" name="robodesk_popover_top_icon" id="robodesk_popover_top_icon" value="<?php echo esc_attr(get_option('robodesk_popover_top_icon', '')); ?>">
                         </div>
                         <div>
                             <div class="mt-4">
                                 <label for="theme_color" class="block text-sm font-medium text-gray-700">Theme Color</label>
                                 <input type="color" name="theme_color" id="theme_color" value="<?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>" class="styled-input mt-1 block w-10 h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <div class="mt-2">
                                     <div id="theme-color-preview" class="hidden w-16 h-8 rounded-md border border-gray-300" style="background-color: <?php echo esc_attr(get_option('robodesk_theme_color', '#3B82F6')); ?>;"></div>
                                 </div>
                             </div>
                         </div>
                         <div>
                             <label for="popover_icon" class="block text-sm font-medium text-gray-700">Pop over Icon/Image</label>
                             <input type="file" accept="image/*" name="popover_icon" id="popover_icon" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                             <div class="mt-2 flex items-center gap-2">
                                 <img id="popover-icon-preview" src="<?php echo esc_url(get_option('robodesk_popover_icon', '')); ?>" alt="Pop over Icon Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                 <button type="button" onclick="removeImage('popover-icon-preview', 'robodesk_popover_icon', 'popover_icon')" class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_popover_icon', '') ? '' : 'hidden'; ?>">Remove</button>
                             </div>
                             <input type="hidden" name="robodesk_popover_icon" id="robodesk_popover_icon" value="<?php echo esc_attr(get_option('robodesk_popover_icon', '')); ?>">
                         </div>
                         <div>
                             <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                             <select name="language" id="language" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <?php
                                    $saved_language = get_option('robodesk_language', 'en');
                                    $languages = ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German'];
                                    foreach ($languages as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '"' . selected($saved_language, $code, false) . '>' . esc_html($name) . '</option>';
                                    }
                                    ?>
                             </select>
                         </div>
                         <div>
                             <label for="logo" class="block text-sm font-medium text-gray-700">Logo</label>
                             <input type="file" accept="image/*" name="logo" id="logo" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                             <div class="mt-2 flex items-center gap-2">
                                 <img id="logo-preview" src="<?php echo esc_url(get_option('robodesk_logo', '')); ?>" alt="Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                 <button type="button" onclick="removeImage('logo-preview', 'robodesk_logo', 'logo')" class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_logo', '') ? '' : 'hidden'; ?>">Remove</button>
                             </div>
                             <input type="hidden" name="robodesk_logo" id="robodesk_logo" value="<?php echo esc_attr(get_option('robodesk_logo', '')); ?>">
                         </div>
                         <div>
                             <label for="wide_logo" class="block text-sm font-medium text-gray-700">Wide Logo</label>
                             <input type="file" accept="image/*" name="wide_logo" id="wide_logo" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                             <div class="mt-2 flex items-center gap-2">
                                 <img id="wide-logo-preview" src="<?php echo esc_url(get_option('robodesk_wide_logo', '')); ?>" alt="Wide Logo Preview" class="hidden max-w-[150px] h-auto rounded-md">
                                 <button type="button" onclick="removeImage('wide-logo-preview', 'robodesk_wide_logo', 'wide_logo')" class="text-red-600 hover:text-red-800 text-sm <?php echo get_option('robodesk_wide_logo', '') ? '' : 'hidden'; ?>">Remove</button>
                             </div>
                             <input type="hidden" name="robodesk_wide_logo" id="robodesk_wide_logo" value="<?php echo esc_attr(get_option('robodesk_wide_logo', '')); ?>">
                         </div>
                         <!-- adding a notification sound for chat -->
                         <div>
                             <label for="notification_sound" class="block text-sm font-medium text-gray-700">Notification Sound</label>
                             <select name="robodesk_notification_sound" id="notification_sound" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                 <?php
                                    $audio_files = [
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
                                    $selected_sound = get_option('robodesk_notification_sound', '');
                                    foreach ($audio_files as $label => $url) {
                                        echo '<option value="' . esc_url($url) . '" ' . selected($selected_sound, $url, false) . '>' . ucfirst($label) . '</option>';
                                    }
                                    ?>
                             </select>
                             <div class="mt-2 flex items-center gap-2">
                                 <audio id="notification-sound-preview" controls class="<?php echo $selected_sound ? '' : 'hidden'; ?>">
                                     <source src="<?php echo esc_url($selected_sound); ?>" type="audio/mpeg">
                                     Your browser does not support the audio element.
                                 </audio>

                             </div>
                             <input type="hidden" name="robodesk_notification_sound" id="robodesk_notification_sound" value="<?php echo esc_attr($selected_sound); ?>">
                         </div>
                         <!-- end of notification sound -->
                         <!-- adding a default category of ticket -->
                         <div class="mt-4">
                             <label for="robodesk_default_ticket_category" class="block text-sm font-medium text-gray-700">Default Ticket Category</label>
                             <select name="robodesk_default_ticket_category" id="robodesk_default_ticket_category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                 <option value="">Select a category</option>
                                 <?php
                                    $selected_category = get_option('robodesk_default_ticket_category', '');
                                    $categories = get_terms(array(
                                        'taxonomy'   => 'robodesk_ticket_category',
                                        'hide_empty' => false,
                                    ));
                                    foreach ($categories as $category) {
                                        echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($selected_category, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
                                    }
                                    ?>
                             </select>
                         </div>
                     </div>
                     <hr class="my-6 border-gray-200">
                     <button type="button" id="save-basic-settings" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                         Save Settings
                     </button>
                 </form>
             </div>

             <!----------------------- IMAP Settings load saved settings ------------------->
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

             <!--------------------------- IMAP Settings Tab ---------------------------------------->
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


             <!------------------------------------------ Email Templates Tab --------------------------->

             <div id="templates-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">Email Templates</h3>

                 <div class="bg-[#ebedf5] rounded-lg p-6 text-gray-800 shadow-sm">
                     <div class="flex justify-between items-center">
                         <h2 class="text-base font-semibold text-gray-900">Customize the email templates with Variables</h2>
                         <button onclick="toggleAccordion()" id="toggle-btn"
                             class="text-sm text-blue-600 hover:underline focus:outline-none">
                             Show
                         </button>
                     </div>

                     <div id="accordion-content" class="mt-4 text-sm text-gray-700 leading-relaxed space-y-4 hidden">
                         <p>You can use the following variables in your templates:</p>
                         <ul class="list-disc pl-5 space-y-1">
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_id}</code> – The ID of the ticket</li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_author}</code> – The name of the ticket author</li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_link}</code> – The link to the ticket</li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_title}</code> – The title of the ticket</li>
                             <li><code class="bg-gray-200 px-1 rounded text-sm">{ticket_status}</code> – The status of the ticket</li>
                         </ul>
                     </div>
                 </div>


                 <form id="robodesk_email_template_save" class="space-y-6">
                     <input type="hidden" name="robodesk_email_template_nonce" value="<?php echo wp_create_nonce('robodesk_email_template_action'); ?>">
                     <div class="space-y-6">
                         <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">New Conversation Notification</label>
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
                             <label class="block text-sm font-medium text-gray-700 mb-2">Conversation Closed</label>
                             <div class="editor-toolbar mb-2">
                                 <button type="button" onclick="formatText('bold', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-bold"></i></button>
                                 <button type="button" onclick="formatText('italic', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-italic"></i></button>
                                 <button type="button" onclick="formatText('underline', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-underline"></i></button>
                                 <button type="button" onclick="formatText('insertUnorderedList', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-list-ul"></i></button>
                                 <button type="button" onclick="formatText('insertOrderedList', 'ticket-closed-template')" class="editor-btn"><i class="fas fa-list-ol"></i></button>
                             </div>
                             <div id="ticket-closed-template" class="editor-content" contenteditable="true"><?php echo wp_kses_post(get_option('robodesk_email_template_ticket-closed', 'Your ticket has been closed. Please let us know if you need further assistance.')); ?></div>
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
             <!------------------------------ Fetch Tickets Tab --------------------------------------->
             <div id="fetch-tab" class="settings-tab-content hidden">
                 <h3 class="text-lg font-medium text-gray-900 mb-6">Fetch Conversations</h3>
                 <form id="robodesk-fetch-tickets-form" class="space-y-6">
                     <input type="hidden" name="robodesk_ticket_assignment_nonce" value="<?php echo wp_create_nonce('robodesk_ticket_assignment_action'); ?>">
                     <div class="space-y-6">
                         <p class="text-sm text-gray-600">Use this feature to fetch new emails from your configured IMAP account and convert them into conversations and replies.</p>
                         <div class="bg-gray-50 p-4 rounded-lg">
                             <h4 class="font-medium text-gray-900 mb-2">Email Fetching Settings</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                 <div>
                                     <label class="block text-sm font-medium text-gray-700">Fetch Interval (minutes)</label>
                                     <input type="number" name="fetch_interval" value="<?php echo esc_attr(get_option('robodesk_fetch_interval', 15)); ?>" class="styled-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" min="1" max="1440" required>
                                 </div>
                                 <div>
                                     <label class="block text-sm font-medium text-gray-700">Auto-assign to</label>
                                     <select name="default_agent" class="styled-select mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                         <!-- default options -->
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

                                         <?php if (!in_array($default_agent, $agent_names) && !empty($default_agent)): ?>
                                             <option value="<?php echo esc_attr($default_agent); ?>" selected>
                                                 <?php echo esc_html($default_agent); ?>
                                             </option>
                                         <?php endif; ?>


                                     </select>
                                 </div>
                             </div>
                         </div>
                         <div class="flex space-x-4">
                             <button id="save-ticket-assignment-settings" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                 <i class="fas fa-cog mr-2"></i>
                                 Save Auto-Fetch
                             </button>
                         </div>
                     </div>
                 </form>

                 <div class="flex space-x-4 mt-6">
                     <button onclick="fetchEmails()" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                         <i class="fas fa-sync-alt mr-2"></i>
                         Fetch Emails Now
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
                                 Enable auto-assignment of Conversations
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
         </div>
     </div>
 </div>