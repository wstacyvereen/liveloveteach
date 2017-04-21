<?php

/* ************************************************************************* *\
        WPML Translation Management integration
\* ************************************************************************* */


/**
 * Auxiliar function to override the current language
 *
 * @param $lang string the current language
 * @return bool $sitepress->get_default_language()
 *
 * @since unknown
 */
function wpv_wpml_icl_current_language( $lang ) { // TODO check why is this needed: it just returns the default language when looking for the current language...
    global $sitepress;

    return $sitepress->get_default_language();
}

/**
 * Converts links in a string to the corresponding ones in the current language
 *
 * @param $body string to check against
 * @return bool|mixed|string $body
 *
 * @since unknown
 */
function wpml_content_fix_links_to_translated_content($body){
    global $WPV_settings, $wpdb, $sitepress, $sitepress_settings, $wp_taxonomies;

    if (isset($sitepress)) {

        static $content_cache = array();

        $target_lang_code = apply_filters( 'wpml_current_language', '' );

        $cache_code = md5($body . $target_lang_code);
        if (isset($content_cache[$cache_code])) {
            $body = $content_cache[$cache_code];
        } else {

			// On the latest fix, those two hooks were  moved to after the _process_generic_text call
			// This needs wild testing on sites with a non-english first language
            add_filter('icl_current_language', 'wpv_wpml_icl_current_language');
            remove_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));

            require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
            $icl_abs_links = new AbsoluteLinks;

            $old_body = $body;
            $alp_broken_links = array();
            $body = $icl_abs_links->_process_generic_text($body, $alp_broken_links);

            // Restore the language as the above call can change the current language.
			do_action( 'wpml_switch_language', $target_lang_code );

            if ($body == '') {
                // Handle a problem with abs links occasionally return empty.
                $body = $old_body;
            }

            $new_body = $body;

            $base_url_parts = parse_url(get_option('home'));

            $links = wpml_content_get_link_paths($body);

            $all_links_fixed = 1;

            $pass_on_qvars = array();
            $pass_on_fragments = array();

            foreach($links as $link_idx => $link) {
                $path = $link[2];
                $url_parts = parse_url($path);

                if(isset($url_parts['fragment'])){
                    $pass_on_fragments[$link_idx] = $url_parts['fragment'];
                }

                if((!isset($url_parts['host']) or $base_url_parts['host'] == $url_parts['host']) and
                        (!isset($url_parts['scheme']) or $base_url_parts['scheme'] == $url_parts['scheme']) and
                        isset($url_parts['query'])) {
                    $query_parts = explode('&', $url_parts['query']);

                    foreach($query_parts as $query){
                        // find p=id or cat=id or tag=id queries
						$query_elements = explode('=', $query);
						if ( count( $query_elements ) < 2 ) {
							continue;
						}
						$key = $query_elements[0];
						$value = $query_elements[1];
                        $translations = NULL;
                        $is_tax = false;
                        if($key == 'p'){
                            $kind = 'post_' . $wpdb->get_var(
								$wpdb->prepare(
									"SELECT post_type FROM {$wpdb->posts} 
									WHERE ID = %d 
									LIMIT 1",
									$value
								)
							);
                        } else if($key == "page_id"){
                            $kind = 'post_page';
                        } else if($key == 'cat' || $key == 'cat_ID'){
                            $is_tax = true;
                            $kind = 'tax_category';
                            $taxonomy = 'category';
                        } else if($key == 'tag'){
                            $is_tax = true;
                            $taxonomy = 'post_tag';
                            $kind = 'tax_' . $taxonomy;
                            $value = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
									ON t.term_id = x.term_id 
									WHERE x.taxonomy = %s 
									AND t.slug = %s 
									LIMIT 1",
									$taxonomy,
									$value
								)
							);
                        } else {
                            $found = false;
                            foreach($wp_taxonomies as $ktax => $tax){
                                if($tax->query_var && $key == $tax->query_var){
                                    $found = true;
                                    $is_tax = true;
                                    $kind = 'tax_' . $ktax;
                                    $value = $wpdb->get_var(
										$wpdb->prepare(
											"SELECT term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
											ON t.term_id = x.term_id 
											WHERE x.taxonomy = %s 
											AND t.slug = %s 
											LIMIT 1",
											$ktax,
											$value
										)
									);
                                    $taxonomy = $ktax;
                                }
                            }
                            if(!$found){
                                $pass_on_qvars[$link_idx][] = $query;
                                continue;
                            }
                        }

                        $link_id = (int)$value;

                        if (!$link_id) {
                            continue;
                        }

                        $trid = $sitepress->get_element_trid($link_id, $kind);
                        if(!$trid){
                            continue;
                        }
                        if($trid !== NULL){
                            $translations = $sitepress->get_element_translations($trid, $kind);
                        }
                        if(isset($translations[$target_lang_code]) && $translations[$target_lang_code]->element_id != null){

                            // use the new translated id in the link path.

                            $translated_id = $translations[$target_lang_code]->element_id;

                            if($is_tax){ //if it's a tax, get the translated link based on the term slug (to avoid the need to convert from term_taxonomy_id to term_id)
                                $translated_id = $wpdb->get_var(
									$wpdb->prepare(
										"SELECT slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
										ON t.term_id = x.term_id 
										WHERE x.term_taxonomy_id = %d 
										LIMIT 1",
										$translated_id
									)
								);
                            }

                            // if absolute links is not on turn into WP permalinks
                            if(empty($GLOBALS['WPML_Sticky_Links'])){
                                ////////
                                if(preg_match('#^post_#', $kind)){
                                    $replace = get_permalink($translated_id);
                                }elseif(preg_match('#^tax_#', $kind)){
                                remove_filter('icl_current_language', 'wpv_wpml_icl_current_language');
                                    if(is_numeric($translated_id)) $translated_id = intval($translated_id);
                                    $replace = get_term_link($translated_id, $taxonomy);
                                    add_filter('icl_current_language', 'wpv_wpml_icl_current_language');
                                }
                                $new_link = str_replace($link[2], $replace, $link[0]);

                                $replace_link_arr[$link_idx] = array('from'=> $link[2], 'to'=>$replace);
                            }else{
                                $replace = $key . '=' . $translated_id;
                                $new_link = str_replace($query, $replace, $link[0]);

                                $replace_link_arr[$link_idx] = array('from'=> $query, 'to'=>$replace);
                            }

                            // replace the link in the body.
                            // $new_body = str_replace($link[0], $new_link, $new_body);
                            $all_links_arr[$link_idx] = array('from'=> $link[0], 'to'=>$new_link);
                            // done in the next loop

                        } else {
                            // translation not found for this.
                            $all_links_fixed = 0;
                        }
                    }
                }

            }

            if(!empty($replace_link_arr))
            foreach($replace_link_arr as $link_idx => $rep){
                $rep_to = $rep['to'];
                $fragment = '';

                // if sticky links is not ON, fix query parameters and fragments
                if(empty($GLOBALS['WPML_Sticky_Links'])){
                    if(!empty($pass_on_fragments[$link_idx])){
                        $fragment = '#' . $pass_on_fragments[$link_idx];
                    }
                    if(!empty($pass_on_qvars[$link_idx])){
                        $url_glue = (strpos($rep['to'], '?') === false) ? '?' : '&';
                        $rep_to = $rep['to'] . $url_glue . join('&', $pass_on_qvars[$link_idx]);
                    }
                }

                $all_links_arr[$link_idx]['to'] = str_replace($rep['to'], $rep_to . $fragment, $all_links_arr[$link_idx]['to']);

            }

            if(!empty($all_links_arr))
            foreach($all_links_arr as $link){
                $new_body = str_replace($link['from'], $link['to'], $new_body);
            }

            $body = $new_body;
            $content_cache[$cache_code] = $body;

            remove_filter('icl_current_language', 'wpv_wpml_icl_current_language');
            add_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));

        }
    }

    return $body;
}


