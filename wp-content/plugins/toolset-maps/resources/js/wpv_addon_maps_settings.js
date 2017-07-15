var WPViews = WPViews || {};
var WPV_Toolset = WPV_Toolset  || {};

WPViews.ViewAddonMapsSettings = function( $ ) {
	
	var self = this;
	self.marker_add_button = $( '#js-wpv-addon-maps-marker-add' );
	self.settings = {
		'api_key': $( '#js-wpv-map-api-key' ).val(),
	};
	self.legacy_mode_api_key = ( $( '.js-wpv-map-api-key-save' ).length > 0 );
	
	if ( typeof WPV_Toolset.only_img_src_allowed_here !== "undefined" ) {
		WPV_Toolset.only_img_src_allowed_here.push( "wpv-addpn-maps-custom-marker-newurl" );
	}
	
	$( '#wpv-addpn-maps-custom-marker-newurl' ).on( 'js_icl_media_manager_inserted', function( event ) {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		thiz_list = thiz_container.find( '.js-wpv-add-item-settings-list' ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">'),
		data = {
			action:		'wpv_addon_maps_update_marker',
			csaction:	'add',
			cstarget:	thiz.val(),
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};
		
		if ( $( '.js-wpv-addon-maps-custom-marker-list img[src="' + thiz.val() + '"]' ).length > 0 ) {
			thiz.val('');
			return;
		}
		
		self.marker_add_button.prop( 'disabled', true );
		spinnerContainer.insertAfter( self.marker_add_button ).show();
		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz_list.append('<li class="js-wpv-addon-maps-custom-marker-item"><img src="' + thiz.val() + '" class="js-wpv-addon-maps-custom-marker-item-img" /> <i class="icon-remove-sign fa fa-times-circle js-wpv-addon-maps-custom-marker-delete"></i></li>');
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				}
			},
			error:		function( ajaxContext ) {
				
			},
			complete:	function() {
				thiz.val('');
				spinnerContainer.remove();
				self.marker_add_button.prop( 'disabled', false );
			}
		});
		
	});
	
	$( document ).on( 'click', '.js-wpv-addon-maps-custom-marker-delete', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		thiz_item = thiz.closest( '.js-wpv-addon-maps-custom-marker-item' ),
		thiz_image = thiz_item.find( 'img' ).attr( 'src' ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertAfter( self.marker_add_button ).show(),
		data = {
			action:		'wpv_addon_maps_update_marker',
			csaction:	'delete',
			cstarget:	thiz_image,
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};
		
		self.marker_add_button.prop( 'disabled', true );

		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz_item
						.addClass( 'remove' )
						.fadeOut( 'fast', function() {
							$( this ).remove(); 
						});
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				}
			},
			error:		function( ajaxContext ) {
				
			},
			complete:	function() {
				spinnerContainer.remove();
				self.marker_add_button.prop( 'disabled', false );
			}
		});
		
	});
	
	$( document ).on( 'change keyup input cut paste', '#js-wpv-map-api-key', function() {
		var thiz = $( this );
		self.maybe_glow_api_key( thiz );
		if ( thiz.val() != self.settings.api_key ) {
			if ( self.legacy_mode_api_key ) {
				// Legacy: we add a button on Views < 2.0
				$( '.js-wpv-map-api-key-save' )
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );
			} else {
				self.api_key_debounce_update();
			}
		} else if ( self.legacy_mode_api_key ) {
			$( '.js-wpv-map-api-key-save' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		}
	});
	
	$( document ).on( 'click', '.js-wpv-map-api-key-save', function() {
		var thiz = $( this );
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		self.save_api_key_options();
	});
	
	$( document ).on( 'js-toolset-event-update-setting-section', function( event, data ) {
		if ( self.legacy_mode_api_key ) {
			$( '.js-wpv-map-api-key-save' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
			$( '.js-wpv-map-api-key-save' )
				.closest( '.update-button-wrap' )
					.find( '.wpv-spinner' )
						.remove();
			$( '.js-wpv-map-api-key-save' )
				.closest( '.update-button-wrap' )
					.find( '.js-wpv-messages' )
						.wpvToolsetMessage({
								text: wpv_addon_maps_settings_local.setting_saved,
								type: 'success',
								inline: true,
								stay: false
							});
		}
	});
	
	self.save_api_key_options = function() {
		var api_key = $( '#js-wpv-map-api-key' ).val(),
		container = $( '.js-toolset-maps-api-key' ),
		data_for_events = {},
		data = {
			action:		'wpv_addon_maps_update_api_key',
			api_key:	api_key,
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.settings.api_key = api_key;
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			},
			error:		function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			},
			complete:	function() {
				if ( self.legacy_mode_api_key ) {
					$( '.js-wpv-map-api-key-save' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					$( '.js-wpv-map-api-key-save' )
						.closest( '.update-button-wrap' )
							.find( '.wpv-spinner' )
								.remove();
				}
			}
		});
	};
	
	self.api_key_debounce_update = _.debounce( self.save_api_key_options, 1000 );
	
	self.maybe_glow_api_key = function( api_key_field ) {
		if ( api_key_field.val() == '' ) {
			api_key_field.css( {'box-shadow': '0 0 5px 1px #f6921e'} );
		} else {
			api_key_field.css( {'box-shadow': 'none'} );
		}
		return self;
	};
	
	$( document ).on( 'click', '#js-wpv-map-load-stored-data', function() {
		var thiz = $( this ),
		thiz_before = thiz.closest( '.js-wpv-map-load-stored-data-before' ),
		thiz_after = $( '.js-wpv-map-load-stored-data-after' ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		data = {
			action:		'wpv_addon_maps_get_stored_data',
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};
		
		thiz.prop( 'disabled', true );
		
		$.ajax({
			type:		"GET",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					thiz_after
						.html( response.data.table )
						.fadeIn();
				}
			},
			error:		function( ajaxContext ) {
				
			},
			complete:	function() {
				spinnerContainer.remove();
				thiz.prop( 'disabled', false );
				thiz_before.remove();
			}
		});
	});
	
	self.delete_stored_addresses = function( keys ) {
		data = {
			action:		'wpv_addon_maps_delete_stored_addresses',
			keys:		keys,
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};		
		return $.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data
		});
	};
	
	$( document ).on( 'click', '.js-wpv-map-delete-stored-address', function() {
		var thiz = $( this ),
		thiz_key = thiz.data( 'key' ),
		thiz_row = thiz.closest( 'tr' ),
		data = {
			action:		'wpv_addon_maps_delete_stored_addresses',
			keys:		[ thiz_key ],
			wpnonce:	wpv_addon_maps_settings_local.global_nonce
		};
		
		thiz.toggleClass( 'fa-times fa-circle-o-notch fa-spin wpv-map-delete-stored-address-deleting' );
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
		self.delete_stored_addresses( [ thiz_key ] )
			.done( function( response ) {
				if ( response.success ) {
					thiz_row
						.addClass( 'deleted' )
						.fadeOut( 'fast', function() {
							thiz_row.remove();
						});
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
	});
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	self.init = function() {
		self.maybe_glow_api_key( $( '#js-wpv-map-api-key' ) );
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.view_addon_maps_settings = new WPViews.ViewAddonMapsSettings( $ );
});