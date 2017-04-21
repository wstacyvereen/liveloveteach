<?php
namespace Libsyn;

class Utilities extends \Libsyn{
	
	/**
	 * Handles WP callback to send variable to trigger AJAX response.
	 * 
	 * @param <array> $vars 
	 * 
	 * @return <array>
	 */
	public static function plugin_add_trigger_libsyn_check_ajax($vars) {
		$vars[] = 'libsyn_check_url';
		return $vars;
	}
	
	/**
	 * Handles WP callback to save ajax settings
	 * 
	 * @param <array> $vars 
	 * 
	 * @return <array>
	 */
	public static function plugin_add_trigger_libsyn_oauth_settings($vars) {
		$vars[] = 'libsyn_oauth_settings';
		return $vars;
	}
	
	/**
	 * Handles WP callback to clear outh settings
	 * 
	 * @param <array> $vars 
	 * 
	 * @return <array>
	 */
	public static function plugin_add_trigger_libsyn_update_oauth_settings($vars) {
		$vars[] = 'libsyn_update_oauth_settings';
		return $vars;
	}
	
	/**
	 * Renders a simple ajax page to check against and test the ajax urls
	 * 
	 * 
	 * @return <mixed>
	 */
	public static function checkAjax() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		parse_str($checkUrl, $urlParams);
		if(intval($urlParams['libsyn_check_url']) === 1) {
			$error = false;
			$json = true; //TODO: may need to do a check here later.
			//set output
			header('Content-Type: application/json');
			if(!$error) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}
	
	/**
	 * Saves Settings form oauth settings for dialog
	 * 
	 * 
	 * @return <mixed>
	 */
	public static function saveOauthSettings() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		parse_str($checkUrl, $urlParams);
		if(intval($urlParams['libsyn_oauth_settings']) === 1) {
			$error = false;
			$json = true; //TODO: may need to do a check here later.
			$sanitize = new \Libsyn\Service\Sanitize();		
			
			if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) { 
				update_option('libsyn-podcasting-client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret']))); 
				$clientId = $_POST['clientId']; 
				$clientSecret = $_POST['clientSecret'];
			}
			if(!empty($clientId)) $json = json_encode(array('client_id' => $clientId, 'client_secret' => $clientSecret));
				else $error = true;
			
			//set output
			header('Content-Type: application/json');
			if(!$error) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}
	
	/**
	 * Saves Settings form oauth settings for dialog
	 * 
	 * 
	 * @return <mixed>
	 */
	public static function updateOauthSettings() {
		$error = true;
		$checkUrl  = self::getCurrentPageUrl();
		parse_str($checkUrl, $urlParams);
		if(intval($urlParams['libsyn_update_oauth_settings']) === 1) {
			$error = false;
			$json = true;
			$sanitize = new \Libsyn\Service\Sanitize();
			$json = 'true'; //set generic response to true
			
			if(isset($_GET['client_id']) && isset($_GET['client_secret'])) {
				update_option('libsyn-podcasting-client', array('id' => $sanitize->clientId($_GET['client_id']), 'secret' =>$sanitize->clientSecret($_GET['client_secret']))); 
			} else {
				$error=true;
				$json ='false';
			}
			
			//set output
			header('Content-Type: application/json');
			if(!$error) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}
	
	/**
	 * Clears Settings and deletes table for uninstall
	 * 
	 * 
	 * @return <mixed>
	 */
	public static function uninstallSettings() {
		global $wpdb;
		$option_names = array(
			'libsyn-podcasting-client',
			'libsyn_api_settings'
		);
		$service = new \Libsyn\Service();
		
		foreach($option_names as $option) {
			//delete option
			delete_option( $option );
			// For site options in Multisite
			delete_site_option( $option_name );
		}

		//drop libsyn db table
		$wpdb->query( "DROP TABLE IF EXISTS ".$service->api_table_name ); //currently used without prefix
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}".$service-api_table_name ); //not really needed in current build
	}
	
	/**
	 * Clears Settings and deletes table for uninstall
	 * 
	 * 
	 * @return <mixed>
	 */
	public static function deactivateSettings() {
		global $wpdb;
		$option_names = array(
			'libsyn-podcasting-client',
			'libsyn_api_settings'
		);
		$service = new \Libsyn\Service();
		
		foreach($option_names as $option) {
			//delete option
			delete_option( $option );
			// For site options in Multisite
			delete_site_option( $option_name );
		}

		//drop libsyn db table
		$wpdb->query( "DROP TABLE IF EXISTS ".$service->api_table_name ); //old without prefix
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}".$service->api_table_name );		
	}

	/**
	 * Gets the current page url
	 * @return <string>
	 */
	public static function getCurrentPageUrl() {
		global $wp;
		return add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
	}
	
	/**
	 * function will chmod dirs and files recursively
	 * @param type $start_dir 
	 * @param type $debug (set false if you don't want the function to echo)
	 */
	public static function chmod_recursive($start_dir, $debug = false) {
		$dir_perms = 0755;
		$file_perms = 0644;
		$str = "";
		$files = array();
		if (is_dir($start_dir)) {
			$fh = opendir($start_dir);
			while (($file = readdir($fh)) !== false) {
				// skip hidden files and dirs and recursing if necessary
				if (strpos($file, '.')=== 0) continue;
				$filepath = $start_dir . '/' . $file;
				if ( is_dir($filepath) ) {
					@chmod($filepath, $dir_perms);
					self::chmod_recursive($filepath);
				} else {
					@chmod($filepath, $file_perms);
				}
			}
			closedir($fh);
		}
		if ($debug) {
			echo $str;
		}
	}
}

?>