/**
 * Parse links from a given string
 *
 * @param $body string to be parsed
 * @return array $links array of parsed links
 *
 * @since unknown
 */
function wpml_content_get_link_paths($body) {

    $regexp_links = array(
                        /*"/<a.*?href\s*=\s*([\"\']??)([^\"]*)[\"\']>(.*?)<\/a>/i",*/
                        "/<a[^>]*href\s*=\s*([\"\']??)([^\"^>]+)[\"\']??([^>]*)>/i",
                        );

    $links = array();

    foreach($regexp_links as $regexp) {
        if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
              $links[] = $match;
            }
        }
    }
    return $links;
}


/* ************************************************************************* *\
        WPML String Translation integration
\* ************************************************************************* */

/**
 * Utility function to translate strings used in wpv-control shortcodes.
 *
 * @param string $content The content of the Filter HTML textarea to parse
 * @param int $view_id The current View ID to build the content from
 *
 * @since 1.3.0
 */
function wpv_add_controls_labels_to_translation( $content, $view_id ) {
	if( function_exists('icl_register_string') ) {
		/*
		** Array of fields to be checked
		*/
		$tobechecked = array(
			'display_values',
			'default_label',
			'title',
			'auto_fill_default',
			'name',
			'reset_label'
		);
		/*
		** If there are commas escaped or placeheld please replace with '|' (pipe char)
		*/
		$content = str_replace( array( '%%COMMA%%', "\\\\\," ), '|', $content );
		/*
		** Strip all slashes if any left
		*/
		$content = stripslashes( $content );
		/*
		** Make a context out of View title
		*/
		$context = get_post_field( 'post_name', $view_id );
		/*
		** Empty array to store what's already being parsed (when BETWEEN or NOT BETWEEN we can have 2 recorrences of the same labels)
		*/
		$control = array();

		/*
		** Loop through all our fields
		*/
		foreach( $tobechecked as $string ) {
		
			if ( $string == 'name' ) {
				$button_name = 'submit';
			} else {
				$button_name = 'button';
			}
			/*
			** Make sure we have parameters in the form of param="
			*/
			if( strpos( $content, $string.'="' ) !== false ) {
				/*
				** Subquery 1: ( (url_param\s*?=\"(.*?)\").*?)? make sure if we have 0 or more occurences of 'url_param="' and take the value (.*?) in a subquery
				** array[3]
				** Subquery 3: this is our main without ? operator (".$string."\s*?=\"(.*?)\"), if there is store (.*?) subquery value in array[5]
				*/
				preg_match_all( "/( (url_param\s*?=\"(.*?)\").*?)?(".$string."\s*?=\"(.*?)\")/", $content, $matches );
				if ( $string == 'default_label' ) {
					preg_match_all( "/( (ancestor_type\s*?=\"(.*?)\").*?)?(".$string."\s*?=\"(.*?)\")/", $content, $anc_matches );
				}
				/*
				** If we have a corrsponding match on (".$string."\s*?=\"(.*?)\") this one and first element of result array is not empty loop
				*/
				if( isset( $matches[5] ) && isset( $matches[5][0] ) ) {
					/*
					** Loop through results and store $key for control
					*/
					foreach( $matches[5] as $key=>$translate ) {
						/*
						** If we have values we will store first element of the list here to be translated
						*/
						$translate_first = '';
						/*
						** If we have values keep track if the first display_value should be translated or not
						*/

						$should_do = false;
						
						/**
						** Take the key of the actual record to translate.
						** If there are multiple, do it for every one of them
						**/
						$key_juan = array_keys( $matches[5], $translate );
						
						foreach ( $key_juan as $key_t ) {

							/**
							** Create a name for label to translate
							**/
							
							if ( $string == 'default_label' && isset( $anc_matches[3] ) && isset( $anc_matches[3][$key_t] ) ) {
								$button_name = $anc_matches[3][$key_t];
							}
							
							$name = !empty( $matches[3][$key_t] ) ? $matches[3][$key_t] : $button_name;

							/*
							** Make sure we do not already have a translatable string for this occurence
							*/
							if( !in_array($translate . $name, $control) ) {

								/*
								** If we have values loop through them
								*/
								if( $string == 'display_values' ) {
									$should_do = true;
									/*
									** Keep track of the values already pushed for translation f we have more occurences of same value
									*/

									$trs_values = array();
									/*
									** Loop through values
									*/

									/*
									** Translate only display_values if first value is empty and we didn't push it already
									*/

									$translate_first = explode( ',', $translate );
									foreach( $translate_first as $trs_first ) {
										$trs_first = str_replace('|', ',', $trs_first );
										array_push($trs_values, $trs_first);
									}

								}

								/**
								** If eligible for translation do
								**/

								if( $should_do ) {
									$count_values = 1;
									foreach( $trs_values as $trs )
									{
										icl_register_string( "View ".$context, $name.'_'.$string."_".$count_values, $trs );
										$count_values++;
									}
									$trs_first = '';
								} else {
									icl_register_string( "View ".$context, $name.'_'.$string, $translate );
								}

								array_push($control, $translate . $name);
							}

						}
					}
				}
			}
		}
	}
}

