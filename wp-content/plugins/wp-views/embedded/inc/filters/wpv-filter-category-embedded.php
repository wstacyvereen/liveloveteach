<?php

/**
* Taxonomy frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Taxonomy_Frontend_Filter::on_load();

/**
* WPV_Taxonomy_Frontend_Filter
*
* Views Taxonomy Filter Frontend Class
*
* @since 2.1
*/

class WPV_Taxonomy_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post taxonomy
        add_filter( 'wpv_filter_query',										array( 'WPV_Taxonomy_Frontend_Filter', 'filter_post_taxonomy' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Taxonomy_Frontend_Filter', 'archive_filter_post_taxonomy' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Taxonomy_Frontend_Filter', 'requires_current_page' ), 10, 2 );
		add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Taxonomy_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_parent_term',						array( 'WPV_Taxonomy_Frontend_Filter', 'requires_parent_term' ), 10, 2 );
		add_filter( 'wpv_filter_requires_current_archive',					array( 'WPV_Taxonomy_Frontend_Filter', 'requires_current_archive' ), 10, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Taxonomy_Frontend_Filter', 'requires_framework_values' ), 10, 2 );
		
	}
	
	/**
	* filter_post_taxonomy
	*
	* Apply taxonomy query filters to Views.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_post_category and moved to a static method
	*/
	
	static function filter_post_taxonomy( $query, $view_settings, $view_id ) {
		$taxonomy_query = WPV_Taxonomy_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
		if ( count( $taxonomy_query ) > 0 ) {
			$taxonomy_query['relation'] = isset( $view_settings['taxonomy_relationship'] ) ? $view_settings['taxonomy_relationship'] : 'AND';
			$query['tax_query'] = $taxonomy_query;
		}
		return $query;
	}
	
	/**
	* archive_filter_post_taxonomy
	*
	* Apply filters by post taxonomy to WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_taxonomy( $query, $archive_settings, $archive_id ) {
		$tax_to_exclude = array();
		if ( $query->get( 'wpv_dependency_query' ) ) {
			$wpv_dependency_query = $query->get( 'wpv_dependency_query' );
			if ( isset( $wpv_dependency_query['taxonomy'] ) ) {
				$tax_to_exclude[] = $wpv_dependency_query['taxonomy'];
			}
		}
		if ( 
			$query->is_archive 
			&& (
				$query->is_category  
				|| $query->is_tag  
				|| $query->is_tax 
			)
		) {
			$term = $query->get_queried_object();
			if ( 
				$term 
				&& isset( $term->taxonomy )
			) {
				$tax_to_exclude[] = $term->taxonomy;
			}
		}
		$taxonomy_query = WPV_Taxonomy_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id, $tax_to_exclude );
		
		// Re-apply the taxonomy query caused by a taxonomy archive page
		// Note that on Layout-based archives this duplicates the native archive tax query entry, but we can not avoid it
		if ( 
			isset( $query->tax_query ) 
			&& is_object( $query->tax_query )
		) {
			$tax_query_obj		= clone $query->tax_query;
			$tax_query_queries	= $tax_query_obj->queries;
			if ( 
				count( $tax_query_queries ) > 0 
				&& count( $tax_to_exclude ) > 0
			) {
				foreach ( $tax_query_queries as $tax_query_queries_item ) {
					if ( 
						is_array( $tax_query_queries_item ) 
						&& isset( $tax_query_queries_item['taxonomy'] ) 
						&& in_array( $tax_query_queries_item['taxonomy'], $tax_to_exclude ) 
					) {
						$taxonomy_query[] = $tax_query_queries_item;
					}
				}
			}
		}
		
		if ( count( $taxonomy_query ) > 0 ) {
			$taxonomy_query['relation'] = isset( $archive_settings['taxonomy_relationship'] ) ? $archive_settings['taxonomy_relationship'] : 'AND';
			$query->set( 'tax_query', $taxonomy_query );
			$query->tax_query = new WP_Tax_Query( $taxonomy_query );
		}
	}
	
	/**
	* get_settings
	*
	* Get settings for the query filter by post taxonomy.
	*
	* @note We can pass an array of taxonomies to exclude from the filters.
	*
	* @since 2.1
	*/
	
	static function get_settings( $query, $view_settings, $view_id, $tax_to_exclude = array() ) {
		$taxonomy_query			= array();
		$taxonomies				= get_taxonomies( '', 'objects' );
		$archive_environment	= apply_filters( 'wpv_filter_wpv_get_current_archive_loop', array() );
		
		foreach ( $taxonomies as $category_slug => $category ) {
			if ( in_array( $category_slug, $tax_to_exclude ) ) {
				continue;
			}
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
			if ( isset( $view_settings[ $relationship_name ] ) ) {
				$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;
				$attribute_operator = ( isset( $view_settings['taxonomy-' . $category->name . '-attribute-operator'] ) ) ? $view_settings['taxonomy-' . $category->name . '-attribute-operator'] : 'IN';
				
				if ( $attribute_operator == 'IN' ) {
					$include_child = true;	
				} else {
					$include_child = false;	
				}
				
				/*
				 * Filter: wpv_filter_tax_filter_include_children
				 * 
				 * @param: $include_child - current status
				 * @paran: $category->name - Category nicename
				 * @param: $view_id
				 * 
				*/
				//$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
				
				switch ( $view_settings['tax_' . $category->name . '_relationship'] ) {
					case 'top_current_post':
						$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
						if ( $current_page ) {
							$terms = array();
							$term_obj = get_the_terms( $current_page->ID, $category->name );
							if ( 
								$term_obj 
								&& ! is_wp_error( $term_obj ) 
							) {
								$terms = array_values( wp_list_pluck( $term_obj, 'term_id' ) );
							}
							if ( count( $terms ) ) {
								$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $terms, $category->name ),
									'operator'			=> "IN",
									"include_children"	=> $include_child
								);
							} else { // if the current page has no term in the given taxonomy, return nothing
								$taxonomy_query[] = array(
									'taxonomy'	=> $category->name,
									'field'		=> 'id',
									'terms'		=> 0,
									'operator'	=> "IN"
								);
							}
						}
						break;
					case 'FROM PAGE': // @deprecated in 1.12.1
					case 'current_post_or_parent_post_view':
						// @todo this should be FROM PARENT POST VIEW, and create a new mode for get_top_current_page(); might need adjust in labels too
						$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
						if ( $current_page ) {
							$terms = array();
							$term_obj = get_the_terms( $current_page->ID, $category->name );
							if ( 
								$term_obj 
								&& ! is_wp_error( $term_obj ) 
							) {
								$terms = array_values( wp_list_pluck( $term_obj, 'term_id' ) );
							}
							if ( count( $terms ) ) {
								$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $terms, $category->name ),
									'operator'			=> "IN",
									"include_children"	=> $include_child
								);
							} else { // if the current page has no term in the given taxonomy, return nothing
								$taxonomy_query[] = array(
									'taxonomy'	=> $category->name,
									'field'		=> 'id',
									'terms'		=> 0,
									'operator'	=> "IN"
								);
							}
						}
						break;
					case 'FROM ARCHIVE':
						if (
							isset( $archive_environment['type'] ) 
							&& $archive_environment['type'] == 'taxonomy' 
							&& isset( $archive_environment['data']['taxonomy'] ) 
							&& $archive_environment['data']['taxonomy'] == $category->name
							&& isset( $archive_environment['data']['term_id'] ) 
						) {
							$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
							$taxonomy_query[] = array(
								'taxonomy'			=> $category->name,
								'field'				=> 'id',
								'terms'				=> (int) $archive_environment['data']['term_id'],
								'operator'			=> "IN",
								"include_children"	=> $include_child
							);
						} else if (  
							is_tax() 
							|| is_category() 
							|| is_tag() 
						) {
							global $wp_query;
							$term = $wp_query->get_queried_object();
							if ( 
								$term 
								&& isset( $term->taxonomy )
								&& $term->taxonomy == $category->name
							) {
								$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> $term->term_id,
									'operator'			=> "IN",
									"include_children"	=> $include_child
								);
							}
						} else {
							$taxonomy_query[] = array(
								'taxonomy'	=> $category->name,
								'field'		=> 'id',
								'terms'		=> 0,
								'operator'	=> "IN"
							);
						}
						break;
					case 'FROM ATTRIBUTE':
						$attribute = $view_settings['taxonomy-' . $category->name . '-attribute-url'];
						if ( isset( $view_settings['taxonomy-' . $category->name . '-attribute-url-format'] ) ) {
							$attribute_format = $view_settings['taxonomy-' . $category->name . '-attribute-url-format'][0];
						} else {
							$attribute_format = 'name';
						}
						$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
						if ( 
							isset( $view_attrs[$attribute] ) 
							&& '' != $view_attrs[$attribute]
						) {
							$terms = explode(',', $view_attrs[$attribute]);
							$term_ids = array();
							foreach ( $terms as $t ) {
								// get_term_by does sanitization
								$term = get_term_by( $attribute_format, trim( $t ), $category->name );
								if ( $term ) {
									array_push( $term_ids, $term->term_id );
								}
							}
							if ( count( $term_ids ) > 0 ) {
								$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $term_ids, $category->name ),
									'operator'			=> $attribute_operator,
									"include_children"	=> $include_child
								);
							} else if ( count( $terms ) > 0 ) { // if the shortcode attribute exists and is not empty, and no term matches the value, return nothing
								$taxonomy_query[] = array(
									'taxonomy'	=> $category->name,
									'field'		=> 'id',
									'terms'		=> 0,
									'operator'	=> "IN"
								);
							}
						}
						break;
					case 'FROM URL':
						$url_parameter = $view_settings['taxonomy-' . $category->name . '-attribute-url'];
						if ( isset( $view_settings['taxonomy-' . $category->name . '-attribute-url-format'] ) ) {
							$url_format = $view_settings['taxonomy-' . $category->name . '-attribute-url-format'][0];
						} else {
							$url_format = 'name';
						}
						if ( isset( $_GET[$url_parameter] ) ) {
							if ( is_array( $_GET[$url_parameter] ) ) {
								$terms = $_GET[$url_parameter];
							} else {
								$terms = explode( ',', $_GET[$url_parameter] );
							}
							$term_ids = array();
							foreach ( $terms as $t ) {
								// get_term_by does sanitization
								$term = get_term_by( $url_format, trim( $t ), $category->name );
								if ( $term ) {
									array_push( $term_ids, $term->term_id );
								}
							}
							if ( count( $term_ids ) > 0 ) {
								$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $term_ids, $category->name ),
									'operator'			=> $attribute_operator,
									"include_children"	=> $include_child
								);
							} else if ( ! empty( $_GET[$url_parameter] ) ) {
								$taxonomy_query[] = array(
									'taxonomy'	=> $category->name,
									'field'		=> 'id',
									'terms'		=> 0,
									'operator'	=> "IN"
								);
							}
						}
						break;
					case 'FROM PARENT VIEW': // @deprecated on 1.12.1
					case 'current_taxonomy_view':
						$parent_term_id = apply_filters( 'wpv_filter_wpv_get_parent_view_taxonomy', null );
						if ( $parent_term_id ) {
							$include_child = true;
							$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
							$taxonomy_query[] = array(
								'taxonomy'			=> $category->name,
								'field'				=> 'id',
								'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( array( $parent_term_id ), $category->name ),
								'operator'			=> "IN",
								"include_children"	=> $include_child
							);
						} else {
							$taxonomy_query[] = array(
								'taxonomy'	=> $category->name,
								'field'		=> 'id',
								'terms'		=> 0,
								'operator'	=> "IN"
							);
						}
						break;
					case 'IN':
					case 'NOT IN':
					case 'AND':
						if ( $view_settings['tax_' . $category->name . '_relationship'] == 'IN' ) {
							$include_child = true;	
						} else {
							$include_child = false;	
						}
						$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
						if ( isset( $view_settings[$save_name] ) ) {
							$term_ids = $view_settings[$save_name];
							$taxonomy_query[] = array(
								'taxonomy'			=> $category->name,
								'field'				=> 'id',
								'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $term_ids, $category->name ),
								'operator'			=> $view_settings['tax_' . $category->name . '_relationship'],
								"include_children"	=> $include_child
							);
						}
						break;
					case 'framework':
						global $WP_Views_fapi;
						if ( 
							$WP_Views_fapi->framework_valid
							&& isset( $view_settings['taxonomy-' . $category->name . '-framework'] )
							&& '' != $view_settings['taxonomy-' . $category->name . '-framework']
						) {
							$include_child = true;
							$include_child = apply_filters( 'wpv_filter_tax_filter_include_children', $include_child, $category->name, $view_id );
							$framework_key = $view_settings['taxonomy-' . $category->name . '-framework'];
							$taxonomy_terms_candidates = $WP_Views_fapi->get_framework_value( $framework_key, array() );
							if ( ! is_array( $taxonomy_terms_candidates ) ) {
								$taxonomy_terms_candidates = explode( ',', $taxonomy_terms_candidates );
							}
							$taxonomy_terms_candidates = array_map( 'esc_attr', $taxonomy_terms_candidates );
							$taxonomy_terms_candidates = array_map( 'trim', $taxonomy_terms_candidates );
							// is_numeric does sanitization
							$taxonomy_terms_candidates = array_filter( $taxonomy_terms_candidates, 'is_numeric' );
							if ( count( $taxonomy_terms_candidates ) ) {
								$taxonomy_query[] = array(
									'taxonomy'			=> $category->name,
									'field'				=> 'id',
									'terms'				=> WPV_Taxonomy_Frontend_Filter::get_adjusted_terms( $taxonomy_terms_candidates, $category->name ),
									'operator'			=> 'IN',
									"include_children"	=> $include_child
								);
							}
						}
						break;
					
				}
			}
		}
		return $taxonomy_query;
	}
	
	
	/**
	* requires_current_page
	*
	* Whether the current View requires the current page data for any filter by taxonomy
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_cat_requires_current_page and moved to a static method
	*/
	
	static function requires_current_page( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		$taxonomies = get_taxonomies('', 'objects');
		foreach ( $taxonomies as $category_slug => $category ) {
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
			if ( isset( $view_settings[$relationship_name] ) ) {
				if ( $view_settings['tax_' . $category->name . '_relationship'] == "top_current_post" ) {
					$state = true;
					break;
				}
			}
		}
		return $state;
	}
	
	/**
	* requires_parent_post
	*
	* Check if the current filter by post parent needs info about the parent post
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_cat_requires_parent_post and mved to a static method
	*/
	
	static function requires_parent_post( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		$taxonomies = get_taxonomies('', 'objects');
		foreach ( $taxonomies as $category_slug => $category ) {
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
			if ( isset( $view_settings[$relationship_name] ) ) {
				if ( in_array( $view_settings['tax_' . $category->name . '_relationship'], array( "FROM PAGE", 'current_post_or_parent_post_view' ) ) ) {
					$state = true;
					break;
				}
			}
		}
		return $state;
	}
	
	/**
	* requires_parent_term
	*
	* Whether the current View is nested and requires the user set by the parent View for any filter by taxonomy
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.9
	* @since 2.1	Renamed from wpv_filter_cat_requires_parent_term and moved to a static method
	*/
	
	static function requires_parent_term( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		$taxonomies = get_taxonomies('', 'objects');
		foreach ( $taxonomies as $category_slug => $category ) {
			if ( 
				isset( $view_settings['tax_' . $category->name . '_relationship'] ) 
				&& in_array( $view_settings['tax_' . $category->name . '_relationship'], array( 'FROM PARENT VIEW', 'current_taxonomy_view' ) )
			) {
				$state = true;
				break;
			}
		}
		return $state;
	}
	
	/**
	* requires_current_archive
	*
	* Whether the current View requires the current archive loop
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_cat_requires_current_archive and moved to a static method
	*/
	
	static function requires_current_archive( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		$taxonomies = get_taxonomies('', 'objects');
		foreach ( $taxonomies as $category_slug => $category ) {
			if ( 
				isset( $view_settings['tax_' . $category->name . '_relationship'] ) 
				&& $view_settings['tax_' . $category->name . '_relationship'] == 'FROM ARCHIVE'
			) {
				$state = true;
				break;
			}
		}
		return $state;
	}
	
	/**
	* requires_framework_values
	*
	* Whether the current View requires values from a framework
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_cat_requires_framework_values and moved to a static method
	*/
	
	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		$taxonomies = get_taxonomies('', 'objects');
		foreach ( $taxonomies as $category_slug => $category ) {
			if ( 
				isset( $view_settings['tax_' . $category->name . '_relationship'] ) 
				&& $view_settings['tax_' . $category->name . '_relationship'] == 'framework'
			) {
				$state = true;
				break;
			}
		}
		return $state;
	}
	
	/**
	* get_adjusted_terms
	*
	* Adjust terms used on a frontend query filter.
	* Ensures compatibility with WordPress > 4.2 and WPML.
	*
	* @since unknown
	*/
	
	static function get_adjusted_terms( $term_ids, $category_name ) {
		if ( ! empty( $term_ids ) ) {
			$adjusted_term_ids = array();
			foreach ( $term_ids as $candidate_term_id ) {
				// WordPress 4.2 compatibility - split terms
				$candidate_term_id_splitted = wpv_compat_get_split_term( $candidate_term_id, $category_name );
				if ( $candidate_term_id_splitted ) {
					$candidate_term_id = $candidate_term_id_splitted;
				}
				// WPML support
				$candidate_term_id = apply_filters( 'translate_object_id', $candidate_term_id, $category_name, true, null );
				$adjusted_term_ids[] = $candidate_term_id;
			}
			$term_ids = $adjusted_term_ids;
		}
		return $term_ids;	
	}
	
}

/**
* This might be deprecated, but does not hurt
* Maybe add a _doing_it_wrong call_user_func
*/
function wpv_get_taxonomy_view_params($view_settings) {
	$results = array();
	
	$taxonomies = get_taxonomies('', 'objects');
	foreach ($taxonomies as $category_slug => $category) {
		$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
		
		if (isset($view_settings[$relationship_name])) {
			
			$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;			
			
			if ($view_settings['tax_' . $category->name . '_relationship'] == "FROM ATTRIBUTE") {
				$attribute = $view_settings['taxonomy-' . $category->name . '-attribute-url'];
				$results[] = $attribute;
			}
		}
    }
    
	return $results;
}

