<?php
require_once( plugin_dir_path( __FILE__ ) .  'lib/functions.php');

/* Check for Podcast Playlists */
$podcasts = getSavedPodcasts();

/* Handle Adding New RSS */
if(!empty($_POST)) handlePost($_POST);
?>

<style media="screen" type="text/css">
.code { font-family:'Courier New', Courier, monospace; }
.code-bold {
	font-family:'Courier New', Courier, monospace; 
	font-weight: bold;
}
</style>





<div class="wrap">
  <h2><?php _e("Libsyn Playlist Settings", LIBSYN_DIR); ?></h2>
  <form name="<?php echo LIBSYN_KEY . "form" ?>" method="post" action="">
     <div id="poststuff">
      <div id="post-body">
        <div id="post-body-content">
          <div class="stuffbox">
            <h3 class="hndle"><span><?php _e("Add New Playlist", LIBSYN_DIR); ?></span></h3>
            <div class="inside" style="margin: 15px;">
              <p><em><?php _e("Enter a podcast feed url and title to your a playlist.", LIBSYN_DIR); ?></em></p>
              <table class="form-table">
                <tr valign="top">
                  <th><?php _e("Feed Url:", LIBSYN_DIR); ?></th>
                  <td>
					<input id="podcast_feed_url" type="text" value="http://www.yourfeed.com/feed" name="podcast_feed_url" />
					<input id="podcast_feed_button" type="button" value="check" name="podcast_feed_button" onclick="form.submit();" <?php checked("check", get_option(LIBSYN_KEY . "podcast_feed_url")); ?> />		 
                  </td>
                </tr>
              </table>
            </div>
          </div>
<?php IF(isset($podcasts)): ?>
          <div class="stuffbox">
            <h3 class="hndle"><span><?php _e("Saved Playlists", LIBSYN_DIR); ?></span></h3>
            <div class="inside" style="margin: 15px;">
              <table class="form-table">
<?php  FOREACH($podcasts as $podcast): ?>
                <tr valign="top">
                  <th><?php _e($podcast->post_title, LIBSYN_DIR); ?></th>		
                  <td>
					id: <?php _e($podcast->ID); ?>
					<input id="podcast_delete_button" type="button" value="delete" name="podcast_feed_button" onclick="form.submit();" <?php checked("delete", get_option(LIBSYN_KEY . "podcast_delete_button")); ?> />		 
                  </td>
                </tr>
<?php  ENDFOREACH; ?>
              </table>
            </div>
          </div>
<?php ENDIF; ?>		  
        </div>
      </div>
    </div>
  </form>
</div>

<?php IF(isset($json)&&!empty($json)): ?>
<script type="text/html" id="tmpl-wp-playlist-current-item">
	<# if ( data.image ) { #>
	<img src="{{ data.thumb.src }}"/>
	<# } #>
	<div class="wp-playlist-caption">
		<span class="wp-playlist-item-meta wp-playlist-item-title">&#8220;{{ data.title }}&#8221;</span>
		<# if ( data.meta.album ) { #><span class="wp-playlist-item-meta wp-playlist-item-album">{{ data.meta.album }}</span><# } #>
		<# if ( data.meta.artist ) { #><span class="wp-playlist-item-meta wp-playlist-item-artist">{{ data.meta.artist }}</span><# } #>
	</div>
</script>
<script type="text/html" id="tmpl-wp-playlist-item">
	<div class="wp-playlist-item">
		<a class="wp-playlist-caption" href="{{ data.src }}">
			{{ data.index ? ( data.index + '. ' ) : '' }}
			<# if ( data.caption ) { #>
				{{ data.caption }}
			<# } else { #>
				<span class="wp-playlist-item-title">&#8220;{{{ data.title }}}&#8221;</span>
				<# if ( data.artists && data.meta.artist ) { #>
				<span class="wp-playlist-item-artist"> &mdash; {{ data.meta.artist }}</span>
				<# } #>
			<# } #>
		</a>
		<# if ( data.meta.length_formatted ) { #>
		<div class="wp-playlist-item-length">{{ data.meta.length_formatted }}</div>
		<# } #>
	</div>
</script>
<?php ENDIF; ?>