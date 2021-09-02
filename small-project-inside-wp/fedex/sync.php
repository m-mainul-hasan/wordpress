<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

try {
    $job_name = 'sync_fedex';

    $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

    if (!count($running_sync)) {

        $wpdb->insert(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name,
                'started_on' => current_time('mysql')
            )
        );

        $limit = 10;

        // Name of fedex order table is fixed.
        $shipped_orders = $wpdb->get_results("
            SELECT * 
            FROM wp_orders_fedex 
            WHERE is_shipped = 1 AND 
            is_cancelled = 0 AND 
            date_shipped IS NOT NULL AND
            date_shipped != '' AND
            tracking_number IS NOT NULL AND
            tracking_number != '' AND
            is_tracking_info_pulled_by_wc = 0 
            ORDER BY id 
            LIMIT {$limit}
        ");

        if (count($shipped_orders)) {
            foreach ($shipped_orders as $order) {
                wc_st_add_tracking_number(
                    $order->wc_order_id,
                    $order->tracking_number,
                    'Fedex',
                    strtotime($order->date_shipped));
                $wpdb->update(
                    'wp_orders_fedex',
                    array('is_tracking_info_pulled_by_wc' => 1),
                    array('id' => $order->id)
                );

                $date_shipped_formatted = date('M d, Y', strtotime($order->date_shipped));

                $wc_order_object = wc_get_order($order->wc_order_id);
                $wc_order_object->set_status('completed', "FedEx shipped this order on {$date_shipped_formatted} with tracking #{$order->tracking_number}.", true);
                $wc_order_object->save();
            }
            echo sprintf("Tracking number of %s new shipped orders are pulled.", count($shipped_orders));
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
    mail('moh.mainul.hasan@gmail.com', 'WP Site cron error', $e->getMessage(), 'From: info@wp_site_name.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}
