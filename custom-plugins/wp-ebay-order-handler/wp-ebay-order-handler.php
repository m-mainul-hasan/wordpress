<?php
/**
 * Plugin Name: WP Ebay Order Handler
 * Plugin URI: 
 * Description: Pushes eBay order to Vendor and Fedex. And sends an email to customer about delivery date.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */

// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function wp_ebay_get_order_items_json_str($order_id) {
    $order = wc_get_order($order_id);
    $order_items = $order->get_items();

    $order_items_array = array();

    foreach($order_items as $order_item) {
        $order_item_str = $order_item->get_quantity() . ' x ' . $order_item->get_name() . ' - ' . get_post_meta($order_item->get_product_id(), 'scientific_name', true);
        array_push($order_items_array, $order_item_str);
    }

    $order_items_json_str = json_encode($order_items_array);
    return $order_items_json_str;
}


function wp_ebay_get_delivery_date() {
    // Get future dates based on today. So set today as base_date.
    $shipping_date = current_time('Y-m-d');

    // How many future dates we wanna get for delivery dates?
    $how_many_days_to_get = 5; // Including the first empty option

    // Init an array for storing delivery dates
    $delivery_dates = array();

    // Do not include following week days in delivery dates
    // date( "w", $timestamp) gives day number of a week. Sunday = 0, Monday = 1, ..., Saturday = 6.
    $exclude_days = array(0, 1, 6);

    $excluded_dates = array(
      '2019-05-28', 
      '2019-07-04', 
      '2019-07-05', 
      '2019-09-03', 
      '2019-11-28', 
      '2019-11-29',
      '2019-12-24',
      '2019-12-25',
      '2019-12-26',
      '2019-01-02',
	);

    $next_day_is_skipped = false;

    // Until we get required number of future dates, keep trying.
    while (count($delivery_dates) < $how_many_days_to_get) {
        // Get shipping date object
        $shipping_date_obj = new DateTime($shipping_date);
        $interval = new DateInterval("P1D");
        // Increment shipping date. We don't do same day delivery.
        $shipping_date_obj = $shipping_date_obj->add($interval); // Shipping date object is incremented.

        // We can't ship for next day for orders received after 7am EST
        // Next day shipping date needs to be cut off at 7am EST
        // Our server time is EST / EDT (when daylight saving is on). So we don't need to think about timezone here.
        // date('G')  = 24-hour format of an hour without leading zeros
        if (intval(current_time('G')) > 6 && !$next_day_is_skipped) {
            $shipping_date_obj = $shipping_date_obj->add($interval); // Shipping date object is incremented.
            $next_day_is_skipped = true;
        }
        // Increment shipping_date so that loop can forward.
        $shipping_date = $shipping_date_obj->format('l, F d, Y'); // Shipping date is incremented.
        $shipping_date_in_time = strtotime($shipping_date);
        $shipping_date_iso = $shipping_date_obj->format('Y-m-d');
        // If current shipping_date is not in excluded_days and not in excluded_dates then push to delivery_dates array.
        if (
            !in_array(date('w', $shipping_date_in_time), $exclude_days)
            &&
            !in_array($shipping_date_iso, $excluded_dates)
        ) {
            // For saturday delivery, add $15 dollar message in the label
            if (date('w', $shipping_date_in_time) == 6) {
                $delivery_dates[$shipping_date_iso] = sprintf("%s ($15 extra charge for Saturday)", $shipping_date);
            } else {
                $delivery_dates[$shipping_date_iso] = $shipping_date;
            }
        }
    }

    $delivery_dates_keys = array_keys($delivery_dates);
    return $delivery_dates_keys[0];
}

