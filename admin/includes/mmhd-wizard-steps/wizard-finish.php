<?php
if (!defined('ABSPATH')) {
    exit;
}

// Mark wizard as completed once user reaches finish screen
if (!mmhd_is_wizard_completed()) {
    mmhd_mark_wizard_complete();
}

$steps = mmhd_get_wizard_steps();
$current = 'finish';
$step_keys = array_keys($steps);
$current_index = 3; // final step

$dept_count = wp_count_terms(array('taxonomy' => 'mmhd_ticket_category', 'hide_empty' => false));
$priority_count = wp_count_terms(array('taxonomy' => 'mmhd_ticket_priority', 'hide_empty' => false));
$chat_enabled = get_option('mmhd_enable_chat', '1') === '1';
?>
<div class="mmhd-wizard-wrap">
    <div class="mmhd-wizard-step" id="mmhd-wizard-finish">

        <!-- Top Header Navigation -->
        <div class="mmhd-wizard-header">
            <div class="mmhd-wizard-header-brand">
                <div class="mmhd-wizard-brand-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="mmhd-wizard-brand-title">
                    <span><?php esc_html_e('Metamint Helpdesk', 'metamint-helpdesk'); ?></span>
                    <span class="mmhd-wizard-badge" style="background:#ecfdf5;color:#059669;border-color:#a7f3d0;"><?php esc_html_e('Setup Complete', 'metamint-helpdesk'); ?></span>
                </div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mmhd-dashboard')); ?>" class="mmhd-wizard-exit-btn">
                <span><?php esc_html_e('Go to Dashboard', 'metamint-helpdesk'); ?></span>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <!-- Progress Stepper (All 4 Completed) -->
        <div class="mmhd-wizard-stepper">
            <?php foreach ($step_keys as $index => $key):
                $step_item = $steps[$key];
            ?>
                <div class="mmhd-wizard-stepper-item">
                    <div class="mmhd-wizard-stepper-node">
                        <div class="mmhd-wizard-stepper-dot is-completed">
                            &#10003;
                        </div>
                        <span class="mmhd-wizard-stepper-label"><?php echo esc_html($step_item['label']); ?></span>
                    </div>
                </div>
                <?php if ($index < count($step_keys) - 1): ?>
                    <div class="mmhd-wizard-stepper-line is-completed"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Celebratory Card -->
        <div class="mmhd-wizard-finish-wrap">
            <div class="mmhd-wizard-confetti-circle">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="mmhd-wizard-finish-title"><?php esc_html_e('You\'re All Set!', 'metamint-helpdesk'); ?></h1>
            <p class="mmhd-wizard-finish-subtitle">
                <?php esc_html_e('Metamint Helpdesk is ready to deliver fast, reliable customer support directly on your WordPress site. Your settings have been saved.', 'metamint-helpdesk'); ?>
            </p>

            <!-- Summary Stats Bar -->
            <div class="mmhd-wizard-summary-bar">
                <div class="mmhd-wizard-summary-pill">
                    <span class="mmhd-wizard-summary-value"><?php echo is_wp_error($dept_count) ? '0' : absint($dept_count); ?></span>
                    <span class="mmhd-wizard-summary-label"><?php esc_html_e('Departments', 'metamint-helpdesk'); ?></span>
                </div>
                <div class="mmhd-wizard-summary-pill">
                    <span class="mmhd-wizard-summary-value"><?php echo is_wp_error($priority_count) ? '0' : absint($priority_count); ?></span>
                    <span class="mmhd-wizard-summary-label"><?php esc_html_e('Urgency Priorities', 'metamint-helpdesk'); ?></span>
                </div>
                <div class="mmhd-wizard-summary-pill">
                    <span class="mmhd-wizard-summary-value" style="color: <?php echo $chat_enabled ? '#10b981' : '#64748b'; ?>;">
                        <?php echo $chat_enabled ? '✓ Enabled' : 'Disabled'; ?>
                    </span>
                    <span class="mmhd-wizard-summary-label"><?php esc_html_e('Live Chat Widget', 'metamint-helpdesk'); ?></span>
                </div>
            </div>

            <!-- Ready-to-Use Shortcodes -->
            <div class="mmhd-wizard-shortcodes-box">
                <div class="mmhd-wizard-shortcodes-header">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span><?php esc_html_e('Frontend Shortcodes for Pages', 'metamint-helpdesk'); ?></span>
                </div>

                <div class="mmhd-wizard-shortcode-item">
                    <div>
                        <span class="mmhd-wizard-shortcode-code">[mmhd_frontend_dashboard]</span>
                        <div class="mmhd-wizard-shortcode-desc"><?php esc_html_e('Customer ticket dashboard & history', 'metamint-helpdesk'); ?></div>
                    </div>
                    <button type="button" class="mmhd-wizard-copy-btn" data-code="[mmhd_frontend_dashboard]">
                        <?php esc_html_e('Copy Shortcode', 'metamint-helpdesk'); ?>
                    </button>
                </div>

                <div class="mmhd-wizard-shortcode-item">
                    <div>
                        <span class="mmhd-wizard-shortcode-code">[mmhd_ticket_form]</span>
                        <div class="mmhd-wizard-shortcode-desc"><?php esc_html_e('Embeddable ticket creation form', 'metamint-helpdesk'); ?></div>
                    </div>
                    <button type="button" class="mmhd-wizard-copy-btn" data-code="[mmhd_ticket_form]">
                        <?php esc_html_e('Copy Shortcode', 'metamint-helpdesk'); ?>
                    </button>
                </div>

                <div class="mmhd-wizard-shortcode-item mmhd-wizard-shortcode-item-faq">
                    <div style="width:100%;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.25rem;">
                            <span class="mmhd-wizard-shortcode-code">[mmhd_faq]</span>
                            <button type="button" class="mmhd-wizard-copy-btn" data-code="[mmhd_faq]">
                                <?php esc_html_e('Copy Default', 'metamint-helpdesk'); ?>
                            </button>
                        </div>
                        <div class="mmhd-wizard-shortcode-desc"><?php esc_html_e('Public knowledge base & FAQ accordion (includes Google FAQPage JSON-LD schema)', 'metamint-helpdesk'); ?></div>

                        <!-- FAQ Parameter Details -->
                        <div class="mmhd-wizard-faq-params">
                            <div class="mmhd-wizard-faq-param-title"><?php esc_html_e('Supported Parameters:', 'metamint-helpdesk'); ?></div>
                            <ul class="mmhd-wizard-faq-param-list">
                                <li>
                                    <code>accordion="true"</code> — <span><?php esc_html_e('Renders FAQs in interactive collapsible accordions (defaults to standard list).', 'metamint-helpdesk'); ?></span>
                                </li>
                                <li>
                                    <code>category="slug"</code> — <span><?php esc_html_e('Filters FAQs by category slug (manage under FAQs → FAQ Categories).', 'metamint-helpdesk'); ?></span>
                                </li>
                            </ul>

                            <div class="mmhd-wizard-faq-presets">
                                <span class="mmhd-wizard-faq-presets-label"><?php esc_html_e('Quick Copy Variants:', 'metamint-helpdesk'); ?></span>
                                <button type="button" class="mmhd-wizard-copy-chip" data-code='[mmhd_faq accordion="true"]' title="<?php esc_attr_e('Copy accordion shortcode', 'metamint-helpdesk'); ?>">
                                    <span>[mmhd_faq accordion="true"]</span>
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                </button>
                                <button type="button" class="mmhd-wizard-copy-chip" data-code='[mmhd_faq category="general" accordion="true"]' title="<?php esc_attr_e('Copy category accordion shortcode', 'metamint-helpdesk'); ?>">
                                    <span>[mmhd_faq category="slug" accordion="true"]</span>
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary CTAs -->
            <div class="mmhd-wizard-finish-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mmhd-dashboard')); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span><?php esc_html_e('Launch Helpdesk Dashboard', 'metamint-helpdesk'); ?></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=mmhd-dashboard&tab=new-ticket&view=new-ticket')); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-secondary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span><?php esc_html_e('Create First Ticket', 'metamint-helpdesk'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="mmhd-wizard-btn mmhd-wizard-btn-secondary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span><?php esc_html_e('Visit Live Site', 'metamint-helpdesk'); ?></span>
                </a>
            </div>

            <p style="font-size:0.8125rem;color:var(--mmhd-slate-400);margin:0;">
                <?php esc_html_e('Need to change anything? You can update all preferences anytime under Metamint Helpdesk → Settings.', 'metamint-helpdesk'); ?>
            </p>
        </div>

    </div>
</div>
