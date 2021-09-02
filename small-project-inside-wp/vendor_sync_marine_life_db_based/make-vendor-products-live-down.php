<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

try {
    $job_name = 'make_vendor_products_live_down';

    $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

    if (!count($running_sync)) {
        $wpdb->insert(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name,
                'started_on' => current_time('mysql')
            )
        );

        // To make a product hidden, the product_id must have entries for both exclude-from-search and
        // exclude-from-catalog in wp_term_relationships table.
        // Step1: Find term_ids of exclude-from-search and exclude-from-catalog from wp_terms table
        $terms = $wpdb->get_results("
            SELECT term_id 
            FROM wp_terms 
            WHERE slug = 'exclude-from-search' OR slug = 'exclude-from-catalog'");
        $term_ids = array();
        foreach ($terms as $term) {
            array_push($term_ids, $term->term_id);
        }
        $term_ids_str = implode(',', $term_ids);

        // Step2: (based on found term_ids from step1), find term_taxonomy_ids from wp_term_taxonomy table
        $term_taxonomies = $wpdb->get_results("
            SELECT term_taxonomy_id 
            FROM wp_term_taxonomy 
            WHERE taxonomy = 'product_visibility' AND term_id IN ($term_ids_str)
        ");
        $term_taxonomy_ids = array();
        foreach($term_taxonomies as $term_taxonomy) {
            array_push($term_taxonomy_ids, $term_taxonomy->term_taxonomy_id);
        }
        $term_taxonomy_ids_str = implode(',', $term_taxonomy_ids);

        $liveProducts = $wpdb->get_results("
            SELECT post_id AS id FROM r4_postmeta WHERE meta_key = 'vendor_inventory_type' AND meta_value = 'live'
        ");

        foreach ($liveProducts as $liveProduct) {
            // Step3: Delete (product_id + term_taxonomy_ids) combination from wp_term_relationships table
            $wpdb->query("DELETE FROM wp_term_relationships WHERE object_id = {$liveProduct->id} AND term_taxonomy_id IN ($term_taxonomy_ids_str)");

            // Step4: Insert (product_id + term_taxonomy_ids) combination into wp_term_relationships table
            foreach($term_taxonomy_ids as $term_taxonomy_id) {
                $wpdb->insert(
                    "wp_term_relationships",
                    array('object_id' => $liveProduct->id, 'term_taxonomy_id' => $term_taxonomy_id)
                );
            }
        }

        $wpdb->delete(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name
            )
        );

        mail(
            'mainul@mvisolutions.com',
            'WP Site - Cronjob run for making WYSIWYG down',
            'Cronjob ran for making WYSIWYG down at ' . current_time('Y-m-d H:i:s'),
            'From: support@wp_site.com'
        );

        echo current_time('Y-m-d H:i:s') . 'Eibi is now down.' . PHP_EOL;
    } else {
        exit('Already running job: ' . $job_name);
    }
} catch (Exception $e) {
    mail('moh.mainul.hasan@gmail.com', 'WP Site cron error', $e->getMessage(), 'From: info@wp_site.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}
