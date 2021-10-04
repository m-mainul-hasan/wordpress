<?php
// Hide Woocommerce "Ship to different address" if custom radio option is selected
// Stackoverflow question and my answer
// https://stackoverflow.com/questions/69425644/hide-woocommerce-ship-to-different-address-if-custom-radio-option-is-selected/69425954#69425954
// Custom field
add_action( 'woocommerce_before_checkout_billing_form', 'display_extra_fields_after_billing_address' , 10, 1 );
function display_extra_fields_after_billing_address () { ?>
    <h3>Do you have a PO box?<sup>*</sup></h3>
    <span><input type="radio" name="delivery_option" value="Yes" required /> Yes </span>
    <span><input type="radio" name="delivery_option" value="No" />No</span>
  <?php 
}

function add_custom_scripts(){ ?>
    <script type="text/javascript">
        (function($){
            $( document ).ready(function() {
                $('input[name=delivery_option]').change(function(){
                    var po_box_val = $('input[name="delivery_option"]:checked').val();
                    if ( po_box_val === "Yes") {
                        jQuery('#ship-to-different-address').hide();
                    }else if (po_box_val === "No"){
                        jQuery('#ship-to-different-address').show();
                    }
                });
            });
        })(jQuery);
    </script>
<?php }
add_action( 'wp_footer', 'add_custom_scripts', 10, 1 );