<?php

/**
* WPV_Cache
*
* Caching class for Views.
*
* This class is useful on a series of scenarios:
* 	- Parametric search with dependencies or counters, including post relationship filters.
* 	- Invalidating stored cache in transients for Views output and meta keys
*
* @since 1.12
*/

class WPV_Cache {
	
	static $stored_cache									= array();
	static $stored_cache_extended_for_post_relationship		= array();
	static $stored_relationship_cache						= array();
	
	static $invalidate_views_cache_flag						= false;
	static $delete_transient_meta_keys_flag					= false;
	static $delete_transient_termmeta_keys_flag				= false;
	static $delete_transient_usermeta_keys_flag				= false;
	
	static $collected_parametric_search_filter_attributes	= array();
	
	function __construct() {
		
		/* 
         * 
         * Invalidate Views cache on these actions 
         * 
         */
        // Invalidation on post and postmeta changes
        add_action( 'transition_post_status',		array( $this, 'invalidate_views_cache' ) );
        add_action( 'save_post',					array( $this, 'invalidate_views_cache' ) );
        add_action( 'delete_post',					array( $this, 'invalidate_views_cache' ) );
        add_action( 'added_post_meta',				array( $this, 'invalidate_views_cache' ) );
        add_action( 'updated_post_meta',			array( $this, 'invalidate_views_cache' ) );
        add_action( 'deleted_post_meta',			array( $this, 'invalidate_views_cache' ) );
        // Invalidation on term changes
        add_action( 'create_term',					array( $this, 'invalidate_views_cache' ) );
        add_action( 'edit_terms',					array( $this, 'invalidate_views_cache' ) );
        add_action( 'delete_term',					array( $this, 'invalidate_views_cache' ) );
        // Invalidation on user and usermeta changes
        add_action( 'user_register',				array( $this, 'invalidate_views_cache' ) );
        add_action( 'profile_update',				array( $this, 'invalidate_views_cache' ) );
        add_action( 'delete_user',					array( $this, 'invalidate_views_cache' ) );
        add_action( 'added_user_meta',				array( $this, 'invalidate_views_cache' ) );
        add_action( 'updated_user_meta',			array( $this, 'invalidate_views_cache' ) );
        add_action( 'deleted_user_meta',			array( $this, 'invalidate_views_cache' ) );
        // Invalidation on Types-related events
        add_action( 'wpcf_save_group',				array( $this, 'invalidate_views_cache' ) );
		add_action( 'wpcf_group_updated',			array( $this, 'invalidate_views_cache' ) );
        // Invalidation on Views-related events
        add_action( 'wpv_action_wpv_save_item',		array( $this, 'invalidate_views_cache_action' ) );
        add_action( 'wpv_action_wpv_import_item',	array( $this, 'invalidate_views_cache' ) );
		
		// Delete the meta keys transients on post and postmeta create/update/delete
		add_action( 'save_post',					array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'delete_post',					array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'added_post_meta',				array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'updated_post_meta',			array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'deleted_post_meta',			array( $this, 'delete_transient_meta_keys' ) );
		// Delete the meta keys transients on term and termmeta create/update/delete
		add_action( 'create_term',					array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'edit_term',					array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'delete_term',					array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'added_term_meta',				array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'updated_term_meta',			array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'deleted_term_meta',			array( $this, 'delete_transient_termmeta_keys' ) );
		// Delete the meta keys transients on user and usermeta create/update/delete
		add_action( 'user_register',				array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'profile_update',				array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'delete_user',					array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'added_user_meta',				array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'updated_user_meta',			array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'deleted_user_meta',			array( $this, 'delete_transient_usermeta_keys' ) );
		// Delete the meta keys transients on Types groups create/update/delete
		// This covers create and update, deleting a meta entry triggers specific actions above
		// Note: The hooks to use here are types_fields_group_saved, and precisely types_fields_group_post_saved, types_fields_group_term_saved and types_fields_group_user_saved
		add_action( 'types_fields_group_saved',		array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'types_fields_group_saved',		array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'types_fields_group_saved',		array( $this, 'delete_transient_usermeta_keys' ) );
		// Note: Both wpcf_save_group and wpcf_group_updated hooks are deprecated at this point, but kept for back and forward compatibility
		add_action( 'wpcf_save_group',				array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'wpcf_group_updated',			array( $this, 'delete_transient_meta_keys' ) );
		add_action( 'wpcf_save_group',				array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'wpcf_group_updated',			array( $this, 'delete_transient_termmeta_keys' ) );
		add_action( 'wpcf_save_group',				array( $this, 'delete_transient_usermeta_keys' ) );
		add_action( 'wpcf_group_updated',			array( $this, 'delete_transient_usermeta_keys' ) );
		// Custom action
		add_action( 'wpv_action_wpv_delete_transient_meta_keys',		array( $this, 'delete_transient_meta_keys_action' ) );
		add_action( 'wpv_action_wpv_delete_transient_termmeta_keys',	array( $this, 'delete_transient_termmeta_keys_action' ) );
		add_action( 'wpv_action_wpv_delete_transient_usermeta_keys',	array( $this, 'delete_transient_usermeta_keys_action' ) );
		
		// Invalidate the shortcodes GUI transient data
		add_action( 'save_post',					array( $this, 'delete_shortcodes_gui_transients_action' ), 10, 2 );
		add_action( 'delete_post',					array( $this, 'delete_shortcodes_gui_transients_action' ), 10 );
		add_action( 'wpv_action_wpv_save_item',		array( $this, 'delete_shortcodes_gui_transients_action' ) );
		// Custom action
		add_action( 'wpv_action_wpv_delete_transient_shortcodes_gui_views',		array( $this, 'delete_shortcodes_gui_views_transient_action' ) );
		add_action( 'wpv_action_wpv_delete_transient_shortcodes_gui_cts',		array( $this, 'delete_shortcodes_gui_cts_transient_action' ) );
		
		// Execution!!!
		add_action( 'shutdown',						array( $this, 'maybe_clear_cache' ) );
	}
	
