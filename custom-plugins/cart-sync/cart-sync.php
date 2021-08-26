<?php

/**
 * Plugin Name: Cart Sync
 * Plugin URI: 
 * Description: Will sync cart and order with Vendor, which will provide the products or goods.
 * Version: 0.1
 * Author: Mainul Hasan (moh.mainu.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit; // Exit if WooCommerce is not activated.
}

// This will hold provider configuration settings,s for example, database connection or API endpoint setting.
require_once ABSPATH . 'vendor-name/constants.php';

add_action('woocommerce_thankyou', 'wordpress_site_submit_order_to_vendor', 10, 1);
function wordpress_site_submit_order_to_vendor($order_id)
{
    global $wpdb, $db_link;

    $order = wc_get_order($order_id);

    // Fedex data such as location, address etc.
    $fedex_hold_loc = 'Fedex hold location';
    $fedex_hold_station = '0';
    $fedex_hold_loc_meta = 'fedex_hold_location';
    $fedex_hold_loc_add1 = '';
    $fedex_hold_loc_city = '';
    $fedex_hold_loc_state = '';
    $fedex_hold_loc_zip_code = '';

    // Make sure, order is sent to vendor only once. Do not send multiple request on page reload.
    $vendor_order_id_exists = boolval(get_post_meta($order_id, '_vendor_order_id', true));
    if (!$vendor_order_id_exists) {
        $ship_via_type = $order->get_shipping_method();
        // Vendor Order Insert Query
        $order_sql = "INSERT INTO `orders` (";
        $order_sql .= "OrderDate, OrderStatus, ShiptoCompany, ShipToFirstName, ShipToLastName, ShipToAddress1, ShipToAddress2, ShipToCity, ";
        $order_sql .= "ShipToState, ShipToZipCode, ShipToCountry, CustomerPhone, CustomeEmail, ShipViaType, ";
        $order_sql .= "OrderTotal, FedExHoldStation, FedExLocationAddress1, FedExLocaionCity, FedExLocaionState, FedExLocaionZipCode, ";
        $order_sql .= "swfOrderId";
        $order_sql .= ") VALUES (";
        $order_sql .= "NOW(), '1.0', '{$order->get_shipping_company()}', '{$order->get_shipping_first_name()}','{$order->get_shipping_last_name()}',";
        $order_sql .= "'{$order->get_shipping_address_1()}', '{$order->get_shipping_address_2()}', '{$order->get_shipping_city()}', '{$order->get_shipping_state()}',";
        $order_sql .= "'{$order->get_shipping_postcode()}', '{$order->get_shipping_country()}', '{$order->get_billing_phone()}', '{$order->get_billing_email()}',";
        $order_sql .= "'{$ship_via_type}', '{$order->get_total()}', ";
        if(strtolower($ship_via_type) == strtolower($fedex_hold_loc)){
            $hold_loc_address = $order->get_meta($fedex_hold_loc_meta);
            $fedex_hold_loc_arr = explode(',', $hold_loc_address);
            $fedex_hold_loc_add1 = $fedex_hold_loc_arr[0];
            $fedex_hold_loc_city = $fedex_hold_loc_arr[1];
            $fedex_hold_loc_state = $fedex_hold_loc_arr[2];
            $fedex_hold_loc_zip_code = $fedex_hold_loc_arr[3];
            $fedex_hold_station = '1';
        }
        $order_sql .= "$fedex_hold_station, '{$fedex_hold_loc_add1}', '{$fedex_hold_loc_city}', '{$fedex_hold_loc_state}', '{$fedex_hold_loc_zip_code}', ";
        $order_sql .= "'{$order->get_id()}')";

        if ($db_link->query($order_sql) === TRUE) {
            $last_vendor_order_id = $db_link->insert_id;
        } else {
            $last_vendor_order_id = '';
            echo "Error: " . $order_sql . "<br>" . $db_link->error;
        }

        if (empty($last_vendor_order_id)) {
            $wpdb->insert('wordpress_site_table_prefix_vendor_failed_orders', array('wc_order_id' => $order_id));
        } else {
            $order->update_meta_data('_vendor_order_response', $last_vendor_order_id.'-Successfully order inserted');
            $order->update_meta_data('_vendor_order_id', $last_vendor_order_id);
            $order->save_meta_data();
        }

        // Get and Loop Over Order Items
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            $sku_size_arr = explode("-", $sku);

            $order_products_sql = "INSERT INTO `orderproducts` (";
            $order_products_sql .= "OrderId, Sku, Size, Price, QtyOrder, DateInserted";
            $order_products_sql .= ") VALUES (";
            $order_products_sql .= "'{$last_vendor_order_id}', '{$sku_size_arr[0]}', '{$sku_size_arr[1]}', '{$item->get_total()}',";
            $order_products_sql .= "'{$item->get_quantity()}', NOW())";
            $db_link->query($order_products_sql);
        }

    }
}


add_action('woocommerce_admin_order_data_after_billing_address', 'wordpress_site_custom_billing_fields_display_admin_order_meta', 10, 1);
function wordpress_site_custom_billing_fields_display_admin_order_meta($order)
{
    echo '<p><strong>Vendor Order ID:</strong><br>' . get_post_meta($order->get_ID(), '_vendor_order_id', true) . '</p>';
}

add_action('woocommerce_after_checkout_form', 'wordpress_site_action_after_checkout_form_simplified');
function wordpress_site_action_after_checkout_form_simplified()
{
    global $db_link;

    // get wordpress site cart items
    $wordpressSiteCartProducts = wc()->cart->get_cart_contents();

    $items_to_insert = array();
    foreach ($wordpressSiteCartProducts as $wordpressSiteCartProduct) {
        if ($wordpressSiteCartProduct['variation_id']) {
            $wcProduct = wc_get_product($wordpressSiteCartProduct['variation_id']);
        } else {
            $wcProduct = wc_get_product($wordpressSiteCartProduct['product_id']);
        }

        $product_sku = $wcProduct->get_sku();
        $item_qty = $wordpressSiteCartProduct['quantity'];
        $vendor_inventory_type = $wcProduct->get_meta('vendor_inventory_type');

        $vendor_products_sql = "SELECT * FROM products ";
        $vendor_products_sql .= "WHERE `vendor_sku` IS NOT NULL ";
        $vendor_products_sql .= " AND `vendor_sku` = '{$product_sku}'";

        if ($result = $db_link -> query($vendor_products_sql)) {
            while ($product = $result->fetch_object()) {
                if(!empty($vendor_inventory_type)){
                    if ($vendor_inventory_type == 'managed' || $vendor_inventory_type == 'live') {
                        if ($product->QtyAvail < $item_qty) {
                            wc_add_notice("{$wcProduct->get_title()} does not have enough stock. Only {$product->QtyAvail} quantity is available. Please remove this item from your cart or update your quantity, then proceed to checkout.", 'error');
                            wp_redirect(wc_get_cart_url());
                            exit;
                        }
                    }
                } // endif
            } // endwhile
            $result -> free_result();
        } // endif
    }
}
