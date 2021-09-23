<?php

require_once 'mapquest.php';

/**
 * Plugin Name: WP Fedex Hold Locations
 * Plugin URI:
 * Description: Shows nearest Fedex Hold Locations to choose from for shipping.
 * Version: 0.1
 * Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
 * Author URI: https://github.com/m-mainul-hasan
 */

/**
 * Add Fedex hold location fields
 */
add_action('wp_fedex_hold_fields', 'wp_fedex_hold_field');

function wp_fedex_hold_field($checkout)
{
    global $woocommerce;
    echo '<div id="wp_fedex_field_wrapper">';

    woocommerce_form_field('fedex_hold_location', array(
        'required' => true,
        'type' => 'select',
        'class' => array('form-row-wide'),
        'label' => __('All shipments go to an authorized FedEx location'),
        'options' => array(
            '' => 'Enter your zip code above and click the button below...',
        ),
    ), $checkout->get_value('fedex_hold_location'));

    echo '</div>';
}

/**
 * Process the checkout
 */
add_action('woocommerce_after_checkout_validation', 'wp_checkout_field_validation', 10, 2);

function wp_checkout_field_validation($fields, $errors)
{
    // Customer must select at least one shipping method (fedex or personal)

    if (!$_POST['ship_to_different_address'] && empty($_POST['fedex_hold_location'])) {
        $errors->add('validation', 'Choose your <strong>Fedex Hold Location</strong> from drop-down menu or enter your <strong>personal shipping address</strong>.');
    }

    if (!$_POST['ship_to_different_address']) {
        // Fedex shipment
        if (empty(trim($_POST['shipping_first_name']))) {
            $errors->add('validation', '<strong>Shipping First Name</strong> is a required field');
        }

        if (empty(trim($_POST['shipping_last_name']))) {
            $errors->add('validation', '<strong>Shipping Last Name</strong> is a required field');
        }

        if (empty(trim($_POST['shipping_postcode']))) {
            $errors->add('validation', '<strong>Shipping Zip</strong> is a required field');
        }
    }
}

/**
 * Update the order meta with field value
 */
add_action('woocommerce_checkout_update_order_meta', 'wp_checkout_field_update_order_meta');

function wp_checkout_field_update_order_meta($order_id)
{
    if (!empty($_POST['fedex_hold_location'])) {
        update_post_meta($order_id, 'fedex_hold_location', sanitize_text_field($_POST['fedex_hold_location']));
    }
}

/**
 * If customer chooses personal shipping address, then hide Fedex hold location drop-down list.
 */
add_action('wp_footer', 'wp_swap_visibility_of_shipping_options');

function wp_swap_visibility_of_shipping_options()
{
    if (is_checkout()) {
        ?>
        <script>
            (function ($) {
                var shippingFieldsHeading = $('.woocommerce-shipping-fields h3');
                var shipToDifferentAddressCheckbox = $('#ship-to-different-address-checkbox');
                shipToDifferentAddressCheckbox.on('click', function() {
                    var self = $(this);
                    if (self.is(':checked')) {
                        var billingFirstName = $('#billing_first_name');
                        var billingLastName = $('#billing_last_name');
                        var billingAddress1 = $('#billing_address_1');
                        var billingAddress2 = $('#billing_address_2');
                        var billingCity = $('#billing_city');
                        var billingState = $('#billing_state');
                        var billingStateValue = billingState.val();
                        var billingPostCode = $('#billing_postcode');
                        var billingPhone = $('#billing_phone');

                        var shippingFirstName = $('#shipping_first_name');
                        var shippingLastName = $('#shipping_last_name');
                        var shippingAddress1 = $('#shipping_address_1');
                        var shippingAddress2 = $('#shipping_address_2');
                        var shippingCity = $('#shipping_city');
                        var shippingState = $('#shipping_state');
                        var shippingPostCode = $('#shipping_postcode');

                        $('#fedex_hold_location').val('');
                        $('#fedex_hold_location_field').hide();

                        shippingFieldsHeading.text('Shipping to');

                        if(!$.trim(shippingFirstName.val()).length) {
                            shippingFirstName.val(billingFirstName.val());
                        }
                        if(!$.trim(shippingLastName.val()).length) {
                            shippingLastName.val(billingLastName.val());
                        }
                        if(!$.trim(shippingAddress1.val()).length) {
                            shippingAddress1.val(billingAddress1.val());
                        }
                        if(!$.trim(shippingAddress2.val()).length) {
                            shippingAddress2.val(billingAddress2.val());
                        }
                        if(!$.trim(shippingCity.val()).length) {
                            shippingCity.val(billingCity.val());
                        }
                        if(!$.trim(shippingState.val()).length) {
                            shippingState.val(billingStateValue);
                            shippingState.trigger('change');
                        }
                        if(!$.trim(shippingPostCode.val()).length) {
                            shippingPostCode.val(billingPostCode.val());
                        }
                    } else {
                        $('#fedex_hold_location_field').show();
                        shippingFieldsHeading.text('Shipment to FedEx hold location');
                    }
                });

                $(document).on('update_checkout', function (param) {
                });
            })(jQuery);
        </script>
        <?php
    }
}

