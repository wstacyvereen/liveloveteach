<?php
namespace Libsyn;

class Api extends \Libsyn{

	protected $plugin_api_id;

	protected $client_id;
	
	protected $client_secret;
	
	protected $access_token;
	
	protected $refresh_token;
	
	protected $is_active;
	
	protected $refresh_token_expires;
	
	protected $access_token_expires;
	
	protected $show_id;
	
	protected $feed_redirect_url;
	
	protected $itunes_subscription_url;
	
	protected $creation_date;
	
	protected $last_updated;
	
	
	public function __construct($properties){
		parent::getVars();
		if($properties===null||empty($properties)) return false;
		foreach($properties as $key => $value){
			$this->{$key} = $value;
		}
		$this->sanitize = new Service\Sanitize();
	}
	
    /**
     * Simply does a API call to refresh the token and update access_tokens.
     * 
     * 
     * @return <bool>
     */
	public function refreshToken() {
		global $wpdb;
		
		//first check to see if we need to make the call
		if(strtotime($this->refresh_token_expires) >= strtotime("+87 days 23 hours 59 minutes")) return true;
		
		$args =array(
		'headers' => array (
				'date' => date("D j M Y H:i:s T", $this->wp_time),
				'x-powered-by' => $this->plugin_name,
				'x-server' => $this->plugin_version,
				'expires' => date("D j M Y H:i:s T", strtotime("+3 hours", $this->wp_time)),
				'vary' => 'Accept-Encoding',
				'connection' => 'close',
				'content-type' => 'application/json',
				'accept' => 'application/json',
			),
		'body' =>json_encode(
				array(
					'grant_type' => 'refresh_token',
					'refresh_token' => $this->getRefreshToken(),
					'client_id' => $this->getClientId(),
					'client_secret' => $this->getClientSecret(),
				)
			),
		'timeout' => 20,
		);
		$url = $this->api_base_uri."/oauth";
		$refreshResponse = wp_remote_post($url, $args);
		$checkResponse = ($refreshResponse['response']['code'] == 200)?true:false;
		$data = json_decode($refreshResponse['body']);
		if(!$checkResponse) { //bad check will remove refresh token and return false so user can re-authenticate oauth
			$wpdb->update(
				$this->api_table_name,
				array('refresh_token' => ''),
				array(
					'plugin_api_id' => $this->getPluginApiId(),
					'client_id' => $this->getClientId(),
					'client_secret' => $this->getClientSecret(),
					'is_active' => 1,
				)
			);
			if($this->logger) $this->logger->error("API:\trefreshToken:\tcheckResponse is false.");
			return false;
		}

		//check to make sure we have proper data response
		if(isset($data->access_token) && isset($data->refresh_token)) {
			//update settings
			$wpdb->update(
				$this->api_table_name,
				array(
					'access_token' => $this->sanitize->accessToken($data->access_token),
					'refresh_token' => $this->sanitize->refreshToken($data->refresh_token),
					'refresh_token_expires' => $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+88 days", $this->wp_time))), 
					'access_token_expires' => $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+28 days", $this->wp_time))),
				),
				array(
					'plugin_api_id' => $this->plugin_api_id,
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'is_active' => 1,
				)
			);
			return true;
		} else {
			if($this->logger) $this->logger->error("API:\trefreshToken:\tdata->access_token or data->refresh_token is not set.");
			return false;
		}
	}
	
    /**
     * Gets an API by the Id.
     * 
     * @param <int> $plugin_api_id 
     * 
     * @return <Libsyn\Api>
     */
	public function retrieveApiById($plugin_api_id) {
		global $wpdb;
		
		$data = $wpdb->get_row(
			'SELECT * FROM '.$this->api_table_name.' WHERE plugin_api_id = '.$this->sanitize->pluginApiId($plugin_api_id),
			OBJECT
		);
		
		if(!empty($data) && is_object($data)) {
			return new \Libsyn\Api($data);
		} else {
			if($this->logger) $this->logger->error("API:\trefreshToken:\tCould not retrieve API data set.");
			return false;
		}
		
	}
	
    /**
     * Check for if the refresh token is expired.
     * 
     * 
     * @return <bool>
     */
	public function isRefreshExpired() {
		return ($this->wp_time >=  strtotime($this->refresh_token_expires));
	}
	
