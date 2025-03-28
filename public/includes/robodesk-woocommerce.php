<?php 
/**
 * Hooks and modifications related to woocommerce are added here
 */

// Might be required later for disabling block support
//  use Automattic\WooCommerce\Packages;

//  add_action( 'plugins_loaded', function() {
//      if ( ! class_exists( Packages::class ) ) {
//          return;
//      }
//      // Dependencies are require for Woo admin screens.
//      if ( ! empty( $_GET['page'] ) && is_admin() && str_starts_with( $_GET['page'], 'wc-' ) ) {
//          return;
//      }
//      $override = new class() extends Packages {
//          public function __construct() {
//          }
//          public function remove_woocommerce_blocks() : void {
//              unset( static::$packages['woocommerce-blocks'] );
//          }
//      };
//      $override->remove_woocommerce_blocks();
//  }, 6 );


// Add Support menu item to My Account page

function robodesk_add_custom_menu_item_to_my_account($items)
{

    $new_items = array(
        'robodesk-support' => __('Support', 'robodesk')
    );

    $position = array_search('orders', array_keys($items)) + 1;
    $items = array_slice($items, 0, $position, true) +
        $new_items +
        array_slice($items, $position, count($items) - $position, true);

    return $items;
}
add_filter('woocommerce_account_menu_items', 'robodesk_add_custom_menu_item_to_my_account');

//Register the custom endpoint
function robodesk_add_support_endpoint()
{
    add_rewrite_endpoint('robodesk-support', EP_ROOT | EP_PAGES);
}
add_action('init', 'robodesk_add_support_endpoint');

//Handle the redirect
function robodesk_handle_support_redirect()
{
    global $wp_query;

    if (isset($wp_query->query_vars['robodesk-support']) && !is_admin()) {
        wp_redirect(site_url('/dashboard/'));
        exit;
    }
}
add_action('template_redirect', 'robodesk_handle_support_redirect');

/**
 * Flush rewrite rules on activation
 */
function robodesk_flush_rewrite_rules()
{
    robodesk_add_support_endpoint();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'robodesk_flush_rewrite_rules');