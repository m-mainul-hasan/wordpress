<?php
/*
Plugin Name: Story List
Plugin URI: http://localhost
Version: 1.0
Author: Sazedul Karim
Description: It's a simple post custom sorting order management plugin.
*/
?>
<?php 

define( 'CT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Hook for adding admin menus
add_action('admin_menu', 'ct_story_sort');
function ct_story_sort(){
	add_submenu_page( 'edit.php','Manage Contents','Manage Contents', 'moderate_comments', 'manage-story-list', 'manage_story_list');
}

function manage_story_list(){
	include( CT_PLUGIN_PATH . '/templates/story-list-view.php');
}

// css and javascript include

add_action( 'admin_enqueue_scripts', 'ct_add_stylesheet_to_story' );

/**
 * Add stylesheet to the page
 */
function ct_add_stylesheet_to_story( $page ) {
	if( 'posts_page_manage-story-list' != $page )
	{
		return;
	}
	wp_enqueue_style( 'story-style', plugins_url('assets/style_admin.css', __FILE__) );
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script( 'list-min', plugins_url('assets/list-min.js', __FILE__),array(),'', true );
	wp_enqueue_script( 'story-js', plugins_url('assets/story-js.js', __FILE__),array(),'', true );
}

// save top stories ajax request

add_action('wp_ajax_top-stories-sort', '_sort_top_stories');

function _sort_top_stories()
{	
	global $wpdb;
	
	if( empty($_POST['action'])){return;}

	$data = array_map('sanitize_text_field',$_POST['sort']);
	// var_dump($data); die();
	$messages = array();
	
	// remove old values
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = 'hrgmu_m_top_stories'");
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = 'top_stories_sort_order'");

	
	//_remove_all_sort_values('hrgmu_m_top_stories', 'top_stories_sort_order');

	foreach($data as $k => $v)
	{
		$id = ltrim($v, 'post-'); //Trim the "post-" prefix from the id
		// var_dump($v);
		// exit;
		$index = ($k + 1); //Make sure our sorting index starts at #1
		// update 
		update_post_meta( $id, 'hrgmu_m_top_stories', 1);
		update_post_meta( $id, 'top_stories_sort_order', $index );
	}
	
	exit();
}

// fetured-stories-sort 
add_action('wp_ajax_fetured-stories-sort', '_sort_fetured_stories');

function _sort_fetured_stories()
{	
	global $wpdb;
	if( empty($_POST['action'])){return;}

	$data = array_map('sanitize_text_field',$_POST['sort']);
	var_dump($data); 
	// die();

	$messages = array();
	// remove old values
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = 'hrgmu_m_fetured_stories'");
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = 'fetured_stories_sort_order'");
	//_remove_all_sort_values('hrgmu_m_fetured_stories', 'fetured_stories_sort_order');

	foreach($data as $k => $v)
	{
		// $id = ltrim($v, 'post-'); //Trim the "post-" prefix from the id
		$id = ltrim($v, 'post-'); //Trim the "post-" prefix from the id
		// var_dump($id);
		$index = ($k + 1); //Make sure our sorting index starts at #1
		var_dump($id);
		var_dump($index);
		// update 
		update_post_meta( $id, 'hrgmu_m_fetured_stories', 1);
		update_post_meta( $id, 'fetured_stories_sort_order', $index );
	}
	
	exit();
}

// worth-reading-sort
add_action('wp_ajax_worth-reading-sort', '_sort_worth_reading');

function _sort_worth_reading()
{	
	global $wpdb;
	if( empty($_POST['action'])){return;}

	$data = array_map('sanitize_text_field',$_POST['sort']);

	$messages = array();
	// remove old values
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = 'hrgmu_m_worth_reading'");
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = 'worth_reading_stories_sort_order'");
	//_remove_all_sort_values('hrgmu_m_worth_reading', 'worth_reading_stories_sort_order');

	foreach($data as $k => $v)
	{
		$id = ltrim($v, 'post-'); //Trim the "post-" prefix from the id
		$index = ($k + 1); //Make sure our sorting index starts at #1
		// update 
		update_post_meta( $id, 'hrgmu_m_worth_reading', 1);
		update_post_meta( $id, 'worth_reading_stories_sort_order', $index );
	}
	
	exit();
}
// latest-story-sort
add_action('wp_ajax_latest-story-sort', '_sort_latest_story');

function _sort_latest_story()
{	
	global $wpdb;
	if( empty($_POST['action'])){return;}

	$data = array_map('sanitize_text_field',$_POST['sort']);

	$messages = array();
	// remove old values
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = 'hrgmu_m_latest_story'");
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = 'latest_stories_sort_order'");
	//_remove_all_sort_values('hrgmu_m_latest_story', 'latest_stories_sort_order');

	foreach($data as $k => $v)
	{
		$id = ltrim($v, 'post-'); //Trim the "post-" prefix from the id
		$index = ($k + 1); //Make sure our sorting index starts at #1
		// update 
		update_post_meta( $id, 'hrgmu_m_latest_story', 1);
		update_post_meta( $id, 'latest_stories_sort_order', $index );
	}
	
	exit();
}

// remove old values 

function _remove_all_sort_values($stories, $sort_order){
	global $wpdb;

	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 15 WHERE meta_key = $stories");
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 999 WHERE meta_key = $sort_order");

/*	$args = array( 		
		'post_type'	=> 'post',
		'posts_per_page' => -1,	
		);		
	$posts_list = get_posts($args);
	if($posts_list){
		foreach ($posts_list as $post) {
			update_post_meta( $post->ID, $stories, 0);
			update_post_meta( $post->ID, $sort_order, 999);
		}		
	}*/
}

