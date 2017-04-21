<?php

global $wp_version;

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if (isset($_POST['Uninstall'])) {
  uninstall();
} else {  }
  
function uninstall() {
/*
	global $wpdb;

	$meta_query = "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '" . LIBSYN_KEY . "%';";
	$option_query = "DELETE FROM $wpdb->options WHERE option_name LIKE '" . LIBSYN_KEY . "%';";
	$post_query = "DELETE FROM $wpdb->posts WHERE post_type = 'jw_playlist';";

	$wpdb->query($meta_query);
	$wpdb->query($option_query);
	$wpdb->query($post_query);

	@unlink(LongTailFramework::getPlayerPath());
	@unlink(LongTailFramework::getEmbedderPath());
	@rmdir(JWPLAYER_FILES_DIR . "/player/");

	$handler = @opendir(JWPLAYER_FILES_DIR . "/configs");
	if ($handler) {
	while ($file = readdir($handler)) {
	  if ($file != "." && $file != ".." && strstr($file, ".xml")) {
		@unlink(JWPLAYER_FILES_DIR . "/configs/$file");
	  }
	}
	closedir($handler);
	}
	@rmdir(JWPLAYER_FILES_DIR . "/configs/");
	@rmdir(JWPLAYER_FILES_DIR);

	update_option(LIBSYN_KEY . "uninstalled", true);
	feedback_message(__('Files and settings deleted.  The plugin can now be deactivated.', 'jw-player-plugin-for-wordpress'));
*/
}

function feedback_message ($message, $timeout = 0) { ?>
  <div class="fade updated" id="message" onclick="this.parentNode.removeChild (this)">
    <p><strong><?php echo $message ?></strong></p>
  </div> <?php
}

?>
<style media="screen" type="text/css">
.code { font-family:'Courier New', Courier, monospace; }
.code-bold {
	font-family:'Courier New', Courier, monospace; 
	font-weight: bold;
}
</style>
<div class="wrap">
  <h2><?php _e("Libsyn Plugin Settings", LIBSYN_DIR); ?></h2>
  <form name="<?php echo LIBSYN_KEY . "form" ?>" method="post" action="">

     <div id="poststuff">
      <div id="post-body">
        <div id="post-body-content">
          <div class="stuffbox">
            <h3 class="hndle"><span><?php _e("Shortcode Settings", LIBSYN_DIR); ?></span></h3>
            <div class="inside" style="margin: 15px;">
              <p><em><?php _e("Here is the general usage and examples for the <span class=\"code-bold\">[podcast]</span>, also supports <span class=\"code-bold\">[iframe]</span> shortcode (same usage).", LIBSYN_DIR); ?></em></p>
              <table class="form-table">
                <tr valign="top">
                  <th><?php _e("Parameters:", LIBSYN_DIR); ?></th>
                  <td>
				  <ul style="list-style-type: none;">
					<li><span class="code-bold">src</span>- source of the player <span class="code">`[podcast src="http://www.youtube.com/embed/A3PDXmYoF5U"]`</span> (by default src="http://www.youtube.com/embed/A3PDXmYoF5U")</li>
					<li><span class="code-bold">width</span>- width in pixels or in percents <span class="code">`[podcast width="100%" src="http://www.youtube.com/embed/A3PDXmYoF5U"]`</span> or <span class="code">`[podcast width="640" src="http://www.youtube.com/embed/A3PDXmYoF5U"]`</span> (by default width="100%")</li>
					<li><span class="code-bold">height</span>- height in pixels <span class="code">`[podcast height="480" src="http://www.youtube.com/embed/A3PDXmYoF5U"]`</span> (by default height="480")</li>
					<li><span class="code-bold">scrolling</span>- parameter <span class="code">`[podcast scrolling="yes"]`</span> (by default scrolling="no")</li>
					<li><span class="code-bold">frameborder</span>- parameter <span class="code">`[podcast frameborder="0"]`</span> (by default frameborder="0")</li>
					<li><span class="code-bold">marginheight</span>- parameter <span class="code">`[pocast marginheight="0"]`</span> (removed by default)</li>
					<li><span class="code-bold">marginwidth</span>- parameter <span class="code">`[podcast marginwidth="0"]`</span> (removed by default)</li>
					<li><span class="code-bold">allowtransparency</span>- allows to set transparency of the player <span class="code">`[podcast allowtransparency="true"]`</span> (removed by default)</li>
					<li><span class="code-bold">id</span>- allows to add the id of the player <span class="code">`[podcast id="my-id"]`</span> (removed by default)</li>
					<li><span class="code-bold">class</span>- allows to add the class of the player <span class="code">`[podcast class="my-class"]`</span> (by default class="podcast-class")</li>
					<li><span class="code-bold">style</span>- allows to add the css styles of the player <span class="code">`[podcast style="margin-left:-30px"]`</li>
				  </ul>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
	<div id="poststuff">
      <div id="post-body">
        <div id="post-body-content">
          <div class="stuffbox">
            <h3 class="hndle"><span><?php _e("Uninstall", LIBSYN_DIR); ?></span></h3>
            <div class="inside" style="margin: 15px;">
              <table>
                <tr valign="top">
                  <td>
                    <div>
                      <p><?php _e('To fully remove the plugin, click the Uninstall button.  Deactivating without uninstalling will not remove the data created by the plugin.', LIBSYN_DIR) ;?></p>
                    </div>
                    <p><span style="color: red; "><strong><?php _e('WARNING:', LIBSYN_DIR) ;?></strong><br />
                    <?php _e('This cannot be undone.  Since this is deleting data from your database, it is recommended that you create a backup.', LIBSYN_DIR) ;?></span></p>
                    <div align="left">
                      <input type="submit" name="Uninstall" class="button-secondary delete" value="<?php _e('Uninstall plugin', LIBSYN_DIR) ?>" onclick="return confirm('<?php _e('You are about to Uninstall this plugin from WordPress.\nThis action is not reversible.\n\nChoose [Cancel] to Stop, [OK] to Uninstall.\n', LIBSYN_DIR); ?>');"/>
                    </div>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>