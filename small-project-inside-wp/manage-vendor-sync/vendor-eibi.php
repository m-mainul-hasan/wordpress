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

$wpdb->query("DELETE FROM wp_sync_eibi_job_queue");

$wpdb->insert(
    'wp_sync_eibi_job_queue',
    array(
        'request_from_ip' => get_client_ip(),
        'created_at' => current_time('mysql')
    )
);

$current_time = date('Y-m-d h:i:s');
$msg = "Vendor Eibi sync request from public URL is received at {$current_time}";
mail(
    'moh.mainul.hasan@gmail.com',
    'Vendor Eibi sync request from public URL',
    $msg,
    'From: info@wp_site.com'
);

?>

<!doctype html>
<html>
<head>
    <title>Sync Eibi</title>
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
</head>
<body>
<p>Eibi sync request is received. Within 5 minutes the sync will start.</p>
</body>
</html>
