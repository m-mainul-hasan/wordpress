<?php
/**
 * Plugin Name: WP Guarantee
 * Plugin URI:
 * Description: Create guarantee options.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('DOA_GUARANTEE_PERCENT', 0);
define('FIVE_DAY_GUARANTEE_PERCENT', 30);

function wp_get_marine_life_total() {
    $supplies_cat_ids = array(21, 22);
    $added_marine_life_amount = 0;

    $cart_items = wc()->cart->get_cart_contents();

    foreach($cart_items as $cart_item_key => $cart_item) {
        $product = wc_get_product($cart_item['product_id']);
        $product_cat_ids = $product->get_category_ids();

        if (count(array_intersect($product_cat_ids, $supplies_cat_ids)) ) {
            // This item is from supplies category, do not consider this for free marine life shipping.
        } else {
            $added_marine_life_amount += $cart_item['line_subtotal'];
        }
    }

    return $added_marine_life_amount;
}

/**
 * Add guarantee fields
 */
add_action('wp_guarantee_fields', 'wp_guarantee_field', 30, 1);

function wp_guarantee_field($checkout)
{
    global $woocommerce;
    echo '<div id="wp_guarantee_field_wrapper"><h3>' . __('Marine life guarantee') . '</h3>';

    woocommerce_form_field('wp_guarantee', array(
        'required' => true,
        'type' => 'radio',
        'class' => array('form-row-wide wp-guarantee-fields'),
        'label' => __('Choose your guarantee'),
        'options' => array(
            'DOA' => 'DOA guarantee ($0.00)',
            '5' => sprintf("5-day guarantee ($%s)", wc_format_decimal(
                wp_get_marine_life_total() * (FIVE_DAY_GUARANTEE_PERCENT / 100),
                2
            )),
        ),
        'default' => 'DOA',
    ), $checkout->get_value('wp_guarantee'));

    echo '</div>';
}

/**
 * Process the checkout
 */
add_action('woocommerce_checkout_process', 'wp_guarantee_process');

function wp_guarantee_process()
{
    // Check if set, if its not set add an error.
    if (!$_POST['wp_guarantee']) {
        wc_add_notice(__('Please choose your <b>guarantee type</b>.'), 'error');
    }
}

/**
 * Trigger selection of guarantee option
 */

add_action('wp_footer', 'wp_trigger_guarantee_selection');

function wp_trigger_guarantee_selection()
{
    if (is_checkout()) {
        ?>
        <script>
            jQuery('.wp-guarantee-fields input[type="radio"]').change(function () {
                var self = jQuery(this);
                var selectedGuarantee = self.val();

                var postData = {
                    'selectedGuarantee': selectedGuarantee
                };

                jQuery.post(ajax_url, {action: 'activate_selected_guarantee', postData}, function (returnData) {
                    if (returnData.success) {
                        jQuery(document.body).trigger('update_checkout');
                    }

                });
            });
        </script>
        <?php
    }
}

/**
 * Handle ajax request from guarantee selector
 */

add_action("wp_ajax_activate_selected_guarantee", "activate_selected_guarantee");
add_action("wp_ajax_nopriv_activate_selected_guarantee", "activate_selected_guarantee");

function activate_selected_guarantee()
{
    wp_send_json_success();
}


/**
 * Add guarantee fee
 */
add_action('woocommerce_cart_calculate_fees', 'wp_add_guarantee_fee');

function wp_add_guarantee_fee($cart)
{
    $selected_guarantee = null;

    if (isset($_POST['wp_guarantee'])) {
        $selected_guarantee = $_POST['wp_guarantee'];
    }

    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $parsed_post_data);
        if (isset($parsed_post_data['wp_guarantee'])) {
            $selected_guarantee = $parsed_post_data['wp_guarantee'];
        }
    }

    if (!is_null($selected_guarantee)) {
        if (isset($selected_guarantee)) {
            switch ($selected_guarantee) {
                case '5':
                    $guarantee_cost = wp_get_marine_life_total() * (FIVE_DAY_GUARANTEE_PERCENT / 100);
                    $cart->add_fee('5-day guarantee', wc_format_decimal($guarantee_cost), 2);
                    break;
                default:
                    $cart->add_fee('DOA guarantee', '0.00');
            }
        }
    }
}
