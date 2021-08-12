( function( $ ) {

	"use strict";
	
	//Attach sortable to the tbody, NOT tr
	var tbody = $(".story_list_cont #sorted_list");
	
	tbody.sortable({
		cursor: "move",
		connectWith: ".ct_sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	//Attach sortable to the tbody, NOT tr
	var tbody1 = $(".top_story_list_cont #top_stories_sorted_list");
	
	tbody1.sortable({
		cursor: "move",
		connectWith: ".ct_sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	var tbody2 = $(".story_list_cont #un_sorted_list");
	
	tbody2.sortable({
		cursor: "move",
		connectWith: ".ct_sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	$('.sortable_submit').on('click', function(e){
		// hide ct_sort_updated msg
		$('#sort_loading').css('display','block');
		$('.ct_sort_updated').css('display','none');
		$('.ct_sort_error').css('display','none');	
		//grabs all of the ids of the post rows and pushes them into an array
		ct_data.sort = tbody.sortable('toArray');
		//console.log(ct_data.sort);

		$.post(ajaxurl, ct_data)
		.done(function(response) {
				$('#sort_loading').css('display','none');
				$('.ct_sort_updated').css('display','block');				
			}).fail(function() {
				$('#sort_loading').css('display','none');
				$('.ct_sort_error').css('display','block');				
			});

		// hide ct_sort_updated msg
		// $('#sort_loading').css('display','block');
		// $('.ct_sort_updated').css('display','none');
		// $('.ct_sort_error').css('display','none');	
		//grabs all of the ids of the post rows and pushes them into an array
		ct_top_stories_data.sort = tbody1.sortable('toArray');

		$.post(ajaxurl, ct_top_stories_data)
		.done(function(response) {
				$('#sort_loading').css('display','none');
				$('.ct_sort_updated').css('display','block');				
			}).fail(function() {
				$('#sort_loading').css('display','none');
				$('.ct_sort_error').css('display','block');				
			});
	});

	$('.st_delete').bind('click', function () {
	    // Find the parent of the element clicked (an li) and remove it
	    $(this).parent().remove();
	});
	

	var options = {
	    valueNames: [ 'story-title', 'postedDate' ]
	};

	var recentstorieList = new List('recentstories', options);

	// var options = {
	//   valueNames: [ 'name']
	// };

	// var userList = new List('recent_top_stories_list', options);	
	// // var recent_top_stories_listList = new List('recent_stories_list', options);	

})( jQuery );






