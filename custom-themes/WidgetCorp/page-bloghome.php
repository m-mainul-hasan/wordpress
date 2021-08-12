<?php 
	
	/*
		Template Name: Blog Homepage
	*/	
?>


<?php get_header(); ?>
<!-- Wordpress works with modular kind of way
get_header means go the folder and find header.php 
and place the code right here -->
<div id='main-content'>
	<h4 class="giant">The Grind</h4>
	<?php query_posts("posts_per_page=5"); ?>
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">

			<h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>

			<?php //include (TEMPLATEPATH . '/inc/meta.php' ); ?>
			<?php //include (get_template_directory() . '/inc/meta.php' ); ?>
			<?php include (get_option('template_url') . '/inc/meta.php' ); ?>
		
			<div class="entry">
				<?php the_content(); ?>
			</div>

		</div>

	<?php endwhile; ?>

	<?php include (TEMPLATEPATH . '/inc/nav.php' ); ?>

	<?php else : ?>

		<h2>Not Found</h2>

	<?php endif; ?>
</div>
<!-- get_sidebar means put all sidebar here -->
<?php get_sidebar(); ?>

<?php get_footer(); ?>