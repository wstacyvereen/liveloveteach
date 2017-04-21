<?php
//sets access control headers
// send_origin_headers();
header("Access-Control-Allow-Origin: *");
?>
<h3>
	<label>Support</label>
</h3>
<div class="inside">
	<div class="box_clear"></div>
	<div style="height:24px;">
		<div style="float: left;margin-right: 36px;width:25%;"><strong>Rate 5-star</strong></div>
		<div style="float: left; width: 60%;">
			<a target="_blank" href="//wordpress.org/support/view/plugin-reviews/libsyn-podcasting" style="text-decoration: none">
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			<span class="dashicons dashicons-star-filled"></span>
			</a>
		</div>
	</div>
	<div class="box_clear"></div>
	<div style="height: 24px;">
		<div style="float: left;margin-right: 36px;width:25%;"><strong>Facebook Page</strong></div>
		<div style="float: left; width: 60%;">
			<div id="fb-root"></div>
			<script>
				(function(d, s, id) {
				  var js, fjs = d.getElementsByTagName(s)[0];
				  if (d.getElementById(id)) return;
				  js = d.createElement(s); js.id = id;
				  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.4";
				  fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));
			</script>
			<div class="fb-like" data-href="https://www.facebook.com/libsyn" data-layout="button" data-action="like" data-show-faces="false" data-share="true"></div>
		</div>
	</div>
	<div class="box_clear"></div>
	<div style="height: 24px;">
		<div style="float: left;margin-right: 36px;width:25%;"><strong>Follow on Twitter</strong></div>
		<div style="float: left; width: 60%;">
			<a href="https://twitter.com/libsyn" class="twitter-follow-button" data-show-count="false">Follow @libsyn</a>
			<script>
				!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
			</script>
		</div>
	</div>
	<div class="box_clear"></div>
</div>
<br>