add_action('wp_footer', 'wp_load_fedex_locations');

function wp_load_fedex_locations()
{
    if (is_checkout()) {
        ?>
        <script>
            var ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
            (function ($) {
                $('#fedex_hold_location').after('<button class="button alt" id="load-fedex-locations">&#x1F50E; Find nearest FedEx hold locations</button>');

                $('#load-fedex-locations').click(function (e) {
                    e.preventDefault();
                    var self = $(this);

                    var shippingPostCode = $.trim($('#shipping_postcode').val());

                    if (isNaN(Number(shippingPostCode)) || shippingPostCode.length != 5) {
                        $('#shipping_postcode').css('backgroundColor', '#fcf6d5');
                        alert("Please enter numeric 5 digit shipping zip code above.");
                        return false;
                    }

                    $('#shipping_postcode').css('backgroundColor', '#fff');

                    self.prop('disabled', true);

                    var postData = {
                        'shipping_postcode': shippingPostCode
                    };

                    $.post(ajax_url, {action: 'load_fedex_locations', postData}, function (returnData) {
                        self.prop('disabled', false);
                        if (returnData.length) {

                            var nearestLocation = returnData[0];
                            var nearestLocationParts = nearestLocation.split(',');
                            var nearestLocationState = $.trim(nearestLocationParts[nearestLocationParts.length - 2]);
                            $('#shipping_state').val(nearestLocationState);
                            $('#shipping_state').trigger('change');

                            $('#fedex_hold_location').prev('.nearest-location').remove();
                            $('#fedex_hold_location option:gt(0)').remove();
                            $('#fedex_hold_location').before('<div class="nearest-location"><p><strong>Nearest pickup location:</strong></p><h1 class="heading-font">' + returnData[0] + '</h1></div>');
                            for (var i = 0; i < returnData.length; i++) {
                                $('#fedex_hold_location').append($('<option>', {
                                    value: returnData[i],
                                    text: returnData[i]
                                }));
                            }
                            $('#fedex_hold_location option').eq(1).prop('selected', true);
                            $(document.body).trigger('update_checkout');
                        } else {
                            alert("We did not find any nearby FedEx hold locations. Please use your personal shipping address.");
                        }
                    });
                });

                $(document).ready(function() {
                    var shippingPostCode = $.trim($('#shipping_postcode').val());
                    if(shippingPostCode.length) {
                        $('#load-fedex-locations').trigger('click');
                    }
                });
            })(jQuery);
        </script>
        <?php
    }
}

add_action("wp_ajax_load_fedex_locations", "load_fedex_locations");
add_action("wp_ajax_nopriv_load_fedex_locations", "load_fedex_locations");

function load_fedex_locations()
{
    global $wpdb;

    $shipping_postcode = sanitize_text_field($_POST['postData']['shipping_postcode']);

    $location = rawurlencode("{$shipping_postcode}");

    $mapquest_url = ENDPOINT . "?key=" . KEY . "&location={$location}&maxResults=1";

    $maprequest_response = json_decode(file_get_contents($mapquest_url));

    $billing_lat = $maprequest_response->results[0]->locations[0]->latLng->lat;
    $billing_lng = $maprequest_response->results[0]->locations[0]->latLng->lng;

    $nearby_loc_query = "SELECT id, Address, City, State, Zip, lat, lng, ( 3959 * acos( cos( radians({$billing_lat}) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians({$billing_lng}) ) + sin( radians({$billing_lat}) ) * sin( radians( lat ) ) ) ) AS distance FROM {$wpdb->prefix}fedex_ship_centers WHERE (LocDisplayName = 'FedEx Ship Center') AND (lat IS NOT NULL) AND (lng IS NOT NULL) HAVING distance < 200 ORDER BY distance LIMIT 5";

    $nearby_loc_resp = $wpdb->get_results($nearby_loc_query);

    $nearby_locations = array();

    foreach ($nearby_loc_resp as $loc) {
        array_push($nearby_locations, "{$loc->Address}, {$loc->City}, {$loc->State}, {$loc->Zip}");
    }

    header('Content-Type: application/json');
    echo json_encode($nearby_locations);

    exit;
}