/**
 * wpv_parse_wpml_shortcode
 *
 * Parses wpml-string shortcodes in a given string, handling slashes coming from escaped quotes
 *
 * @param $content the string to parse shortcodes from
 * @return array $output array( N => array( 'context'=> $context, 'content'=> $content, 'name'=> $name ) )
 *
 * @since 1.5.0
 * @deprecated 2.3.0 Keep for backwards compatibility
 */
function wpv_parse_wpml_shortcode( $content ) {
	
	_doing_it_wrong(
		'wpv_parse_wpml_shortcode', 
		__( 'This function was deprecated in Views 2.3.0.', 'wpv-views' ),
		'2.2.2'
	);
	
	$output = array();
	$content = stripslashes( $content );
	preg_match_all( "/\[wpml-string context=\"([^\"]+)\"]([^\[]+)\[\/wpml-string\]/iUs", $content, $out );
	if ( count( $out[0] ) > 0 ) {
		$matches = count( $out[0] );
		for( $i=0; $i < $matches; $i++ ){
			$output[] = array( 'context' => $out[1][$i], 'content' => $out[2][$i], 'name' => 'wpml-shortcode-' . md5( $out[2][$i] ) );
		}
	}
	return $output;
}


/**
 * wpv_register_wpml_strings
 *
 * Registers strings wrapped into wpml-string shortcodes for translation using WPML, handling slashes coming from escaped quotes
 *
 * @param string $content The string to parse shortcodes from.
 *
 * @since 1.5.0
 * @since 2.2.2 Return early when there is no wpml-string shortode to register.
 * @since 2.2.2 Register strings using a fake wpml-string shortcode callback.
 * @deprecated 2.3.0 Keep for backwards compatibility.
 */
function wpv_register_wpml_strings( $content ) {
	
	_doing_it_wrong(
		'wpv_register_wpml_strings', 
		__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_wpml_strings" action instead.', 'wpv-views' ),
		'2.3.0'
	);
	
	if ( strpos( $content, '[wpml-string' ) === false ) {
		return;
	}
	
	if ( function_exists( 'icl_register_string' ) ) {
		
		$content = stripslashes( $content );
		
		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		
		add_shortcode( 'wpml-string', 'wpv_fake_wpml_string_shortcode_to_icl_register_string' );
		do_shortcode( $content );
		
		$shortcode_tags = $orig_shortcode_tags;
	}
}

/**
 * wpv_fake_wpml_string_shortcode_to_icl_register_string
 *
 * Fake callback for the wpml-string shortcode,
 * so its attributes can be parsed and defaulted, and the string can be registered.
 *
 * @param atts array
 * @param content string
 *
 * @since 2.2.2
 * @deprecated 2.3.0 Keep for backwards compatibility.
 */

function wpv_fake_wpml_string_shortcode_to_icl_register_string( $atts, $content ) {
	
	_doing_it_wrong(
		'wpv_fake_wpml_string_shortcode_to_icl_register_string', 
		__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_wpml_strings" action instead.', 'wpv-views' ),
		'2.3.0'
	);
	
	if ( function_exists( 'icl_register_string' ) ) {
		$atts = shortcode_atts( 
			array(
				'context'	=> 'wpml-shortcode',
				'name'		=> ''
			), 
			$atts 
		);
		$atts['name'] = empty( $atts['name'] ) ? 'wpml-shortcode-' . md5( $content ) : $atts['name'];
		icl_register_string( $atts['context'], $atts['name'], $content );
	}
	return;
}

/**
* wpv_add_string_translation_to_formatting_instructions
* 
* Registers the hooks to add the String Translation information to the formatting instructions under CodeMirror textareas
*
* @since 1.7
*/

add_action( 'init', 'wpv_add_string_translation_to_formatting_instructions' );

function wpv_add_string_translation_to_formatting_instructions() {
	if ( function_exists( 'wpml_string_shortcode' )	) {
		// Register the section
		add_filter( 'wpv_filter_formatting_help_filter', 'wpv_register_wpml_section' );
		add_filter( 'wpv_filter_formatting_help_layout', 'wpv_register_wpml_section' );
		add_filter( 'wpv_filter_formatting_help_inline_content_template', 'wpv_register_wpml_section' );
		add_filter( 'wpv_filter_formatting_help_layouts_content_template_cell', 'wpv_register_wpml_section' );
		add_filter( 'wpv_filter_formatting_help_combined_output', 'wpv_register_wpml_section' );
		add_filter( 'wpv_filter_formatting_help_content_template', 'wpv_register_wpml_section' );
		// Register the section content
		add_filter( 'wpv_filter_formatting_instructions_section', 'wpv_wpml_string_translation_shortcodes_instructions', 10, 2 );
	}
}

/**
* wpv_register_wpml_section
*
* Registers the formatting instructions section for WPML in several textareas
*
* Check if the string_translation section has already been registered. If not, add it to the hooked formatting instructions boxes
*
* @param $sections (array) Registered sections for the formatting instructions
*
* @return $sections (array)
*
* @since 1.7
*/

function wpv_register_wpml_section( $sections ) {
	if ( ! in_array( 'string_translation', $sections ) ) {
		array_splice( $sections, -2, 0, array( 'string_translation' ) );
	}
	return $sections;
}


/**
 * wpv_wpml_string_translation_shortcodes_instructions
 *
 * Registers the content of the WPML section in several formatting instructions boxes
 *
 * @param $return (array|false) What to return, generally an array for the section that you want to give content to
 *     'classname' => (string) A specific classname for this section, useful when some kind of show/hide functionality is needed
 *     'title' => (string) The title of the section
 *     'content' => (string) The main text of the section
 *     'table' => (array) Table of ( Element, Description) arrays to showcase shortcodes, markup or related things
 *         array(
 *             'element' => (string) The element to describe. You can use some classes to add styling like in the CodeMirror instances: .wpv-code-shortcode, .wpv-code-html, .wpv-code-attr or .wpv-code-val
 *             'description' => (string) The element description
 *         )
 *     'content_extra' => (string) Extra text to be displayed after the table
 * @param $section (string) The name of the section
 * @return array $return (array|false)
 *
 * @since 1.7
 */