	/**
	* get_parametric_search_data_to_cache
	*
	* Process the filter_meta_html content, find the wpv-control and wpv-control-set shortcodes and extract their attributes.
	* Transform that data into something that WPV_Cache can use.
	*
	* @param $view_settings			array	The object settings
	* @param $override_settings		array	Additional settings that will override the ones in $view_settings and needed to perform this action:
	* 		'post_type'		array	The post types that the current object will be returning. Needed as WordPress Archives get this on-the-fly.
	*
	* @since 2.1
	*/
	
	static function get_parametric_search_data_to_cache( $view_settings = array(), $override_settings = array() ) {
		$parametric_search_data_to_cache = array(
			'cf' => array(),
			'tax' => array()
		);
		if ( 
			! isset( $view_settings['filter_meta_html'] ) 
			|| (
				strpos( $view_settings['filter_meta_html'], '[wpv-control' ) === false
				&& strpos( $view_settings['filter_meta_html'], '[wpv-control-set' ) === false 
			)
		) {
			return $parametric_search_data_to_cache;
		}
		
		foreach ( $override_settings as $override_key => $override_value ) {
			$view_settings[ $override_key ] = $override_value;
		}
		
		global $shortcode_tags;
		self::$collected_parametric_search_filter_attributes = array();
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		
		add_shortcode( 'wpv-control',		array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		add_shortcode( 'wpv-control-set',	array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		do_shortcode( $view_settings['filter_meta_html'] );
		
		$shortcode_tags = $orig_shortcode_tags;
		
		foreach ( self::$collected_parametric_search_filter_attributes as $atts_set ) {
			if ( isset( $atts_set['ancestors'] ) ) {
				if ( function_exists( 'wpcf_pr_get_belongs' ) ) {
					$returned_post_types = $view_settings['post_type'];
					$returned_post_types = is_array( $returned_post_types ) ? $returned_post_types : array( $returned_post_types );
					$returned_post_type_parents = array();
					if ( empty( $returned_post_types ) ) {
						$returned_post_types = array( 'any' );
					}
					foreach ( $returned_post_types as $returned_post_type_slug ) {
						$parent_parents_array = wpcf_pr_get_belongs( $returned_post_type_slug );
						if ( $parent_parents_array != false && is_array( $parent_parents_array ) ) {
							$returned_post_type_parents = array_merge( $returned_post_type_parents, array_values( array_keys( $parent_parents_array ) ) );
						}
					}
					foreach ( $returned_post_type_parents as $parent_to_cache ) {
						$parametric_search_data_to_cache['cf'][] = '_wpcf_belongs_' . $parent_to_cache . '_id';
					}
				}
			} else if ( isset( $atts_set['taxonomy'] ) ) {
				$parametric_search_data_to_cache['tax'][] = $atts_set['taxonomy'];
			} else if ( isset( $atts_set['auto_fill'] ) ) {
				$parametric_search_data_to_cache['cf'][] = _wpv_get_field_real_slug( $atts_set['auto_fill'] );
			} else if ( isset( $atts_set['field'] ) ) {
				$parametric_search_data_to_cache['cf'][] = _wpv_get_field_real_slug( $atts_set['field'] );
			}
		}
		self::$collected_parametric_search_filter_attributes = array();
		return $parametric_search_data_to_cache;
	}
	
	/**
	* collect_shortcode_attributes
	*
	* Dummy helper callback for collecting shortcode attributes.
	*
	* @since 2.1
	*/
	
	static function collect_shortcode_attributes( $atts, $content = null ) {
		self::$collected_parametric_search_filter_attributes[] = $atts;
		return;
	}
	
	/**
	* generate_native_cache
	*
	* Mimics the caching construction of WordPress so we can use it for counting posts.
	* update_postmeta_cache should get cached data, so we avoind further queries for postmeta.
	* We still need to generate the cache for the given taxonomies.
	*
	* @param $id_posts array of post IDs
	* @param $f_data array of data to pseudo-cache
	*    'tax' => array of taxonomy names to cache
	*
	* @return (array) cached data compatible with the native $wp_object_cache->cache format
	*
	* @uses update_postmeta_cache
	*
	* @since 1.12
	*/

	static function generate_native_cache( $id_posts = array(), $f_data = array() ) {
		$f_taxes = ( isset( $f_data['tax'] ) ) ? $f_data['tax'] : array();
		$cache_post_meta = array();
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );
		
		$cache_post_meta = update_postmeta_cache( $id_posts );
		
		//Then, the taxonomies
		// Note that the settings might be polluted by non-existing taxonomies, so we need to intersect
		$cache_post_taxes = array();
		$current_taxonomies = get_taxonomies( '', 'names' );
		$f_taxes = array_intersect( $f_taxes, $current_taxonomies );
		$f_taxes = array_values( $f_taxes );
		if ( 
			! empty( $f_taxes ) 
			&& ! empty( $id_posts ) 
		) {
			$terms = wp_get_object_terms( $id_posts, $f_taxes, array('fields' => 'all_with_object_id') );
			if ( is_wp_error( $terms ) ) {
				$terms = array();
			}
			$object_terms = array();
			foreach ( (array) $terms as $term ) {
				$object_terms[ $term->object_id ][ $term->taxonomy ][ $term->term_id ] = $term;
			}
			foreach ( $id_posts as $id_needed ) {
				foreach ( $f_taxes as $taxonomy ) {
					if ( ! isset( $object_terms[ $id_needed ][ $taxonomy ] ) ) {
						if ( ! isset( $object_terms[ $id_needed ] ) ) {
							$object_terms[ $id_needed ] = array();
						}
						$object_terms[ $id_needed ][ $taxonomy ] = array();
					}
				}
			}
			foreach ( $object_terms as $post_id => $value ) {
				foreach ( $value as $taxonomy => $terms ) {
					if ( ! isset( $cache_post_taxes[ $taxonomy . '_relationships' ] ) ) {
						$cache_post_taxes[ $taxonomy . '_relationships' ] = array();
					}
					$cache_post_taxes[ $taxonomy . '_relationships' ][ $post_id ] = $terms;
				}
			}
		}
		
		$cache_combined = array();
		$cache_combined['post_meta'] = $cache_post_meta;
		foreach ( $cache_post_taxes as $tax_key => $tax_cached_values ) {
			$cache_combined[ $tax_key ] = $tax_cached_values;
		}
		
		self::$stored_cache = $cache_combined;
		
		return $cache_combined;
	}
	
	static function restart_cache() {
		self::$stored_cache = array();
	}
	
	/**
	* generate_auxiliar_cache
	*
	* Mimics the caching construction of WordPress so we can use it for counting posts.
	* Caches data for the passed custom fields and taxonomies, without adding it to self::$stored_cache
	*
	* @param $id_posts array of post IDs
	* @param $f_data array of data to pseudo-cache
	*    'tax' => array of taxonomy names to cache
	*    'cf'  => array of field meta_key's to cache
	*
	* @return (array) cached data compatible with the native $wp_object_cache->cache format
	*
	* @since 1.12
	*/

	static function generate_auxiliar_cache( $id_posts = array(), $f_data = array() ) {
		$cache_combined = self::$stored_cache;
		$f_fields = ( isset( $f_data['cf'] ) ) ? $f_data['cf'] : array();
		$f_taxes = ( isset( $f_data['tax'] ) ) ? $f_data['tax'] : array();
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );
		$id_posts_postmeta_matched = array();
		$id_posts_postmeta_missed = array();
		// Clear $id_posts from posts already cached
		// Also, make sure $cache_combined['post_meta'] is an array
		if ( 
			isset( $cache_combined['post_meta'] )
			&& is_array( $cache_combined['post_meta'] )
		) {
			$exclude_ids = array_keys( $cache_combined['post_meta'] );
			$id_posts = array_diff( $id_posts, $exclude_ids );
		} else {
			$cache_combined['post_meta'] = array();
		}
		// First, the post_meta
		if ( 
			! empty( $f_fields ) 
			&& ! empty( $id_posts ) 
		) {
			global $wpdb;
			$id_list = implode( ',', $id_posts );
			$f_fields_count = count( $f_fields );
			$f_fields_placeholders = array_fill( 0, $f_fields_count, '%s' );
			$meta_list = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT post_id, meta_key, meta_value 
					FROM {$wpdb->postmeta} 
					WHERE post_id IN ({$id_list}) 
					AND meta_key IN (" . implode( ",", $f_fields_placeholders ) . ") 
					ORDER BY post_id ASC", 
					$f_fields
				),
				ARRAY_A 
			);
			if ( ! empty( $meta_list ) ) {
				foreach ( $meta_list as $metarow) {
					$mpid = intval( $metarow['post_id'] );
					$mkey = $metarow['meta_key'];
					$mval = $metarow['meta_value'];
					if ( ! in_array( $mpid, $id_posts_postmeta_matched ) ) {
						$id_posts_postmeta_matched[] = $mpid;
					}
					if (
						isset( $cache_combined['post_meta'][ $mpid ] )
					) {
						// The post has already been cached, let's check whether its meta key has been cached too
						if ( ! isset( $cache_combined['post_meta'][ $mpid ][ $mkey ] ) ) {
							$cache_combined['post_meta'][ $mpid ][ $mkey ] = array();
							$cache_combined['post_meta'][ $mpid ][ $mkey ][] = $mval;
						}
					} else {
						// We add to the $cache_combined['post_meta']
						$cache_combined['post_meta'][ $mpid ] = array();
						$cache_combined['post_meta'][ $mpid ][ $mkey ] = array();
						$cache_combined['post_meta'][ $mpid ][ $mkey ][] = $mval;
					}
				}
			}
			// Fill the gaps
			$id_posts_postmeta_missed = array_diff( $id_posts, $id_posts_postmeta_matched );
			foreach ( $id_posts_postmeta_missed as $id_needed ) {
				if ( ! isset( $cache_combined['post_meta'][ $id_needed ] ) ) {
					$cache_combined['post_meta'][ $id_needed ] = array();
				}
			}
		}
		
		//Then, the taxonomies
		// Note that the settings might be polluted by non-existing taxonomies, so we need to intersect
		$cache_post_taxes = array();
		$current_taxonomies = get_taxonomies( '', 'names' );
		$f_taxes = array_intersect( $f_taxes, $current_taxonomies );
		$f_taxes = array_values( $f_taxes );
		if ( 
			! empty( $f_taxes ) 
			&& ! empty( $id_posts ) 
		) {
			$terms = wp_get_object_terms( $id_posts, $f_taxes, array('fields' => 'all_with_object_id') );
			if ( is_wp_error( $terms ) ) {
				$terms = array();
			}
			$object_terms = array();
			foreach ( (array) $terms as $term ) {
				$object_terms[ $term->object_id ][ $term->taxonomy ][ $term->term_id ] = $term;
			}
			foreach ( $id_posts as $id_needed ) {
				foreach ( $f_taxes as $taxonomy ) {
					if ( ! isset( $object_terms[ $id_needed ][ $taxonomy ] ) ) {
						if ( ! isset( $object_terms[ $id_needed ] ) ) {
							$object_terms[ $id_needed ] = array();
						}
						$object_terms[ $id_needed ][ $taxonomy ] = array();
					}
				}
			}
			foreach ( $object_terms as $post_id => $value ) {
				foreach ( $value as $taxonomy => $terms ) {
					if ( ! isset( $cache_post_taxes[ $taxonomy . '_relationships' ] ) ) {
						$cache_post_taxes[ $taxonomy . '_relationships' ] = array();
					}
					$cache_post_taxes[ $taxonomy . '_relationships' ][ $post_id ] = $terms;
				}
			}
		}
		foreach ( $cache_post_taxes as $tax_key => $tax_cached_values ) {
			if ( isset( $cache_combined[ $tax_key ] ) ) {
				$cache_combined[ $tax_key ] = self::merge_taxonomy_cache( $cache_combined[ $tax_key ], $tax_cached_values );
			} else {
				$cache_combined[ $tax_key ] = $tax_cached_values;
			}
		}
		
		return $cache_combined;
	}
	
	/**
	* generate_cache
	*
	* Mimics the caching construction of WordPress so we can use it for counting posts.
	* Caches data for the passed custom fields and taxonomies, and adds it to self::$stored_cache
	*
	* @param $id_posts array of post IDs
	* @param $f_data array of data to pseudo-cache
	*    'tax' => array of taxonomy names to cache
	*    'cf'  => array of field meta_key's to cache
	*
	* @uses self::generate_auxiliar_cache
	*
	* @return (array) cached data compatible with the native $wp_object_cache->cache format
	*
	* @since 1.12
	*/

	static function generate_cache( $id_posts = array(), $f_data = array() ) {
		$cache_combined = self::generate_auxiliar_cache( $id_posts, $f_data );
		self::$stored_cache = $cache_combined;
		return $cache_combined;
	}
	
	/**
	* generate_cache_extended_for_post_relationship
	*
	* Mimics the caching construction of WordPress so we can use it for counting posts.
	* Caches data for the passed custom fields and taxonomies, and adds it to self::$stored_cache_extended_for_post_relationship
	* Used when rendering post relationship filters with dependency or counters,
	* as we need to generate a specific query that avoinds the filter by the current post type in the relationship ree, if it exists
	*
	* @param $id_posts array of post IDs
	* @param $f_data array of data to pseudo-cache
	*    'tax' => array of taxonomy names to cache
	*    'cf'  => array of field meta_key's to cache
	*
	* @uses self::generate_auxiliar_cache
	*
	* @return (array) cached data compatible with the native $wp_object_cache->cache format
	*
	* @since 1.12
	*/
	
	static function generate_cache_extended_for_post_relationship( $id_posts = array(), $f_data = array() ) {
		$cache_combined = self::generate_auxiliar_cache( $id_posts, $f_data );
		self::$stored_cache_extended_for_post_relationship = $cache_combined;
		return $cache_combined;
	}
	
	/**
	* generate_post_relationship_tree_cache
	*
	* Generates data for counters and disable/hide elements in a post relationship parametric filter
	*
	* @param $tree		string	greater-than separated list of ancestors, in a top-to-bottom order
	* @param $count		bool	whether the count should return the number of matches or just a true/false statement
	*
	* @return
	*
	* @since 1.12
	*/
	
	static function generate_post_relationship_tree_cache( $tree, $count = true ) {
		$tree_array = explode( '>', $tree );
		$tree_real = array_reverse( $tree_array );
		$tree_remove = array_shift( $tree_real );
		$tree_ground = end( $tree_array );
		$tree_roof = reset( $tree_array );
		$current_post_ids = array();
		$counters = array();
		global $wpdb;
		$cache_combined = self::$stored_cache_extended_for_post_relationship;
		if ( 
			isset( $cache_combined['post_meta'] )
			&& is_array( $cache_combined['post_meta'] )
		) {
			$cached_postmeta = $cache_combined['post_meta'];
			$field = '_wpcf_belongs_' . $tree_ground . '_id';
			foreach ( $cached_postmeta as $key => $value ) {
				if ( isset( $value[ $field ] ) ) {
					$cached_postmeta[ $key ] = $value[ $field ];
				} else {
					unset( $cached_postmeta[ $key ] );
				}
			}
			$current_post_ids = array();
			if ( count( $cached_postmeta ) > 0 ) {
				$current_post_ids = call_user_func_array('array_merge', $cached_postmeta );
			}
			foreach ( $current_post_ids as $cpi ) {
				$meta_criteria_to_filter = array( '_wpcf_belongs_' . $tree_ground . '_id' => array( $cpi ) );
				$data = array();
				$data['list'] = $cache_combined['post_meta'];
				$data['args'] = $meta_criteria_to_filter;
				$data['kind'] = '';
				$data['comparator'] = 'equal';
				$data['count_matches'] = $count;
				$counters[ $cpi ] = array(
					'type'	=> $tree_ground,
					'count'	=> wpv_list_filter_checker( $data )
				);
			}
		}
		foreach ( $tree_real as $tree_branch ) {
			$current_post_ids = array_map( 'esc_attr', $current_post_ids );
			$current_post_ids = array_map( 'trim', $current_post_ids );
			$current_post_ids = array_filter( $current_post_ids, 'is_numeric' );
			$current_post_ids = array_map( 'intval', $current_post_ids );
			if ( count( $current_post_ids ) > 0 ) {
				$future_post_ids = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, meta_value
						FROM {$wpdb->postmeta}
						WHERE meta_key = %s 
						AND post_id IN ('" . implode( "','", $current_post_ids ) . "')",
						'_wpcf_belongs_' . $tree_branch . '_id'
					),
					ARRAY_A
				);
				foreach ( $future_post_ids as $fpid ) {
					$add = isset( $counters[ $fpid['post_id'] ] ) ? $counters[ $fpid['post_id'] ]['count'] : 1;
					if ( ! isset( $counters[ $fpid['meta_value'] ] ) ) {
						$counters[ $fpid['meta_value'] ] = array(
							'type'	=> $tree_branch,
							'count'	=> 0
						);
					}
					$counters[ $fpid['meta_value'] ]['count'] = isset( $counters[ $fpid['meta_value'] ] ) ? ( $counters[ $fpid['meta_value'] ]['count'] + $add ) : $add;
				}
				$current_post_ids = wp_list_pluck( $future_post_ids, 'meta_value' );
			} else {
				$current_post_ids = array();
			}
		}
		self::$stored_relationship_cache = $counters;
		return $counters;
	}
	
	/**
	* Merge the native and auxiliar caches for taxonomies
	*
	* Note that we can not use array_merge as it does not preserve indexes
	* We would get duplicates when the same page contains more than a View because the generate cache is run twice
	*
	* @param $tax_cache_combined	The existing cache
	* @param $tax_cached_values		The newly cached values
	*
	* @since 2.0
	*/
	
	static function merge_taxonomy_cache( $tax_cache_combined, $tax_cached_values ) {
		foreach ( $tax_cached_values as $tax_post_id => $tax_post_terms ) {
			if ( ! isset( $tax_cache_combined[ $tax_post_id ] ) ) {
				// If we already have cached data for this taxonomy and post, we should do nothing
				// Otherwise we add it
				$tax_cache_combined[ $tax_post_id ] = $tax_post_terms;
			}
		}
		return $tax_cache_combined;
	}
	
	/**
	* Invalidate Views first page cache if necessary - store flag
	*  
	* @since 2.0
	*/
	
	function invalidate_views_cache( $p ) {
		self::$invalidate_views_cache_flag = true;
	}
	
	/**
	* Invalidate wpv_transient_meta_keys_*** cache when:
	* 	creating, updating or deleting a post
	* 	creating, updating or deleting a postmeta
	* 	creating, updating or deleting a Types field group
	*
	* This method stores the flag
	*
	* @since 2.0
	*/
	
	function delete_transient_meta_keys() {
		self::$delete_transient_meta_keys_flag = true;
	}
	
	/**
	* Invalidate wpv_transient_termmeta_keys_*** cache when:
	* 	creating, updating or deleting a term
	* 	creating, updating or deleting a termmeta
	* 	creating, updating or deleting a Types field group
	*
	* This method stores the flag
	*
	* @since 2.0
	*/
	
	function delete_transient_termmeta_keys() {
		self::$delete_transient_termmeta_keys_flag = true;
	}
	
	/**
	* Invalidate wpv_transient_meta_keys_*** cache when:
	* 	creating, updating or deleting a user
	* 	creating, updating or deleting a usermeta
	* 	creating, updating or deleting a Types field group
	*
	* This method stores the flag
	*
	* @since 2.0
	*/
	
	function delete_transient_usermeta_keys() {
		self::$delete_transient_usermeta_keys_flag = true;
	}
	
	/**
	* Invalidate wpv_transient_published_*** cache when:
	* 	creating, updating or deleting a View
	* 	creating, updating or deleting a Content Template
	*
	* @todo We might want to use a flag here, not sure
	*
	* @since 2.0
	*/
	
	function delete_shortcodes_gui_transients_action( $post_id, $post = null  ) {
		if ( is_null( $post ) ) {
			$post = get_post( $post_id );
			if ( is_null( $post ) ) {
				return;
			}
		}
		$slugs = array( 'view', 'view-template' );
		if ( ! in_array( $post->post_type, $slugs ) ) {
			return;
		}
		switch ( $post->post_type ) {
			case 'view':
				delete_transient( 'wpv_transient_published_views' );
				break;
			case 'view-template':
				delete_transient( 'wpv_transient_published_cts' );
				break;
			
		}
	}
	
	/**
	* Invalidate wpv_transient_published_views cache manually
	*
	* @since 2.1
	*/
	
	function delete_shortcodes_gui_views_transient_action() {
		delete_transient( 'wpv_transient_published_views' );
	}
	
	/**
	* Invalidate wpv_transient_published_cts cache manually
	*
	* @since 2.1
	*/
	
	function delete_shortcodes_gui_cts_transient_action() {
		delete_transient( 'wpv_transient_published_cts' );
	}
	
	/**
	* Maybe delete cached data on shutdown
	*  
	* @since 2.0
	*/
	
	public function maybe_clear_cache() {
		if ( self::$invalidate_views_cache_flag ) {
			$this->invalidate_views_cache_action();
		}
		if ( self::$delete_transient_meta_keys_flag ) {
			$this->delete_transient_meta_keys_action();
		}
		if ( self::$delete_transient_termmeta_keys_flag ) {
			$this->delete_transient_termmeta_keys_action();
		}
		if ( self::$delete_transient_usermeta_keys_flag ) {
			$this->delete_transient_usermeta_keys_action();
		}
	}
	
	/**
	* Invalidate Views first page cache if necessary
	*  
	* @since 2.0
	*/

    function invalidate_views_cache_action() {
        // Invalidate Views Cache when
        // - A (any post-type) Post is created/updated/trashed/deleted...
        // - A Taxonomy Term has been created/updated/...
        // - An User has been created/updated
        // - A View has been updated
        
        // Remove both [wpv-view] and [wpv-form-view] caches
        $cached_output_index = get_option( 'wpv_transient_view_index', array() );
		foreach( $cached_output_index as $cache_id => $v ) {
			$trasient = 'wpv_transient_view_'.$cache_id;
			delete_transient( $trasient );
		}
        delete_option( 'wpv_transient_view_index' );
        
        $cached_filter_index = get_option( 'wpv_transient_viewform_index', array() );
		foreach( $cached_filter_index as $cache_id => $v ) {
			$trasient = 'wpv_transient_viewform_'.$cache_id;
			delete_transient( $trasient );
		}
        delete_option( 'wpv_transient_viewform_index' );
    }
	
	/**
	* Invalidate wpv_transient_meta_keys_*** cache when:
	* 	creating, updating or deleting a post
	* 	creating, updating or deleting a postmeta
	* 	creating, updating or deleting a Types field group
	*
	* @since 2.0
	*/
	
	function delete_transient_meta_keys_action() {
		delete_transient( 'wpv_transient_meta_keys_visible512' );
		delete_transient( 'wpv_transient_meta_keys_hidden512' );
	}
	
	/**
	* Invalidate wpv_transient_termmeta_keys_*** cache when:
	* 	creating, updating or deleting a term
	* 	creating, updating or deleting a termmeta
	* 	creating, updating or deleting a Types field group
	*
	* @since 2.0
	*/
	
	function delete_transient_termmeta_keys_action() {
		delete_transient( 'wpv_transient_termmeta_keys_visible512' );
		delete_transient( 'wpv_transient_termmeta_keys_hidden512' );
	}
	
	/**
	* Invalidate wpv_transient_meta_keys_*** cache when:
	* 	creating, updating or deleting a user
	* 	creating, updating or deleting a usermeta
	* 	creating, updating or deleting a Types field group
	*
	* @since 2.0
	*/
	
	function delete_transient_usermeta_keys_action() {
		delete_transient( 'wpv_transient_usermeta_keys_visible512' );
		delete_transient( 'wpv_transient_usermeta_keys_hidden512' );
	}
	
}

$WPV_Cache = new WPV_Cache();