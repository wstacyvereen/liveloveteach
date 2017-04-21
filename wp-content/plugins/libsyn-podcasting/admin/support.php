<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_style( 'metaBoxes', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn_meta_boxes.css' ); ?>
<?php wp_enqueue_style( 'metaForm', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn_meta_form.css' ); ?>
<h2><?php _e("Libsyn Wordpress Plugin Support", LIBSYN_DIR); ?></h2>

<div id="poststuff">
	<div id="post-body">
		<div id="post-body-content">
		<!-- BOS Initial Setup -->
		  <div class="stuffbox" style="width:93.5%">
			<h3 class="hndle"><span><?php _e("Initial Setup", LIBSYN_DIR); ?></span></h3>
			<div class="inside" style="margin: 15px;">
				<h4>Setting up a new Wordpress Account</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							You will need to setup an account with Libsyn if you don't have one already to host your podcast.  Please visit <a href="//www.libsyn.com">http://www.libsyn.com</a> to setup an account.
						</li>
						<li>
							Within your Libsyn account navigate to <strong>Settings > Wordpress Plugins</strong> or by visiting the following link to <a href="//four.libsyn.com/wordpress-plugins" target="_blank">Wordpress Plugins</a>.  Select <strong>ADD NEW WORDPRESS PLUGIN</strong>.
							<br><img src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/support/support_ss1.png'));?>" alt="Add new Wordpress Plugin">
						</li>
						<li>Choose an Application Name and the Domain of your Wordpress site.<br></li>
						<p></p><br><img src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/support/support_ss2.png'));?>" alt="Wordpress Plugin Added"><p></p>
						<li>Navigate to the Libsyn Podcast Plugin <a href="/wp-admin/admin.php?page=<?php _e(LIBSYN_DIR); ?>/admin/support.php">Settings</a> page.  Enter the above client id and secret and follow the login procedure to connect Wordpress to your Libsyn account.  Before posting make sure to choose your show from the Settings page after successfully connecting the plugin.</li>
					</ul>
				</div>
			</div>
		  </div>
		<!-- EOS Initial Setup -->
		<!-- BOS Usage -->
		  <div class="stuffbox" style="width:93.5%">
			<h3 class="hndle"><span><?php _e("Usage", LIBSYN_DIR); ?></span></h3>
			<div class="inside" style="margin: 15px;">
				<h4>Creating/Editing a New Podcast Post</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<p>
								Navigate to the <a href="/wp-admin/post-new.php">Post Episode</a> page.  Once the post page is loaded, you should see the <strong>Post Episode</strong> form.
							</p>
							<img src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/support/support_ss3.png'));?>" alt="Post Form">
							<p></p>
						</li>
						<li>
							The Libsyn Wordpress Plugin uses the Wordpress Post title as the title of your podcast episode and body as the episode description.  Fill out the fields in the form above to post a new episode.  **If you do not check the box <strong>Post Libsyn Episode<strong> this will not post a new podcast episode, but will post a new Wordpress post as normal.
						</li>
					</ul>
				</div>
				<hr />
				<h4>Adding a Podcast Playlist into Post</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<p>Navigate to the <a href="/wp-admin/post-new.php">Post Episode</a> page.  You will see a button below the title called <strong>Add Podcast Playlist</strong>, this will open a new dialog to <strong>Create Podcast Playlist</strong>.</p>
							<img src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/support/support_ss4.png'));?>" alt="Create Podcast Playlist">
							<p></p>
						</li>
						<li>
							<p><strong>Playlist Type:</strong>  Choose audio or video based on your podcast.  (If you have both audio and Video, choose Video.)</p>
							<p>
								This plugin supports either your Libsyn Podcast or inserting an rss of any other podcast.  If you would like to include a playlist of a non-Libsyn podcast choose <strong>Other Podcast</strong> and paste the link to the podcast rss feed below.
							</p>
						</li>
					</ul>
				</div>
				<hr />
				<h4>Adding a Podcast Playlist Sidebar Widget</h4>
				<div class="inside supportDiv">
					<ul>
						<li>
							<p>Navigate to the <strong>Appearence > <a href="/wp-admin/widgets.php">Widgets</a></strong> page.  Drag and drop the widget named <strong>Playlist Widget</strong> from the left to your desired theme widget area.
							<img src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/support/support_ss5.png'));?>" alt="Create Podcast Playlist Widget">
							<p></p>
						</li>
						<li>
							<p><strong>Playlist Type:</strong>  Choose audio or video based on your podcast.  (If you have both audio and Video, choose Video.)</p>
							<p>
								This plugin supports either your Libsyn Podcast or inserting an rss of any other podcast.  If you would like to include a playlist of a non-Libsyn podcast choose <strong>Other Podcast</strong> and paste the link to the podcast rss feed below.
							</p>
							<strong>**Note: you may see overlapping dimensions based on your theme, you may also adjust the default height/width of the player.</strong>
						</li>
					</ul>
				</div>
			</div>
		  </div>
		<!-- EOS Usage -->
		<!-- BOS Integration -->
		  <?php //TODO: Set "stuffbox" to display:none; remove this for the support of the integraqtion ?>
		  <div class="stuffbox" style="width:93.5%;display:none;">
			<h3 class="hndle"><span><?php _e("Integration", LIBSYN_DIR); ?></span></h3>
			<div class="inside supportDiv" style="margin: 15px;">
			<p>
				Migrating from the PowerPress Plugin to Libsyn Plugin.  We offer full support to migrate your exisiting podcast when hosting with Libsyn.
			</p>
			<p>The Powerpress plugin will need to be active at the time of integration.  You will only need to provide your Powerpress feed url for submission for integration to the Libsyn Wordpress Plugin.  This will do a couple of things, first it will enable your existing podcast feed url to be redirected to the new one (if applicable).  Then it will automatically update all your Podcast's hosting episodes be available using the Libsyn as the Podcast Feed Host (Again, if applicable).</p>
			<p>After selecting a show and submitting your Powerpress feed url, you will be ready for use with the Libsyn Podcast Plugin!  You may now deactivate the Powerpress plugin at this time.</p>
			
			</div>
		  </div>
		<!-- EOS Integration -->
		<div>
	<div>
<div>