function wpv_wpml_string_translation_shortcodes_instructions( $return, $section ) {
	if ( $section == 'string_translation' ) {
		$return = array(
			'classname' => 'js-wpv-editor-instructions-for-string-translation',
			'title' => __( 'String translation shortcodes', 'wpv-views' ),
			'content' => '',
			'table' => array(
				array(
					'element' => '<span class="wpv-code-shortcode">[wpml-string</span> <span class="wpv-code-attr">context</span>=<span class="wpv-code-val">"wpv-views"</span><span class="wpv-code-shortcode">]</span>' 
							. __( 'Text content', 'wpv-views' )
							. '<span class="wpv-code-shortcode">[/wpml-string]</span>',
					'description' => __( 'Makes the text content translatable via WPML\'s String Translation.', 'wpv-views' )
				)
			),
			'content_extra' => ''
		);
	}
	return $return;
}

/**
* wpv_suggest_wpml_contexts
*
* Suggest for WPML string shortcode context, from a suggest callback
*
* @since 1.4
*/

add_action('wp_ajax_wpv_suggest_wpml_contexts', 'wpv_suggest_wpml_contexts');
add_action('wp_ajax_nopriv_wpv_suggest_wpml_contexts', 'wpv_suggest_wpml_contexts');

function wpv_suggest_wpml_contexts() {
	global $wpdb;
	$context_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$results = $wpdb->get_results( 
		$wpdb->prepare( 
            "SELECT DISTINCT context 
            FROM {$wpdb->prefix}icl_strings
            WHERE context LIKE %s
            ORDER BY context ASC", 
			$context_q 
		) 
	);
	foreach ( $results as $row ) {
		echo $row->context . "\n";
	}
	die();
}

/**
* wpv_disable_wpml_admin_lang_switcher
*
* Disable the WPML admin bar language switcher on Views, CT and WPA related pages
*
* @since 1.9
*/

add_filter('wpml_show_admin_language_switcher', 'wpv_disable_wpml_admin_lang_switcher');

function wpv_disable_wpml_admin_lang_switcher( $state ) {
	global $pagenow;
	$disable_in_views_pages = array(
		'views', 'views-editor', 'embedded-views', 'views-embedded', 
		'view-templates', 'ct-editor', 'embedded-views-templates', 'view-templates-embedded', 
		'view-archives', 'view-archives-editor', 'embedded-views-archives', 'view-archives-embedded', 
		// DEPRECATED:
		'views-settings', 'views-import-export', 'views-debug-information', 'views-update-help'
	);
	if ( 
		$pagenow == 'admin.php' 
		&& isset( $_GET['page'] ) 
		&& in_array( $_GET['page'], $disable_in_views_pages )
	) {
		$state = false;
	}
	return $state;
}


/**
 * Singleton encapsulating (new) WPML-related functionality.
 *
 * @since 1.10
 */
class WPV_WPML_Integration_Embedded {

    /**
     * The instance.
     *
     * @var WPV_WPML_Integration_Embedded
     * @since 1.10
     */
    protected static $instance = null;


    /**
     * Get the instance of the singleton (and create it if it doesn't exist yet).
     *
     * @return WPV_WPML_Integration_Embedded
     * @since 1.10
     */
    public static function get_instance() {
        if( null == self::$instance ) {
            self::$instance = new WPV_WPML_Integration_Embedded();
        }
        return self::$instance;
    }


    /**
     * Initialize the singleton.
     *
     * @since 1.10
     */
    public static function initialize() {
        self::get_instance();
    }
	
	/**
     * @var bool Holds information about the state of WPML activation.
     * @since 2.3.0
     */
    protected $_is_wpml_loaded = false;


    /**
     * @var bool Holds information about the state of WPML TM activation.
     * @since 1.10
     */
    protected $_is_wpml_tm_loaded = false;
	
	/**
	 * @var string Holds the current context when registering attribute values for Views shortcodes.
	 * @since 2.3.0
	 */
	protected $_context = '';


