<?php
// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// if a job tracker is not deleted by that particular cron job within 1 day, then we must force clear that.
// otherwise, that particular cronjob will never run. Because all sync cron jobs, check if same job is already running.
$wpdb->query("DELETE FROM wp_running_jobs_tracker WHERE started_on < DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