// https://stackoverflow.com/questions/53152285/refresh-cached-shipping-methods-on-checkout-update-ajax-event-in-woocommerce
function wp_always_reload_shipping_rate($post_data)
{
    $packages = WC()->cart->get_shipping_packages();

    WC()->shipping()->calculate_shipping($packages);

    foreach ($packages as $package_key => $package) {
        WC()->session->set('shipping_for_package_' . $package_key, false); // Or true
    }
}

add_action('woocommerce_checkout_update_order_review', 'wp_always_reload_shipping_rate');


function wp_get_order_items_json_str($order_id) {
    $order = wc_get_order($order_id);
    $order_items = $order->get_items();

    $order_items_array = array();

    foreach($order_items as $order_item) {
        $order_item_str = $order_item->get_quantity() . ' x ' . $order_item->get_name() . ' - ' . get_post_meta($order_item->get_product_id(), 'scientific_name', true);
        array_push($order_items_array, $order_item_str);
    }

    $order_items_json_str = json_encode($order_items_array);
    return $order_items_json_str;
}



add_action('woocommerce_checkout_order_processed', 'wp_post_process_shipping_info', 10, 3);
function wp_post_process_shipping_info($order_id, $posted_data, $order)
{
    global $wpdb;

    $order = wc_get_order($order_id);

    $delivery_date = sanitize_text_field(trim($_POST['wp_delivery_date']));

    if ( isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1 ) {
        $shipping_type = 'personal';
    } else {
        $shipping_type = 'fedex';
    }

    if ( $shipping_type == 'fedex' && !empty( $_POST['fedex_hold_location'] ) ) {
        $fedex_hold_location = sanitize_text_field($_POST['fedex_hold_location']);
        $fedex_hold_location_array = explode(',', $fedex_hold_location);

        $shipping_first_name = sanitize_text_field(trim($_POST['shipping_first_name']));
        $shipping_last_name = sanitize_text_field(trim($_POST['shipping_last_name']));

        $order->set_shipping_first_name($shipping_first_name);
        $order->set_shipping_last_name($shipping_last_name);
        $order->set_shipping_company('Fedex');
        $order->set_shipping_address_1(trim($fedex_hold_location_array[0]));
        $order->set_shipping_address_2('');
        $order->set_shipping_city(trim($fedex_hold_location_array[1]));
        $order->set_shipping_state(trim($fedex_hold_location_array[2]));
        $order->set_shipping_postcode(trim($fedex_hold_location_array[3]));
        $order->set_shipping_country($order->get_shipping_country());
        $order->save();
    }

    // Re-fetch to make sure updated order data
    $order = wc_get_order($order_id);

    $fedex_order = array(
        'wc_order_id' => $order->get_id(),
        'order_id' => sprintf('R%s', $order->get_id()),
        'shipping_first_name' => $order->get_shipping_first_name(),
        'shipping_last_name' => $order->get_shipping_last_name(),
        'shipping_company' => $order->get_shipping_company(),
        'shipping_address_1' => $order->get_shipping_address_1(),
        'shipping_address_2' => $order->get_shipping_address_2(),
        'shipping_phone' => $order->get_billing_phone(),
        'shipping_email' => $order->get_billing_email(),
        'shipping_send_email_notification' => 1,
        'shipping_city' => $order->get_shipping_city(),
        'shipping_state' => $order->get_shipping_state(),
        'shipping_postcode' => $order->get_shipping_postcode(),
        'shipping_country' => $order->get_shipping_country(),
        'delivery_date' => $delivery_date,
        'is_shipped' => 0,
        'is_cancelled' => 0,
        'date_shipped' => null,
        'tracking_number' => null,
        'created_on' => date('Y-m-d H:i:s'),
        'updated_on' => date('Y-m-d H:i:s'),
        'is_tracking_info_pulled_by_wc' => 0,
        'order_items' => wp_get_order_items_json_str($order_id),
    );

    if ( $shipping_type == 'fedex' ) {
        $fedex_order['hold'] = 1;
        $fedex_order['shipping_location_type'] = 'fedex_ship_center';
        $fedex_order['delivery_type'] = 'fedex_standard_overnight';
    } else {
        $fedex_order['hold'] = 0;
        $fedex_order['shipping_location_type'] = 'personal';
        $fedex_order['delivery_type'] = 'fedex_priority_overnight';
    }

    if (date('w', strtotime($delivery_date)) == 6) {
        $fedex_order['is_saturday_delivery'] = 1;
    } else {
        $fedex_order['is_saturday_delivery'] = 0;
    }

    // Instead of using wpdb->prefix the table name is hardcoded here. Because even if we can change table prefix,
    // but Fedex will not update their code without any good reason.

    $already_inserted = $wpdb->get_results('SELECT id FROM wp_orders_fedex WHERE wc_order_id = ' . $order->get_id());

    if (!$already_inserted) {
        $inserted = $wpdb->insert(
            'wp_orders_fedex',
            $fedex_order
        );
    }
}

