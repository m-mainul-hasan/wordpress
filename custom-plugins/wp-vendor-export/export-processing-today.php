<div class="wrap">
    <h1 class="wp-heading-inline">WP Export - Orders with [Processing Today] Status</h1>
    <?php
    $orders = wc_get_orders(array('post_status' => 'wc-processing-today', 'limit' => -1));
    if (!$orders) {
        ?>
        <p>No orders available with status of Processing Today.</p>
        <?php
    } else {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <th><b>Vendor Order number</b></th>
                <th><b>WP Order number</b></th>
                <th><b>Customer Name</b></th>
            </tr>
            <?php foreach ($orders as $order): 
            ?>
            <tr>
                <td><?php echo get_post_meta($order->get_id(), '_vendor_order_id', true); ?></td>
                <td><?php echo sprintf("R%d", $order->get_id()); ?></td>
                <td><?php echo sprintf("%s %s", $order->get_shipping_first_name(), $order->get_shipping_last_name()); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p>
        <form action="admin.php?page=wp-vendor-export" method="POST">
            <input type="hidden" name="wp_action" value="vendor_export_processing_today">
            <button class="button button-primary button-large" type="submit">Export CSV</button>
        </form>
        </p>
        <?php
    }
    ?>
</div>
