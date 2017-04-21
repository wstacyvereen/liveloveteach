<?php

/**
* Author frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Author_Frontend_Filter::on_load();

/**
* WPV_Author_Filter
*
* Views Author Filter Frontend Class
*
* @since 2.1
*/

class WPV_Author_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post author
        add_filter( 'wpv_filter_query',										array( 'WPV_Author_Frontend_Filter', 'filter_post_author' ), 13, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Author_Frontend_Filter', 'archive_filter_post_author' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Author_Frontend_Filter', 'requires_current_page' ), 20, 2 );
		add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Author_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_current_user',						array( 'WPV_Author_Frontend_Filter', 'requires_current_user' ), 20, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Author_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		add_filter( 'wpv_filter_requires_parent_user',						array( 'WPV_Author_Frontend_Filter', 'requires_parent_user' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Author_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Author_Frontend_Filter', 'url_parameters' ), 10, 2 );
    }
	
	/**
	* filter_post_author
	*
	* Apply the filter by post author on Views.
	*
	* @since unknown
	*/
	
	static function filter_post_author( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['author_mode'][0] ) ) {
			$show_author_array = WPV_Author_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
			if ( isset( $show_author_array ) ) { // only modify the query if the URL parameter is present and not empty
				if ( count( $show_author_array ) > 0 ) {
					// $query['author'] must be a string like 'id1,id2,id3'
					// because we're using &get_posts() to run the query
					// and it doesn't accept an array as author parameter
					$show_author_list = implode( ",", $show_author_array );
					if ( isset( $query['author'] ) ) {
						$query['author'] = implode( ",", array_merge( (array) $query['author'], $show_author_array ) );
					} else {
						$query['author'] = implode( ",", $show_author_array );
					}
				} else {
					// this only happens when:
					// - auth_mode = current_user and user is not logged in
					// - auth_mode = by_url and no numeric id or valid nicename is given
					// we need to return an empty query
					$query['post__in'] = array( '0' );
				}
			}
		}
		return $query;
	}
	
	/**
	* archive_filter_post_author
	*
	* Apply the filter by post author on WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_author( $query, $archive_settings, $archive_id ) {
		if (
			$query->is_archive 
			&& $query->is_author 
		) {
			// Do not apply on author archive pages
			return;
		}
		if ( isset( $archive_settings['author_mode'][0] ) ) {
			$show_author_array = WPV_Author_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id );
			if ( isset( $show_author_array ) ) {
				if ( count( $show_author_array ) > 0 ) {
					$show_author = implode( ",", $show_author_array );
					$query->set('author', $show_author );
				} else {
					// this only happens when:
					// - auth_mode = current_user and user is not logged in
					// - auth_mode = by_url and no numeric id or valid nicename is given
					// we need to return an empty query
					$query->set('post__in', array( 0 ) );
				}
			}
		}
	}
	
	/**
	* get_settings
	*
	* Auxiliar method to get the author filter frontend data.
	*
	* @since 2.1
	*/
	
	static function get_settings( $query, $view_settings, $view_id ) {
		$show_author_array = array();
		switch ( $view_settings['author_mode'][0] ) {
			case 'top_current_post':
				$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
				if ( $current_page ) {
					$show_author_array[] = $current_page->post_author;
				}
				break;
			case 'current_page': // @deprecated in 1.12.1
			case 'current_post_or_parent_post_view':
				$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
				if ( $current_page ) {
					$show_author_array[] = $current_page->post_author;
				}
				break;
			case 'current_user':
				global $current_user;
				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
					$show_author_array[] = $current_user->ID; // set the array to only the current user ID if is logged in
				}
				break;
			case 'this_user':
				if (
					isset( $view_settings['author_id'] ) 
					&& is_numeric( $view_settings['author_id'] )
					&& $view_settings['author_id'] > 0
				) {
					$show_author_array[] = $view_settings['author_id']; // set the array to only the selected user ID
				}
				break;
			case 'parent_view': // @deprecated in 1.12.1
			case 'parent_user_view':
				$parent_user_id = apply_filters( 'wpv_filter_wpv_get_parent_view_user', null );
				if ( $parent_user_id ) {
					$show_author_array[] = $parent_user_id;
				}
				break;
			case 'by_url':
				if (
					isset( $view_settings['author_url'] ) 
					&& '' != $view_settings['author_url']
					&& isset( $view_settings['author_url_type'] ) 
					&& '' != $view_settings['author_url_type']
				) {
					$author_parameter = $view_settings['author_url'];
					$author_url_type = $view_settings['author_url_type'];
					if ( isset( $_GET[$author_parameter] ) ) {
						$authors_to_load = $_GET[$author_parameter];
						if ( is_string( $authors_to_load ) ) {
							$authors_to_load = explode( ',', $authors_to_load );
						}
						if ( 1 == count( $authors_to_load ) ) {
							$authors_to_load = explode( ',', $authors_to_load[0] ); // fix on the pagination for the author filter
						}
						if ( 
							0 == count( $authors_to_load ) 
							|| '' == $authors_to_load[0] 
						) {
							// The URL parameter is empty
							$show_author_array = null;
						} else {
							// The URL parameter is not empty
							switch ( $author_url_type ) {
								case 'id':
									foreach ( $authors_to_load as $id_author_to_load ) {
										if ( is_numeric( $id_author_to_load ) ) { // if ID expected and not a number, skip it
											$show_author_array[] = $id_author_to_load; // if ID expected and is a number, add it to the array
										}
									}
									break;
								case 'username':
									foreach ( $authors_to_load as $username_author_to_load ) {
										$username_author_to_load = strip_tags( $username_author_to_load );
										$author_username_id = username_exists( $username_author_to_load );
										if ($author_username_id) {
											$show_author_array[] = $author_username_id; // if user exists, add it to the array
										}
									}
									break;
							}
						}
					} else {
						$show_author_array = null; // if the URL parameter is missing
					}
				}
				break;
			case 'shortcode':
				if (
					isset( $view_settings['author_shortcode'] ) 
					&& '' != $view_settings['author_shortcode']
					&& isset( $view_settings['author_shortcode_type'] ) 
					&& '' != $view_settings['author_shortcode_type']
				) {
					global $WP_Views;
					$author_shortcode = $view_settings['author_shortcode'];
					$author_shortcode_type = $view_settings['author_shortcode_type'];
					$view_attrs = $WP_Views->get_view_shortcodes_attributes();
					if ( 
						isset( $view_attrs[$author_shortcode] ) 
						&& '' != $view_attrs[$author_shortcode]
					) {
						$author_candidates = explode( ',', $view_attrs[$author_shortcode] );
						switch ( $author_shortcode_type ) {
							case 'id':
								foreach ( $author_candidates as $id_candid ) {
									$id_candid = trim( strip_tags( $id_candid ) );
									if ( is_numeric( $id_candid ) ) {
										$show_author_array[] = $id_candid;
									}
								}
								break;
							case 'username':
								foreach ( $author_candidates as $username_candid ) {
									$username_candid = trim( strip_tags( $username_candid ) );
									$username_candid_id = username_exists( $username_candid );
									if ( $username_candid_id ) {
										$show_author_array[] = $username_candid_id;
									}
								}						
								break;			
						}
					} else {
						$show_author_array = null;
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['author_framework'] ) 
						&& '' != $view_settings['author_framework']
						&& isset( $view_settings['author_framework_type'] ) 
						&& '' != $view_settings['author_framework_type']
					) {
						$author_framework = $view_settings['author_framework'];
						$author_framework_type = $view_settings['author_framework_type'];
						$author_candidates = $WP_Views_fapi->get_framework_value( $author_framework, array() );
						if ( ! is_array( $author_candidates ) ) {
							$author_candidates = explode( ',', $author_candidates );
						}
						$author_candidates = array_map( 'trim', $author_candidates );
						switch ( $author_framework_type ) {
							case 'id':
								foreach ( $author_candidates as $id_candid ) {
									if ( is_numeric( $id_candid ) ) {
										$show_author_array[] = $id_candid;
									}
								}
								break;
							case 'username':
								foreach ( $author_candidates as $username_candid ) {
									$username_candid = trim( strip_tags( $username_candid ) );
									// username_exists adds the sanitization
									$username_candid_id = username_exists( $username_candid );
									if ( $username_candid_id ) {
										$show_author_array[] = $username_candid_id;
									}
								}
								break;			
						}
					}
				} else {
					$show_author_array = null;
				}
				break;
		}
		return $show_author_array;
	}
	
	/**
	* requires_current_page
	*
	* Whether the current View requires the top current post data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.6.2
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_current_page( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( 
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& $view_settings['author_mode'][0] == 'top_current_post' 
		) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* requires_parent_post
	*
	* Whether the current View requires the current post data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.6.2
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_parent_post( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( 
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& in_array( $view_settings['author_mode'][0], array( 'current_page', 'current_post_or_parent_post_view' ) ) 
		) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* requires_current_user
	*
	* Whether the current View requires the current user data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_current_user( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( 
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& $view_settings['author_mode'][0] == 'current_user' 
		) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* requires_framework_values
	*
	* Whether the current View requires framework data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( 
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& $view_settings['author_mode'][0] == 'framework' 
		) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* requires_parent_user
	*
	* Whether the current View is nested and requires the user set by the parent View for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.9.0
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_parent_user( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		if ( 
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& in_array( $view_settings['author_mode'][0], array( 'parent_view', 'parent_user_view' ) )
		) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* shortcode_attributes
	*
	* Register the filter by post author on the method to get View shortcode attributes
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/
	
	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& $view_settings['author_mode'][0] == 'shortcode' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_author',
				'filter_label'	=> __( 'Post author', 'wpv-views' ),
				'value'			=> $view_settings['author_shortcode_type'],
				'attribute'		=> $view_settings['author_shortcode'],
				'expected'		=> ( $view_settings['author_shortcode_type'] == 'id' ) ? 'numberlist' : 'string',
				'placeholder'	=> ( $view_settings['author_shortcode_type'] == 'id' ) ? '1, 2' : 'admin, john',
				'description'	=> ( $view_settings['author_shortcode_type'] == 'id' ) ? __( 'Please type a comma separated list of author IDs', 'wpv-views' ) : __( 'Please type a comma separated list of author usernames', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
	/**
	* url_parameters
	*
	* Register the filter by post author on the method to get URL parameters
	*
	* @since 1.11
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['author_mode'] ) 
			&& isset( $view_settings['author_mode'][0] ) 
			&& $view_settings['author_mode'][0] == 'by_url' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_author',
				'filter_label'	=> __( 'Post author', 'wpv-views' ),
				'value'			=> $view_settings['author_url_type'],
				'attribute'		=> $view_settings['author_url'],
				'expected'		=> ( $view_settings['author_url_type'] == 'id' ) ? 'numberlist' : 'string',
				'placeholder'	=> ( $view_settings['author_url_type'] == 'id' ) ? '1, 2' : 'admin, john',
				'description'	=> ( $view_settings['author_url_type'] == 'id' ) ? __( 'Please type a comma separated list of author IDs', 'wpv-views' ) : __( 'Please type a comma separated list of author usernames', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
}