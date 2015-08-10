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
	 * @access public
	 * @var array
	 */
	public $display_label = 'DoubleClick for Publishers (DFP)';
	
	/**
	 * Formatting tags.
	 * @access public
	 * @var array
	 */
	public $formatting_tags;
	
	/**
	 * The regular expression used to find formatting tags.
	 * @access public
	 * @var string
	 */
	public $formatting_tag_pattern = '/#[a-zA-Z\_]+#/';
	
	/**
	 * Ad slot prefix.
	 * @access public
	 * @var string
	 */
	public $ad_slot_prefix = 'div-gpt-ad-';
	
	/**
	 * Stores size mappings as these are also used in the construction of paths.
	 * @access private
	 * @var string
	 */
	private $mappings;

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Define the available formatting tags
		$this->formatting_tags = apply_filters( 'ad_layers_dfp_formatting_tags', $this->set_formatting_tags() );
		
		// Allow filtering of the ad slot prefix
		$this->ad_slot_prefix = apply_filters( 'ad_layers_dfp_ad_slot_prefix', $this->ad_slot_prefix );
		
		// Add a help tab
		add_action( 'load-' . Ad_Layers_Post_Type::instance()->get_post_type() . '_page_' . $this->option_name, array( $this, 'add_help_tab' ) );
	}
	
	/**
	 * Returns the ad server display label.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_display_label() {
		return $this->display_label;
	}
	
	/**
	 * Set the available formatting tags.
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
	 * Should be implemented by all child classes, if needed.
	 * @access public
	 * @return array
	 */
	public function header_setup() {
		do_action( 'ad_layers_dfp_before_setup' ); ?>
		?>
		<script type='text/javascript'>
		var dfp_ad_slots = [];
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
				// Add mapping JS for responsive ad serving
				$this->mapping_js();
				
				// Add the ad slots
				$this->ad_slot_js();
				
				// Add custom targeting
				$this->targeting_js();
			
				echo apply_filters( 'ad_layers_dfp_async_rendering', "googletag.pubads().enableAsyncRendering();\n" );
				echo apply_filters( 'ad_layers_dfp_collapse_empty_divs', "googletag.pubads().collapseEmptyDivs();\n" );
				
				do_action( 'ad_layers_dfp_custom_targeting' );
			?>
			googletag.enableServices();
		});
		<?php do_action( 'ad_layers_dfp_after_ad_slots' ); ?>
		</script>
		<?php
	}
	
	/**
	 * Returns the ad server settings fields to merge into the ad settings page.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
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
							'options' => $this->get_page_types(),
						)
					),
				),
			) ),
			'ad_setup' => new Fieldmanager_Group( array(
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
					'ad_units' => new Fieldmanager_Group( array(
						'limit' => 0,
						'extra_elements' => 0,
						'label' => __( 'Ad Units', 'ad-layers' ),
						'label_macro' => array( __( 'Ad Unit: %s', 'ad-layers' ), 'code' ),
						'add_more_label' => __( 'Add Ad Unit', 'ad-layers' ),
						'collapsible' => true,
						'collapsed' => true,
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
								'children' => array(
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
								)
							) )
						)
					) )
				)
			) )
		);
	}
	
	/**
	 * Creates the mapping Javascript required for responsive ad serving.
	 * @access private
	 */
	private function mapping_js() {
		// Get the ad setup and ensure it's valid
		$ad_setup = $this->get_setting( 'ad_setup' );
		if ( empty( $ad_setup ) ) {
			return;
		}
		
		// Loop through the sizes available for each breakpoint
		$this->mappings = array();
		foreach ( $ad_setup as $i => $breakpoint ) {
			// Ensure this breakpoint is valid or else skip it
			if ( empty( $breakpoint['ad_units'] ) ) {
				continue;
			}
			
			// Loop through the sizes and add them to the mapping
			foreach ( $breakpoint['ad_units'] as $ad_unit ) {
				// Skip this unit if invalid
				if ( empty( $ad_unit['code'] ) || empty( $ad_unit['sizes'] ) ) {
					continue;
				}
				
				// Set the sizes
				$sizes = array();
				foreach ( $ad_unit['sizes'] as $size ) {
					if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
						$sizes[] = array( absint( $size['width'] ), absint( $size['height'] ) );
					}
				}
				$sizes = apply_filters( 'ad_layers_ad_unit_sizes', $sizes, $ad_unit, $breakpoint );
				
				// Generate the mapping JS and store it with the unit
				$unit_key = $this->get_key( $ad_unit['code'] );
				if ( empty( $unit_key ) ) {
					continue; 
				}
				
				// Initialize as an array
				if ( ! isset( $this->mappings[ $unit_key ] ) ) {
					$this->mappings[ $unit_key ] = array();
				}
				
				$this->mappings[ $unit_key ][] = sprintf(
					".addSize(%s,%s)",
					json_encode( array( absint( $breakpoint['min_width'] ), absint( $breakpoint['min_height'] ) ) ),
					json_encode( $sizes )
				);
			}
		}
		
		// Echo the final mappings by ad unit
		foreach ( $this->mappings as $ad_unit => $mappings ) {
			echo sprintf(
				"var mapping%s = googletag.sizeMapping()%s.build();\n",
				esc_js( preg_replace( '/[^a-zA-Z0-9]+/', '', $ad_unit ) ),
				implode( '', $mappings )
			);
		}
	}
	
	/**
	 * Creates the ad slot Javascript.
	 * @access private
	 */
	private function ad_slot_js() {
		// Get the page type
		$page_type = $this->get_current_page_type();
		
		// Get the path
		$path = $this->get_path( $page_type );
			
		// Iterate over the slots for this breakpoint
		$ad_slot_num = 0;
		foreach ( $this->get_ad_slots as $ad_slot => $sizes ) {
		
			// If no size mappings exist for this slot, skip it.
			// It shouldn't even be displayed.
			if ( ! isset( $this->mappings[ $ad_slot ] ) ) {
				continue;
			}
		
			// Store the ad slot as available for this page
			$this->available_slots[] = $ad_slot;
		
			// Build the slot name
			$slot_name = $this->ad_slot_prefix . $ad_slot;
		
			// Finalize output for this slot and add it to the final return value
			// Add slots are also saved to an array based on ad type so they can be refreshed if the page size changes
			echo sprintf(
				"dfp_ad_slots[%s] = googletag.defineSlot('%s%s',%s,'%s').defineSizeMapping(mapping%s).addService(googletag.pubads());\n",
				esc_js( $ad_slot_num ),
				esc_js( $path_prefix ),
				esc_js( $ad_slot ),
				json_encode( $this->ad_defaults[ $ad_slot ] ),
				esc_js( $slot_name ),
				esc_js( preg_replace( '/[^a-zA-Z0-9]+/', '', $ad_slot ) )
			);
		
			$ad_slot_num++;
		}
		
		// Manually add interstitials
		$oop_slot_name = 'Interstitial';
		echo sprintf(
			"dfp_ad_slots[%s] = googletag.defineOutOfPageSlot('%s%s', '%s').addService(googletag.pubads());\n",
			esc_js( $ad_slot_num ),
			esc_js( $path_prefix ),
			esc_js( $oop_slot_name ),
			esc_js( $this->ad_slot_prefix . $oop_slot_name )
		);
	}
	
	/**
	 * Creates the DFP targeting Javascript.
	 * @access private
	 */
	private function targeting_js() {
		/*if ( ! empty( $targeting_js ) ) {
			echo 'googletag.pubads()' . $targeting_js . ";\n";
		}*/
	}
	
	/**
	 * Gets available ad slots.
	 * @access public
	 * @return array
	 */
	public function get_ad_slots() {
		$ad_slots = array();
		$ad_setup = $this->get_setting( 'ad_setup' );
		if ( ! empty( $ad_setup ) ) {
			foreach ( $ad_setup as $breakpoint ) {
				if ( ! empty( $breakpoint['ad_units'] ) ) {
					foreach ( $breakpoint['ad_units'] as $ad_unit ) {
						if ( ! empty( $ad_unit['code'] ) ) {	
							$ad_slots[] = $ad_unit['code'];
						}
					}
				}
			}
			$ad_slots = array_unique( $ad_slots );
			sort( $ad_slots );
		}
		return $ad_slots;
	}
	
	/**
	 * Generate the code for a single ad slot.
	 * @access public
	 * @param string $slot
	 * @return string
	 */
	public function get_ad_slot( $slot ) {
		$slot_name = $this->ad_slot_prefix . $slot;
		$slot_class = apply_filters( 'ad_layers_dfp_slot_class', sanitize_html_class( 'dfp-' . $slot ), $slot );
		$output = '';
		$output = "<div id='" . esc_attr( $slot_name ) . "' class='dfp-ad " . esc_attr( $slot_class ) . "'>\n";
		$output .= "\t<script type='text/javascript'>\n";
		$output .= "\t\tif ( typeof googletag != 'undefined' ) {\n";
		$output .= "\t\tgoogletag.cmd.push(function() { googletag.display('" . esc_js( $slot_name ) . "'); });\n";
		$output .= "\t\t}\n";
		$output .= "\t</script>\n";
		$output .= "</div>\n";
		
		return apply_filters( 'ad_layers_dfp_slot_html', $output, $slot );
	}
	
	/**
	 * Create a key from a string value, likely for use in Javascript.
	 * @access public
	 * @param string $value
	 * @return string
	 */
	public function get_key( $value ) {
		return apply_filters( 'ad_layers_dfp_breakpoint_key', sanitize_key( $value ) );
	}
	
	/**
	 * Gets the correct path for the current page being displayed.
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
							$value = '';
							
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
									if ( taxonomy_exists( str_replace( '#', '', $tag ) ) ) {
										if ( is_tax() ) {
											$value = $this->get_term_path( get_queried_object()->term_id, $tag );
										} else if ( is_singular() ) {
											$terms = get_the_terms( get_the_ID(), $tag );
											if ( ! empty( $terms ) ) {
												$term = array_shift( $terms );
												$value = $this->get_term_path( $term->term_id, $tag );
											}
										}
									}
									break;
							}
							
							// Always allow filtering of the value for custom formatting tags
							$value = apply_filters( 'ad_layers_dfp_formatting_tag_value', $value, $tag, $page_type, $ad_unit );
							
							// If a value was found, we'll replace it.
							// Otherwise, the "match" will be ignored.
							if ( ! empty( $value ) ) {
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
		
		return apply_filters( 'ad_layers_dfp_path', $path, $page_type );
	}
	
	/**
	 * Gets the path for a term. Will just return the term if not hierarchical.
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
	 * Render the content of the "Formatting Tags" help tab.
	 *
	 * The tab displays a table of each available formatting tab and any
	 * provided description.
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
}

Ad_Layers_DFP::instance();

endif;