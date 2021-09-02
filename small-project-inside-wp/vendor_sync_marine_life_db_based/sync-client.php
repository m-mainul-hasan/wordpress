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
        $inventoryType = $argv[1];

        $job_name = null;

        switch ($inventoryType) {
            case 'managed':
                $job_name = 'sync_managed';
                break;

            case 'live':
                $job_name = 'sync_live';
                break;
        }

        $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

        if ( ! count($running_sync) ) {

            $wpdb->insert(
                'wp_running_jobs_tracker',
                array(
                    'job_name' => $job_name,
                    'started_on' => current_time('mysql')
                )
            );

            $syncManager = VENDORSyncManagerFactory::get($inventoryType);
            $syncManager->setDbHandle($wpdb);
            $syncManager->setWcProductFactory(wc()->product_factory);

            $syncManager->sync();
            echo current_time('Y-m-d H:i:s') . ' Data sync between vendor database table and woocommerce ended.' . PHP_EOL;

            $wpdb->delete(
                'wp_running_jobs_tracker',
                array(
                    'job_name' => $job_name
                )
            );
        } else {
            exit('Already running job: ' . $job_name);
        }
    }
} catch (Exception $e) {
    mail('moh.mainul.hasan@gmail.com', 'WP Site cron error', $e->getMessage(), 'From: info@wp_site.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}