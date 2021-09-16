<div class="wrap">
    <h1 class="wp-heading-inline">Fedex Order Table</h1>
    <p>Latest 100 rows</p>
    <?php

    $table_cols = array(
        'id',
        'wc_order_id',
        'order_id',
        'hold',
        'shipping_location_type',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_company',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_phone',
        'shipping_email',
        'shipping_send_email_notification',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'shipping_country',
        'delivery_date',
        'is_shipped',
        'is_cancelled',
        'is_saturday_delivery',
        'date_shipped',
        'tracking_number',
        'delivery_type',
        'created_on',
        'updated_on',
        'is_tracking_info_pulled_by_wc',
    );

    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM wp_orders_fedex ORDER BY id DESC');
    if ($rows):
        ?>
        <table class="wp-list-table widefat striped">
            <tr>
                <th><b>id</b></th>
                <th><b>wc_order_id</b></th>
                <th><b>order_id</b></th>
                <th><b>hold</b></th>
                <th><b>shipping_location_type</b></th>
                <th><b>shipping_first_name</b></th>
                <th><b>shipping_last_name</b></th>
                <th><b>shipping_company</b></th>
                <th><b>shipping_address_1</b></th>
                <th><b>shipping_address_2</b></th>
                <th><b>shipping_phone</b></th>
                <th><b>shipping_email</b></th>
                <th><b>shipping_send_email_notification</b></th>
                <th><b>shipping_city</b></th>
                <th><b>shipping_state</b></th>
                <th><b>shipping_postcode</b></th>
                <th><b>shipping_country</b></th>
                <th><b>delivery_date</b></th>
                <th><b>is_shipped</b></th>
                <th><b>is_cancelled</b></th>
                <th><b>is_saturday_delivery</b></th>
                <th><b>date_shipped</b></th>
                <th><b>tracking_number</b></th>
                <th><b>delivery_type</b></th>
                <th><b>created_on</b></th>
                <th><b>updated_on</b></th>
                <th><b>is_tracking_info_pulled_by_wc</b></th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach($table_cols as $col): ?>
                    <td><?php echo $row->$col; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>