<?php

/**
 * Plugin Name: WP Fedex Table View
 * Plugin URI:
 * Description: Just display Fedex Order Table view.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */

add_action( 'admin_menu', 'wp_fedex_order_view_menu' );

function wp_fedex_order_view_menu() {
    add_menu_page(
        'Fedex Order Table',
        'Fedex Table',
        'manage_options',
        'wp-fedex-order-table',
        'wp_fedex_order_table',
        'dashicons-buddicons-groups'
    );
}

function wp_fedex_order_table() {
    require_once __DIR__ . '/fedex-table-view.php';
}