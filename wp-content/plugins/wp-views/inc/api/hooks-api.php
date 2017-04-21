<?php
/**
 * Public filter hook API to be used by other Toolset plugins.
 *
 * In optimal case, all interaction with Views would happen through these hooks.
 */
class WPV_API extends WPV_API_Embedded {


	public static function initialize() {
		WPV_API_Embedded::$instance = new WPV_API();
	}


	protected function __construct() {
		parent::__construct();
		$this->register_hooks( WPV_API::$filter_hooks );
	}



	/**
	 * Filter hook definitions for embedded Views.
	 *
	 * See register_hooks() for details.
	 *
	 * @var array
	 */
	private static $filter_hooks = array(
		array( 'duplicate_content_template', 4 ),
		array( 'duplicate_wordpress_archive', 4 ),
		array( 'duplicate_view', 4 )
	);



	/**
	 * Duplicate a WordPress archive and return ID of the duplicate.
	 *
	 * Note that this may also involve duplication of it's loop template. Refer to WPV_View_Base::duplicate() for
	 * detailed description.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_wpa_id ID of the original WPA. It must exist and must be a WPA.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.11
	 */
	public function duplicate_wordpress_archive( $default_result, $original_wpa_id, $new_title, $adjust_duplicate_title ) {

		$original_wpa = WPV_View_Base::get_instance( $original_wpa_id );
		if( null == $original_wpa || !( $original_wpa instanceof WPV_WordPress_Archive ) ) {
			return $default_result;
		}

		$duplicate_wpa_id = $original_wpa->duplicate( $new_title, $adjust_duplicate_title );

		return ( false == $duplicate_wpa_id ) ? $default_result : $duplicate_wpa_id;
	}


	/**
	 * Duplicate a View and return ID of the duplicate.
	 *
	 * Note that this may also involve duplication of it's loop template. Refer to WPV_View_Base::duplicate() for
	 * detailed description.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_view_id ID of the original View. It must exist and must be a View.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.12
	 */
	public function duplicate_view( $default_result, $original_view_id, $new_title, $adjust_duplicate_title ) {

		$original_view = WPV_View_Base::get_instance( $original_view_id );
		if( null == $original_view || !( $original_view instanceof WPV_View ) ) {
			return $default_result;
		}

		$duplicate_view_id = $original_view->duplicate( $new_title, $adjust_duplicate_title );

		return ( false == $duplicate_view_id ) ? $default_result : $duplicate_view_id;
	}


	/**
	 * Duplicate a Content Template and return ID of the duplicate.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_ct_id ID of the original Content Template. It must exist and must be a Content Template.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.12
	 */
	public function duplicate_content_template( $default_result, $original_ct_id, $new_title, $adjust_duplicate_title ) {

		$original_ct = WPV_Content_Template::get_instance( $original_ct_id );
		if( null == $original_ct ) {
			return $default_result;
		}

		$duplicate_ct = $original_ct->duplicate( $new_title, $adjust_duplicate_title );

		return ( null == $duplicate_ct ) ? $default_result : $duplicate_ct->id;
	}

}