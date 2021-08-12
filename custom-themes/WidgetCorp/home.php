
<?php get_header(); ?>

<div id='main-content'>
	<p id="intro">Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>

	<blockquote id="main-quote">We are able to do the work we do because of the quality of these widgets. <cite>- Frank James, Tick Tock Corp</cite></blockquote>

	<h2>Featured Widgets</h2>

	<ul id="featured-widgets">
		<li>
			<h3>Super Sprocket 1000</h3>
			<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
			<div class="image-and-button">
				<img src="<?php echo bloginfo('template_url'); ?>/product-images/prod-supersprocket.png" alt="Image of Super Sprocket 1000" />
				<a href="#" class="button">View Product</a>
			</div>
		</li>
		<li>
			<h3>Triple Sprunkler</h3>
			<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
			<div class="image-and-button">
				<img src="<?php echo bloginfo('template_url'); ?>/product-images/prod-sprunkler.png" alt="Image of Super Sprunkler" />
				<a href="#" class="button">View Product</a>
			</div>
		</li>
	</ul>

	<br />

	<h2>Web Special<em> of the</em> Week</h2>

	<div class="coupon">
		Buy two sprockets get the third free!
	</div>
</div>
<!-- get_sidebar means put all sidebar here -->
<?php get_sidebar(); ?>

<?php get_footer(); ?>