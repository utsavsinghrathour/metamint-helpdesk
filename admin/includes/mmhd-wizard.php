<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wizard step definitions
 */
function mmhd_get_wizard_steps()
{
    return array(
        'welcome' => array(
            'label' => __('Welcome', 'metamint-helpdesk'),
            'file'  => MMHD_PLUGIN_PATH . 'admin/includes/mmhd-wizard-steps/wizard-welcome.php',
        ),
        'setup'   => array(
            'label' => __('Setup', 'metamint-helpdesk'),
            'file'  => MMHD_PLUGIN_PATH . 'admin/includes/mmhd-wizard-steps/wizard-setup.php',
        ),
        'addons'  => array(
            'label' => __('Addons', 'metamint-helpdesk'),
            'file'  => MMHD_PLUGIN_PATH . 'admin/includes/mmhd-wizard-steps/wizard-addons.php',
        ),
        'finish'  => array(
            'label' => __('Finish', 'metamint-helpdesk'),
            'file'  => MMHD_PLUGIN_PATH . 'admin/includes/mmhd-wizard-steps/wizard-finish.php',
        ),
    );
}

/**
 * Get current wizard step from URL query parameter.
 */
function mmhd_get_wizard_current_step()
{
    $steps = array_keys(mmhd_get_wizard_steps());

    if (isset($_GET['step']) && in_array(sanitize_key($_GET['step']), $steps, true)) {
        return sanitize_key($_GET['step']);
    }

    return 'welcome';
}

/**
 * Mark wizard as completed
 */
function mmhd_mark_wizard_complete()
{
    update_option('mmhd_wizard_completed', '1', true);
}

/**
 * Check if wizard is already completed
 */
function mmhd_is_wizard_completed()
{
    return get_option('mmhd_wizard_completed', '0') === '1';
}

/**
 * Enqueue wizard assets on wizard admin page.
 */
add_action('admin_enqueue_scripts', 'mmhd_wizard_enqueue_assets');
function mmhd_wizard_enqueue_assets()
{
    $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    if ($current_page !== 'mmhd-wizard') {
        return;
    }

    wp_enqueue_style(
        'mmhd-wizard-styles',
        MMHD_PLUGIN_URL . 'admin/assets/css/mmhd-wizard.css',
        array(),
        MMHD_VERSION
    );

    wp_enqueue_script(
        'mmhd-wizard-js',
        MMHD_PLUGIN_URL . 'admin/assets/js/mmhd-wizard.js',
        array(),
        MMHD_VERSION,
        true
    );

    wp_localize_script('mmhd-wizard-js', 'mmhdWizardData', array(
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mmhd_wizard_action'),
        'adminUrl' => admin_url(),
        'steps'    => array(
            'welcome' => admin_url('admin.php?page=mmhd-wizard&step=welcome'),
            'setup'   => admin_url('admin.php?page=mmhd-wizard&step=setup'),
            'addons'  => admin_url('admin.php?page=mmhd-wizard&step=addons'),
            'finish'  => admin_url('admin.php?page=mmhd-wizard&step=finish'),
        ),
        'dashboardUrl' => admin_url('admin.php?page=mmhd-dashboard'),
        'purchaseUrl'  => 'https://metamintapps.com/metamint-helpdesk/',
    ));
}

/**
 * Wizard page callback
 */
function mmhd_wizard_callback()
{
    if (!current_user_can('mmhd_manage_settings')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'metamint-helpdesk'));
    }

    $steps   = mmhd_get_wizard_steps();
    $current = mmhd_get_wizard_current_step();

    if (!array_key_exists($current, $steps)) {
        $current = 'welcome';
    }

    include $steps[$current]['file'];
}
