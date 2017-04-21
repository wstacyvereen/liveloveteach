<?php
namespace Libsyn;

class Playlist {
	
    /**
     * Sets WP to use custom Libsyn Playlist for blog pages.
     * 
     * 
     * @return <type>
     */
	public static function PlaylistInit() {}
	
    /**
     * Registers the shortcode for WP usage
     * 
     * 
     * @return <type>
     */
	public static function embedShortcode($atts) {
		//make sure we have params we need
		$html = '';
		if(isset($atts['podcast'])&&!empty($atts['podcast'])) {
			if(strpos($atts['podcast'], 'libsyn-podcast-')!==false) {
				//regular my-podcast
				$atts['show_id'] = str_replace('libsyn-podcast-', '', $atts['podcast']);
				unset($atts['podcast']);
				$html .= self::getDataForLibsynPodcast($atts);
			} elseif($atts['podcast']==='my-podcast') {
				//my-podcast with no show_id
				$plugin = new Service();
				$api = $plugin->getApis();
				$show_id = $api->getShowId();
				if(is_numeric($show_id)) {
					$atts['show_id'] = $show_id;
					unset($atts['podcast']);
				}
				$html .= self::getDataForLibsynPodcast($atts);
			} else {
				//handle podcast url
				if(isset($atts['podcast'])&&!empty($atts['podcast'])){
					$atts['feed_url'] = $atts['podcast'];
					unset($atts['podcast']);
				}
				$html .= self::getDataForPodcast($atts);
			}
			return $html;
		}
	}
	
