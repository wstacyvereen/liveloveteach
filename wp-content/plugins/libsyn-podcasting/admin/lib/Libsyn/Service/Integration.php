<?php
namespace Libsyn\Service;
/*
	This class is used for 3rd party software checks/integration
	For 3rd party podcast importing see Importer class.
*/
class Integration extends \Libsyn\Service {
	
	public static $instance;
	
	 function __construct() {
		parent::__construct();
		unset($this->args);
		self::$instance = $this;
	 }
	
    /**
     * Check if a plugin by name exists
	 * and if that pugin is active also.
	 * 
     * 
     * @param <string> $plugin_name 
     * 
     * @return <bool>
     */	
	public function checkPlugin($plugin_name) {
		
		//check to make sure include exisists
		if(file_exists($this->wp_base_dir . 'wp-admin/includes/plugin.php')) {
			include_once( $this->wp_base_dir . 'wp-admin/includes/plugin.php' );
		} elseif(file_exists( ABSPATH . 'wp-admin/includes/plugin.php')) { //try abspath
			include_once( ABSPATH . 'wp-admin/includes/plugin.php');
		} else { // Can't file plugin.php
			return false;
		}
		if(is_plugin_active( $plugin_name . '/' . $plugin_name . '.php')) {
			switch($plugin_name) {
				case'powerpress':
					add_action("after_plugin_row_{$this->plugin_path}","Libsyn\Service\Integration::handlePowerpress",10, 3 );
					return true;
					break;
				case 'printaura-woocommerce-api':
					add_action("after_plugin_row_{$this->plugin_path}","Libsyn\Service\Integration::handlePrintaura",10, 3 );
					break;
				case 'some-other-plugin':
					//do stuff
					break;
				default:
					return false;
			}
		} else return false;
	 }
	
    /**
     * Handles powerpress wordpress messages
     * 
     * 
     * @return <type>
     */
	public function handlePowerpress() {
		$plugin_file = self::getInstance()->plugin_path;
		$status = "Active";
		$plugin_data = self::getInstance()->getPluginData();
		$message = '<strong>PowerPress Installed:</strong> This plugin may conflict with the Libsyn Podcasting Plugin.';
		echo self::getInstance()->buildError($message);
	}
	
    /**
     * Handles powerpress wordpress messages
     * 
     * 
     * @return <type>
     */
	public function handlePrintaura() {
		$plugin_file = self::getInstance()->plugin_path;
		$status = "Active";
		$plugin_data = self::getInstance()->getPluginData();
		$message = '<strong>Printaura for WooCommerce Installed:</strong> This plugin may conflict with the Libsyn Podcasting Plugin.';
		echo self::getInstance()->buildError($message);
	}
	
    /**
     * Handles checking the PHP version before plugin install
     * 
     * 
     * @return <type>
     */
	public function checkPhpVersion() {
		$php_version = floatval(phpversion());
		if($php_version < $this->getMinimumPhpVersion() || $php_version > $this->getMaxPhpVersion()) {
			add_action("after_plugin_row_{$this->plugin_path}", array($this, "handlePhpVersionError"));
		}
	}

	/**
	 * Checks the recommended php version
	 * @return bool
	 */
	public function checkRecommendedPhpVersion() {
		$php_version = floatval(phpversion());
		if($php_version > $this->getRecommendedPhpVersion() && $php_version < $this->getMaxPhpVersion()) {
			return true;
		} else {
			return false;
		}
	}
	
    /**
     * Generates php version error response
     * 
     * 
     * @return <type>
     */
	public function handlePhpVersionError() {
		$php_version = floatval(phpversion());
		$message = '<strong>PHP Conflict:</strong> You currently have <strong>PHP version '.$php_version.'</strong> installed on the Webserver.  This has not been tested and may conflict with the Libsyn Podcasting Plugin, the supported PHP versions for this plugin are <strong>'.$this->getMinimumPhpVersion().' to '.$this->getMaxPhpVersion().'</strong>.';
		echo self::getInstance()->buildError($message);
	}
	
    /**
     * Simple adds the the proper markup for the plugins page error.
     * 
     * @param <type> $message 
     * 
     * @return <type>
     */
	private function buildError($message) {
		return '<tr class="plugin-error-tr active">
					<th class="check-column"></th>
					<td class="plugin-error colspanchange" colspan="2">
						<div class="error-message">
							'.$message.'
						</div>
					</td>
				</tr>
				<style>
					.plugin-error-tr .error-message::before {
						color: #d54e21;
						content: "\f348";
						display: inline-block;
						font: 400 20px/1 dashicons;
						margin: 0 8px 0 -2px;
						vertical-align: top;
					}
					.plugin-error-tr .error-message {
						background-color: rgba(0, 0, 0, 0.03);
						font-size: 13px;
						font-weight: 400;
						margin: 0 10px 8px 31px;
						padding: 6px 12px;
						border: 1px solid #d54e21;
					}
					.error-message {
						color: #000;
					}
				</style>
				';
	}
	
    /**
     * Create instance of class
     * 
     * 
     * @return <Libsyn\Service\Integration>
     */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
}

?>