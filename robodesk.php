<?php
ob_start();
/**
 * Plugin Name: Robodesk Support Ticket System
 * Description: A support ticket and FAQ system.
 * Version: 1.0.6
 */

// Define constants
define('ROBODESK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ROBODESK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation/Deactivation Hooks
register_activation_hook(__FILE__, 'robodesk_activate');
register_deactivation_hook(__FILE__, 'robodesk_deactivate');

require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-shortcodes.php';

function robodesk_activate()
{
    $admin_role = get_role('administrator'); // Get the administrator role
    if ($admin_role) {
        $admin_role->add_cap('create_tickets');
        $admin_role->add_cap('edit_tickets');
        $admin_role->add_cap('edit_others_tickets');
        $admin_role->add_cap('publish_tickets');
        $admin_role->add_cap('read_private_tickets');
        $admin_role->add_cap('read_ticket');
        $admin_role->add_cap('delete_ticket');
    }
    add_role('robodesk_support_manager', 'Support Manager', array('read' => true, 'manage_options' => true, 'create_tickets' => true, 'edit_tickets' => true, 'edit_others_tickets' => true, 'publish_tickets' => true, 'read_private_tickets' => true, 'read_ticket' => true, 'delete_ticket' => true));
    
    add_role('robodesk_support_staff', 'Support Staff', array('read' => true, 'edit_posts' => true, 'create_tickets' => true, 'edit_tickets' => true, 'read_ticket' => true));
    
    add_role('robodesk_support_customer', 'Support Customer', array('read' => true, 'create_tickets' => true, 'read_ticket' => true));
}

function robodesk_deactivate()
{
    remove_role('robodesk_support_manager');
    remove_role('robodesk_support_staff');
    remove_role('robodesk_support_customer');
}

// Include necessary files
// if (is_admin()) {
    require_once ROBODESK_PLUGIN_PATH . 'admin/robodesk-admin.php';
    
    
// } else {
    require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-functions.php';
    require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-shortcodes.php';
    require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-ajax.php';
    require_once ROBODESK_PLUGIN_PATH . 'public/includes/robodesk-notes.php';
// }

// Enqueue Scripts
add_action('wp_enqueue_scripts', 'robodesk_enqueue_scripts');
function robodesk_enqueue_scripts()
{

    wp_enqueue_script('jquery');
    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'robodesk_enqueue_admin_scripts');
      
    } else {
        
        wp_enqueue_style('robodesk-styles', ROBODESK_PLUGIN_URL . 'public/assets/css/robodesk-styles.css', array(), '1.0');
    
        wp_enqueue_script('robodesk-scripts', ROBODESK_PLUGIN_URL . 'public/assets/js/robodesk-scripts.js', array('jquery'), time(), true);

        wp_localize_script('robodesk-scripts', 'robodesk_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('robodesk_change_ticket_status_nonce'), // Existing nonce
            'create_ticket_nonce' => wp_create_nonce('robodesk_create_ticket_nonce'), // Existing nonce
            'add_ticket_note_nonce' => wp_create_nonce('robodesk_add_ticket_note_nonce'), // Existing nonce
            'faq_nonce' => wp_create_nonce('robodesk_faq_vote_nonce'), // Existing nonce
            'add_credential_nonce' => wp_create_nonce('robodesk_add_credential_nonce'), // New nonce
            'delete_credential_nonce' => wp_create_nonce('robodesk_delete_credential_nonce'), // New nonce
        ));
    }
}


/**
 * Enqueues admin-specific scripts and styles for the Robodesk plugin.
 *
 * This function is hooked into the admin_enqueue_scripts action and ensures
 * that scripts and styles are only loaded on the post.php and post-new.php
 * pages for the 'robodesk_ticket' post type.
 *
 * @param string $hook The current admin page hook suffix.
 */

function robodesk_enqueue_admin_scripts($hook)
{
    global $post;
    wp_enqueue_script('jquery');
    if(!empty($_GET) && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'robodesk_ticket_priority' && $_GET['post_type'] == 'robodesk_ticket' ){
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('custom-taxonomy-order',ROBODESK_PLUGIN_URL . 'admin/assets/js/custom-taxonomy-order.js', array('jquery', 'jquery-ui-sortable'), time(), true);
        wp_localize_script('custom-taxonomy-order', 'taxonomyOrderData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('taxonomy_order_nonce')
        ));
    }

    if ('post.php' != $hook && 'post-new.php' != $hook) return;
    if (!isset($post) || $post->post_type != 'robodesk_ticket') return;

    wp_enqueue_style('robodesk-admin-styles', ROBODESK_PLUGIN_URL . 'admin/assets/css/robodesk-admin-styles.css', array(), '1.0');
   
    wp_enqueue_script('robodesk-admin-scripts', ROBODESK_PLUGIN_URL . 'admin/assets/js/robodesk-admin-scripts.js', array('jquery'), '1.0', true);
    wp_localize_script('robodesk-admin-scripts', 'robodesk_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'ticket_id' => $post->ID, 'nonce' => wp_create_nonce('robodesk_change_ticket_nonce'), 'faq_nonce' => wp_create_nonce('robodesk_faq_vote_nonce'), 'credentials_nonce' => wp_create_nonce('robodesk_credentials_nonce')));
    

}
add_action('admin_enqueue_scripts', 'robodesk_enqueue_admin_scripts');


//@utsav sir code dont remove now
// function robodesk_create_credentials_table()
// {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'robodesk_credentials';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         user_id BIGINT(20) UNSIGNED NOT NULL,
//         name VARCHAR(255) NOT NULL,
//         username VARCHAR(255) NOT NULL,
//         password VARCHAR(255) NOT NULL,
//         url VARCHAR(255),
//         notes TEXT,
//         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
//         PRIMARY KEY (id),
//         KEY user_id (user_id),
//         CONSTRAINT fk_robodesk_credentials_user FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// }
// register_activation_hook(__FILE__, 'robodesk_create_credentials_table');



// Function to create the table
function robodesk_create_credentials_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'robodesk_credentials'; // Add WordPress table prefix
    $charset_collate = $wpdb->get_charset_collate();


    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        CONSTRAINT fk_robodesk_credentials_user FOREIGN KEY (user_id) 
            REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to run the function on plugin activation
register_activation_hook(__FILE__, 'robodesk_create_credentials_table');



function robodesk_encrypt($plaintext, $key)
{
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options = 0, $iv);
    return base64_encode($ciphertext . '::' . $iv);
}

// Decryption function
function robodesk_decrypt($ciphertext, $key)
{
    list($encrypted_data, $iv) = explode('::', base64_decode($ciphertext), 2);
    return openssl_decrypt($encrypted_data, "AES-256-CBC", $key, $options = 0, $iv);
}


function robodesk_hide_admin_bar_for_support_customer()
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('robodesk_support_customer', (array) $user->roles)) {
            show_admin_bar(false);
        }
    }
}
add_action('init', 'robodesk_hide_admin_bar_for_support_customer');



add_action('wp_head', function(){


});