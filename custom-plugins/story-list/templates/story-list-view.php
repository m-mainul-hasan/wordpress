<?php 
	// grab all the Request params 
	$story_data = $_REQUEST;

	// check any stories selectd or not (if not then select Fetured stories)
	if(! isset($story_data['stories'])){
		$story_data['stories'] = 1;
	}
?>
<div class="updated ct_sort_updated" style="display:none;"><p><strong><?php _e('Sorting saved.', 'menu-test' ); ?></strong></p></div>
<div class="error notice ct_sort_error" style="display:none;">
    <p>There has been an error.</p>
</div>
<div id="sort_loading"><span class="sort_loading_animator"><div class='uil-ripple-css' style='transform:scale(1);'><div></div><div></div></div></span></div>
<div class="wrap">
	<h1>Manage Contents</h1>
	<ul class="subsubsub">
		<li class="featured_stories">
			<a <?php echo $story_data['stories'] == 1 ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=1">Featured / Top Stories</span></a> |
		</li>
		<!-- <li class="top_stories">
			<a <?php echo $story_data['stories'] == 2 ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=2">Top Stories</span></a> |
		</li> -->
		<li class="worth_reading_stories">
			<a <?php echo $story_data['stories'] == 3 ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=3">Worth Reading Stories</span></a> |
		</li>
		<li class="latest_stories">
			<a <?php echo $story_data['stories'] == 4 ? 'class="current"' : ''; ?> href="edit.php?page=manage-story-list&stories=4">Latest Stories</span></a>
		</li>		

	</ul>
	<div class="clear"></div>
</div>


<?php 
	if($story_data['stories'] == 1){
		include('featured-stories-view.php');
	}else if($story_data['stories'] == 3){
		include('worth-reading-stories-view.php');
	}else if($story_data['stories'] == 4){
		include('latest-stories-view.php');
	}
	
?>