    /**
     * Singleton instantiation.
     *
     * Should happen before plugins_loaded action. Register further action hooks.
	 *
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
     *
     * @since 1.10
     */
    protected function __construct() {
		
		add_action( 'init',											array( $this, 'init' ) );
        add_action( 'admin_init',									array( $this, 'admin_init' ) );
		
		// this will be run during plugins_loaded
        add_action( 'wpml_loaded',									array( $this, 'wpml_loaded' ) );
        // this will be run during plugins_loaded
        add_action( 'wpml_tm_loaded',								array( $this, 'wpml_tm_loaded' ) );

		// Action after saving translated post content
		add_action( 'icl_pro_translation_completed',				array( $this, 'icl_pro_translation_completed' ) );
		
		// WPML shortcodes in the Fields and Views dialog
		add_shortcode( 'wpml-lang-switcher',						array( $this, 'wpv_wpml_lang_switcher' ) );
		add_shortcode( 'wpml-lang-footer',							array( $this, 'wpv_wpml_lang_footer' ) );
		//add_shortcode( 'wpml-breadcrumbs',							array( $this, 'wpv_wpml_breadcrumbs' ) );
		add_shortcode( 'wpml-sidebar',								array( $this, 'wpv_wpml_sidebar' ) );
		add_action( 'wpv_action_wpv_add_wpml_shortcodes_to_editor',	array( $this, 'wpv_add_wpml_shortcodes_to_editor' ), 10, 2 );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',			array( $this, 'wpv_register_wpml_shortcodes_data' ) );
		
		add_filter( 'wpv_custom_inner_shortcodes',					array( $this, 'wpv_wpml_string_in_custom_inner_shortcodes' ) );
		// Views shortcodes integration - register on activation
		add_action( 'init',											array( $this, 'wpv_register_wpml_strings_on_activation' ), 99 );
		// Views shortcodes integration - generic
		add_action( 'wpv_action_wpv_register_wpml_strings',			array( $this, 'wpv_register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_register_wpml_strings',			array( $this, 'wpv_register_shortcode_attributes_to_translate' ), 20, 2 );
		// Views shortcodes integration - automatic
		add_action( 'wpv_action_wpv_after_set_filter_meta_html',	array( $this, 'wpv_register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_filter_meta_html',	array( $this, 'wpv_register_shortcode_attributes_to_translate' ), 20, 2 );
		add_action( 'wpv_action_wpv_after_set_loop_meta_html',		array( $this, 'wpv_register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_loop_meta_html',		array( $this, 'wpv_register_shortcode_attributes_to_translate' ), 20, 2 );
		add_action( 'wpv_action_wpv_after_set_content_raw',			array( $this, 'wpv_register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_content_raw',			array( $this, 'wpv_register_shortcode_attributes_to_translate' ), 20, 2 );
		
    }
	
	public function init() {
		$this->register_shortcodes_dialog_group();
	}


    /**
     * WPML integration actions on admin_init.
     *
     * @since 1.10
     */
    public function admin_init() {
        $this->hook_filters_for_links();
    }


    /**
     * wpml_tm_loaded action hook.
     *
     * @since 2.3.0
     */
    public function wpml_loaded() {
        $this->_is_wpml_loaded = true;
    }
	
	
	/**
     * Determine whether WPML Translation Management is active and fully loaded.
     *
     * @return bool
     * @since 1.10
     */
    public function is_wpml_loaded() {
        return $this->_is_wpml_loaded;
    }
	

    /**
     * wpml_tm_loaded action hook.
     *
     * @since 1.10
     */
    public function wpml_tm_loaded() {
        $this->_is_wpml_tm_loaded = true;
    }


    /**
     * Determine whether WPML Translation Management is active and fully loaded.
     *
     * @return bool
     * @since 1.10
     */
    public function is_wpml_tm_loaded() {
        return $this->_is_wpml_tm_loaded;
    }


    /**
     * Hook into WPML filters and modify links to edit or view Content Templates in
     * WPML Translation Management.
     *
     * @since 1.10
     */
    protected function hook_filters_for_links() {
        add_filter( 'wpml_document_edit_item_link', array( $this, 'wpml_get_document_edit_link_ct' ), 10, 5 );
        add_filter( 'wpml_document_view_item_link', array( $this, 'wpml_get_document_view_link_ct' ), 10, 5 );
        add_filter( 'wpml_document_edit_item_url', array( $this, 'wpml_document_edit_item_url_ct' ), 10, 3 );
    }


    /**
     * Modify Edit link on Translation Dashboard of WPML Translation Management
     *
     * For Content Templates in default language, return the link to CT read-only page. For
     * CTs in different languages, don't show any link.
     *
     * @param string $post_edit_link The HTML code of the link.
     * @param string $label Link label to be displayed.
     * @param object $current_document
     * @param string $element_type 'post' for posts.
     * @param string $content_type If $element_type is 'post', this will contain a post type.
     *
     * @return string Link HTML.
     *
     * @since 1.10
     */
    public function wpml_get_document_edit_link_ct( $post_edit_link, $label, $current_document, $element_type, $content_type ) {

        if( 'post' == $element_type && WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
            $ct_id = $current_document->ID;

            // we know WPML is active, nothing else should call this filter
            global $sitepress;

            if( $sitepress->get_default_language() != $current_document->language_code ) {
                // We don't allow editing CTs in nondefault languages in our editor.
                // todo add link to translation editor instead
                $post_edit_link = '';
            } else {
                $link = apply_filters( 'icl_post_link', array(), WPV_Content_Template_Embedded::POST_TYPE, $ct_id, 'edit' );
                $is_disabled = wpv_getarr( $link, 'is_disabled', false );
                $url = wpv_getarr( $link, 'url' );

                if( $is_disabled ) {
                    $post_edit_link = '';
                } else if( !empty( $url ) ) {
                    $post_edit_link = sprintf( '<a href="%s" target="_blank">%s</a>', $url, $label );
                }
            }
        }
        return $post_edit_link;
    }


    /**
     * Modify View link on Translation Dashboard of WPML Translation Management
     *
     * Content Templates have no clear "View" option, so we're disabling the link for them.
     *
     * @param string $post_view_link Current view link
     * @param string $label Link label to be displayed
     * @param object $current_document
     * @param string $element_type 'post' for posts.
     * @param string $content_type If $element_type is 'post', this will contain a post type.
     *
     * @return string Link HTML
     *
     * @since 1.10
     */
    public function wpml_get_document_view_link_ct( $post_view_link,
        /** @noinspection PhpUnusedParameterInspection */ $label,
        /** @noinspection PhpUnusedParameterInspection */ $current_document,
                                                 $element_type, $content_type ) {
        if( 'post' == $element_type && WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
            // For a Content Template, there is nothing to view directly
            // todo link to some example content, if any exists
            $post_view_link = '';
        }
        return $post_view_link;
    }


    /**
     * Modify edit URLs for Content Templates on Translation Queue in WPML Translation Management.
     *
     * For CT, return URL to CT edit page.
     *
     * @param string $edit_url Current edit URL
     * @param string $content_type For posts, this will be post_{$post_type}.
     * @param int $element_id Post ID if the element is a post.
     *
     * @return string Edit URL.
     *
     * @since 1.10
     */
    public function wpml_document_edit_item_url_ct( $edit_url, $content_type, $element_id ) {
        if ( 'post_' . WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
            if ( $element_id ) {
                $link = apply_filters( 'icl_post_link', array(), WPV_Content_Template_Embedded::POST_TYPE, $element_id, 'edit' );
                $url = wpv_getarr( $link, 'url' );
                $is_disabled = wpv_getarr( $link, 'is_disabled', false );
                if( $is_disabled ) {
                    $edit_url = ''; // todo check if this works well
                } else if( !empty( $url ) ) {
                    $edit_url = $url;
                }
            }
        }
        
        return $edit_url;
    }


	/**
	 * This action hook is invoked when the translation in WPML TM is completed.
	 *
	 * For Views, WPAs and Content Templates, we will manually run the appropriate "after update" action.
	 *
	 * @param int $new_post_id ID of the newly created post.
	 * @since 1.12
	 */
	public function icl_pro_translation_completed( $new_post_id ) {
		$post = get_post( $new_post_id );
		if( $post instanceof WP_Post ) {
			switch( $post->post_type ) {
				case WPV_Content_Template_Embedded::POST_TYPE:
					$ct = WPV_Content_Template_Embedded::get_instance( $post );
					$ct->after_update_action();
					break;
				case WPV_View_Base::POST_TYPE:
					// View or WPA, doesn't make difference this time.
					$view = WPV_View_Base::get_instance( $post );
					$view->after_update_action();
					break;
			}
		}
	}
	
	
	public function wpml_get_user_admin_language_post_id( $id, $element_type = 'any' ) {
		$current_user_id = get_current_user_id();
		$user_admin_lang = apply_filters( 'wpml_get_user_admin_language', '', $current_user_id );
		$id = apply_filters( 'translate_object_id', $id, $element_type, true, $user_admin_lang );
		return $id;
	}
	
	
	/**
	 * wpv_wpml_lang_switcher
	 *
	 * Callback for the [wpml-lang-switcher] shortcode.
	 *
	 * @since unknown
	 * @since 2.3.0		Moved to this compatibility class
	 */
	
	public function wpv_wpml_lang_switcher( $atts, $value ) {
	
		ob_start();
		do_action( 'wpml_add_language_selector' );
		$result = ob_get_clean();

		return $result;
	}
	

	/**
	 * wpv_wpml_lang_switcher
	 *
	 * Callback for the [wpml-lang-footer] shortcode.
	 *
	 * @since unknown
	 * @since 2.3.0		Moved to this compatibility class
	 */

	public function wpv_wpml_lang_footer( $atts, $value ) {

		ob_start();
		do_action( 'wpml_footer_language_selector' );
		$result = ob_get_clean();

		return $result;

	}
	
	
	/**
	 * wpv_wpml_breadcrumb
	 *
	 * Callback for the [wpml-breadcrumbs] shortcode.
	 *
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
	 *
	 * @since unknown
	 * @since 2.3.0		Moved to this compatibility class
	 */
	/*
	public function wpv_wpml_breadcrumbs( $atts, $value ) {
		
		ob_start();
		do_action( 'icl_navigation_breadcrumb' );
		$result = ob_get_clean();

		return $result;
	}
	*/
	
	
	/**
	 * wpv_wpml_lang_switcher
	 *
	 * Callback for the [wpml-sidebar] shortcode.
	 *
	 * @since unknown
	 * @since 2.3.0		Moved to this compatibility class
	 */

	public function wpv_wpml_sidebar( $atts, $value ) {
		
		ob_start();
		do_action( 'icl_navigation_sidebar' );
		$result = ob_get_clean();

		return $result;
	}
	
	
	/**
	 * wpv_add_wpml_shortcodes_to_editor
	 *
	 * Register the WPML shortcodes in the Fields and Views dialog, when the WPML pieces are available.
	 *
	 * @todo avoid globals and use API filters instead.
	 *
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
	 *
	 * @since 2.3.0
	 * @deprecated 2.3.0 Keep it for backwards compatibility.
	 */
	
	public function wpv_add_wpml_shortcodes_to_editor( $editor, $nonce ) {
		
		_doing_it_wrong(
			'wpv_add_wpml_shortcodes_to_editor', 
			__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_dialog_group" action instead.', 'wpv-views' ),
			'2.3.0'
		);
		
		if ( $this->is_wpml_loaded() ) {
			$editor->add_insert_shortcode_menu(
				__( 'Language selector', 'wpv-views' ),
				'wpml-lang-switcher',
				'WPML',
				"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-switcher', title: '" . esc_js( __( 'Language selector', 'wpv-views' ) ) . "' })"
			);
			$editor->add_insert_shortcode_menu(
				__( 'Footer language selector', 'wpv-views' ),
				'wpml-lang-footer',
				'WPML',
				"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-footer', title: '" . esc_js( __( 'Footer language selector', 'wpv-views' ) ) . "' })"
			);
			global $iclCMSNavigation;
			if ( isset( $iclCMSNavigation ) ) {
				/*
				
				$editor->add_insert_shortcode_menu(
					__( 'Breadcrumbs navigation', 'wpv-views' ),
					'wpml-breadcrumbs',
					'WPML'
				);
				*/
				$editor->add_insert_shortcode_menu(
					__( 'Sidebar navigation', 'wpv-views' ),
					'wpml-sidebar',
					'WPML',
					"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-sidebar', title: '" . esc_js( __( 'Sidebar navigation', 'wpv-views' ) ) . "' })"
				);
			}
		}
	}
	
	public function register_shortcodes_dialog_group() {
		
		if ( ! $this->is_wpml_loaded() ) {
			return;
		}
		
		// @todo review the nonce management in the shortcodes GUI script:
		// we are passing it as a localization string, we do not need them here anymore.
		$nonce = '';
		
		$group_id	= 'wpml';
		$group_data	= array(
			'name'		=> __( 'WPML', 'wpv-views' ),
			'fields'	=> array()
		);
		
		if ( function_exists('icl_register_string') ) {
			$group_data['fields']['wpml-string'] = array(
				'name'		=> __( 'Translatable string', 'wpv-views' ),
				'shortcode'	=> 'wpml-string',
				'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-string', title: '" . esc_js( __( 'Translatable string', 'wpv-views' ) ) . "' })"
			);
		}
		
		$group_data['fields']['wpml-lang-switcher'] = array(
			'name'		=> __( 'Language selector', 'wpv-views' ),
			'shortcode'	=> 'wpml-lang-switcher',
			'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-switcher', title: '" . esc_js( __( 'Language selector', 'wpv-views' ) ) . "' })"
		);
		$group_data['fields']['wpml-lang-footer'] = array(
			'name'		=> __( 'Footer language selector', 'wpv-views' ),
			'shortcode'	=> 'wpml-lang-footer',
			'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-footer', title: '" . esc_js( __( 'Footer language selector', 'wpv-views' ) ) . "' })"
		);
		
		global $iclCMSNavigation;
		if ( isset( $iclCMSNavigation ) ) {
			$group_data['fields']['wpml-sidebar'] = array(
				'name'		=> __( 'Sidebar navigation', 'wpv-views' ),
				'shortcode'	=> 'wpml-sidebar',
				'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-sidebar', title: '" . esc_js( __( 'Sidebar navigation', 'wpv-views' ) ) . "' })"
			);
		}
		
		do_action( 'wpv_action_wpv_register_dialog_group', $group_id, $group_data );
	}
	
	
	/**
	 * wpv_register_wpml_shortcodes_data
	 *
	 * Register the WPML shortcodes in the general shortcodes GUI so we can display attribute oprions and information.
	 *
	 * @since 2.3.0
	 */
	
	public function wpv_register_wpml_shortcodes_data( $views_shortcodes ) {
		if ( $this->is_wpml_loaded() ) {
			if ( function_exists('icl_register_string') ) {
				$views_shortcodes['wpml-string'] = array(
					'callback' => array( $this, 'wpv_shortcodes_get_wpml_string_data' )
				);
			}
			$views_shortcodes['wpml-lang-switcher'] = array(
				'callback' => array( $this, 'wpv_shortcodes_get_wpml_lang_switcher_data' )
			);
			$views_shortcodes['wpml-lang-footer'] = array(
				'callback' => array( $this, 'wpv_shortcodes_get_wpml_lang_footer_data' )
			);
			global $iclCMSNavigation;
			if ( isset( $iclCMSNavigation ) ) {
				$views_shortcodes['wpml-sidebar'] = array(
					'callback' => array( $this, 'wpv_shortcodes_get_wpml_sidebar_data' )
				);
			}
		}
		return $views_shortcodes;
	}

	function wpv_shortcodes_get_wpml_string_data() {
		
		if ( ! $this->is_wpml_loaded() ) {
			return array();
		}
		
		if ( ! function_exists('icl_register_string') ) {
			return array();
		}
		
		$data = array(
			'name' => __( 'Translatable string', 'wpv-views' ),
			'label' => __( 'Translatable string', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __('Display options', 'wpv-views'),
					'header' => __('Display options', 'wpv-views'),
					'fields' => array(
						'context' => array(
							'label' => __( 'WPML context', 'wpv-views'),
							'type' => 'suggest',
							'action' => 'wpv_suggest_wpml_contexts',
							'default' => '',
							'required' => true,
							'placeholder' => __( 'Start typing', 'wpv-views' ),
						),
						'name' => array(
							'label' => __( 'String name', 'wpv-views'),
							'type' => 'text',
							'default' => '',
							'description' => __( 'Name this string to find it easily in the WPML String Translation page.', 'wpv-views' ),
						),
					),
					'content' => array(
						'label' => __( 'String to translate', 'wpv-views' )
					)
				),
			),
		);
		return $data;
	}
	
	
	public function wpv_shortcodes_get_wpml_lang_switcher_data() {
		
		if ( ! $this->is_wpml_loaded() ) {
			return array();
		}
		
		$data = array(
			'name'			=> __( 'Language switcher', 'wpv-views' ),
			'label'			=> __( 'Language switcher', 'wpv-views' ),
			'attributes'	=> array(
				'display-info' => array(
					'label'		=> __('Information', 'wpv-views'),
					'header'	=> __('Information', 'wpv-views'),
					'fields'	=> array(
						'wpv-wpml-lang-switcher-information' => array(
							'type'		=> 'info',
							'content'	=> __( 'This will display a language switcher styled as set in the WPML > Languages settings.', 'wpv-views' )
						),
					)
				),
			),
		);
		return $data;
		
	}
	
	
	public function wpv_shortcodes_get_wpml_lang_footer_data() {
		
		if ( ! $this->is_wpml_loaded() ) {
			return array();
		}
		
		$data = array(
			'name'			=> __( 'Footer language switcher', 'wpv-views' ),
			'label'			=> __( 'Footer language switcher', 'wpv-views' ),
			'attributes'	=> array(
				'display-info' => array(
					'label'		=> __( 'Information', 'wpv-views' ),
					'header'	=> __( 'Information', 'wpv-views' ),
					'fields'	=> array(
						'wpv-wpml-lang-footer-information' => array(
							'type'		=> 'info',
							'content'	=> __( 'This will display a footer language switcher styled as set in the WPML > Languages settings.', 'wpv-views' )
						),
					)
				),
			),
		);
		return $data;
		
	}
	
	
	public function wpv_shortcodes_get_wpml_sidebar_data() {
		
		if ( ! $this->is_wpml_loaded() ) {
			return array();
		}
		
		global $iclCMSNavigation;
		if ( ! isset( $iclCMSNavigation ) ) {
			return array();
		}
		
		$data = array(
			'name'			=> __( 'Sidebar navigation', 'wpv-views' ),
			'label'			=> __( 'Sidebar navigation', 'wpv-views' ),
			'attributes'	=> array(
				'display-info' => array(
					'label'		=> __('Information', 'wpv-views'),
					'header'	=> __('Information', 'wpv-views'),
					'fields'	=> array(
						'wpv-wpml-sidebar-information' => array(
							'type'		=> 'info',
							'content'	=> __( 'This will display the current page local navigation tree with ancestors, siblings and descendants.', 'wpv-views' )
						),
					)
				),
			),
		);
		return $data;
		
	}
	
	/**
	 * Filter hooked into wpv_custom_inner_shortcodes.
	 * Add the [wpml-string] shortcode to the allowed inner shortcodes, even if the [wpml-string] shortcode itself does not exist
	 *
	 * @param $custom_inner_shortcodes array() of allowed custom inner shortcodes
	 *
	 * @return $custom_inner_shortcodes
	 *
	 * @since 1.4.0
	 * @since 2.3.0 Moved to a proper method in the WPML integration class.
	 */
	function wpv_wpml_string_in_custom_inner_shortcodes( $custom_inner_shortcodes ) {
		if ( ! is_array( $custom_inner_shortcodes ) ) {
			$custom_inner_shortcodes = array();
		}
		$custom_inner_shortcodes[] = 'wpml-string';
		$custom_inner_shortcodes = array_unique( $custom_inner_shortcodes );
		return $custom_inner_shortcodes;
	}
	
	/**
	 * Register all Views wpml-string shortcodes and all translatable strings in Views shortcodes.
	 *
	 * @since 1.5.0
	 * @since 1.6.2 Change of the hook to init as the user capabilities are not reliable before that (and they are used in get_posts()).
	 * @since 2.3.0 Moved to be a proper method in the WPML integration class.
	 */
	
	function wpv_register_wpml_strings_on_activation() {
		if (
			function_exists( 'icl_register_string' ) 
			&& ! get_option( 'wpv_strings_translation_initialized', false ) 
			&& current_user_can( 'manage_options' )
		) {
			// Register strings from Views
			$views = get_posts( 'post_type=view&post_status=any&posts_per_page=-1' );
			foreach ( $views as $key => $view_post ) {
				$view_post = (array) $view_post;
				// Register strings in the content
				do_action( 'wpv_action_wpv_register_wpml_strings', $view_post['post_content'], $view_post["ID"] );
				// Register strings in the Filter HTML textarea
				$view_array = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_post["ID"] );
				if ( isset( $view_array['filter_meta_html'] ) ) {
					wpv_add_controls_labels_to_translation( $view_array['filter_meta_html'], $view_post["ID"] );
					do_action( 'wpv_action_wpv_register_wpml_strings', $view_array['filter_meta_html'], $view_post["ID"] );
				}
				// Register strings in the Layout HTML textarea
				$view_layout_array = apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array(), $view_post["ID"] );
				if ( isset( $view_layout_array['layout_meta_html'] ) ) {
					do_action( 'wpv_action_wpv_register_wpml_strings', $view_layout_array['layout_meta_html'], $view_post["ID"] );
				}
			}
			// Register strings from Content Templates
			$view_templates = get_posts( 'post_type=view-template&post_status=any&posts_per_page=-1' );
			foreach ( $view_templates as $key => $ct_post ) {
				$ct_post = (array) $ct_post;
				// Register strings in the content
				do_action( 'wpv_action_wpv_register_wpml_strings', $ct_post['post_content'], $ct_post["ID"] );
			}
			// Update the flag in the options so this is only run once
			update_option( 'wpv_strings_translation_initialized', 1 );
		}
	}
	
	public function wpv_register_wpml_strings( $content ) {
	
		if ( strpos( $content, '[wpml-string' ) === false ) {
			return;
		}
		
		if ( function_exists( 'icl_register_string' ) ) {
			
			$content = stripslashes( $content );
			
			global $shortcode_tags;
			// Back up current registered shortcodes and clear them all out
			$orig_shortcode_tags = $shortcode_tags;
			remove_all_shortcodes();
			
			add_shortcode( 'wpml-string', array( $this, 'wpv_fake_wpml_string_shortcode_to_icl_register_string' ) );
			do_shortcode( $content );
			
			$shortcode_tags = $orig_shortcode_tags;
			
		}
		
	}
	
	public function wpv_register_shortcode_attributes_to_translate( $content, $id ) {
		if ( function_exists('icl_register_string') ) {
			$this->_context = 'View ' . get_post_field( 'post_name', $id );
			$this->wpv_register_wpv_control_shortcode_attributes_to_translate( $content, $id );
			$this->wpv_register_wpv_sorting_shortcode_attributes_to_translate( $content, $id );
			$this->_context = '';
		}
	}
	
	public function wpv_register_wpv_control_shortcode_attributes_to_translate( $content, $id ) {
		
		if ( ! function_exists('icl_register_string') ) {
			return;
		}
		
	}
	
	public function wpv_register_wpv_sorting_shortcode_attributes_to_translate( $content, $id ) {
		
		if ( ! function_exists('icl_register_string') ) {
			return;
		}
		
		if ( 
			strpos( $content, '[wpv-sort-orderby ' ) !== false 
			|| strpos( $content, '[wpv-sort-order ' ) !== false
		) {
			global $shortcode_tags;
			// Back up current registered shortcodes and clear them all out
			$orig_shortcode_tags = $shortcode_tags;
			remove_all_shortcodes();
			add_shortcode( 'wpv-sort-orderby', array( $this, 'wpv_fake_wpv_sorting_shortcode_to_icl_register_string' ) );
			add_shortcode( 'wpv-sort-order', array( $this, 'wpv_fake_wpv_sorting_shortcode_to_icl_register_string' ) );
			
			$content = stripslashes( $content );
			
			do_shortcode( $content );
			
			$shortcode_tags = $orig_shortcode_tags;
		}
		
	}
	
	/**
	 * Fake callback for the wpml-string shortcode,
	 * so its attributes can be parsed and defaulted, and the string can be registered.
	 *
	 * @param atts array
	 * @param content string
	 *
	 * @since 2.2.2
	 * @since 2.3.0 Moved to a proper method of the WPML integration class.
	 */

	public function wpv_fake_wpml_string_shortcode_to_icl_register_string( $atts, $content ) {
		if ( function_exists( 'icl_register_string' ) ) {
			$atts = shortcode_atts( 
				array(
					'context'	=> 'wpml-shortcode',
					'name'		=> ''
				), 
				$atts 
			);
			$atts['name'] = empty( $atts['name'] ) ? 'wpml-shortcode-' . md5( $content ) : $atts['name'];
			icl_register_string( $atts['context'], $atts['name'], $content );
		}
		return;
	}
	
	/**
	 * Fake callback for the wpv-sort-orderby and wpv-sort-order shortcodes,
	 * so its label attributes can be registered.
	 *
	 * @param atts array
	 *
	 * @since 2.3.0
	 * @since 2.3.1 Register "label_asc_for_{field-slug] and label_desc_for_{field-slug} attribute values.
	 */
	
	public function wpv_fake_wpv_sorting_shortcode_to_icl_register_string( $atts ) {
		
		if ( ! function_exists('icl_register_string') ) {
			return;
		}
		
		$atts = wp_parse_args( 
			$atts, 
			array()
		);
		
		$atts_to_names_for_labels = array(
			'label_for_'		=> 'sorting_control_for_',
			'label_asc_for_'	=> 'sorting_control_asc_for_',
			'label_desc_for_'	=> 'sorting_control_desc_for_'
		);
		
		foreach ( $atts as $att_key => $att_value ) {
			
			foreach ( $atts_to_names_for_labels as $att_for_label => $name_for_label ) {
				
				if ( strpos( $att_key, $att_for_label ) === 0 ) {
				
					$att_meta_key = substr( $att_key, strlen( $att_for_label ) );
					$name = $name_for_label . $att_meta_key;
					icl_register_string( $this->_context, $name, $att_value );
					break;
					
				}
				
			}
			
		}
		
	}
	
}