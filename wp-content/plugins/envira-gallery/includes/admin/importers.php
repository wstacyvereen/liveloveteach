<?php
/**
 * Importers class.
 *
 * @since 2.5.3
 *
 * @package Envira Gallery
 * @author	Envira Gallery Team
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Envira_Importers' ) ) :

class Envira_Importers {

	/**
	 * Holds the class object.
	 *
	 * @since 2.5.3
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Path to the file.
	 *
	 * @since 2.5.3
	 *
	 * @var string
	 */
	public $file = __FILE__;

	/**
	 * Holds the base class object.
	 *
	 * @since 2.5.3
	 *
	 * @var object
	 */
	public $base;

	/**
	 * Holds the submenu pagehook.
	 *
	 * @since 2.5.3
	 *
	 * @var string
	 */
	public $hook;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.5.3
	 */
	public function __construct() {

		// Load the base class object.
		$this->base = Envira_Gallery::get_instance();

		// Add custom settings submenu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );

	}

	/**
	 * Register the Settings submenu item for Soliloquy.
	 *
	 * @since 2.5.3
	 */
	public function admin_menu() {

		$common = Envira_Gallery_Common_Admin::get_instance();

		$importers = $common->get_importers();

		if ( ! empty( $importers ) ){

			 // Register the submenu.
			 $this->hook = add_submenu_page(
				 'edit.php?post_type=envira',
				 esc_attr__( 'Envira Importers', 'envira-gallery' ),
				 esc_attr__( 'Import', 'envira-gallery' ),
				 apply_filters( 'envira_menu_cap', 'manage_options' ),
				 $this->base->plugin_slug . '-importers',
				 array( $this, 'import_page' )
			 );

			 // If successful, load admin assets only on that page and check for importers refresh.
			 if ( $this->hook ) {

				 add_action( 'load-' . $this->hook, array( $this, 'settings_page_assets' ) );

			 }

		}

	}

	 /**
	 * Outputs a WordPress style notification to tell the user their settings were saved
	 *
	 * @since 2.3.9.6
	 */
	public function updated_settings() {
		 ?>
		 <div class="updated">
			<p><?php esc_html_e( 'Settings updated.', 'envira-gallery' ); ?></p>
		</div>
		 <?php

	}

	/**
	 * Loads assets for the settings page.
	 *
	 * @since 2.5.3
	 */
	public function settings_page_assets() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}

	/**
	 * Register and enqueue settings page specific CSS.
	 *
	 * @since 2.5.3
	 */
	public function enqueue_admin_styles() {

		wp_register_style( $this->base->plugin_slug . '-importers-style', plugins_url( 'assets/css/importers.css', $this->base->file ), array(), $this->base->version );
		wp_register_style( $this->base->plugin_slug . '-select2', plugins_url( 'assets/css/select2.css', $this->base->file ), array(), $this->base->version );

		wp_enqueue_style( $this->base->plugin_slug . '-importers-style' );
		wp_enqueue_style( $this->base->plugin_slug . '-select2' );

		$active_section    = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'general';

		if ( $active_section === 'general' ) {

			// Run a hook to load in custom scripts.
			do_action( 'envira_importers_styles' );

		} else {

			do_action( 'envira_importers_styles_' . $active_section );

		}
	}

	/**
	 * Register and enqueue settings page specific JS.
	 *
	 * @since 2.5.3
	 */
	public function enqueue_admin_scripts() {

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_register_script( $this->base->plugin_slug . '-select2', plugins_url( 'assets/js/min/select2.full-min.js', $this->base->file ), array(), $this->base->version, true );
		wp_enqueue_script( $this->base->plugin_slug . '-select2' );

		wp_register_script( $this->base->plugin_slug . '-importers-script', plugins_url( 'assets/js/min/importer-min.js', $this->base->file ), array( 'jquery', 'jquery-ui-tabs' ), $this->base->version, true );
		wp_enqueue_script( $this->base->plugin_slug . '-importers-script' );



		$active_section    = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'general';
		if ( $active_section === 'general' ) {
		// Run a hook to load in custom scripts.
		do_action( 'envira_importers_scripts' );

		} else {

			do_action( 'envira_importers_scripts_' . $active_section );

		}
	}

	/**
	 * Callback to output the Soliloquy settings page.
	 *
	 * @since 2.5.3
	 */
	public function import_page() {

		$common = Envira_Gallery_Common_Admin::get_instance();
		$importers = $common->get_importers();
		$active_section    = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'general';

		do_action('envira_head');

		?>

		<?php if ( $active_section === 'general' ): ?>

        <div id="importer-heading" class="subheading clearfix">
            <h2><?php _e( 'Envira Gallery Importers', 'envira-gallery' ); ?></h2>
            <form id="add-on-search">
                <span class="spinner"></span>
                <input id="add-on-searchbox" name="envira-addon-search" value="" placeholder="<?php _e( 'Search Envira Addons', 'envira-gallery' ); ?>" />
                <select id="envira-filter-select">
                    <option value="asc"><?php _e( 'Sort Ascending (A-Z)', 'envira-gallery' ); ?></option>
                    <option value="desc"><?php _e( 'Sort Descending (Z-A)', 'envira-gallery' ); ?></option>
                </select>
            </form>
        </div>

		<div id="envira-importers" class="wrap">

			 <h1 class="envira-hideme"></h1>

			<div class="envira-clearfix"></div>

			<div id="envira-importers" class="envira envira-clear">

				<?php $i = 0; foreach ( (array) $importers as $id => $info ) : $class = 0 === $i ? 'envira-active' : ''; ?>

					 <div class="envira-importer" data-importer-title="Carousel importer" data-importer-status="inactive">

						 <div class="envira-importer-content">

							 <h3 class="envira-importer-title"><?php echo $info['title'] ?></h3>

							 <img class="envira-importer-thumb" src="<?php echo $info['thumb'] ?>" width="300px" height="250px" alt="<?php echo $info['title'] ?>">

							 <p class="envira-importer-excerpt"><?php echo $info['description'] ?></p>

						</div>

						<div class="envira-importer-footer">

							<div class="envira-importer-inactive envira-importer-message">

								<div class="envira-importer-action">

									<a class="envira-icon-cloud-download button button-envira-secondary envira-importer-action-button envira-activate-importer" href="<?php echo esc_url( $info['url'] ) ?>">
										<i class="envira-cloud-download"></i>
										<?php _e( 'Import', 'envira-gallery' ); ?>
									</a>

								</div>

							</div>

						</div>

					</div>

				<?php $i++; endforeach; ?>

			</div>

		</div>

		<?php
		else:

			do_action( 'envira_importer_section_' . $active_section );

		endif;

	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 2.5.3
	 *
	 * @return object The Soliloquy_Settings object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Importers ) ) {

			self::$instance = new Envira_Importers();

		}

		return self::$instance;

	}

}

// Load the settings class.
$envira_importers = Envira_Importers::get_instance();

endif;