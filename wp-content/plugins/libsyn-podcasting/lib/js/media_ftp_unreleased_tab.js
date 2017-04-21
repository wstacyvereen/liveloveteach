(function ($) {
	$(document).ready(function () {
		var data = '<?php _e($object->ID) ?>';
		$('.loading-libsyn-form').fadeIn('normal');
		$('.libsyn-post-form').hide();
		$.ajax({
			url : '<?php _e(plugins_url(' includes / load_ftp_unreleased.php ', __FILE__)) ?>',
			type : 'POST',
			data : data,
			cache : false,
			dataType : 'json',
			processData : false,
			contentType : false,
			success : function (data, textStatus, jqXHR) {
				if (typeof data.error === 'undefined') {
					$('.loading-libsyn-form').hide();
					$('.libsyn-post-form').fadeIn('normal');
					console.log('Success!');
				} else {
					console.log('ERRORS: ' + data.error);
				}
			},
			error : function (jqXHR, textStatus, errorThrown) {
				console.log('ERRORS: ' + textStatus);
				$('#loading-libsyn-form').hide();
			}
		});
	});
})(jQuery);
