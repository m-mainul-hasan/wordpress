<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
}

$inventory_type = sanitize_text_field($_GET['type']);

if (
    $inventory_type == 'disabled' ||
    $inventory_type == 'static' ||
    $inventory_type == 'managed' ||
    $inventory_type == 'managed_inventory' ||
    $inventory_type == 'live' ||
    $inventory_type == 'live_inventory' ||
    $inventory_type == 'sync_live_image'
) {
    // do nothing
} else {
    exit('Invalid inventory type.');
}

$wpdb->query("DELETE FROM wp_sync_http_requests WHERE inventory_type = '{$inventory_type}'");
$existing_requests = $wpdb->get_results("SELECT * FROM wp_sync_http_requests WHERE inventory_type = '{$inventory_type}'");
if (count($existing_requests)) {
    echo "An entry to sync {$inventory_type} inventory is already in request queue. After successful sync the entry for {$inventory_type} will be automatically removed.";
} else {
    $wpdb->insert(
        'wp_sync_http_requests',
        array(
            'inventory_type' => $inventory_type,
            'request_from_ip' => get_client_ip(),
            'created_at' => current_time('mysql')
        )
    );
    echo "An entry to sync {$inventory_type} inventory is saved in request queue.";
}
