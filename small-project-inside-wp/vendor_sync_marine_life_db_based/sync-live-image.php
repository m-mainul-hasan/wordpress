<?php

// Load WordPress without theme support.
define('WP_USE_THEMES', false);
require_once dirname(dirname(__FILE__)) . '/wp-load.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once 'vendor/autoload.php';
require_once ABSPATH . 'vendor_sync_marine_life_db_based/constants.php';

use Intervention\Image\ImageManager;

// get most recent synced live products
try {
    $job_name = 'sync_live_image';

    $running_sync = $wpdb->get_results("SELECT * FROM wp_running_jobs_tracker WHERE job_name = '{$job_name}'");

    if (!count($running_sync)) {

        $wpdb->insert(
            'wp_running_jobs_tracker',
            array(
                'job_name' => $job_name,
                'started_on' => current_time('mysql')
            )
        );

        $eibi_temp_folder_name = 'eibi-live-img';

        // Check vendor eibi live image temporary folder exits to save live image from FTP, otherwise create new one
        if (!is_dir(__DIR__.'/'.$eibi_temp_folder_name)) {
            mkdir(__DIR__.'/'.$eibi_temp_folder_name, 0777, true);
        }
        // FTP Connection
        $host = 'hostname or ftp address';
        $user = 'username';
        $password = 'password';
        $ftp_conn = ftp_connect($host);
        $login = ftp_login($ftp_conn, $user, $password);
        ftp_pasv($ftp_conn, true);
        // Check connection
        if((!$ftp_conn) || (!$login)) {
            echo 'FTP connection has failed! Attempted to connect to '. $host .' for user '.$user.'.';
            exit;
        }

        global $vendor_db_link;
        $sku = 'sku';
        $wysiwyg_folder_path = '/WYSIWYG/';
        $vendor_live_products_sql = "SELECT * FROM products ";
        $vendor_live_products_sql .= "WHERE `sku` IS NOT NULL ";
        $vendor_live_products_sql .= " AND `sku` LIKE '5%'";

        if ($result = $vendor_db_link -> query($vendor_live_products_sql)) {
            while ($product = $result->fetch_object()) {
                $wc_product_id = wc_get_product_id_by_sku($product->$sku);
                if(!empty($wc_product_id)){
                    $wc_product = wc_get_product($wc_product_id);

                    // delete old featured image
                    if (has_post_thumbnail($wc_product_id)) {
                        $attachment_id = get_post_thumbnail_id($wc_product_id);
                        wp_delete_attachment($attachment_id, true);
                    }
                    $vendor_image_number = explode('-', $product->$sku)[1];
                    $vendor_image_name = $vendor_image_number . '.jpg';
                    // Download FTP image
                    // Check file exists on the Vendor FTP Server
                    if(!ftp_get($ftp_conn, __DIR__.'/'.$eibi_temp_folder_name.'/ftp-'.$vendor_image_name, '/WYSIWYG/'.$vendor_image_name, FTP_BINARY)){
                        continue;
                    }
                    $vendor_live_filename = md5(time(). $vendor_image_number);
                    // use third party image library to fetch the image
                    $manager = new ImageManager(array('driver' => 'gd'));
                    $img = $manager->make(__DIR__.'/'.$eibi_temp_folder_name.'/ftp-'.$vendor_image_name);
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $img->save(__DIR__.'/'.$eibi_temp_folder_name.'/'.$vendor_image_name);
                    // upload wp attachment
                    $upload = wp_upload_bits(
                        $vendor_live_filename.'.jpg',
                        null,
                        file_get_contents(__DIR__ . '/'.$eibi_temp_folder_name.'/' . $vendor_image_name)
                    );

                    $filename = $upload['file'];
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => $wc_product->get_name(),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $filename, $wc_product_id);
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($wc_product_id, $attach_id);
                    update_post_meta( $attach_id, '_wp_attachment_image_alt', $wc_product->get_name() );
                    unlink(__DIR__ . '/'.$eibi_temp_folder_name.'/ftp-' . $vendor_image_name);
                    unlink(__DIR__ . '/'.$eibi_temp_folder_name.'/' . $vendor_image_name);
                    echo current_time('Y-m-d H:i:s') . " Updated image for eibi sku: {$product->$sku}" . PHP_EOL;
                }
            }
        }

        // close ftp connection
        ftp_close($ftp_conn);

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
    mail('moh.mainul.hasan@gmail.com', 'WP Site cron error', $e->getMessage(), 'From: info@wp_site.com');
    exit("Exception: " . $e->getMessage() . PHP_EOL);
}