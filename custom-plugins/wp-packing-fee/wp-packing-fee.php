<?php

/**
 * Plugin Name: WP Packing Fee
 * Plugin URI: 
 * Description: Adds packing fee based on products types in cart.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */

/**
 * Packing fee rule:
 * 1 Packing Fee of $9.99 for Animals
 * 1 Packing fee of $9.99 for Supplies
 * If they order both, then $9.99 + $9.99
 */

define('SUPPLIES_PACKING_FEE', 9.99);
define('ANIMAL_PACKING_FEE', 9.99);

add_action('woocommerce_cart_calculate_fees', 'wp_packing_fee');

function wp_packing_fee()
{
    global $woocommerce;

    // Define accessories / supplies category IDs
    $supplies_cat_ids = array(22);

    // Boolean vars to control decisions
    $supplies_packing_fee_applied = false;
    $animal_packing_fee_applied = false;

    // Container for product ids
    $cart_product_ids = array();
    // Container for cart product category ids
    $cart_product_cat_ids = array();

    // Get cart items
    $cart_items = $woocommerce->cart->get_cart();

    // From cart items, populate product_ids and product_category_ids
    foreach ($cart_items as $key => $item) {
        $product = wc_get_product($item['product_id']);
        array_push($cart_product_ids, $product->get_id());
        $item_cat_ids = $product->get_category_ids();
        foreach ($item_cat_ids as $cat_id) {
            array_push($cart_product_cat_ids, $cat_id);
        }
    }

    // Remove duplicates
    $cart_product_cat_ids = array_unique($cart_product_cat_ids);

    /*
     * Scan product_category_ids and apply packing fee
     *
     * Logic:
     *
     * Currently we're considering two types of products. Supplies and Animals.
     * If cat id is not in supplies, consider that as animal cat.
     *
     * Loop through all $cart_product_cat_ids
     *
     * If a cat_id is found in $supplies_cat_ids
        * If supplies packing fee is not applied
            * add supplies packing fee
        * remove that category from $supplies_cat_ids
     *
     * After above loop, if $cart_product_cat_ids still has any cat_ids left, which means there are products from
     * animal categories as well.
        * add animal packing fee
     */

    foreach ($cart_product_cat_ids as $key => $cat_id) {
        if (in_array($cat_id, $supplies_cat_ids)) {
            if (!$supplies_packing_fee_applied) {
                $woocommerce->cart->add_fee('Supplies packing fee', SUPPLIES_PACKING_FEE);
                $supplies_packing_fee_applied = true;
            }
            unset($cart_product_cat_ids[$key]);
        }
    }

    if (count($cart_product_cat_ids)) {
        $woocommerce->cart->add_fee('Animal packing fee', ANIMAL_PACKING_FEE);
    }
}
