<?php
/**
 * Handles all background proccessing interactions for the Envira Gallery plugin.
 *
 * @since 1.6.0
 *
 * @package Envira Gallery
 * @author  Envira Team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

    exit;

}

if ( ! class_exists( 'Envira_Background_Procces' ) ) :

	final class Envira_Background_Procces{

		/**
		 * instance
		 *
		 * (default value: null)
		 *
		 * @var mixed
		 * @access public
		 * @static
		 */
		public static $_instance = null;

		/**
		 * namespace
		 *
		 * (default value: 'envira')
		 *
		 * @var string
		 * @access public
		 */
		public $domain 	= 'envira';

		/**
		 * version
		 *
		 * (default value: 'v1')
		 *
		 * @var string
		 * @access public
		 */
		public $version 	= 'v1';

		/**
		 * base
		 *
		 * (default value: null)
		 *
		 * @var mixed
		 * @access public
		 */
		public $base = null;
		public $common = null;
		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		function __construct(){

			$this->base = Envira_Gallery::get_instance();

			add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );

		}

		/**
		 * register_api_routes function.
		 *
		 * @access public
		 * @return void
		 */
		function register_api_routes(){

			$name 	 = $this->domain;
			$version = $this->version;

			register_rest_route(
				$name .'/' . $version ,
				'/insert-gallery',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'maybe_insert_gallery' ),
				)
			);
			register_rest_route(
				$name .'/' . $version ,
				'/insert-album',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'maybe_insert_album' ),
				)
			);
			register_rest_route(
				$name .'/' . $version ,
				'/insert-image',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'insert_image' ),
				)
			);
			register_rest_route(
				$name .'/' . $version ,
				'/complete',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'finished_proccessing' ),
				)
			);

			do_action( 'envira_gallery_routes', $name, $version );

		}

		/**
		 * insert_gallery function.
		 *
		 * @access public
		 * @return void
		 */
		function maybe_insert_gallery( WP_REST_Request $request ){

		    //Set the request.
			$this->request = $request;

			//Get the body
	   		$body = $request->get_body_params();

	   		//Validate the request
	   		$valid = $this->validate_request();
	   		
	   		//Setup the ID Var
	   		$post_id = '';
	   		
	   		//Return if request not valid
	   		if ( ! $valid ){

		   		return;

	   		}
	   		
			$common = Envira_Gallery_Common::get_instance();

	   		$defaults = array(
	   			'ID'		  => 0,
	   			'post_type'	  => 'envira',
	   			'post_status' => 'publish',
	   			'post_title'  => ''
	   		);

	   		$post_args = wp_parse_args( $body['data']['gallery'], $defaults );
	   		
	   		if ( isset( $body['data']['id'] ) ){
		   		
	   			$post_id = get_post( $body['data']['id'] );
	   		
	   		}
	   		
	   		if ( ! $post_id ){
	   			
	   			$post = wp_insert_post( $post_args );

	   		} else {

	   			$post = wp_update_post( $post_args );

	   		}
	   		//make a request to insert images is
	   		if ( is_array( $body['data'][ 'images' ] ) ){
		   		
		   		$images = $body['data'][ 'images' ];

		   		foreach( $images as $image => $data ){
		   			error_log( $image );

			   		//Build the Image Data
			   		$image_data = array(
				   		'gallery' => $post,
				   		'image'   => $data,
				   );
	
			   		//Make the background request for inserting each image.
			   		$this->background_request( $image_data, 'insert-image' );
			   		
		   		}
		   		
	   		}

		}

		/**
		 * insert_image function.
		 *
		 * @access public
		 * @return void
		 */
		function insert_image( WP_REST_Request $request ){

		    //Set the request.
			$this->request = $request;

			//Get the body
	   		$body = $request->get_body_params();

	   		//Validate the request
	   		$valid = $this->validate_request();
	   		
	   		//Return if request not valid
	   		if ( ! $valid ){

		   		return;

	   		}
	   		
			//Require if the function doesnt exist
			if( ! function_exists( 'wp_handle_sideload' ) ){
	
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				include( ABSPATH . 'wp-admin/includes/image.php' );
	
			}	   		

	   		$post_id = $body['data']['gallery'];
	   		$image = $body['data']['image'];
	   		
	   		//Check that $post_id is a envira post_type
	   		if( get_post_type( $post_id ) != 'envira' ){

	   			return false;

   			}

   			$in_gallery =  get_post_meta( $post_id, '_eg_in_gallery', true );

		    if ( empty( $in_gallery ) ) {

		        $in_gallery = array();

		    }

		    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );

		    //If Gallery Data is emptyy prepare it.
		    if ( empty( $gallery_data ) ) {

		        $gallery_data = array();
		    }

		    //Set the File name from the API
			$new_attachment = $image['title'];

			//Grab the Upload Directory
			$upload_dir = wp_upload_dir();

			//Set the upload path
			$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

			//Decode the returned image
			$image_upload = file_put_contents( $upload_path . $new_attachment, file_get_contents( $image['src'] ) );

			//Prep the new file
			$file             = array();
			$file['error']    = '';
			$file['tmp_name'] = $upload_path . $new_attachment;
			$file['name']     = $new_attachment;
			$file['type']     = $gallery_data;
			$file['size']     = filesize( $upload_path . $new_attachment );

			$file_return      = wp_handle_sideload( $file, array( 'test_form' => false ) );

			//Setup the Attachment Data
			$attachment = array(
				'post_type'		 => 'attachment',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $new_attachment ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'	 => $post_id
			);

			//Insert new attachment - check
			$attachment_id = wp_insert_attachment( $attachment, $file_return['file'], $post_id );

			//Generate Attachment Metadata
			$meta_data = wp_generate_attachment_metadata( $attachment_id, $file_return['file'] );

			//Update Attachments metadata
			$update_data = wp_update_attachment_metadata( $attachment_id,  $meta_data );

			// Update the attachment image post meta first.
			$has_slider = get_post_meta( $attachment_id, '_eg_has_gallery', true );

			if ( empty( $has_slider ) ) {

				$has_slider = array();

			}

			$has_slider[] = $post_id;

			// Now add the image to the slider for this particular post.
			$in_slider[] = $attachment_id;
			$slider_data = $this->prepare_gallery_data( $gallery_data, $attachment_id );

			// Update the slider data.
			update_post_meta( $attachment_id, '_eg_has_gallery', $has_slider );
			update_post_meta( $post_id, '_eg_in_gallery', $in_slider );
			update_post_meta( $post_id, '_eg_gallery_data', $slider_data );

		}
		
		/**
		 * maybe_insert_album function.
		 * 
		 * @access public
		 * @param WP_REST_Request $request
		 * @return void
		 */
		function maybe_insert_album( WP_REST_Request $request ){
		    //Set the request.
			$this->request = $request;

			//Get the body
	   		$body = $request->get_body_params();

	   		//Validate the request
	   		$valid = $this->validate_request();
	   		
	   		//Setup the ID Var
	   		$post_id = '';
	   		
	   		//Return if request not valid
	   		if ( ! $valid ){

		   		return;

	   		}
	   		
			$common = Envira_Gallery_Common::get_instance();

	   		$defaults = array(
	   			'ID'		  => 0,
	   			'post_type'	  => 'envira-album',
	   			'post_status' => 'publish',
	   			'post_title'  => ''
	   		);

	   		$post_args = wp_parse_args( $body['data']['album'], $defaults );
	   		
	   		if ( isset( $body['data']['id'] ) ){
		   		
	   			$post_id = get_post( $body['data']['id'] );
	   		
	   		}
	   		
	   		if ( ! $post_id ){
	   			
	   			$post = wp_insert_post( $post_args );

	   		} else {

	   			$post = wp_update_post( $post_args );

	   		}

	   		//make a request to insert images is
	   		if ( is_array($body['data'][ 'galleries' ] ) ){
		   		
		   		$images = $body['data'][ 'galleries' ];

		   		foreach( $galleries as $gallery => $data ){

			   		//Build the Image Data
			   		$image_data = array(
				   		'album' => $post,
				   		'galleries'   => $data,
				   );
	
			   		//Make the background request for inserting each image.
			   	//	$this->background_request( $image_data, 'insert-gallery' );
			   		
		   		}
		   		
	   		}			
		}

	    /**
	     * Validates the API request.
	     *
	     * @since 1.0.0
	     *
	     * @return bool True if valid, false otherwise.
	     */
	    public function validate_request() {

		    // Verify the request is comming from soliloquy
		    $referer = isset( $_SERVER['HTTP_REFERER'] ) ? stripslashes( $_SERVER['HTTP_REFERER'] ) : false;
			$site_url = site_url();

		    if ( strpos( $referer, $site_url ) === false ) {

			    //return false;

		    }

		    $verify_nonce = wp_verify_nonce( 'envira-background-processing' );

		    if ( ! $verify_nonce ){

			   // return false;

		    }

		    do_action( 'envira_gallery_validate_bp' );

			// All checks passed.
			return true;

	    }

	    /**
	     * background_request function.
	     *
	     * @access public
	     * @param mixed $data
	     * @param mixed $type
	     * @return void
	     */
	    public function background_request( $data, $type ){
		
		    if ( ! is_array( $data ) || ! isset( $type ) ){
			    return;
		    }
		    
			$name 	 = $this->domain;
			$version = $this->version;
			$rest_url = get_rest_url();
		    $nonce = wp_create_nonce( 'envira-background-processing' );

		    $defaults = array(
				'data' 		=> $data,
				'nonce' 	=> $nonce,
				'site' 		=> get_home_url(),
		    );

			$body = array(
				'data' => wp_parse_args(  $data , $defaults ),
			);

		    $headers = array(
		    	//'X-Envira-Process-Hash' => hash_hmac( 'sha256', $body, $nonce ),
		    );
		    
			switch( $type ){
				case 'insert-gallery':
					$url = trailingslashit( $rest_url ). $name .'/' . $version . '/insert-gallery';
				break;
				case 'insert-album':
					$url = trailingslashit( $rest_url ). $name .'/' . $version . '/insert-album';
				break;
				case 'insert-image':
					$url = trailingslashit( $rest_url ). $name .'/' . $version . '/insert-image';
				break;
			}

		    $args 	 = array(
		    	'headers'     => $headers,
				'body'        => $body,
				'sslverify'   => apply_filters( 'envira_bg_sslverify', false ),
				'blocking'    => false,
				'timeout'	  => 20,
				'httpversion' => '1.1'
			);			
			
			$callit = wp_remote_post( $url, $args );
			
	    }
		/**
		 * Helper function to prepare the metadata for an image in a gallery.
		 *
		 * @since 1.0.0
		 *
		 * @param array $gallery_data	Array of data for the gallery.
		 * @param int	$id				The attachment ID to prepare data for.
		 * @param array $image			Attachment image. Populated if inserting from the Media Library
		 * @return array $gallery_data Amended gallery data with updated image metadata.
		 */
		function prepare_gallery_data( $gallery_data, $id, $image = false ) {
		
			// Get attachment
			$attachment = get_post( $id );
		
			// Depending on whether we're inserting from the Media Library or not, prepare the image array
			if ( ! $image ) {
				$url		= wp_get_attachment_image_src( $id, 'full' );
				$alt_text	= get_post_meta( $id, '_wp_attachment_image_alt', true );
				$new_image = array(
					'status'  => 'active',
					'src'	  => isset( $url[0] ) ? esc_url( $url[0] ) : '',
					'title'	  => get_the_title( $id ),
					'link'	  => ( isset( $url[0] ) ? esc_url( $url[0] ) : '' ),
					'alt'	  => ! empty( $alt_text ) ? $alt_text : '',
					'caption' => ! empty( $attachment->post_excerpt ) ? $attachment->post_excerpt : '',
					'thumb'	  => ''
				);
			} else {
				$new_image = array(
					'status'  => 'active',
					'src'	  => ( isset( $image['src'] ) ? $image['src'] : $image['url'] ),
					'title'	  => $image['title'],
					'link'	  => $image['link'],
					'alt'	  => $image['alt'],
					'caption' => $image['caption'],
					'thumb'	  => '',
				);
			}
		
			// Allow Addons to possibly add metadata now
			$image = apply_filters( 'envira_gallery_ajax_prepare_gallery_data_item', $new_image, $image, $id, $gallery_data );
		
			// If gallery data is not an array (i.e. we have no images), just add the image to the array
			if ( ! isset( $gallery_data['gallery'] ) || ! is_array( $gallery_data['gallery'] ) ) {
				$gallery_data['gallery'] = array();
				$gallery_data['gallery'][ $id ] = $image;
			} else {
				// Add this image to the start or end of the gallery, depending on the setting
				$media_position = $this->get_setting( 'media_position' );
		
				switch ( $media_position ) {
					case 'before':
						// Add image to start of images array
						// Store copy of images, reset gallery array and rebuild
						$images = $gallery_data['gallery'];
						$gallery_data['gallery'] = array();
						$gallery_data['gallery'][ $id ] = $image;
						foreach ( $images as $old_image_id => $old_image ) {
							$gallery_data['gallery'][ $old_image_id ] = $old_image;
						}
						break;
					case 'after':
					default:
						// Add image, this will default to the end of the array
						$gallery_data['gallery'][ $id ] = $image;
						break;
				}
			}
		
			// Filter and return
			$gallery_data = apply_filters( 'envira_gallery_ajax_item_data', $gallery_data, $attachment, $id, $image );
		
			return $gallery_data;
		
		}
		
	    /**
	     * Helper method for getting a setting's value. Falls back to the default
	     * setting value if none exists in the options table.
	     *
	     * @since 1.3.3.6
	     *
	     * @param string $key   The setting key to retrieve.
	     * @return string       Key value on success, false on failure.
	     */
	    public function get_setting( $key ) {
	
	        // Prefix the key
	        $prefixed_key = 'envira_gallery_' . $key;
	
	        // Get the option value
	        $value = get_option( $prefixed_key );
	
	        // If no value exists, fallback to the default
	        if ( ! isset( $value ) ) {
	            $value = $this->get_setting_default( $key );
	        }
	
	        // Allow devs to filter
	        $value = apply_filters( 'envira_gallery_get_setting', $value, $key, $prefixed_key );
	
	        return $value;
	
	    }
	    
		/**
		 * get_instance function.
		 * 
		 * @access public
		 * @static
		 * @return void
		 */
		public static function get_instance(){

			if ( ! isset( self::$_instance ) && ! ( self::$_instance instanceof Envira_Background_Procces ) ) {

				self::$_instance = new Envira_Background_Procces();

			}

			return self::$_instance;
		}

	}

	//start the class
	$envira_background_process = Envira_Background_Procces::get_instance();

endif;
