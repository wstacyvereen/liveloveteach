(function ($){
	$( "#libsyn-player-settings-page-dialog" ).dialog({
		autoOpen: false,
		draggable: true,
		height: 'auto',
		width: 620,
		modal: true,
		resizable: false,
		open: function(){
			$('#player-settings-submit').hide();
			$('#player-description-text').empty().append('<em>Player settings for this post.  **Note these settings will be changed only for this post.  You can modify the default player settings on the Libsyn Podcasting settings page.</em>');
			
			if($('#player-settings-input-div').length > 0) {
				$('#player-settings-input-div').empty();
			} else {
				$(".libsyn-post-form").append('<div id="player-settings-input-div" class="hidden"></div>');
			}
			$('.ui-widget-overlay').bind('click',function(){
				updateFormWithSettings();
				$('#libsyn-player-settings-page-dialog').dialog('close');
			});
			$('#player_settings_title').hide();
		},
		buttons: [
			{
				id: "dialog-player-settings-button-cancel",
				text: "Cancel",
				click: function(){
					updateFormWithSettings();
					$('#libsyn-player-settings-page-dialog').dialog('close');
				},
			},
			{
				id: "dialog-button-insert",
				text: "Use Custom Settings",
				class: "button-primary",
				click: function(){
					var dlgPlayerSettings = $(this);
					updateFormWithSettings();
					dlgPlayerSettings.dialog('close');
				}
			}
		]
	});
	
	var updateFormWithSettings = function() {
		//player_use_thumbnail
		if($('#player_use_thumbnail').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_thumbnail" value="use_thumbnail">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_thumbnail" value="">');
		}
		
		//player_use_theme
		if($('#player_use_theme_standard').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="standard" type="hidden">');
		} else if($('#player_use_theme_mini').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="mini" type="hidden">');
		} else if($('#player_use_theme_custom').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="custom" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
		
		//player_width
		var playerSettingsWidth = $('#player_width').val();
		$("#player-settings-input-div").append('<input name="player_width" value="' + playerSettingsWidth + '" type="hidden">');
		
		
		//player_height
		var playerSettingsHeight = $('#player_height').val();
		$("#player-settings-input-div").append('<input name="player_height" value="' + playerSettingsHeight + '" type="hidden">');
		
		//player_placement
		if($('#player_placement_top').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_placement" value="top" type="hidden">');
		} else if($('#player_placement_bottom').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_placement" value="bottom" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
		
		//player_use_download_link
		if($('#player_use_download_link').is(':checked')) {
			$("#player-settings-input-div").append('<input name="player_use_download_link" value="use_download_link">');
			var playerUseDownloadLinkText = $('#player_use_download_link_text').val();
			$("#player-settings-input-div").append('<input name="player_use_download_link_text" value="' + playerUseDownloadLinkText + '" type="hidden">');
		$("#player-settings-input-div").append('<input name="player_width" value="' + playerSettingsWidth + '" type="hidden">');
		} else {
			$("#player-settings-input-div").append('<input name="player_use_download_link" value="">');
			$("#player-settings-input-div").append('<input name="player_use_download_link_text" value="" type="hidden">');
		}
		
		//player_custom_color
		var playerSettingsCustomColor = $('#player_custom_color').val();
		$("#player-settings-input-div").append('<input name="player_custom_color" value="' + playerSettingsCustomColor + '" type="hidden">');			
	};
	
	var playerSettingsButton = $("<button/>",
	{
		text: " Libsyn Player Settings",
		click: function(event) {
			event.preventDefault();
			$("#libsyn-player-settings-page-dialog").dialog( "open" );
		},
		class: "button",
		"data-editor": "content",
		"font": "400 18px/1 dashicons"
	}).prepend("<span class=\"dashicons dashicons-format-video wp-media-buttons-icon\"></span>");

	$("#wp-content-media-buttons").append(playerSettingsButton);
	
}) (jQuery);