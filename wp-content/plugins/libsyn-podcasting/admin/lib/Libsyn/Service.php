<?php
namespace Libsyn;

class Service extends \Libsyn {
	
	protected $args;
	
	public static $instance;
	
	public function __construct() {
		global $wpdb;
		global $wp_version;
		parent::getVars();
		self::$instance = $this;
		$this->args = array(
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     =>  array (
				'date' => date("D j M Y H:i:s T", $this->wp_time),
				'x-powered-by' => $this->plugin_name,
				'x-server' => $this->plugin_version,
				'expires' => date("D j M Y H:i:s T", strtotime("+3 hours", $this->wp_time)),
				'vary' => 'Accept-Encoding',
				'connection' => 'close',
				'accept' => 'application/json',
				'content-type' => 'application/json',
			),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
		);
	}
	
    /**
     * Runs a GET to /post collection endpoint
     * 
     * @param <mixed> $urlParamsObj 
     * 
     * @return <object>
     */
	public function getPosts ($urlParamsObj) {
		if(!is_array($urlParamsObj)) { //handles objects too
			$urlParams = array();
			foreach($urlParamsObj as $key => $val) $urlParams[$key] = $val;
		} else { $urlParams = $urlParamsObj; }
		$url = $this->api_base_uri."/post?" . http_build_query($urlParams);
		return (object) wp_remote_get( $url, $this->args );
	}
	
    /**
     * Runs a GET to /post/$id entity endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getPost (\Libsyn\Api $api, $urlParams=array()) {
		$url = $this->api_base_uri."/post?" . http_build_query($urlParams);
		if($api instanceof Libsyn\Api) {
			return (object) wp_remote_get( $url, $this->args );
		} else {
			if(isset($this->logger) && $this->logger) $this->logger->error("Service:\tgetPost:\tapi not instance of Libsyn\Api.");
			return false;
		}
	}
	
    /**
     * Runs a generic GET request
     * 
     * @param <string> $base_url 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getGeneric($base_url, $urlParams=array()) {
		//Sanitize Data
		$sanitize = new \Libsyn\Service\Sanitize();
		$sanitized_url = $sanitize->url_raw($base_url);
		if(!empty($sanitized_url)) {
			$url = $sanitize->url_raw($base_url) . "?" . http_build_query($urlParams);
		} else {
			if(isset($this->logger) && $this->logger) $this->logger->error("Service:\tgetGeneric:\tbase_url empty.");
			return false;
		}
		return (object) wp_remote_get( $url, $this->args );
	}
	
    /**
     * Runs a GET to /post entity endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getEpisodes($urlParams=array()) {
		if(isset($urlParams['show_id'])&&!empty($urlParams['show_id'])) {
			$url = $this->api_base_uri."/post?" . http_build_query($urlParams);
		}
		$obj =  (object) wp_remote_get( $url, $this->args );
		if($this->checkResponse($obj)) {
			$response = json_decode($obj->body);
			if($response) return $response;
		} else return false;
	}
	
    /**
     * Runs a GET to /post entity endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getEpisode($urlParams=array()) {
		if(isset($urlParams['show_id'])&&!empty($urlParams['show_id']) && isset($urlParams['item_id'])&&!empty($urlParams['item_id'])) {
			$itemId = $urlParams['item_id'];
			unset($urlParams['item_id']);
			$url = $this->api_base_uri."/post/$itemId?" . http_build_query($urlParams);
		}

		$obj =  (object) wp_remote_get( $url, $this->args );
		if($this->checkResponse($obj)) {
			$response = json_decode($obj->body);
			if($response) return $response;
		} else return false;
	}
	
    /**
     * Runs a POST to /post endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $item
     * 
     * @return <mixed>
     */
	public function postPost(\Libsyn\Api $api, $item) {
		if($api instanceof \Libsyn\Api) {
			if(isset($item['item_id'])&&!empty($item['item_id'])) $url = $this->api_base_uri."/post/".$item['item_id'];
				else $url = $this->api_base_uri."/post";
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();

			$payload = '';
			$boundary = wp_generate_password( 24 );
			$this->args['headers']['content-type'] = "multipart/form-data; boundary=".$boundary;
			// First, add the standard POST fields:
			foreach ( $item as $name => $value ) {
				//handle sub arrays
				if(is_array($value)) {
					foreach($value as $key => $val) {
						if(is_array($val)) { //check 2nd level array (this is all that is needed is two levels)
							foreach ($val as $subKey => $subVal) {
								$payload .= '--' . $boundary;
								$payload .= "\r\n";
								$payload .= 'Content-Disposition: form-data; name="' . $name .'['.$key.']' .'['.$subKey.']"' . "\r\n\r\n";
								$payload .= $subVal;
								$payload .= "\r\n";
							}
						} else {						
							$payload .= '--' . $boundary;
							$payload .= "\r\n";
							$payload .= 'Content-Disposition: form-data; name="' . $name .'['.$key.']"' . "\r\n\r\n";
							$payload .= $val;
							$payload .= "\r\n";
						}
					}
				} else {
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="' . $name .'"' . "\r\n\r\n";
					$payload .= $value;
					$payload .= "\r\n";
				}
			}
			$payload .= '--' . $boundary . '--';
			$this->args['body'] = $payload;
			$obj =  (object) wp_remote_post( $url, $this->args );
			if($this->checkResponse($obj)) {
				$response = json_decode($obj->body);
				if($response->{'status'}==='success') return $response->{'post'};
					else return false;
			} else return false;
		} else return false;
	}
	
