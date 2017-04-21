<?php

/**
 * Public filter hook API to be used by other Toolset plugins.
 *
 * In optimal case, all interaction with Views would happen through these hooks.
 */
class WPV_API_Embedded {


	protected static $instance;


	public static function initialize() {
		WPV_API_Embedded::$instance = new WPV_API_Embedded();
	}


	protected function __construct() {
		$this->register_hooks( WPV_API_Embedded::$filter_hooks );
	}


	/**
	 * Filter hook definitions for embedded Views.
	 *
	 * See register_hooks() for details.
	 *
	 * @var array
	 */
	private static $filter_hooks = array(
		array( 'get_setting', 2 ),
		array( 'update_setting', 3 )
	);


	/**
	 * Register all API hooks.
	 *
	 * Filter hooks are defined by their name and number of arguments. Each filter gets the wpv_prefix.
	 * Name of the handler function equals filter name.
	 *
	 * @param array $filter_hooks Array of two-element arrays, where first element is hook name and the secon one is
	 *     number of arguments they accept.
	 * @since 1.12
	 */
	protected function register_hooks( $filter_hooks ) {
		foreach( $filter_hooks as $filter_hook ) {
			$hook_name = $filter_hook[0];
			$argument_count = $filter_hook[1];
			add_filter( 'wpv_' . $hook_name, array( $this, $hook_name ), 10, $argument_count );
		}
	}


	/**
	 * Retrieve a setting value from WPV_Settings.
	 *
	 * @param mixed $default_value Return value when the setting is not recognized.
	 * @param string $setting_name Name of the setting.
	 * @return mixed If the setting exists (it has some value or WPV_Settings has either default value
	 *     or a custom getter defined for it), return the value from WPV_Settings. Otherwise fall back to $default_value.
	 * @since 1.12
	 */
	public function get_setting( $default_value, $setting_name ) {
		$settings = WPV_Settings::get_instance();
		if( !$settings->has_setting( $setting_name ) ) {
			return $default_value;
		}
		return $settings[ $setting_name ];
	}


	/**
	 * Update a setting value in WPV_Settings.
	 *
	 * @param mixed $default_value Return value when the filter is not applied.
	 * @param string $setting_name Name of the setting.
	 * @param mixed $setting_value New value.
	 * @return mixed Value stored in WPV_Settings after the update (as it is possible $setting_value will be changed or
	 *    discarded by some custom setter in WPV_Settings).
	 * @since 1.12
	 */
	public function update_setting(
		/** @noinspection PhpUnusedParameterInspection */ $default_value, $setting_name, $setting_value
	) {
		$settings = WPV_Settings::get_instance();
		$settings[ $setting_name ] = $setting_value;
		$settings->save();
		return $settings[ $setting_name ];
	}


}
