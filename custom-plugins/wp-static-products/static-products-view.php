<?php

$products = new WP_Query(
    array(
        'posts_per_page' => -1,
        'post_type' => 'product',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'wp_inventory_type',
                'value' => 'static'
            )
        )
    )
);


?>

<style>
    .static-products {

    }

    table.static-products td {
        vertical-align: middle;
    }

    table.static-products td img {
        width: 50px;
        height: 50px;
    }
</style>
<div class="wrap">
    <h1 class="wp-heading-inline">Static Products</h1>
    <table class="wp-list-table widefat striped static-products">
        <tr>
            <th><b>Thumbnail</b></th>
            <th><b>ID</b></th>
            <th><b>Title</b></th>
            <th><b>SKU</b></th>
            <th><b>Actions</b></th>
        </tr>
        <?php foreach ($products->posts as $product): $wc_product = wc_get_product($product->ID); ?>
            <tr>
                <td><?php echo get_the_post_thumbnail($wc_product->get_id(), 'woocommerce_gallery_thumbnail'); ?></td>
                <td><?php echo $wc_product->get_id(); ?></td>
                <td><a href="<?php echo get_permalink($wc_product->get_id()); ?>"><?php echo $wc_product->get_title(); ?></a></td>
                <td><?php echo $wc_product->get_sku(); ?></td>
                <td><a class="button" href="<?php echo get_edit_post_link($wc_product->get_id()); ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>