(function($) {
	/* Set  vars */
	var getLibsynPostId = function getLibsynPostId() {
		return $('#libsyn-advanced-destination-form-button').attr('libsyn_wp_post_id');
	};

	var isLibsynDestinationsVisible = function isLibsynDestinationsVisible() {
		return ($('#libsyn-advanced-destination-form-container').is(":visible") || ($('#libsyn-advanced-destination-form-button').attr('value') == "true"));
	}
	
	/* Form Handling */
	$(document).ready(function() {
		/* Set the show/hide Destinations form*/
		$('#libsyn-advanced-destination-form-button').click(function(e) {
			e.preventDefault();
			$.when($('#libsyn-advanced-destination-form-container').fadeToggle('normal')).done(function(){
				if($('#libsyn-advanced-destination-form-button').attr('value') == "false") {
					$('#libsyn-advanced-destination-form-button').attr('value', "true");
				} else {
					$('#libsyn-advanced-destination-form-button').attr('value', "false");
				}
				$('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('true');
				setToggles(); //set toggles once the div is loaded
			});
		});

		/* Set initial hidden state */
		if (isLibsynDestinationsVisible()) $('#libsyn-advanced-destination-form-button').trigger('click');
		// if (isLibsynDestinationsVisible()) $('#libsyn-advanced-destination-form-button').fadeIn('normal');
		
		//set the select alls
		$('#cb-select-all-1, #cb-select-all-2').click(function() {
			var checkedStatus = Math.random() >= 0.5; //TODO: fix WP checked status always returning true?
			$('.libsyn_destinations tbody#the-list').find("input[type=checkbox]").each(function() {
				$(this).prop('checked', checkedStatus);
			});
		});
		

		/* Handle all the WP stuff */
		libsynList = {
			/**
			 * Register our triggers
			 * 
			 * We want to capture clicks on specific links, but also value change in
			 * the pagination input field. The links contain all the information we
			 * need concerning the wanted page number or ordering, so we'll just
			 * parse the URL to extract these variables.
			 * 
			 */
			init: function() {
				// This will have its utility when dealing with the page number input
				var timer;
				var delay = 500;
				// Pagination links, sortable link
				$('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
					e.preventDefault();
					var query = this.search.substring( 1 );
					var data = {
						paged: libsynList.__query( query, 'paged' ) || '1',
						order: libsynList.__query( query, 'order' ) || 'asc',
						orderby: libsynList.__query( query, 'orderby' ) || 'id'
					};
					libsynList.update( data );
				});

				// Page number input
				$('input[name=paged]').on('keyup', function(e) {
					if ( 13 == e.which )
						e.preventDefault();
					var data = {
						paged: parseInt( $('input[name=paged]').val() ) || '1',
						order: $('input[name=order]').val() || 'asc',
						orderby: $('input[name=orderby]').val() || 'id'
					};
					window.clearTimeout( timer );
					timer = window.setTimeout(function() {
						libsynList.update( data );
					}, delay);
				});
				
				if($('#libsyn-post-episode-advanced-destination-form-data').html().length !== 0 && $('#libsyn-post-episode-advanced-destination-form-data').html() !=='[]') {
					var libsynStoredForm = $('#libsyn-post-episode-advanced-destination-form-data').html();
					$('#libsyn-post-episode-advanced-destination-form-data').empty();
					$('#libsyn-post-episode-advanced-destination-form-data-input').val('').val(libsynStoredForm);
						if (isLibsynDestinationsVisible()) $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('true');
							else $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('false');
				} else {
					var libsynStoredForm = sessionStorage.getItem("libsynFormData" + getLibsynPostId());
					if(libsynStoredForm == null){
						//not set in sessionStorage, try to get input val
						libsynStoredForm = $('#libsyn-post-episode-advanced-destination-form-data-input').val();
					}
					if(typeof libsynStoredForm !== 'undefined' && libsynStoredForm !== 'null'){
						$('#libsyn-post-episode-advanced-destination-form-data-input').val('').val(libsynStoredForm);
						if (isLibsynDestinationsVisible()) $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('true');
							else $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('false');
					}
				}
				if((libsynStoredForm != 'null') && (typeof libsynStoredform != 'undefined')) {
					sessionStorage.setItem("libsynFormData" + getLibsynPostId(), libsynStoredForm);
				} else { //does not have saved data from anywhere so set default row checked state to checked
					$('.libsyn_destinations tbody#the-list').find("input[type=checkbox]").each(function() {
						$that = $(this);
						this.checked = $that.is(':checked');
						// this.checked = true; //set all to checked
					});
					// $('#cb-select-all-1').attr('checked', true);
					// $('#cb-select-all-2').attr('checked', true);
				}

				setToggles();
				if(libsynStoredForm != '') libsynAdvancedDestinationStringToForm(libsynStoredForm, $('.libsyn_destinations tbody#the-list'));
				setToggles(); //have to run again because of ajax and objects not created yet.
			},
			/** AJAX call
			 * 
			 * Send the call and replace table parts with updated version!
			 * 
			 * @param    object    data The data to pass through AJAX
			 */
			update: function( data ) {
				
				$.ajax({
					// /wp-admin/admin-ajax.php
					url: ajaxurl,
					// Add action and nonce to our collected data
					data: $.extend(
						{
							_ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
							action: '_ajax_fetch_custom_list',
						},
						data
					),
					// Handle the successful result
					success: function( response ) {
						// WP_List_Table::ajax_response() returns json
						var response = $.parseJSON( response );
						// Add the requested rows
						if ( response.rows.length )
							$('.libsyn_destinations tbody#the-list').html( response.rows );
						// Update column headers for sorting
						if ( response.column_headers.length )
							$('.libsyn-post-form thead tr, tfoot tr').html( response.column_headers );
						// Update pagination for navigation
						if ( response.pagination.bottom.length )
							$('.libsyn-post-form .tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
						if ( response.pagination.top.length )
							$('.libsyn-post-form .tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );
						libsynList.init();
					}
				});
			},
			/**
			 * Filter the URL Query to extract variables
			 * 
			 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
			 * 
			 * @param    string    query The URL query part containing the variables
			 * @param    string    variable Name of the variable we want to get
			 * 
			 * @return   string|boolean The variable value if available, false else.
			 */
			__query: function( query, variable ) {
				var vars = query.split("&");
				for ( var i = 0; i <vars.length; i++ ) {
					var pair = vars[ i ].split("=");
					if ( pair[0] == variable )
						return pair[1];
				}
				return false;
			},
		}
		// Show time!
		libsynList.init();
	});
	
	/* Set open/closed states for radio buttons */
	var setToggles = function(){
		// var libsyn_destination_ids = [];
		var times = {
			'12:00 AM': '12:00 AM',
			'12:30 AM': '12:30 AM',
			'01:00 AM': '01:00 AM',
			'01:30 AM': '01:30 AM',
			'02:00 AM': '02:00 AM',
			'02:30 AM': '02:30 AM',
			'03:00 AM': '03:00 AM',
			'03:30 AM': '03:30 AM',
			'04:00 AM': '04:00 AM',
			'04:30 AM': '04:30 AM',
			'05:00 AM': '05:00 AM',
			'05:30 AM': '05:30 AM',
			'06:00 AM': '06:00 AM',
			'06:30 AM': '06:30 AM',
			'07:00 AM': '07:00 AM',
			'07:30 AM': '07:30 AM',
			'08:30 AM': '08:00 AM',
			'09:00 AM': '09:00 AM',
			'09:30 AM': '09:30 AM',
			'10:00 AM': '10:00 AM',
			'10:30 AM': '10:30 AM',
			'11:00 AM': '11:00 AM',
			'11:30 AM': '11:30 AM',
			'12:00 PM': '12:00 PM',
			'12:30 PM': '12:30 PM',
			'01:00 PM': '01:00 PM',
			'01:30 PM': '01:30 PM',
			'02:00 PM': '02:00 PM',
			'02:30 PM': '02:30 PM',
			'03:00 PM': '03:00 PM',
			'03:30 PM': '03:30 PM',
			'04:00 PM': '04:00 PM',
			'04:30 PM': '04:30 PM',
			'05:00 PM': '05:00 PM',
			'05:30 PM': '05:30 PM',
			'06:00 PM': '06:00 PM',
			'06:30 PM': '06:30 PM',
			'07:00 PM': '07:00 PM',
			'07:30 PM': '07:30 PM',
			'08:30 PM': '08:00 PM',
			'09:00 PM': '09:00 PM',
			'09:30 PM': '09:30 PM',
			'10:00 PM': '10:00 PM',
			'10:30 PM': '10:30 PM',
			'11:00 PM': '11:00 PM',
			'11:30 PM': '11:30 PM',
		};
		
		/* Loop through each table element */
		$('.libsyn_destinations tbody#the-list').children('tr').each(function() {
			var record = $(this).attr('id');
			if(record.length){
				var destination_id = parseInt(record.replace('record_', ''));
				// libsyn_destination_ids.push(destination_id);
				var destination_working_tr = $(this);
				
				/* Release Date */
				//set default radio button state
				if($('#set_release_scheduler_advanced_release_lc__' + destination_id + '-2').is(':checked')) {
					$('#form-field-wrapper_release_scheduler_advanced_release_lc__' + destination_id).fadeIn('normal');					
					if(destination_working_tr.height() != 0 && destination_working_tr.height() <= 155) {
						destination_working_tr.animate({height: (destination_working_tr.height() + 100) + 'px'}, 'fast');
					}
				}
				$('#set_release_scheduler_advanced_release_lc__' + destination_id + '-0').click(function() {
					$('#form-field-wrapper_release_scheduler_advanced_release_lc__' + destination_id).fadeOut('fast');
					if(destination_working_tr.height() != 0 && destination_working_tr.height() >= 155 && $('#set_expiration_scheduler_advanced_release_lc__' + destination_id + '-2').is(':checked') == false) {
						destination_working_tr.animate({height: (destination_working_tr.height() - 100) + 'px'}, 'fast');
					}
				});	
				$('#set_release_scheduler_advanced_release_lc__' + destination_id + '-2').click(function() {
					$('#form-field-wrapper_release_scheduler_advanced_release_lc__' + destination_id).fadeIn('normal');
					if(destination_working_tr.height() != 0 && destination_working_tr.height() <= 155) {
						destination_working_tr.animate({height: (destination_working_tr.height() + 100) + 'px'}, 'fast');
					}
				});
				
				//set datepicker & timepicker
				$('#release_scheduler_advanced_release_lc__' + destination_id + '_date').datepicker({
					dateFormat: "yy-mm-dd"
					, changeMonth: true
					, changeYear: true
					, showOn: "button"
					, minDate: 0
					,onSelect: function(dateText, inst) {
						/* Change Expiration Start Date */
						var workingReleaseDate = function(workingReleaseDateString){
							workingReleaseDateLocal = new Date(workingReleaseDateString);
							return  new Date(workingReleaseDateLocal.valueOf() + workingReleaseDateLocal.getTimezoneOffset() * 60000);
						}
						$('#expiration_scheduler_advanced_release_lc__' + destination_id + '_date').datepicker( "option", "minDate", workingReleaseDate(dateText)).datepicker('refresh').next('button').button({text: false, icons:{primary : 'ui-icon-calendar'}});
					}
				}).next('button').button({text: false, icons:{primary : 'ui-icon-calendar'}});
				$('select#release_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').css({
					'margin': '4px -1px',
					'border': '1px solid #ccc',
					'background': 'transparent',
					'font-size': '1.1em',
					'height': '34px',
					'width': '108px',
					'-webkit-appearance': 'none',
					'-mox-appearance': 'none',
					'appearance': 'none',
					'background': 'url(\'data:image/svg+xml;utf8,<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20"><path d="M15 8l-4.030 6-3.97-6h8z"></path></svg>\') 96% / 15% no-repeat #eee'
				});

				//build & handle the time select options
				$('select#release_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').empty();
				$.each(times, function(key, val){
					if(val == $('#libsyn-post-episode-advanced-destination-' + destination_id + '-release-time').val()){
						$('select#release_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').append('<option value="' + val + '" selected>' + val + '</option>');
					} else {
						$('select#release_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').append('<option value="' + val + '">' + val + '</option>');
					}
				});
				$('select#release_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').change(function() {
						$('#libsyn-post-episode-advanced-destination-' + destination_id + '-release-time').val($('#release_scheduler_advanced_release_lc__' + destination_id +'_time_select_select-element').val());
				});

				//set default radio button state
				if($('#set_expiration_scheduler_advanced_release_lc__' + destination_id + '-2').is(':checked')) {
					$('#form-field-wrapper_expiration_scheduler_advanced_release_lc__' + destination_id).fadeIn('normal');
					if(destination_working_tr.height() != 0 && destination_working_tr.height() <= 155) {
						destination_working_tr.animate({height: (destination_working_tr.height() + 100) + 'px'}, 'fast');
					}
				}
				$('#set_expiration_scheduler_advanced_release_lc__' + destination_id + '-0').click(function() {
					$('#form-field-wrapper_expiration_scheduler_advanced_release_lc__' + destination_id).fadeOut('fast');
					if(destination_working_tr.height() != 0 && destination_working_tr.height() >= 155 && $('#set_release_scheduler_advanced_release_lc__' + destination_id + '-2').is(':checked') == false)  {
						destination_working_tr.animate({height: (destination_working_tr.height() - 100) + 'px'}, 'fast');
					}
				});
				$('#set_expiration_scheduler_advanced_release_lc__' + destination_id + '-2').click(function() {
					$('#form-field-wrapper_expiration_scheduler_advanced_release_lc__' + destination_id).fadeIn('normal');
					if(destination_working_tr.height() != 0 && destination_working_tr.height() <= 155) {
						destination_working_tr.animate({height: (destination_working_tr.height() + 100) + 'px'}, 'fast');
					}
				});
				
				//set datepicker & timepicker
				$('#expiration_scheduler_advanced_release_lc__' + destination_id + '_date').datepicker({
					dateFormat: "yy-mm-dd"
					, changeMonth: true
					, changeYear: true
					, showOn: "button"
					, minDate: 1
				}).next('button').button({text: false, icons:{primary : 'ui-icon-calendar'}});
				$('#expiration_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').css({
					'margin': '4px -1px',
					'border': '1px solid #ccc',
					'background': 'transparent',
					'font-size': '1.1em',
					'height': '34px',
					'width': '108px',
					'-webkit-appearance': 'none',
					'-mox-appearance': 'none',
					'appearance': 'none',
					'background': 'url(\'data:image/svg+xml;utf8,<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20"><path d="M15 8l-4.030 6-3.97-6h8z"></path></svg>\') 96% / 15% no-repeat #eee'
				});
				
				//build & handle the time select options
				$('select#expiration_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').empty();
				$.each(times, function(key, val){
					if(val === $('#libsyn-post-episode-advanced-destination-' + destination_id + '-expiration-time').val()){
						$('select#expiration_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').append('<option value="' + val + '" selected>' + val + '</option>');
					} else {
						$('select#expiration_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').append('<option value="' + val + '">' + val + '</option>');
					}
				});
				$('select#expiration_scheduler_advanced_release_lc__' + destination_id + '_time_select_select-element').change(function() {
						$('#libsyn-post-episode-advanced-destination-' + destination_id + '-expiration-time').val($('#expiration_scheduler_advanced_release_lc__' + destination_id +'_time_select_select-element').val());
				});
				
				//set the checkbox id
				$('input[type=checkbox][value=' + destination_id + ']').attr('id', 'libsyn-advanced-destination-checkbox-' + destination_id);

				//run through setting form data
				$(this).find("input, select, textarea").each(function() {
					$(this).on('blur', function() {
						/* Store Form Data */
						sessionStorage.setItem("libsynFormData" + getLibsynPostId(), libsynAdvancedDestinationFormtoString($('.libsyn_destinations tbody#the-list')));
						var libsynStoredForm = sessionStorage.getItem("libsynFormData" + getLibsynPostId());
					});
					$(this).on('change', function() {
						/* Store Form Data */
						sessionStorage.setItem("libsynFormData" + getLibsynPostId(), libsynAdvancedDestinationFormtoString($('.libsyn_destinations tbody#the-list')));
						var libsynStoredForm = sessionStorage.getItem("libsynFormData" + getLibsynPostId());
					});
				});
			}
		});
	};
	
	/* Save Handler for ajax destination pages */
	function libsynAdvancedDestinationFormtoString(libsynForm) {
		libsynFormObject = new Object
		libsynForm.find("input, select, textarea").each(function() {
			if (this.id) {
				elem = $(this);
				if (elem.attr("type") == 'checkbox') {
					libsynFormObject[this.id] = elem.attr("checked");
					if(elem.prop("checked")) {
						libsynFormObject[this.id] = 'checked';
					} else {
						libsynFormObject[this.id] = '';
					}
				} else if(elem.attr("type") == 'radio') {
					if(elem.prop("checked")) {
						libsynFormObject[this.id] = 'checked';
					} else {
						libsynFormObject[this.id] = '';
					}
				} else {
					libsynFormObject[this.id] = elem.val();
				}
			}
		});
		
		//check new obj against currently saved obj
		var libsynStoredForm = sessionStorage.getItem("libsynFormData" + getLibsynPostId());
		if(typeof libsynStoredForm !== 'undefined'){
			libsynFormObject = jQuery.extend(JSON.parse(libsynStoredForm), libsynFormObject);
			formString = JSON.stringify(libsynFormObject);
		}
		//set form string to the form input
		$('#libsyn-post-episode-advanced-destination-form-data-input').val('').val(formString);
		if (isLibsynDestinationsVisible()) $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('true');
			else $('#libsyn-post-episode-advanced-destination-form-data-input-enabled').val('').val('false');
		
		//return data
		return formString;
	}
	
	/* Handle setting values of the form */
	function libsynAdvancedDestinationStringToForm(libsynFormString, libsynUnfilledForm) {
		libsynFormObject = JSON.parse(libsynFormString);
		libsynUnfilledForm.find("input, textarea").each(function() {
			if (this.id) {
				id = this.id;
				elem = $(this);
				if (elem.attr("type") == "checkbox") {
					if(libsynFormObject !== null && typeof libsynFormObject[id] !== 'undefined') {
						if(libsynFormObject[id] == 'checked') elem.attr("checked", libsynFormObject[id]);
					}
				} else if(elem.attr("type") == "radio") {
					if(libsynFormObject !== null) {
						if(libsynFormObject[id] == 'checked') {
							if(typeof libsynFormObject[id] !== 'undefined') libsynFormObject[id] = elem.prop("checked", true);
						} else {
							if(typeof libsynFormObject[id] !== 'undefined') libsynFormObject[id] = elem.prop("checked", false);
						}
					}
				} else {
					if(libsynFormObject !== null && typeof libsynFormObject[id] !== 'undefined') elem.attr("value", libsynFormObject[id]);
				}
			}
		});
	}
})(jQuery);