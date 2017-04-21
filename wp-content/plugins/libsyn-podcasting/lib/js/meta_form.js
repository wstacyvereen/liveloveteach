(function ($){	
	$(document).ready(function() {		

		if($("#libsyn-post-episode").is(':checked')) { handleRequired(); }

		$("#libsyn-post-episode").click(function(){ 
			$("#libsyn-new-media-media").attr("required", true);
			handleRequired(); 
		});
		
		function handleRequired() {
			if ($("#libsyn-post-episode").is(':checked')){
				$("#libsyn-new-media-media").prop("required", true);
				$("#libsyn-post-episode-category").prop("required", true);
				$("#title").prop("required", true);
			} else {
				$("#libsyn-new-media-media").prop("required", false);
				$("#libsyn-post-episode-category").prop("required", false);
				$("#title").prop("required", false);
			}
		}
		
	});
}) (jQuery);