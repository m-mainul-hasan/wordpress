<?php
/**
 * Plugin Name: WP Admin Order Table Customization
 * Plugin URI: 
 * Description: Add new columns in order table
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */


add_filter('manage_edit-shop_order_columns', 'wp_order_table_columns');
function wp_order_table_columns($columns)
{
    $new_columns = array();

    foreach ($columns as $column_name => $column_info) {

        $new_columns[$column_name] = $column_info;

        echo $column_name;

        if ('order_total' === $column_name) {
            $new_columns['delivery_date'] = 'Delivery Date';
        }
        if ('order_status' === $column_name) {
            $new_columns['vendor_order_status'] = 'Vendor Status';
        }

    }

    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'wp_order_table_columns_content', 10, 2);
function wp_order_table_columns_content($column_name, $post_id)
{
    if ('delivery_date' == $column_name) {
        //Get delivery date from post meta
        $delivery_date = get_post_meta($post_id, 'vendor_delivery_date', true);
        echo date('M d, Y', strtotime($delivery_date));
    }
    if ('vendor_order_status' == $column_name) {
        //Get Vendor Order Status from post meta
        echo get_post_meta($post_id, '_vendor_order_status', true);
    }
}

add_filter('manage_edit-shop_order_sortable_columns', 'wp_order_table_sortable_columns');
function wp_order_table_sortable_columns($columns)
{
    $columns['delivery_date'] = 'delivery_date';
    $columns['vendor_order_status'] = 'vendor_order_status';

    //To make a column 'un-sortable' remove it from the array
    //unset($columns['date']);

    return $columns;
}

add_action('pre_get_posts', 'wp_order_table_columns_query_mods');
function wp_order_table_columns_query_mods($query)
{
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('delivery_date' == $orderby) {
        $query->set('meta_key', 'vendor_delivery_date');
        $query->set('orderby', 'meta_value');
    }

    if ('vendor_order_status' == $orderby) {
        $query->set('meta_key', '_vendor_order_status');
        $query->set('orderby', 'meta_value');
    }
}