    /**
     * Runs a GET on /shows endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getShows(\Libsyn\Api $api, $urlParams=array()) {
		$params = (!empty($urlParams))?"?".http_build_query($urlParams):"";
		$url = $this->api_base_uri."/user-shows" . $params;
		if($api instanceof \Libsyn\Api) {
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();
			$obj =  (object) wp_remote_get( $url, $this->args );
			if($this->checkResponse($obj)) return json_decode($obj->body)->_embedded;
				else return false;
		}
		else return false;
	}
	
    /**
     * Runs a GET on /wordpress endpoint
     * Also sets up redirect on WP
	 *
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function feedImport(\Libsyn\Api $api, $urlParams=array()) {
		$urlParams = array('show_id' => $api->getShowId(), 'feed_url' => $api->getFeedRedirectUrl());
		$params = (!empty($urlParams))?"?".http_build_query($urlParams):"";
		$url = $this->api_base_uri."/wordpress" . $params;
		if($api instanceof \Libsyn\Api) {
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();
			$obj =  (object) wp_remote_get( $url, $this->args );
			if($this->checkResponse($obj)) return json_decode($obj->body)->_embedded;
				else return false;
		}
		else return false;
	}
	
    /**
     * Runs a GET on /ftp-unreleased endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getFtpUnreleased (\Libsyn\Api $api, $urlParams=array()) {
		if($api instanceof \Libsyn\Api) {
			if(!isset($urlParams['show_id'])) $urlParams['show_id'] = $api->getShowId();
			$params = "?".http_build_query($urlParams);
			$url = $this->api_base_uri."/ftp-unreleased" . $params;
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();
			$obj =  (object) wp_remote_get( $url, $this->args );
			if($this->checkResponse($obj)) return json_decode($obj->body)->_embedded;
				else return false;
		} else return false;
	}
	
    /**
     * Runs a GET on /categories endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getCategories (\Libsyn\Api $api, $urlParams=array()) {
		if($api instanceof \Libsyn\Api) {
			if(!isset($urlParams['show_id'])) $urlParams['show_id'] = $api->getShowId();
			$params = "?".http_build_query($urlParams);
			$url = $this->api_base_uri."/categories" . $params;
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();
			$obj =  (object) wp_remote_get( $url, $this->args );
			if($this->checkResponse($obj)) return json_decode($obj->body)->_embedded;
				else return false;
		} else return false;
	}
	
    /**
     * Runs a GET on /destinations endpoint
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $urlParams 
     * 
     * @return <mixed>
     */
	public function getDestinations(\Libsyn\Api $api, $urlParams=array()) {
		if($api instanceof \Libsyn\Api) {
			if(!isset($urlParams['show_id'])) $urlParams['show_id'] = $api->getShowId();
			$params = "?".http_build_query($urlParams);
			$url = $this->api_base_uri."/destinations/"  . $api->getShowId() . $params;
			$this->args['headers']['Authorization'] = "Bearer ".$api->getAccessToken();
			$obj = (object) wp_remote_get( $url, $this->args );
			if($this->checkResponse($obj)&&isset(json_decode($obj->body)->_embedded)) return json_decode($obj->body)->_embedded;
				else return false;
		} else return false;
	}
	
    /**
     * Handles changes made to Libsyn-WP api settings
     * 
     * @param <Libsyn\Api> $api
     * 
     * @return <type>
     */
	public function updateSettings(\Libsyn\Api $api) {
		global $wpdb;
		$wpdb->update(
			$this->api_table_name, 
			array(
				'client_id' => $api->getClientId(),
				'client_secret' => $api->getClientSecret(),
				'show_id' => $api->getShowId(),
				'feed_redirect_url' => $api->getFeedRedirectUrl(),
				'itunes_subscription_url' => $api->getItunesSubscriptionUrl(),
			),
			array(
				'is_active' => 1,
				'plugin_api_id' => $api->getPluginApiId(),
			)
		);		
	}
	
