<?php 
	
	/*
		Template Name: Product Category
	*/	
?>

<?php get_header(); the_post(); ?>

<div id='main-content'>
	<?php
		//categoriesCF means categories custom field MH
		$categoriesCF = get_post_meta($post->ID, "categories", true);
		// example return value = "Sprockets|92,Sprunklers|94"
		
		$allCategories = explode(",", $categoriesCF);
		// $allCategories[0] = "Sprockets|92"
		// $allCategories[1] = "Sprunklers|94"
		
		$template_url = get_option('template_url');
		// var_dump($template_url);

		foreach ($allCategories as $category) {

			$pieces = explode("|", $category);
			// $pieces[0] = "Sprockets"
			// $pieces[1] = 92
			
			// Retrieves the full permalink for the current post or post ID. MH		
			$link = get_permalink($pieces[1]);
			echo "<div class='product-group group'>";
			echo "<h3><a href='$link'>" . $pieces[0] . "</a></h3>";

			// Return to us all child pages of that particular category MH
			// post_parent  Pass the ID of a post or Page to get its children MH
			query_posts("posts_per_page=-1&post_type=page&post_parent=$pieces[1]");

			while (have_posts()) : the_post(); ?>

			 <a href="<?php the_permalink(); ?>" class="product-jump" title="<?php echo "$" . get_post_meta($post->ID, "product_price", true); ?>" data-large="<?php get_post_meta($post->ID, "product_image", true); ?>">

			     <?php echo "<img src='" .get_template_directory_uri(). get_post_meta($post->ID, "product_regular", true) . "' />"; ?>
			     <span class="product-title"><?php the_title(); ?></span>
			     <span class="product-code"><?php echo get_post_meta($post->ID, "product_code", true); ?></span>

			 </a>

			<?php endwhile; wp_reset_query();

			echo "</div>";

		};
	?>
</div>
<!-- get_sidebar means put all sidebar here -->
<?php get_sidebar(); ?>

<?php get_footer(); ?>