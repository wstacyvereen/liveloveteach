/**
* wpv_addon_maps_editor.js
*
* Contains helper functions for the dialogs GUI used for Types field
*
* @since 1.0.0
* @package Views Addon Maps
*/

var WPViews = WPViews || {};

WPViews.AddonMapsEditor = function( $ ) {

    var self = this;

    //Last created random ID
    self.previous_random_id = null;
	// Google Maps geocoder, for getting addresses given locations
	self.geocoder = new google.maps.Geocoder();
	// Selector that holds address fields
	self.selector = '.js-toolset-google-map, [data-types-field-type="google_address"]';
	// Latitude and longitude validation regex
	self.validate_lat = /^(-?([0-9]|8[0-4]|[1-7][0-9])(\.{1}\d{1,20})?)$/;
	self.validate_lon = /^-?([0-9]|[1-9][0-9]|[1][0-7][0-9]|180)(\.{1}\d{1,20})?$/;
	// Extra inputs structure for latitude and longitude
	self.inputs_structure = '<a class="toolset-google-map-toggle-latlon js-toolset-google-map-toggle-latlon">' + toolset_google_address_i10n.showhidecoords + '</a>';
	self.inputs_structure += '<div class="js-toolset-google-map-toggling-latlon toolset-google-map-toggling-latlon" style="display:none"><p><label for="toolset-google-map-lat" class="toolset-google-map-label js-wpt-auxiliar-label">' + toolset_google_address_i10n.latitude + '</label><input id="toolset-google-map-lat" class="js-toolset-google-map-latlon js-toolset-google-map-lat toolset-google-map-lat" type="text" value="" /></p>';
	self.inputs_structure += '<p><label for="toolset-google-map-lon" class="toolset-google-map-label js-wpt-auxiliar-label">' + toolset_google_address_i10n.longitude + '</label><input id="toolset-google-map-lon" class="js-toolset-google-map-latlon js-toolset-google-map-lon toolset-google-map-lon" type="text" value="" /></p></div>';
	// Extra structure for preview and closest address
	self.preview_structure = '<div class="toolset-google-map-preview js-toolset-google-map-preview"></div>';
	self.preview_structure += '<div style="display:none;" class="toolset-google-map-preview-closest-address js-toolset-google-map-preview-closest-address"><div style="padding:5px 10px 10px;">' + toolset_google_address_i10n.closestaddress + '<span class="toolset-google-map-preview-closest-address-value js-toolset-google-map-preview-closest-address-value"></span><br /><button class="button buton-secondary button-small js-toolset-google-map-preview-closest-address-apply">' + toolset_google_address_i10n.usethisaddress + '</button></div></div>';
	
	self.is_google_autocomplete_service_tested	= false;
	self.is_google_autocomplete_service_working	= false;
	
	/**
	* ###########################################
	* Helper methods
	* ###########################################
	*/
	
	/**
	* glow_selectors
	*
	* Glow a given selector for a given reason
	*
	* @param selectors	object		The selectors thay will be glown
	* @param reason		string		The reason for the glow, as a classname
	*
	* @since 1.1.0
	*/
	
	self.glow_selectors = function( selectors, reason ) {
		$( selectors ).addClass( reason );
		setTimeout( function () {
			$( selectors ).removeClass( reason );
		}, 500 );
	};

	/**
	* create_random_id
	*
	* Creates a randomized ID to be used with repetitive fields.
	*
	* @param prefix	            string		The original id that will be placed before the randomized stirng.
	* @param use_previous		bool		Use true to use the same previous generated id.
	*
	* @since 1.1.1
	*/

	self.create_random_id = function( prefix, use_previous ) {
		if ( ! use_previous ) {
			function s4() {
			    return Math.floor( ( 1 + Math.random() ) * 0x10000 )
			      .toString( 16 )
			      .substring( 1 );
			}
			self.previous_random_id = prefix + s4() + s4() + '' + s4();			
			return self.previous_random_id;
		} else {
			if ( self.previous_random_id != null ) {
				return self.previous_random_id;
			} else {
				self.create_random_id( prefix, false );
			}
		}
	};

	/**
	 * recreate_input_structure
	 *
	 * Recreates the input structure for the latitdue/longitude fields.
	 *
	 * @param input_name
	 *
	 * @since 1.1.1
	 */
	self.recreate_input_structure = function( input_name ) {
		// Recreate input structure with new IDs and names
		self.inputs_structure = '<a class="toolset-google-map-toggle-latlon js-toolset-google-map-toggle-latlon">' + toolset_google_address_i10n.showhidecoords + '</a>';
		self.inputs_structure += '<div class="js-toolset-google-map-toggling-latlon toolset-google-map-toggling-latlon" style="display:none">';
		self.inputs_structure += '<p><label for="' + self.create_random_id( "toolset-google-map-lat" ) + '" class="toolset-google-map-label js-wpt-auxiliar-label">' + toolset_google_address_i10n.latitude + '</label><input id="' + self.create_random_id( "toolset-google-map-lat", true ) + '" name="toolset-extended-form-' + input_name + '[latitude]" class="js-toolset-google-map-latlon js-toolset-google-map-lat toolset-google-map-lat" type="text" value="" /></p>';
		self.inputs_structure += '<p><label for="' + self.create_random_id( "toolset-google-map-lon" ) + '" class="toolset-google-map-label js-wpt-auxiliar-label">' + toolset_google_address_i10n.longitude + '</label><input id="' + self.create_random_id( "toolset-google-map-lon", true ) + '" name="toolset-extended-form-' + input_name + '[longitude]" class="js-toolset-google-map-latlon js-toolset-google-map-lon toolset-google-map-lon" type="text" value="" /></p>';
		self.inputs_structure += '</div>';
	};
	
	/**
	 * Check whether Google Places AutocompleteService is available.
	 *
	 * @return bool
	 *
	 * @since 1.2.2
	 */
	
	self.test_google_autocomplete_service = function( thiz ) {
		if ( ! self.is_google_autocomplete_service_tested ) {
			var service = new google.maps.places.AutocompleteService();
			service.getPlacePredictions({ input: 'Phoenix' }, function ( predictions, status ) {
				self.is_google_autocomplete_service_tested	= true;
				self.is_google_autocomplete_service_working	= ( status == google.maps.places.PlacesServiceStatus.OK );
				self.init_address_field( thiz );
			});
		} else {
			self.init_address_field( thiz );
		}
	};

		
	/**
	* ###########################################
	* Validation methods
	* ###########################################
	*/
	
	/**
	* is_valid_latitude
	*
	* Validate latitude value candidates
	*
	* @param lat	string		The latitude candidate
	*
	* @return bool
	*
	* @since 1.1.0
	*/
	
	self.is_valid_latitude = function( lat ) {
		var result = false;
		if ( self.validate_lat.test( lat ) ) {
			result = true;
		}
		return result;
	};
	
	/**
	* is_valid_longitude
	*
	* Validate longitude value candidates
	*
	* @param lat	string		The longitude candidate
	*
	* @return bool
	*
	* @since 1.1.0
	*/
	
	self.is_valid_longitude = function( lon ) {
		var result = false;
		if ( self.validate_lon.test( lon ) ) {
			result = true;
		}
		return result;
	};
	
	/**
	* ###########################################
	* Init and re-init methods
	* ###########################################
	*/
	
	/**
	* init_address_fields
	*
	* Init all address fields on the page
	*
	* @uses self.init_address_field
	*
	* @since 1.0.0
	*/
	self.init_address_fields = function() {
        $( self.selector ).each( function() {
			self.test_google_autocomplete_service( $( this ) );
        });
    };
	
	/**
	* init_address_fields_in_group
	*
	* Init all adddress fields in a given DOM element
	*
	* @uses self.init_address_field
	*
	* @since 1.0.0
	*/
	
	self.init_address_fields_in_group = function( $container ) {
        $( self.selector, $container ).each( function() {
			self.test_google_autocomplete_service( $( this ) );
        });
    };
	
	/**
	 * init_address_field
	 *
	 * Init a given address field
	 *
	 * @since 1.0.0
	 * @since 1.1.0	Do not init addres fields inside Toolset Forms hidden fields
	 * @since 1.2.2	Check that Google autocomplete services are working before using them
	 */

    self.init_address_field = function( thiz ) {
		
        if ( thiz.hasClass( 'js-toolset-google-map-geocomplete-added' ) ) {
            return;
        }
		
		if ( ! self.is_google_autocomplete_service_working ) {
			thiz.before( '<p class="toolset-alert toolset-alert-error">' + toolset_google_address_i10n.autocompleteoff + '</p>' );
			thiz.hasClass( 'js-toolset-google-map-geocomplete-added' )
			return;
		}
		
		var thiz_postbox = thiz.closest( '.postbox' );
		
		if ( thiz_postbox.hasClass( 'closed' ) ) {
			return;
		}
		
		var thiz_conditionalbox = thiz.closest( '.js-wpt-field' );
		
		if ( thiz_conditionalbox.hasClass( 'wpt-conditional-hidden' ) ) {
			return;
		}
		
		var thiz_container		= thiz.closest( '.js-toolset-google-map-container' ),
		thiz_inputs_container	= thiz.closest( '.js-toolset-google-map-inputs-container' ),
		thiz_marker_options		= {},
		thiz_name				= thiz.attr( 'name' );
		
		if ( ! thiz.prop( 'disabled' ) ) {
			thiz_marker_options = {
				draggable: true
			};
		}
		
		self.recreate_input_structure( thiz_name );
		
		$( self.preview_structure )
			.appendTo( thiz_container );
		
		$( self.inputs_structure )
			.appendTo( thiz_inputs_container );
		
		var thiz_val = thiz.val(),
		this_coordinates = thiz.data( 'coordinates' ),
		this_map_options = {
			map: $('.js-toolset-google-map-preview', thiz_container),
			markerOptions: thiz_marker_options
		},
		thiz_init_geocode = false;
		
		if ( '' != thiz_val ) {
			thiz_init_geocode = true;
		}
		
		if (
			'' != this_coordinates 
			&& this_coordinates.match("^{") 
			&& this_coordinates.match("}$")
		) {
			var thiz_coords = this_coordinates.slice( 1, -1 ),
			thiz_location = thiz_coords.split( ',' );
			if ( thiz_location.length == 2 ) {
				if (
					self.is_valid_latitude( thiz_location[0] ) 
					&& self.is_valid_longitude( thiz_location[1] )
				) {
					this_map_options[ 'location' ] = thiz_location;
					$( '.js-toolset-google-map-lat', thiz_container ).val( thiz_location[0] );
					$( '.js-toolset-google-map-lon', thiz_container ).val( thiz_location[1] );
				}
				thiz_init_geocode = false;
			}
		} else if (
			'' != thiz_val 
			&& thiz_val.match("^{") 
			&& thiz_val.match("}$")
		) {
			// Let's cover ourselves in case we have a {lat,lon} value but no data-coordinates for some reason
			var thiz_coords = thiz_val.slice( 1, -1 ),
			thiz_location = thiz_coords.split( ',' );
			if ( thiz_location.length == 2 ) {
				if (
					self.is_valid_latitude( thiz_location[0] ) 
					&& self.is_valid_longitude( thiz_location[1] )
				) {
					this_map_options[ 'location' ] = thiz_location;
					$( '.js-toolset-google-map-lat', thiz_container ).val( thiz_location[0] );
					$( '.js-toolset-google-map-lon', thiz_container ).val( thiz_location[1] );
				}
				thiz_init_geocode = false;
			}
		}
		
		
		thiz_map = thiz
			.geocomplete( this_map_options )
			.bind( "geocode:result", function( event, result ) {
				if ( 'undefined' != typeof result.geometry.location ) {
					keys = Object.keys( result.geometry.location );
					$( '.js-toolset-google-map-lat', thiz_container ).val( result.geometry.location[keys[0]] );
					$( '.js-toolset-google-map-lon', thiz_container ).val( result.geometry.location[keys[1]] );
				}
			});
		
		if ( thiz_init_geocode ) {
			thiz.trigger('geocode');
		}
		
		if ( thiz.prop( 'disabled' ) ) {
			$( '.js-toolset-google-map-lat', thiz_container ).prop( 'disabled', true );
			$( '.js-toolset-google-map-lon', thiz_container ).prop( 'disabled', true );
		} else {
			thiz.bind( "geocode:dragged", function( event, new_position ) {
				if ( new_position == null ) {
					return;
				}
				self.update_latlon_values( thiz_container, new_position.lat(), new_position.lng(), 'both', false );
				self.get_closest_address_position( new_position, thiz_container );
			});
		}
		
		thiz.addClass( 'js-toolset-google-map-geocomplete-added' );
		
    }
	
	/**
	* ###########################################
	* Auxiliar methods
	* ###########################################
	*/
	
	/**
	* update_latlon_values
	*
	* Update the address, latitude and longitude fields on a simpe container, and maybe force the preview reload
	*
	* @param container				object		The container for the given address field instance
	* @param lat_val				string		The new latitude value
	* @param lon_val				string		The new longitude value
	* @param update_main_target		string		The reason for the update, the fields that will get new values
	* @param force_reload_preview	bool		Whether the mp preview needs further reload action
	*
	* @since 1.1.0
	*/
	
	self.update_latlon_values = function( $container, lat_val, lon_val, update_main_target, force_reload_preview ) {
		var lat = $container.find( '.js-toolset-google-map-lat' ),
		lon = $container.find( '.js-toolset-google-map-lon' ),
		address = $container.find( '.js-toolset-google-map' ),
		thiz_toggling = $container.find( '.js-toolset-google-map-toggling-latlon' );
		$container
			.find( '.js-toolset-latlon-error' )
			.removeClass( 'toolset-latlon-error js-toolset-latlon-error' );
		if (
			! self.is_valid_latitude( lat_val ) 
			|| ! self.is_valid_longitude( lon_val )
		) {
			if ( update_main_target == 'latlon' ) {
				address.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
			} else if ( update_main_target == 'address' ) {
				lat.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
				lon.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
			} else if ( update_main_target == 'both' ) {
				address.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
				lat.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
				lon.addClass( 'toolset-latlon-error js-toolset-latlon-error' );
			}
			address.trigger( 'js_event_toolset_latlon_values_error' );
		} else {
			lat.val( lat_val );
			lon.val( lon_val );
			address
				.val( "{" + lat_val + ',' + lon_val + "}" )
				.data( 'coordinates', "{" + lat_val + ',' + lon_val + "}" )
				.trigger( 'js_event_toolset_latlon_values_updated' );
			if ( force_reload_preview ) {
				address.trigger( 'js_event_toolset_latlon_updated_needs_preview_reload' );
			}
			if ( update_main_target == 'address' ) {
				self.glow_selectors( address, 'toolset-being-updated' );
			} else if ( update_main_target == 'latlon' ) {
				thiz_toggling.slideDown( 'fast', function() {
					self.glow_selectors( lat, 'toolset-being-updated' );
					self.glow_selectors( lon, 'toolset-being-updated' );
				});
			} else if ( update_main_target == 'both' ) {
				thiz_toggling.slideDown( 'fast', function() {
					self.glow_selectors( address, 'toolset-being-updated' );
					self.glow_selectors( lat, 'toolset-being-updated' );
					self.glow_selectors( lon, 'toolset-being-updated' );
				});
			}
		}
	};
	
	/**
	* get_closest_address_position
	*
	* Get the closest address to a given location set by latitude and longitude pairs passed as a google.maps.LatLng object
	* The, display the auxiliar box for using it if desired
	*
	* @param position	object		The google.maps.LatLng object
	* @param container	object		The address field instance container
	*
	* @since 1.1.0
	*/
	
	self.get_closest_address_position = function( position, container ) {
		self.geocoder.geocode({
			latLng: position
		}, function( responses ) {
			if (
				responses 
				&& responses.length > 0
			) {
				container
					.find( '.js-toolset-google-map-preview-closest-address-value' )
					.html( responses[0].formatted_address )
					.data( 'lat', responses[0].geometry.location.lat() )
					.data( 'lon', responses[0].geometry.location.lng() );
				container
					.find( '.toolset-google-map-preview-closest-address' )
					.slideDown( 'fast' );
			}
		});
	};
	
	/**
	* ###########################################
	* Events
	* ###########################################
	*/
	
	/**
	* Toogle container for the latitude and longitude inputs
	*
	* @since 1.0.0
	*/
	
	$( document ).on( 'click', '.js-toolset-google-map-toggle-latlon', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-toolset-google-map-inputs-container' ),
		thiz_toggling = thiz_container.find( '.js-toolset-google-map-toggling-latlon' );
		thiz_toggling.slideToggle( 'fast' );
	});
	
	/**
	* Update latitude and longitude values when editing the values of latitude or longitude inputs
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'input cut paste', '.js-toolset-google-map-latlon', function( e ) {
		var $container = $( this ).closest( '.js-toolset-google-map-inputs-container' ),
		lat_val = $container.find( '.js-toolset-google-map-lat' ).val(),
		lon_val = $container.find( '.js-toolset-google-map-lon' ).val();
		self.update_latlon_values( $container, lat_val, lon_val, 'address', true );
	});
	
	/**
	* Update latitude and longitude values when editing the values of the address input if it uses the {lat,lon} format
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'input cut paste', self.selector, function( e ) {
		var thiz = $( this ),
		thiz_val = thiz.val();
		if (
			'' != thiz_val 
			&& thiz_val.match("^{") 
			&& thiz_val.match("}$")
		) {
			var thiz_coords = thiz_val.slice( 1, -1 ),
			thiz_location = thiz_coords.split( ',' ),
			thiz_container = thiz.closest( '.js-toolset-google-map-container' );
			if ( thiz_location.length == 2 ) {
				self.update_latlon_values( thiz_container, thiz_location[0], thiz_location[1], 'latlon', true );
			}
		}
	});
	
	/**
	* Enforces a reload overlay for the map preview when playing with latitude and longitude coordinates
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'js_event_toolset_latlon_updated_needs_preview_reload', self.selector, function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-toolset-google-map-container' );
		if ( thiz_container.find( '.js-toolset-google-map-preview-reload' ).length > 0 ) {
			return;
		}
		$( '<div class="toolset-google-map-preview-reload js-toolset-google-map-preview-reload"><a class="toolset-google-map-reload-preview js-toolset-google-map-reload-preview">ReloadPreview</a></div>' )
			.prependTo( thiz_container.find( '.js-toolset-google-map-preview' ) )
			.fadeIn( 'fast' );
		thiz_container
			.find( '.toolset-google-map-preview-closest-address' )
			.slideUp( 'fast' );
	});
	
	/**
	* Reloads the map preview using the values from the address field, if it uses the {lat,lon} format
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'click', '.js-toolset-google-map-reload-preview', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-toolset-google-map-container' ),
		thiz_container_overlay = thiz_container.find( '.js-toolset-google-map-preview-reload' );
		if ( thiz_container.find( '.js-toolset-latlon-error' ).length > 0 ) {
			return;
		}
		thiz_container_overlay.fadeOut( 'fast', function() {
			thiz_container_overlay.remove();
		});
		$( self.selector, thiz_container ).each( function() {
			var thiz_val = $( this ).val();
			if (
				'' != thiz_val 
				&& thiz_val.match("^{") 
				&& thiz_val.match("}$")
			) {
				var thiz_coords = thiz_val.slice( 1, -1 ),
				thiz_location = thiz_coords.split( ',' );
				if ( thiz_location.length == 2 ) {
					var thiz_map = $( this ).geocomplete( "map" ),
					thiz_marker = $( this ).geocomplete( "marker" ),
					thiz_latLng = new google.maps.LatLng( thiz_location[0], thiz_location[1] );
					if ( thiz_latLng ) {
						thiz_map.setCenter( thiz_latLng );
						thiz_marker.setPosition( thiz_latLng );
						self.get_closest_address_position( thiz_latLng, thiz_container );
					}
				}
			}
        });
	});
	
	/**
	* Ensures the address field that belongs to a postbox that is first rendered closed is init when the postbox is opened
	*
	* @since 1.0.0
	*/
	
	$( document ).on( 'postbox-toggled', function( event, postbox ) {
		if ( 
			$( self.selector, postbox ).length > 0 
			&& ! $( postbox ).hasClass( 'closed' )
		) {
			self.init_address_fields_in_group( postbox );
		}
	});
	
	/**
	* Ensures the address field that belongs to a post relationship table is init when the child post row is reloaded
	*
	* @since 1.0.0
	*/
	
	$( document ).on( 'js_event_wpcf_types_relationship_child_saved js_event_wpcf_types_relationship_child_added js_event_wpcf_types_relationship_children_changed', function( event, data ) {
		self.init_address_fields_in_group( data.table );
	});
	
	/**
	* Ensures the address field that belongs to a conditionally hidden piece is init when the piece is shown
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'js_event_toolset_forms_conditional_field_toggled', function( event, data ) {
		if ( 
			$( self.selector, data.container ).length > 0 
			&& data.visible 
		) {
			self.init_address_fields_in_group( data.container );
		}
	});
	
	/**
	* Re-init ddress fields on CRED forms after they get submitted using AJAX.
	*
	* @since 1.1.1
	*/
	
	$( document ).on( 'js_event_cred_ajax_form_response_completed', function( event ) {
		self.init_address_fields();
	});
	
	/**
	* Applies the closest address provided after a finetune
	*
	* @since 1.1.0
	*/
	
	$( document ).on( 'click', '.js-toolset-google-map-preview-closest-address-apply', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-toolset-google-map-container' ),
		thiz_address = thiz_container.find( '.js-toolset-google-map-preview-closest-address-value' ),
		lat = thiz_container.find( '.js-toolset-google-map-lat' ),
		lon = thiz_container.find( '.js-toolset-google-map-lon' ),
		address = thiz_container.find( '.js-toolset-google-map' ),
		//thiz_map = address.geocomplete( "map" ),
		thiz_marker = address.geocomplete( "marker" ),
		thiz_lat_lon = new google.maps.LatLng( thiz_address.data( 'lat' ), thiz_address.data( 'lon' ) );
		if ( thiz_lat_lon ) {
			//thiz_map.setCenter( thiz_lat_lon );
			thiz_marker.setPosition( thiz_lat_lon );
		}
		
		lat.val( thiz_address.data( 'lat' ) );
		lon.val( thiz_address.data( 'lon' ) );
		address
			.val( thiz_address.html() )
			.data( 'coordinates', "{" + thiz_address.data( 'lat' ) + ',' + thiz_address.data( 'lon' ) + "}" )
			.trigger( 'js_event_toolset_latlon_values_updated' );

		self.glow_selectors( address, 'toolset-being-updated' );
		self.glow_selectors( lat, 'toolset-being-updated' );
		self.glow_selectors( lon, 'toolset-being-updated' );
		
		thiz_container
			.find( '.toolset-google-map-preview-closest-address' )
			.slideUp( 'fast' );
	});
	
	/**
	* init
	*
	* Init the script
	*
	* @since 1.0.0
	*/

    self.init = function() {
        self.init_address_fields();
		wptCallbacks.addRepetitive.add( self.init_address_fields_in_group );
    };

    self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.addon_maps_editor = new WPViews.AddonMapsEditor( $ );
});