    /**
     * Remove Libsyn-WP api settings
     * 
     * @param <Libsyn\Api> $api 
     * 
     * @return <type>
     */
	public function removeApiSettings(Libsyn\Api $api) {
		global $wpdb;
		$wpdb->update(
			$this->api_table_name, 
			array(
				'is_active' => 0,
			),
			array(
				'plugin_api_id' => $api->getPluginApiId(),
			)
		);		
	}
	
    /**
     * Get a iFrame output for the Libsyn-WP settings panel
     * 
     * @param <mixed> $clientId 
     * @param <string> $redirectUri 
     * 
     * @return <string>
     */
	public function oauthAuthorize($clientId=null, $redirectUri='') {
		if(!empty($clientId) && !empty($redirectUri)) {
			$urlParams = array(
				'client_id' => $clientId
				,'redirect_uri' => urldecode($redirectUri)
				,'response_type' => 'code'
				,'state' => 'xyz'
			);
			$url = $this->api_base_uri."/oauth/authorize?" . http_build_query($urlParams);
			return "<iframe id=\"oauthBox\" src=\"".$url."&authorized=true"."\" width=\"600\" height=\"450\"></iframe>";
		} else {
			return "<iframe id=\"oauthBox\" width=\"600\" height=\"450\"><html><head></head><body><h3>Either the client ID or Wordpress Site URL are incorrect.  Please check your settings and try again.</h3></body></html></iframe>";
		}
		return "<iframe id=\"oauthBox\" width=\"600\" height=\"450\"><html><head></head><body><h3>An unknown error has occurred,  please check your settings and try again.</h3></body></html></iframe>";
	}
	
    /**
     * Get a the API URL to load authentication.
     * 
     * @param <mixed> $clientId 
     * @param <string> $redirectUri 
     * 
     * @return <string>
     */
	public function getAuthorizeUrl($clientId=null, $redirectUri='') {
		$urlParams = array(
			'client_id' => $clientId
			,'redirect_uri' => urldecode($redirectUri)
			,'response_type' => 'code'
			,'state' => 'xyz'
		);
		return $this->api_base_uri."/oauth/authorize?" . http_build_query($urlParams);
	}

    /**
     * Do a new auth bearer request
     * 
     * @param <mixed> $clientId 
     * @param <mixed> $secret 
     * @param <string> $code 
     * @param <string> $redirectUri 
     * 
     * @return <mixed>
     */
	public function requestBearer($clientId=null, $secret=null, $code, $redirectUri='') {
		$redirectUriParts = parse_url($redirectUri);
		parse_str($redirectUriParts['query']);
		
		//set params
		$params = array();
		$params['redirect_uri'] = $redirectUriParts['scheme'].'://'.$redirectUriParts['host'].$redirectUriParts['path'].'?page='.$page;
		if(!empty($clientId)) $params['client_id'] = $clientId;
		if(!empty($secret)) $params['client_secret'] = $secret;
		if(!empty($code)) $params['code'] = $code;
		$params['grant_type'] = 'authorization_code';
		$args = array("method" => "POST") + $this->args;
		$args['body'] = json_encode($params);
		$url = $this->api_base_uri."/oauth";
		if(!is_null($clientId)&&!is_null($secret))
			return (object) wp_remote_post( $url, $args );
		else return false;
	}
	
	public function checkResponse($obj) {
		if(!is_object($obj)) return false;
		if($obj->response['code']!==200) {
			if(isset($this->logger)) {
				if($this->logger) $this->logger->error("Service:\tcheckResponse:\tError");
				if($this->logger) $this->logger->error("Service:\turl:\t".$obj->response['url']);
				if($this->logger) $this->logger->error("Service:\tsuccess:\t".$obj->response['success']);
				if($this->logger) $this->logger->error("Service:\tstatus_code:\t".$obj->response['status_code']);
			}
			return false;
		} else {
			return true;
		}
	}
	
