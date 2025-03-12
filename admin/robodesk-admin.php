<?php

// Include admin-side post types
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-ticket-post-type.php';
require_once ROBODESK_PLUGIN_PATH . 'admin/includes/post-types/robodesk-faq-post-type.php';

// Add Admin Menu Pages (if needed)
// add_action('admin_menu', 'robodesk_add_admin_menu');
// function robodesk_add_admin_menu() {
// }


// Other admin related functions can go here
add_action('admin_menu', 'robodesk_add_admin_menu');
function robodesk_add_admin_menu() 
{
    add_menu_page(
        'Robodesk',
        'Support',
        'manage_options', 
        'edit.php?post_type=robodesk_ticket',
        '',
        'dashicons-email-alt',
        25
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All Tickets',
        'All Tickets',
        'edit_tickets', // Use custom capability for the submenus
        'edit.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New Ticket',
        'Add New',
        'edit_tickets',
        'post-new.php?post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Origins',
        'Ticket Origins',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_origin&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Ticket Priorities',
        'Ticket Priorities',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_ticket_priority&post_type=robodesk_ticket'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'All FAQs',
        'FAQs',
        'edit_posts',
        'edit.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'Add New FAQ',
        'Add New FAQ',
        'edit_posts',
        'post-new.php?post_type=robodesk_faq'
    );
    add_submenu_page(
        'edit.php?post_type=robodesk_ticket',
        'FAQ Categories',
        'FAQ Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=robodesk_faq_category&post_type=robodesk_faq'
    );
}
