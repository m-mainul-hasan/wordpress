<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once ABSPATH . 'vendor_sync_marine_life/constants.php';

try {
    $job_name = 'vendor_fedex';

    $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

    if (!count($running_sync)) {

        $wpdb->insert(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name,
                'started_on' => current_time('mysql')
            )
        );

        $vendor_orders = $wpdb->get_results("
            SELECT *
            FROM wp_vendor_orders_info
            WHERE shipping_status != '4.0' AND
            tracking_number IS NULL
            ORDER BY id
        ");

        if (count($vendor_orders) > 0) {
            foreach ($vendor_orders as $order) {
                $vendor_order_sql = "SELECT * FROM orders ";
                $vendor_order_sql .= "WHERE `OrderId` = '{$order->vendor_order_id}'";
                $vendor_order_sql .= " AND `OrderStatus` = '4.0'";
                $vendor_order_sql .= " AND `StoreId` = 2";

                if ($result = $vendor_db_link -> query($vendor_order_sql)) {
                    while ($vendor_order = $result->fetch_object()) {
                        wc_st_add_tracking_number(
                            $order->wc_order_id,
                            $vendor_order->TrackingNumber,
                            'Fedex',
                            strtotime($vendor_order->OrderShipDate)
                        );
                        $wpdb->update(
                            'wp_vendor_orders_info',
                            array('shipping_status' => 4.0, 'tracking_number' => $vendor_order->TrackingNumber),
                            array('id' => $order->id)
                        );

                        $date_shipped_formatted = date('M d, Y', strtotime($vendor_order->OrderShipDate));

                        $wc_order_object = wc_get_order($order->wc_order_id);
                        $wc_order_object->set_status('completed', "FedEx shipped this order on {$date_shipped_formatted} with tracking #{$vendor_order->TrackingNumber}.", true);
                        $wc_order_object->save();

                    }
                    $result -> free_result();
                }
            }
           echo sprintf("Tracking number of %s new shipped orders are pulled.", count($vendor_orders));
        } else {
            echo "No new shipped orders found to pull tracking number.";
        }

        $wpdb->delete(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name
            )
        );
    } else {
        exit('Already running job: ' . $job_name);
    }
} catch (Exception $e) {
    mail('moh.mainul.hasan@gmail.com', 'WP site cron error', $e->getMessage(), 'From: info@wp_site.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}