    /**
     * Gets the ajax script for enabling the playlist for other pocast urls
     * 
     * @param <array> $atts 
     * 
     * @return <type>
     */
	public static function getDataForPodcast($atts){
		global $content_width;
		$post = get_post();

		static $instance = 0;
		$instance++;
		$post_id = $post ? $post->ID : 0;
		if(!isset($atts['type'])||empty($atts['type'])) $atts['type'] = "audio";
		
		//will need to have javascript set these vars
		$atts['type'] = $atts['type'];
		$safe_type = $atts['type'];
		$atts['style'] = 'light';
		$safe_style = "light";
		$outer = 22; // default padding and border of wrapper

		$default_width = 640;
		$default_height = 360;

		$theme_width = empty( $content_width ) ? $default_width : ( $content_width - $outer );
		$theme_height = empty( $content_width ) ? $default_height : round( ( $default_height * $theme_width ) / $default_width );
		
		//need to set the query vars for the ajax
		$atts['load_playlist'] =  true;
		
		ob_start();
		?>
		<div class="wp-playlist wp-<?php echo $safe_type ?>-playlist wp-playlist-<?php echo $safe_style ?>" id="libsyn-playlist-<?php echo $post_id;?>">
			<?php if ( 'audio' === $atts['type'] ): ?>
		<div class="wp-playlist-current-item"></div>
			<?php endif ?>
			<<?php echo $safe_type ?> controls="controls" preload="none" width="<?php
			echo (int) $theme_width;
		?>"<?php if ( 'video' === $safe_type ):
			echo ' height="', (int) $theme_height, '"';
		else:
			echo ' style="visibility: hidden"';
		endif; ?>></<?php echo $safe_type ?>>
		<div class="wp-playlist-next" id="libsyn-playlist-next-<?php echo $post_id; ?>"></div>
		<div class="wp-playlist-prev" id="libsyn-playlist-prev-<?php echo $post_id; ?>"></div>
		<noscript>
			<ol></ol>
		</noscript>
		<script class="wp-playlist-script" id="libsyn-playlist-script-<?php echo $post_id;?>"  type="application/json"></script>
		</div>
		<?php
		if ( 1 === $instance ) {
		?>
		<script type="text/javascript">
		(function ($){
			$(document).ready(function() {
				var pageNum = 1; //will have to get the pageNum from links?
				var playlistUrl = "/?<?php echo http_build_query($atts)?>&page=" + pageNum;
				$.ajax({
					url: playlistUrl,
					type: "GET",
					dataType: "json",
					success: function (data, textStatus, xhr) {
						$("#libsyn-playlist-script-<?php echo $post_id;?>").empty().append(JSON.stringify(data));
							$('#libsyn-playlist-<?php echo $post_id;?>').each( function() {
								return new WPPlaylistView({ el: this });
							} );
						$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks").attr({
							style: "overflow-y:scroll;height:250px;"
						});
						if($("div[class='wp-playlist-tracks']").length > 1) { $("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks").next().remove(); }
					},
						error: function (xhr, status, error) {
					}
				});
			});
		}) (jQuery);
		</script>		
		<?php
			/**
			 * Print and enqueue playlist scripts, styles, and JavaScript templates.
			 *
			 * @since 3.9.0
			 *
			 * @param string $type  Type of playlist. Possible values are 'audio' or 'video'.
			 * @param string $style The 'theme' for the playlist. Core provides 'light' and 'dark'.
			 */
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		}
			return ob_get_clean();
	}
	
    /**
     * Gets the ajax script for enabling the libsyn playlist
     * 
     * @param <array> $atts 
     * 
     * @return <type>
     */
	public static function getDataForLibsynPodcast($atts){
		global $content_width;
		$post = get_post();

		static $instance = 0;
		$instance++;
		$post_id = $post ? $post->ID : 0;
		if(!isset($atts['type'])||empty($atts['type'])) $atts['type'] = "video";
		
		//will need to have javascript set these vars
		$atts['type'] = $atts['type'];
		$safe_type = $atts['type'];
		$atts['style'] = 'light';
		$safe_style = "light";
		if(!isset($atts['limit'])) $atts['limit'] = 5;
		$outer = 22; // default padding and border of wrapper

		$default_width = 640;
		$default_height = 360;

		$theme_width = empty( $content_width ) ? $default_width : ( $content_width - $outer );
		$theme_height = empty( $content_width ) ? $default_height : round( ( $default_height * $theme_width ) / $default_width );
		
		//need to set the query vars for the ajax
		$atts['load_libsyn_playlist'] =  true;
		
		ob_start();
		?>
		<div class="wp-playlist wp-<?php echo $safe_type ?>-playlist wp-playlist-<?php echo $safe_style ?>" id="libsyn-playlist-<?php echo $post_id;?>">
			<?php if ( 'audio' === $atts['type'] ): ?>
		<div class="wp-playlist-current-item"></div>
			<?php endif ?>
			<<?php echo $safe_type ?> controls="controls" preload="none" width="<?php
			echo (int) $theme_width;
		?>"<?php if ( 'video' === $safe_type ):
			echo ' height="', (int) $theme_height, '"';
		else:
			echo ' style="visibility: hidden"';
		endif; ?>></<?php echo $safe_type ?>>
		<div class="wp-playlist-next" id="libsyn-playlist-next-<?php echo $post_id; ?>"></div>
		<div class="wp-playlist-prev" id="libsyn-playlist-prev-<?php echo $post_id; ?>"></div>
		<noscript>
			<ol></ol>
		</noscript>
		<script class="wp-playlist-script" id="libsyn-playlist-script-<?php echo $post_id;?>"  type="application/json"></script>
		</div>
		<?php
		if ( 1 === $instance ) {
		?>
		<script type="text/javascript">
		(function ($){
			$(document).ready(function() {
				var playerAjax = function (exisitingObj, pageNum, scrollPosition) {
					if(typeof pageNum == "undefined") {
						var pageNum = 1;
					}
					var playlistUrl = "/?<?php echo http_build_query($atts)?>&page=" + pageNum;
					$.ajax({
						url: playlistUrl,
						type: "GET",
						dataType: "json",
						success: function (data, textStatus, xhr) {
							if(typeof exisitingObj == "undefined") {
								$("#libsyn-playlist-script-<?php echo $post_id;?>").empty().append(JSON.stringify(data));
							} else {
								for(i=0; i < data.tracks.length; i++) {
									exisitingObj.tracks.push(data.tracks[i]);
								}
								$("#libsyn-playlist-script-<?php echo $post_id;?>").empty().append(JSON.stringify(exisitingObj));
								$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks").remove();
							}
							$('#libsyn-playlist-<?php echo $post_id;?>').each( function() {
								return new WPPlaylistView({ el: this });
							});
							var tracksHeight = (<?php echo intval($atts['limit']);?>*1.9);
							var tracksElement = $("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks");
							tracksElement.attr({
								style: "overflow-y:scroll;height:" + tracksHeight + "em;"
							});
							
							if(typeof scrollPosition != "undefinded") { tracksElement.scrollTop((scrollPosition-10)); }
							if($("div[class='wp-playlist-tracks']").length > 1) { $("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks").next().remove(); }
							if(pageNum > 1) {
								$("div#libsyn-playlist-<?php echo $post_id; ?> a.wp-playlist-caption").click(function() {
									var mediaElement = $("#libsyn-playlist-<?php echo $post_id; ?>").find(".mejs-mediaelement");
									if("<?php echo $atts['type']; ?>" == "audio") {
										
										var playerElement = mediaElement.find("<?php echo strtoupper($atts['type']); ?>");
										playerElement.attr("src", $(this).attr("href")).stop();
										var controlsElement = $("#libsyn-playlist-<?php echo $post_id; ?>").find(".mejs-controls");
										controlsElement.find(".mejs-playpause-button").removeClass("mejs-pause").addClass("mejs-play");
										controlsElement.find(".mejs-playpause-button").find("BUTTON").attr({"aria-label": "Play","title": "Play"});
										$("#libsyn-playlist-<?php echo $post_id; ?> div.wp-playlist-current-item").find("SPAN").empty().append($(this).find("SPAN").html());
									 } else if("<?php echo $atts['type'];?>" == "video") {
										var playerId = $("#libsyn-playlist-<?php echo $post_id; ?>").find(".mejs-container").attr("id");
										var playerElement = window.mejs.players[playerId];
										mediaElement.find("<?php echo strtoupper($atts['type']); ?>").attr("src", $(this).attr("href"));
										playerElement.setSrc($(this).attr("href"));
										//playerElement.load();
										playerElement.play();
									 }
								});
							}
							$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-tracks").bind('scroll', function() {
								var scrollPosition = $(this).scrollTop() + $(this).outerHeight();
								var divTotalHeight = $(this)[0].scrollHeight 
													  + parseInt($(this).css('padding-top'), 10) 
													  + parseInt($(this).css('padding-bottom'), 10)
													  + parseInt($(this).css('border-top-width'), 10)
													  + parseInt($(this).css('border-bottom-width'), 10);

								if( scrollPosition == divTotalHeight ) {
									pageNum = pageNum + 1;
									var exisitingObj = $.parseJSON($("#libsyn-playlist-script-<?php echo $post_id;?>").html());
									playerAjax(exisitingObj, pageNum, scrollPosition);
								}
							});
						},
							error: function (xhr, status, error) {
						}
					});
				}
				playerAjax();
			});
		}) (jQuery);
		</script>
		<?php
			/**
			 * Print and enqueue playlist scripts, styles, and JavaScript templates.
			 *
			 * @since 3.9.0
			 *
			 * @param string $type  Type of playlist. Possible values are 'audio' or 'video'.
			 * @param string $style The 'theme' for the playlist. Core provides 'light' and 'dark'.
			 */
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
			wp_enqueue_script( 'jquery-tinyscrollbar', plugins_url(LIBSYN_DIR.'/lib/js/jquery.tinyscrollbar.min.js'));
			wp_enqueue_style( 'jquery-tinyscrollbar', plugins_url(LIBSYN_DIR.'/lib/css/tinyscrollbar.css'));
		}
			return ob_get_clean();
	}
	
    /**
     * Handles WP callback to send variable to trigger AJAX response.
     * 
     * @param <type> $vars 
     * 
     * @return <type>
     */
	public static function plugin_add_trigger_load_playlist($vars) {
		$vars[] = 'load_playlist';
		return $vars;
	}
	
    /**
     * Handles WP callback to send variable to trigger AJAX response
     * 
     * @param <type> $vars 
     * 
     * @return <type>
     */
	public static function plugin_add_trigger_load_libsyn_playlist($vars) {
		$vars[] = 'load_libsyn_playlist';
		return $vars;
	}
	
    /**
     * Function to represent the ajax response page for the playlist media objects.
     * 
     * 
     * @return <type>
     */
	public static function loadLibsynPlaylist() {
		if(intval(get_query_var('load_libsyn_playlist')) == 1) {
			$playlist = new Service\Playlist();
			$params = array();
			//Defaults
			if(isset($_GET['show_id'])) $params['show_id'] = $_GET['show_id'];
			if(isset($_GET['height'])) $height = $_GET['height']; else $height = 360;
			if(isset($_GET['width'])) $width = $_GET['width']; else $width = 640;
			if(isset($_GET['page'])) $params['page'] = $_GET['page']; else $params['page'] = 1;
			if(isset($_GET['limit'])) $params['limit'] = $_GET['limit']; else $params['limit'] = 5;
			if(isset($_GET['type'])) $params['type'] = $_GET['type'];


			if(isset($params['show_id'])) {
				$plugin = new Service();
				$data = $plugin->getEpisodes($params);
			}

			if(isset($data)) {
				$json = array();
				$json['title'] =  ''; //does not support getting show title yet.
				$json['description'] = ''; //does not support getting show description yet.
				$json['type'] = (isset($params['type']))?$params['type']:"video";
				$i=0;
				$json['page_count'] = $data->page_count;
				$json['page_size'] = $data->page_size;
				$json['total_items'] = $data->total_items;
				if(isset($data->_embedded->item)) {
					foreach($data->_embedded->item as $item) { //build output
					
						//set tracks
						$unset = false;
						if(isset($item->primary_content)) {
							if(isset($item->primary_content->mime_type)) {
								if(strpos($item->primary_content->mime_type, 'video')!==false) { $enclosureType = "video"; }
									elseif(strpos($item->primary_content->mime_type, 'audio')!==false) $enclosureType = "audio";
										else $enclosureType = null;
								if(!is_null($enclosureType)) {
									$json['tracks'][$i]['type'] = $enclosureType;
									if(isset($params['type'])&&$enclosureType=="audio"&&$enclosureType!=$params['type']) $unset = true;
								}
							}
							if(isset($item->primary_content->duration)) {
								$json['tracks'][$i]['meta']['length_formatted'] = $playlist->seconds_to_duration(intval($item->primary_content->duration));
							} else { $json['tracks'][$i]['meta']['length_formatted'] = ""; }
							if(isset($item->primary_content->url)) {
								$enclosureUrl = $item->primary_content->url;
								if(isset($enclosureUrl)) $json['tracks'][$i]['src'] = $enclosureUrl;
							}
						}
						
						//dimensions (video only)
						$json['tracks'][$i]['dimensions'] = array(
							'original' => array(
								'width' => $width,
								'height' => $height
							),
							'resized' => array(
								'width' => $width,
								'height' => $height
							)
						);
						
						//image
						$itemId = $item->id;
						if(!is_null($itemId)&&!empty($itemId)) $elementImage = 'https://assets.libsyn.com/item/'.$itemId;
							else $elementImage = '';						
						if(isset($elementImage)&&!empty($elementImage)) $image = $elementImage;
							elseif(isset($showImage)&&!empty($showImage)) $image = $showImage;
								else $image = '';
						if(isset($image)&&!empty($image)) { 
							$json['tracks'][$i]['image'] = array(
								'src' => $image,
								 'width' => 48,
								 'height' => 64,
							);
						}
						
						//thumb
						$json['tracks'][$i]['thumb'] = array(
							'src' => $image,
							'width' => 48,
							'height' => 64,
						);
						
						if(isset($item->item_title)) { $json['tracks'][$i]['title'] = $item->item_title; }
						if(isset($description)) { $json['tracks'][$i]['description'] = $description; unset($description); }
						if(is_null($enclosureType)||!isset($enclosureUrl)||($unset)) unset($json['tracks'][$i]);
							else $i =$i+1;
						// unset stuff for loop
						unset($enclosureType);
						unset($enclosureUrl);
						unset($elementImage);
					}
				} else $json = array();
			} else $json = array();
			//handle audio removal of dimensions
			if(isset($json)&&is_array($json)&&!empty($json)) {
				if($json['type'] == "audio") foreach($json['tracks'] as $row) unset($row['dimensions']);
				$json['tracklist'] = true;
				$json['tracknumbers'] = true;
				$json['images'] = true; //currently does not support images
				$json['artists'] = false;
			} else $json = array();

			header('Content-Type: application/json');
			echo json_encode($json);exit;
		}
	}
	
	public static function loadPlaylist() {
		if(intval(get_query_var('load_playlist')) == 1) {
			if(isset($_GET['feed_url'])) $feedUrl = $_GET['feed_url'];
			if(isset($_GET['height'])) $height = $_GET['height']; else $height = 360;
			if(isset($_GET['width'])) $width = $_GET['width']; else $width = 640;

			$feed = new \DOMDocument();
			$feed->load($feedUrl);
			$playlist = new Service\Playlist();
			$json = array();
			$json['title'] =  $playlist->clean($feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('title')->item(0)->firstChild->nodeValue);
			$descriptionNode = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('description')->item(0);
			if(isset($descriptionNode->firstChild)) $json['description'] = $playlist->clean($descriptionNode->nodeValue);
				elseif(isset($descriptionNode->nodeValue)) $json['description'] = $descriptionNode->nodeValue;
					else $json['description'] = '';

			// Initialize XPath    
			$xpath = new \DOMXpath($feed);
			// Register the itunes namespace
			$xpath->registerNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
			$showImageObj = $xpath->query('itunes:image', $feed->getElementsByTagName('channel')->item(0))->item(0);
			if(!is_null($showImageObj)&&$showImageObj!==false) $showImage = $showImageObj->getAttribute('href');
			$items = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('item');
			$i = 0;
			$type = 'audio'; //default to audio but check for video
			$images = false;//default to no images will check for images

			foreach($items as $item) { //get xml data
				$title = $item->getElementsByTagName('title')->item(0)->firstChild->nodeValue;
				//$description = $item->getElementsByTagName('description')->item(0)->firstChild->nodeValue;
				$itunesImage = $xpath->query('itunes:image', $item)->item(0);
				if(!is_null($itunesImage)) $elementImage = $itunesImage->getAttribute('href');
				if(isset($elementImage)&&!empty($elementImage)) { $images=true; }
				$elementDuration = $xpath->query('itunes:duration', $item)->item(0);
				$elementDurationLength = (!is_null($elementDuration))?$elementDuration->firstChild->nodeValue:null;
				if(!empty($elementDurationLength)) {
					if(strpos($elementDurationLength, ":")!== false) $length = $elementDurationLength;
					else $length = $playlist->seconds_to_duration(intval($elementDurationLength));
				}
				unset($elementDuration);
				
				$enclosure = $item->getElementsByTagName('enclosure')->item(0);
				if(!is_null($enclosure)) {
					$enclosureUrl = $enclosure->getAttribute('url');
					$enclosureType = $enclosure->getAttribute('type');
					if(!$playlist->check_enclosure($enclosureType)) $type = 'video';
						else $type = 'audio';
				}

				//set tracks
				if(isset($enclosureType)) { $json['tracks'][$i]['type'] = $type; unset($enclosureType); }
				if(isset($title)) { $json['tracks'][$i]['title'] = $title; unset($title); }
				if(isset($caption)) { $json['tracks'][$i]['caption'] = $caption; unset($caption); } //currently does not support caption.
				//if(isset($description)) { $json['tracks'][$i]['description'] = ''; unset($description); }
				if(isset($length)) { $json['tracks'][$i]['meta']['length_formatted'] = $length; unset($length); }
					else { $json['tracks'][$i]['meta']['length_formatted'] = ""; }
					
				//dimensions (video only)
				$json['tracks'][$i]['dimensions'] = array(
					'original' => array(
						'width' => $width,
						'height' => $height
					),
					'resized' => array(
						'width' => $width,
						'height' => $height
					)
				);
				
				//image
				if(isset($elementImage)&&!empty($elementImage)) $image = $elementImage;
					elseif(isset($showImage)&&!empty($showImage)) $image = $showImage;
						else $image = '';
				if(isset($image)&&!empty($image)) { 
					$json['tracks'][$i]['image'] = array(
						'src' => $image,
						 'width' => 48,
						 'height' => 64,
					);
				}
				
				//thumb
				$json['tracks'][$i]['thumb'] = array(
					'src' => $image,
					'width' => 48,
					'height' => 64,
				);
				if(isset($image)) unset($image); 
				if(isset($itunesImage)) unset($itunesImage);
				if(isset($elementImage)) unset($elementImage);
				if(isset($enclosureUrl)) { $json['tracks'][$i]['src'] = $enclosureUrl;  unset($enclosureUrl); }
				if(!isset($json['tracks'][$i]['src'])||empty($json['tracks'][$i]['src'])) unset($json['tracks'][$i]);
					else $i=$i+1;
			}
			$json['type'] = $type;
			//handle audio removal of dimensions
			if($type == "audio") foreach($json['tracks'] as $row) unset($row['dimensions']);

			$json['tracklist'] = true;
			$json['tracknumbers'] = true;
			$json['images'] = $images;
			$json['artists'] = false;

			header('Content-Type: application/json');
			echo json_encode($json);exit;
		}
	}
}

?>