<?php

/**
 * Plugin Name: WP Vendor Cart Sync
 * Plugin URI: 
 * Description: Will sync cart and order with vendor.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit; // Exit if WooCommerce is not activated.
}

require_once ABSPATH . 'vendor/constants.php';
require_once ABSPATH . 'vendor/sync-manager.php';

add_action('wp_footer', 'wp_make_sure_vendor_cookie_exists');
function wp_make_sure_vendor_cookie_exists()
{
    ?>
    <script>
        function reloadIfVendorIdExpires() {
            if (typeof Cookies === 'function') {
                if (Cookies.get('vendor_id') === undefined) {
                    window.location.reload();
                }
            }
        }

        setInterval(reloadIfVendorIdExpires, 10000);
    </script>
    <?php
}

add_action('init', 'wp_set_vendor_cookie');
function wp_set_vendor_cookie()
{
    // vendor_id == wp_session_id
    if (isset($_COOKIE['vendor_id']) && !is_null($_COOKIE['vendor_id'])) {
        // vendor_id is available in cookie.
    } else {
        // Before setting new vendor session id, clear old cart items
        if (!is_null(wc()->cart)) {
            wc()->cart->empty_cart();
        }

        try {
            $syncManager = SyncManagerFactory::get('any');
        } catch (Exception $e) {
            exit("Exception: {$e->getFile()} (#{$e->getLine()}) - {$e->getMessage()}");
        }

        $syncManager->authenticate();
        $vendor_id = $syncManager->getSessionId();

        // vendor session_id is valid for 6 hours. To be extra safe we're setting cookie for 5h.
        setcookie('vendor_id', $vendor_id, time() + 18000, '/');
    }
}

// add_action('woocommerce_add_to_cart', 'wp_vendor_add_to_cart', 10, 4);
function wp_vendor_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id)
{
    global $wpdb;

    // get all cart items
    $cart = wc()->cart->get_cart();

    $session_key = wc()->session->get_session_cookie()[0];

    if ($variation_id) {
        $product_id = $variation_id;
    }

    $wc_product = wc_get_product($product_id);

    // get inventory type to do inventory type specific actions
    $inventory_type = $wc_product->get_meta('vendor_inventory_type', true);

    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];
    $payload->items = array();

    $item = new stdClass();
    $item->sku = $wc_product->get_sku();
    $item->qty = $cart[$cart_item_key]['quantity'];

    array_push($payload->items, $item);

    $syncManager = SyncManagerFactory::get('any');
    $syncManager->setEndPoint('addCart');
    $syncManager::makeApiCall(
        $syncManager->getEndPoint(),
        json_encode($payload)
    );
}

// add_action('woocommerce_remove_cart_item', 'wp_remove_cart_item', 10, 1);
function wp_remove_cart_item($cart_item_key)
{
    global $wpdb;

    $session_key = wc()->session->get_session_cookie()[0];

    // get all cart items
    $cart = wc()->cart->get_cart();

    // get product id by using cart item key.
    if ($cart[$cart_item_key]['variation_id']) {
        $product_id = $cart[$cart_item_key]['variation_id'];
    } else {
        $product_id = $cart[$cart_item_key]['product_id'];
    }

    // get instance of woocommerce product
    $wc_product = wc_get_product($product_id);

    // get inventory type to do inventory type specific actions
    $inventory_type = $wc_product->get_meta('vendor_inventory_type', true);

    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];
    $payload->items = array();

    $item = new stdClass();
    $item->sku = $wc_product->get_sku();
    $item->qty = $cart[$cart_item_key]['quantity'];

    array_push($payload->items, $item);

    $syncManager = SyncManagerFactory::get('any');
    $syncManager->setEndPoint('removeCart');
    $syncManager::makeApiCall(
        $syncManager->getEndPoint(),
        json_encode($payload)
    );
}

add_action('woocommerce_thankyou', 'wp_submit_order_to_vendor', 10, 1);
function wp_submit_order_to_vendor($order_id)
{
    global $wpdb;

    $session_key = wc()->session->get_session_cookie()[0];

    $order = wc_get_order($order_id);
    $customer = get_userdata($order->get_customer_id());

    // Make sure, order is sent to vendor only once. Do not send multiple request on page reload.
    $vendor_order_id_exists = boolval(get_post_meta($order_id, '_vendor_order_id', true));
    if (!$vendor_order_id_exists) {
        $payload = new stdClass();
        $payload->session_id = $_COOKIE['vendor_id'];
        $payload->po = $order->get_id();
        $payload->ship_placedby = 'R' . $order->get_id() . ' / ' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $payload->ship_address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $payload->ship_county = $order->get_shipping_city();
        $payload->ship_city = $order->get_shipping_city();
        $payload->ship_state = $order->get_shipping_state();
        $payload->ship_zipcode = $order->get_shipping_postcode();
        $payload->shipping_nickname = 'R' . $order->get_id() . ' / ' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $payload->shipping_phone = $order->get_billing_phone();
        $payload->shipping_email = $order->get_billing_email();
        $payload->shipping_company = $order->get_shipping_company();
        $payload->shipping_id = $order->get_id();
        $payload->shipping_company = $order->get_shipping_company();
        $payload->order_comment = $order->get_customer_note();

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('confirm');
        $response = $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );

        if ($response->status == 'error') {
            $wpdb->insert('wp_vendor_failed_orders', array('wc_order_id' => $order_id));
        } else {
            $order->update_meta_data('_vendor_order_response', json_encode($response));
            $order->update_meta_data('_vendor_order_id', $response->orderid);
            $order->save_meta_data();
        }

        $payload = new stdClass();
        $payload->session_id = $_COOKIE['vendor_id'];

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('deleteCart');
        $response = $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );

        setcookie('vendor_id', '', time() - 3600, '/');
    }
}


add_action('woocommerce_admin_order_data_after_billing_address', 'wp_custom_billing_fields_display_admin_order_meta', 10, 1);
function wp_custom_billing_fields_display_admin_order_meta($order)
{
    echo '<p><strong>vendor Order ID:</strong><br>' . get_post_meta($order->get_ID(), '_vendor_order_id', true) . '</p>';
}

// add_action('woocommerce_after_cart_item_quantity_update', 'wp_woocommerce_after_cart_item_quantity_update', 10, 3);
function wp_woocommerce_after_cart_item_quantity_update($cart_item_key, $quantity, $old_quantity)
{
    global $wpdb;

    $session_key = wc()->session->get_session_cookie()[0];
    $cart_item = wc()->cart->get_cart_item($cart_item_key);

    if ($cart_item['variation_id']) {
        $product_id = $cart_item['variation_id'];
    } else {
        $product_id = $cart_item['product_id'];
    }

    $wc_product = wc_get_product($product_id);

    // get inventory type to do inventory type specific actions
    $inventory_type = $wc_product->get_meta('vendor_inventory_type', true);

    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];
    $payload->items = array();

    $item = new stdClass();
    $item->sku = $wc_product->get_sku();
    $item->qty = $quantity;

    array_push($payload->items, $item);

    $syncManager = SyncManagerFactory::get('any');
    $syncManager->setEndPoint('addCart');
    $syncManager::makeApiCall(
        $syncManager->getEndPoint(),
        json_encode($payload)
    );
}

function wp_vendor_fallback_get_cart_items()
{
    // get vendor cart items
    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];

    $syncManager = SyncManagerFactory::get('any');
    $syncManager->setEndPoint('cartProducts');
    $vendorCart = $syncManager::makeApiCall(
        $syncManager->getEndPoint(),
        json_encode($payload),
        true
    );

    if ($vendorCart->response) {
        return $vendorCart->response;
    }

    return array();
}

function wp_vendor_fallback_insert_cart_items($items = array())
{
    // check if any items are gone out of stock or does not have enough stock at vendor-end during checkout
    foreach ($items as $item) {
        $payload = new stdClass();
        $payload->session_id = $_COOKIE['vendor_id'];
        $payload->sku = $item->sku;

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('getItems');
        $vendorItemData = $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );

        $vendorItemResponse = boolval($vendorItemData->response);
        $vendorItemStock = intval($vendorItemData->response[0]->in_stock);
        $vendorItemInventoryType = strtolower(trim($vendorItemData->response[0]->inventory_type));

        // Following logic needs to be simplified later.
        if ($vendorItemResponse == false) {
            $wcProductId = wc_get_product_id_by_sku($item->sku);
            $wcProduct = wc_get_product($wcProductId);

            wc_add_notice("{$wcProduct->get_title()} is no longer available to deliver. Please remove this item from your cart, then proceed to checkout.", 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } else {
            if ($vendorItemInventoryType == 'managed' || $vendorItemInventoryType == 'live') {
                if ($vendorItemStock < $item->qty) {
                    $wcProductId = wc_get_product_id_by_sku($item->sku);
                    $wcProduct = wc_get_product($wcProductId);

                    wc_add_notice("{$wcProduct->get_title()} does not have enough stock. Please remove this item from your cart, then proceed to checkout.", 'error');
                    wp_redirect(wc_get_cart_url());
                    exit;
                }
            }
        }
    }

    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];
    $payload->items = array();

    if (count($items)) {
        $payload->items = $items;

        if (isset($syncManager)) {
            unset($syncManager);
        }

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('addCart');
        $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );
    }
}

function wp_vendor_fallback_remove_cart_items($items = array())
{
    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];
    $payload->items = array();

    if (count($items)) {
        $payload->items = $items;

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('removeCart');
        $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );
    }
}

add_action('woocommerce_after_checkout_form', 'wp_action_after_checkout_form_simplified');
function wp_action_after_checkout_form()
{
    $vendorCartProducts = wp_vendor_fallback_get_cart_items();
    $vendorCartProductsSKUs = array();

    // get r4l cart items
    $wpCartProducts = wc()->cart->get_cart_contents();
    $wpCartProductsSKUs = array();

    // loop through vendor cart and create an array of skus
    if (count($vendorCartProducts)) {
        foreach ($vendorCartProducts as $vendorCartProduct) {
            $vendorCartProductsSKUs[$vendorCartProduct->sku] = array(
                'qty' => $vendorCartProduct->qty
            );
        }
    }

    // Handle: items missing in vendor cart
    $items_to_insert = array();
    foreach ($wpCartProducts as $wpCartProduct) {
        if ($wpCartProduct['variation_id']) {
            $wcProduct = wc_get_product($wpCartProduct['variation_id']);
        } else {
            $wcProduct = wc_get_product($wpCartProduct['product_id']);
        }

        array_push($wpCartProductsSKUs, $wcProduct->get_sku());

        if (!array_key_exists($wcProduct->get_sku(), $vendorCartProductsSKUs)) {
            $item = new stdClass();
            $item->sku = $wcProduct->get_sku();
            $item->qty = $wpCartProduct['quantity'];
            array_push($items_to_insert, $item);
        }
    }

    wp_vendor_fallback_insert_cart_items($items_to_insert);

    // Handle: extra items in vendor cart
    $items_to_remove = array();
    foreach ($vendorCartProductsSKUs as $vendorCartSku => $vendorCartItemData) {
        if (!in_array($vendorCartSku, $wpCartProductsSKUs)) {
            $item = new stdClass();
            $item->sku = $vendorCartSku;
            $item->qty = $vendorCartItemData['qty'];
            array_push($items_to_remove, $item);
        }
    }
    wp_vendor_fallback_remove_cart_items($items_to_remove);
}

function wp_action_after_checkout_form_simplified()
{
    // vendor does not have update cart api.
    // so we need to remove then add cart items, otherwise if cart qty is updated, vendor cannot handle that
    $payload = new stdClass();
    $payload->session_id = $_COOKIE['vendor_id'];

    $syncManager = SyncManagerFactory::get('any');
    $syncManager->setEndPoint('deleteCart');
    $syncManager::makeApiCall(
        $syncManager->getEndPoint(),
        json_encode($payload)
    );

    // get r4l cart items
    $wpCartProducts = wc()->cart->get_cart_contents();

    // Handle: items missing in vendor cart
    $items_to_insert = array();
    foreach ($wpCartProducts as $wpCartProduct) {
        if ($wpCartProduct['variation_id']) {
            $wcProduct = wc_get_product($wpCartProduct['variation_id']);
        } else {
            $wcProduct = wc_get_product($wpCartProduct['product_id']);
        }

        $item = new stdClass();
        $item->sku = $wcProduct->get_sku();
        $item->qty = $wpCartProduct['quantity'];
        array_push($items_to_insert, $item);
    }

    wp_vendor_fallback_insert_cart_items($items_to_insert);
}
