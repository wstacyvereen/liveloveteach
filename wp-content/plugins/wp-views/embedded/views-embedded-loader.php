<?php
/*
Plugin Name: Toolset Views Embedded
Plugin URI: http://wp-types.com/?utm_source=viewsplugin&utm_campaign=views&utm_medium=plugins-list-embbedded-version&utm_term=Visit plugin site
Description: Views will query the content from the database, iterate through it and let you display it with flair. This is the embedded version of the plugin, so you will not be able to edit any component.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 2.4.0
*/



/**
* wpv_embedded_load_or_deactivate
*
* Check if the plugin version (or an embedded one inside another plugin) is already active. If it is, start the deactivation flow. If it is not, define the WPV_EMBEDDED_ALONE constant and load the plugin
*
* @since 1.6.2
*/

// @todo this is happening also on views.php which is included right after this if posible, so we might want to remove it here
// no point of loading this if the plugin is to be deactivated
require dirname(__FILE__) . '/toolset/onthego-resources/loader.php';
if ( ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) || is_ssl() ) {
	onthego_initialize(dirname(__FILE__) . '/toolset/onthego-resources/',
								   rtrim( str_replace( 'http://', 'https://', plugins_url() ), '/' ) . '/' . basename( dirname( __FILE__ ) ) . '/toolset/onthego-resources/');
} else {
	onthego_initialize(dirname(__FILE__) . '/toolset/onthego-resources/',
								   plugins_url() . '/' . basename( dirname( __FILE__ ) ) . '/toolset/onthego-resources/');
}

/**
* wpv_embedded_load_or_deactivate
*
* This must happen early, as Toolset Common is initialized at plugins_loaded:-1 and we include it on views.php
*
* @since unknown
* @since 2.0.0 Change priority to 1
* @since 2.3.0 Change priority to -10
*/

add_action( 'plugins_loaded', 'wpv_embedded_load_or_deactivate', -10 );

function wpv_embedded_load_or_deactivate() {
	if ( class_exists( 'WP_Views' ) ) {
		add_action( 'admin_init', 'wpv_embedded_deactivate' );
		add_action( 'admin_notices', 'wpv_embedded_deactivate_notice' );
	} else {
		define( 'WPV_EMBEDDED_ALONE', true );
		require_once 'views.php';
	}
}

/**
* wpv_embedded_deactivate
*
* Deactivate this plugin
*
* @since 1.6.2
*/


function wpv_embedded_deactivate() {
	$plugin = plugin_basename( __FILE__ );
	deactivate_plugins( $plugin );
}

/**
* wpv_embedded_deactivate_notice
*
* Deactivate notice for this plugin
*
* @since 1.6.2
*/

function wpv_embedded_deactivate_notice() {
    ?>
    <div class="error is-dismissable">
        <p>
			<?php _e( 'WP Views Embedded was <strong>deactivated</strong>! You are already running the complete WP Views plugin, so this one is not needed anymore.', 'wpv-views' ); ?>
		</p>
    </div>
    <?php
}