<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';
if(!is_user_logged_in()) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
// is_admin() not working outside WP. So using wp_get_current_user and then admin capability.
$user = wp_get_current_user();
if (!in_array('administrator', $user->caps)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$orders = $wpdb->get_results("SELECT * FROM wp_site_orders_fedex ORDER BY id DESC LIMIT 10 ");
wp_send_json($orders);
