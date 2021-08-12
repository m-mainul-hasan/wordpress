<?php 
	
	/*
		Template Name: Product Page
	*/	
?>

<!-- Wordpress recognize a page as a template by writng Template Name in comment -->
<!-- Product Page template will parse those page where the template page is product page -->

<?php $template_url = get_template_directory_uri() ?>
<?php get_header(); the_post(); ?>

<div id='main-content'>
	<h2 class="product-title"><?php  the_title(); ?></h2>
    <div class="product-info-box">
    <?php //echo get_post_meta($post->ID, 'product_large', true) ?>
		<?php $image_src = $template_url.get_post_meta($post->ID, 'product_large', true); ?>
        <img src="<?php echo $image_src; ?>" />            
        <ul>
        	<!-- get_post_meta has three parameters which post which custom field exactly true or false
        	if true just return the first value if multiple value has return first value if false then return an array -->
            <li><h5>Price</h5> <?php echo get_post_meta($post->ID, 'price', true) ?> </li>
            <li><h5>Product Code</h5> <?php echo get_post_meta($post->ID, 'product_code', true) ?> </li>
            <li><h5>Dimensions</h5> <?php echo get_post_meta($post->ID, 'dimensions', true) ?> </li>
            <li><a class="button" href="#">Add to Cart</a></li>
        </ul>
    </div>
    <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
</div>
<!-- get_sidebar means put all sidebar here -->
<?php get_sidebar(); ?>

<?php get_footer(); ?>