    /**
     * Updates a saved api if the refresh token expired.
     * 
     * @param <Libsyn\Api> $api 
     * @param <array> $settings 
     * 
     * @return <Libsyn\Api>
     */
	public function update(array $settings) {
		global $wpdb;
		
		//check to make sure we have proper data response
		if(isset($settings['access_token']) && isset($settings['refresh_token'])) {
			//update settings
			$wpdb->update( 
				$this->api_table_name, 
				array(
					'access_token' => $this->sanitize->accessToken($settings['access_token']),
					'refresh_token' => $this->sanitize->refreshToken($settings['refresh_token']),
					'refresh_token_expires' => $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+88 days", $this->wp_time))), 
					'access_token_expires' => $this->sanitize->date_format(date("Y-m-d H:i:s", strtotime("+28 days", $this->wp_time))),
				),
				array(
					'plugin_api_id' => $this->sanitize->pluginApiId($this->plugin_api_id),
					'client_id' => $this->sanitize->clientId($this->client_id),
					'client_secret' => $this->sanitize->clientSecret($this->client_secret),
					'is_active' => 1,
				)
			);
			return $this->retrieveApiById($this->sanitize->pluginApiId($this->plugin_api_id));
		} else {
			if($this->logger) $this->logger->error("API:\tupdate:\taccess_token or refresh_token not set.");
			if($this->logger) $this->logger->error("API:\tupdate:\taccess_token:\t".$settings['access_token']);
			if($this->logger) $this->logger->error("API:\tupdate:\trefresh_token:\t".$settings['refresh_token']);
			return false;
		}
	}
	
    /**
     * Send back API object as an Array
     * 
     * 
     * @return <array>
     */
	public function toArray() {
		return array(
			'plugin_api_id'				=>	$this->getPluginApiId(),
			'client_id'					=>	$this->getClientId(),
			'client_secret'				=>	$this->getClientSecret(),
			'access_token'				=>	$this->getAccessToken(),
			'refresh_token'				=>	$this->getRefreshToken(),
			'is_active'					=>	$this->is_active,
			'refresh_token_expires'		=>	$this->getRefreshTokenExpires(),
			'access_token_expires'		=>	$this->getAccessTokenExpires(),
			'show_id'					=>	$this->getShowId(),
			'feed_redirect_url'			=>	$this->getFeedRedirectUrl(),
			'itunes_subscription_url'	=>	$this->getItunesSubscriptionUrl(),
			'creation_date'				=>	$this->getCreationDate(),
			'last_updated'				=>	$this->getLastUpdated(),		
		);
	}
	
	
	
	/* GETTERS OVERRIDE */
	
	public function getPluginApiId() { return (!is_null($this->plugin_api_id))?$this->sanitize->pluginApiId($this->plugin_api_id):null; }
	
	public function getClientId() { return (!is_null($this->client_id))?$this->sanitize->clientId($this->client_id):null; }
	
	public function getClientSecret() { return (!is_null($this->client_secret))?$this->sanitize->clientSecret($this->client_secret):null; }
	
	public function getAccessToken() { return (!is_null($this->access_token))?$this->sanitize->accessToken($this->access_token):null; }
	
	public function getRefreshToken() { return (!is_null($this->refresh_token))?$this->sanitize->refreshToken($this->refresh_token):null; }
	
	public function getFeedRedirectUrl() { return (!is_null($this->feed_redirect_url))?$this->sanitize->feedRedirectUrl($this->feed_redirect_url):null; }
	
	public function getItunesSubscriptionUrl() { return (!is_null($this->itunes_subscription_url))?$this->sanitize->itunesSubscriptionUrl($this->itunes_subscription_url):null; }
	
	public function getIsActive() { return ($this->is_active==1)?true:false; }
	
	public function getRefreshTokenExpires() { return (!is_null($this->refresh_token_expires))?$this->sanitize->refreshTokenExpires($this->refresh_token_expires):null; }
	
	public function getAccessTokenExpires() { return (!is_null($this->access_token_expires))?$this->sanitize->accessTokenExpires($this->access_token_expires):null; }
	
	public function getShowId() { return (!is_null($this->show_id))?$this->sanitize->showId($this->show_id):null; }
	
	public function getCreationDate() { return (!is_null($this->creation_date))?$this->sanitize->creationDate($this->creation_date):null; }
	
	public function getLastUpdated() { return (!is_null($this->last_update))?$this->sanitize->lastUpdated($this->last_updated):null; }
	
	
}

?>