<?php
namespace Libsyn;

class PlaylistWidget extends \WP_Widget {

	/**
	 * Adds PlaylistWidget to WP
	 */
	function __construct() {
		parent::__construct(
			'libsyn_playlist_widget', // Base ID
			__( 'Libsyn Playlist', 'libsyn_playlist_domain' ) // Name
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args	Widget Arguements
	 * @param array $instance	Saved values from DB
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		//call correct widget if set
		if(!empty($instance)&&!empty($instance['playlist_type'])&&!empty($instance['playlist_feed_type'])) {
			$atts = array();
			$atts['type'] = $instance['playlist_type'];
			if($instance['playlist_type']==='video') {
				$atts['width'] = (!empty($instance['playlist_video_width']))?intval($instance['playlist_video_width']):240;
				$atts['height'] = (!empty($instance['playlist_video_height']))?intval($instance['playlist_video_height']):180;
			}

			$atts['id'] = (isset($instance['playlist_widget_id'])&&!empty($instance['playlist_widget_id']))?intval($instance['playlist_widget_id']):1;
			if(isset($instance['playlist_feed_type'])&&$instance['playlist_feed_type']==='other-podcast') {
				//handle which player
				if(isset($instance['podcast_url'])&&!empty($instance['podcast_url'])) {
					$atts['feed_url'] = $instance['podcast_url'];
					echo $this->getDataForPodcast($atts);
				}
			} elseif(isset($instance['playlist_feed_type'])&&strpos($instance['playlist_feed_type'], 'libsyn-podcast-')!==false) {
				$atts['show_id'] = str_replace('libsyn-podcast-', '', $instance['playlist_feed_type']);
				echo $this->getDataForLibsynPodcast($atts);
			}
		}
		echo $args['after_widget'];
	}
	
    /**
     * Gets the ajax script for enabling the playlist for other pocast urls
     * 
     * @param <array> $atts 
     * 
     * @return <type>
     */
	public static function getDataForPodcast($atts){
		$content_width = ''; //can change in future. (for sidebar width)
		static $instanceNum = 0;
		$instanceNum++;
		$post_id = 'widget-'.$atts['id'];
		if(!isset($atts['type'])||empty($atts['type'])) $atts['type'] = "audio";
		
		//will need to have javascript set these vars
		$atts['type'] = $atts['type'];
		$safe_type = $atts['type'];
		$atts['style'] = 'light';
		$safe_style = "light";
		$outer = 11; // default padding and border of wrapper
		
		if(!isset($atts['width'])) $atts['width'] = 191;
		if(!isset($atts['height'])) $atts['height'] = 300;

		$theme_width = empty( $content_width ) ? $atts['width'] : ( $atts['width'] - $outer );
		$theme_height = empty( $content_width ) ? $atts['height'] : round( ( $atts['height'] * $theme_width ) / $theme_width );
		
		//need to set the query vars for the ajax
		$atts['load_playlist'] =  true;
		
		ob_start();
		?>
		<div class="wp-playlist wp-<?php echo $safe_type ?>-playlist wp-playlist-<?php echo $safe_style ?>" id="libsyn-playlist-<?php echo $post_id;?>" >
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
		if ( 1 === $instanceNum ) {
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
						//if(data.type == "video") { $("#libsyn-playlist-<?php echo $post_id; ?>").find(".mejs-container").css({'style':'width: 226px'}); }
						//if(data.type == "video") { $("#libsyn-playlist-<?php echo $post_id; ?>").find(".mejs-poster").css({'style':'width: 226px'}); }
						$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-item-title").addClass("smallerListFont");
						$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-item-length").addClass("smallerListFont");
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
			wp_enqueue_style('libsyn-playlist-widget', plugins_url(LIBSYN_DIR.'/lib/css/libsyn_playlist_widget.css'));
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
		$content_width = ''; //can change in future. (for sidebar width)
		static $instanceNum = 0;
		$instanceNum++;
		$post_id = 'widget-'.$atts['id'];
		if(!isset($atts['type'])||empty($atts['type'])) $atts['type'] = "video";
		
		//will need to have javascript set these vars
		$atts['type'] = $atts['type'];
		$safe_type = $atts['type'];
		$atts['style'] = 'light';
		$safe_style = "light";
		$outer = 11; // default padding and border of wrapper
		if(!isset($atts['limit'])) $atts['limit'] = 5;

		if(!isset($atts['width'])) $atts['width'] = 191;
		if(!isset($atts['height'])) $atts['height'] = 300;
		
		$theme_width = empty( $content_width ) ? $atts['width'] : ( $atts['width'] - $outer );
		$theme_height = empty( $content_width ) ? $atts['height'] : round( ( $atts['height'] * $theme_width ) / $theme_width );
		
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
		if ( 1 === $instanceNum ) {
		?>
		<script type="text/javascript">
		(function ($){
			$(document).ready(function() {
				var playerAjax = function (exisitingObj, pageNum, scrollPosition) {
					if(typeof pageNum == "undefined") {
						var pageNum = 1;
					}
					var playlistUrl = "/?<?php echo http_build_query($atts)?>&page=" + pageNum;
					console.log(playlistUrl);
					$.ajax({
						url: playlistUrl,
						type: "GET",
						dataType: "json",
						success: function (data, textStatus, xhr) {
							console.log(data);
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
							$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-item-title").addClass("smallerListFont");
							$("#libsyn-playlist-<?php echo $post_id; ?>").find(".wp-playlist-item-length").addClass("smallerListFont");
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
			wp_enqueue_style('libsyn-playlist-widget', plugins_url(LIBSYN_DIR.'/lib/css/libsyn_playlist_widget.css'));
		}
			return ob_get_clean();
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$plugin = new Service();
		$api = $plugin->getApis();
		$my_podcast_text = (isset($api)&&($api!==false))?"libsyn-podcast-".$api->getShowId():"my-podcast";
		
		$podcast_url = ! empty( $instance['podcast_url'] ) ? $instance['podcast_url'] : __( '', 'text_domain' );
		$playlist_type = ! empty( $instance['playlist_type'] ) ? $instance['playlist_type'] : __( '', 'text_domain' );
		$playlist_feed_type = ! empty( $instance['playlist_feed_type'] ) ? $instance['playlist_feed_type'] : __( '', 'text_domain' );
		$playlist_video_width = ! empty( $instance['playlist_video_width'] ) ? $instance['playlist_video_width'] : 240;
		$playlist_video_height = ! empty( $instance['playlist_video_height'] ) ? $instance['playlist_video_height'] : 180;
		//get the playlist id
		$typeStr = $this->get_field_id( 'playlist_type' );
		$typeArr = explode('-', $typeStr);
		$id = intval($typeArr[2]);
		

		?>
			<p>
				<span style="font-weight:bold;">Playlist Type:</span><br>
				<input type="radio" name="<?php echo $this->get_field_name( 'playlist_type' ); ?>" value="audio" id="<?php echo $this->get_field_id( 'playlist_type' ); ?>_audio" onclick="hideDimensionsDiv<?php echo $id; ?>()" <?php echo (esc_attr( $playlist_type ) === 'audio') ? 'checked="checked"' : ''; if(empty($playlist_type)) echo 'checked="checked"'; ?> /><label for="<?php _e( 'playlist_type', 'playlist-widget' ); ?>">Audio</label>
				<input type="radio" name="<?php echo $this->get_field_name( 'playlist_type' ); ?>" value="video" id="<?php echo $this->get_field_id( 'playlist_type' ); ?>_video" onclick="showDimensionsDiv<?php echo $id; ?>()" <?php echo (esc_attr( $playlist_type ) === 'video') ? 'checked="checked"' : ''; ?> /><label for="<?php _e( 'playlist_type', 'playlist-widget' ); ?>">Video</label>
				<div style="padding:5px;display:none;" id="<?php echo $this->get_field_id( 'playlist_type' ); ?>_playlist-dimensions-div">
					<label for="<?php _e( 'playlist_video_width', 'playlist-widget' ); ?>">Width</label>
					<input name="<?php echo $this->get_field_name( 'playlist_video_width' ); ?>" id="playlist_video_width-<?php _e($id);?>" type="text" value="<?php _e($playlist_video_width);?>">
					<br>
					<label for="<?php _e( 'playlist_video_height', 'playlist-widget' ); ?>">Height</label>
					<input name="<?php echo $this->get_field_name( 'playlist_video_height' ); ?>" id="playlist_video_height-<?php _e($id);?>" type="text" value="<?php _e($playlist_video_height);?>">
				</div>
				<br><span style="font-weight:bold;">Playlist Source:</span><br>
				<input type="radio" name="<?php echo $this->get_field_name( 'playlist_feed_type' ); ?>" value="<?php echo $my_podcast_text; ?>" id="my-podcast-<?php _e($id);?>" onclick="hideOtherUrl<?php echo $id; ?>()" <?php echo (esc_attr( $playlist_feed_type ) == $my_podcast_text) ? 'checked="checked"' : ''; if(empty($playlist_feed_type)) echo 'checked="checked"'; ?> /><label for="<?php _e( 'playlist_feed_type', 'playlist-widget' ); ?>">My Libsyn Podcast</label>
				<br>
				<input type="radio" name="<?php echo $this->get_field_name( 'playlist_feed_type' ); ?>" value="other-podcast" id="other-podcast-<?php _e($id);?>" onclick="showOtherUrl<?php echo $id; ?>()" <?php echo (esc_attr( $playlist_feed_type ) == 'other-podcast') ? 'checked="checked"' : ''; ?> /><label for="<?php _e( 'playlist_feed_type', 'playlist-widget' ); ?>">Other Podcast</label>
				<div id="<?php echo $this->get_field_id( 'podcast_url' ); ?>_div" style="display:none;">
					<p>
					<label for="<?php _e( 'podcast_url', 'playlist-widget' ); ?>"><?php _e( 'Podcast Url:' ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'podcast_url' ); ?>" name="<?php echo $this->get_field_name( 'podcast_url' ); ?>" type="text" value="<?php echo esc_attr( $podcast_url ); ?>" type="url" class="other-url" placeholder="http://www.your-wordpress-site.com/rss">
					</p>
				</div>
				<input type="hidden" id="<?php echo $this->get_field_id( 'playlist_widget_id' ); ?>" name="<?php echo $this->get_field_name( 'playlist_widget_id' ); ?>" value="<?php _e($id); ?>">
			</p>	
		<script type="text/javascript">
				var showOtherUrl<?php echo $id; ?> = function(){
					jQuery('#<?php echo $this->get_field_id( 'podcast_url' ); ?>_div').fadeIn('normal');
				}
				var hideOtherUrl<?php echo $id; ?> = function(){
					jQuery('#<?php echo $this->get_field_id( 'podcast_url' ); ?>_div').hide();
				}
				var showDimensionsDiv<?php echo $id; ?> = function(){
					jQuery("#<?php echo $this->get_field_id( 'playlist_type' ); ?>_playlist-dimensions-div").fadeIn("normal");
				}
				var hideDimensionsDiv<?php echo $id; ?> = function(){
					jQuery("#<?php echo $this->get_field_id( 'playlist_type' ); ?>_playlist-dimensions-div").hide();
				}
				if (jQuery('#<?php echo $this->get_field_id( 'playlist_type' ); ?>_video:checked').val() == 'video') {
					showDimensionsDiv<?php echo $id; ?>();
				}
				if (jQuery('#other-podcast-<?php _e($id);?>:checked').val() == 'other-podcast') {
					showOtherUrl<?php echo $id; ?>();
				}
			jQuery( document ).on( 'widget-added widget-updated', function() {
				//console.log(jQuery("[id$='podcast_url_div']"));
				jQuery("[id$='playlist_type_video']").click(function(){
					jQuery("[id$='playlist_type_playlist-dimensions-div']").fadeIn("normal");
				});
				jQuery("[id$='playlist_type_audio']").click(function(){
					jQuery("[id$='playlist_type_playlist-dimensions-div']").hide();
				});
				jQuery("[id^='other-podcast']").click(function(){
					jQuery("[id$='podcast_url_div']").fadeIn('normal');
				});
				jQuery("input[id^='my-podcast-']").click(function(){
					jQuery("[id$='podcast_url_div']").hide();
				});
			});
		</script>			

		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['playlist_widget_id'] = ( ! empty( $new_instance['playlist_widget_id'] ) ) ? strip_tags( $new_instance['playlist_widget_id'] ) : '';
		$instance['podcast_url'] = ( ! empty( $new_instance['podcast_url'] ) ) ? strip_tags( $new_instance['podcast_url'] ) : '';
		$instance['playlist_type'] = ( ! empty( $new_instance['playlist_type'] ) ) ? strip_tags( $new_instance['playlist_type'] ) : '';
		$instance['playlist_feed_type'] = ( ! empty( $new_instance['playlist_feed_type'] ) ) ? strip_tags( $new_instance['playlist_feed_type'] ) : '';
		$instance['playlist_video_width'] = ( ! empty( $new_instance['playlist_video_width'] ) ) ? strip_tags( $new_instance['playlist_video_width'] ) : '';
		$instance['playlist_video_height'] = ( ! empty( $new_instance['playlist_video_height'] ) ) ? strip_tags( $new_instance['playlist_video_height'] ) : '';
		
		return $instance;
	}
}

?>