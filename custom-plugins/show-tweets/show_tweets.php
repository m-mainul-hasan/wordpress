<?php 
/*
Plugin Name: Show Tweets
Plugin URI: http://localhost 
Description: Simple shortcode
Version: 1.0
Author: Mainul Hasan
Author URI: http://localhost
*/

// Plugin URI means where the plugin is located in or where the documentation of the plugin
// more easier way is 
add_shortcode('twitter', function($atts, $content) {
	// Some people uses defaults some uses options we use atts to overwrite the previous one
	$atts = shortcode_atts(
		array(
			'username' => 'envatowebdev',
			'content'  => !empty($content) ? $content : 'Follow me on Twitter!',
			'show_tweets' => false,
			// its ultimately translates every refresh the page after every 10 minutes grabs and posts twitter posts after every 10 minustes
			'tweet_reset_time' => 10,
			'num_tweets' => 5 	
		), $atts
	);

	// This function uses array keys as variable names and values as variable values. For each element it will create a variable in the current symbol table.
	extract($atts);

	if($show_tweets) {
		$tweets = fetch_tweets($num_tweets, $username, $tweet_reset_time);
	}

	return "$tweets <p><a href='http://twitter.com/$username'>$content</a></p>";
	// extract will extract all array key as variable like $atts['username'] change in $username
});

// $num_tweets how many posts fetch from twitter
function fetch_tweets($num_tweets, $username, $tweet_reset_time){
	global $id;
	$recent_tweets = get_post_meta($id, 'mh_recent_tweets');
	reset_data($recent_tweets, $tweet_reset_time);
	// if no cache, fetch new tweets and cache.
	if(empty($recent_tweets)) {
		$tweets = curl($username,$num_tweets);
		$data = array();
		foreach ($tweets as $tweet) {
			if($num_tweets-- === 0) break;
			$data[] = $tweet->text;
		}

		$recent_tweets = array( (int)date('i', time()));
		$recent_tweets[] = '<ul class="mh_tweets"><li>' . implode('</li><li>', $data). '</li><ul>';

		cache($recent_tweets);
	}

	return isset($recent_tweets[0][1]) ? $recent_tweets[0][1] : $recent_tweets[1];
}

function curl($username,$num_tweets){
	$token = '';
	$token_secret = '';
	$consumer_key = '';
	$consumer_secret = '';

	$host = 'api.twitter.com';
	$method = 'GET';
	$path = '/1.1/statuses/user_timeline.json'; // api call path

	$query = array( // query parameters
	    'screen_name' => $username,
	    'count' => $num_tweets
	);

	$oauth = array(
	    'oauth_consumer_key' => $consumer_key,
	    'oauth_token' => $token,
	    'oauth_nonce' => (string)mt_rand(), // a stronger nonce is recommended
	    'oauth_timestamp' => time(),
	    'oauth_signature_method' => 'HMAC-SHA1',
	    'oauth_version' => '1.0'
	);

	$oauth = array_map("rawurlencode", $oauth); // must be encoded before sorting
	$query = array_map("rawurlencode", $query);

	$arr = array_merge($oauth, $query); // combine the values THEN sort

	asort($arr); // secondary sort (value)
	ksort($arr); // primary sort (key)

	// http_build_query automatically encodes, but our parameters
	// are already encoded, and must be by this point, so we undo
	// the encoding step
	$querystring = urldecode(http_build_query($arr, '', '&'));

	$url = "https://$host$path";

	// mash everything together for the text to hash
	$base_string = $method."&".rawurlencode($url)."&".rawurlencode($querystring);

	// same with the key
	$key = rawurlencode($consumer_secret)."&".rawurlencode($token_secret);

	// generate the hash
	$signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));

	// this time we're using a normal GET query, and we're only encoding the query params
	// (without the oauth params)
	$url .= "?".http_build_query($query);
	$url=str_replace("&amp;","&",$url); //Patch by @Frewuill

	$oauth['oauth_signature'] = $signature; // don't want to abandon all that work!
	ksort($oauth); // probably not necessary, but twitter's demo does it

	// also not necessary, but twitter's demo does this too
	function add_quotes($str) { return '"'.$str.'"'; }
	$oauth = array_map("add_quotes", $oauth);

	// this is the full value of the Authorization line
	$auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));

	$options = array( CURLOPT_HTTPHEADER => array("Authorization: $auth"),
	                  //CURLOPT_POSTFIELDS => $postfields,
	                  CURLOPT_HEADER => false,
	                  CURLOPT_URL => $url,
	                  CURLOPT_RETURNTRANSFER => true,
	                  CURLOPT_SSL_VERIFYPEER => false,
	                  CURLOPT_CONNECTTIMEOUT => 3,
	                  CURLOPT_TIMEOUT => 5);

	// // do our business
	$feed = curl_init();
	curl_setopt_array($feed, $options);
	$json = curl_exec($feed);
	curl_close($feed);

	$twitter_data = json_decode($json);

	return $twitter_data;	
}

function cache($recent_tweets){
   global $id; // get the global id of the post
   // id refers to the id of the post
   // name of the meta data
   // name could be anything its simply identifier
   // true means every time data will be unique then add meta in the database
   // otherwise we use update_post_meta() method
   add_post_meta($id, 'mh_recent_tweets', $recent_tweets, true);
}

function reset_data($recent_tweets, $tweet_reset_time){
	global $id;
	// print_r($recent_tweets);
	// $recent_tweets[0][0] this is the time of the last updated
	if(isset($recent_tweets[0][0])) {
		$delay = $recent_tweets[0][0] + (int) $tweet_reset_time;
		if($delay >= 60) $delay -= 60;
		// var_dump((int) date('i', time()));
		if($delay <= (int) date('i', time())) {
			delete_post_meta($id,'mh_recent_tweets');
		}
	}
}