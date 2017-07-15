/**
* wpv_addon_maps_dialogs.js
*
* Contains helper functions for the dialogs GUI used for the WP Views Addon Maps links
*
* @since 1.0
* @package Views Addon Maps
*/

var WPViews = WPViews || {};

WPViews.AddonMapsDialogs = function( $ ) {
	
	var self = this;
	
	self.cache = {};
	self.map_counter = 0;
	self.marker_counter = 0;
	self.inserted_map_ids = [];
	
	self.current_active_editor = null;
	
	self.has_colorpicker = ( typeof $.fn.wpColorPicker == 'function' );
	
	self.dialogs = {};
	
	self.extended_links_computed_classname_filters = {};
	
	if ( typeof WPV_Toolset.only_img_src_allowed_here !== "undefined" ) {
		WPV_Toolset.only_img_src_allowed_here.push( "js-wpv-toolset-maps-add-map-image" );
		WPV_Toolset.only_img_src_allowed_here.push( "js-wpv-toolset-maps-add-map-image-hover" );
		WPV_Toolset.only_img_src_allowed_here.push( "js-wpv-toolset-maps-add-marker-image" );
		WPV_Toolset.only_img_src_allowed_here.push( "js-wpv-toolset-maps-add-marker-image-hover" );
	}
	
	self.is_views_editor = false;
	self.is_wpa_editor = false;
	self.current_content_type = 'posts';
	
	/**
	* context = map|marker
	* type = image|image-hover
	*/
	self.add_marker_button = function( context, type ) {
		var result = '';
		result += '<span style="display:none"><input type="text" autocomplete="off" id="js-wpv-toolset-maps-add-' + context + '-' + type + '" data-context="' + context + '" data-type="' + type + '" /></span>';
		result += '<button class="button button-secondary js-wpv-toolset-maps-media-manager js-wpv-media-manager" data-content="js-wpv-toolset-maps-add-' + context + '-' + type + '" data-id="0">';
		result += '<i class="icon-plus fa fa-plus"></i> ' + wpv_addon_maps_dialogs_local.add_marker_icon;
		result += '</button>';
		return result;
	};
	
	self.add_marker_hover_options = function() {
		var result = '';
		result += '<ul class="js-wpv-dismiss">';
		result += '<li><label><input type="radio" name="wpv-addon-maps-different-hover-image" value="same" class="js-wpv-addon-maps-different-hover-image" checked="checked" />' + wpv_addon_maps_dialogs_local.use_same_image + '</label></li>';
		result += '<li><label><input type="radio" name="wpv-addon-maps-different-hover-image" value="other" class="js-wpv-addon-maps-different-hover-image" />' + wpv_addon_maps_dialogs_local.user_another_image + '</label></li>';
		result += '</ul>';
		return result;
	};
	
	self.add_marker_result = function( context, type, value ) {
		var result = '',
		thiz_name = '';
		if ( context == 'map' ) {
			if ( type == 'image' ) {
				thiz_name = 'wpv-map-render-marker_icon';
			} else if ( type == 'image-hover' ) {
				thiz_name = 'wpv-map-render-marker_icon_hover';
			}
		} else if ( context == 'marker' ) {
			if ( type == 'image' ) {
				thiz_name = 'wpv-map-marker-marker_icon';
			} else if ( type == 'image-hover' ) {
				thiz_name = 'wpv-map-marker-marker_icon_hover';
			}
		}
		result = '<li><label><input class="js-shortcode-gui-field" type="radio" value="' + value + '" name="' + thiz_name + '">';
		result += '<span class="wpv-icon-img js-wpv-icon-img" data-img="' + value + '" style="background-image:url(' + value + ');"></span></label></li>';
		return result;
	};
	
	self.after_closing_media_modal = function() {
		if ( self.current_active_editor !== null ) {
			$( 'body' ).addClass( 'modal-open' );
			window.wpcfActiveEditor = self.current_active_editor;
		}
	};
	
	$( document ).on( 'click', '.media-modal-close', function() {
		self.after_closing_media_modal();
	});
	
	$( document ).on( 'js_icl_media_manager_inserted', '#js-wpv-toolset-maps-add-map-image, #js-wpv-toolset-maps-add-map-image-hover, #js-wpv-toolset-maps-add-marker-image, #js-wpv-toolset-maps-add-marker-image-hover', function( event ) {
		var thiz = $( this ),
		thiz_context = thiz.data( 'context' ),
		thiz_type = thiz.data( 'type' ),
		marker_container = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon' ),
		marker_hover_container = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' ),
		data = {
			action: 'wpv_addon_maps_update_marker',
			csaction: 'add',
			cstarget: thiz.val(),
			wpnonce: wpv_addon_maps_dialogs_local.global_nonce
		};
		
		self.after_closing_media_modal();
		
		if ( $( 'span[data-img="' + thiz.val() + '"]', marker_container ).length > 0 ) {
			thiz.val('');
			return;
		}
		
		$( '.js-wpv-toolset-maps-media-manager' ).prop( 'disabled', true );
		
		$.ajax({
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					marker_container
						.find( 'ul:not(.js-wpv-dismiss)' )
							.append( self.add_marker_result( thiz_context, 'image', thiz.val() ) );
					marker_hover_container
						.find( 'ul:not(.js-wpv-dismiss)' )
							.append( self.add_marker_result( thiz_context, 'image-hover', thiz.val() ) );
					if ( thiz_type == 'image' ) {
						marker_container
							.find( 'ul:not(.js-wpv-dismiss) li:last .js-shortcode-gui-field' )
								.trigger( 'click' );
					} else if ( thiz_type == 'image-hover' ) {
						// This might not be needed anymore, keep just in case
						marker_hover_container
							.find( 'ul:not(.js-wpv-dismiss) li:last .js-shortcode-gui-field' )
								.trigger( 'click' );
					}
				}
			},
			error: function( ajaxContext ) {
				
			},
			complete: function() {
				thiz.val('');
				$( '.js-wpv-toolset-maps-media-manager' ).prop( 'disabled', false );
			}
		});
		
	});
	
	self.init_colorpicker = function() {
		if ( ! self.has_colorpicker ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-background_color .description' )
				.append( '<br />' + wpv_addon_maps_dialogs_local.background_hex_format );
			return;
		}
		$( '#wpv-map-render-background_color' ).wpColorPicker({
			change: function( event, ui ) {
				
			},
			clear: function() {
				
			},
			palettes: true
		});
	};
	
	self.wpv_open_dialog = function( kind, title, params, nonce, object ) {
		
		var dialog_height = $( window ).height() - 100;
		
		if ( kind in self.dialogs ) {
			self.dialogs[kind].dialog('open').dialog({
				title: title,
				width: 650,
				maxHeight: dialog_height,
				draggable: false,
				resizable: false,
				position: { my: "center top+50", at: "center top", of: window }
			});
		}
		
	};
	
	self.init_counters = function() {
		self.map_counter = wpv_addon_maps_dialogs_local.counters.map;
		self.marker_counter = wpv_addon_maps_dialogs_local.counters.map;
	}
	
	self.update_counters = function() {
		// perform man AJAX call to save the new values - both of them
		var data = {
			action: 'wpv_toolset_maps_addon_update_counters',
			map_counter: self.map_counter,
			marker_counter: self.marker_counter,
			wpnonce: wpv_addon_maps_dialogs_local.nonce
		};
		$.ajax({
			async: false,
			type: "POST",
			url: ajaxurl,
			data: data,
			dataType: "json",
			success: function( response ) {
				
			},
			error: function( ajaxContext ) {
				
			},
			complete: function() {
				
			}
		});
	};
	
	self.init_dialogs = function() {
		self.dialogs['reload'] = $( "#js-wpv-addon-maps-dialog-reload" ).dialog({
			autoOpen: false,
			modal: true,
			minWidth: 450,
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-addon-maps-links, .js-wpv-addon-maps-anchor, .js-wpv-addon-maps-class, .js-wpv-addon-maps-style', '#js-wpv-addon-maps-dialog-reload' ).val( '' );
				$( '.js-wpv-addon-maps-insert-reload' )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' )
					.prop( 'disabled', true );
				$('.js-wpv-addon-maps-reload-tabs')
					.tabs()
					.addClass('ui-tabs-vertical ui-helper-clearfix')
					.removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$('.js-wpv-addon-maps-reload-tabs, .js-wpv-addon-maps-reload-tabs li').removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$( document ).trigger( 'js_event_wpv_addon_maps_extra_dialog_opened', [ 'reload' ] );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: wpv_addon_maps_dialogs_local.close_dialog,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-secondary js-wpv-addon-maps-insert-reload',
					text: wpv_addon_maps_dialogs_local.insert_link,
					'data-kind': 'reload',
					click: function() {
						self.insert_to_editor( 'reload' );
					}
				}
			]
		});
		self.dialogs['focus'] = $( "#js-wpv-addon-maps-dialog-focus" ).dialog({
			autoOpen: false,
			modal: true,
			minWidth: 450,
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-addon-maps-links, .js-wpv-addon-maps-anchor, .js-wpv-addon-maps-class, .js-wpv-addon-maps-style', '#js-wpv-addon-maps-dialog-focus' ).val( '' );
				$( '.js-wpv-addon-maps-focus-interaction', '#js-wpv-addon-maps-dialog-focus' ).prop( 'checked', false );
				$( '.js-wpv-addon-maps-insert-focus' )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' )
					.prop( 'disabled', true );
				$('.js-wpv-addon-maps-focus-tabs')
					.tabs({ active: 0 })
					.addClass('ui-tabs-vertical ui-helper-clearfix')
					.removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$('.js-wpv-addon-maps-focus-tabs, .js-wpv-addon-maps-focus-tabs li').removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$( document ).trigger( 'js_event_wpv_addon_maps_extra_dialog_opened', [ 'focus' ] );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: wpv_addon_maps_dialogs_local.close_dialog,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-secondary js-wpv-addon-maps-insert-focus',
					text: wpv_addon_maps_dialogs_local.insert_link,
					'data-kind': 'focus',
					click: function() {
						self.insert_to_editor( 'focus' );
					}
				}
			]
		});
		self.dialogs['restore'] = $( "#js-wpv-addon-maps-dialog-restore" ).dialog({
			autoOpen: false,
			modal: true,
			minWidth: 450,
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-addon-maps-links, .js-wpv-addon-maps-anchor, .js-wpv-addon-maps-class, .js-wpv-addon-maps-style', '#js-wpv-addon-maps-dialog-restore' ).val( '' );
				$( '.js-wpv-addon-maps-insert-restore' )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' )
					.prop( 'disabled', true );
				$('.js-wpv-addon-maps-restore-tabs')
					.tabs()
					.addClass('ui-tabs-vertical ui-helper-clearfix')
					.removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$('.js-wpv-addon-maps-restore-tabs, .js-wpv-addon-maps-restore-tabs li').removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$( document ).trigger( 'js_event_wpv_addon_maps_extra_dialog_opened', [ 'restore' ] );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: wpv_addon_maps_dialogs_local.close_dialog,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-secondary js-wpv-addon-maps-insert-restore',
					text: wpv_addon_maps_dialogs_local.insert_link,
					'data-kind': 'restore',
					click: function() {
						self.insert_to_editor( 'restore' );
					}
				}
			]
		});
	};
	
	$( document ).on( 'input change paste cut', '.js-wpv-addon-maps-links, .js-wpv-addon-maps-anchor', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpv-dialog' ),
		thiz_kind = thiz_container.data( 'kind' ),
		thiz_button = $( '.js-wpv-addon-maps-insert-' + thiz_kind );
		thiz_enable = true;
		thiz_container.find( '.js-wpv-addon-maps-links, .js-wpv-addon-maps-anchor' ).each( function() {
			if ( $( this ).val() == '' ) {
				thiz_enable = false;
				return false;
			}
		});
		if ( thiz_enable ) {
			thiz_button
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			thiz_button
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		}
	});
	
	self.insert_to_editor = function( kind ) {
		var shortcode_to_insert,
		container,
		tag,
		tag_end,
		classname = '',
		style = '',
		attribute = {},
		atribute_string = '';
		
		if ( kind in self.dialogs ) {
			if ( $( '#js-wpv-addon-maps-dialog-' + kind ).length > 0 ) {
				container = $( '#js-wpv-addon-maps-dialog-' + kind );
				tag = container.find( '.js-wpv-addon-maps-tag' ).val();
				switch ( tag ) {
					case 'button':
						shortcode_to_insert = '<button';
						tag_end = '</button>';
						break;
					default:
						shortcode_to_insert = '<a href="#"';
						tag_end = '</a>';
						break;
				}
				classname = 'js-wpv-addon-maps-' + kind + '-map';
				if ( container.find( '.js-wpv-addon-maps-class' ).val() != '' ) {
					classname += ' ' + container.find( '.js-wpv-addon-maps-class' ).val();
				}
				if ( container.find( '.js-wpv-addon-maps-style' ).val() != '' ) {
					style += ' style="' + container.find( '.js-wpv-addon-maps-style' ).val() + '"';
				}
				container.find( '.js-wpv-addon-maps-links' ).each( function() {
					attribute[ $( this ).data( 'attribute' ) ] = $( this ).val();
					atribute_string += ' data-' + $( this ).data( 'attribute' ) + '="' + $( this ).val() + '"';
				});
				
				classname = self.extended_links_computed_classname( kind, classname, style, attribute );
				
				shortcode_to_insert += ' class="' + classname + '"';
				shortcode_to_insert += style;
				shortcode_to_insert += atribute_string;
				
				shortcode_to_insert += '>';
				shortcode_to_insert += container.find( '.js-wpv-addon-maps-anchor' ).val();
				shortcode_to_insert += tag_end;
				self.dialogs[ kind ].dialog('close');
				
				if ( WPViews.shortcodes_gui.shortcode_gui_insert == 'insert' ) {
					window.icl_editor.insert( shortcode_to_insert );
				}
				$( document ).trigger( 'js_event_wpv_shortcode_inserted', [ 'wpv-addon-maps-' + kind, '', {}, shortcode_to_insert ] );
			} else {
				self.dialogs[ kind ].dialog('close');
			}
			
		}
	};
	
	$( document ).on( 'js_event_wpv_shortcode_inserted', function( event, shortcode_name, shortcode_content, shortcode_attribute_values, shortcode_to_insert ) {
		if ( shortcode_name == 'wpv-map-render' ) {
			self.current_active_editor = null;
			self.inserted_map_ids.push( shortcode_attribute_values['map_id'] );
			self.cache['map_id'] = shortcode_attribute_values['map_id'];
			// Only update counters when used the default map_id format
			if ( shortcode_attribute_values['map_id'] == 'map-' + ( self.map_counter + 1 ) ) {
				self.map_counter++;
				self.update_counters();
			}
		}
		if ( shortcode_name == 'wpv-map-marker' ) {
			self.current_active_editor = null;
			// Only update counters when used the default marker_id format
			if ( shortcode_attribute_values['marker_id'] == 'marker-' + ( self.marker_counter + 1 ) ) {
				self.marker_counter++;
				self.update_counters();
			}
		}
	});
	
	$( document ).on( 'js_event_wpv_shortcode_gui_dialog_opened', function( event, data ) {
		switch ( data.shortcode ) {
			case 'wpv-map-render':
				if ( $( '.js-wpv-shortcode-gui-attribute-wrapper-for-missing_api_key' ).length > 0 ) {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-missing_api_key h3' ).remove();
					$( '.js-wpv-shortcode-gui-button-insert' )
						.prop( 'disabled', true )
						.toggleClass( 'button-secondary button-primary' );
				} else {
					// Set current active editor
					self.current_active_editor = window.wpcfActiveEditor;
					// Preload the map ID
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-map_id #wpv-map-render-map_id' ).val( 'map-' + ( self.map_counter + 1 ) );
					// Hide advanced options that are shown on demand: zoom and center
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-general_zoom, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lat, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lon, .js-wpv-shortcode-gui-attribute-wrapper-for-single_center' ).hide();
					// Hide advanced options that are shown on demand: cluster
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster_click_zoom' ).hide();
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lon h3' ).remove();
					self.init_cluster_options();
					self.init_marker_icons( 'map' );
					self.init_colorpicker();
				}
				break;
			case 'wpv-map-marker':
				if ( $( '.js-wpv-shortcode-gui-attribute-wrapper-for-missing_api_key' ).length > 0 ) {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-missing_api_key h3' ).remove();
					$( '.js-wpv-shortcode-gui-button-insert' )
						.prop( 'disabled', true )
						.toggleClass( 'button-secondary button-primary' );
				} else {
					// Set current active editor
					self.current_active_editor = window.wpcfActiveEditor;
					self.init_ids_for_marker();
					self.init_sources_for_marker();
					self.init_marker_icons( 'marker' );
					self.init_marker_icons_inherit();
				}
				break;
		}
	});
	
	self.init_ids_for_marker = function() {
		// Preload the map ID input with the latests cached value
		// Show other options for map IDs used in this page, if there is more than one - ¿?
		// Not sure about this, because you might have deleted it to re-create it again... - skipping
		// In case there is no map ID cached -> No map was inserted since last page reload -> Show warning/info/something
		if ( 
			'map_id' in self.cache 
			&& self.cache['map_id'] != '' 
		) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-map_id #wpv-map-marker-map_id' ).val( self.cache['map_id'] );
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-map_id h3' ).after( '<span class="toolset-alert js-wpv-add-a-map-first">' + wpv_addon_maps_dialogs_local.add_a_map_first + '</span>' );
		}
		// Preload a marker ID value
		// Note than on Loop Outputs in Views it better includes a [wpv-post-id] shortcode
		if ( 
			self.is_views_editor 
			|| self.is_wpa_editor
		) {
			var marker_placeholder_shortcode = '[wpv-post-id]';
			switch( self.current_content_type ) {
				case 'taxonomy':
					marker_placeholder_shortcode = '[wpv-taxonomy-id]';
					break;
				case 'users':
					// Note: we do not have a shortcode without atributes for displaying the ID of the current user in the loop, we might want to add one...
					marker_placeholder_shortcode = ( self.marker_counter + 1 );
					break;
			}
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_id #wpv-map-marker-marker_id' ).val( 'marker-' + marker_placeholder_shortcode );
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_id #wpv-map-marker-marker_id' ).val( 'marker-' + ( self.marker_counter + 1 ) );
		}
	};
	
	self.init_sources_for_marker = function() {
		var current_suggest_callback = 'wpv_suggest_wpv_post_field_name',
		current_generic_description = wpv_addon_maps_dialogs_local.marker_source_desc.posts_attr_id;
		// Initialize the address origin depending on whether there are Types fields or not
		// Add any other options like generic field, custom address and lat/lon pairs
		// Check the first option and .geocomplete() the custom address option
		if ( _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_postmeta_fields ) ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_postmeta_field"]')
				.closest( 'li' )
					.remove();
		}
		if ( self.current_content_type == 'taxonomy' ) {
			current_suggest_callback = 'wpv_suggest_wpv_taxonomy_field_name';
			current_generic_description = wpv_addon_maps_dialogs_local.marker_source_desc.taxonomy_attr_id_v;
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_usermeta_field"]')
				.closest( 'li' )
					.remove();
			if ( _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_termmeta_fields ) ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_termmeta_field"]')
					.closest( 'li' )
						.remove();
			}
		} else if ( self.current_content_type == 'users' ) {
			current_suggest_callback = 'wpv_suggest_wpv_user_field_name';
			current_generic_description = wpv_addon_maps_dialogs_local.marker_source_desc.users_attr_id_v;
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_termmeta_field"]')
				.closest( 'li' )
					.remove();
			if ( _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_usermeta_fields ) ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_usermeta_field"]')
					.closest( 'li' )
						.remove();
			}
		} else {
			if ( _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_termmeta_fields ) ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_termmeta_field"]')
					.closest( 'li' )
						.remove();
			}
			if ( _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_usermeta_fields ) ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field[value="types_usermeta_field"]')
					.closest( 'li' )
						.remove();
			}
		}
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field' )
			.each( function() {
				var thiz = $( this ),
				marker_position_item = thiz.closest( 'li' ),
				marker_position_source = thiz.val(),
				marker_position_extra_inner = '',
				marker_position_extra = '';
				switch ( marker_position_source ) {
					case 'types_postmeta_field':
						if ( ! _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_postmeta_fields ) ) {
							marker_position_extra_inner += '<select class="js-wpv-map-marker-marker_position-types_postmeta_field">';
							_.each( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_postmeta_fields, function( value, key ) {
								marker_position_extra_inner += '<option value="' + value.meta_key + '">' + value.name + '</option>';
							});
							marker_position_extra_inner += '</select>';
							marker_position_extra = '<div style="display:none" class="custom-combo-target js-wpv-map-marker-marker_position-target">';
							marker_position_extra += '<label for="wpv-map-marker-marker_position-types_postmeta_field-id" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.id_attribute_label.posts + '</label>';
							marker_position_extra += '<input id="wpv-map-marker-marker_position-types_postmeta_field-id" type="text" class="js-wpv-map-marker-marker_position-types_postmeta_field-id" value="" />';
							marker_position_extra += '<p class="description">' + wpv_addon_maps_dialogs_local.marker_source_desc.posts_attr_id + '</p>';
							marker_position_extra += '</div>';
						}
						break;
					case 'types_termmeta_field':
						if ( ! _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_termmeta_fields ) ) {
							marker_position_extra_inner += '<select class="js-wpv-map-marker-marker_position-types_termmeta_field">';
							_.each( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_termmeta_fields, function( value, key ) {
								marker_position_extra_inner += '<option value="' + value.meta_key + '">' + value.name + '</option>';
							});
							marker_position_extra_inner += '</select>';
							marker_position_extra = '<div style="display:none" class="custom-combo-target js-wpv-map-marker-marker_position-target">';
							marker_position_extra += '<label for="wpv-map-marker-marker_position-types_termmeta_field-id" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.id_attribute_label.taxonomy + '</label>';
							marker_position_extra += '<input id="wpv-map-marker-marker_position-types_termmeta_field-id" type="text" class="js-wpv-map-marker-marker_position-types_termmeta_field-id" value="" />';
							if ( self.current_content_type == 'taxonomy' ) {
								marker_position_extra += '<p class="description">' + wpv_addon_maps_dialogs_local.marker_source_desc.taxonomy_attr_id_v + '</p>';
							} else {
								marker_position_extra += '<p class="description">' + wpv_addon_maps_dialogs_local.marker_source_desc.taxonomy_attr_id + '</p>';
							}
							marker_position_extra += '</div>';
						}
						break;
					case 'types_usermeta_field':
						if ( ! _.isEmpty( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_usermeta_fields ) ) {
							marker_position_extra_inner += '<select class="js-wpv-map-marker-marker_position-types_usermeta_field">';
							_.each( wpv_addon_maps_dialogs_local.types_field_options.toolset_map_usermeta_fields, function( value, key ) {
								marker_position_extra_inner += '<option value="' + value.meta_key + '">' + value.name + '</option>';
							});
							marker_position_extra_inner += '</select>';
							marker_position_extra = '<div style="display:none" class="custom-combo-target js-wpv-map-marker-marker_position-target">';
							marker_position_extra += '<label for="wpv-map-marker-marker_position-types_usermeta_field-id" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.id_attribute_label.users + '</label>';
							marker_position_extra += '<input id="wpv-map-marker-marker_position-types_usermeta_field-id" type="text" class="js-wpv-map-marker-marker_position-types_usermeta_field-id" value="" />';
							if ( self.current_content_type == 'users' ) {
								marker_position_extra += '<p class="description">' + wpv_addon_maps_dialogs_local.marker_source_desc.users_attr_id_v + '</p>';
							} else {
								marker_position_extra += '<p class="description">' + wpv_addon_maps_dialogs_local.marker_source_desc.users_attr_id + '</p>';
							}
							marker_position_extra += '</div>';
						}
						break;
					case 'generic_field':
						marker_position_extra = '<div style="display:none" class="custom-combo-target js-wpv-map-marker-marker_position-target">';
						marker_position_extra += '<label for="toolset-gui-google-map-generic" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.generic_field_label[ self.current_content_type ] + '</label>';
						marker_position_extra += '<input type="text" id="toolset-gui-google-map-generic" class="js-wpv-map-marker-marker_position-generic_field js-wpv-shortcode-gui-suggest" data-action="' + current_suggest_callback + '" autocomplete="off" value="" />';
						
						marker_position_extra += '<br /><label for="wpv-map-marker-marker_position-generic_field-id" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.id_attribute_label[ self.current_content_type ] + '</label>';
						marker_position_extra += '<input id="wpv-map-marker-marker_position-generic_field-id" type="text" class="js-wpv-map-marker-marker_position-generic_field-id" value="" />';
						marker_position_extra += '<p class="description">' + current_generic_description + '</p>';
						
						marker_position_extra += '</div>';
						break;
					case 'address':
						marker_position_extra = '<input style="display:none" type="text" class="regular-text custom-combo-target js-wpv-map-marker-marker_position-target js-wpv-map-marker-marker_position-address" autocomplete="off" value="" />';
						break;
					case 'latlon':
						marker_position_extra = '<div style="display:none" class="custom-combo-target js-wpv-map-marker-marker_position-target">';
						marker_position_extra += '<label for="toolset-gui-google-map-lat" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.latitude + '</label><input type="text" id="toolset-gui-google-map-lat" class="js-wpv-map-marker-marker_position-latlon-lat toolset-google-map-lat" autocomplete="off" value="" />';
						marker_position_extra += '<br />';
						marker_position_extra += '<label for="toolset-gui-google-map-lon" class="toolset-google-map-label">' + wpv_addon_maps_dialogs_local.longitude + '</label><input type="text" id="toolset-gui-google-map-lon" class="js-wpv-map-marker-marker_position-latlon-lon toolset-google-map-lon" autocomplete="off" value="" />';
						marker_position_extra += '</div>';
						break;
				}
				if ( marker_position_extra_inner != '' ) {
					marker_position_item
						.find( 'label' )
						.append( marker_position_extra_inner );
				}
				if ( marker_position_extra != '' ) {
					marker_position_item.append( marker_position_extra );
				}
			})
			.first()
				.prop( 'checked', true )
				.trigger( 'change' );
		$( '.js-wpv-map-marker-marker_position-address', '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position' ).geocomplete();
	};
	
	self.init_marker_icons = function( context ) {
		// Highlight first options for marker icon and icon hover lists
		var marker_container = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon' ),
		marker_hover_container = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' );
		self.highlight_selected( marker_container );
		self.highlight_selected( marker_hover_container );
		// Hide the list for the hover icons behind a set of radio buttons
		// Add a button to upload a new marker icon in case the user has the right rights
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover ul' ).hide();
		marker_hover_container
			.find( 'h3' )
				.after( self.add_marker_hover_options() );
		if ( wpv_addon_maps_dialogs_local.can_manage_options == 'yes' ) {
			marker_container
				.append( self.add_marker_button( context, 'image' ) );
		}
	};
	
	self.init_marker_icons_inherit = function() {
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon, .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' ).hide();
	};
	
	self.init_cluster_options = function() {
		var options_container = '';
		options_container += '<div class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper-for-cluster_extra_options" style="display:none">';
		options_container += '<h3>' + wpv_addon_maps_dialogs_local.clusters.extra_options_title + '</h3>';
		options_container += '<ul>';
		options_container += '<li><label>' + wpv_addon_maps_dialogs_local.clusters.extra_options_min_size + '<input id="js-wpv-cluster-min-size" type="text" data-type="number" class="small-text" autocomplete="off" placeholder="2" /></label></li>';
		options_container += '<li><label>' + wpv_addon_maps_dialogs_local.clusters.extra_options_grid_size + '<input id="js-wpv-cluster-grid-size" type="text" data-type="number" class="small-text" autocomplete="off" placeholder="60" /></label></li>';
		options_container += '<li><label>' + wpv_addon_maps_dialogs_local.clusters.extra_options_max_zoom + '<input id="js-wpv-cluster-max-zoom" type="text" data-type="number" class="small-text" autocomplete="off" placeholder="" /></label></li>';
		options_container += '</ul>';
		options_container += '<p class="description">' + wpv_addon_maps_dialogs_local.clusters.extra_options_description + '</p>';
		options_container += '</div>';
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster' ).after( options_container );
	}
	
	$( document ).on( 'change', '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field', function() {
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-wpv-map-marker-marker_position-target' ).hide();
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field:checked' )
			.closest( 'li' )
			.find( '.js-wpv-map-marker-marker_position-target' )
				.slideDown( 'fast' );
		if ( self.current_content_type != 'taxonomy' ) {
			var selected_target = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position .js-shortcode-gui-field:checked' ).val();
			if ( selected_target == 'types_termmeta_field' ) {
				$( '.js-wpv-map-marker-marker_position-types_termmeta_field-id' ).addClass( 'js-shortcode-gui-field js-wpv-shortcode-gui-required' );
			} else {
				$( '.js-wpv-map-marker-marker_position-types_termmeta_field-id' ).removeClass( 'js-shortcode-gui-field js-wpv-shortcode-gui-required' );
			}
		}
	});
	
	$( document ).on( 'change', '.js-wpv-shortcode-gui-attribute-wrapper-for-fitbounds .js-shortcode-gui-field', function() {
		var value_selected = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-fitbounds .js-shortcode-gui-field:checked' ).val();
		if ( value_selected == 'on' ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-general_zoom, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lat, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lon, .js-wpv-shortcode-gui-attribute-wrapper-for-single_center' ).slideUp();
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-general_zoom, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lat, .js-wpv-shortcode-gui-attribute-wrapper-for-general_center_lon, .js-wpv-shortcode-gui-attribute-wrapper-for-single_center' ).slideDown();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-addon-maps-different-hover-image', function() {
		var thiz_val = $( '.js-wpv-addon-maps-different-hover-image:checked' ).val();
		if ( thiz_val == 'same' ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover ul:not(.js-wpv-dismiss)' ).slideUp();
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover ul:not(.js-wpv-dismiss)' ).slideDown();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_inherit .js-shortcode-gui-field', function() {
		var inherit_selected = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_inherit .js-shortcode-gui-field:checked' ).val();
		if ( inherit_selected == 'yes' ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon, .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' ).slideUp();
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon, .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' ).slideDown();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster .js-shortcode-gui-field', function() {
		var value_selected = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster .js-shortcode-gui-field:checked' ).val();
		if ( value_selected == 'on' ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster_click_zoom, .js-wpv-shortcode-gui-attribute-wrapper-for-cluster_extra_options' ).slideDown();
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster_click_zoom, .js-wpv-shortcode-gui-attribute-wrapper-for-cluster_extra_options' ).slideUp();
		}
	});
	
	$( document ).on( 'js_event_wpv_addon_maps_extra_dialog_opened', function( event, kind ) {
		if ( 
			'map_id' in self.cache 
			&& self.cache['map_id'] != '' 
		) {
			switch ( kind ) {
				case 'reload':
					$( '#wpv-addon-maps-reload' ).val( self.cache['map_id'] );
					break;
				case 'focus':
					$( '#wpv-addon-maps-focus-map' ).val( self.cache['map_id'] );
					break;
				case 'restore':
					$( '#wpv-addon-maps-restore' ).val( self.cache['map_id'] );
					break;
			}
		}
	});
	
	self.focus_computed_classname_filter = function( classname, style, attribute ) {
		$( '#js-wpv-addon-maps-focus-interaction .js-wpv-addon-maps-focus-interaction:checked' ).each( function() {
			var value = $( this ).val();
			switch ( value ) {
				case 'hover':
					classname += ' js-toolset-maps-hover-map-' + attribute.map + '-marker-' + attribute.marker;
					break;
				case 'click':
					classname += ' js-toolset-maps-open-infowindow-map-' + attribute.map + '-marker-' + attribute.marker;
					break;
			}
		});
		return classname;
	};
	
	self.extended_links_computed_classname = function( shortcode_name, classname, style, attribute ) {
		if ( shortcode_name in self.extended_links_computed_classname_filters ) {
			var filter_callback_func = self.extended_links_computed_classname_filters[ shortcode_name ];
			if ( typeof filter_callback_func == "function" ) {
				classname = filter_callback_func( classname, style, attribute );
			}
		}
		return classname;
	};
	
	self.init_filters = function() {
		WPViews.shortcodes_gui.shortcode_gui_computed_attribute_pairs_filters[ 'wpv-map-render' ] = self.map_computed_attribute_pairs_filter;
		WPViews.shortcodes_gui.shortcode_gui_computed_attribute_pairs_filters[ 'wpv-map-marker' ] = self.marker_computed_attribute_pairs_filter;
		self.extended_links_computed_classname_filters['focus'] = self.focus_computed_classname_filter;
	};
	
	self.map_computed_attribute_pairs_filter = function( shortcode_attribute_values ) {
		if ( $( '.js-wpv-shortcode-gui-attribute-wrapper-for-fitbounds .js-shortcode-gui-field:checked' ).val() == 'on' ) {
			shortcode_attribute_values['general_zoom'] = false;
			shortcode_attribute_values['general_center_lat'] = false;
			shortcode_attribute_values['general_center_lon'] = false;
			shortcode_attribute_values['single_center'] = false;
		}
		if ( 
			$( '.js-wpv-addon-maps-different-hover-image:checked', '.js-insert-wpv-map-render-dialog' ).length > 0 
			&& $( '.js-wpv-addon-maps-different-hover-image:checked', '.js-insert-wpv-map-render-dialog' ).val() == 'other'
		) {
			shortcode_attribute_values[ 'marker_icon_hover' ] = $( '.js-insert-wpv-map-render-dialog .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' )
				.find( '.js-shortcode-gui-field:checked' )
					.closest( 'li' )
						.find( '.js-wpv-icon-img' )
							.data( 'img' );
		} else {
			shortcode_attribute_values[ 'marker_icon_hover' ] = false;
		}
		if ( $( '.js-wpv-shortcode-gui-attribute-wrapper-for-cluster .js-shortcode-gui-field:checked' ).val() == 'on' ) {
			if ( $( '#js-wpv-cluster-min-size' ).val() != '' ) {
				shortcode_attribute_values['cluster_min_size'] = $( '#js-wpv-cluster-min-size' ).val();
			}
			if ( $( '#js-wpv-cluster-grid-size' ).val() != '' ) {
				shortcode_attribute_values['cluster_grid_size'] = $( '#js-wpv-cluster-grid-size' ).val();
			}
			if ( $( '#js-wpv-cluster-max-zoom' ).val() != '' ) {
				shortcode_attribute_values['cluster_max_zoom'] = $( '#js-wpv-cluster-max-zoom' ).val();
			}
		} else {
			shortcode_attribute_values['cluster_click_zoom'] = false;
		}
		return shortcode_attribute_values;
	};
	
	self.marker_computed_attribute_pairs_filter = function( shortcode_attribute_values ) {
		if ( 'marker_position' in shortcode_attribute_values ) {
			var marker_position_option = shortcode_attribute_values[ 'marker_position' ],
			marker_position_wrapper = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_position' );
			switch ( marker_position_option ) {
				case 'types_postmeta_field':
					shortcode_attribute_values[ 'marker_field' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option, marker_position_wrapper ).val();
					if ( $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val() != '' ) {
						shortcode_attribute_values[ 'id' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val();
					}
					break;
				case 'types_termmeta_field':
					shortcode_attribute_values[ 'marker_termmeta' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option, marker_position_wrapper ).val();
					if ( $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val() != '' ) {
						shortcode_attribute_values[ 'id' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val();
					}
					break;
				case 'types_usermeta_field':
					shortcode_attribute_values[ 'marker_usermeta' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option, marker_position_wrapper ).val();
					if ( $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val() != '' ) {
						shortcode_attribute_values[ 'id' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val();
					}
					break;
				case 'generic_field':
					var current_target_attribute = 'marker_field';
					switch ( self.current_content_type ) {
						case 'taxonomy':
							current_target_attribute = 'marker_termmeta';
							break;
						case 'users':
							current_target_attribute = 'marker_usermeta';
							break;
					}
					shortcode_attribute_values[ current_target_attribute ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option, marker_position_wrapper ).val();
					if ( $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val() != '' ) {
						shortcode_attribute_values[ 'id' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option + '-id', marker_position_wrapper ).val();
					}
					break;
				case 'address':
					shortcode_attribute_values[ 'address' ] = $( '.js-wpv-map-marker-marker_position-' + marker_position_option, marker_position_wrapper ).val();
					break;
				case 'latlon':
					shortcode_attribute_values[ 'lat' ] = $( '.js-wpv-map-marker-marker_position-latlon-lat', marker_position_wrapper ).val();
					shortcode_attribute_values[ 'lon' ] = $( '.js-wpv-map-marker-marker_position-latlon-lon', marker_position_wrapper ).val();
					break;
			}
			shortcode_attribute_values[ 'marker_position' ] = false;
		}
		if ( 'marker_inherit' in shortcode_attribute_values ) {
			var marker_inherit_option = shortcode_attribute_values[ 'marker_inherit' ];
			if ( marker_inherit_option == 'no' ) {
				shortcode_attribute_values[ 'marker_icon' ] = $( '.js-insert-wpv-map-marker-dialog .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon' )
					.find( '.js-shortcode-gui-field:checked' )
						.closest( 'li' )
							.find( '.js-wpv-icon-img' )
								.data( 'img' );
				if ( 
					$( '.js-wpv-addon-maps-different-hover-image:checked', '.js-insert-wpv-map-marker-dialog' ).length > 0 
					&& $( '.js-wpv-addon-maps-different-hover-image:checked', '.js-insert-wpv-map-marker-dialog' ).val() == 'other'
				) {
					shortcode_attribute_values[ 'marker_icon_hover' ] = $( '.js-insert-wpv-map-marker-dialog .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover' )
						.find( '.js-shortcode-gui-field:checked' )
							.closest( 'li' )
								.find( '.js-wpv-icon-img' )
									.data( 'img' );
				} else {
					shortcode_attribute_values[ 'marker_icon_hover' ] = false;
				}
			} else {
				shortcode_attribute_values[ 'marker_icon' ] = false;
				shortcode_attribute_values[ 'marker_icon_hover' ] = false;
			}
			shortcode_attribute_values[ 'marker_inherit' ] = false;
		}
		return shortcode_attribute_values;
	};
	
	$( document ).on( 'change', '.js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon .js-shortcode-gui-field, .js-wpv-shortcode-gui-attribute-wrapper-for-marker_icon_hover .js-shortcode-gui-field', function() {
			self.highlight_selected( $( this ).closest( '.js-wpv-shortcode-gui-attribute-wrapper' ) );
	});
	
	self.highlight_selected = function( container ) {
		$( 'label', container ).removeClass( 'selected' );
		$( '.js-shortcode-gui-field:checked', container )
			.closest( 'label' )
				.addClass( 'selected' );
	};
	
	self.init = function() {
		self.init_counters();
		self.init_dialogs();
		self.init_filters();
		
		self.is_views_editor = ( typeof WPViews.view_edit_screen != 'undefined' );
		self.is_wpa_editor = ( typeof WPViews.wpa_edit_screen != 'undefined' );
		if ( self.is_views_editor ) {
			self.current_content_type = $( '.js-wpv-query-type:checked' ).val();
			$( document ).on( 'change', '.js-wpv-query-type', function() {
				self.current_content_type = $( '.js-wpv-query-type:checked' ).val();
			});
		}
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
	WPViews.addon_maps_dialogs = new WPViews.AddonMapsDialogs( $ );
});