<?php
$plugin = new Libsyn\Service();
$sanitize = new Libsyn\Service\Sanitize();
$api = $plugin->getApis();
$render = true;
$error = false;

/* Handle saved api */
if ($api instanceof Libsyn\Api && !$api->isRefreshExpired()){
	$refreshApi = $api->refreshToken(); 
	if($refreshApi) { //successfully refreshed
		$api = $api->retrieveApiById($api->getPluginApiId());
	} else { //in case of a api call error...
		$handleApi = true; 
		$clientId = (!isset($clientId))?$api->getClientId():$clientId; 
		$clientSecret = (!isset($clientSecret))?$api->getClientSecret():$clientSecret; 
		$api = false;
		if(isset($showSelect)) unset($showSelect);
	}
}

if(isset($_POST['msg'])) $msg = $_POST['msg'];
if(isset($_POST['error'])) $error = ($_POST['error']==='true')?true:false;

/* Handle Form Submit */
if (isset($_POST['submit'])||isset($_POST['libsyn_settings_submit'])) { //has showSelect on form.
	if($api instanceof Libsyn\Api) { //Brand new setup or changes?
		if($_POST['submit']==='Save Player Settings') { //has Player Settings Update
			if(isset($_POST['clear-settings-data'])) {
				$check = $sanitize->clear_settings($_POST['clear-settings-data']);
				if($check === true) {
					$plugin->removeSettings($api);
					$msg = "Settings Cleared";
					echo $plugin->redirectUrlScript(admin_url().'?page='.LIBSYN_DIR.'/admin/settings.php&msg='.$msg); 
				} else {
					$msg = "There was a problem when trying to remove settings.";
					$error = true;
				}
			}
			//sanitize player_settings
			$playerSettings = array();
			if(!isset($_POST['player_use_thumbnail'])) $playerSettings['player_use_thumbnail'] = '';
				else $playerSettings['player_use_thumbnail'] = $_POST['player_use_thumbnail'];
			$playerSettings['player_use_theme'] = $_POST['player_use_theme'];
			$playerSettings['player_height'] = $_POST['player_height'];
			$playerSettings['player_width'] = $_POST['player_width'];
			$playerSettings['player_placement'] = $_POST['player_placement'];
			$playerSettings['player_custom_color'] = $_POST['player_custom_color'];
			
			if(!isset($_POST['player_use_download_link'])) $playerSettings['player_use_download_link'] = '';
				else $playerSettings['player_use_download_link'] = $_POST['player_use_download_link'];
			$playerSettings['player_use_download_link_text'] = $_POST['player_use_download_link_text'];
			$playerSettings_clean = $sanitize->player_settings($playerSettings);

			if(!$playerSettings_clean||empty($playerSettings_clean)) { //malformed data
				$error =  true; $msg = __('Something wrong with player input settings, please try again.', $plugin->getTextDom());
			} elseif(is_array($playerSettings_clean)) { //looks good update options
				foreach ($playerSettings_clean as $key => $val) {
					update_option('libsyn-podcasting-'.$key, $val);
				}
			}
			
		} elseif ($_POST['submit']==='Save Changes' || $_POST['libsyn_settings_submit']==='Save Changes') { //has config changes or update
			if(!is_null($api->getClientId())) { //check for cleared data
				if (isset($_POST['showSelect'])) $api->setShowId($_POST['showSelect']);
				if($api->getClientSecret()!==$sanitize->clientSecret($_POST['clientSecret'])) $api->setClientSecret($sanitize->clientSecret($_POST['clientSecret']));
				if($api->getClientId()!==$sanitize->clientId($_POST['clientId'])) $api->setClientId($sanitize->clientId($_POST['clientId']));
				if(!isset($_POST['feed_redirect_url'])) $_POST['feed_redirect_url'] = '';
				if($api->getFeedRedirectUrl()!==$_POST['feed_redirect_url']) $api->setFeedRedirectUrl($_POST['feed_redirect_url']);
				$update = $plugin->updateSettings($api);
				if($update!==false) $msg = __('Settings Updated',$plugin->getTextDom());
				
				//do feed import
				$show_id = $api->getShowId();
				if($api->getFeedRedirectUrl()!==$_POST['feed_redirect_url']&&!empty($_POST['feed_redirect_url'])&&!empty($show_id)) {
					$feedImport = $plugin->feedImport($api);
					if(!$feedImport) { $msg = __('Feed Import failed, check data or try again later.', $plugin->getTextDom()); $error = true; }
					$importer = new LIbsyn\Service\Importer();
					$importer->setFeedRedirect($api);
				}
			} else { //doesn't have client id data saved (must be cleared data update)
				if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) { 
					update_option('libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret']))); 
					$clientId = $_POST['clientId']; 
				}
			}
		}
	} else { // for brand new setup just store in session through redirects.
		if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) {
			update_option('libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret']))); 
			$clientId = $_POST['clientId']; 
		}
	}
}

