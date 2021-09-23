<?php
/**
 * Plugin Name: Wp Site Custom Order Status Emails
 * Plugin URI: 
 * Description: Will send email fors custom order statuses
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */


add_action('woocommerce_order_status_changed', 'wp_order_status_changed');

function wp_order_status_changed($order_id) {
    $order = wc_get_order($order_id);
    $order_status = $order->get_status();
    if ($order_status == 'shipped') {
        $wc_emails = wc()->mailer()->get_emails();
        $wc_emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
    }
}
