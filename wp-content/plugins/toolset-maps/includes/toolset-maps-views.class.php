<?php
/**
* Toolset Maps - Views integration
*
* @package ToolsetMaps
*
* @since 0.1
*
* @todo review https://github.com/wimagguc/jquery-latitude-longitude-picker-gmaps
* @todo review http://humaan.com/custom-html-markers-google-maps/
* @todo https://developers.google.com/maps/documentation/javascript/styling#styling_the_default_map
* @todo http://gis.stackexchange.com/a/15442
*/

class Toolset_Addon_Maps_Views {

	const option_name = 'wpv_addon_maps_options';

	static $is_wpv_embedded	= false;

	function __construct() {
		
		add_action( 'init',			array( $this, 'init' ) );
		add_action( 'admin_init',	array( $this, 'admin_init' ) );
		
		// Shortcodes in the Fields and Views dialog
		// Primary groups get registered at -10, then Google Maps, Types meta fields get there at -1
		add_action( 'init',			array( $this, 'register_shortcodes_dialog_groups' ), -5 );
		
		$this->enqueue_marker_clusterer_script = false;
		
	}

	function init() {
		
		self::$is_wpv_embedded = apply_filters( 'toolset_is_views_embedded_available', false );
		
		// Assets
		$this->register_assets();
		add_action( 'wp_enqueue_scripts',		array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts',	array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'wp_footer',				array( $this, 'render_dialogs' ), 50 );
		add_action( 'admin_footer',				array( $this, 'render_dialogs' ), 50 );

		// Shortcodes
		add_shortcode( 'wpv-map-render',	array( $this, 'wpv_map_render_shortcode' ) );
		add_shortcode( 'wpv-map-marker',	array( $this, 'wpv_map_marker_shortcode' ) );
		// Filters
		add_filter( 'the_content',								array( $this, 'wpv_map_run_shortcodes' ), 8 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed',	array( $this, 'wpv_map_run_shortcodes' ), 8 );
		add_filter( 'wpv-pre-do-shortcode',						array( $this, 'wpv_map_run_shortcodes' ), 8 );
		
		// AJAX callbacks for updating Toolset Maps settings
		add_action( 'wp_ajax_wpv_addon_maps_update_marker',				array( $this, 'wpv_addon_maps_update_marker' ) );
		add_action( 'wp_ajax_wpv_addon_maps_get_stored_data',			array( $this, 'wpv_addon_maps_get_stored_data' ) );
		add_action( 'wp_ajax_wpv_addon_maps_delete_stored_addresses',	array( $this, 'wpv_addon_maps_delete_stored_addresses' ) );
		// AJAX callback to update map and marker counters
		add_action( 'wp_ajax_wpv_toolset_maps_addon_update_counters',	array( $this, 'wpv_toolset_maps_addon_update_counters' ) );
		// Register in the Views shortcodes GUI API
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',				array( $this, 'wpv_map_shortcodes_register_data' ) );
		add_filter( 'editor_addon_items_wpv-views',						array( $this, 'wpv_map_shortcodes_to_gui' ), 11 );
		add_filter( 'wpv_filter_wpv_editor_addon_keep_default_registered_menus_for_taxonomy', array( $this, 'wpv_map_group_to_gui' ) );
		add_filter( 'wpv_filter_wpv_editor_addon_keep_default_registered_menus_for_users', array( $this, 'wpv_map_group_to_gui' ) );
		// Fallback callbacks for the suggest actions for postmeta, termmeta and usermeta field keys
		add_action( 'wp_ajax_wpv_suggest_wpv_post_field_name',				array( $this, 'wpv_suggest_wpv_post_field_name' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_wpv_post_field_name',		array( $this, 'wpv_suggest_wpv_post_field_name' ) );
		add_action( 'wp_ajax_wpv_suggest_wpv_taxonomy_field_name',			array( $this, 'wpv_suggest_wpv_taxonomy_field_name' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_wpv_taxonomy_field_name',	array( $this, 'wpv_suggest_wpv_taxonomy_field_name' ) );
		add_action( 'wp_ajax_wpv_suggest_wpv_user_field_name',				array( $this, 'wpv_suggest_wpv_user_field_name' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_wpv_user_field_name',		array( $this, 'wpv_suggest_wpv_user_field_name' ) );
		
		// Delete a registered marker icon when the image is deleted
		add_action( 'delete_attachment',	array( $this, 'wpv_addon_maps_delete_stored_icon_on_delete_attachment' ) );

	}

	function admin_init() {

		if ( ! self::$is_wpv_embedded ) {
			
			/**
			* Backwards compatibility
			*
			* Before Views 2.0, the Toolset Maps settings are integrated in the Views Settings page.
			*
			* From Views 2.0, the Toolset Maps settings are integrated in the Toolset Settings page entirely.
			* From Toolset Maps 1.2 the Google Maps API key settings are registered globally for all Toolset integrations.
			* The marker icons and stored data settings still belong to the Views integration.
			* The legacy map setting is registered in Views entirely.
			*/
			if ( version_compare( WPV_VERSION, '2.0', '<' ) ) {
				
				if ( class_exists( 'WPV_Settings_Screen' ) ) {
					$WPV_Settings_Screen = WPV_Settings_Screen::get_instance();
					remove_action( 'wpv_action_views_settings_features_section',		array( $WPV_Settings_Screen, 'wpv_map_plugin_options' ), 30 );
				} else {
					global $WPV_settings;
					remove_action( 'wpv_action_views_settings_features_section',		array( $WPV_settings, 'wpv_map_plugin_options' ), 30 );
				}

				add_filter( 'wpv_filter_wpv_settings_admin_tabs',						array( $this, 'register_settings_admin_tab' ) );
				add_action( 'wpv_action_views_settings_addon_maps_section',				array( $this, 'wpv_addon_maps_options' ), 30 );
				
				add_filter( 'toolset_filter_toolset_maps_settings_link',				array( $this, 'toolset_maps_settings_link' ) );
			
			} else {
				
				// Register the custom sections in the Map tab registered in Toolset_Addon_Maps_Common
				add_filter( 'toolset_filter_toolset_register_settings_maps_section',	array( $this, 'wpv_addon_maps_marker_options' ), 20 );
				add_filter( 'toolset_filter_toolset_register_settings_maps_section',	array( $this, 'wpv_addon_maps_cache_options' ), 30 );
				
			}

			//add_action( 'wpv_action_wpv_add_field_on_loop_wizard_for_posts', array( $this, 'wpv_map_shortcodes_to_loop_wizard' ), 10, 2 );
			
			// Helpers in the Filter editor for inserting callbacks
			add_filter( 'wpv_filter_wpv_dialog_frontend_events_tabs',					array( $this, 'wpv_addon_maps_frontend_events_tab' ), 10, 2 );
			add_action( 'wpv_filter_wpv_dialog_frontend_events_sections',				array( $this, 'wpv_addom_maps_frontend_events_section' ) );
			
			// Compaibility with Views parametric search: manage address fields as textfields
			add_filter( 'wpv_filter_wpv_paranetric_search_computed_field_properties',	array( $this, 'wpv_addon_maps_parametric_search_pretend_textfield_type' ) );
		}

		
	}

	function register_assets() {
		$toolset_maps_dialogs_dependencies = array( 'jquery', 'underscore', 'jquery-ui-dialog', 'jquery-ui-tabs', 'views-shortcodes-gui-script', 'jquery-geocomplete', 'icl_media-manager-js' );
		if ( is_admin() ) {
			// 'wp-color-picker'  is an asset only available for wp-admin
			// SO it becoms an optional dependency, and the script itself chcks its existence 
			// before initializing it on the map background selector.
			$toolset_maps_dialogs_dependencies[] = 'wp-color-picker';
		}
		wp_register_script( 'views-addon-maps-dialogs-script', TOOLSET_ADDON_MAPS_URL . '/resources/js/wpv_addon_maps_dialogs.js', $toolset_maps_dialogs_dependencies, TOOLSET_ADDON_MAPS_VERSION, true );
		$types_postmeta_fields = apply_filters( 'toolset_filter_toolset_maps_get_types_postmeta_fields', array() );
		$types_termmeta_fields = apply_filters( 'toolset_filter_toolset_maps_get_types_termmeta_fields', array() );
		$types_usermeta_fields = apply_filters( 'toolset_filter_toolset_maps_get_types_usermeta_fields', array() );
		$types_opt_array		= array( 
			'toolset_map_postmeta_fields'	=> $types_postmeta_fields,
			'toolset_map_termmeta_fields'	=> $types_termmeta_fields,
			'toolset_map_usermeta_fields'	=> $types_usermeta_fields
		);
		$saved_options			= apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		$wpv_addon_maps_dialogs_localization = array(
			'insert_link'			=> __( 'Insert link', 'toolset-maps' ),
			'close_dialog'			=> __( 'Close', 'toolset-maps' ),
			'latitude'				=> __( 'Latitude', 'toolset-maps' ),
			'longitude'				=> __( 'Longitude', 'toolset-maps' ),
			'types_postmeta_field_label'		=> __( 'Types custom field', 'toolset-maps' ),
			'types_termmeta_field_label'		=> __( 'Types termmeta field', 'toolset-maps' ),
			'types_usermeta_field_label'		=> __( 'Types usermeta field', 'toolset-maps' ),
			'types_field_options'	=> $types_opt_array,
			'generic_field_label'	=> array(
										'posts'		=> __( 'Field slug', 'toolset-maps' ),
										'taxonomy'	=> __( 'Taxonomy field slug', 'toolset-maps' ),
										'users'		=> __( 'User field slug', 'toolset-maps' ),
									),
			'id_attribute_label'	=> array(
										'posts'		=> __( 'Post ID', 'toolset-maps' ),
										'taxonomy'	=> __( 'Term ID', 'toolset-maps' ),
										'users'		=> __( 'User ID', 'toolset-maps' ),
									),
			'marker_source_desc'	=> array(
										'posts_attr_id'			=> __( 'You can use $parent for the native parent post, $posttype for the Types parent post, or a post ID. Defaults to the current post.', 'toolset-maps' ),
										'taxonomy_attr_id'		=> __( 'You can use a term ID.', 'toolset-maps' ),
										'taxonomy_attr_id_v'	=> __( 'You can use a term ID. Defaults to the current taxonomy term in the View loop.', 'toolset-maps' ),
										'users_attr_id'			=> __( 'You can use $current for the current user, $author for the author of the current post, or a user ID. Defaults to the current user.', 'toolset-maps' ),
										'users_attr_id_v'		=> __( 'You can use $current for the current user, $author for the author of the current post, or a user ID. Defaults to the current user in the View loop.', 'toolset-maps' ),
									),
			'add_marker_icon'		=> __( 'Add another marker icon', 'toolset-maps' ),
			'use_same_image'		=> __( 'Use the same marker icon', 'toolset-maps' ),
			'user_another_image'	=> __( 'Use a different marker icon', 'toolset-maps' ),
			'add_a_map_first'		=> __( 'Remember that you need to add a map first. Then, use its ID here to add markers into that map.', 'toolset-maps' ),
			'clusters'				=> array(
										'extra_options_title'		=> __( 'Conditions for replacing markers with clusters', 'toolset-maps' ),
										'extra_options_min_size'	=> __( 'Minimal number of markers in a cluster:', 'toolset-maps' ),
										'extra_options_grid_size'	=> __( 'Minimal distance, in pixels, between markers:', 'toolset-maps' ),
										'extra_options_max_zoom'	=> __( 'Maximal map zoom level that allows clustering:', 'toolset-maps' ),
										'extra_options_description'	=> __( 'You can leave all these options blank to use defaults.', 'toolset-maps' )
									),
			'counters'				=> array(
										'map'		=> $saved_options['map_counter'],
										'marker'	=> $saved_options['marker_counter']
									),
			'background_hex_format'	=> __( 'Use HEX format.', 'toolset-maps' ),
			'can_manage_options'	=> current_user_can( 'manage_options' ) ? 'yes' : 'no',
			'nonce'					=> wp_create_nonce( 'toolset_views_addon_maps_dialogs' ),
			'global_nonce'			=> wp_create_nonce( 'toolset_views_addon_maps_global' )
		);
		wp_localize_script( 'views-addon-maps-dialogs-script', 'wpv_addon_maps_dialogs_local', $wpv_addon_maps_dialogs_localization );
	}

	function enqueue_scripts( $hook ) {
		if (
			isset( $_GET['page'] )
			&& $_GET['page'] == 'views-settings'
			&& isset( $_GET['tab'] )
			&& $_GET['tab'] == 'addon_maps'
		) {
			// Legacy, needed before Views 2.0
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script( 'views-addon-maps-settings-script' );
		}
		$wpv_addon_maps_css = "img.js-wpv-addon-maps-custom-marker-item-img {"
		. "\n\tvertical-align: middle;"
		. "\n\tpadding: 5px 0;"
		. "\n\tmax-height: 45px;"
		. "\n}"
		. "\n#wpv-map-render-marker_icon li, "
		. "\n#wpv-map-render-marker_icon_hover li, "
		. "\n#wpv-map-marker-marker_icon li, "
		. "\n#wpv-map-marker-marker_icon_hover li {"
		. "\n\tdisplay: inline-block;"
		. "\n\twidth: 70px;"
		. "\n\tmargin-bottom: 0;"
		. "\n}"
		
		. "\n#wpv-map-render-marker_icon label, "
		. "\n#wpv-map-render-marker_icon_hover label, "
		. "\n#wpv-map-marker-marker_icon label, "
		. "\n#wpv-map-marker-marker_icon_hover label {"
		. "\n\tdisplay: block;"
		. "\n\tposition: relative;"
		. "\n\toverflow: hidden;"
		. "\n\theight: 82px;"
		. "\n\twidth: 58px;"
		. "\n\ttext-align: center;"
		. "\n\tborder-radius: 5px;"
		. "\n}"
		
		. "\n#wpv-map-render-marker_icon label.selected, "
		. "\n#wpv-map-render-marker_icon_hover label.selected, "
		. "\n#wpv-map-marker-marker_icon label.selected, "
		. "\n#wpv-map-marker-marker_icon_hover label.selected {"
		. "\n\tbox-shadow: 0 0 3px 0 #999 inset;"
		. "\n}"
		
		. "\n#wpv-map-render-marker_icon .js-shortcode-gui-field, "
		. "\n#wpv-map-render-marker_icon_hover .js-shortcode-gui-field, "
		. "\n#wpv-map-marker-marker_icon .js-shortcode-gui-field, "
		. "\n#wpv-map-marker-marker_icon_hover .js-shortcode-gui-field {"
		. "\n\tmargin: 60px 0 0;"
		. "\n}"
		
		. "\n#wpv-map-render-marker_icon .wpv-icon-img, "
		. "\n#wpv-map-render-marker_icon_hover .wpv-icon-img, "
		. "\n#wpv-map-marker-marker_icon .wpv-icon-img, "
		. "\n#wpv-map-marker-marker_icon_hover .wpv-icon-img {"
		. "\n\tposition: absolute;"
		. "\n\ttop: 8px;"
		. "\n\tleft: 5px;"
		. "\n\twidth: 48px;"
		. "\n\theight: 48px;"
		. "\n\tbackground-position: 50%;"
		. "\n\tbackground-repeat: no-repeat;"
		. "\n\tbackground-size: contain;"
		. "\n}"
		
		. "\n#wpv-maps-stored-data-table tr.deleted {"
		. "\n\tbackground-color: #b94a48;"
		. "\n}"
		
		. "\n#wpv-maps-stored-data-table td {"
		. "\n\tvertical-align: middle;"
		. "\n}"
		
		. "\n#wpv-maps-stored-data-table .wpv-map-delete-stored-address {"
		. "\n\tbackground: none;"
		. "\n\tcolor: #b94a48;"
		. "\n\tcursor: pointer;"
		. "\n\tpadding: 2px 4px;"
		. "\n\tmargin: 0 10px 0 20px;"
		. "\n\tborder-radius: 3px;"
		. "\n}"

		. "\n#wpv-maps-stored-data-table .wpv-map-delete-stored-address:hover { "
		. "\n\tbackground: #b94a48;"
		. "\n\tcolor: #eee;"
		. "\n}"
		
		. "\n#wpv-maps-stored-data-table .wpv-map-delete-stored-address.wpv-map-delete-stored-address-deleting,"
		. "\n#wpv-maps-stored-data-table .wpv-map-delete-stored-address.wpv-map-delete-stored-address-deleting:hover {"
		. "\n\tbackground: none;"
		. "\n\tcolor: #b94a48;"
		. "\n\tcursor: default;"
		. "\n}"
		
		. "";
		
		wp_add_inline_style( 'views-admin-css', $wpv_addon_maps_css );
		if ( wp_script_is( 'views-shortcodes-gui-script' ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script( 'views-addon-maps-dialogs-script' );
		}
	}

	function render_dialogs() {
		if ( wp_script_is( 'views-shortcodes-gui-script' ) ) {
			?>
			<div id="js-wpv-addon-maps-dialogs" style="display:none">

				<div id="js-wpv-addon-maps-dialog-reload" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
					<div class="wpv-dialog js-wpv-dialog" data-kind="reload">
						<div class="wpv-shortcode-gui-tabs js-wpv-addon-maps-reload-tabs">
							<ul>
								<li>
									<a href="#js-wpv-addon-maps-reload-settings"><?php echo esc_html( __( 'Options', 'toolset-maps' ) ); ?></a>
								</li>
							</ul>
							<div id="js-wpv-addon-maps-reload-settings" class="wpv-shortcode-gui-attribute-wrapper">
								<h3><?php _e( 'Map ID', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-reload"><?php _e( 'ID of the map to reload&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-reload" class="large-text js-wpv-addon-maps-links" value="" data-attribute="map" />
									</li>
								</ul>
								<h3><?php _e( 'Display', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-tag"><?php _e( 'Use this HTML element', 'toolset-maps' ); ?></label>
										<select id="wpv-addon-maps-link-tag" class="large-text js-wpv-addon-maps-tag" autocomplete="off">
											<option value="link"><?php _e( 'Link', 'toolset-maps' ); ?></option>
											<option value="button"><?php _e( 'Button', 'toolset-maps' ); ?></option>
										</select>
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-anchor"><?php _e( 'Use this text&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-anchor" class="large-text js-wpv-addon-maps-anchor" value="" autocomplete="off" />
									</li>
								</ul>
								<h3><?php _e( 'Extra classnames and styles', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-class"><?php _e( 'Classnames', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-class" class="large-text js-wpv-addon-maps-class" value="" autocomplete="off" />
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-style"><?php _e( 'Styles', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-style" class="large-text js-wpv-addon-maps-style" value="" autocomplete="off" />
									</li>
								</ul>
								<div class="tab-metadata">
									<p class="description">
										<?php _e( '&#42; required', 'toolset-maps' ); ?>
									</p>
								</div>
							</div>

						</div>
					</div>
				</div>

				<div id="js-wpv-addon-maps-dialog-focus" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
					<div class="wpv-dialog js-wpv-dialog" data-kind="focus">
						<div class="wpv-shortcode-gui-tabs js-wpv-addon-maps-focus-tabs">
							<ul>
								<li>
									<a href="#js-wpv-addon-maps-focus-settings"><?php echo esc_html( __( 'Options', 'toolset-maps' ) ); ?></a>
								</li>
								<li>
									<a href="#js-wpv-addon-maps-focus-interaction"><?php echo esc_html( __( 'Interaction', 'toolset-maps' ) ); ?></a>
								</li>
							</ul>
							<div id="js-wpv-addon-maps-focus-settings" class="wpv-shortcode-gui-attribute-wrapper">
								<h3><?php _e( 'Map and marker', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-focus-map"><?php _e( 'ID of the map&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-focus-map" class="large-text js-wpv-addon-maps-links" value="" data-attribute="map" />
									</li>
									<li>
										<label for="wpv-addon-maps-focus-marker"><?php _e( 'ID of the marker to zoom in&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-focus-marker" class="large-text js-wpv-addon-maps-links" value="" data-attribute="marker" autocomplete="off" />

									</li>
								</ul>
								<h3><?php _e( 'Display', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-tag"><?php _e( 'Use this HTML element', 'toolset-maps' ); ?></label>
										<select id="wpv-addon-maps-link-tag" class="large-text js-wpv-addon-maps-tag" autocomplete="off">
											<option value="link"><?php _e( 'Link', 'toolset-maps' ); ?></option>
											<option value="button"><?php _e( 'Button', 'toolset-maps' ); ?></option>
										</select>
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-anchor"><?php _e( 'Use this text&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-anchor" class="large-text js-wpv-addon-maps-anchor" value="" autocomplete="off" />
									</li>
								</ul>
								<h3><?php _e( 'Extra classnames and styles', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-class"><?php _e( 'Classnames', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-class" class="large-text js-wpv-addon-maps-class" value="" autocomplete="off" />
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-style"><?php _e( 'Styles', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-style" class="large-text js-wpv-addon-maps-style" value="" autocomplete="off" />
									</li>
								</ul>
								<div class="tab-metadata">
									<p class="description">
										<?php _e( '&#42; required', 'toolset-maps' ); ?>
									</p>
								</div>
							</div>
							<div id="js-wpv-addon-maps-focus-interaction" class="wpv-shortcode-gui-attribute-wrapper">
								<h3><?php _e( 'Mouse interaction', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-focus-hover">
											<input type="checkbox" id="wpv-addon-maps-focus-hover" class="js-wpv-addon-maps-focus-interaction" value="hover" autocomplete="off" />
											<?php _e( 'Hovering this item acts like hovering on the marker itself', 'toolset-maps' ); ?>
										</label>
									</li>
									<li>
										<label for="wpv-addon-maps-focus-click">
											<input type="checkbox" id="wpv-addon-maps-focus-click" class="js-wpv-addon-maps-focus-interaction" value="click" autocomplete="off" />
											<?php _e( 'Clicking this item will also open the marker popup', 'toolset-maps' ); ?>
										</label>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>

				<div id="js-wpv-addon-maps-dialog-restore" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
					<div class="wpv-dialog js-wpv-dialog" data-kind="restore">
						<div class="wpv-shortcode-gui-tabs js-wpv-addon-maps-restore-tabs">
							<ul>
								<li>
									<a href="#js-wpv-addon-maps-restore-settings"><?php echo esc_html( __( 'Options', 'toolset-maps' ) ); ?></a>
								</li>
							</ul>
							<div id="js-wpv-addon-maps-restore-settings" class="wpv-shortcode-gui-attribute-wrapper">
								<h3><?php _e( 'Map ID', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-restore"><?php _e( 'ID of the map to restore&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-restore" class="large-text js-wpv-addon-maps-links" value="" data-attribute="map" />
									</li>
								</ul>
								<h3><?php _e( 'Display', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-tag"><?php _e( 'Use this HTML element', 'toolset-maps' ); ?></label>
										<select id="wpv-addon-maps-link-tag" class="large-text js-wpv-addon-maps-tag" autocomplete="off">
											<option value="link"><?php _e( 'Link', 'toolset-maps' ); ?></option>
											<option value="button"><?php _e( 'Button', 'toolset-maps' ); ?></option>
										</select>
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-anchor"><?php _e( 'Use this text&#42;', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-anchor" class="large-text js-wpv-addon-maps-anchor" value="" autocomplete="off" />
									</li>
								</ul>
								<h3><?php _e( 'Extra classnames and styles', 'toolset-maps' ); ?></h3>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-class"><?php _e( 'Classnames', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-class" class="large-text js-wpv-addon-maps-class" value="" autocomplete="off" />
									</li>
								</ul>
								<ul>
									<li>
										<label for="wpv-addon-maps-link-style"><?php _e( 'Styles', 'toolset-maps' ); ?></label>
										<input type="text" id="wpv-addon-maps-link-style" class="large-text js-wpv-addon-maps-style" value="" autocomplete="off" />
									</li>
								</ul>
								<div class="tab-metadata">
									<p class="description">
										<?php _e( '&#42; required', 'toolset-maps' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
			<?php
		}
		
	}
	
	function wpv_addon_maps_delete_stored_icon_on_delete_attachment( $attachment_id ) {
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		if ( empty( $saved_options['marker_images'] ) ) {
			return;
		}
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( in_array( $attachment_url, $saved_options['marker_images'] ) ) {
			$updated_images = array_diff( $saved_options['marker_images'], array( $attachment_url ) );
			if ( is_array( $updated_images ) ) {
				$saved_options['marker_images'] = array_values( $updated_images );
			} else {
				$saved_options['marker_images'] = array();
			}
			do_action( 'toolset_filter_toolset_maps_update_options', $saved_options );
		}
	}

	function register_settings_admin_tab( $tabs ) {
		$tabs['addon_maps'] = array(
			'slug'	=> 'addon_maps',
			'title'	=> __( 'Toolset Maps', 'toolset-maps' )
		);
		return $tabs;
	}

	// Legacy, neded for Views before 2.0
	function wpv_addon_maps_options( $WPV_settings ) {
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		?>
		<div class="wpv-setting-container js-wpv-setting-container">
			<span style="display:inline-block;padding:0 8px 0 5px;margin-right:10px;background:#f1f1f1;border-radius:5px;box-shadow:inset 0 0 10px #c5c5c5;color:#F05A28;">
				<i class="icon-toolset-map-logo ont-color-orange ont-icon-36"></i>
			</span>
			<?php
				$analytics_strings = array(
					'utm_source'	=> 'toolsetmapsplugin',
					'utm_campaign'	=> 'toolsetmaps',
					'utm_medium'	=> 'views-integration-settings',
					'utm_term'		=> 'Check the documentation'
				);
				echo __( "<strong>Toolset Maps</strong> will include the Google Maps API on your site.", 'toolset-maps' )
					. WPV_MESSAGE_SPACE_CHAR
					. '<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings ) ) . '" target="_blank">'
					. __( 'Check the documentation', 'toolset-maps' )
					. '</a>.';
			?>
		</div>
		
		<div class="wpv-setting-container js-wpv-setting-container">
            <div class="wpv-settings-header">
                <h2><?php _e( 'Google Maps API key', 'toolset-maps' ); ?></h2>
            </div>
            <div class="wpv-setting">
				<?php
				$this->wpv_addon_maps_render_api_key_options( $saved_options );
				?>
            </div>
        </div>
		
		<div class="wpv-setting-container js-wpv-setting-container">
			<div class="wpv-settings-header">
                <h2><?php _e( 'Map markers', 'toolset-maps' ); ?></h2>
            </div>
            <div class="wpv-setting">
				<?php
				$this->wpv_addon_maps_render_marker_options( $saved_options );
				?>
            </div>
        </div>
		
		<div class="wpv-setting-container js-wpv-setting-container">
            <div class="wpv-settings-header">
                <h2><?php _e( 'Cached data', 'toolset-maps' ); ?></h2>
            </div>
            <div class="wpv-setting">
                <?php
				$this->wpv_addon_maps_render_cache_options( $saved_options );
				?>
            </div>
        </div>

		<div class="wpv-setting-container js-wpv-setting-container">
            <div class="wpv-settings-header">
                <h2><?php _e( 'Legacy mode', 'toolset-maps' ); ?></h2>
            </div>
            <div class="wpv-setting">
                <p>
                    <?php _e( "Enable the old Views Maps plugin if you were already displaying some Google Maps with it.", 'toolset-maps' ); ?>
                </p>
                <div class="js-wpv-map-plugin-form">
                    <p>
                        <label>
                            <input type="checkbox" name="wpv-map-plugin" class="js-wpv-map-plugin" value="1" <?php checked( $WPV_settings->wpv_map_plugin ); ?> autocomplete="off" />
                            <?php _e( "Enable the old Views Map Plugin", 'toolset-maps' ); ?>
                        </label>
                    </p>
                    <?php
                    wp_nonce_field( 'wpv_map_plugin_nonce', 'wpv_map_plugin_nonce' );
                    ?>
                </div>

                <p class="update-button-wrap">
                    <span class="js-wpv-messages"></span>
                    <button class="js-wpv-map-plugin-settings-save button-secondary" disabled="disabled">
                        <?php _e( 'Save', 'toolset-maps' ); ?>
                    </button>
                </p>
            </div>
        </div>
		
        <?php
	}
	
	function wpv_addon_maps_marker_options( $sections ) {
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		ob_start();
		$this->wpv_addon_maps_render_marker_options( $saved_options );
		$section_content = ob_get_clean();
			
		$sections['maps-markers'] = array(
			'slug'		=> 'maps-markers',
			'title'		=> __( 'Map markers', 'toolset-maps' ),
			'content'	=> $section_content
		);
		return $sections;
	}
	
	function wpv_addon_maps_render_marker_options( $saved_options ) {
		?>
		<p><?php _e( 'Add custom markers here, and use them later when inserting a map or an individual marker.', 'toolset-maps' ); ?></p>
		<div class="wpv-add-item-settings js-wpv-add-item-settings-wrapper">
			<ul class="wpv-taglike-list js-wpv-add-item-settings-list js-wpv-addon-maps-custom-marker-list">
				<?php
				if ( count( $saved_options['marker_images'] ) > 0 ) {
					foreach ( $saved_options['marker_images'] as $marker_img ) {
						?>
						<li class="js-wpv-addon-maps-custom-marker-item">
							<img src="<?php echo esc_attr( $marker_img ); ?>" class="js-wpv-addon-maps-custom-marker-item-img" />
							<i class="icon-remove-sign fa fa-times-circle js-wpv-addon-maps-custom-marker-delete"></i>
						</li>
						<?php
					}
				}
				?>
			</ul>
			<form class="js-wpv-add-item-settings-form js-wp-addon-maps-custom-marker-form-add">
				<input type="text" id="wpv-addpn-maps-custom-marker-newurl" class="hidden js-wpv-add-item-settings-form-newname js-wpv-addon-maps-custom-marker-newurl" autocomplete="off" />
				<button id="js-wpv-addon-maps-marker-add" class="botton button-secondary js-wpv-media-manager" data-content="wpv-addpn-maps-custom-marker-newurl"><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add a new marker', 'toolset-maps' ); ?></button>
			</form>
		</div>
		<?php
	}
	
	function wpv_addon_maps_render_api_key_options( $saved_options ) {
		?>
		<p>
			<?php _e( "Set your Google Maps API key.", 'toolset-maps' ); ?>
		</p>
		<div class="js-wpv-map-plugin-form">
			<p>
				<input id="js-wpv-map-api-key" type="text" name="wpv-map-api-key" class="regular-text js-wpv-map-api-key" value="<?php echo esc_attr( $saved_options['api_key'] ); ?>" autocomplete="off" placeholder="<?php echo esc_attr( __( 'API key', 'toolset-maps' ) ); ?>" />
			</p>
			<p>
				<?php
				echo sprintf( 
					__( 'A Google Maps API key is <strong>required</strong> to use Toolset Maps. You will need to create a <a href="%1$s" target="_blank">project in the Developers console</a>, then create an API key and enable it for some specific API services.', 'toolset-maps' ),
					'https://console.developers.google.com'
				);
				?>
			</p>
			<p>
				<?php 
				$analytics_strings = array(
					'utm_source'	=> 'toolsetmapsplugin',
					'utm_campaign'	=> 'toolsetmaps',
					'utm_medium'	=> 'views-integration-settings-for-api-key',
					'utm_term'		=> 'our documentation'
				);
				echo sprintf(
					__( 'You can find more information in %1$sour documentation%2$s.', 'toolset-maps' ),
					'<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings, 'anchor' => 'api-key' ), 'https://wp-types.com/documentation/user-guides/display-on-google-maps/' ) . '" target="_blank">',
					'</a>'
				);
				?>
			</p>
		</div>
		<p class="update-button-wrap">
			<span class="js-wpv-messages"></span>
			<button class="js-wpv-map-api-key-save button-secondary" disabled="disabled">
				<?php _e( 'Save', 'toolset-maps' ); ?>
			</button>
		</p>
		<?php
	}
	
	function wpv_addon_maps_cache_options( $sections ) {
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		ob_start();
		$this->wpv_addon_maps_render_cache_options( $saved_options );
		$section_content = ob_get_clean();
			
		$sections['maps-cache'] = array(
			'slug'		=> 'maps-cache',
			'title'		=> __( 'Cached data', 'toolset-maps' ),
			'content'	=> $section_content
		);
		return $sections;
	}
	
	function wpv_addon_maps_render_cache_options( $saved_options ) {
		?>
		<p>
			<?php _e( "We cache all the addresses used in your maps so we do not need to hit the Google Maps API every time. You can review and delete cached data here.", 'toolset-maps' ); ?>
		</p>
		<p>
			<?php _e( "Note that deleting cached data will not delete any field value, just the stored cache about it. Addresses stored as custom fields or user fields will generate their cache again when rendered inside a map.", 'toolset-maps' ); ?>
		</p>
		<p>
			<?php 
			$analytics_strings = array(
				'utm_source'	=> 'toolsetmapsplugin',
				'utm_campaign'	=> 'toolsetmaps',
				'utm_medium'	=> 'views-integration-settings-for-cached-data',
				'utm_term'		=> 'our documentation'
			);
			echo sprintf(
				__( 'You can find more information in %1$sour documentation%2$s.', 'toolset-maps' ),
				'<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings ), 'https://wp-types.com/documentation/user-guides/data-caching-for-google-maps-addresses/' ) . '" target="_blank">',
				'</a>'
			);
			?>
		</p>
		<p class="update-button-wrap js-wpv-map-load-stored-data-before">
			<button id="js-wpv-map-load-stored-data" class="button-secondary"><?php echo esc_html( 'Load stored data', 'toolset-maps' ); ?></button>
		</p>
		<div class="js-wpv-map-load-stored-data-after" style="display:none"></div>
		<?php
	}

	function wpv_addon_maps_update_marker() {
		wpv_ajax_authenticate( 'toolset_views_addon_maps_global', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );
		if (
			! isset( $_POST['csaction'] )
			|| ! isset( $_POST['cstarget'] )
		) {
			wp_send_json_error();
		}
		$action = ( in_array( $_POST['csaction'], array( 'add', 'delete' ) ) ) ? $_POST['csaction'] : '';
		$target = esc_url( $_POST['cstarget'] );
		if (
			empty( $action )
			|| empty( $target )
		) {
			wp_send_json_error();
		}
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		switch ( $action ) {
			case 'add':
				if ( ! in_array( $target, $saved_options['marker_images'] ) ) {
					$saved_options['marker_images'][] = $target;
				} else {
					wp_send_json_error();
				}
				break;
			case 'delete':
				$key = array_search( $target, $saved_options['marker_images'] );
				if ( $key !== false ) {
					unset( $saved_options['marker_images'][$key] );
				}
				break;
			default:
				wp_send_json_error();
				break;
		}
		do_action( 'toolset_filter_toolset_maps_update_options', $saved_options );
		wp_send_json_success();
	}
	
	function wpv_addon_maps_get_stored_data() {
		wpv_ajax_authenticate( 'toolset_views_addon_maps_global', array( 'parameter_source' => 'get', 'type_of_death' => 'data' ) );
		$coordinates_set = Toolset_Addon_Maps_Common::get_stored_coordinates();
		$alternate = 'alternate';
		ob_start();
		?>
		<table id="wpv-maps-stored-data-table" class="widefat">
			<thead>
				<tr>
					<th><?php echo esc_html( __( 'Address', 'toolset-maps' ) ); ?></th>
					<th><?php echo esc_html( __( 'Latitude', 'toolset-maps' ) ); ?></th>
					<th><?php echo esc_html( __( 'Longitude', 'toolset-maps' ) ); ?></th>
					<th></th>
				</tr>
			</thead>
				<tbody>
                <?php
				foreach ( $coordinates_set as $data_key => $data_set ) {
					// Note that the address_passed might not exist as it was added after the first beta
					// Because we were first storing the same Maps PI returned address
					// On hashes based on different addresses (because of lower/uppercases, commas, etc)
					// And that leads to different hashes pointing to the same addresses, hence different entries
					?>
					<tr class="<?php echo esc_attr( $alternate ); ?>">
						<td><?php echo isset( $data_set['address_passed'] ) ? esc_html( $data_set['address_passed'] ) : esc_html( $data_set['address'] ); ?></td>
						<td><?php echo esc_html( $data_set['lat'] ); ?></td>
						<td><?php echo esc_html( $data_set['lon'] ); ?></td>
						<td><i class="fa fa-times wpv-map-delete-stored-address js-wpv-map-delete-stored-address" data-key="<?php echo esc_attr( $data_key ); ?>"></i></td>
					</tr>
					<?php
					$alternate = ( $alternate == 'alternate' ) ? '' : 'alternate';
				}
				?>
			</tbody>
		</table>
		<p class="toolset-alert toolset-alert-info" style="line-height: 1.2em;">
		<?php echo __( 'Google only allows your site to convert 10 addresses to coordinates per second. If you delete too many addresses, the maps on your site may take several page refreshes to display correctly. Google doesn\'t like it when sites call the address resolution too often.', 'toolset-maps' ); ?>
		</p>
		<?php
		$table = ob_get_clean();
		$data = array(
			'table' => $table
		);
		wp_send_json_success( $data );
	}
	
	function wpv_addon_maps_delete_stored_addresses() {
		wpv_ajax_authenticate( 'toolset_views_addon_maps_global', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );
		$keys = ( isset( $_POST['keys'] ) && is_array( $_POST['keys']  ) ) ? $_POST['keys'] : array();
		$keys = array_map( 'sanitize_text_field', $keys );
		if ( ! empty( $keys ) ) {
			$coordinates_set = Toolset_Addon_Maps_Common::get_stored_coordinates();
			$save_after = false;
			foreach ( $keys as $key ) {
				if ( isset( $coordinates_set[ $key ] ) ) {
					unset( $coordinates_set[ $key ] );
					$save_after = true;
				}
			}
			if ( $save_after ) {
				Toolset_Addon_Maps_Common::save_stored_coordinates( $coordinates_set );
			}
		}
		wp_send_json_success();
	}
	
	function wpv_toolset_maps_addon_update_counters() {
		wpv_ajax_authenticate( 'toolset_views_addon_maps_dialogs', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );
		$update = false;
		$update_data = array();
		if ( 
			isset( $_POST['map_counter'] ) 
			&& intval( $_POST['map_counter'] > 0 )
		) {
			$update = true;
			$update_data['map_counter'] = intval( $_POST['map_counter'] );
		}
		if ( 
			isset( $_POST['marker_counter'] ) 
			&& intval( $_POST['marker_counter'] > 0 )
		) {
			$update = true;
			$update_data['marker_counter'] = intval( $_POST['marker_counter'] );
		}
		if ( $update ) {
			$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
			foreach ( $update_data as $key => $value ) {
				$saved_options[ $key ] = $value;
			}
			do_action( 'toolset_filter_toolset_maps_update_options', $saved_options );
		}
		wp_send_json_success();
	}

	function wpv_map_render_shortcode( $atts ) {
		extract(
			shortcode_atts(
				array(
					'map_id'				=> '',
					'map_width'				=> '100%',
					'map_height'			=> '250px',
					'general_zoom'			=> 5,
					'general_center_lat'	=> 0,
					'general_center_lon'	=> 0,
					'fitbounds'				=> 'on',
					'single_zoom'			=> 14,
					'single_center'			=> 'on',
					'map_type'				=> 'roadmap',
					'show_layer_interests'	=> 'false',
					'marker_icon'			=> '',
					'marker_icon_hover'		=> '',
					'draggable'				=> 'on',
					'scrollwheel'			=> 'on',
					'double_click_zoom'		=> 'on',
					'map_type_control'		=> 'on',
					'full_screen_control'	=> 'off',
					'zoom_control'			=> 'on',
					'street_view_control'	=> 'on',
					'background_color'		=> '',
					'cluster'				=> 'off',
					'cluster_grid_size'		=> 60,
					'cluster_max_zoom'		=> '',
					'cluster_click_zoom'	=> 'on',
					'cluster_min_size'		=> 2,
					'debug'					=> 'false'
				),
				$atts
			)
		);

		$return = '';

		if ( empty( $map_id ) ) {
			if ( $debug == 'true' ) {
				$logging_string = "####################<br />Map data<br />------------"
					. "<br />Error: empty map_id attribute"
					. "<br />####################<br />";
				$return .= $logging_string;
			}
			return $return;
		}

		if ( preg_match( "/^[0-9.]+$/", $map_width ) ) {
			$map_width .= 'px';
		}

		if ( preg_match( "/^[0-9.]+$/", $map_height ) ) {
			$map_height .= 'px';
		}
		
		$map_id = $this->wpv_get_unique_map_id( $map_id );
		
		if ( ! wp_script_is( 'views-addon-maps-script' ) ) {
			wp_enqueue_script( 'views-addon-maps-script' );
		}
		if ( $cluster == 'on' ) {
			$this->enqueue_marker_clusterer_script = true;
			if ( ! wp_script_is( 'marker-clusterer-script' ) ) {
				wp_enqueue_script( 'marker-clusterer-script' );
			}
		}
		
		$return = Toolset_Addon_Maps_Common::render_map(
			$map_id,
			array(
				'map_width'				=> $map_width,
				'map_height'			=> $map_height,
				'general_zoom'			=> $general_zoom,
				'general_center_lat'	=> $general_center_lat,
				'general_center_lon'	=> $general_center_lon,
				'fitbounds'				=> $fitbounds,
				'single_zoom'			=> $single_zoom,
				'single_center'			=> $single_center,
				'map_type'				=> $map_type,
				'show_layer_interests'	=> $show_layer_interests,
				'marker_icon'			=> $marker_icon,
				'marker_icon_hover'		=> $marker_icon_hover,
				'draggable'				=> $draggable,
				'scrollwheel'			=> $scrollwheel,
				'double_click_zoom'		=> $double_click_zoom,
				'map_type_control'		=> $map_type_control,
				'full_screen_control'	=> $full_screen_control,
				'zoom_control'			=> $zoom_control,
				'street_view_control'	=> $street_view_control,
				'background_color'		=> $background_color,
				'cluster'				=> $cluster,
				'cluster_grid_size'		=> $cluster_grid_size,
				'cluster_max_zoom'		=> $cluster_max_zoom,
				'cluster_click_zoom'	=> $cluster_click_zoom,
				'cluster_min_size'		=> $cluster_min_size
			)
		);
		
		if ( $debug == 'true' ) {
			$logging_string = "####################<br />Map data<br />------------"
				. "<br />Original attributes: "
				. "<code><pre>" . print_r( $atts, true ) . "</pre></code>"
				. "Used IDs: "
				. "<br />* map_id: " . $map_id
				. "<br />####################<br />";
			$return .= $logging_string;
		}
		
		return $return;
	}

	function wpv_map_marker_shortcode( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'map_id'			=> '',
					'marker_id'			=> '',
					'marker_title'		=> '',
					'marker_field'		=> '',
					'marker_termmeta'	=> '',
					'marker_usermeta'	=> '',
					'lat'				=> '',
					'lon'				=> '',
					'address'			=> '',
					'marker_icon'		=> '',
					'marker_icon_hover'	=> '',
					'id'				=> '',
					'debug'				=> 'false'
				),
				$atts
			)
		);

		$return = '';
		$markers_array = array();

		if ( empty( $map_id ) ) {
			if ( $debug == 'true' ) {
				$logging_string = "####################<br />Marker data<br />------------"
					. "<br />Error: empty map_id attribute"
					. "<br />####################<br />";
				$return .= $logging_string;
			}
			return $return;
		}
		
		// First, the case where lat and lon attributes were passed
		// Then, a custom address was used or a custom field was selected: get the address data and take care of multiple values
		if ( 
			$lat != ''
			&& $lon != ''
		) {
			$markers_array[] = array(
				'lat'	=> $lat,
				'lon'	=> $lon
			);
		} else if ( 
			$address != '' 
			|| $marker_field != '' 
			|| $marker_usermeta != '' 
			|| $marker_termmeta != ''
		) {
			$addresss_array = array();
			if ( $address != '' ) {
				$addresss_array[] = $address;
			} else if ( $marker_field != '' ) {
				$post_id_atts = new WPV_wpcf_switch_post_from_attr_id( $atts );
				global $post;
				if ( ! empty( $post ) ) {
					$marker_id = ( empty( $marker_id ) ) ? $post->ID : $marker_id;
					$marker_title = ( empty( $marker_title ) ) ? $post->post_title : $marker_title;
					$addresss_array = get_post_meta( $post->ID, $marker_field );
				}
			} else if ( $marker_termmeta != '' ) {
				$marker_term = false;
				if ( empty( $id ) ) {
					global $WP_Views;
					if ( isset( $WP_Views->taxonomy_data['term'] ) ) {
						$marker_term = $WP_Views->taxonomy_data['term'];
					}
				} else {
					$marker_term = get_term( $id );
				}
				if ( $marker_term ) {
					$marker_id = ( empty( $marker_id ) ) ? $marker_term->term_id : $marker_id;
					$marker_title = ( empty( $marker_title ) ) ? $marker_term->name : $marker_title;
					$addresss_array = get_term_meta( $marker_term->term_id, $marker_termmeta );
				}
			} else if ( $marker_usermeta != '' ) {
				$marker_user = false;
				if ( empty( $id ) ) {
					global $WP_Views;
					if ( isset( $WP_Views->users_data['term'] ) ) {
						$marker_user = $WP_Views->users_data['term'];
					} else {
						if ( is_user_logged_in() ) {
							global $current_user;
							$marker_user = $current_user;
						}
					}
				} else {
					switch ( $id ) {
						case '$author':
							global $post;
							$marker_user = get_user_by( 'id', $post->post_author );
							break;
						case '$current':
							if ( is_user_logged_in() ) {
								global $current_user;
								$marker_user = $current_user;
							}
							break;
						default:
							$marker_user = get_user_by( 'id', $id );
							break;
					}
				}
				if ( $marker_user ) {
					$marker_id = ( empty( $marker_id ) ) ? $marker_user->ID : $marker_id;
					$marker_title = ( empty( $marker_title ) ) ? $marker_user->user_nicename : $marker_title;
					$addresss_array = get_user_meta( $marker_user->ID, $marker_usermeta );
				}
			}
			foreach ( $addresss_array as $addresss_candidate ) {
				$addresss_candidate_data = Toolset_Addon_Maps_Common::get_coordinates( $addresss_candidate );
				if ( is_array( $addresss_candidate_data ) ) {
					$markers_array[] = array(
						'lat'	=> $addresss_candidate_data['lat'],
						'lon'	=> $addresss_candidate_data['lon']
					);
				} else {
					if ( $debug == 'true' ) {
						$logging_string = "####################<br />Marker data<br />------------"
							. "<br />Marker address: " . $addresss_candidate
							. "<br />Error connecting the Google Maps API: " . $addresss_candidate_data
							. "<br />####################<br />";
						$return .= $logging_string;
					}
				}
			}
		} else {
			if ( $debug == 'true' ) {
				$logging_string = "####################<br />Marker data<br />------------"
					. "<br />Marker source unknown"
					. "<br />####################<br />";
				$return .= $logging_string;
			}
		}
		
		foreach ( $markers_array as $marker_candidate ) {
			$marker_id_corrected = $this->wpv_get_unique_marker_id( $map_id, $marker_id );
			$return .= Toolset_Addon_Maps_Common::render_marker(
				$map_id,
				array(
					'id'			=> $marker_id_corrected,
					'title'			=> $marker_title,
					'lat'			=> $marker_candidate['lat'],
					'lon'			=> $marker_candidate['lon'],
					'icon'			=> $marker_icon,
					'icon_hover'	=> $marker_icon_hover,
				),
				$content
			);
			if ( $debug == 'true' ) {
				$used_atts = array(
					'map_id' => $map_id,
					'marker_id' => $marker_id_corrected,
					'marker_lat' => $marker_candidate['lat'],
					'marker_lon' => $marker_candidate['lon'],
				);
				$logging_string = "####################<br />Marker data<br />------------"
					. "<br />Original attributes: "
					. "<code><pre>" . print_r( $atts, true ) . "</pre></code>"
					. "Used attributes: "
					. "<code><pre>" . print_r( $used_atts, true ) . "</pre></code>"
					. "####################<br />";
				$return .= $logging_string;
			}
		}
		
		return $return;
	}
	
	function wpv_get_unique_map_id( $map_id ) {
		$used_map_ids = Toolset_Addon_Maps_Common::$used_map_ids;
		$map_id_corrected = $map_id;
		$loop_counter = 0;
		while ( in_array( $map_id_corrected, $used_map_ids ) ) {
			$loop_counter = $loop_counter + 1;
			$map_id_corrected = $map_id . '-' . $loop_counter;
		}
		return $map_id_corrected;
	}
	
	function wpv_get_unique_marker_id( $map_id, $marker_id ) {
		$used_marker_ids = Toolset_Addon_Maps_Common::$used_marker_ids;
		if ( ! isset( $used_marker_ids[ $map_id ] ) ) {
			$used_marker_ids[ $map_id ] = array();
		}
		$marker_id_corrected = $marker_id;
		$loop_counter = 0;
		while ( in_array( $marker_id_corrected, $used_marker_ids[ $map_id ] ) ) {
			$loop_counter = $loop_counter + 1;
			$marker_id_corrected = $marker_id . '-' . $loop_counter;
		}
		return $marker_id_corrected;
	}

	function wpv_map_run_shortcodes( $content ) {
		if ( strpos( $content, '[wpv-map' ) !== false ) {
			global $shortcode_tags;
			// Back up current registered shortcodes and clear them all out
			$orig_shortcode_tags = $shortcode_tags;
			remove_all_shortcodes();
			add_shortcode( 'wpv-map-render', array( $this, 'wpv_map_render_shortcode' ) );
			add_shortcode( 'wpv-map-marker', array( $this, 'wpv_map_marker_shortcode' ) );
			$content = do_shortcode( $content );
			$shortcode_tags = $orig_shortcode_tags;
		}
		return $content;
	}

	function wpv_map_shortcodes_register_data( $views_shortcodes ) {
		$views_shortcodes['wpv-map-render'] = array(
			'callback' => array( $this, 'wpv_shortcodes_get_wpv_map_render_data' )
		);
		$views_shortcodes['wpv-map-marker'] = array(
			'callback' => array( $this, 'wpv_shortcodes_get_wpv_map_marker_data' )
		);
		return $views_shortcodes;
	}

	function get_marker_options() {
		$saved_options = apply_filters( 'toolset_filter_toolset_maps_get_options', array() );
		$marker_options = array(
			'' => '<span class="wpv-icon-img js-wpv-icon-img" data-img="' . TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/default.png"  style="background-image:url(' . TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/default.png' . ');"></span>'
		);
		$marker_builtin = array(
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Buildings.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Home.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Hospital-1.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Hospital-2.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/School-1.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/School-2.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/School-3.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Shop-1.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Shop-2.png',
			TOOLSET_ADDON_MAPS_URL . '/resources/images/markers/Shop-3.png'
		);
		foreach ( $marker_builtin as $bimg ) {
			$marker_options[$bimg] = '<span class="wpv-icon-img js-wpv-icon-img" data-img="' . esc_attr( $bimg ) . '" style="background-image:url(' . esc_attr( $bimg ) . ');"></span>';
		}
		foreach ( $saved_options['marker_images'] as $img ) {
			$marker_options[$img] = '<span class="wpv-icon-img js-wpv-icon-img" data-img="' . esc_attr( $img ) . '" style="background-image:url(' . esc_attr( $img ) . ');"></span>';
		}
		return $marker_options;
	}
	
	function get_missing_api_key_warning() {
		$return = '';
		$return .= '<div class="toolset-alert toolset-alert-wrning">';
		$return .= '<p>';
		$return .= __( 'A Google Maps API key is <strong>required</strong> to use Toolset Maps.', 'toolset-maps' );
		$return .= '</p>';
		$return .= '<p>';
		$analytics_strings = array(
			'utm_source'	=> 'toolsetmapsplugin',
			'utm_campaign'	=> 'toolsetmaps',
			'utm_medium'	=> 'views-integration-settings-for-api-key',
			'utm_term'		=> 'our documentation'
		);
		$return .= sprintf(
			__( 'You can find more information in %1$sour documentation%2$s.', 'toolset-maps' ),
			'<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings, 'anchor' => 'api-key' ), 'https://wp-types.com/documentation/user-guides/display-on-google-maps/' ) . '" target="_blank">',
			'</a>'
		);
		$return .= '</p>';
		$return .= '</div>';
		return $return;
	}

	function wpv_shortcodes_get_wpv_map_render_data() {
		$can_manage_options = current_user_can( 'manage_options' );
		$maps_api_key = apply_filters( 'toolset_filter_toolset_maps_get_api_key', '' );
		
		$data = array(
			'name'			=> __( 'Map', 'toolset-maps' ),
			'label'			=> __( 'Map', 'toolset-maps' ),
			'attributes'	=> array()
		);
		
		if ( empty( $maps_api_key ) ) {
			$data['attributes']['map-api-key'] = array(
				'label'			=> __('Google Maps API', 'toolset-maps'),
				'header'		=> __('Missing Google Maps API key', 'toolset-maps'),
				'fields'		=> array(
					'missing_api_key' => array(
						'label'			=> __( 'Missing Google Maps API key', 'toolset-maps'),
						'type'			=> 'callback',
						'callback'		=> array( $this, 'get_missing_api_key_warning' )
					),
				)
			);
			return $data;
		}
		
		$data['attributes']['map-options'] = array(
			'label'			=> __('Map', 'toolset-maps'),
			'header'		=> __('Map', 'toolset-maps'),
			'fields'		=> array(
				'map_id' => array(
					'label'			=> __( 'Map ID', 'toolset-maps'),
					'type'			=> 'text',
					'default'		=> '',
					'required'		=> true,
					'description'	=> __( 'This is the map unique identifier, used to also add markers to this map.', 'toolset-maps' )
				),
				'map_width' => array(
					'label'			=> __( 'Map width', 'toolset-maps'),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder' 	=> '100%',
					'description' 	=> __('You can use percentages or units. Raw numbers default to pixels.','toolset-maps'),
				),
				'map_height' => array(
					'label'			=> __( 'Map height', 'toolset-maps'),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '250px',
					'description'	=> __('You can use percentages or units. Raw numbers default to pixels.','toolset-maps'),
				),
			),
		);
		
		$data['attributes']['zoom-options'] = array(
			'label'			=> __('Map zoom and center', 'toolset-maps'),
			'header'		=> __('Map zoom and center', 'toolset-maps'),
			'description'	=> __( 'The zoom levels can take a value from 0 up to a number that depends on the displayed location. It should be safe to use any number below 18. Zoom and center options can be combined in several ways.', 'toolset-maps' ),
			'fields'		=> array(
				'fitbounds' => array(
					'label'			=> __( 'Adjust automatically', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Adjust automatically to show all markers at once', 'toolset-maps'),
						'off'		=> __('Set zoom center and center manually', 'toolset-maps'),
					),
					'default'		=> 'on',
					'description'	=> __( 'Whether to set the highest zoom level and the best map center that can show all markers at once.', 'toolset-maps' ),
				),
				'general_zoom' => array(
					'label'			=> __( 'Use this zoom level when there is more than one marker', 'toolset-maps'),
					'type'			=> 'number',
					'default'		=> '5',
				),
				'single_zoom' => array(
					'label'			=> __( 'Use this zoom level when there is only one marker', 'toolset-maps'),
					'type'			=> 'number',
					'default'		=> '14',
				),
				'general_center_lat' => array(
					'label'			=> __( 'Coordinates for the map center', 'toolset-maps'),
					'type'			=> 'text',
					'default'		=> '0',
					'description'	=> __('Latitude for the center of the map.','toolset-maps'),
				),
				'general_center_lon' => array(
					'label'			=> __( 'General centering - longitude', 'toolset-maps'),
					'type'			=> 'text',
					'default'		=> '0',
					'description'	=> __('Longitude for the center of the map.','toolset-maps'),
				),
				'single_center' => array(
					'label'			=> __( 'Map center with just one marker', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Center the map in the marker when there is only one', 'toolset-maps'),
						'off'		=> __('Force the map center setting even with just one marker', 'toolset-maps'),
					),
					'default'		=> 'on',
					'description'	=> __( 'Will override the center coordinates set above.', 'toolset-maps' )
				),
			),
		);
		
		$data['attributes']['marker-clustering'] = array(
			'label'			=> __('Marker clustering', 'toolset-maps'),
			'header'		=> __('Marker clustering', 'toolset-maps'),
			'fields'		=> array(
				'cluster' => array(
					'label'			=> __( 'Cluster markers', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('When several markers are close, display as one \'cluster\' marker', 'toolset-maps'),
						'off'		=> __('Always show each marker separatedly', 'toolset-maps'),
					),
					'default'		=> 'off',
				),
				'cluster_click_zoom' => array(
					'label'			=> __( 'Clicking on a cluster icon', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Zoom the map when clicking on a cluster icon', 'toolset-maps'),
						'off'		=> __('Do nothing', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
			),
		);
		
		$data['attributes']['extra-options'] = array(
			'label'			=> __('Map interaction', 'toolset-maps'),
			'header'		=> __('Map interaction', 'toolset-maps'),
			'fields'		=> array(
				'draggable' => array(
					'label'			=> __( 'Dragging a map', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Move inside the map by dragging it', 'toolset-maps'),
						'off'		=> __('Do nothing', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
				'scrollwheel' => array(
					'label'			=> __( 'Scroll inside a map', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Scroll inside the map to zoom it', 'toolset-maps'),
						'off'		=> __('Do nothing', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
				'double_click_zoom' => array(
					'label'			=> __( 'Double click on on a map', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Double click on the map to zoom it', 'toolset-maps'),
						'off'		=> __('Do nothing', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
			),
		);
		
		$data['attributes']['control-options'] = array(
			'label'			=> __('Map controls', 'toolset-maps'),
			'header'		=> __('Map controls', 'toolset-maps'),
			'fields'		=> array(
				'map_type_control' => array(
					'label'			=> __( 'Map type control', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Display the map type control to switch between roadmap, satellite or terrain views', 'toolset-maps'),
						'off'		=> __('Do not show the map type control', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
				/*
				'full_screen_control' => array(
					'label'			=> __( 'Full screen control', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Display a full screen control', 'toolset-maps'),
						'off'		=> __('Do not show a full screen control', 'toolset-maps'),
					),
					'default'		=> 'off',
				),
				*/
				'zoom_control' => array(
					'label'			=> __( 'Zoom controls', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Display zoom controls to zoom in and out', 'toolset-maps'),
						'off'		=> __('Do not show zoom controls', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
				'street_view_control' => array(
					'label'			=> __( 'Street view control', 'toolset-maps'),
					'type'			=> 'radio',
					'options'		=> array(
						'on'		=> __('Display a street view control', 'toolset-maps'),
						'off'		=> __('Do not show a street view control', 'toolset-maps'),
					),
					'default'		=> 'on',
				),
			),
		);
		
		$marker_options = $this->get_marker_options();
		$styling_fields = array();
		if ( 
			count( $marker_options ) > 1 
			|| $can_manage_options
		) {
			$styling_fields['marker_icon'] = array(
				'label'		=> __( 'Icon for markers in this map', 'toolset-maps'),
				'type'		=> 'radiohtml',
				'options'	=> $marker_options,
				'default'	=> '',
			);
			$styling_fields['marker_icon_hover'] = array(
				'label'		=> __( 'Icon when hovering markers on this map', 'toolset-maps'),
				'type'		=> 'radiohtml',
				'options'	=> $marker_options,
				'default'	=> '',
			);
		}
		$styling_fields['background_color'] = array(
			'label'			=> __( 'Background color of this map', 'toolset-maps'),
			'type'			=> 'text',
			'default'		=> '#e5e3df',
			'description'	=> __('Will only be visible when the map needs to redraw a section.','toolset-maps'),
		);

		$data['attributes']['style-options'] = array(
			'label'		=> __('Map styles', 'toolset-maps'),
			'header'	=> __('Map styles', 'toolset-maps'),
			'fields'	=> $styling_fields,
		);
		if ( $can_manage_options ) {
			$analytics_strings = array(
				'utm_source'	=> 'toolsetmapsplugin',
				'utm_campaign'	=> 'toolsetmaps',
				'utm_medium'	=> 'map-shortcode-dialog',
				'utm_term'		=> 'Learn about using custom markers and backgrounds'
			);
			$data['attributes']['style-options']['documentation'] = sprintf(
				__( '%1$sLearn about using custom markers and backgrounds »%2$s', 'toolset-maps' ),
				'<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings, 'anchor' => 'marker-icon' ) ) . '" target="_blank" title="' . esc_attr( __( 'Learn about using custom markers and backgrounds', 'toolset-maps' ) ) . '">',
				'</a>'
			);
		}
		return $data;
	}

	function wpv_shortcodes_get_wpv_map_marker_data() {
		$can_manage_options = current_user_can( 'manage_options' );
		$maps_api_key = apply_filters( 'toolset_filter_toolset_maps_get_api_key', '' );
		
		$data = array(
			'name'			=> __( 'Marker', 'toolset-maps' ),
			'label'			=> __( 'Marker', 'toolset-maps' ),
			'attributes'	=> array()
		);
		
		if ( empty( $maps_api_key ) ) {
			$data['attributes']['map-api-key'] = array(
				'label'			=> __('Google Maps API', 'toolset-maps'),
				'header'		=> __('Missing Google Maps API key', 'toolset-maps'),
				'fields'		=> array(
					'missing_api_key' => array(
						'label'			=> __( 'Missing Google Maps API key', 'toolset-maps'),
						'type'			=> 'callback',
						'callback'		=> array( $this, 'get_missing_api_key_warning' )
					),
				)
			);
			return $data;
		}
		
		$analytics_strings = array(
			'utm_source'	=> 'toolsetmapsplugin',
			'utm_campaign'	=> 'toolsetmaps',
			'utm_medium'	=> 'marker-shortcode-dialog',
			'utm_term'		=> 'Learn about marker popups'
		);
		
		$data['attributes']['marker-options'] = array(
			'label' => __('Marker', 'toolset-maps'),
			'header' => __('Marker', 'toolset-maps'),
			'fields' => array(
				'map_id' => array(
					'label' => __( 'Map ID', 'toolset-maps'),
					'type' => 'text',
					'default' => '',
					'required' => true,
					'description'	=> __( 'This is the unique identifier for the map that this marker belongs to.', 'toolset-maps' )
				),
				'marker_id' => array(
					'label' => __( 'Marker ID', 'toolset-maps'),
					'type' => 'text',
					'default' => '',
					'required' => true,
					'description'	=> __( 'This is the marker unique identifier.', 'toolset-maps' )
				),
				'marker_position' => array(
					'label' => __( 'Marker address comes from', 'toolset-maps' ),
					'type' => 'radiohtml',
					'options' => array(
						'types_postmeta_field'	=> __( 'Google Maps post field: ', 'toolset-maps' ),
						'types_termmeta_field'	=> __( 'Google Maps taxonomy field: ', 'toolset-maps' ),
						'types_usermeta_field'	=> __( 'Google Maps user field: ', 'toolset-maps' ),
						'generic_field'	=> __( 'Another custom field', 'toolset-maps' ),
						'address'		=> __( 'An address', 'toolset-maps' ),
						'latlon'		=> __( 'A pair of latitude and longitude coordinates', 'toolset-maps' )
					),
				),
			)
		);
		
		$data['attributes']['data-options'] = array(
			'label' => __( 'Marker data', 'toolset-maps' ),
			'header' => __( 'Marker data', 'toolset-maps' ),
			'documentation' => '<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings, 'anchor' => 'marker-title-and-popup' ) ) . '" target="_blank" title="' . esc_attr( __( 'Learn about marker popups', 'toolset-maps' ) ) . '">' . esc_html( __( 'Learn how to display rich content in the marker popups »', 'toolset-maps' ) ) . '</a>',
			'fields' => array(
				'marker_title' => array(
					'label' => __( 'Text to display when hovering over the marker', 'toolset-maps'),
					'type' => 'text',
					'default' => '',
				),
			),
			'content' => array(
				'label' => __( 'Popup content', 'toolset-maps' ),
				'type'	=> 'textarea',
				'description' => __( 'This will be displayed as a popup when someone clicks on the marker. You can add HTML and shortcodes here.', 'toolset-maps' ),
			)
		);
		
		$marker_options = $this->get_marker_options();
		if ( 
			count( $marker_options ) > 1 
			|| $can_manage_options
		) {
			$data['attributes']['icon-options'] = array(
				'label' => __('Marker icons', 'toolset-maps'),
				'header' => __('Marker icons', 'toolset-maps'),
				'fields' => array(
					'marker_inherit' => array(
						'label'		=> __( 'Use the icons settings from the map', 'toolset-maps' ),
						'type'		=> 'radio',
						'options'	=> array(
											'yes'	=> __( 'Yes', 'toolset-maps' ),
											'no'	=> __( 'No, use other icons', 'toolset-maps' )
										),
						'default_force'	=> 'yes'
					),
					'marker_icon' => array(
						'label' => __( 'Icon for this marker', 'toolset-maps'),
						'type' => 'radiohtml',
						'options' => $marker_options,
						'default' => '',
					),
					'marker_icon_hover' => array(
						'label' => __( 'Icon when hovering this marker', 'toolset-maps'),
						'type' => 'radiohtml',
						'options' => $marker_options,
						'default' => '',
					),
				),
			);
			$analytics_strings = array(
				'utm_source'	=> 'toolsetmapsplugin',
				'utm_campaign'	=> 'toolsetmaps',
				'utm_medium'	=> 'marker-shortcode-dialog',
				'utm_term'		=> 'Learn about using custom markers'
			);
			$data['attributes']['icon-options']['documentation'] = sprintf(
				__( '%1$sLearn about using custom markers »%2$s', 'toolset-maps' ),
				'<a href="' . Toolset_Addon_Maps_Common::get_documentation_promotional_link( array( 'query' => $analytics_strings, 'anchor' => 'marker-icon' ) ) . '" target="_blank" title="' . esc_attr( __( 'Learn about using custom markers', 'toolset-maps' ) ) . '">',
				'</a>'
			);
		}
		return $data;
	}
	function register_shortcodes_dialog_groups() {
		
		$group_id	= 'toolset-maps';
		$group_data	= array(
			'name'		=> __( 'Google Maps', 'toolset-maps' ),
			'fields'	=> array()
		);
		
		$nonce = wp_create_nonce('wpv_editor_callback');
		
		$map_shortcodes = array(
			'wpv-map-render'			=> __( 'Map', 'toolset-maps' ),
			'wpv-map-marker'			=> __( 'Marker', 'toolset-maps' )
		);
		
		foreach ( $map_shortcodes as $map_shortcode_slug => $map_shortcode_title ) {
			$group_data['fields'][ $map_shortcode_slug ] = array(
				'name'		=> $map_shortcode_title,
				'shortcode'	=> $map_shortcode_slug,
				'callback'	=> "WPViews.shortcodes_gui.wpv_insert_popup('" . esc_js( $map_shortcode_slug ) . "', '" . esc_js( $map_shortcode_title ) . "', {}, '" . $nonce . "', this )"
			);
		}
		
		$group_data['fields']['reload'] = array(
			'name'		=> __( '"Reload" button', 'toolset-maps' ),
			'shortcode'	=> '',
			'callback'	=> "WPViews.addon_maps_dialogs.wpv_open_dialog('reload', '" . esc_js( __( 'Map reload', 'toolset-maps' ) ) . "', {}, '" . $nonce . "', this )"
		);
		$group_data['fields']['focus'] = array(
			'name'		=> __( '"Focus on marker" button', 'toolset-maps' ),
			'shortcode'	=> '',
			'callback'	=> "WPViews.addon_maps_dialogs.wpv_open_dialog('focus', '" . esc_js( __( 'Map focus on marker', 'toolset-maps' ) ) . "', {}, '" . $nonce . "', this )"
		);
		$group_data['fields']['restore'] = array(
			'name'		=> __( '"Zoom out" button', 'toolset-maps' ),
			'shortcode'	=> '',
			'callback'	=> "WPViews.addon_maps_dialogs.wpv_open_dialog('restore', '" . esc_js( __( 'Map restore zoom', 'toolset-maps' ) ) . "', {}, '" . $nonce . "', this )"
		);
		
		do_action( 'wpv_action_wpv_register_dialog_group', $group_id, $group_data );
		
	}

	// Deprecated since Views 2.3.0
	function wpv_map_shortcodes_to_gui( $items ) {
		$nonce = wp_create_nonce('wpv_editor_callback');

		$items[] = array(
			__( 'Map', 'toolset-maps' ),
			'wpv-map-render',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.shortcodes_gui.wpv_insert_popup('wpv-map-render', '" . __( 'Map render', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);

		$items[] = array(
			__( 'Marker', 'toolset-maps' ),
			'wpv-map-marker',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.shortcodes_gui.wpv_insert_popup('wpv-map-marker', '" . __( 'Map marker', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);

		$items[] = array(
			__( '"Reload" button', 'toolset-maps' ),
			'wpv-map-reload',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.addon_maps_dialogs.wpv_open_dialog('reload', '" . __( 'Map reload', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);

		$items[] = array(
			__( '"Focus on marker" button', 'toolset-maps' ),
			'wpv-map-zoom-marker',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.addon_maps_dialogs.wpv_open_dialog('focus', '" . __( 'Map focus on marker', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);

		$items[] = array(
			__( '"Zoom out" button', 'toolset-maps' ),
			'wpv-map-zoom-out',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.addon_maps_dialogs.wpv_open_dialog('restore', '" . __( 'Map restore zoom', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);

		return $items;
	}
	
	// Deprecated since Views 2.3.0
	function wpv_map_group_to_gui( $groups ) {
		$groups[ __( 'Google Maps', 'toolset-maps' ) ] = true;
		return $groups;
	}
	
	function wpv_suggest_wpv_post_field_name() {
		global $wpdb;
		$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
		$cf_keys = $wpdb->get_col( 
			$wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM {$wpdb->postmeta}
				WHERE meta_key LIKE %s
				ORDER BY meta_key
				LIMIT 5",
				$meta_key_q 
			) 
		);
		foreach ( $cf_keys as $key ) {
			echo $key . "\n";
		}
		die();
	}

	function wpv_suggest_wpv_taxonomy_field_name() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.4' ) < 0 ) {
			echo '';
			die();
		}
		global $wpdb;
		$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
		$cf_keys = $wpdb->get_col( 
			$wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM {$wpdb->termmeta}
				WHERE meta_key LIKE %s
				ORDER BY meta_key
				LIMIT 5",
				$meta_key_q 
			) 
		);
		foreach ( $cf_keys as $key ) {
			echo $key . "\n";
		}
		die();
	}

	function wpv_suggest_wpv_user_field_name() {
		global $wpdb;
		$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
		$cf_keys = $wpdb->get_col( 
			$wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM {$wpdb->usermeta}
				WHERE meta_key LIKE %s
				ORDER BY meta_key
				LIMIT 5",
				$meta_key_q 
			) 
		);
		foreach ( $cf_keys as $key ) {
			echo $key . "\n";
		}
		die();
	}

	/*
	function wpv_map_shortcodes_to_loop_wizard( $editor, $nonce ) {
		$editor->add_insert_shortcode_menu(
			__( 'Map marker', 'toolset-maps' ),
			'wpv-map-marker',
			__( 'Google Maps', 'toolset-maps' ),
			"WPViews.shortcodes_gui.wpv_insert_popup('wpv-map-marker', '" . __( 'Map marker', 'toolset-maps' ) . "', {}, '" . $nonce . "', this )"
		);
	}
	*/

	function wpv_addon_maps_frontend_events_tab( $tabs, $query_type ) {
		if ( $query_type == 'posts' ) {
			$tabs['wpv-dialog-frontend-events-addon-maps-container'] = array(
				'title' => __( 'Google Maps', 'toolset-maps' )
			);
		}
		return $tabs;
	}

	function wpv_addom_maps_frontend_events_section( $query_type ) {
		if ( $query_type == 'posts' ) {
		?>
		<div id="wpv-dialog-frontend-events-addon-maps-container">
			<h2><?php _e( 'Google Maps events', 'toolset-maps' ); ?></h2>
			<p>
				<?php _e( 'The Google Maps addon triggers some events.', 'toolset-maps' ); ?>
			</p>
			<ul>
				<li>
					<label for="wpv-frontent-event-map-init-started">
						<input type="checkbox" id="wpv-frontent-event-map-init-started" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_addon_maps_init_map_started" />
						<?php _e( 'The Google Map is going to be inited', 'toolset-maps' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This happens when a map init starts', 'toolset-maps' ); ?></span>
				</li>
				<li>
					<label for="wpv-frontent-event-map-init-inited">
						<input type="checkbox" id="wpv-frontent-event-map-init-inited" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_addon_maps_init_map_inited" />
						<?php _e( 'The Google Map was just inited', 'toolset-maps' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This happens when a map is inited but before the markers have been inited', 'toolset-maps' ); ?></span>
				</li>
				<li>
					<label for="wpv-frontent-event-map-init-completed">
						<input type="checkbox" id="wpv-frontent-event-map-init-completed" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_addon_maps_init_map_completed" />
						<?php _e( 'The Google Map was just completely inited', 'toolset-maps' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This happens when a map reload is completely rendered including its markers', 'toolset-maps' ); ?></span>
				</li>
				<li>
					<label for="wpv-frontent-event-map-reload-started">
						<input type="checkbox" id="wpv-frontent-event-map-reload-started" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_addon_maps_reload_map_started" />
						<?php _e( 'The Google Map is going to be reloaded', 'toolset-maps' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This happens when a map reload starts', 'toolset-maps' ); ?></span>
				</li>
				<li>
					<label for="wpv-frontent-event-map-reload-completed">
						<input type="checkbox" id="wpv-frontent-event-map-reload-completed" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_addon_maps_reload_map_completed" />
						<?php _e( 'The Google Map was just reloaded', 'toolset-maps' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This happens when a map reload is completed', 'toolset-maps' ); ?></span>
				</li>
			</ul>
		</div>
		<?php
		}
	}
	
	function wpv_addon_maps_parametric_search_pretend_textfield_type( $field_properties ) {
		if (
			isset( $field_properties['type'] ) 
			&& $field_properties['type'] == TOOLSET_ADDON_MAPS_FIELD_TYPE
		) {
			$field_properties['type'] = 'textfield';
		}
		return $field_properties;
	}
	
	function toolset_maps_settings_link( $toolset_maps_settings_link ) {
		$toolset_maps_settings_link = admin_url( 'admin.php?page=views-settings&tab=addon_maps' );
		return $toolset_maps_settings_link;
	}

}

$Toolset_Addon_Maps_Views = new Toolset_Addon_Maps_Views();
