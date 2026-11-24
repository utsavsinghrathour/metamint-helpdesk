<?php
if (!defined('ABSPATH')) {
    exit;
}

$steps = mmhd_get_wizard_steps();
$current = mmhd_get_wizard_current_step();
$step_keys = array_keys($steps);
$current_index = array_search($current, $step_keys, true);
if ($current_index === false) {
    $current_index = 0;
}
$logo_url = file_exists(MMHD_PLUGIN_PATH . 'admin/assets/images/mmhd-logo.png')
    ? MMHD_PLUGIN_URL . 'admin/assets/images/mmhd-logo.png'
    : '';
?>
<div class="mmhd-wizard-wrap">
    <div class="mmhd-wizard-step" id="mmhd-wizard-welcome">

        <!-- Top Header Navigation -->
        <div class="mmhd-wizard-header">
            <div class="mmhd-wizard-header-brand">
                <div class="mmhd-wizard-brand-icon">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="Metamint Helpdesk">
                    <?php else: ?>
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <?php endif; ?>
                </div>
                <div class="mmhd-wizard-brand-title">
                    <span><?php esc_html_e('Metamint Helpdesk', 'metamint-helpdesk'); ?></span>
                    <span class="mmhd-wizard-badge"><?php esc_html_e('Quick Setup', 'metamint-helpdesk'); ?></span>
                </div>
            </div>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?mmhd_skip_wizard=1'), 'mmhd_skip_wizard')); ?>" class="mmhd-wizard-exit-btn">
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

        <!-- Hero Section -->
        <div class="mmhd-wizard-hero">
            <span class="mmhd-wizard-hero-badge"><?php esc_html_e('AI-Native Helpdesk for WordPress', 'metamint-helpdesk'); ?></span>
            <div class="mmhd-wizard-hero-icon">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Metamint">
                <?php else: ?>
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <?php endif; ?>
            </div>
            <h1 class="mmhd-wizard-hero-title"><?php esc_html_e('Welcome to Metamint Helpdesk', 'metamint-helpdesk'); ?></h1>
            <p class="mmhd-wizard-hero-subtitle">
                <?php esc_html_e('Live chat, AI-powered ticketing, and a unified support portal directly inside WordPress — no external SaaS subscriptions, and 100% data ownership on your own server.', 'metamint-helpdesk'); ?>
            </p>
        </div>

        <!-- What You Will Configure Grid -->
        <div class="mmhd-wizard-features">
            <div class="mmhd-wizard-section-heading">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?php esc_html_e('What you\'ll set up in less than 3 minutes', 'metamint-helpdesk'); ?></span>
            </div>
            <div class="mmhd-wizard-features-grid">
                <div class="mmhd-wizard-feature-card">
                    <div class="mmhd-wizard-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="mmhd-wizard-feature-title"><?php esc_html_e('Helpdesk & Live Chat', 'metamint-helpdesk'); ?></div>
                    <div class="mmhd-wizard-feature-desc"><?php esc_html_e('Configure your support email, primary theme color, and customer live chat widget.', 'metamint-helpdesk'); ?></div>
                </div>

                <div class="mmhd-wizard-feature-card">
                    <div class="mmhd-wizard-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="mmhd-wizard-feature-title"><?php esc_html_e('Departments & Priorities', 'metamint-helpdesk'); ?></div>
                    <div class="mmhd-wizard-feature-desc"><?php esc_html_e('Organize conversations into clear categories and set urgency color badges.', 'metamint-helpdesk'); ?></div>
                </div>

                <div class="mmhd-wizard-feature-card">
                    <div class="mmhd-wizard-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="mmhd-wizard-feature-title"><?php esc_html_e('Team & Knowledge Base', 'metamint-helpdesk'); ?></div>
                    <div class="mmhd-wizard-feature-desc"><?php esc_html_e('Assign Support Managers and Staff roles, plus create self-service FAQs.', 'metamint-helpdesk'); ?></div>
                </div>

                <div class="mmhd-wizard-feature-card">
                    <div class="mmhd-wizard-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="mmhd-wizard-feature-title"><?php esc_html_e('12 Supercharged Addons', 'metamint-helpdesk'); ?></div>
                    <div class="mmhd-wizard-feature-desc"><?php esc_html_e('Connect AI Bot, WooCommerce, WhatsApp, Slack, Zapier, and Multi-Inbox.', 'metamint-helpdesk'); ?></div>
                </div>
            </div>
        </div>

        <!-- Value Propositions (from official site) -->
        <div class="mmhd-wizard-values">
            <div class="mmhd-wizard-value-card">
                <div class="mmhd-wizard-value-header">
                    <div class="mmhd-wizard-value-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div class="mmhd-wizard-value-title"><?php esc_html_e('Omni-Channel Support', 'metamint-helpdesk'); ?></div>
                </div>
                <p class="mmhd-wizard-value-desc">
                    <?php esc_html_e('Whether customers contact you from live chat, email, or a frontend form, every inquiry converts into a structured conversation on one dashboard.', 'metamint-helpdesk'); ?>
                </p>
            </div>

            <div class="mmhd-wizard-value-card">
                <div class="mmhd-wizard-value-header">
                    <div class="mmhd-wizard-value-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="mmhd-wizard-value-title"><?php esc_html_e('100% Data Sovereignty', 'metamint-helpdesk'); ?></div>
                </div>
                <p class="mmhd-wizard-value-desc">
                    <?php esc_html_e('Your customer conversations live on your own database. No third-party data tracking, no per-agent SaaS fee surprises, and complete privacy compliance.', 'metamint-helpdesk'); ?>
                </p>
            </div>

            <div class="mmhd-wizard-value-card">
                <div class="mmhd-wizard-value-header">
                    <div class="mmhd-wizard-value-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="mmhd-wizard-value-title"><?php esc_html_e('All Addons, One License', 'metamint-helpdesk'); ?></div>
                </div>
                <p class="mmhd-wizard-value-desc">
                    <?php esc_html_e('Never pay for each addon individually. AI Bot, WooCommerce insights, WhatsApp, Slack, and 8+ more addons are unlocked in one convenient pack.', 'metamint-helpdesk'); ?>
                </p>
            </div>
        </div>

        <!-- Navigation Action -->
        <div class="mmhd-wizard-actions">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?mmhd_skip_wizard=1'), 'mmhd_skip_wizard')); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-ghost">
                <?php esc_html_e('Skip to Dashboard', 'metamint-helpdesk'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('step', 'setup', admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-primary">
                <span><?php esc_html_e('Get Started with Setup', 'metamint-helpdesk'); ?></span>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

    </div>
</div>
