<?php

/**
* Post Relationship frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Post_Relationship_Frontend_Filter::on_load();

/**
* WPV_Parent_Frontend_Filter
*
* Views Post Relationship Filter Frontend Class
*
* @since 2.1
*/

class WPV_Post_Relationship_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post relationship
        add_filter( 'wpv_filter_query',										array( 'WPV_Post_Relationship_Frontend_Filter', 'filter_post_relationship' ), 11, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Post_Relationship_Frontend_Filter', 'archive_filter_post_relationship' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Post_Relationship_Frontend_Filter', 'requires_current_page' ), 10, 2 );
		add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Post_Relationship_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Post_Relationship_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Post_Relationship_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Post_Relationship_Frontend_Filter', 'url_parameters' ), 10, 2 );
		// Extra methods
		add_action( 'wpv-before-display-post',								array( 'WPV_Post_Relationship_Frontend_Filter', 'wpv_before_display_post_post_relationship' ), 10, 2 );
    }
	
	/**
	* filter_post_relationship
	*
	* Add the filter by post relationship to the $query
	*
	* This function adds the filter by post relationship to the $query.
	* It uses an additional auxiliary query, because post relationships are stored in custom fields, and we need to intersect those two filters.
	* It usually takes a parent ID to execute the filter, but when filtering by URL parameter we must accept multiple parent IDs.
	*
	* @param $query
	* @param $view_settings
	*
	* @return $query
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_post_relationship and moved to a static method
	*/
	
	static function filter_post_relationship( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['post_relationship_mode'][0] ) ) {
			$post_relationship_query = WPV_Post_Relationship_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
			if ( count( $post_relationship_query['post__in'] ) > 0 ) {
				if ( isset( $query['post__in'] ) ) {
					$query['post__in'] = array_intersect( (array) $query['post__in'], $post_relationship_query['post__in'] );
					$query['post__in'] = array_values( $query['post__in'] );
					if ( empty( $query['post__in'] ) ) {
						$query['post__in'] = array( '0' );
					}
				} else {
					$query['post__in'] = $post_relationship_query['post__in'];
				}
			}
			if ( count( $post_relationship_query['pr_filter_post__in'] ) > 0 ) {
				$query['pr_filter_post__in'] = $post_relationship_query['pr_filter_post__in'];
			}
		}
		return $query;
	}
	
	/**
	* archive_filter_post_relationship
	*
	* Apply the post relationship filter to WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_relationship( $query, $archive_settings, $archive_id ) {
		if ( isset( $archive_settings['post_relationship_mode'][0] ) ) {
			$post_relationship_query = WPV_Post_Relationship_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id );
			if ( count( $post_relationship_query['post__in'] ) > 0 ) {
				$post__in = $query->get( 'post__in' );
				$post__in = isset( $post__in ) ? $post__in : array();
				if ( count( $post__in ) > 0 ) {
					$post__in = array_intersect( (array) $post__in, $post_relationship_query['post__in'] );
					$post__in = array_values( $post__in );
					if ( empty( $post__in ) ) {
						$post__in = array( '0' );
					}
					$query->set( 'post__in', $post__in );
				} else {
					$query->set( 'post__in', $post_relationship_query['post__in'] );
				}
			}
			if ( count( $post_relationship_query['pr_filter_post__in'] ) > 0 ) {
				$query->set( 'pr_filter_post__in', $post_relationship_query['pr_filter_post__in'] );
			}
		}
	}
	
	/**
	* get_settings
	*
	* Get settings for the query filter by post relationship.
	*
	* @since 2.1
	*/
	
	static function get_settings( $query, $view_settings, $view_id ) {
		global $wpdb;
		$post_relationship_query = array(
			'post__in'				=> array(),
			'pr_filter_post__in'	=> array()
			
		);
		$post_owner_id = 0; // the parent ID when it is just one
		$post_owner_data = array(); // we will store the data (parent ID and post_type) here to perform the auxiliar wp_query
		
		$returned_post_types = WPV_Post_Relationship_Frontend_Filter::get_returned_post_types( $view_settings );
		
		switch ( $view_settings['post_relationship_mode'][0] ) {
			case 'current_page': // @deprecated in 1.12.1
			case 'top_current_post':
				$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
				if ( is_archive() ) {
					// For archive pages, the "current page" as "post where this View is inserted" is this
					// @todo check if this is also needed for flters by post author, post parent or post taxonomy
					$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
				}
				if ( $current_page ) {
					$post_owner_id = $current_page->ID;
				}
				if ( $post_owner_id > 0 ) {
					$post_type = $wpdb->get_var( 
						$wpdb->prepare( 
							"SELECT post_type FROM {$wpdb->posts} 
							WHERE ID = %d 
							LIMIT 1", 
							$post_owner_id 
						) 
					);
					$post_owner_data[$post_type][] = $post_owner_id;
				}
				break;
			case 'parent_view': // @deprecated in 1.12.1
			case 'current_post_or_parent_post_view':
				$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
				if ( $current_page ) {
					$post_owner_id = $current_page->ID;
				}
				if ( $post_owner_id > 0 ) {
					$post_type = $wpdb->get_var( 
						$wpdb->prepare( 
							"SELECT post_type FROM {$wpdb->posts} 
							WHERE ID = %d 
							LIMIT 1", 
							$post_owner_id 
						) 
					);
					$post_owner_data[$post_type][] = $post_owner_id;
				}
				break;
			case 'this_page':
				if (
					isset( $view_settings['post_relationship_id'] ) 
					&& intval( $view_settings['post_relationship_id'] ) > 0
				) {
					$post_owner_id = intval( $view_settings['post_relationship_id'] );
					$post_owner_id_type = $wpdb->get_var( 
						$wpdb->prepare( 
							"SELECT post_type FROM {$wpdb->posts} 
							WHERE ID = %d 
							LIMIT 1", 
							$post_owner_id 
						) 
					);
					// Adjust for WPML support
					$post_owner_id = apply_filters( 'translate_object_id', $post_owner_id, $post_owner_id_type, true, null );
					$post_owner_data[$post_owner_id_type][] = $post_owner_id;
				}
				break;
			case 'shortcode_attribute':
				if (
					isset( $view_settings['post_relationship_shortcode_attribute'] ) 
					&& '' != $view_settings['post_relationship_shortcode_attribute']
				) {
					$post_relationship_shortcode = $view_settings['post_relationship_shortcode_attribute'];
					$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
					if ( 
						isset( $view_attrs[$post_relationship_shortcode] ) 
						&& intval( $view_attrs[$post_relationship_shortcode] ) > 0
					) {
						$post_owner_id = intval( $view_attrs[$post_relationship_shortcode] );
						$post_owner_id_type = $wpdb->get_var( 
							$wpdb->prepare( 
								"SELECT post_type FROM {$wpdb->posts} 
								WHERE ID = %d 
								LIMIT 1", 
								$post_owner_id 
							) 
						);
						// Adjust for WPML support
						$post_owner_id = apply_filters( 'translate_object_id', $post_owner_id, $post_owner_id_type, true, null );
						$post_owner_data[$post_owner_id_type][] = $post_owner_id;
					}
				}
				break;
			case 'url_parameter':
				if (
					isset( $view_settings['post_relationship_url_parameter'] ) 
					&& '' != $view_settings['post_relationship_url_parameter']
				) {
					$post_relationship_url_parameter = $view_settings['post_relationship_url_parameter'];
					if ( isset( $_GET[$post_relationship_url_parameter] ) 
						&& $_GET[$post_relationship_url_parameter] != array( 0 ) 
						&& $_GET[$post_relationship_url_parameter] != 0 
					) {
						$post_owner_ids_from_url = $_GET[$post_relationship_url_parameter];
						$post_owner_ids_sanitized = array();
						if ( is_array( $post_owner_ids_from_url ) ) {
							foreach ( $post_owner_ids_from_url as $id_value ) {
								$id_value = (int) esc_attr( trim( $id_value ) );
								if ( $id_value > 0 ) {
									$post_owner_ids_sanitized[] = $id_value;
								}
							}
						} else {
							$post_owner_ids_from_url = (int) esc_attr( $post_owner_ids_from_url );
							if ( $post_owner_ids_from_url > 0 ) {
								$post_owner_ids_sanitized[] = $post_owner_ids_from_url;
							}
						}
						if ( count( $post_owner_ids_sanitized ) ) {
							// We do not need to prepare this query as $post_owner_ids_sanitized only contains numeric natural IDs
							$post_types_from_url = $wpdb->get_results( 
								"SELECT ID, post_type FROM {$wpdb->posts} 
								WHERE ID IN ('" . implode("','", $post_owner_ids_sanitized) . "')" 
							);
							foreach ( $post_types_from_url as $ptfu_key => $ptfu_values ) {
								$post_owner_id_item = $ptfu_values->ID;
								// Adjust for WPML support
								$post_owner_id_item = apply_filters( 'translate_object_id', $post_owner_id_item, $ptfu_values->post_type, true, null );
								$post_owner_data[$ptfu_values->post_type][] = $post_owner_id_item;
							}
						}
					} else if ( function_exists( 'wpcf_pr_get_belongs' ) ) {
						/*
						1. get the returned post type parents
						2. get the tree applied here, will be stored in $view_settings['post_relationship_url_tree']
						3. reverse the tree so the real parent is the first one now; this parent has no value since the url param is not set
						4. get up in the tree until you find the first one element with a value
							4.1 if we get to the latest ancestor and even it does not hold any value, there is no value at all so filter by nothing: $post_owner_data = array() empty
							4.2 if we get to an ancestor with value, filter the last ancestor by the value of this one
							4.3 go down the tree following this filter until the real parent and populate the $post_owner_data[real-parent-slug]
						*/
						
						/*
						1
						*/
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
						
						/*
						2 + 3
						*/
						if ( isset( $view_settings['post_relationship_url_tree'] ) ) {
							$relationship_tree = $view_settings['post_relationship_url_tree'];
						} else {
							$relationship_tree = '';
						}
						$relationship_tree_array = array_reverse( explode( '>', $relationship_tree ) );
						
						$tree_root = end( $relationship_tree_array );
						$tree_ground = reset( $relationship_tree_array );
						
						if ( $tree_root && $tree_ground 
							&& isset( $_GET[$post_relationship_url_parameter . '-' . $tree_root] ) 
							&& !empty( $_GET[$post_relationship_url_parameter . '-' . $tree_root] ) 
							&& $_GET[$post_relationship_url_parameter . '-' . $tree_root] != array( 0 ) 
							&& $_GET[$post_relationship_url_parameter . '-' . $tree_root] != 0  
						) {
							// There are influencer values: let's get the last one
							$ancestor_influence = array();
							$starting_key = 0;
							array_shift( $relationship_tree_array ); // take out the first element as it is a real parent and has no value
							foreach ( $relationship_tree_array as $tree_key => $tree_ancestor ) {
								if ( $tree_ancestor != $tree_ground ) { // just check ancestors that are not the direct parent, as is has no value
							//	if ( !in_array( $tree_ancestor, $returned_post_type_parents ) ) { // just check ancestors that are not direct parents, as they have no value
									if ( isset( $_GET[$post_relationship_url_parameter . '-' . $tree_ancestor] ) && !empty( $_GET[$post_relationship_url_parameter . '-' . $tree_ancestor] ) && $_GET[$post_relationship_url_parameter . '-' . $tree_ancestor] != array( 0 ) ) {
										// This ancestor has a value. Yay!
										$post_owner_ids_from_url = $_GET[$post_relationship_url_parameter . '-' . $tree_ancestor];
										$post_owner_ids_sanitized = array();
										if ( is_array( $post_owner_ids_from_url ) ) {
											foreach ( $post_owner_ids_from_url as $id_key => $id_value ) {
												$id_value = (int) esc_attr( trim( $id_value ) );
												if ( $id_value > 0 ) {
													$post_owner_ids_sanitized[$id_key] = $id_value;
												}
											}
										} else {
											$post_owner_ids_from_url = (int) esc_attr( $post_owner_ids_from_url );
											if ( $post_owner_ids_from_url > 0 ) {
												$post_owner_ids_sanitized[] = $post_owner_ids_from_url;
											}
										}
										$ancestor_influence[$tree_ancestor] = array(
											'key' => $tree_key,
											'ids' => $post_owner_ids_sanitized
										);
										$starting_key = $tree_key;
										break;
									}
								}
							}
							if ( !empty( $ancestor_influence ) ) { // Should not be empty, but better check anyway
								$ancestor_influence = array_slice( $ancestor_influence, 0, 1 ); // It shuold have just one value, but check it anyway
								$i = 0;
								$no_results = false;
								while ( $i < $tree_key ) {
									$this_key = $tree_key - $i;
									if ( $this_key > 0 ) {
										$current_post_type = $relationship_tree_array[$this_key-1];
									} else {
										$current_post_type = $tree_ground;
									}
									$current_influencer = end( $ancestor_influence );
									$query_here = array();
									$query_here['posts_per_page'] = -1;
									$query_here['paged'] = 1;
									$query_here['offset'] = 0;
									$query_here['fields'] = 'ids';
									$query_here['cache_results'] = false;
									$query_here['update_post_meta_cache'] = false;
									$query_here['update_post_term_cache'] = false;
									$query_here['post_type'] = $current_post_type;
									$query_here['meta_query'][] = array(
										'key' => '_wpcf_belongs_' . $relationship_tree_array[$this_key] . '_id',
										'value' => $current_influencer['ids']
									);
									$aux_relationship_query = new WP_Query( $query_here );
									if ( is_array( $aux_relationship_query->posts ) && count( $aux_relationship_query->posts ) ) {
										$ancestor_influence[$current_post_type] = array(
											'key' => $this_key-1,
											'ids' => $aux_relationship_query->posts
										);
										$i++;
									} else {
										$no_results = true;
										break;
									}
									$i++;
								}
								if ( $no_results ) {
									// Along the intermediate filters, no posts were returned
									$post_relationship_query['post__in'] = array( '0' );
								} else {
									$real_parent_filter = end( $ancestor_influence );
									$query_here = array();
									$query_here['posts_per_page'] = -1;
									$query_here['paged'] = 1;
									$query_here['offset'] = 0;
									$query_here['fields'] = 'ids';
									$query_here['cache_results'] = false;
									$query_here['update_post_meta_cache'] = false;
									$query_here['update_post_term_cache'] = false;
									$query_here['post_type'] = $tree_ground;
									$query_here['meta_query'][] = array(
										'key' => '_wpcf_belongs_' . $relationship_tree_array[0] . '_id',
										'value' => $real_parent_filter['ids']
									);
									$aux_relationship_query = new WP_Query( $query_here );
									if ( is_array( $aux_relationship_query->posts ) && count( $aux_relationship_query->posts ) ) {
										$post_owner_data[$tree_ground] = $aux_relationship_query->posts;
									} else {
										// Just on the late filter, no posts were returned
										$post_relationship_query['post__in'] = array( '0' );
									}
								}
							}
						} else {
							// There are no values set, so filter by nothing
							// $post_owner_data = array() already;
						}
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['post_relationship_framework'] ) 
						&& '' != $view_settings['post_relationship_framework']
					) {
						$post_relationship_framework = $view_settings['post_relationship_framework'];
						$post_relationship_candidates = $WP_Views_fapi->get_framework_value( $post_relationship_framework, array() );
						if ( ! is_array( $post_relationship_candidates ) ) {
							$post_relationship_candidates = explode( ',', $post_relationship_candidates );
						}
						$post_relationship_candidates = array_map( 'esc_attr', $post_relationship_candidates );
						$post_relationship_candidates = array_map( 'trim', $post_relationship_candidates );
						// is_numeric does sanitization
						$post_relationship_candidates = array_filter( $post_relationship_candidates, 'is_numeric' );
						$post_relationship_candidates = array_map( 'intval', $post_relationship_candidates );
						if ( count( $post_relationship_candidates ) ) {
							// We do not need to prepare this query as $post_relationship_candidates only contains numeric natural IDs
							$post_types_from_framework = $wpdb->get_results( 
								"SELECT ID, post_type FROM {$wpdb->posts} 
								WHERE ID IN ('" . implode("','", $post_relationship_candidates) . "')" 
							);
							foreach ( $post_types_from_framework as $ptfu_key => $ptfu_values ) {
								$post_owner_id_item = $ptfu_values->ID;
								// Adjust for WPML support
								$post_owner_id_item = apply_filters( 'translate_object_id', $post_owner_id_item, $ptfu_values->post_type, true, null );
								$post_owner_data[$ptfu_values->post_type][] = $post_owner_id_item;
							}
						}
					}
				}
				break;
		}
		if ( ! empty( $post_owner_data ) ) {
			$query_here = array();
			$query_here['posts_per_page'] = -1;
			$query_here['paged'] = 1;
			$query_here['offset'] = 0;
			$query_here['post_type'] = 'any';
			$query_here['fields'] = 'ids';
			$query_here['cache_results'] = false;
			$query_here['update_post_meta_cache'] = false;
			$query_here['update_post_term_cache'] = false;
			$query_here['post_type'] = $returned_post_types;
			$query_here['meta_query']['relation'] = 'AND';
			// Set the post status, although I am not sure tis is needed as we do have the post status filter anyway
			// @todo use this for setting the 'post_type' query argument, depending on the current $query and $view_settings
			$query_here = apply_filters( 'wpv_filter_wpv_filter_auxiliar_post_relationship_query', $query_here, $view_settings, $view_id );
			foreach ( $post_owner_data as $type => $ides ) {
				$query_here['meta_query'][] = array(
					'key' => '_wpcf_belongs_' . $type . '_id',
					'value' => $ides,
				);
			}
			$aux_relationship_query = new WP_Query( $query_here );
			
			if ( is_array( $aux_relationship_query->posts ) ) {
				if ( count( $aux_relationship_query->posts ) > 0 ) {
					if ( count( $post_relationship_query['post__in'] ) > 0 ) {
						$post_relationship_query['post__in'] = array_intersect( (array) $post_relationship_query['post__in'], $aux_relationship_query->posts );
						$post_relationship_query['post__in'] = array_values( $post_relationship_query['post__in'] );
						if ( empty( $post_relationship_query['post__in'] ) ) {
							$post_relationship_query['post__in'] = array( '0' );
						}
					} else {
						$post_relationship_query['post__in'] = $aux_relationship_query->posts;
					}
					$post_relationship_query['pr_filter_post__in'] = $aux_relationship_query->posts;
				} else {
					$post_relationship_query['post__in'] = array( '0' );
				}
			}
		}
		return $post_relationship_query;
	}
	
	static function requires_current_page( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		if ( isset( $view_settings['post_relationship_mode'][0] ) ) {   
			if ( in_array( $view_settings['post_relationship_mode'][0], array( 'current_page', 'top_current_post' ) ) ) {
				$state = true;
			}
		}
		return $state;
	}
	
	static function requires_parent_post( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		if ( isset( $view_settings['post_relationship_mode'][0] ) ) {   
			if ( in_array( $view_settings['post_relationship_mode'][0], array( 'parent_view', 'current_post_or_parent_post_view' ) ) ) {
				$state = true;
			}
		}
		return $state;
	}
	
	/**
	* requires_framework_values
	*
	* Check if the current filter by post relationship needs info about the framework values
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_post_relationship_requires_framework_values and moved to a proper static method
	*/
	
	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( isset( $view_settings['post_relationship_mode'][0] ) ) {
			if ( $view_settings['post_relationship_mode'][0] == 'framework' ) {
				$state = true;
			}
		}
		return $state;
	}
	
	/**
	* shortcode_attributes
	*
	* Register the filter by post relationship on the method to get View shortcode attributes
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_register_post_relationship_shortcode_attributes and moved to a proper static method
	*/
	
	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_relationship_mode'] ) 
			&& isset( $view_settings['post_relationship_mode'][0] ) 
			&& $view_settings['post_relationship_mode'][0] == 'shortcode_attribute' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_relationship',
				'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
				'value'			=> 'ancestor_id',
				'attribute'		=> $view_settings['post_relationship_shortcode_attribute'],
				'expected'		=> 'number',
				'placeholder'	=> '103',
				'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
	/**
	 * Register the filter by post relationship on the method to get View URL parameters.
	 *
	 * @since 1.11.0
	 * @since 2.1.0	Renamed from wpv_filter_register_post_relationship_url_parameters and moved to a static method.
	 * @since 2.3.0 Ensured that each ancestor gets a proper 'filter_type' key, since we then 
	 *     wp_list_pluck by that key and having repeated values produced some unexpected issues. Also, 
	 *     make sure that we do not get duplicates, since first-level parents shoudl be covered by the 
	 *     default $view_settings['post_relationship_url_parameter'] attribute.
	 */
	
	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_relationship_mode'] ) 
			&& isset( $view_settings['post_relationship_mode'][0] ) 
			&& $view_settings['post_relationship_mode'][0] == 'url_parameter' 
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_relationship',
				'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
				'value'			=> 'ancestor_id',
				'attribute'		=> $view_settings['post_relationship_url_parameter'],
				'expected'		=> 'number',
				'placeholder'	=> '103',
				'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
			);
			
			$returned_post_types = WPV_Post_Relationship_Frontend_Filter::get_returned_post_types( $view_settings );
			
			$ancestor_post_types = array();
			if ( 
				! empty( $returned_post_types ) 
				&& function_exists( 'wpcf_pr_get_belongs' )
			) {
				$returned_post_types_parents = array();
				foreach ( $returned_post_types as $ground_post_type ) {
					$ground_post_type_parents = wpcf_pr_get_belongs( $ground_post_type );
					if ( 
						$ground_post_type_parents != false 
						&& is_array( $ground_post_type_parents ) 
					) {
						$ground_post_type_parents = array_values( array_keys( $ground_post_type_parents ) );
						$returned_post_types_parents = array_merge( $returned_post_types_parents, $ground_post_type_parents );
					}
				}
				$returned_post_types_parents = array_unique( $returned_post_types_parents );
				$returned_post_types_parents = array_values( $returned_post_types_parents );
				if ( ! empty( $returned_post_types_parents ) ) {
					$ancestor_post_types = wpv_get_post_type_ancestors( $returned_post_types_parents );
				}
			}
			foreach ( $ancestor_post_types as $ancestor_slug ) {
				$attributes[] = array(
					'query_type'	=> $view_settings['query_type'][0],
					'filter_type'	=> 'post_relationship_' . $ancestor_slug,
					'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
					'value'			=> 'ancestor_id',
					'attribute'		=> $view_settings['post_relationship_url_parameter'] . '-' . $ancestor_slug,
					'expected'		=> 'number',
					'placeholder'	=> '103',
					'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
				);
			}
		}
		return $attributes;
	}
	
	/**
	* get_returned_post_types
	*
	* Get the post types displayed by the current View or WordPress Archive.
	*
	* @param $view_settings
	*
	* @return array
	*
	* @since 2.1
	*/
	
	static function get_returned_post_types( $view_settings ) {
		$returned_post_types = array();
		if ( 
			isset( $view_settings['view-query-mode'] ) 
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			$returned_post_types = $view_settings['post_type'];
		} else {
			// we assume 'archive' or 'layouts-loop'
			global $wp_query;
			$returned_post_types = $wp_query->get( 'post_type' );
			if ( ! is_array( $returned_post_types ) ) {
				$returned_post_types = array( $returned_post_types );
			}
		}
		return $returned_post_types;
	}
	
	static function wpv_before_display_post_post_relationship( $post, $view_id ) {
		static $related = array();
		global $WP_Views;
		if ( function_exists( 'wpcf_pr_get_belongs' ) ) {
			if ( ! isset( $related[$post->post_type] ) ) {
				$related[$post->post_type] = wpcf_pr_get_belongs( $post->post_type );
			}
			if ( is_array( $related[$post->post_type] ) ) {
				foreach( $related[$post->post_type] as $post_type => $data ) {
					$related_id = wpcf_pr_post_get_belongs( $post->ID, $post_type );
					if ( $related_id ) {
						$WP_Views->set_variable( $post_type . '_id', $related_id );
					}
				}
			}
		}
		
	}

}