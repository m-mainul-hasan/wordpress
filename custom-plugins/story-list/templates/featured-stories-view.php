<div class="wrap top_stories_container">
	<div class="story_list_cont">
		<?php 
			// get all top stories post 
			$args = array( 		
			'post_type'	=> 'post',
			'posts_per_page' => 20,
			'meta_key' => 'fetured_stories_sort_order',
			// 'meta_key' => array('fetured_stories','fetured_stories_sort_order'),
			// 'meta_value' => array('true'),
			// 'order_by' => array('fetured_stories_sort_order'),
			'orderby'=>'meta_value_num',
			// 'orderby'=>'fetured_stories_sort_order',
			'order' =>'ASC',
			'meta_query' => array(
				array(
					'key' => 'hrgmu_m_fetured_stories',
					'value' => '1',
					'compare' => '='
					)
				),		
			);		
			// $posts_list = get_posts($args); 
			$posts_list = query_posts($args);; 

			// print_r($post_list);
		?>
		<div class="featured-stories-title">
		<h2>Featured Stories</h2>
		<!-- <p class="submit"> -->
			<input type="submit" name="Submit" class="button-primary sortable_submit" value="<?php esc_attr_e('Save Changes') ?>" />
		<!-- </p> -->
		</div>
		<ul id="sorted_list" class="ct_sort_list">
		<?php 
			if($posts_list):
				// get_post_meta()
				$i = 1; 
				foreach ($posts_list as $story): ?>
					<!-- <pre> -->
						 <!-- <?php print_r(get_post_meta($story->ID)); ?>  -->
						 <!-- <?php print_r($posts_list); ?>  -->
					<!-- </pre>  -->
					<li id="<?php echo $story->ID; ?>">
						<?php echo $i.'. '.$story->post_title . ' <span class="postedDate">' . date("g:i a j F Y",strtotime($story->post_date)) . ' </span> '; ?>
						<button class="st_delete">X</button>
					</li>				
			<?php 
				$i++;
				endforeach; 
			endif; ?>
		</ul>
	</div>
	<div class="story_list_cont" id="recentstories">
		<input class="search" placeholder="Search" />
		<!-- <button class="sort" data-sort="story-title">
		   Sort
		 </button> -->
		<?php 
			// get all top stories post 
			$args = array( 		
			'post_type' => 'post',
			'posts_per_page' => 300				
			);		
			$posts_list = get_posts($args); 
		?>
		<h2>Recent Stories</h2>
		<ul id="un_sorted_list" class="ct_sort_list list">
		<?php 
			if($posts_list): 
				foreach ($posts_list as $story):
				$feat_story = get_post_meta($story->ID, 'hrgmu_m_fetured_stories', true);
				$feat_story_for_top_stories = get_post_meta($story->ID, 'hrgmu_m_top_stories', true);

				if($feat_story)
					continue;
				elseif($feat_story_for_top_stories)
					continue;
				?>
					<li id="<?php echo $story->ID; ?>">						
						<?php echo '<span class="story-title">'.$story->post_title .'</span> ' . ' <span class="postedDate">' . date("g:i a j F Y",strtotime($story->post_date)) . ' </span>'; ?>
						<button class="st_delete">X</button>
					</li>				
			<?php endforeach; 
			endif; ?>
		</ul>
	</div>
	
	<div class="clear"></div>

	<?php include('top-stories-view.php'); ?>

	<p class="submit">
		<input type="submit" name="Submit" class="button-primary sortable_submit" value="<?php esc_attr_e('Save Changes') ?>" />
	</p>
	<script type="text/javascript">
		var ct_data = {
			'action': 'fetured-stories-sort', //Set an action for our ajax function
		};
	</script>	
	
</div>