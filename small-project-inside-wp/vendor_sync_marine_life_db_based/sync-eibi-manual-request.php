<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once ABSPATH . 'vendor_sync_marine_life_db_based/constants.php';
require_once ABSPATH . 'vendor_sync_marine_life_db_based/sync-manager.php';

try {
    // Proceed only if WooCommerce is activated.
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $eibi_job_queue = $wpdb->get_results('SELECT * FROM wp_sync_eibi_job_queue');
        if (count($eibi_job_queue)) {
            $inventoryType = 'live';
            $job_name = 'sync_live';

            $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

            if ( ! count($running_sync) ) {

                $wpdb->insert(
                    'wp_running_jobs_tracker',
                    array(
                        'job_name' => $job_name,
                        'started_on' => current_time('mysql')
                    )
                );

                $syncManager = SyncManagerFactory::get($inventoryType);
                $syncManager->setDbHandle($wpdb);
                $syncManager->setWcProductFactory(wc()->product_factory);

                echo current_time('Y-m-d H:i:s') . ' Data sync between vendor table and woocommerce started.' . PHP_EOL;
                $syncManager->sync();
                echo current_time('Y-m-d H:i:s') . ' Data sync between vendor table and woocommerce ended.' . PHP_EOL;

                $wpdb->delete(
                    'wp_running_jobs_tracker',
                    array(
                        'job_name' => $job_name
                    )
                );

                $wpdb->query("DELETE FROM wp_sync_eibi_job_queue");

                $current_time = current_time('Y-m-d h:i:s');
                $msg = "WP Eibi sync completed at {$current_time} for a manual request (via public URL).";
                mail(
                    'moh.mainul.hasan@gmail.com',
                    'WP Eibi sync completed',
                    $msg,
                    'From: info@wp_site.com'
                );
            } else {
                exit('Already running job: ' . $job_name);
            }
        } else {
            exit('No entry found in wp_sync_eibi_job_queue');
        }
    }
} catch (Exception $e) {
    mail('moh.mainul.hasan@gmail.com', 'WP Site cron error', $e->getMessage(), 'From: info@wp_site.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}