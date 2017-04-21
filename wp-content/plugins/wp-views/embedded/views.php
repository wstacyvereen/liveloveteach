<?php

// ----------------------------------
// Plugin initialization
// ----------------------------------

/**
* Set WPV_VERSION
*/

if ( defined( 'WPV_VERSION' ) ) {
    return; 
}

define( 'WPV_VERSION', '2.3.1' );

/**
* Set constants
*/

/*
 * Note: This modification was not authorized, but might be in the future. I'll dare to just leave it here. --Jan
 *
 * WPV_PATH and WPV_PATH_EMBEDDED can be overriden, if WPV_PATH_OVERRIDE is also defined.
 *
 * If those constants are defined at this point, Views will not try to redefine them. This may be helpful in some weird
 * scenarios (like using symlinks on dev site) and will not affect anyone who doesn't make an effort to use this
 * feature.
 */
/*if( !defined( 'WPV_PATH_OVERRIDE' ) || !defined( 'WPV_PATH' ) ) {
    define( 'WPV_PATH', dirname( __FILE__ ) );
}

if( !defined( 'WPV_PATH_OVERRIDE' ) || !defined( 'WPV_PATH_EMBEDDED' ) ) {
    define( 'WPV_PATH_EMBEDDED', dirname( __FILE__ ) );
}*/

define( 'WPV_PATH',				dirname( __FILE__ ) );
define( 'WPV_PATH_EMBEDDED',	dirname( __FILE__ ) );

if ( ! defined( 'WPV_FOLDER' ) ) {
	define( 'WPV_FOLDER',		basename( WPV_PATH ) );
}

if ( strpos( str_replace( '\\', '/', WPV_PATH_EMBEDDED ), str_replace( '\\', '/', WP_PLUGIN_DIR ) ) !== false ) {
	$wpv_url = plugins_url( 'embedded-views' , dirname( __FILE__ ) );
	if ( defined( 'WPV_EMBEDDED_ALONE' ) ) {
		$wpv_url = plugins_url() . '/' . WPV_FOLDER;
	}
	if ( 
		( 
			defined( 'FORCE_SSL_ADMIN' ) 
			&& FORCE_SSL_ADMIN 
		) || is_ssl() 
	) {
		$wpv_url = str_replace( 'http://', 'https://', $wpv_url );
	}
	define( 'WPV_URL',			$wpv_url );
	define( 'WPV_URL_EMBEDDED',	$wpv_url );
} else {
	define( 'WPV_URL',			get_stylesheet_directory_uri() . '/' . WPV_FOLDER);
	define( 'WPV_URL_EMBEDDED',	WPV_URL);
}
if ( is_ssl() ) {
	define( 'WPV_URL_EMBEDDED_FRONTEND',	WPV_URL_EMBEDDED );
} else {
	define( 'WPV_URL_EMBEDDED_FRONTEND',	str_replace( 'https://', 'http://', WPV_URL_EMBEDDED ) );
}

/**
* Require OnTheGo Resources and Toolset Common
*/

define( 'WPV_PATH_EMBEDDED_TOOLSET',	WPV_PATH_EMBEDDED . '/toolset' );
define( 'WPV_URL_EMBEDDED_TOOLSET',		WPV_URL_EMBEDDED . '/toolset' );

require WPV_PATH_EMBEDDED_TOOLSET . '/onthego-resources/loader.php';
onthego_initialize( WPV_PATH_EMBEDDED_TOOLSET . '/onthego-resources/', WPV_URL_EMBEDDED_TOOLSET . '/onthego-resources/' );
require WPV_PATH_EMBEDDED_TOOLSET . '/toolset-common/loader.php';
toolset_common_initialize( WPV_PATH_EMBEDDED_TOOLSET . '/toolset-common/', WPV_URL_EMBEDDED_TOOLSET . '/toolset-common/' );

/**
* Initialize the Views Settings
* @global $WPV_settings WPV_Settings Views settings manager.
* @deprecated Use $s = WPV_Settings::get_instance() instead.
*/

require_once WPV_PATH_EMBEDDED . '/inc/wpv-settings.class.php';
global $WPV_settings;
$WPV_settings = WPV_Settings::get_instance();

// ----------------------------------
// Require files
// ----------------------------------

/**
* Public Views API
*/

require_once WPV_PATH_EMBEDDED . '/inc/third-party/hooks-api.php';
WPV_API_Embedded::initialize();

/**
* WPV_View and other Toolset object wrappers
*/

require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-post-object-wrapper.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-view-base.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-view-embedded.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-wordpress-archive-embedded.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-content-template-embedded.class.php';

/**
* Cache
*/

require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-cache.class.php';

/**
* Module Manager integration
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-module-manager.php';

/**
* Constants
* @todo merge this and load just one
*/

require WPV_PATH_EMBEDDED . '/inc/constants-embedded.php';

