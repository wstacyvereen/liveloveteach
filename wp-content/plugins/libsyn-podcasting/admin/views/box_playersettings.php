<?php
//sets access control headers
// send_origin_headers();
header("Access-Control-Allow-Origin: *");
?>
<h3 id="player_settings_title">
	<label>Player Settings</label>
</h3>
<div class="inside">
	<p id="player-description-text"><em>Below are the default player settings.  You may also modify the size on each individual post on the post page.</em></p>
	<div class="box_clear"></div>
	<table class="form-table">
		<tr valign="top">
			<th>Player Theme</th>
			<td>
				<div>
					<div>
						<input id="player_use_theme_standard" type="radio" value="standard" name="player_use_theme" /><span style="margin-left:16px;"><strong>Standard</strong>&nbsp;&nbsp;<em style="font-weight:300;">(minimum height 45px)</em></span>
					</div>
					<div style="margin-left:36px;" id="player_use_theme_standard_image">
					</div>
					<br />
					<div>
						<input id="player_use_theme_mini" type="radio" value="mini" name="player_use_theme" /><span style="margin-left:16px;"><strong>Mini</strong>&nbsp;&nbsp;<em style="font-weight:300;">(minimum height 26px)</em></span>
					</div>
					<div style="margin-left:36px;" id="player_use_theme_mini_image">
					</div>
					<br />
					<div>
						<input id="player_use_theme_custom" type="radio" value="custom" name="player_use_theme" /><span style="margin-left:16px;"><strong>Custom</strong>&nbsp;&nbsp;<em style="font-weight:300;">(minimum height 90px, width 450px)</em></span>
					</div>
					<div style="margin-left:36px;" id="player_use_theme_custom_image">
					</div>
				</div>
			</td>
		</tr>
		<tr id="player_custom_color_picker" style="display:none;">
			<th>Custom Color</th>
			<td>
				<div>
					<div style="margin-left:36px;">
						<input id="player_custom_color" class="color-picker" name="player_custom_color" value=""/><button type="button" class="button" data-editor="content" font="400 18px/1 dashicons" id="player_custom_color_picker_button"><span class="dashicons dashicons-art" style="padding-top: 4px;"></span> Pick Color</button>
					</div>				
				</div>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr valign="top">
			<th colspan="2"><input style="margin-left: 2px;" id="player_use_thumbnail" type="checkbox" value="use_thumbnail" name="player_use_thumbnail" />&nbsp;Display episode/show artwork on the player?&nbsp;&nbsp;<em style="font-weight:300;">(minimum height 200px)</em></th>
			<td>
			</td>
		</tr>
		<tr valign="top">
			<th>Player Width:</th>
			<td>
				<input id="player_width" type="number" value="" name="player_width" maxlength="4" autocomplete="on" min="200" step="1" />
			</td>
		</tr>
		<tr valign="top">
			<th>Player Height:</th>
			<td>
				<input id="player_height" type="number" value="" name="player_height" autocomplete="on" min="45" step="1" />
			</td>
		</tr>
		<tr valign="top">
			<th>Player Placement</th>
			<td>
				<div>
					<div>
						<input id="player_placement_top" type="radio" value="top" name="player_placement" /><span style="margin-left:16px;"><strong>Top</strong>&nbsp;&nbsp;<em style="font-weight:300;">(Before Post)</em></span>
					</div>
					<div style="margin-left:36px;" class="post-position-image-box">
						<div class="post-position-shape-top"></div>
					</div>
					<br />
					<div>
						<input id="player_placement_bottom" type="radio" value="bottom" name="player_placement" /><span style="margin-left:16px;"><strong>Bottom</strong>&nbsp;&nbsp;<em style="font-weight:300;">(After Post)</em></span>
					</div>
					<div style="margin-left:36px;" class="post-position-image-box">
						<div class="post-position-shape-bottom"></div>
					</div>
				</div>
			</td>
		</tr>
		<tr valign="top">
			<th colspan="2"><input style="margin-left: 2px;" id="player_use_download_link" type="checkbox" value="use_download_link" name="player_use_download_link" />&nbsp;Display download link below the player?</th>
			<td>
			</td>
		</tr>
		<tr valign="top" style="display:none;" id="player_use_download_link_text_div">
			<th></th>
			<td>
				Download Link Text:&nbsp;&nbsp;<input id="player_use_download_link_text" type="text" value="" name="player_use_download_link_text" maxlength="256" min="200"  />
			</td>
		</tr>
		<tr valign="bottom">
			<th></th>
			<td>
				<br />
					<input type="submit" value="Save Player Settings" class="button button-primary" id="player-settings-submit" name="submit">
			</td>
		</tr>
		<tr valign="bottom">
			<th style="font-size:.8em;font-weight:200;">**<em>height and width in Pixels (px)</em></th>
			<td></td>
		</tr>
	</table>
	<br />
</div>