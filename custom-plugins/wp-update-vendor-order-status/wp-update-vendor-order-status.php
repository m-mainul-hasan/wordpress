<?php

/**
 * Plugin Name: WP Update Vendor Order Status
 * Plugin URI: 
 * Description: Update Vendor Order Status in Woocommerce and Vendor Database.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'vendor/constants.php';

/**
 * Add dropdown for select Vendor order status
 */
add_action( 'woocommerce_admin_order_data_after_order_details', 'add_custom_fields_vendor_order_status' );
function add_custom_fields_vendor_order_status($order) {
    // First check this is a Vendor order
    $vendor_order_id = get_post_meta($order->get_id(), '_vendor_order_id', true);

    if(!empty($vendor_order_id)) {
        // Vendor Order Status 1.0,2.0,2.9,3.0,4.0,5.0
        // Get the current Vendor order status from woocommerce
        $current_status = get_post_meta( $order->get_id(), '_vendor_order_status', true );

        echo '<div class="options_group">';
        woocommerce_wp_select( array(
            'id'          => '_vendor_order_status',
            'label'       => __( 'Change Vendor Order Status', 'woocommerce' ),
            'value' => $current_status,
            'options'     => array(
                ''        => __( 'Select Vendor Order Status', 'woocommerce' ),
                '1.0'    => __('1.0', 'woocommerce' ),
                '2.0' => __('2.0', 'woocommerce' ),
                '2.9' => __('2.9', 'woocommerce' ),
                '3.0' => __('3.0', 'woocommerce' ),
                '4.0' => __('4.0', 'woocommerce' ),
                '5.0' => __('5.0', 'woocommerce' ),
            )
        ) );

        echo '</div>';
    }
}

add_action( 'woocommerce_process_shop_order_meta', 'update_vendor_order_status' );
function update_vendor_order_status( $order_id ){
    global $vendor_db_link;

    // First check this is a Vendor order
    $vendor_order_id = get_post_meta($order_id, '_vendor_order_id', true);
    if(!empty($vendor_order_id)) {
        if(in_array($_POST['_vendor_order_status'], array(1.0, 2.0, 2.9, 3.0, 4.0, 5.0))){
            update_post_meta( $order_id, '_vendor_order_status', wc_clean( $_POST['_vendor_order_status'] ) );

            // Update QM shared database status
            $vendor_order_update_sql = "UPDATE orders SET OrderStatus = '{$_POST['_vendor_order_status']}' ";
            $vendor_order_update_sql .= "WHERE OrderId = {$vendor_order_id} AND StoreId = 2";
            $vendor_db_link->query($vendor_order_update_sql);
            $vendor_db_link->close();
        }
    }
}