/**
* Working files
* @todo review
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-admin-messages.php';
require WPV_PATH_EMBEDDED . '/inc/functions-core-embedded.php';

/**
* Debug tool
*/

if ( ! function_exists( 'wpv_debuger' ) ) { 
	require_once(WPV_PATH_EMBEDDED) . '/inc/wpv-query-debug.class.php';
}

/**
* Shortcodes
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes-in-shortcodes.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes-gui.php';
if ( ! function_exists( 'wpv_shortcode_generator_initialize' ) ) {
	add_action( 'after_setup_theme', 'wpv_shortcode_generator_initialize', 999 );
	function wpv_shortcode_generator_initialize() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_sections = array( 'toolset_shortcode_generator' );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		require WPV_PATH_EMBEDDED . '/inc/classes/wpv-shortcode-generator.php';
	}
}

/**
* Conditional
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-conditional.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-condition.php';

/**
* Working files
* @todo review
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-formatting-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-layout-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-meta-html-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-pagination-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-archive-loop.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-user-functions.php';

/**
* Query modifiers
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-filter-order-by-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-types-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-post-types-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-limit-embedded.php';

/**
* Frontend query filters
*/

require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-author-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-category-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-date-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-id-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-meta-field-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-parent-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-post-relationship-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-search-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-status-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-sticky-embedded.php';

/**
* WPML integration
*/

require WPV_PATH_EMBEDDED . '/inc/WPML/wpv_wpml.php';
WPV_WPML_Integration_Embedded::initialize();

/**
 * WooCommerce integration
 */

require WPV_PATH_EMBEDDED . '/inc/third-party/wpv-compatibility-woocommerce.class.php';


// Other third-party compatibility fixes
require_once WPV_PATH_EMBEDDED . '/inc/third-party/wpv-compatibility-generic.class.php';
WPV_Compatibility_Generic::initialize();


/**
* Main plugin classes
*/

require WPV_PATH_EMBEDDED . '/inc/wpv.class.php';
global $WP_Views;
$WP_Views = new WP_Views();

require WPV_PATH . '/inc/views-templates/functions-templates.php';
require WPV_PATH . '/inc/views-templates/wpv-template.class.php';
global $WPV_templates;
$WPV_templates = new WPV_template();

/**
* Query controllers
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-filter-query.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-taxonomy-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-users-embedded.php';

/**
* Frameworks integration
*/

require_once WPV_PATH_EMBEDDED . '/inc/third-party/wpv-framework-api.php';

/**
* Widgets
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-widgets.php';

/**
* Export / import
*/

if ( is_admin() ) {
    require WPV_PATH_EMBEDDED . '/inc/wpv-import-export-embedded.php';
}

/**
* Working files
* @todo review
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-summary-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-readonly-embedded.php';

/**
* Public Views API functions
*/

require WPV_PATH_EMBEDDED . '/inc/wpv-api.php';



if (!is_admin()) {

	add_action('init', 'wpv_add_jquery');

    function wpv_add_jquery() {
		wp_enqueue_script('jquery');
	}
}

/**
* toolset_is_views_embedded_available
*
* Filter to check whether Views Embedded is installed
*
* @since 1.9
*/

add_filter( 'toolset_is_views_embedded_available', '__return_true' );

/**
* toolset_views_version
*
* Return the current Views version installed
*
* @since 2.1
*/

add_filter( 'toolset_views_version_installed', 'wpv_return_installed_version' );

/**
 * Register screen IDs where Toolset promotion pop-up should be available.
 *
 * Currently we use it on embedded listing pages.
 *
 * @since 1.8
 * @since 2.0	Register the new screen IDs after merging Toolset into one shared menu
 */
add_filter( 'toolset_promotion_screen_ids', 'wpv_register_toolset_promotion_screen_ids' );

function wpv_register_toolset_promotion_screen_ids( $screen_ids ) {
    if( is_array( $screen_ids ) ) {
        $screen_ids = array_merge(
            $screen_ids,
            array( 
				'toplevel_page_embedded-views',
				'views_page_embedded-views-templates', 'views_page_embedded-views-archives', //DEPRECATED
				'toolset_page_embedded-views', 'toolset_page_embedded-views-templates', 'toolset_page_embedded-views-archives'
			) 
		);
    }
    return $screen_ids;
}

/**
* Load all dependencies that needs toolset common loader
* to be completely loaded before being required
*/

if ( ! function_exists( 'wpv_toolset_common_dependent_setup' ) ) {
	add_action('after_setup_theme', 'wpv_toolset_common_dependent_setup', 11 );
	function wpv_toolset_common_dependent_setup(){
		require_once WPV_PATH_EMBEDDED . '/inc/wpv-views-help-videos.class.php';
		require_once WPV_PATH_EMBEDDED . '/inc/wpv-views-scripts.class.php';
	}
}