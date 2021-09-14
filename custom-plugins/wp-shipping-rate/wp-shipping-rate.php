<?php

/**
 * Plugin Name: WP Shipping Rate
 * Plugin URI: 
 * Description: Set shipping rate based on subtotal.
 * Version: 0.1
 * Author: Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan/wordpress/tree/master/custom-plugins
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function wp_shipping_method_init() {
        if ( ! class_exists( 'WP_Shipping_Method' ) ) {
            class WP_Shipping_Method extends WC_Shipping_Method {

                private $fedex_hold_location_shipping_cost;
                private $marine_subtotal_for_free_fedex_hold_shipping;
                private $supplies_cat_ids;
                private $has_supplies_in_cart;
                private $supplies_shipping_cost;
                private $personal_location_shipping_cost;
                private $marine_life_subtotal;
                private $supplies_subtotal;

                /**
                 * Constructor for shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 0) {
                    $this->id = 'wp_shipping_method';
                    $this->instance_id = absint($instance_id);
                    $this->title = __( 'WP Shipping Method' );
                    $this->method_title = 'WP Shipping Method';
                    $this->method_description = __( 'Shipping will be flat rate $24.99 to FedEx Hold under $149. Free to FedEx from $149.' ); //
                    $this->enabled = "yes"; // This can be added as an setting but for this case its forced enabled
                    $this->supports = array(
                        'shipping-zones',
                    );

                    $this->personal_location_shipping_cost = 6.99;
                    $this->marine_subtotal_for_free_fedex_hold_shipping = 149;
                    $this->supplies_shipping_cost = 9.99;
                    $this->supplies_cat_ids = array(21, 22);
                    $this->has_supplies_in_cart = false;
                    $this->fedex_hold_location_shipping_cost = 24.99;
                    $this->supplies_subtotal = 0;
                    $this->marine_life_subtotal = 0;
                    $this->init();
                }

                /**
                 * Init our settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add our own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                public function find_supplies_in_cart()
                {
                    // Get cart items
                    $cart_items = wc()->cart->get_cart();

                    foreach ($cart_items as $key => $item) {
                        $product = wc_get_product($item['product_id']);
                        $item_cat_ids = $product->get_category_ids();
                        foreach ($item_cat_ids as $cat_id) {
                            if(in_array($cat_id, $this->supplies_cat_ids)) {
                                $this->has_supplies_in_cart = true;
                            }
                        }
                    }
                }

                public function break_down_subtotal_by_types($package)
                {
                    $cart_items = wc()->cart->get_cart_contents();
                    foreach ($cart_items as $cart_item_key => $cart_item) {
                        $product = wc_get_product($cart_item['product_id']);
                        $product_cat_ids = $product->get_category_ids();

                        if (count(array_intersect($product_cat_ids, $this->supplies_cat_ids))) {
                            // This item is from supplies category
                            $this->supplies_subtotal += $cart_item['line_subtotal'];
                        }
                    }

                    $this->marine_life_subtotal = $package['cart_subtotal'] - $this->supplies_subtotal;
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array()) {

                    // Setup common indexes of rate array
                    $rate = array(
                        'id'       => $this->id,
                        'calc_tax' => 'per_order'
                    );

                    $this->break_down_subtotal_by_types($package);

                    if ($this->marine_life_subtotal >= $this->marine_subtotal_for_free_fedex_hold_shipping) {
                        $this->fedex_hold_location_shipping_cost = 0;
                    }

                    // Determine if ship_to_different_address (= not fedex) is chosen
                    if (isset($_POST['post_data']) ) {
                        $post_data = $_POST['post_data'];
                        parse_str($post_data, $post_data_array);
                    }

                    if ( isset($post_data_array) && isset($post_data_array['ship_to_different_address']) && $post_data_array['ship_to_different_address'] == 1) {
                        // Ship to personal address
                        $rate['label'] = 'Personal location';
                        $rate['cost'] = $this->fedex_hold_location_shipping_cost + $this->personal_location_shipping_cost;
                    } else if (isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1) {
                        $rate['label'] = 'Personal location';
                        $rate['cost'] = $this->fedex_hold_location_shipping_cost + $this->personal_location_shipping_cost;
                    } else {
                        // Ship to Fedex Hold location
                        $rate['label'] = 'Fedex hold location';
                        $rate['cost'] = $this->fedex_hold_location_shipping_cost;
                    }

                    $this->find_supplies_in_cart();

                    if ($this->has_supplies_in_cart) {
                        $rate['label'] = sprintf("%s + Supplies shipping", $rate['label']);
                        $rate['cost'] = $rate['cost'] + $this->supplies_shipping_cost;
                    }

                    $this->add_rate( $rate );
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'wp_shipping_method_init' );

    function wp_add_shipping_method( $methods ) {
        $methods['wp_shipping_method'] = 'WP_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'wp_add_shipping_method' );

    add_filter( 'woocommerce_cart_shipping_method_full_label', 'wp_free_shipping_label', 10, 2 );
    function wp_free_shipping_label( $label, $method ) {
        if ( $method->cost == 0 ) {
            $label = 'Free shipping';
        }
        return $label;
    }

    function wp_shipping_calc_on_cart( $show_shipping ) {
        return $show_shipping;
    }
    add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'wp_shipping_calc_on_cart', 99 );
}

