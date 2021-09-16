<?php

/**
 * Plugin Name: WP Vendor Export
 * Plugin URI: 
 * Description: Will export Processing Today order list for Vendor.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

add_action( 'admin_menu', 'wp_vendor_export_menu' );

function wp_vendor_export_menu() {
    add_menu_page(
        'Vendor Export - Processing Today Orders',
        'Vendor Export',
        'manage_options',
        'wp-vendor-export',
        'wp_vendor_export_page',
        'dashicons-tickets'
    );
}

function wp_vendor_export_page() {
    require_once __DIR__ . '/export-processing-today.php';
}

function wp_vendor_export_csv() {
    if(isset($_POST['wp_action'])) {
        switch ($_POST['wp_action']) {
            case 'vendor_export_processing_today':
                $orders = wc_get_orders(array('post_status' => 'wc-processing-today', 'limit' => -1));
                if ($orders) {
                    $collection = array();
                    foreach($orders as $order) {
                        array_push(
                            $collection,
                            array(
                                get_post_meta($order->get_id(), '_vendor_order_id', true),
                                sprintf("R%d", $order->get_id()),
                                sprintf("%s %s", $order->get_shipping_first_name(), $order->get_shipping_last_name()),
                            )
                        );
                    }
                }

                ob_start();

                $df = fopen("php://output", 'w');

                fputcsv($df, array('Vendor Order Number', 'R4L Order Number', 'Customer Name'));

                foreach ($collection as $row) {
                    fputcsv($df, $row);
                }
                fclose($df);

                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");

                // disposition / encoding on response body
                $file_name = "WP-Orders-" . date('Y_m_d');
                header("Content-Disposition: attachment;filename={$file_name}.csv");
                header("Content-Transfer-Encoding: binary");

                echo ob_get_clean();
                exit;

                break;
        }
    }
}

add_action('admin_init', 'wp_vendor_export_csv');
