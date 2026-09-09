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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function mmhd_add_custom_menu_item_to_my_account($items)
{

    $new_items = array(
        'mmhd-support' => __('Support', 'metamint-helpdesk')
    );

    $position = array_search('orders', array_keys($items)) + 1;
    $items = array_slice($items, 0, $position, true) +
        $new_items +
        array_slice($items, $position, count($items) - $position, true);

    return $items;
}
add_filter('woocommerce_account_menu_items', 'mmhd_add_custom_menu_item_to_my_account');

//Register the custom endpoint
function mmhd_get_support_endpoint_url($url, $endpoint, $value, $permalink)
{
    if ($endpoint === 'mmhd-support') {
        return mmhd_get_support_page_url();
    }

    return $url;
}
add_filter('woocommerce_get_endpoint_url', 'mmhd_get_support_endpoint_url', 10, 4);
