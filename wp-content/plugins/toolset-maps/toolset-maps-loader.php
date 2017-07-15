<?php
/*
Plugin Name: Toolset Maps
Plugin URI: https://wp-types.com/documentation/user-guides/display-on-google-maps/
Description: Toolset Maps will extend Types, WP Views and CRED with advanced geolocalization features
Version: 1.3.0
Text Domain: toolset-maps
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
*/

add_action( 'plugins_loaded', 'toolset_addon_map_load_or_deactivate', 25 );

/**
* toolset_addon_map_load_or_deactivate
*
* Check dependencies, load required files and activate if posible
*
* @since 1.0
* @since 1.1 Raised the priority number of this action so it actually happens after Toolset Views Embedded is loaded.
*/

function toolset_addon_map_load_or_deactivate() {
	$requirements = array(
		'cred'		=> array(
			'class_exists'		=> 'CRED_Loader',
			'version_constant'	=> 'CRED_FE_VERSION',
			'version_minimum'	=> '1.4.2',
			'require_once'		=> '/includes/toolset-maps-cred.class.php',
		),
		'views'		=> array(
			'class_exists'		=> 'WP_Views',
			'version_constant'	=> 'WPV_VERSION',
			'version_minimum'	=> '1.11.1',
			'require_once'		=> '/includes/toolset-maps-views.class.php',
		),
		'types'		=> array(
			'function_exists'	=> 'wpcf_bootstrap',
			'version_constant'	=> 'WPCF_VERSION',
			'version_minimum'	=> '1.8.9',
			'require_once'		=> '/includes/google_address.php'
		),
	);
	$do_load = false;
	$do_available = array();

	foreach ( $requirements as $req_slug => $req_data ) {
		if (
			(
				( 
					isset( $req_data['class_exists'] ) 
					&& class_exists( $req_data['class_exists'] )
				) || ( 
					isset( $req_data['function_exists'] ) 
					&& function_exists( $req_data['function_exists'] )
				)
			)
			&& isset( $req_data['version_constant'] )
			&& defined( $req_data['version_constant'] )
			&& version_compare( constant( $req_data['version_constant'] ), $req_data['version_minimum'], '>=' )
		) {
			$do_load = true;
			$do_available[] = $req_slug;
		}
	}

	if ( $do_load ) {
		define( 'TOOLSET_ADDON_MAPS_VERSION', '1.3.0' );
		define( 'TOOLSET_ADDON_MAPS_PATH', dirname( __FILE__ ) );
		define( 'TOOLSET_ADDON_MAPS_FOLDER', basename( TOOLSET_ADDON_MAPS_PATH ) );
		define( 'TOOLSET_ADDON_MAPS_FIELD_TYPE', 'google_address' );
		define( 'TOOLSET_ADDON_MAPS_MESSAGE_SPACE_CHAR', '&nbsp;' );
		define( 'TOOLSET_ADDON_MAPS_DOC_LINK', 'https://wp-types.com/documentation/user-guides/display-on-google-maps/' );
		if (
			is_ssl()
			|| (
				defined( 'FORCE_SSL_ADMIN' )
				&& FORCE_SSL_ADMIN
			)
		) {
			define( 'TOOLSET_ADDON_MAPS_URL', rtrim( str_replace( 'http://', 'https://', plugins_url() ), '/' ) . '/' . TOOLSET_ADDON_MAPS_FOLDER );
			define( 'TOOLSET_ADDON_MAPS_PROTOCOL', 'https' );
		} else {
			define( 'TOOLSET_ADDON_MAPS_URL', plugins_url() . '/' . TOOLSET_ADDON_MAPS_FOLDER );
			define( 'TOOLSET_ADDON_MAPS_PROTOCOL', 'http' );
		}
		if ( is_ssl() ) {
			define( 'TOOLSET_ADDON_MAPS_FRONTEND_URL', TOOLSET_ADDON_MAPS_URL );
			define( 'TOOLSET_ADDON_MAPS_FRONTEND_PROTOCOL', 'https' );
		} else {
			define( 'TOOLSET_ADDON_MAPS_FRONTEND_URL', str_replace( 'https://', 'http://', TOOLSET_ADDON_MAPS_URL ) );
			define( 'TOOLSET_ADDON_MAPS_FRONTEND_PROTOCOL', 'http' );
		}
		require_once TOOLSET_ADDON_MAPS_PATH.'/includes/toolset-common-functions.php';
		foreach ( $do_available as $do_slug ) {
			if ( isset( $requirements[ $do_slug ]['require_once'] ) ) {
				require_once TOOLSET_ADDON_MAPS_PATH . $requirements[ $do_slug ]['require_once'];
			}
		}
		load_plugin_textdomain( 'toolset-maps', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} else {
		add_action( 'admin_init', 'toolset_addon_map_deactivate' );
		add_action( 'admin_notices', 'toolset_addon_map_deactivate_notice' );
	}
}

/**
* toolset_addon_map_deactivate
*
* Deactivate this plugin
*
* @since 1.0
*/

function toolset_addon_map_deactivate() {
	$plugin = plugin_basename( __FILE__ );
	deactivate_plugins( $plugin );
	if ( ! is_network_admin() ) {
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	} else {
		update_site_option( 'recently_activated', array( $plugin => time() ) + (array) get_site_option( 'recently_activated' ) );
	}
}

/**
* toolset_addon_map_deactivate_notice
*
* Deactivate notice for this plugin
*
* @since 1.0
*/

function toolset_addon_map_deactivate_notice() {
    ?>
    <div class="error is-dismissable">
        <p>
		<?php
		_e( 'Toolset - Google Maps Addon was <strong>deactivated</strong>! You need at least WP Views 1.11.1 or Types 1.8.9 or CRED 1.4.2', 'toolset-maps' );
		?>
		</p>
    </div>
    <?php
}
