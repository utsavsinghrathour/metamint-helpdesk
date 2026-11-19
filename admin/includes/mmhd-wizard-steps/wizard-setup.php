<?php
if (!defined('ABSPATH')) {
    exit;
}

$steps = mmhd_get_wizard_steps();
$current = mmhd_get_wizard_current_step();
$step_keys = array_keys($steps);
$current_index = array_search($current, $step_keys, true);
if ($current_index === false) {
    $current_index = 1;
}

// Prefilled settings
$site_name = get_option('mmhd_site_name', get_bloginfo('name') . ' Support');
$admin_email = get_option('mmhd_admin_email', get_option('admin_email'));
$theme_color = get_option('mmhd_theme_color', '#10b981');
$enable_chat = get_option('mmhd_enable_chat', '1');
$chat_heading = get_option('mmhd_chat_heading', 'Have Questions?');
$welcome_message = get_option('mmhd_welcome_message', 'Have any questions or need assistance? Leave us a message.');
$logo_url = file_exists(MMHD_PLUGIN_PATH . 'admin/assets/images/mmhd-logo.png')
    ? MMHD_PLUGIN_URL . 'admin/assets/images/mmhd-logo.png'
    : '';

// Terms
$departments = get_terms(array(
    'taxonomy'   => 'mmhd_ticket_category',
    'hide_empty' => false,
));
$priorities = get_terms(array(
    'taxonomy'   => 'mmhd_ticket_priority',
    'hide_empty' => false,
));