add_action('save_post', 'wp_action_save_post');

function wp_action_save_post($post_id) {
    global $wpdb;

    $post_type = get_post_type($post_id);

    if(
        is_admin()
        && $post_type == 'shop_order'
        && isset($_POST['action']) && $_POST['action'] == 'editpost'
    ) {
        $wpdb->update(
            'wp_orders_fedex',
            array(
                'shipping_first_name' => sanitize_text_field($_POST['_shipping_first_name']),
                'shipping_last_name' => sanitize_text_field($_POST['_shipping_last_name']),
                'shipping_company' => sanitize_text_field($_POST['_shipping_company']),
                'shipping_address_1' => sanitize_text_field($_POST['_shipping_address_1']),
                'shipping_address_2' => sanitize_text_field($_POST['_shipping_address_2']),
                'shipping_city' => sanitize_text_field($_POST['_shipping_city']),
                'shipping_state' => sanitize_text_field($_POST['_shipping_state']),
                'shipping_postcode' => sanitize_text_field($_POST['_shipping_postcode']),
                'shipping_country' => sanitize_text_field($_POST['_shipping_country']),
            ),
            array(
                'wc_order_id' => $post_id,
            )
        );
    }
}

/**
 * Allow admin to change hold / not hold
 */
add_action('acf/save_post', 'wp_admin_change_is_fedex_hold', 10, 3);
function wp_admin_change_is_fedex_hold($post_id) {
    global $wpdb;
    $post = get_post($post_id);
    if (is_admin() && $post->post_type == 'shop_order') {
        $is_fedex_hold = get_field('is_fedex_hold', $post_id, true);
        if ($is_fedex_hold == 'yes') {
            $hold = 1;
            $delivery_type = 'fedex_standard_overnight';
        } else {
            $hold = 0;
            $delivery_type = 'fedex_priority_overnight';
        }

        $wpdb->update(
            'wp_orders_fedex',
            array('hold' => $hold, 'delivery_type' => $delivery_type),
            array('wc_order_id' => $post_id)
        );
    }
}

/**
 * Allow admin to change saturday priority delivery
 */
add_action('acf/save_post', 'wp_admin_change_is_saturday_priority', 10, 3);
function wp_admin_change_is_saturday_priority($post_id) {
    global $wpdb;
    $post = get_post($post_id);
    if (is_admin() && $post->post_type == 'shop_order') {
        $is_saturday_priority = get_field('is_saturday_priority', $post_id, true);
        if ($is_saturday_priority == 'yes') {
            $is_saturday_delivery = 1;
            $delivery_type = 'fedex_priority_overnight';

            $wpdb->update(
                'wp_orders_fedex',
                array('is_saturday_delivery' => $is_saturday_delivery, 'delivery_type' => $delivery_type),
                array('wc_order_id' => $post_id)
            );
        } else {
            $is_saturday_delivery = 0;

            $wpdb->update(
                'wp_orders_fedex',
                array('is_saturday_delivery' => $is_saturday_delivery),
                array('wc_order_id' => $post_id)
            );
        }

        
    }
}
