<?php
/* Admin Functions */

function getSavedPodcasts() {
	global $wpdb;
	
	$podcasts = $wpdb->get_results(
		"
		SELECT *
		FROM $wpdb->posts 
		WHERE post_content LIKE \"[playlist%podcast=%\"
		", OBJECT
	);
	
	return $podcasts;
}

function handlePost($post=array()) {
	global $wpdb;
	$ok = false; // will need this to be check for previous podcasts
	$podcast = (!empty($post['podcast_feed_url'])&&$post['podcast_feed_url']!=="http://www.yourfeed.com/feed")?new SimpleXmlElement(file_get_contents($post['podcast_feed_url'])):'';
	
	if(!empty($podcast)) { //disable adding new if doens't meet criteria
		//TODO: need to do check to see if podcast url is in already.
		$ok = true;
	}

	//TODO get $podcastType in form (audio, video)
	$podcastType = "audio";
	$podcastTracks = array();
	$tracks = array();
	//for($p=0; $p<5; $p++) { //limit for testing
	for($p=0; $p<count($podcast->channel->item); $p++) {
		$attributesObj = $podcast->channel->item[$p]->enclosure->attributes();
		$attributesArr = (array) $attributesObj;
		$attribute = $attributesArr['@attributes'];
		
		$podcastTracks[] =  
			array( //TODO: add "caption" and "description" if supporting other feeds
				"src" => $attribute['url']
				//,"type" => $attribute['type']
				,"type" => "video/mpeg"
				,"title" => (string) $podcast->channel->item[$p]->title
				,"meta" => array( //TODO: add "album", "genre"
					"artist" => $podcast->channel->title
					,"length_formatted" => gmdate("H:i:s", $attribute['length'])
				)
				,"image" => array( //TODO: handle episode images if not default to show's.
					"src" => (string) $podcast->channel->image->url
					,"width" => "308" //TODO: handle height, width if has in feed
					,"height" => "240"
				) //TODO: add thumb if feed has thumbnails...
			);
		
		//Add posts
		if($ok) {
			$post = array(
				'post_content' => (string) $podcast->channel->item[$p]->title
				,'post_title' => (string) $podcast->channel->item[$p]->title
				,'post_name' => str_replace(' ', '-', (trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags((string) $podcast->channel->item[$p]->title))))))))
				,'post_status' => 'inherit'
				,'post_parent' => 0
				,'guid' => $attribute['url']
				,'menu_order' => 0
				,'post_type' => 'attachment'
				,'post_mime_type' => 'audio/mpeg' //will need to add video also
			);
			$tracks[] = wp_insert_post( $post );
		}
	}
	
	if($ok) {
		$podcastSlug = $podcast->channel->title;  //TODO: get something to generate podcast slug
		$post = array(
			'post_content' => '[playlist ids="'.implode(',', $tracks).'" podcast="'.$podcastSlug.'"]'
			,'post_title' => $podcast->channel->title
			,'post_name' => 'podcast_'.str_replace(' ', '-', (trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($podcast->channel->title))))))))
			,'post_status' => 'publish'
			,'post_parent' => 0
			,'menu_order' => 0
			,'post_type' => 'post'
		);
		$parent = wp_insert_post( $post );
		
		foreach($tracks as $track) {
			$post = array(
				'ID' => (string) $podcast->channel->item[$p]->title
				,'post_parent' => $parent
			);
			wp_insert_post( $post );
		}
	}
	
	$podcastArr = array(
		"type" => $podcastType
		,"tracklist" => true
		,"tracknumbers" => true
		,"images" => true
		,"artists" => true
		,"tracks" => $podcastTracks
	);
	$json = json_encode($podcastArr);
	//var_dump(wp_get_playlist());exit;

}