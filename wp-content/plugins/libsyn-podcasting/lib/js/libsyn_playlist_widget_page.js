(function ($){
	// $( "#libsyn-playlist-page-dialog" ).dialog({
		// autoOpen: false,
		// draggable: false,
		// height: 'auto',
		// width: 500,
		// modal: true,
		// resizable: false,
		// open: function(){
			// $('.ui-widget-overlay').bind('click',function(){
				// $('#libsyn-playlist-page-dialog').dialog('close');
			// })
		// },
		// buttons: [
			// {
				// id: "dialog-playlist-button-cancel",
				// text: "Cancel",
				// click: function(){
					// $('#libsyn-playlist-page-dialog').dialog('close');
				// },
			// },
			// {
				// id: "dialog-button-insert",
				// text: "Insert",
				// class: "button-primary",
				// click: function(){
					// var dlgPlaylist = $(this);
					// if($("#playlist-media-type-video:checked").val() == "video") {
						// var typeText = "type=\"" + $("#playlist-media-type-video").val() + "\" height=\"" + $("#playlist-video-height").val() + "\" width=\"" + $("#playlist-video-width").val() + "\"";
					// } else {
						// var typeText = "type=\"" + $("#playlist-media-type-audio").val() + "\"";
					// }
					// if($("#other-podcast:checked").val() == "other-podcast") {
						// var selectionData = "[libsyn-playlist podcast=\"" + $("#podcast-url").val() + "\" " + typeText + "]";
					// } else {
						// var selectionData = "[libsyn-playlist podcast=\"" + $("#my-podcast").val() + "\" " + typeText + "]";
					// }
					// if($("#content").is(":visible")) {
						// var bodyData = $("#content").val();
						// var canvas = document.getElementById(wpActiveEditor);
						// canvas.value += selectionData;
						// canvas.focus();
					// } else {
						// var bodyData = tinymce.activeEditor.getContent({format : 'raw'});
						// tinymce.activeEditor.setContent(bodyData + selectionData);
					// }
					// dlgPlaylist.dialog('close');
				// }
			// }
		// ]
	// });
	
	// var playlistButton = $("<button/>",
	// {
		// text: " Add Podcast Playlist",
		// click: function(event) {
			// event.preventDefault();
			// $("#libsyn-playlist-page-dialog").dialog( "open" );
		// },
		// class: "button",
		// "data-editor": "content",
		// "font": "400 18px/1 dashicons"
	// }).prepend("<span class=\"dashicons dashicons-playlist-video wp-media-buttons-icon\"></span>");
	
	// $("#wp-content-media-buttons").append(playlistButton);
	$("#my-podcast").click(function() {
		$("#podcast-url").hide();
	});
	$("#other-podcast").click(function() {
		$("#podcast-url").fadeIn("normal");
	});
	$("#playlist-media-type-audio").click(function() {
		$("#playlist-dimensions-div").hide();
	});
	$("#playlist-media-type-video").click(function() {
		$("#playlist-dimensions-div").fadeIn("normal");
	});
}) (jQuery);