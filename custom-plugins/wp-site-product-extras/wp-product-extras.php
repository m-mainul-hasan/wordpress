<?php

/**
 * Plugin Name: WP Site Product Extras
 * Plugin URI: 
 * Description: Create extra product fields.
 * Version: 0.1
 * Author: Mainul Hasan (moh.mainu.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function wp_site_custom_inventory_fields()
{
   // Display the vendor inventory type text field.
   $args = array(
       'id' => 'vendor_inventory_type',
       'label' => __('Vendor inventory type', ''),
       'class' => '',
       'desc_tip' => true,
       'description' => __('Vendor inventory type will be updated by script during sync.', ''),
   );
   woocommerce_wp_text_input($args);
}

add_action('woocommerce_product_options_inventory_product_data', 'wp_site_custom_inventory_fields');

function wp_site_custom_general_fields()
{
    // Display the Vendor cost text field.
    $args = array(
        'id' => 'vendor_cost',
        'label' => __('Vendor cost', ''),
        'class' => 'short wc_input_price',
        'desc_tip' => true,
        'description' => __('Vendor cost will be updated by script during sync.', ''),
    );
    woocommerce_wp_text_input($args);

    // Display scientific name field.
    $args = array(
        'id' => 'scientific_name',
        'label' => __('Scientific name', ''),
        'class' => 'short',
        'desc_tip' => true,
        'description' => __('Enter scientific name here', ''),
    );
    woocommerce_wp_text_input($args);
}

add_action('woocommerce_product_options_general_product_data', 'wp_site_custom_general_fields');


/**
 * Save all custom product fields
 */
function vendor_save_vendor_inventory_type_field($post_id)
{
    $product = wc_get_product($post_id);

   $vendor_inventory_type = isset($_POST['vendor_inventory_type']) ? $_POST['vendor_inventory_type'] : '';
   $product->update_meta_data('vendor_inventory_type', sanitize_text_field($vendor_inventory_type));

    $vendor_cost = isset($_POST['vendor_cost']) ? $_POST['vendor_cost'] : '';
    $product->update_meta_data('vendor_cost', sanitize_text_field($vendor_cost));

    $scientific_name = isset($_POST['scientific_name']) ? $_POST['scientific_name'] : '';
    $product->update_meta_data('scientific_name', sanitize_text_field($scientific_name));

    $product->save();
}

add_action('woocommerce_process_product_meta', 'vendor_save_vendor_inventory_type_field');