$faq_count = wp_count_posts('mmhd_faq');
$notice_count = wp_count_posts('mmhd_notice');
?>
<div class="mmhd-wizard-wrap">
    <div class="mmhd-wizard-step" id="mmhd-wizard-setup">

        <!-- Top Header Navigation -->
        <div class="mmhd-wizard-header">
            <div class="mmhd-wizard-header-brand">
                <div class="mmhd-wizard-brand-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="mmhd-wizard-brand-title">
                    <span><?php esc_html_e('Metamint Helpdesk', 'metamint-helpdesk'); ?></span>
                    <span class="mmhd-wizard-badge"><?php esc_html_e('Step 2 of 4', 'metamint-helpdesk'); ?></span>
                </div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mmhd-dashboard')); ?>" class="mmhd-wizard-exit-btn">
                <span><?php esc_html_e('Exit to Dashboard', 'metamint-helpdesk'); ?></span>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        </div>

        <!-- Progress Stepper -->
        <div class="mmhd-wizard-stepper">
            <?php foreach ($step_keys as $index => $key):
                $step_item = $steps[$key];
                $is_completed = $index < $current_index;
                $is_current = $key === $current;
                $dot_class = $is_completed ? 'is-completed' : ($is_current ? 'is-current' : 'is-pending');
            ?>
                <div class="mmhd-wizard-stepper-item">
                    <?php if ($is_completed): ?>
                        <a href="<?php echo esc_url(add_query_arg('step', $key, admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-stepper-node" title="<?php echo esc_attr(sprintf(__('Go to %s', 'metamint-helpdesk'), $step_item['label'])); ?>">
                            <div class="mmhd-wizard-stepper-dot <?php echo esc_attr($dot_class); ?>">&#10003;</div>
                            <span class="mmhd-wizard-stepper-label"><?php echo esc_html($step_item['label']); ?></span>
                        </a>
                    <?php else: ?>
                        <div class="mmhd-wizard-stepper-node <?php echo $is_current ? 'is-current' : ''; ?>">
                            <div class="mmhd-wizard-stepper-dot <?php echo esc_attr($dot_class); ?>">
                                <?php echo esc_html($index + 1); ?>
                            </div>
                            <span class="mmhd-wizard-stepper-label"><?php echo esc_html($step_item['label']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($index < count($step_keys) - 1): ?>
                    <div class="mmhd-wizard-stepper-line <?php echo $index < $current_index ? 'is-completed' : ''; ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Page Intro Header -->
        <div class="mmhd-wizard-page-intro">
            <div class="mmhd-wizard-page-title-group">
                <h2><?php esc_html_e('Helpdesk Core Setup', 'metamint-helpdesk'); ?></h2>
                <p><?php esc_html_e('Configure your helpdesk branding, live chat, ticket categories, and team roles.', 'metamint-helpdesk'); ?></p>
            </div>
            <span class="mmhd-wizard-badge"><?php esc_html_e('Smart Defaults Active', 'metamint-helpdesk'); ?></span>
        </div>

        <!-- 2-Column Configuration Grid -->
        <div class="mmhd-wizard-grid-2">

            <!-- Card 1: Branding & Live Chat -->
            <div class="mmhd-wizard-card">
                <div class="mmhd-wizard-card-header">
                    <div class="mmhd-wizard-card-title-group">
                        <div class="mmhd-wizard-card-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                        <div>
                            <h3 class="mmhd-wizard-card-title"><?php esc_html_e('Branding & Live Chat', 'metamint-helpdesk'); ?></h3>
                            <div class="mmhd-wizard-card-desc"><?php esc_html_e('How customers identify your support team', 'metamint-helpdesk'); ?></div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 0.875rem;">
                    <label for="wz-site-name" style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.35rem;color:var(--mmhd-slate-700);">
                        <?php esc_html_e('Helpdesk Name', 'metamint-helpdesk'); ?>
                    </label>
                    <input type="text" id="wz-site-name" class="mmhd-wizard-input" style="width:100%;" value="<?php echo esc_attr($site_name); ?>" placeholder="e.g. Acme Support">
                </div>

                <div style="display:grid;grid-template-columns: 2fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                    <div>
                        <label for="wz-admin-email" style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.35rem;color:var(--mmhd-slate-700);">
                            <?php esc_html_e('Support Email', 'metamint-helpdesk'); ?>
                        </label>
                        <input type="email" id="wz-admin-email" class="mmhd-wizard-input" style="width:100%;" value="<?php echo esc_attr($admin_email); ?>" placeholder="support@yourdomain.com">
                    </div>
                    <div>
                        <label for="wz-theme-color" style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.35rem;color:var(--mmhd-slate-700);">
                            <?php esc_html_e('Theme Accent', 'metamint-helpdesk'); ?>
                        </label>
                        <div style="display:flex;align-items:center;gap:0.35rem;">
                            <input type="color" id="wz-theme-color" class="mmhd-wizard-color-picker" value="<?php echo esc_attr($theme_color); ?>">
                            <span style="font-size:0.75rem;color:var(--mmhd-slate-500);font-family:monospace;"><?php echo esc_html($theme_color); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Live Chat Widget Toggle -->
                <div class="mmhd-wizard-toggle-row">
                    <div class="mmhd-wizard-toggle-label">
                        <span class="mmhd-wizard-toggle-title"><?php esc_html_e('Enable Live Chat Widget', 'metamint-helpdesk'); ?></span>
                        <span class="mmhd-wizard-toggle-sub"><?php esc_html_e('Display the floating chat bubble on your website for instant visitors', 'metamint-helpdesk'); ?></span>
                    </div>
                    <label class="mmhd-switch">
                        <input type="checkbox" id="wz-enable-chat" value="1" <?php checked($enable_chat, '1'); ?>>
                        <span class="mmhd-slider"></span>
                    </label>
                </div>

                <div>
                    <label for="wz-chat-heading" style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.35rem;color:var(--mmhd-slate-700);">
                        <?php esc_html_e('Chat Greeting Headline', 'metamint-helpdesk'); ?>
                    </label>
                    <input type="text" id="wz-chat-heading" class="mmhd-wizard-input" style="width:100%;" value="<?php echo esc_attr($chat_heading); ?>" placeholder="Have Questions?">
                </div>
            </div>

            <!-- Live Preview Card (new) -->
            <div class="mmhd-wizard-card">
                <div class="mmhd-wizard-card-header">
                    <div class="mmhd-wizard-card-title-group">
                        <div class="mmhd-wizard-card-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>
                        <div>
                            <h3 class="mmhd-wizard-card-title"><?php esc_html_e('Live Preview', 'metamint-helpdesk'); ?></h3>
                            <div class="mmhd-wizard-card-desc"><?php esc_html_e('Real-time visitor view of your widget', 'metamint-helpdesk'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="mmhd-wizard-preview-container">
                    <div class="mmhd-wizard-preview-header">
                        <span class="mmhd-wizard-preview-tag" id="wz-preview-status-tag"><?php esc_html_e('Live Preview', 'metamint-helpdesk'); ?></span>
                        <span style="font-size:0.6875rem;color:var(--mmhd-slate-400);"><?php esc_html_e('Real-time Visitor View', 'metamint-helpdesk'); ?></span>
                    </div>

                    <!-- Live Widget Mockup (1:1 with Actual Frontend Widget) -->
                    <div class="mmhd-wizard-mockup-widget" id="wz-mockup-widget">
                        <!-- Widget Header matching Actual Widget -->
                        <div class="mmhd-wizard-mockup-header" id="wz-preview-header" style="background-color: <?php echo esc_attr($theme_color); ?>;">
                            <div class="mmhd-wizard-mockup-logo-badge">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Metamint">
                                <?php else: ?>
                                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="mmhd-wizard-mockup-close-btn" id="wz-preview-close" style="color: <?php echo esc_attr($theme_color); ?>;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </div>
                        </div>

                        <!-- Widget Body matching Actual Home Tab -->
                        <div class="mmhd-wizard-mockup-body">
                            <h4 class="mmhd-wizard-mockup-heading" id="wz-preview-title"><?php echo esc_html($chat_heading ? $chat_heading : __('Have Questions?', 'metamint-helpdesk')); ?></h4>
                            <p class="mmhd-wizard-mockup-subtext" id="wz-preview-bubble"><?php esc_html_e('Hi there!', 'metamint-helpdesk'); ?></p>

                            <div class="mmhd-wizard-mockup-input-box" id="wz-preview-input-box" style="border-color: <?php echo esc_attr($theme_color); ?>;">
                                <span class="mmhd-wizard-mockup-placeholder" id="wz-preview-placeholder" style="color: <?php echo esc_attr($theme_color); ?>;">
                                    <?php esc_html_e('Start a conversation...', 'metamint-helpdesk'); ?>
                                </span>
                                <span class="mmhd-wizard-mockup-send" id="wz-preview-send" style="color: <?php echo esc_attr($theme_color); ?>;">
                                    <svg width="15" height="15" viewBox="0 0 17 16" fill="currentColor">
                                        <path d="M15.3728 0.0985297L0.886843 8.45568C0.321158 8.78071 0.39304 9.5683 0.955601 9.80582L4.27783 11.1997L13.2569 3.28637C13.4288 3.13323 13.6726 3.36763 13.5257 3.54577L5.99677 12.7186V15.2345C5.99677 15.9721 6.88749 16.2628 7.32503 15.7283L9.30962 13.3124L13.2038 14.9439C13.6476 15.1314 14.1539 14.8532 14.2351 14.3751L16.4854 0.873613C16.5917 0.242295 15.9135 -0.214004 15.3728 0.0985297Z"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="mmhd-wizard-mockup-faq-note">
                                <?php esc_html_e('No FAQs found.', 'metamint-helpdesk'); ?>
                            </div>
                        </div>

                        <!-- Widget Footer Tabs matching Actual Frontend Widget -->
                        <div class="mmhd-wizard-mockup-footer">
                            <div class="mmhd-wizard-mockup-tab-item is-active" id="wz-preview-tab-home" style="color: <?php echo esc_attr($theme_color); ?>;">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                </svg>
                            </div>
                            <div class="mmhd-wizard-mockup-tab-item">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                </svg>
                                <span class="mmhd-wizard-mockup-tab-dot"></span>
                            </div>
                            <div class="mmhd-wizard-mockup-tab-item">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                </svg>
                            </div>
                            <div class="mmhd-wizard-mockup-tab-item">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 16h-2v-2h2v2zm1.07-7.75l-.9.92C12.45 11.9 12 12.5 12 14h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Chat Trigger Button matching Frontend Widget -->
                    <div class="mmhd-wizard-mockup-floating">
                        <div class="mmhd-wizard-mockup-trigger" id="wz-preview-trigger" style="background-color: <?php echo esc_attr($theme_color); ?>;">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Departments -->
            <div class="mmhd-wizard-card">
                <div class="mmhd-wizard-card-header">
                    <div class="mmhd-wizard-card-title-group">
                        <div class="mmhd-wizard-card-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <h3 class="mmhd-wizard-card-title"><?php esc_html_e('Departments / Categories', 'metamint-helpdesk'); ?></h3>
                            <div class="mmhd-wizard-card-desc"><?php esc_html_e('Route conversations to the right team members', 'metamint-helpdesk'); ?></div>
                        </div>
                    </div>
                    <span class="mmhd-wizard-badge"><?php echo count($departments && !is_wp_error($departments) ? $departments : array()); ?> <?php esc_html_e('Active', 'metamint-helpdesk'); ?></span>
                </div>

                <div class="mmhd-wizard-chip-list" id="wz-dept-list">
                    <?php if (!empty($departments) && !is_wp_error($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                            <div class="mmhd-wizard-chip">
                                <span><?php echo esc_html($dept->name); ?></span>
                                <span class="mmhd-wizard-chip-count"><?php echo absint($dept->count); ?></span>
                                <button type="button" class="mmhd-wizard-chip-remove" data-type="department" data-id="<?php echo esc_attr($dept->term_id); ?>" title="<?php esc_attr_e('Remove department', 'metamint-helpdesk'); ?>">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mmhd-wizard-empty-state">
                            <?php esc_html_e('No departments yet. Add categories below (e.g. Sales, Technical Support).', 'metamint-helpdesk'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mmhd-wizard-add-row">
                    <input type="text" id="wz-new-dept" class="mmhd-wizard-input" placeholder="<?php esc_attr_e('New department name (e.g. Billing, Technical Support)', 'metamint-helpdesk'); ?>">
                    <button type="button" id="wz-add-dept" class="mmhd-wizard-add-btn">
                        <?php esc_html_e('+ Add', 'metamint-helpdesk'); ?>
                    </button>
                </div>

                <div class="mmhd-wizard-presets">
                    <span class="mmhd-wizard-preset-label"><?php esc_html_e('Quick Add:', 'metamint-helpdesk'); ?></span>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="department" data-preset-value="General Support">+ General</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="department" data-preset-value="Technical Support">+ Tech Support</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="department" data-preset-value="Billing & Orders">+ Billing</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="department" data-preset-value="Pre-Sales Questions">+ Pre-Sales</button>
                </div>
            </div>

            <!-- Card 3: Priorities -->
            <div class="mmhd-wizard-card">
                <div class="mmhd-wizard-card-header">
                    <div class="mmhd-wizard-card-title-group">
                        <div class="mmhd-wizard-card-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="mmhd-wizard-card-title"><?php esc_html_e('Ticket Urgency Priorities', 'metamint-helpdesk'); ?></h3>
                            <div class="mmhd-wizard-card-desc"><?php esc_html_e('Visual urgency indicators for incoming requests', 'metamint-helpdesk'); ?></div>
                        </div>
                    </div>
                    <span class="mmhd-wizard-badge"><?php echo count($priorities && !is_wp_error($priorities) ? $priorities : array()); ?> <?php esc_html_e('Levels', 'metamint-helpdesk'); ?></span>
                </div>

                <div class="mmhd-wizard-chip-list" id="wz-priority-list">
                    <?php if (!empty($priorities) && !is_wp_error($priorities)): ?>
                        <?php foreach ($priorities as $priority):
                            $p_color = get_term_meta($priority->term_id, 'priority_color', true) ?: '#10b981';
                        ?>
                            <div class="mmhd-wizard-chip">
                                <span class="mmhd-wizard-chip-color" style="background-color: <?php echo esc_attr($p_color); ?>;"></span>
                                <span><?php echo esc_html($priority->name); ?></span>
                                <span class="mmhd-wizard-chip-count"><?php echo absint($priority->count); ?></span>
                                <button type="button" class="mmhd-wizard-chip-remove" data-type="priority" data-id="<?php echo esc_attr($priority->term_id); ?>" title="<?php esc_attr_e('Remove priority', 'metamint-helpdesk'); ?>">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mmhd-wizard-empty-state">
                            <?php esc_html_e('No priorities yet. Add priority levels with custom badge colors.', 'metamint-helpdesk'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mmhd-wizard-add-row">
                    <input type="text" id="wz-new-priority" class="mmhd-wizard-input" placeholder="<?php esc_attr_e('Priority name (e.g. Critical, Urgent)', 'metamint-helpdesk'); ?>">
                    <input type="color" id="wz-new-priority-color" class="mmhd-wizard-color-picker" value="#ef4444" title="<?php esc_attr_e('Choose badge color', 'metamint-helpdesk'); ?>">
                    <button type="button" id="wz-add-priority" class="mmhd-wizard-add-btn">
                        <?php esc_html_e('+ Add', 'metamint-helpdesk'); ?>
                    </button>
                </div>

                <div class="mmhd-wizard-presets">
                    <span class="mmhd-wizard-preset-label"><?php esc_html_e('Quick Add:', 'metamint-helpdesk'); ?></span>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="priority" data-preset-value="Critical (24h)" data-preset-color="#ef4444">+ Critical</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="priority" data-preset-value="High Urgency" data-preset-color="#f59e0b">+ High</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="priority" data-preset-value="Normal" data-preset-color="#10b981">+ Normal</button>
                    <button type="button" class="mmhd-wizard-preset-pill" data-preset-type="priority" data-preset-value="Low" data-preset-color="#3b82f6">+ Low</button>
                </div>
            </div>

            <!-- Card 4: Team Roles & Self-Service -->
            <div class="mmhd-wizard-card">
                <div class="mmhd-wizard-card-header">
                    <div class="mmhd-wizard-card-title-group">
                        <div class="mmhd-wizard-card-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 01-8 0C5 8.417 5 12.011 7 14h4a2 2 0 000-4h0a2 2 0 010-4z"/></svg>
                        </div>
                        <div>
                            <h3 class="mmhd-wizard-card-title"><?php esc_html_e('Team Roles & Knowledge Base', 'metamint-helpdesk'); ?></h3>
                            <div class="mmhd-wizard-card-desc"><?php esc_html_e('Staff permissions and self-service cut repeat tickets', 'metamint-helpdesk'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Roles overview (informational only) -->
                <div class="mmhd-wizard-role-grid">
                    <div class="mmhd-wizard-role-option">
                        <div class="mmhd-wizard-role-title-row">
                            <span class="mmhd-wizard-role-title"><?php esc_html_e('Support Manager', 'metamint-helpdesk'); ?></span>
                            <span class="mmhd-wizard-role-pill"><?php esc_html_e('Full Admin', 'metamint-helpdesk'); ?></span>
                        </div>
                        <span class="mmhd-wizard-role-desc"><?php esc_html_e('Access to tickets, reports, settings, and addon integrations.', 'metamint-helpdesk'); ?></span>
                    </div>

                    <div class="mmhd-wizard-role-option">
                        <div class="mmhd-wizard-role-title-row">
                            <span class="mmhd-wizard-role-title"><?php esc_html_e('Support Staff', 'metamint-helpdesk'); ?></span>
                            <span class="mmhd-wizard-role-pill"><?php esc_html_e('Agent View', 'metamint-helpdesk'); ?></span>
                        </div>
                        <span class="mmhd-wizard-role-desc"><?php esc_html_e('Access to answer tickets and chat without plugin configuration.', 'metamint-helpdesk'); ?></span>
                    </div>
                </div>

                <!-- Knowledge base quick stats -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                    <div class="mmhd-wizard-info-box">
                        <span><strong><?php echo absint($faq_count->publish ?? 0); ?></strong> <?php esc_html_e('FAQs published', 'metamint-helpdesk'); ?></span>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=mmhd_faq')); ?>" target="_blank">+ Add FAQ</a>
                    </div>
                    <div class="mmhd-wizard-info-box">
                        <span><strong><?php echo absint($notice_count->publish ?? 0); ?></strong> <?php esc_html_e('Notice banners', 'metamint-helpdesk'); ?></span>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=mmhd_notice')); ?>" target="_blank">+ Add Notice</a>
                    </div>
                </div>

                <div class="mmhd-wizard-info-box">
                    <span><?php esc_html_e('Assign roles to existing WordPress users at any time in Users.', 'metamint-helpdesk'); ?></span>
                    <a href="<?php echo esc_url(admin_url('users.php')); ?>" target="_blank"><?php esc_html_e('Users →', 'metamint-helpdesk'); ?></a>
                </div>
            </div>

        </div>

        <!-- Navigation Action Bar -->
        <div class="mmhd-wizard-actions">
            <a href="<?php echo esc_url(add_query_arg('step', 'welcome', admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-secondary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span><?php esc_html_e('Back', 'metamint-helpdesk'); ?></span>
            </a>
            <div class="mmhd-wizard-actions-group">
                <a href="<?php echo esc_url(add_query_arg('step', 'addons', admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-ghost">
                    <?php esc_html_e('Skip to Addons', 'metamint-helpdesk'); ?>
                </a>
                <button type="button" id="wz-setup-next" class="mmhd-wizard-btn mmhd-wizard-btn-primary">
                    <span><?php esc_html_e('Next: Supercharge with Addons', 'metamint-helpdesk'); ?></span>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>

    </div>
</div>
