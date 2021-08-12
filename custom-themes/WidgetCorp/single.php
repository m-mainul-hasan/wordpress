<?php get_header(); the_post(); ?>

<h2 id="post-title"><?php the_title(); ?></h2>

<?php include (TEMPLATEPATH . '/inc/meta.php' ); ?>

<div id="main-content">

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">

			<div class="entry">
				
				<?php the_content(); ?>

				<?php wp_link_pages(array('before' => 'Pages: ', 'next_or_number' => 'number')); ?>
				
				<?php the_tags( 'Tags: ', ', ', ''); ?>

			</div>
			
			<?php edit_post_link('Edit this entry','','.'); ?>
			
		</div>

	<?php comments_template(); ?>
	
</div>
	
<?php get_sidebar(); ?>

<?php get_footer(); ?>