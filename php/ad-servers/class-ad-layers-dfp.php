<?php

/**
 * Ad Layers DFP
 *
 * Implements the DFP Ad Server for Ad Layers.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_DFP' ) ) :

class Ad_Layers_DFP extends Ad_Layers_Ad_Server {
	
	/**
	 * The display label for this ad server.
	 *
	 * @access public
	 * @var array
	 */
	public $display_label = 'DoubleClick for Publishers (DFP)';
	
	/**
	 * Formatting tags.
	 *
	 * @access public
	 * @var array
	 */
	public $formatting_tags;
	
	/**
	 * The regular expression used to find formatting tags.
	 *
	 * @access public
	 * @var string
	 */
	public $formatting_tag_pattern = '/#[a-zA-Z\_]+#/';
	
	/**
	 * Ad unit prefix.
	 *
	 * @access public
	 * @var string
	 */
	public $ad_unit_prefix = 'div-gpt-ad-';
	
	/**
	 * Available ad units on the page.
	 *
	 * @access public
	 * @var array
	 */
	public $ad_units;
	
	/**
	 * Cache key
	 *
	 * @access public
	 * @var string
	 */
	public $cache_key = 'ad_layers_dfp_settings';
	
	/**
	 * Javascript API class name
	 *
	 * @access public
	 * @var string
	 */
	public $js_api_class = 'AdLayersDFPAPI';
	
	/**
	 * Handle used for scripts
	 *
	 * @access public
	 * @var string
	 */
	public $handle = 'ad-layers-dfp';
	
	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Define the available formatting tags
		$this->formatting_tags = apply_filters( 'ad_layers_dfp_formatting_tags', $this->set_formatting_tags() );
		
		// Allow filtering of the ad unit prefix
		$this->ad_unit_prefix = apply_filters( 'ad_layers_dfp_ad_unit_prefix', $this->ad_unit_prefix );
		
		// Add a help tab
		add_action( 'load-' . Ad_Layers_Post_Type::instance()->get_post_type() . '_page_' . $this->option_name, array( $this, 'add_help_tab' ) );
		
		// Handle caching
		add_action( 'update_option', array( $this, 'cache_settings' ), 10, 3 );
	}
	
	/**
	 * Load scripts.
	 *
	 * @access public
	 */
	public function enqueue_scripts() {
		// Load the base Javascript library
		wp_enqueue_script( $this->handle, AD_LAYERS_ASSETS_DIR . 'js/ad-layers-dfp.js', array( 'jquery' ), AD_LAYERS_GLOBAL_ASSET_VERSION, false );
		
		// Load the CSS. Mostly used in debug mode.
		wp_enqueue_style( $this->handle, AD_LAYERS_ASSETS_DIR . 'css/ad-layers-dfp.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
	}
	
	/**
	 * Set the available formatting tags.
	 *
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function set_formatting_tags() {
		// Set the base options
		$formatting_tags = array(
			'#account_id#' => __( 'Your DFP account ID', 'ad-layers' ),
			'#domain#' => __( 'The domain of the current site, taken from get_site_url', 'ad-layers' ),
			'#ad_unit#' => __( 'The ad unit name', 'ad-layers' ),
			'#post_type#' => __( 'The post type of the current page, if applicable', 'ad-layers' ),
		);
		
		// Add all registered taxonomies as an option since these are commonly used
		$taxonomies = Ad_Layers::instance()->get_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy_name => $taxonomy_label ) {
				$formatting_tags[ '#' . $taxonomy_name . '#' ] = sprintf( __( 'The current term from the %s taxonomy, if applicable. If the taxonomy is hierarchical, each term in the hierarchy above the current term will be added to the path. If there is more than one term, only the first will be used.', 'ad-layers' ), $taxonomy_label );
			}
		}
		
		return apply_filters( 'ad_layers_dfp_formatting_tags', $formatting_tags );
	}
	
	/**
	 * Handle ad server header setup code.
	 *
	 * Should be implemented by all child classes, if needed.
	 * @access public
	 * @return array
	 */
	public function header_setup() {
		// Get the active ad layer.
		// If this is not defined, we should not proceed.
		$ad_layer = Ad_Layers::instance()->get_ad_layer();
		if ( empty( $ad_layer ) ) {
			return;
		}
	
		do_action( 'ad_layers_dfp_before_setup' ); ?>
		?>
		<script type='text/javascript'>
		var dfpAdUnits = [];
		var googletag = googletag || {};
		googletag.cmd = googletag.cmd || [];
		(function() {
		var gads = document.createElement('script');
		gads.async = true;
		gads.type = 'text/javascript';
		var useSSL = 'https:' == document.location.protocol;
		gads.src = (useSSL ? 'https:' : 'http:') + 
		'//www.googletagservices.com/tag/js/gpt.js';
		var node = document.getElementsByTagName('script')[0];
		node.parentNode.insertBefore(gads, node);
		})();
		</script>
		<?php do_action( 'ad_layers_dfp_after_setup' ); ?>
		<script type="text/javascript">
		googletag.cmd.push(function() {
			<?php
				// Add the ad units
				$this->ad_unit_js( $ad_layer );
				
				// Add custom targeting
				$this->targeting_js( $ad_layer );
			
				echo apply_filters( 'ad_layers_dfp_async_rendering', "googletag.pubads().enableAsyncRendering();\n" );
				echo apply_filters( 'ad_layers_dfp_collapse_empty_divs', "googletag.pubads().collapseEmptyDivs();\n" );
				
				do_action( 'ad_layers_dfp_custom_targeting' );
			?>
			googletag.enableServices();
		});
		<?php do_action( 'ad_layers_dfp_after_ad_units' ); ?>
		</script>
		<?php
	}
	
	/**
	 * Returns the ad server settings fields to merge into the ad settings page.
	 *
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_settings_fields() {
		if ( ! class_exists( 'Fieldmanager_Field' ) ) {
			return array();
		}
	
		// Ad unit args may differ if custom targeting variables are present
		$ad_unit_args = array(
			'collapsible' => true,
			'collapsed' => true,
			'limit' => 0,
			'extra_elements' => 0,
			'label' => __( 'Ad Units', 'ad-layers' ),
			'label_macro' => array( __( 'Ad Unit: %s', 'ad-layers' ), 'code' ),
			'add_more_label' => __( 'Add Ad Unit', 'ad-layers' ),
			'children' => array(
				'code' => new Fieldmanager_Textfield(
					array(
						'label' => __( 'Code', 'ad-layers' ),
					)
				),
				'sizes' => new Fieldmanager_Group( array(
					'limit' => 0,
					'extra_elements' => 0,
					'one_label_per_item' => false,
					'label' => __( 'Sizes', 'ad-layers' ),
					'add_more_label' => __( 'Add Size', 'ad-layers' ),
					'children' => $this->get_size_options(),
				) ),
			)
		);
		
		// Verify if targeting args should be added
		$targeting_args = $this->get_custom_targeting_args( 'custom_targeting' );
		if ( ! empty( $targeting_args ) ) {
			$targeting_args['label'] = __( 'Custom Targeting', 'ad-layers' );
			$targeting_args['one_label_per_item'] = false;
			$ad_unit_args['children']['custom_targeting'] = new Fieldmanager_Group( apply_filters( 'ad_layers_dfp_custom_targeting_field_args', $targeting_args ) );
		}
	
		return apply_filters( 'ad_layers_dfp_get_settings_fields', array(
			'account_id' => new Fieldmanager_Textfield(
				array(
					'label' => __( 'DFP Account ID', 'ad-layers' ),
				)
			),
			'path_templates' => new Fieldmanager_Group( array(
				'collapsible' => true,
				'collapsed' => true,
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Path Templates', 'ad-layers' ),
				'label_macro' => array( __( 'Path Template: %s', 'ad-layers' ), 'path_template' ),
				'add_more_label' => __( 'Add Path Template', 'ad-layers' ),
				'children' => array(
					'path_template' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Path Template', 'ad-layers' ),
							'description' => __( 'See the Help tab above for formatting tags', 'ad-layers' ),
						)
					),
					'page_type' => new Fieldmanager_Select(
						array(
							'label' => __( 'Page Type', 'ad-layers' ),
							'options' => Ad_Layers::instance()->get_page_types(),
						)
					),
				),
			) ),
			'breakpoints' => new Fieldmanager_Group( array(
				'collapsible' => true,
				'collapsed' => true,
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Breakpoint', 'ad-layers' ),
				'label_macro' => array( __( 'Breakpoint: %s', 'ad-layers' ), 'title' ),
				'add_more_label' => __( 'Add Breakpoint', 'ad-layers' ),
				'children' => array(
					'title' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Title', 'ad-layers' ),
						)
					),
					'min_width' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Minimum Width', 'ad-layers' ),
						)
					),
					'min_height' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Minimum Height', 'ad-layers' ),
						)
					),
				),
			) ),
			'ad_units' => new Fieldmanager_Group( $ad_unit_args )
		) );
	}
	
	/**
	 * Returns the available size options for ad configuration.
	 *
	 * @access public
	 * @return array
	 */
	public function get_size_options() {
		if ( ! class_exists( 'Fieldmanager_Field' ) ) {
			return array();
		}
	
		$args = array(
			'width' => new Fieldmanager_Textfield(
				array(
					'label' => __( 'Width', 'ad-layers' ),
					'sanitize' => 'absint',
				)
			),
			'height' => new Fieldmanager_Textfield(
				array(
					'label' => __( 'Height', 'ad-layers' ),
					'sanitize' => 'absint',
				)
			),
			'out_of_page' => new Fieldmanager_Checkbox(
				array(
					'label' => __( 'Out of Page', 'ad-layers' ),
					'checked_value' => 'oop',
				)
			),
			'default_size' => new Fieldmanager_Checkbox(
				array(
					'label' => __( 'Default Size', 'ad-layers' ),
					'checked_value' => 'default',
				)
			),
		);
		
		// Add any defined breakpoints
		$breakpoints = $this->get_setting( 'breakpoints' );
		if ( ! empty( $breakpoints ) ) {
			$args['breakpoints'] = new Fieldmanager_Checkboxes( array(
				'label' => __( 'Breakpoints', 'ad-layers' ),
				'options' => wp_list_pluck( $breakpoints, 'title' ),
			) );
		}
		
		return $args;
	}
	
	/**
	 * Creates the ad unit Javascript.
	 *
	 * @access private
	 * @param array $ad_layer
	 */
	private function ad_unit_js( $ad_layer ) {
		// Ensure breakpoints are set
		$ad_setup = $this->get_settings();
		if ( empty( $ad_setup ) ) {
			return;
		}
		
		// Get the units included in this ad layer
		$this->ad_units = get_post_meta( $ad_layer['post_id'], 'ad_layer_ad_units', true );
		if ( ! empty( $this->ad_units ) ) {
			$this->ad_units = apply_filters( 'ad_layers_dfp_ad_units', wp_list_pluck( $this->ad_units, 'custom_targeting', 'ad_unit' ) );
		} else {
			return;
		}
				
		// Loop through the breakpoints and add the desired units
		$mapping_by_unit = array();
		$default_by_unit = array();
		$targeting_by_unit = array();
		$oop_units = array();
		foreach ( $ad_setup as $i => $breakpoint ) {
			// Ensure this breakpoint is valid or else skip it
			if ( empty( $breakpoint['ad_units'] ) ) {
				continue;
			}
			
			// Loop through the sizes and add them to the mapping
			foreach ( $breakpoint['ad_units'] as $ad_unit ) {
				// Skip this unit if invalid or not included in the layer
				if ( empty( $ad_unit['code'] ) || empty( $ad_unit['sizes'] ) || ! array_key_exists( $ad_unit['code'], $this->ad_units ) ) {
					continue;
				}
				
				// Set the sizes
				$sizes = array();
				foreach ( $ad_unit['sizes'] as $size ) {
					if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
						$sizes[] = array( absint( $size['width'] ), absint( $size['height'] ) );
						
						// If this is the default size, save it.
						// If more than one size is accidentally marked as default, the last one will be used.
						if ( ! empty( $size['default_size'] ) && 'default' == $size['default_size'] ) {
							$default_by_unit[ $ad_unit['code'] ] = array( absint( $size['width'] ), absint( $size['height'] ) );
						}
						
						// If this is an oop unit, note it.
						// If more than one size is accidentally marked as default, the last one will be used.
						if ( ! empty( $size['out_of_page'] ) && 'oop' == $size['out_of_page'] ) {
							$oop_units[] = $ad_unit['code'];
						}
					}
				}
				$sizes = apply_filters( 'ad_layers_dfp_ad_unit_sizes', $sizes, $ad_unit, $breakpoint );
				
				// Generate the mapping JS and store it with the unit
				$unit_key = $this->get_key( $ad_unit['code'] );
				if ( empty( $unit_key ) ) {
					continue; 
				}
				
				// Initialize as an array
				if ( ! isset( $mapping_by_unit[ $unit_key ] ) ) {
					$mapping_by_unit[ $unit_key ] = array();
				}
				
				$mapping_by_unit[ $unit_key ][] = sprintf(
					".addSize(%s,%s)",
					json_encode( array( absint( $breakpoint['min_width'] ), absint( $breakpoint['min_height'] ) ) ),
					json_encode( $sizes )
				);
				
				// Check for any global or ad layer specific targeting
				$custom_targeting = null;
				if ( ! empty( $this->ad_units[ $unit_key ] ) ) {
					$custom_targeting = $this->ad_units[ $unit_key ];
				} else if ( empty( $this->ad_units[ $unit_key ] ) && ! empty( $ad_unit['custom_targeting'] ) ) {
					$custom_targeting = $ad_unit['custom_targeting'];
				}
				
				if ( $custom_targeting ) {
					$targeting_by_unit[ $unit_key ] = $this->get_targeting_js_from_array( apply_filters( 'ad_layers_dfp_targeting_values_by_unit', $custom_targeting, $unit_key ) );
				}
			}
		}
		
		// Apply filters
		$mapping_by_unit = apply_filters( 'ad_layers_dfp_mapping_by_unit', $mapping_by_unit, $ad_layer );
		$default_by_unit = apply_filters( 'ad_layers_dfp_default_by_unit', $default_by_unit, $ad_layer );
		$targeting_by_unit = apply_filters( 'ad_layers_dfp_targeting_by_unit', $targeting_by_unit, $ad_layer );
		$oop_units = apply_filters( 'ad_layers_dfp_oop_units', $oop_units );
		
		// Echo the final mappings by ad unit
		foreach ( $mapping_by_unit as $ad_unit => $mappings ) {
			echo sprintf(
				"var mapping%s = googletag.sizeMapping()%s.build();\n",
				esc_js( $ad_unit ),
				implode( '', $mappings )
			);
		}
		
		// Get the page type
		$page_type = Ad_Layers::instance()->get_current_page_type();
			
		// Add the units
		foreach ( $this->ad_units as $ad_unit => $custom_targeting ) {
			// If no default size is defined, skip it
			if ( empty( $default_by_unit[ $ad_unit ] ) ) {
				continue;
			}
			
			// Finalize output for this unit and add it to the final return value
			// Add units are also saved to an array based on ad type so they can be refreshed if the page size changes
			echo sprintf(
				"dfpAdUnits['%s'] = googletag.%s('%s',%s,'%s')%s%s.addService(googletag.pubads());\n",
				esc_js( $ad_unit ),
				( in_array( $ad_unit, $oop_units ) ) ? 'defineOutOfPageSlot' : 'defineSlot',
				esc_js( $this->get_path( $page_type, $ad_unit ) ),
				json_encode( $default_by_unit[ $ad_unit ] ),
				esc_js( $this->get_ad_unit_id( $ad_unit ) ),
				( ! empty( $mapping_by_unit[ $ad_unit ] ) && ! in_array( $ad_unit, $oop_units ) ) ? '.defineSizeMapping(mapping' . esc_js( $this->get_key( $ad_unit ) ) . ')' : '',
				( ! empty( $targeting_by_unit[ $ad_unit ] ) ) ? $targeting_by_unit[ $ad_unit ] : '' // This is escaped above as it is built
			);
		}
	}
	
	/**
	 * Creates the DFP targeting Javascript.
	 *
	 * @access private
	 * @param array $ad_layer
	 */
	private function targeting_js( $ad_layer ) {
		// Handle any page level custom targeting specified for this ad layer.
		$custom_targeting = get_post_meta( $ad_layer['post_id'], 'ad_layer_custom_targeting', true );
		$custom_targeting = apply_filters( 'ad_layers_dfp_page_level_targeting_as_array', $custom_targeting );

		if ( empty( $custom_targeting ) ) {
			return;
		}

		$targeting_values = apply_filters( 'ad_layers_dfp_page_level_targeting_output_html', $this->get_targeting_js_from_array( $custom_targeting ) );

		// Add the JS
		if ( ! empty( $targeting_values ) ) {
			echo 'googletag.pubads()' . $targeting_values . ";\n";
		}
	}
	
	/**
	 * Creates the DFP targeting Javascript from an array of custom values.
	 *
	 * @access private
	 * @param array $custom_targeting
	 * @return string
	 */
	private function get_targeting_js_from_array( $custom_targeting ) {
		$targeting_values = '';
		foreach ( $custom_targeting as $custom_target ) {
			$values = ( isset( $custom_target['values'] ) ) ? $custom_target['values'] : null;
			$targeting_value = $this->get_targeting_value( $custom_target['custom_variable'], $custom_target['source'], $values );
			if ( ! empty( $targeting_value ) ) {
				$targeting_values .= $this->get_targeting_value_js( $custom_target['custom_variable'], $targeting_value );
			}
		}
		
		return $targeting_values;
	}
	
	/**
	 * Gets the DFP targeting JS for a single key/value pair.
	 *
	 * @access private
	 * @param string $key
	 * @param mixed $value
	 * @return string
	 */
	private function get_targeting_value_js( $key, $value ) {
		return sprintf(
			".setTargeting('%s',%s)",
			esc_js( $key ),
			json_encode( $value )
		);
	}
	
	/**
	 * Creates the DFP targeting key/value pair for a single targeting variable.
	 *
	 * @access public
	 * @param string $key
	 * @param string $source
	 * @param array $values
	 * @return array
	 */
	public function get_targeting_value( $key, $source, $values = null ) {
		$targeting_value = null;
		$queried_object = get_queried_object();
		
		switch ( $source ) {
			case 'other':
				if ( null !== $values ) {
					$targeting_value = $values;
				}
				break;
			case 'author':
				if ( is_singular() ) {
					$targeting_value = get_the_author_meta( apply_filters( 'ad_layers_dfp_author_targeting_field', 'display_name' ), $queried_object->post_author );
				} else if ( is_author() ) {
					$targeting_value = $queried_object->display_name;
				}
				break;
			case 'post_type':
				if ( is_singular() ) {
					$targeting_value = get_post_type();
				} else if ( is_post_type_archive() ) {
					$targeting_value = $queried_object->name;
				}
				break;
			default:
				if ( taxonomy_exists( $source ) ) {
					if ( is_singular() ) {
						$terms = get_the_terms( get_the_ID(), $source );
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
							$value = wp_list_pluck( $terms, apply_filters( 'ad_layers_dfp_term_targeting_field', 'slug' ) );
						} else {
							$value = array();
						}
						$targeting_value = $value;
					} else if ( is_tax() ) {
						$targeting_value = $queried_object->slug;
					}
				}
				break;
		}
		
		return apply_filters( 'ad_layers_dfp_custom_targeting_value', $targeting_value, $key, $source, $values );
	}
	
	/**
	 * Gets available ad units.
	 *
	 * @access public
	 * @return array
	 */
	public function get_ad_units() {
		$ad_unit_setup = $this->get_setting( 'ad_units' );
		$ad_units = wp_list_pluck( $ad_unit_setup, 'code' );
		sort( $ad_units );
		return $ad_units;
	}
	
	/**
	 * Generate the code for a single ad unit.
	 * The terminology of the plugin uses units but we use unit here to be consistent with DFP.
	 *
	 * @access public
	 * @param string $ad_unit
	 * @param boolean $echo
	 * @return string
	 */
	public function get_ad_unit( $ad_unit, $echo = true ) {
		// Make sure this is in the current ad layer and an ad layer is defined
		if ( empty( $this->ad_units ) || ! array_key_exists( $ad_unit, $this->ad_units ) ) {
			return;
		}
	
		$ad_unit_id = $this->get_ad_unit_id( $ad_unit );
		$ad_unit_class = apply_filters( 'ad_layers_dfp_ad_unit_class', sanitize_html_class( 'dfp-' . $ad_unit ), $ad_unit );
		$output = '';
		$output = "<div id='" . esc_attr( $ad_unit_id ) . "' class='dfp-ad " . esc_attr( $ad_unit_class ) . "'>\n";
		$output .= "\t<script type='text/javascript'>\n";
		$output .= "\t\tif ( typeof googletag != 'undefined' ) {\n";
		$output .= "\t\tgoogletag.cmd.push(function() { googletag.display('" . esc_js( $ad_unit_id ) . "'); });\n";
		$output .= "\t\t}\n";
		$output .= "\t</script>\n";
		$output .= "</div>\n";
		
		$output = apply_filters( 'ad_layers_dfp_ad_unit_html', $output, $ad_unit );
		
		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}
	
	/**
	 * Create a key from a string value, likely for use in Javascript.
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	public function get_key( $value ) {
		return apply_filters( 'ad_layers_dfp_breakpoint_key', preg_replace( '/[^a-zA-Z0-9]+/', '', sanitize_key( $value ) ) );
	}
	
	/**
	 * Get an ad unit name for referencing a particular location on page.
	 *
	 * @access public
	 * @param string $ad_unit
	 * @return string
	 */
	public function get_ad_unit_id( $ad_unit ) {
		return apply_filters( 'ad_layers_dfp_ad_unit_id', $this->ad_unit_prefix . $ad_unit, $ad_unit );
	}
	
	/**
	 * Gets the correct path for the current page being displayed.
	 *
	 * @access public
	 * @param string $page_type
	 * @param string $ad_unit
	 * @return string
	 */
	public function get_path( $page_type, $ad_unit = '' ) {
		// The default path template should always just be the account ID and domain
		$account_id = $this->get_setting( 'account_id' );
		$domain = $this->get_domain();
		$path = '/' . $account_id . '/' . $domain;
	
		// Get all available path templates
		$path_templates = $this->get_setting( 'path_templates' );
		if ( ! empty( $path_templates ) ) {
			// Re-sort by page type
			$path_templates_by_page_type = wp_list_pluck( $path_templates, 'path_template', 'page_type' );
			
			// If we have a match, use that template
			if ( ! empty( $path_templates_by_page_type[ $page_type ] ) ) {
				$path_template = $path_templates_by_page_type[ $page_type ];
				
				// Handle any formatting tags
				preg_match_all( apply_filters( 'ad_layers_dfp_formatting_tag_pattern', $this->formatting_tag_pattern ), $path_template, $matches );
				if ( ! empty( $matches[0] ) ) {
					// Build a list of found tags for replacement
					$replacements = array();
					$unique_matches = array_unique( $matches[0] );
					
					// Iterate over and replace each
					foreach ( $this->formatting_tags as $tag => $description ) {
						if ( in_array( $tag, $unique_matches ) ) {
							$value = null;
							
							// Handle built-in formatting tags
							switch ( $tag ) {
								case '#account_id#':
									$value = $account_id;
									break;
								case '#domain#':
									$value = $domain;
									break;
								case '#ad_unit#':
									$value = $ad_unit;
									break;
								case '#post_type#':
									if ( is_post_type_archive() ) {
										$value = get_queried_object()->name;
									} else if ( is_singular() ) {
										$value = get_post_type();
									}
									break;
								default:
									// This is one of the available taxonomy tags if it's not custom
									// which would be handled later by the filter.
									$taxonomy = str_replace( '#', '', $tag );
									if ( taxonomy_exists( $taxonomy ) ) {
										if ( is_tax() ) {
											$value = $this->get_term_path( get_queried_object()->term_id, $taxonomy );
										} else if ( is_singular() ) {
											$terms = get_the_terms( get_the_ID(), $taxonomy );
											if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
												$term = array_shift( $terms );
												$value = $this->get_term_path( $term->term_id, $taxonomy );
											}
										}
										
										// If nothing was found, strip this off the path
										if ( null === $value ) {
											$value = '';
										}
									}
									break;
							}
							
							// Always allow filtering of the value for custom formatting tags
							$value = apply_filters( 'ad_layers_dfp_formatting_tag_value', $value, $tag, $page_type, $ad_unit );
							
							// If a value was found, we'll replace it.
							// Otherwise, the "match" will be ignored.
							if ( null !== $value ) {
								$replacements[ $tag ] = $value;
							}
						}
					}
					
					// Do the replacements and create the final path
					if ( ! empty( $replacements ) ) {
						$path = str_replace( array_keys( $replacements ), array_values( $replacements ), $path_template );
					}
				}
			}
		}
		
		// Finally, the path should never end in a trailing slash.
		// This would possibly mean a term was stripped that wasn't matched.
		$path = untrailingslashit( $path );
		
		return apply_filters( 'ad_layers_dfp_path', $path, $page_type );
	}
	
	/**
	 * Gets the path for a term. Will just return the term if not hierarchical.
	 *
	 * @access public
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return string
	 */
	public function get_term_path( $term_id, $taxonomy, $path = '' ) {
		// Get the WordPress term object
		$term = get_term( $term_id, $taxonomy );

		// Add the term to the front of the path and always append a leading slash.
		$path = $term->slug . $path;

		// Determine if this term has a parent. If so, append to the path.
		if ( ! empty( $term->parent ) ) {
			// Add a leading slash since we are appending another level
			$path = '/' . $path;

			// Add the parent term
			$path = $this->get_term_path( $term->parent, $taxonomy, $path );
		}

		// Return the path
		return $path;
	}
	
	/**
	 * Add tabs to the help menu on the plugin options page.
	 *
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'       => 'dfp-setup-help',
			'title'    => __( 'Ad Server Settings Help', 'wp-seo' ),
			'callback' => array( $this, 'formatting_tags_help_tab' ),
		) );
	}
	
	/**
	 * Render the content of the help tab.
	 * The tab displays a table of each available formatting tab and any
	 * provided description.
	 *
	 * @access public
	 */
	public function formatting_tags_help_tab() {
		if ( ! empty( $this->formatting_tags ) ) :
			?>
			<aside>
				<h2><?php esc_html_e( 'The following formatting tags are available for the path template:', 'wp-seo' ); ?></h2>
				<dl class="formatting-tags">
					<?php foreach( $this->formatting_tags as $tag => $description ) : ?>
						<div class="formatting-tag-wrapper">
							<dt class="formatting-tag-name"><?php echo esc_html( $tag ); ?></dt>
							<dd class="formatting-tag-description"><?php echo esc_html( $description ); ?></dd>
						</div><!-- .formatting-tag-wrapper -->
					<?php endforeach; ?>
				</dl>
			</aside>
			<?php
		endif;
	}
	
	/**
	 * Gets the current DFP-specific settings required to build the setup code.
	 * These are cached from the main Ad_Layers_Ad_Server settings on update for efficiency.
	 *
	 * @access public
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( $this->cache_key );
		
		// If these settings are empty and this plugin in use, this is *very* likely a caching error.
		// Let's at least try to regenerate these and if it fails, accept our fate.
		if ( empty( $settings ) ) {
			$settings = $this->cache_settings( $this->option_name, null, get_option( $this->option_name ), true );
		}
		
		// Return what we have at this point
		return $settings;
	}
	
	/**
	 * Cache the settings in a format more conducive to generating tags.
	 * The default Fieldmanager format currently used is great for the user interface
	 * but not as well suited to how the DFP setup code is actually generated.
	 *
	 * @access public
	 * @param string $option
	 * @param mixed $old_value
	 * @param mixed $value
	 * @param boolean $return
	 * @return mixed
	 */
	public function cache_settings( $option, $old_value, $value, $return = false ) {
		// Make sure this is saving ad server settings
		if ( $option !== $this->option_name ) {
			return;
		}
	
		$cached_setup = array();

		// Don't bother if no breakpoints or no ad units are set
		if ( empty( $value['breakpoints'] ) || empty( $value['ad_units'] ) ) {
			return;
		}
		
		foreach ( $value['breakpoints'] as &$breakpoint ) {
			// Add ad units to the breakpoint data
			$breakpoint['ad_units'] = array();
			
			// Get all ad units for this breakpoint and add their data to the breakpoint
			if ( ! empty( $value['ad_units'] ) ) {
				foreach ( $value['ad_units'] as $ad_unit ) {
					// Iterate over the sizes and find ones used by this breakpoint
					if ( ! empty( $ad_unit['sizes'] ) ) {
						foreach ( $ad_unit['sizes'] as $i => &$size ) {
							// If this ad unit isn't used by the breakpoint, drop it
							if ( ! in_array( $breakpoint['title'], $size['breakpoints'] ) ) {
								unset( $ad_unit['sizes'][ $i ] );
							} else {
								// Leave it alone, but drop the breakpoint info since the cache won't need it
								unset( $size['breakpoints'] );
							}
						}
				
						// If there are any ad unit sizes left, add to the breakpoint
						if ( ! empty( $ad_unit['sizes'] ) ) {
							$breakpoint['ad_units'][] = $ad_unit;
						}
					}
				}
			}
		}
		
		// Store the cached data
		update_option( $this->cache_key, $value['breakpoints'] );
		
		// The action hook doesn't need a return value, but other usage of this plugin might
		if ( true === $return ) {
			return $value['breakpoints'];
		}
	}
}

Ad_Layers_DFP::instance();

endif;