<?php
if (!defined('ABSPATH')) {
    exit;
}

$steps = mmhd_get_wizard_steps();
$current = mmhd_get_wizard_current_step();
$step_keys = array_keys($steps);
$current_index = array_search($current, $step_keys, true);
if ($current_index === false) {
    $current_index = 2;
}

$is_addon_active  = defined('MMHD_ADDON_VERSION');
$is_licensed      = $is_addon_active && function_exists('mmhd_addon_has_valid_license') && mmhd_addon_has_valid_license();
$purchase_url     = 'https://metamintapps.com/metamint-helpdesk/';
?>
<div class="mmhd-wizard-wrap">
    <div class="mmhd-wizard-step" id="mmhd-wizard-addons">

        <!-- Top Header Navigation -->
        <div class="mmhd-wizard-header">
            <div class="mmhd-wizard-header-brand">
                <div class="mmhd-wizard-brand-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="mmhd-wizard-brand-title">
                    <span><?php esc_html_e('Metamint Helpdesk', 'metamint-helpdesk'); ?></span>
                    <span class="mmhd-wizard-badge"><?php esc_html_e('Step 3 of 4', 'metamint-helpdesk'); ?></span>
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
                            <div class="mmhd-wizard-stepper-dot <?php echo esc_attr($dot_class); ?>">
                                &#10003;
                            </div>
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
                <h2><?php esc_html_e('Extend with Supercharged Addons', 'metamint-helpdesk'); ?></h2>
                <p><?php esc_html_e('All 12 addons unlock with one Pro license. Switch on only what fits your team\'s workflow.', 'metamint-helpdesk'); ?></p>
            </div>
            <?php if ($is_addon_active): ?>
                <span class="mmhd-wizard-badge" style="background:#ecfdf5;color:#059669;border-color:#a7f3d0;">
                    ✓ <?php esc_html_e('Addon Pack Active (v' . MMHD_ADDON_VERSION . ')', 'metamint-helpdesk'); ?>
                </span>
            <?php else: ?>
                <span class="mmhd-wizard-badge"><?php esc_html_e('12 Addons • 1 License', 'metamint-helpdesk'); ?></span>
            <?php endif; ?>
        </div>

        <!-- Addons Top Bar: Filters + Search -->
        <div class="mmhd-wizard-addons-topbar">
            <div class="mmhd-wizard-addons-filter" style="margin-bottom:0;">
                <button type="button" class="mmhd-wizard-filter-btn is-active" data-category="all">
                    <?php esc_html_e('All Addons (12)', 'metamint-helpdesk'); ?>
                </button>
                <button type="button" class="mmhd-wizard-filter-btn" data-category="ai">
                    <?php esc_html_e('AI & Intelligence', 'metamint-helpdesk'); ?>
                </button>
                <button type="button" class="mmhd-wizard-filter-btn" data-category="channels">
                    <?php esc_html_e('Channels', 'metamint-helpdesk'); ?>
                </button>
                <button type="button" class="mmhd-wizard-filter-btn" data-category="commerce">
                    <?php esc_html_e('Commerce & Workflows', 'metamint-helpdesk'); ?>
                </button>
            </div>

            <div class="mmhd-wizard-search-box">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="wz-addons-search" placeholder="<?php esc_attr_e('Search 12 addons (e.g. AI, Woo, WhatsApp)...', 'metamint-helpdesk'); ?>">
            </div>
        </div>

        <!-- 12 Addons Grid -->
        <div class="mmhd-wizard-addons-grid" id="wz-addons-grid">

            <!-- 1. AI Bot -->
            <div class="mmhd-wizard-addon-card" data-categories="ai">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">AI Bot</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Trains on your documentation to answer routine questions automatically 24/7. Integrates ChatGPT & Gemini, WordPress MCP ready.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> ChatGPT & Gemini LLM Integration</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Bot conversation to ticket handover</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Grounded in your own store context</li>
                </ul>
            </div>

            <!-- 2. WooCommerce Integration -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">WooCommerce</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('View customer order history, subscription status, and license keys right beside their ticket with native zero-latency database access.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Live customer order insights in sidebar</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Quick tracking links & refund status</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> No external API middleman needed</li>
                </ul>
            </div>

            <!-- 3. WhatsApp Integration -->
            <div class="mmhd-wizard-addon-card" data-categories="channels">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">WhatsApp</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Connect WhatsApp Business API to manage customer chats, trigger automated bot replies, and configure settings directly in your dashboard.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Official WhatsApp Business API support</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Automated welcome & out-of-office bot</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Synchronized ticket thread history</li>
                </ul>
            </div>

            <!-- 4. Multi Inbox -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Multi Inbox</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Manage multiple email support inboxes or distinct brands under one dashboard with zero extra fees for adding inboxes.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Unlimited separate support inboxes</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Distinct signatures and email addresses</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Single unified agent dashboard</li>
                </ul>
            </div>

            <!-- 5. Zapier Integration -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Zapier</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Connect Metamint Helpdesk with 8,000+ apps. Trigger workflows when tickets are created, updated, or customer ratings are submitted.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Instant webhook triggers & actions</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Sync tickets to CRM, Sheets, Trello</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Full REST API authentication</li>
                </ul>
            </div>

            <!-- 6. Lead Report -->
            <div class="mmhd-wizard-addon-card" data-categories="ai">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge new">New</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Lead Report</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Generate an instant AI lead intelligence summary for any conversation: lead score, buying stage, intent signals, and suggested next follow-up.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> AI lead score & buying stage index</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Actionable recommended follow-up</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Intent evidence highlights</li>
                </ul>
            </div>

            <!-- 7. Slack Integration -->
            <div class="mmhd-wizard-addon-card" data-categories="channels">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge">Dual-Sync</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Slack Integration</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Connect Metamint Helpdesk to Slack channels to receive instant notifications and answer customer tickets straight from your Slack app.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Native dual sync with Slack channels</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Instant notification alerts on new tickets</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Quick thread reply synchronization</li>
                </ul>
            </div>

            <!-- 8. Discord Integration -->
            <div class="mmhd-wizard-addon-card" data-categories="channels">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge">Webhooks</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Discord Integration</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Integrate Discord webhooks to broadcast ticket alerts and real-time status updates directly into your community Discord channels.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Webhook integration for Discord channels</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Priority alert notifications</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Perfect for developer communities</li>
                </ul>
            </div>

            <!-- 9. Filter Emails -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge hot">🔥 Popular</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Filter Emails</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Configure email filtering rules to prevent spam and control which incoming emails can create support tickets via wildcard blacklist patterns.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Domain wildcard blacklist patterns</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Stop spam before ticket creation</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Clean, noise-free support queue</li>
                </ul>
            </div>

            <!-- 10. Signature -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge">Branding</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Signature</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Add customizable HTML email signatures to your ticket replies. Personalize with agent names, roles, avatars, and company branding.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Dynamic agent placeholder tags</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Per-inbox and per-agent templates</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Professional corporate sign-offs</li>
                </ul>
            </div>

            <!-- 11. Canned Replies -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge">Productivity</span>
                </div>
                <h3 class="mmhd-wizard-addon-name">Canned Replies</h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Create pre-saved reply templates for frequent customer questions. Insert responses into ticket replies with 1 click.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Fast 1-click template insertion</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Dynamic customer name placeholders</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Organized by topic and category</li>
                </ul>
            </div>

            <!-- 12. Rating & Review -->
            <div class="mmhd-wizard-addon-card" data-categories="commerce">
                <div class="mmhd-wizard-addon-top">
                    <div class="mmhd-wizard-addon-icon-box">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <span class="mmhd-wizard-addon-badge new">New</span>
                </div>
                <h3 class="mmhd-wizard-addon-name"><?php esc_html_e('Rating & Review', 'metamint-helpdesk'); ?></h3>
                <p class="mmhd-wizard-addon-desc">
                    <?php esc_html_e('Let customers rate closed conversations with emoji, like/dislike, or star scales plus written review feedback. Track assignee satisfaction metrics.', 'metamint-helpdesk'); ?>
                </p>
                <ul class="mmhd-wizard-addon-bullets">
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 3 Rating types: Emojis, 👍/👎, or ⭐</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Customer feedback review comments</li>
                    <li class="mmhd-wizard-addon-bullet"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Agent performance leaderboard</li>
                </ul>
            </div>

        </div>

        <!-- Addons Pack Banner -->
        <?php if ($is_licensed): ?>
        <div class="mmhd-wizard-addon-pack-banner" style="background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-color:#6ee7b7;">
            <div class="mmhd-wizard-addon-pack-content">
                <h3 style="color:#065f46;">
                    <svg style="display:inline;vertical-align:middle;margin-right:6px;" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#059669"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php esc_html_e('Addon Pack Active — All 12 Addons Unlocked', 'metamint-helpdesk'); ?>
                </h3>
                <p style="color:#047857;">
                    <?php
                    printf(
                        /* translators: %s: addon version number */
                        esc_html__('You\'re running Addon Pack v%s with a valid license. All addons are available — enable the ones that fit your workflow below.', 'metamint-helpdesk'),
                        esc_html(MMHD_ADDON_VERSION)
                    );
                    ?>
                </p>
            </div>
            <div class="mmhd-wizard-addon-pack-pricing">
                <span class="mmhd-wizard-addon-pack-amount" style="background:#059669;color:#fff;padding:8px 20px;border-radius:8px;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <?php esc_html_e('Licensed & Active', 'metamint-helpdesk'); ?>
                </span>
            </div>
        </div>
        <?php elseif ($is_addon_active): ?>
        <div class="mmhd-wizard-addon-pack-banner" style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border-color:#fcd34d;">
            <div class="mmhd-wizard-addon-pack-content">
                <h3 style="color:#92400e;">
                    <svg style="display:inline;vertical-align:middle;margin-right:6px;" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#d97706"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
                    <?php esc_html_e('Addon Pack Installed — License Required', 'metamint-helpdesk'); ?>
                </h3>
                <p style="color:#78350f;">
                    <?php esc_html_e('The Addon Pack plugin is installed but no active license was found. Add your license key in Settings → License to unlock all 12 addons.', 'metamint-helpdesk'); ?>
                </p>
            </div>
            <div class="mmhd-wizard-addon-pack-pricing">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mmhd-license')); ?>" class="mmhd-wizard-addon-pack-btn" style="background:#d97706;">
                    <span><?php esc_html_e('Activate License →', 'metamint-helpdesk'); ?></span>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="mmhd-wizard-addon-pack-banner">
            <div class="mmhd-wizard-addon-pack-content">
                <h3><?php esc_html_e('Unlock All 12 Addons — One Simple License', 'metamint-helpdesk'); ?></h3>
                <p>
                    <?php esc_html_e('Never pay per agent or per addon. Get instant access to AI Bot, WooCommerce insights, WhatsApp, Multi Inbox, Zapier, and all future addons released while your license is active.', 'metamint-helpdesk'); ?>
                </p>
            </div>
            <div class="mmhd-wizard-addon-pack-pricing">
                <div class="mmhd-wizard-addon-pack-price">
                    <span class="mmhd-wizard-addon-pack-amount">$50</span>
                    <span class="mmhd-wizard-addon-pack-term"><?php esc_html_e('first year', 'metamint-helpdesk'); ?></span>
                </div>
                <a href="<?php echo esc_url($purchase_url); ?>" target="_blank" rel="noopener noreferrer" class="mmhd-wizard-addon-pack-btn">
                    <span><?php esc_html_e('Get Addon Pack →', 'metamint-helpdesk'); ?></span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Action Bar -->
        <div class="mmhd-wizard-actions">
            <a href="<?php echo esc_url(add_query_arg('step', 'setup', admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-secondary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span><?php esc_html_e('Back', 'metamint-helpdesk'); ?></span>
            </a>
            <div class="mmhd-wizard-actions-group">
                <a href="<?php echo esc_url(add_query_arg('step', 'finish', admin_url('admin.php?page=mmhd-wizard'))); ?>" class="mmhd-wizard-btn mmhd-wizard-btn-ghost">
                    <?php esc_html_e('Skip to Finish', 'metamint-helpdesk'); ?>
                </a>
                <button type="button" id="wz-addons-next" class="mmhd-wizard-btn mmhd-wizard-btn-primary">
                    <span><?php esc_html_e('Save & Continue to Finish', 'metamint-helpdesk'); ?></span>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>

    </div>
</div>
