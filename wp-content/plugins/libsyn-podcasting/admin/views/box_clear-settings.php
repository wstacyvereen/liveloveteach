<?php
//sets access control headers
// send_origin_headers();
header("Access-Control-Allow-Origin: *");
?>
<h3>
	<label>Additional Settings</label>
</h3>
<div class="inside">	
	<div class="box_clear"></div>
	<div style="height: 124px;" id="libsyn-download-log-containter">
		<div style="float: left;margin-right: 36px;width:25%;"><strong>Download Plugin Log</strong></div>
		<div style="float: left; width: 60%;">
			<div>
				If you are having trouble with the plugin please download the log file to submit to our developers.
				<br />
				<input type="button" id="libsyn-download-log-button" value="Download Log" class="button button-primary"></input>
			</div>
		</div>
	</div>
	<div class="box_clear"></div>
	<div style="height: 124px;">
		<div style="float: left;margin-right: 36px;width:25%;"><strong>Reset Settings</strong></div>
		<div style="float: left; width: 60%;">
			<div>
				This will clear all the current plugin settings.
				<br />
				<input type="button" id="clear-settings-button" value="Clear Settings" class="button button-primary"></input>
			</div>
		</div>
	</div>
</div>