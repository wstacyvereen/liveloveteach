(function ($){	
	$(document).ready(function() {		
		console.log(atts);
		//		$('.loading-libsyn-form').fadeIn('normal');
		$.ajax({
			url: '',
			type: 'POST',
			data: data,
			cache: false,
			dataType: 'json',
			processData: false, // Don't process the files
			contentType: false, // Set content type to false as jQuery will tell the server its a query string request
			success: function(data, textStatus, jqXHR) {
				// if(typeof data.error === 'undefined') {
					$('.loading-libsyn-form').hide();
					$('.libsyn-post-form').fadeIn('normal');
					console.log("success!");
				// } else {
					//Handle errors here
					// console.log('ERRORS: ' + data.error);
				// }
			},
			error: function(jqXHR, textStatus, errorThrown) {
				// Handle errors here
				console.log('ERRORS: ' + textStatus);
				// STOP LOADING SPINNER
				$('#loading-libsyn-form').hide();
			}
		});
		
	});
}) (jQuery);