/* Handle API Creation/Update*/
if((!$api)||($api->isRefreshExpired())) { //does not have $api setup yet in WP
	$render = false;
}

?>


<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_script('jquery_validate', plugins_url(LIBSYN_DIR.'/lib/js/jquery.validate.min.js'), array('jquery')); ?>
<?php wp_enqueue_script('libsyn_meta_validation', plugins_url(LIBSYN_DIR.'/lib/js/meta_form.js')); ?>
<?php wp_enqueue_style( 'metaBoxes', plugins_url(LIBSYN_DIR.'/lib/css/libsyn_meta_boxes.css' )); ?>
<?php wp_enqueue_style( 'metaForm', plugins_url(LIBSYN_DIR.'/lib/css/libsyn_meta_form.css' )); ?>
<?php wp_enqueue_script( 'colorPicker', plugins_url(LIBSYN_DIR.'/lib/js/jquery.colorpicker.js' )); ?>
<?php wp_enqueue_style( 'colorPickerStyle', plugins_url(LIBSYN_DIR.'/lib/css/jquery.colorpicker.css' )); ?>

	<style media="screen" type="text/css">
	.code { font-family:'Courier New', Courier, monospace; }
	.code-bold {
		font-family:'Courier New', Courier, monospace; 
		font-weight: bold;
	}
	</style>

	<div class="wrap">
	<?php if (isset($msg)) echo $plugin->createNotification($msg, $error); ?>
	  <h2><?php _e("Post Episode", $plugin->getTextDom()); ?><span style="float:right"><a href="http://www.libsyn.com/"><img src="<?php _e(plugins_url( LIBSYN_DIR . '/lib/images/libsyn_dark-small.png'), $plugin->getTextDom()); ?>" title="Libsyn Podcasting" height="28px"></a></span></h2>
	  <form name="<?php echo LIBSYN_KEY . "form" ?>" id="<?php echo LIBSYN_KEY . "form" ?>" method="post" action="javascript:void(0);">
		 <div id="poststuff">
		  <div id="post-body">
			<div id="post-body-content">
			<?php if((isset($api) && ($api !== false)) || $render) { ?>
			
			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Post Actions", $plugin->getTextDom()); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <table class="form-table">
					<tr valign="top">
					  <th>Create New Episode</th>
					  <td valign="top">
						<?php submit_button(__('Create New Episode', $plugin->getTextDom()), 'primary', 'libsyn_create_post', false, array('id' => 'submit_create_post', 'onClick' => "document.getElementById('submit_create_post').value='Create New Episode';")); ?>
					  </td>
					</tr>
					<tr valign="top">
						<th></th>
						<td>
							
						</td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Previously Published", $plugin->getTextDom()); ?></th>
					  <td>
						<select name="showSelect" id="showSelect" autofocus>
							<?php 
								$episodes = $plugin->getEpisodes(array('show_id'=>$api->getShowId(),'limit' => 5000, 'page'=>1));
								foreach($episodes->_embedded->item as $episode) {
									if($api->getShowId()==$episode->{'show_id'})
										echo  "<option value=\"".$sanitize->itemId($episode->{'id'})."\">".$episode->{'item_title'}."</option>";
								}
							?>
						</select>
						<?php submit_button(__('Edit Episode', $plugin->getTextDom()), 'primary', 'libsyn_edit_post', false, array('id' => 'submit_edit_post', 'onClick' => "document.getElementById('submit_edit_post').value='Edit Episode';")); ?>
					  </td>
					</tr>
					<?php if(is_int($api->getShowId())) { ?>
					<tr valign="top">
						<th></th>
						<td>
							<div class="inside" style="margin: 15px;">Libsyn is connected to your Wordpress account successfully.</div>
						</td>
					</tr>					
					<?php } ?>
					<tr valign="top">
					  <th></th>
					  <td>
						<?php //submit_button(_e('Save Changes', $plugin->getTextDom()), 'primary', 'libsyn_settings_submit', true, array('id' => 'submit_save', 'onClick' => "document.getElementById('submit_save').value='Save Changes';")); ?>
					  </td>
					</tr>
				  </table>
				</div>
			  </div>
			<!-- EOS Existing API -->			
			
			<?php } else { ?>
			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Plugin needs configured", $plugin->getTextDom()); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p style="font-size: 1.8em;"><?php _e("The Libsyn Podcasting Plugin is either not setup or something is wrong with the configuration, please visit the <a href='".admin_url('admin.php?page='.LIBSYN_ADMIN_DIR . 'settings.php')."'>settings page</a>.", $plugin->getTextDom()); ?></p>
				</div>
			  </div>
			<!-- EOS Existing API -->				
			<?php } ?>
			<!-- BOS Libsyn WP Post Page -->
			<div class="stuffbox" id="libsyn-wp-post-page" style="display:none;width:100%;">
				
			</div>
			<!-- EOS Libsyn WP Post Page -->
			</div>
		  </div>
		</div>
	  </form>
	</div>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				//check ajax
				var check_ajax_url = "<?php echo $sanitize->text($plugin->admin_url() . '?action=libsyn_check_url&libsyn_check_url=1'); ?>";
				var ajax_error_message = "<?php __('Something went wrong when trying to load your site\'s base url.
						Please make sure your "Site Address (URL)" in Wordpress settings is correct.', LIBSYN_DIR); ?>";		
				$.getJSON( check_ajax_url).done(function(json) {
					if(json){
						//success do nothing
					} else {
						//redirect to error out
						var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page='.LIBSYN_DIR.'/admin/post.php&error=true&msg='; ?>" + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert("Unknown javascript error 1028.  Please report this error to support@libsyn.com and help us improve this plugin!");
					}
				}).fail(function(jqxhr, textStatus, error) {
						//redirect to error out
						var ajax_error_url = "<?php echo $plugin->admin_url('admin.php').'?page='.LIBSYN_DIR.'/admin/post.php&error=true&msg='; ?>" + ajax_error_message;
						if (typeof window.top.location.href == "string") window.top.location.href = ajax_error_url;
								else if(typeof document.location.href == "string") document.location.href = ajax_error_url;
									else if(typeof window.location.href == "string") window.location.href = ajax_error_url;
										else alert("Unknown javascript error 1029.  Please report this error to support@libsyn.com and help us improve this plugin!");
				});
			});
		})(jQuery);
	</script>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				//create html table
				function createTable(tableData) {
					var table = document.createElement('table');
					table.className = "form-table";
					var tableBody = document.createElement('tbody');
					if(typeof tableData[0].primary_content != 'undefined') delete tableData[0].primary_content;
					if(typeof tableData[0]._links != 'undefined') delete tableData[0]._links;
					if(typeof tableData[0].expiration_date != 'undefined') delete tableData[0].expiration_date;
					$.each(tableData[0], function(key, value) {
						var row = document.createElement('tr');
						var head = document.createElement('th');
						head.appendChild(document.createTextNode(key));
						var cell = document.createElement('td');
						cell.appendChild(document.createTextNode(value));
						row.appendChild(head);
						row.appendChild(cell);
						tableBody.appendChild(row);
					});
					table.appendChild(tableBody);
					$("#libsyn-wp-post-page").empty().append('<h3 class="hndle"><span>Episode Information:  ' + tableData[0].item_title + '</span></h3><div class="inside" id="' + tableData[0].id +'-info-box"></div>').fadeIn('fast');
					$("#" + tableData[0].id + "-info-box").append(table);
				}
				$("#submit_create_post").click(function(event){
					event.preventDefault();
					var new_post_url = "<?php echo $plugin->admin_url('post-new.php'); ?>" + "?isLibsynPost=true";
					if (typeof window.top.location.href == 'string') window.top.location.href = new_post_url;
						else if(typeof document.location.href == 'string') document.location.href = new_post_url;
							else if(typeof window.location.href == 'string') window.location.href = new_post_url;
								else alert('Unknown javascript error 1023.  Please report this error to support@libsyn.com and help us improve this plugin!');
					
				});
				$("#submit_edit_post").click(function(event){
					event.preventDefault();
					var edit_post_url = "<?php echo $plugin->admin_url('post-new.php'); ?>?libsyn_edit_post_id=" + $("#showSelect :selected").val();
					if (typeof window.top.location.href == 'string') window.top.location.href = edit_post_url;
						else if(typeof document.location.href == 'string') document.location.href = edit_post_url;
							else if(typeof window.location.href == 'string') window.location.href = edit_post_url;
								else alert('Unknown javascript error 1024.  Please report this error to support@libsyn.com and help us improve this plugin!');
					
				});
				var libsyn_posts = [<?php echo json_encode($episodes->_embedded->item);?>];
				$("#showSelect").change(function(){
					var item_id = parseInt($(this).val());
					var selectedPostObj = libsyn_posts[0].filter(function( obj ) {
						return obj.id == item_id;
					});
					createTable(selectedPostObj);
				});			
			});
		})(jQuery);
	</script>