    /**
     * Create new Libsyn-WP Api
     * 
     * @param <array> $settings 
     * 
     * @return <Libsyn\Api>
     */
	public function createLibsynApi(array $settings) {
		global $wpdb;
		/*
		 * We'll set the default character set and collation for this table.
		 * If we don't do this, some characters could end up being converted 
		 * to just ?'s when saved in our table.
		 */
		$charset_collate = '';


		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$sql = "CREATE TABLE $this->api_table_name (
		  plugin_api_id mediumint(9) NOT NULL AUTO_INCREMENT,
		  client_id varchar(64) NOT NULL,
		  client_secret varchar(80) NOT NULL,
		  access_token varchar(40) NOT NULL,
		  refresh_token varchar(40) NOT NULL,
		  is_active tinyint(3) DEFAULT 0 NOT NULL,
		  refresh_token_expires DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  access_token_expires datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  show_id int(8),
		  feed_redirect_url varchar(510),
		  itunes_subscription_url varchar(510),
		  creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  UNIQUE KEY id (plugin_api_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		//Sanitize Data
		$sanitize = new \Libsyn\Service\Sanitize();
		
		//insert $settings
		$wpdb->insert( 
			$this->api_table_name, 
			array(
				
				'client_id' => $sanitize->clientId($settings['client_id']),
				'client_secret' => $sanitize->clientSecret($settings['client_secret']),
				'access_token' => $sanitize->accessToken($settings['access_token']),
				'refresh_token' => $sanitize->refreshToken($settings['refresh_token']),
				'is_active' => 1,
				'refresh_token_expires' => $sanitize->date_format(date("Y-m-d H:i:s", strtotime("+88 days", strtotime(current_time( 'mysql' ))))), 
				'access_token_expires' => $sanitize->date_format(date("Y-m-d H:i:s", strtotime("+28 days", strtotime(current_time( 'mysql' ))))), 
				'creation_date' =>  $sanitize->date_format(current_time( 'mysql' )),
			)
		);
		$lastId = $wpdb->insert_id;
		$data = $wpdb->get_results("SELECT * FROM $this->api_table_name WHERE plugin_api_id = $lastId AND is_active = 1");
		if(isset($this->logger) && $this->logger) $this->logger->info("Service:\tcreateLibsynApi:\tCreating New Libsyn API");
		return new \Libsyn\Api($data[0]);
	}
	
    /**
     * Get Libsyn-WP Apis
     * 
     * 
     * @return <mixed>
     */
	public function getApis() {
		global $wpdb;
		if ($wpdb->get_var('SHOW TABLES LIKE \''.$this->api_table_name.'\'') != $this->api_table_name) return false;
		
		$data = $wpdb->get_results('SELECT * FROM '.$this->api_table_name.' WHERE is_active=1 ORDER BY plugin_api_id DESC LIMIT 1');
		if(is_array($data)) return new \Libsyn\Api (array_shift($data)); else return false;
	}
	
    /**
     * Removes Plugin Settings
     * 
     * @param <type> $api 
     * 
     * @return <type>
     */
	public function removeSettings($api) {
		global $wpdb;
		
		if(!is_null($api->getPluginApiId())) {
			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$this->api_table_name." WHERE plugin_api_id = %s", $api->getPluginApiId()));
		}
		$wpdb->get_results($wpdb->prepare("DELETE FROM ". $wpdb->prefix . "options" ." WHERE option_name LIKE '%s'", 'libsyn-podcasting-%'));
	}
	
    /**
     * Create WP notification markup
     * 
     * @param <string> $msg 
     * @param <bool> $error 
     * 
     * @return <string>
     */
	public function createNotification($msg, $error=false) {

		if($error) {
			if(isset($this->logger) && $this->logger) $this->logger->error("Service:\createNotification:\t".$msg);
			return "<div class=\"error settings-error\" id=\"setting-error\">"
					."<p><strong>".$msg."</strong></p>"
					."</div>";					
		} else {
			if(isset($this->logger) && $this->logger) $this->logger->info("Service:\createNotification:\t".$msg);
			return "<div class=\"updated settings-error\" id=\"setting-error-settings_updated\">"
					."<p><strong>".$msg."</strong></p>"
					."</div>";			
		}
	}
	
    /**
     * Simple helper to grab the script code to force a redirect.
     * 
     * @param <string> $url 
     * 
     * @return <string>
     */
	public function redirectUrlScript($url) {
		return 
			"<script type=\"text/javascript\">
				if(typeof window.location.assign == 'function') window.location.assign(\"".$url."\");
				else if (typeof window.location == 'object') window.location(\"".$url."\");
				else if (typeof window.location.href == 'string') window.location.href = \"".$url."\";
				else alert('Unknown script error 1021.  To help us improve this plugin, please report this error to support@libsyn.com.');
			</script>";		
	}
	
    /**
     * Gets a instance of this class
     * 
     * 
     * @return <Libsyn\Service>
     */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
    /**
     * WP requires plugin data to be passed for some stuff,
	 * This generates a sudo WP $plugin_data
     * 
     * 
     * @return <array>
     */
	public function getPluginData() {
		return get_plugin_data($this->plugin_base_dir . LIBSYN_DIR.'.php');
	}
	
    /**
     * Checks to see if there is a post edit already created with libsyn-podcasting plugin
	 * If so, then return the post_id of the post with that item.
     * 
     * @param <int> $itemId 
     * 
     * @return <mixed>
     */
	public function checkEditPostDuplicate($itemId){
		global $wpdb;
		$sanitize = new \Libsyn\Service\Sanitize();
		$results = $wpdb->get_results(
			$wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='libsyn-item-id' AND meta_value='%d'", $sanitize->itemId($itemId))
		);
		if(!empty($results)) {
			return array_shift($results);
		}
		return false;
	}
	
}

?>