add_action('woocommerce_new_order', 'wp_ebay_handle_ebay_order');
function wp_ebay_handle_ebay_order($order_id)
{
    global $wpdb;

    $ebay_username = get_post_meta($order_id, '_codisto_ebayusername', true);

    if ($ebay_username) {
        require_once ABSPATH . 'vendor/constants.php';
        require_once ABSPATH . 'vendor/sync-manager.php';

        $syncManager = SyncManagerFactory::get('any');
        $syncManager->authenticate();
        $vendor_id = $syncManager->getSessionId();

        // Set payload for sdc api
        $payload = new stdClass();
        $payload->session_id = $vendor_id;
        $payload->items = array();

        // Get order details
        $order = wc_get_order($order_id);

        // Get order items
        $order_items = $order->get_items();

        // Add each product of this order in payload
        foreach ($order_items as $order_item) {
            $order_item_product = new WC_Order_Item_Product($order_item->get_id());

            $wc_product = wc_get_product($order_item_product->get_product_id());

            $item = new stdClass();
            $item->sku = $wc_product->get_sku();
            $item->qty = $order_item_product->get_quantity();

            array_push($payload->items, $item);
        }

        // Send addcart api call to sdc
        $syncManager = SyncManagerFactory::get('any');
        $syncManager->setEndPoint('addCart');
        $syncManager::makeApiCall(
            $syncManager->getEndPoint(),
            json_encode($payload)
        );

        unset($payload);

        // Send order confirm api call to sdc
        $payload = new stdClass();
        $payload->session_id = $vendor_id;
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
            $wpdb->insert('wp_vendor_failed_orders', array('wc_order_id' => $order->get_id()));
        } else {
            $order->update_meta_data('_vendor_order_response', json_encode($response));
            $order->update_meta_data('_vendor_order_id', $response->orderid);
            $order->save_meta_data();

            $expected_delivery_date = wp_ebay_get_delivery_date();

            update_post_meta($order->get_id(), 'wp_delivery_date', $expected_delivery_date);

            // save this in fedex order table
            $fedex_order = array(
                'wc_order_id' => $order->get_id(),
                'order_id' => sprintf('R%s', $order->get_id()),
                'hold' => 0,
                'shipping_location_type' => 'personal',
                'shipping_first_name' => $order->get_shipping_first_name(),
                'shipping_last_name' => $order->get_shipping_last_name(),
                'shipping_company' => $order->get_shipping_company(),
                'shipping_address_1' => $order->get_shipping_address_1(),
                'shipping_address_2' => $order->get_shipping_address_2(),
                'shipping_phone' => $order->get_billing_phone(),
                'shipping_email' => $order->get_billing_email(),
                'shipping_send_email_notification' => 1,
                'shipping_city' => $order->get_shipping_city(),
                'shipping_state' => $order->get_shipping_state(),
                'shipping_postcode' => $order->get_shipping_postcode(),
                'shipping_country' => $order->get_shipping_country(),
                'delivery_date' => $expected_delivery_date,
                'is_shipped' => 0,
                'is_cancelled' => 0,
                'is_saturday_delivery' => 0,
                'date_shipped' => null,
                'tracking_number' => null,
                'delivery_type' => 'fedex_priority_overnight',
                'created_on' => date('Y-m-d H:i:s'),
                'updated_on' => date('Y-m-d H:i:s'),
                'is_tracking_info_pulled_by_wc' => 0,
                'order_items' => wp_ebay_get_order_items_json_str($order_id),
            );

            $wpdb->insert('wp_orders_fedex', $fedex_order);

            add_filter('wp_mail_content_type', function ($content_type) {
                return 'text/html';
            });

            add_filter('wp_mail_from_name', function($original_email_from) {
                return 'WP Site';
            });

            $us_date_format = date("d F, Y", strtotime($expected_delivery_date));

            $subject = 'WP Site delivery date for your ebay order';
            $msg = "<p>Hi there,<br/><br/>
                    We have received your order from eBay. Your order is expected to be delivered on {$us_date_format}.<br/><br/>
                    Thank you,<br/>
                    WP Site.com";
            $headers = array("From: support@wp-site.com");
            wp_mail($order->get_billing_email(), $subject, $msg, $headers);
        }
    }
}
