<?php

/**
 * Plugin Name: WP Vendor Status Update Bulk Orders
 * Plugin URI: 
 * Description: Will update vendor status for multiple orders.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI:  https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
require_once ABSPATH . 'vendor/constants.php';

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_filter('bulk_actions-edit-shop_order', 'vendor_status_as_options_bulk_actions_edit_order', 500, 1);
    /**
     * @param $actions
     * @return mixed
     * Add vendor statuses '1.0, 2.0, 2.9, 3.0, 4.0, 5.0' as dropdown options on bulk actions dropdown
     */
    function vendor_status_as_options_bulk_actions_edit_order($actions)
    {
        $actions['1.0'] = __('1.0', 'woocommerce');
        $actions['2.0'] = __('2.0', 'woocommerce');
        $actions['2.9'] = __('2.9', 'woocommerce');
        $actions['3.0'] = __('3.0', 'woocommerce');
        $actions['4.0'] = __('4.0', 'woocommerce');
        $actions['5.0'] = __('5.0', 'woocommerce');
        return $actions;
    }

    add_filter( 'handle_bulk_actions-edit-shop_order', 'update_vendor_status_orders_edit_shop_order', 10, 3 );
    /**
     * @param $redirect_to
     * @param $action
     * @param $post_ids
     * @return mixed|string
     * Will update vendor status on both woocommerce and vendor shared db end
     */
    function update_vendor_status_orders_edit_shop_order( $redirect_to, $action, $post_ids ) {
        // Check action has vendor status
        if ( !in_array($action, array('1.0', '2.0', '2.9', '3.0', '4.0', '5.0')))
            return $redirect_to; // Exit

        global $vendor_db_link;
        $processed_ids = array();
        $vendor_order_update_sql = '';
        foreach ( $post_ids as $post_id ) {
            $order = wc_get_order($post_id);
            $order_id = $order->get_id();
            // First check this is a vendor order
            $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
            if (!empty($vendor_order_id)) {
                update_post_meta($order_id, '_vendor_order_status', wc_clean($action));
                // Update vendor shared database status
                $vendor_order_update_sql .= "UPDATE orders SET OrderStatus = '{$action}' ";
                $vendor_order_update_sql .= "WHERE OrderId = {$vendor_order_id} AND StoreId = 2;";
                $processed_ids[] = $post_id;
            }
        }
        // execute the query for updating vendor shared db orders
        if(!empty($vendor_order_update_sql)){
            $vendor_db_link->multi_query($vendor_order_update_sql);
        }
        $vendor_db_link->close();
        return $redirect_to = add_query_arg( array(
            'vendor_status_update' => $action,
            'processed_count' => count( $processed_ids ),
            'processed_ids' => implode( ',', $processed_ids ),
        ), $redirect_to );
    }


    add_action( 'admin_notices', 'vendor_status_update_admin_notice' );
    /**
     *  The results notice from bulk action on orders
     */
    function vendor_status_update_admin_notice() {
        if ( empty( $_REQUEST['vendor_status_update'] ) ) return; // Exit

        $count = intval( $_REQUEST['processed_count'] );

        printf( '<div id="message" class="updated fade"><p>' .
            _n( "Processed %s Order for vendor Status Update to status {$_REQUEST['vendor_status_update']}",
                "Processed %s Orders for vendor Status Update to status {$_REQUEST['vendor_status_update']}",
                $count,
                'vendor_status_update'
            ) . '</p></div>', $count );
    }

}
