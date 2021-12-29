<?php
/**
 * Implements the DFP Ad Server for Ad Layers.
 *
 * @package Ad_Layers
 */

use Ad_Layers\Ad_Layers;
use Ad_Layers\Ad_Layers_Post_Type;
use Ad_Layers\Ad_Servers\Ad_Server;

if ( ! class_exists( 'Ad_Layers_DFP' ) ) :

	/**
	 * Ad_Layers_DFP Class.
	 */
	class Ad_Layers_DFP extends Ad_Server {

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
		public $ad_units = [];

		/**
		 * Size mappings by unit.
		 *
		 * @access public
		 * @var array
		 */
		public $mapping_by_unit = [];

		/**
		 * Default sizes by unit.
		 *
		 * @access public
		 * @var array
		 */
		public $default_by_unit = [];

		/**
		 * Raw values for targeting by unit.
		 *
		 * @access public
		 * @var array
		 */
		public $raw_targeting_by_unit = [];

		/**
		 * Targeting by unit.
		 *
		 * @access public
		 * @var array
		 */
		public $targeting_by_unit = [];

		/**
		 * Out of page units.
		 *
		 * @access public
		 * @var array
		 */
		public $oop_units = [];

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
		 * List of ads for which to skip rendering.
		 *
		 * @var array
		 */
		public $do_not_render_ads = [];

		/**
		 * List of ads for which to skip rendering.
		 *
		 * @var array
		 */
		public $ad_unit_paths = [];

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			// Define the available formatting tags.
			$this->formatting_tags = apply_filters( 'ad_layers_dfp_formatting_tags', $this->set_formatting_tags() );

			// Allow filtering of the ad unit prefix.
			$this->ad_unit_prefix = apply_filters( 'ad_layers_dfp_ad_unit_prefix', $this->ad_unit_prefix );

			// Add a help tab.
			add_action( 'load-' . Ad_Layers_Post_Type::instance()->get_post_type() . '_page_' . $this->option_name, [ $this, 'add_help_tab' ] );

			// Handle caching.
			add_action( 'update_option', [ $this, 'cache_settings' ], 10, 3 );

			// Add the path override field to the ad layer ad units.
			add_filter( 'ad_layers_ad_units_field_args', [ $this, 'ad_layer_path_overrides' ] );
		}

		/**
		 * Load scripts.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {
			// Load the base Javascript library (in header to ensure early ad loading).
			wp_enqueue_script(
				$this->handle,
				get_ad_layers_path( 'adLayersDfp.js' ),
				[ 'jquery' ],
				get_ad_layers_hash( 'adLayersDfp.js' ),
				false
			);

			// Localize the base API with static text strings so they can be translated.
			wp_localize_script(
				$this->handle,
				'adLayersDFP',
				[
					'layerDebugLabel'   => __( 'Current ad layer', 'ad-layers' ),
					'consoleDebugLabel' => __( 'Switch to Google console', 'ad-layers' ),
					'adUnitPrefix'      => $this->ad_unit_prefix,
				]
			);
			wp_localize_script(
				$this->handle,
				'AdLayersDFPAPI',
				[]
			);
		}

		/**
		 * Set the available formatting tags.
		 *
		 * Should be implemented by all child classes.
		 *
		 * @access public
		 * @return array
		 */
		public function set_formatting_tags() {
			// Set the base options.
			$formatting_tags = [
				'#account_id#' => __( 'Your DFP account ID', 'ad-layers' ),
				'#domain#'     => __( 'The domain of the current site, taken from get_site_url', 'ad-layers' ),
				'#ad_unit#'    => __( 'The ad unit name', 'ad-layers' ),
				'#post_type#'  => __( 'The post type of the current page, if applicable', 'ad-layers' ),
			];

			// Add all registered taxonomies as an option since these are commonly used.
			$taxonomies = Ad_Layers::instance()->get_taxonomies();
			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy_name => $taxonomy_label ) {
					/* translators: taxonomy slug. */
					$formatting_tags[ '#' . $taxonomy_name . '#' ] = sprintf( __( 'The current term from the %s taxonomy, if applicable. If the taxonomy is hierarchical, each term in the hierarchy above the current term will be added to the path. If there is more than one term, only the first will be used.', 'ad-layers' ), $taxonomy_label );
				}
			}

			return apply_filters( 'ad_layers_dfp_formatting_tags', $formatting_tags );
		}

		/**
		 * Handle ad server header setup code.
		 *
		 * Should be implemented by all child classes, if needed.
		 *
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
			<?php if ( apply_filters( 'ad_layers_dfp_output_default_gpt_library_script', true, $this ) ) : ?>
				<script type='text/javascript'>
				var dfpAdUnits = {};
				var googletag = googletag || {};
				googletag.cmd = googletag.cmd || [];
				(function() {
				var gads = document.createElement('script');
				gads.async = true;
				gads.type = 'text/javascript';
				var useSSL = 'https:' === document.location.protocol;
				gads.src = (useSSL ? 'https:' : 'http:') +
				'//www.googletagservices.com/tag/js/gpt.js';
				var node = document.getElementsByTagName('script')[0];
				node.parentNode.insertBefore(gads, node);
				})();
				</script>
			<?php endif; ?>
			<?php do_action( 'ad_layers_dfp_after_setup' ); ?>
			<script type="text/javascript">
			var dfpBuiltMappings = {}, dfpAdUnits = {};
			googletag.cmd.push(function() {
				<?php
				// Add the ad units.
				$this->ad_unit_js( $ad_layer );

				// Add custom targeting.
				$this->targeting_js( $ad_layer );

				/**
				 * Fires after all the ad unit javascript has been output. This
				 * is a useful action to call additional methods on ad units.
				 *
				 * @param array $ad_layer The currently active ad layer.
				 * @param Ad_Layers_DFP $this This object.
				 */
				do_action( 'ad_layers_dfp_ad_unit_js_output', $ad_layer, $this );

				if ( apply_filters( 'ad_layers_dfp_enable_async_rendering', true, $this ) ) {
					echo "googletag.pubads().enableAsyncRendering();\n";
				}
				if ( apply_filters( 'ad_layers_dfp_single_request_mode', true, $this ) ) {
					echo "googletag.pubads().enableSingleRequest();\n";
				}
				if ( apply_filters( 'ad_layers_dfp_collapse_empty_divs', true, $this ) ) {
					echo "googletag.pubads().collapseEmptyDivs();\n";
				}

				do_action( 'ad_layers_dfp_custom_targeting' );
				?>

				if ( typeof AdLayersAPI === 'undefined' || ! AdLayersAPI.isDebug() ) {
					googletag.enableServices();
				}
			});
			<?php do_action( 'ad_layers_dfp_after_ad_units' ); ?>
			var dfpSizeMapping = <?php echo wp_json_encode( $this->mapping_by_unit ); ?>;
			var dfpAdLayer = <?php echo wp_json_encode( Ad_Layers::instance()->get_ad_layer() ); ?>;
			var dfpAdDetails = <?php echo wp_json_encode( $this->get_ad_details() ); ?>;
			</script>
			<?php
		}

		/**
		 * Returns the ad server settings fields to merge into the ad settings page.
		 *
		 * Should be implemented by all child classes.
		 *
		 * @access public
		 * @return array
		 */
		public function get_settings_fields() {
			if ( ! class_exists( '\Fieldmanager_Field' ) ) {
				return [];
			}

			// Ad unit args may differ if custom targeting variables are present.
			$ad_unit_args = [
				'collapsible'    => true,
				'collapsed'      => true,
				'limit'          => 0,
				'extra_elements' => 0,
				'label'          => __( 'Ad Units', 'ad-layers' ),
				/* translators: macro title. */
				'label_macro'    => [ __( 'Ad Unit: %s', 'ad-layers' ), 'code' ],
				'add_more_label' => __( 'Add Ad Unit', 'ad-layers' ),
				'children'       => [
					'code'          => new Fieldmanager_Textfield( __( 'Code', 'ad-layers' ) ),
					'path_override' => new Fieldmanager_TextField( __( 'Custom Path Template', 'ad-layers' ) ),
					'sizes'         => new Fieldmanager_Group(
						[
							'limit'              => 0,
							'extra_elements'     => 0,
							'one_label_per_item' => false,
							'label'              => __( 'Sizes', 'ad-layers' ),
							'add_more_label'     => __( 'Add Size', 'ad-layers' ),
							'children'           => $this->get_size_options(),
						]
					),
				],
			];

			// Verify if targeting args should be added.
			$targeting_args = $this->get_custom_targeting_args( 'custom_targeting' );
			if ( ! empty( $targeting_args ) ) {
				$targeting_args['label']                      = __( 'Custom Targeting', 'ad-layers' );
				$targeting_args['one_label_per_item']         = false;
				$ad_unit_args['children']['custom_targeting'] = new Fieldmanager_Group( apply_filters( 'ad_layers_dfp_custom_targeting_field_args', $targeting_args ) );
			}

			return apply_filters(
				'ad_layers_dfp_get_settings_fields',
				[
					'account_id'     => new Fieldmanager_Textfield(
						[
							'label' => __( 'DFP Account ID', 'ad-layers' ),
						]
					),
					'path_templates' => new Fieldmanager_Group(
						[
							'collapsible'    => true,
							'collapsed'      => true,
							'limit'          => 0,
							'extra_elements' => 0,
							'label'          => __( 'Path Templates', 'ad-layers' ),
							/* translators: macro title. */
							'label_macro'    => [ __( 'Path Template: %s', 'ad-layers' ), 'path_template' ],
							'add_more_label' => __( 'Add Path Template', 'ad-layers' ),
							'children'       => [
								'path_template' => new Fieldmanager_Textfield(
									[
										'label'       => __( 'Path Template', 'ad-layers' ),
										'description' => __( 'See the Help tab above for formatting tags', 'ad-layers' ),
									]
								),
								'page_type'     => new Fieldmanager_Select(
									[
										'label'   => __( 'Page Type', 'ad-layers' ),
										'options' => array_merge(
											[
												'all' => __( 'All Pages', 'ad-layers' ),
											],
											Ad_Layers::instance()->get_page_types()
										),
									]
								),
							],
						]
					),
					'breakpoints'    => new Fieldmanager_Group(
						[
							'collapsible'    => true,
							'collapsed'      => true,
							'limit'          => 0,
							'extra_elements' => 0,
							'label'          => __( 'Breakpoint', 'ad-layers' ),
							/* translators: macro title. */
							'label_macro'    => [ __( 'Breakpoint: %s', 'ad-layers' ), 'title' ],
							'add_more_label' => __( 'Add Breakpoint', 'ad-layers' ),
							'children'       => [
								'title'      => new Fieldmanager_Textfield(
									[
										'label' => __( 'Title', 'ad-layers' ),
									]
								),
								'min_width'  => new Fieldmanager_Textfield(
									[
										'label' => __( 'Minimum Width', 'ad-layers' ),
									]
								),
								'min_height' => new Fieldmanager_Textfield(
									[
										'label' => __( 'Minimum Height', 'ad-layers' ),
									]
								),
							],
						]
					),
					'ad_units'       => new Fieldmanager_Group( $ad_unit_args ),
				]
			);
		}

		/**
		 * Returns the available size options for ad configuration.
		 *
		 * @access public
		 * @return array
		 */
		public function get_size_options() {
			if ( ! class_exists( '\Fieldmanager_Field' ) ) {
				return [];
			}

			$args = [
				'width'        => new Fieldmanager_Textfield(
					[
						'label'    => __( 'Width', 'ad-layers' ),
						'sanitize' => 'absint',
					]
				),
				'height'       => new Fieldmanager_Textfield(
					[
						'label'    => __( 'Height', 'ad-layers' ),
						'sanitize' => 'absint',
					]
				),
				'out_of_page'  => new Fieldmanager_Checkbox(
					[
						'label'         => __( 'Out of Page', 'ad-layers' ),
						'checked_value' => 'oop',
					]
				),
				'default_size' => new Fieldmanager_Checkbox(
					[
						'label'         => __( 'Default Size', 'ad-layers' ),
						'checked_value' => 'default',
					]
				),
			];

			// Add any defined breakpoints.
			$breakpoints = $this->get_setting( 'breakpoints' );
			if ( ! empty( $breakpoints ) ) {
				$args['breakpoints'] = new Fieldmanager_Checkboxes(
					[
						'label'   => __( 'Breakpoints', 'ad-layers' ),
						'options' => wp_list_pluck( $breakpoints, 'title' ),
					]
				);
			}

			return $args;
		}

		/**
		 * Overrides path.
		 *
		 * @param array $ad_unit_args ad unit array of args.
		 * @return array moddified array.
		 */
		public function ad_layer_path_overrides( $ad_unit_args ) {
			$ad_unit_args['children']['path_override'] = new Fieldmanager_TextField( __( 'Custom Path Template', 'ad-layers' ) );
			return $ad_unit_args;
		}

		/**
		 * Creates the ad unit Javascript.
		 *
		 * @access private
		 * @param array $ad_layer array of ad laters.
		 */
		private function ad_unit_js( $ad_layer ) {
			// Ensure breakpoints are set.
			$ad_setup = $this->get_settings();
			if ( empty( $ad_setup ) ) {
				return;
			}

			// Get the units included in this ad layer.
			$this->get_ad_units_for_layer( $ad_layer['post_id'] );
			if ( empty( $this->ad_units ) ) {
				return;
			}

			// Expose ad units for filtering.
			$this->ad_units = apply_filters( 'ad_layers_dfp_ad_units', $this->ad_units, $this );

			// Loop through the breakpoints and add the desired units.
			foreach ( $ad_setup as $i => $breakpoint ) {
				// Ensure this breakpoint is valid or else skip it.
				if ( empty( $breakpoint['ad_units'] ) ) {
					continue;
				}

				// Loop through the sizes and add them to the mapping.
				foreach ( $breakpoint['ad_units'] as $ad_unit ) {
					// Skip this unit if invalid or not included in the layer.
					if ( empty( $ad_unit['code'] ) || empty( $ad_unit['sizes'] ) || ! array_key_exists( $ad_unit['code'], $this->ad_units ) ) {
						continue;
					}

					// Set the sizes.
					$sizes = [];
					foreach ( $ad_unit['sizes'] as $size ) {
						if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
							$sizes[] = [ absint( $size['width'] ), absint( $size['height'] ) ];

							// If this is the default size, save it.
							// If more than one size is accidentally marked as default, the last one will be used.
							if ( ! empty( $size['default_size'] ) && 'default' === $size['default_size'] ) {
								$this->default_by_unit[ $ad_unit['code'] ] = [ absint( $size['width'] ), absint( $size['height'] ) ];
							}

							// If this is an oop unit, note it.
							// If more than one size is accidentally marked as default, the last one will be used.
							if ( ! empty( $size['out_of_page'] ) && 'oop' === $size['out_of_page'] ) {
								$this->oop_units[] = $ad_unit['code'];
							}
						}
					}
					$sizes = apply_filters( 'ad_layers_dfp_ad_unit_sizes', $sizes, $ad_unit, $breakpoint );

					// If we have no defaults, assume to use all of the sizes for this breakpoint.
					if ( empty( $this->default_by_unit[ $ad_unit['code'] ] ) ) {
						$this->default_by_unit[ $ad_unit['code'] ] = $sizes;
					}

					// Generate the mapping JS and store it with the unit.
					$unit_key = $this->sanitize_key( $ad_unit['code'] );
					if ( empty( $unit_key ) ) {
						continue;
					}

					// Initialize as an array.
					if ( ! isset( $this->mapping_by_unit[ $unit_key ] ) ) {
						$this->mapping_by_unit[ $unit_key ] = [];
					}

					$this->mapping_by_unit[ $unit_key ][] = [
						[ absint( $breakpoint['min_width'] ), absint( $breakpoint['min_height'] ) ],
						$sizes,
					];

					// Check for any global or ad layer specific targeting.
					$custom_targeting = null;
					if ( ! empty( $this->ad_units[ $unit_key ] ) ) {
						$custom_targeting = $this->ad_units[ $unit_key ];
					} elseif ( ! empty( $ad_unit['custom_targeting'] ) ) {
						$custom_targeting = $ad_unit['custom_targeting'];
					}

					if ( $custom_targeting ) {
						$custom_targeting                         = apply_filters( 'ad_layers_dfp_targeting_values_by_unit', $custom_targeting, $unit_key );
						$this->raw_targeting_by_unit[ $unit_key ] = $custom_targeting;
						$this->targeting_by_unit[ $unit_key ]     = $this->get_targeting_js_from_array( $custom_targeting );
					}
				}
			}

			// Apply filters.
			$this->mapping_by_unit   = apply_filters( 'ad_layers_dfp_mapping_sizes', $this->mapping_by_unit, $ad_layer );
			$this->default_by_unit   = apply_filters( 'ad_layers_dfp_default_by_unit', $this->default_by_unit, $ad_layer );
			$this->targeting_by_unit = apply_filters( 'ad_layers_dfp_targeting_by_unit', $this->targeting_by_unit, $ad_layer );
			$this->oop_units         = apply_filters( 'ad_layers_dfp_oop_units', $this->oop_units );

			// Echo the final mappings by ad unit.
			foreach ( $this->mapping_by_unit as $ad_unit => $mappings ) {
				$mapping_js = '';
				foreach ( $mappings as $mapping ) {
					$mapping_js .= sprintf(
						'.addSize(%s,%s)',
						wp_json_encode( array_shift( $mapping ) ),
						wp_json_encode( array_shift( $mapping ) )
					);
				}

				$mapping_js = apply_filters( 'ad_layers_dfp_mapping_by_unit', $mapping_js, $ad_layer );

				printf(
					"dfpBuiltMappings[%s] = googletag.sizeMapping()%s.build();\n",
					wp_json_encode( $ad_unit ),
					$mapping_js // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}

			// Get the page type.
			$page_type = Ad_Layers::instance()->get_current_page_type();

			// Add the units.
			foreach ( $this->ad_units as $ad_unit => $custom_targeting ) {
				// If no default size is defined, skip it.
				if ( empty( $this->default_by_unit[ $ad_unit ] ) ) {
					continue;
				}

				if ( ! empty( $this->do_not_render_ads[ $ad_unit ] ) ) {
					continue;
				}

				$is_oop = in_array( $ad_unit, $this->oop_units, true );

				// Finalize output for this unit and add it to the final return value.
				// Ad units are also saved to an array based on ad type so they can.
				// be refreshed if the page size changes.
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				printf(
					"dfpAdUnits[%s] = googletag.%s(%s%s,%s)%s%s.addService(googletag.pubads());\n",
					wp_json_encode( $ad_unit ),
					$is_oop ? 'defineOutOfPageSlot' : 'defineSlot',
					wp_json_encode( $this->get_path( $page_type, $ad_unit ) ),
					// if this is not oop, this is an additional arg to the.
					// method call, and is prefixed with a comma:.
					$is_oop ? '' : ',' . wp_json_encode( $this->default_by_unit[ $ad_unit ] ),
					wp_json_encode( $this->get_ad_unit_id( $ad_unit ) ),
					( ! empty( $this->mapping_by_unit[ str_replace( '-', '', $ad_unit ) ] ) && ! in_array( $ad_unit, $this->oop_units, true ) ) ? '.defineSizeMapping(dfpBuiltMappings[' . wp_json_encode( $ad_unit ) . '])' : '',
					( ! empty( $this->targeting_by_unit[ $ad_unit ] ) ) ? $this->targeting_by_unit[ $ad_unit ] : '' // This is escaped above as it is built.
				);
				// phpcs:enable
			}
		}

		/**
		 * Get the ad units for the given ad layer id.
		 *
		 * @param  int $ad_layer_id ad-layer post ID.
		 * phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
		 * @return array
		 */
		public function get_ad_units_for_layer( $ad_layer_id ) {
			$global_ad_units = $this->get_setting( 'ad_units' );
			foreach ( $global_ad_units as $global_ad_unit ) {
				// TODO: $global_path_overrides doesn't exist.
				if ( ! empty( $global_ad_unit['code'] ) && ! empty( $global_ad_unit['path_override'] ) ) {
					$global_path_overrides[ $global_ad_unit['code'] ] = $global_ad_unit['path_override'];
				}
			}

			$this->ad_units = [];
			$temp_ad_units  = get_post_meta( $ad_layer_id, 'ad_layer_ad_units', true );
			if ( ! empty( $temp_ad_units ) ) {
				foreach ( $temp_ad_units as $ad_unit ) {
					if ( ! empty( $ad_unit['ad_unit'] ) ) {
						if ( ! isset( $ad_unit['custom_targeting'] ) ) {
							$ad_unit['custom_targeting'] = [];
						}
						$this->ad_units[ $ad_unit['ad_unit'] ] = $ad_unit['custom_targeting'];
						if ( ! empty( $ad_unit['do_not_render'] ) ) {
							$this->do_not_render_ads[ $ad_unit['ad_unit'] ] = true;
						}
						if ( ! empty( $ad_unit['path_override'] ) ) {
							$this->ad_unit_paths[ $ad_unit['ad_unit'] ] = $ad_unit['path_override'];
						} elseif ( ! empty( $global_path_overrides[ $ad_unit['ad_unit'] ] ) ) {
							$this->ad_unit_paths[ $ad_unit['ad_unit'] ] = $global_path_overrides[ $ad_unit['ad_unit'] ];
						}
					}
				}
			}

			return $this->ad_units;
			/* phpcs:enable */
		}

		/**
		 * Creates the DFP targeting Javascript.
		 *
		 * @access private
		 * @param array $ad_layer ad layer config array.
		 */
		private function targeting_js( $ad_layer ) {
			// Handle any page level custom targeting specified for this ad layer.
			$custom_targeting = get_post_meta( $ad_layer['post_id'], 'ad_layer_custom_targeting', true );
			$custom_targeting = apply_filters( 'ad_layers_dfp_page_level_targeting', $custom_targeting );

			if ( empty( $custom_targeting ) ) {
				return;
			}

			// Add the JS.
			if ( ! empty( $custom_targeting ) ) {
				echo 'googletag.pubads()' . apply_filters( 'ad_layers_dfp_page_level_targeting_output_html', $this->get_targeting_js_from_array( $custom_targeting ) ) . ";\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Get the main details for all ads for use by the JS API.
		 *
		 * @return array
		 */
		protected function get_ad_details() {
			$return = [];

			// Get the page type.
			$page_type = Ad_Layers::instance()->get_current_page_type();

			// Add the units.
			foreach ( $this->ad_units as $ad_unit => $custom_targeting ) {
				// If no default size is defined, skip it.
				if ( empty( $this->default_by_unit[ $ad_unit ] ) ) {
					continue;
				}

				$return[ $ad_unit ] = [
					'path'      => $this->get_path( $page_type, $ad_unit ),
					'sizes'     => $this->default_by_unit[ $ad_unit ],
					'targeting' => [],
				];

				if ( ! empty( $this->raw_targeting_by_unit[ $ad_unit ] ) ) {
					$return[ $ad_unit ]['targeting'] = $this->get_targeting_array_from_custom_values( $this->raw_targeting_by_unit[ $ad_unit ] );
				}
			}

			return $return;
		}

		/**
		 * Creates the DFP targeting Javascript from an array of custom values.
		 *
		 * @access private
		 * @param array $custom_targeting array of targeting values.
		 * @return string
		 */
		private function get_targeting_js_from_array( $custom_targeting ) {
			$targeting_values = '';
			$targeting_array  = $this->get_targeting_array_from_custom_values( $custom_targeting );

			foreach ( $targeting_array as $key => $values ) {
				$targeting_values .= $this->get_targeting_value_js( $key, $values );
			}

			return $targeting_values;
		}

		/**
		 * Creates a key => value array of targeting variables from custom
		 * values.
		 *
		 * @access private
		 * @param array $custom_targeting array of targeting values.
		 * @return string
		 */
		private function get_targeting_array_from_custom_values( $custom_targeting ) {
			$targeting_values = [];
			foreach ( (array) $custom_targeting as $custom_target ) {
				if ( ! empty( $custom_target['custom_variable'] ) ) {
					$values          = ( isset( $custom_target['values'] ) ) ? $custom_target['values'] : null;
					$targeting_value = $this->get_targeting_value( $custom_target['custom_variable'], $custom_target['source'], $values );
					if ( ! empty( $targeting_value ) ) {
						$targeting_values[ $custom_target['custom_variable'] ] = $targeting_value;
					}
				}
			}

			return $targeting_values;
		}

		/**
		 * Gets the DFP targeting JS for a single key/value pair.
		 *
		 * @access private
		 * @param string $key   targeting key.
		 * @param mixed  $value targeting value.
		 * @return string
		 */
		private function get_targeting_value_js( $key, $value ) {
			return sprintf(
				'.setTargeting(%s,%s)',
				wp_json_encode( $key ),
				wp_json_encode( $value )
			);
		}

		/**
		 * Creates the DFP targeting key/value pair for a single targeting variable.
		 *
		 * @access public
		 * @param string $key    targeting key.
		 * @param string $source source type.
		 * @param array  $values targeting values or null.
		 * @return array
		 */
		public function get_targeting_value( $key, $source, $values = null ) {
			$targeting_value = null;
			$queried_object  = get_queried_object();

			switch ( $source ) {
				case 'other':
					if ( null !== $values ) {
						$targeting_value = $values;
					}
					break;
				case 'author':
					if ( is_singular() ) {
						$targeting_value = get_the_author_meta( apply_filters( 'ad_layers_dfp_author_targeting_field', 'display_name' ), $queried_object->post_author );
					} elseif ( is_author() ) {
						$targeting_value = $queried_object->display_name;
					}
					break;
				case 'post_type':
					if ( is_singular() ) {
						$targeting_value = get_post_type();
					} elseif ( is_post_type_archive() ) {
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
								$value = [];
							}
							$targeting_value = $value;
						} elseif ( is_tax() || is_category() || is_tag() ) {
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
			$ad_units      = wp_list_pluck( $ad_unit_setup, 'code' );
			sort( $ad_units );
			return $ad_units;
		}

		/**
		 * Generate the code for a single ad unit.
		 * The terminology of the plugin uses units but we use unit here to be consistent with DFP.
		 *
		 * @access public
		 * @param string  $ad_unit ad unit slug.
		 * @param boolean $echo    whether or not to echo.
		 * @return string
		 */
		public function get_ad_unit( $ad_unit, $echo = true ) {
			// Make sure this is in the current ad layer and an ad layer is defined.
			if ( empty( $this->ad_units ) || ! array_key_exists( $ad_unit, $this->ad_units ) ) {
				return;
			}

			$ad_unit_id  = $this->get_ad_unit_id( $ad_unit );
			$output_html = sprintf(
				'<div id="%1$s" class="dfp-ad %2$s" data-ad-unit="%4$s">
					<script type="text/javascript">
						if ( "undefined" !== typeof googletag ) {
							googletag.cmd.push( function() { googletag.display(%3$s); } );
						}
					</script>
				</div>',
				esc_attr( $ad_unit_id ),
				sanitize_html_class( apply_filters( 'ad_layers_dfp_ad_unit_class', 'dfp-' . $ad_unit, $ad_unit ) ),
				wp_json_encode( $ad_unit_id ),
				esc_attr( $ad_unit )
			);

			$output_html = apply_filters( 'ad_layers_dfp_ad_unit_output_html', $output_html, $ad_unit );

			if ( $echo ) {
				echo $output_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				return $output_html;
			}
		}

		/**
		 * Create a key from a string value, likely for use in Javascript.
		 *
		 * @access public
		 * @param string $value some string value to sanitize.
		 * @return string
		 */
		public function sanitize_key( $value ) {
			return preg_replace( '/[^a-z0-9_]+/i', '', apply_filters( 'ad_layers_dfp_breakpoint_key', $value ) );
		}

		/**
		 * Get an ad unit name for referencing a particular location on page.
		 *
		 * @access public
		 * @param string $ad_unit ad unit slug.
		 * @return string
		 */
		public function get_ad_unit_id( $ad_unit ) {
			return apply_filters( 'ad_layers_dfp_ad_unit_id', $this->ad_unit_prefix . $ad_unit, $ad_unit );
		}

		/**
		 * Gets the correct path for the current page being displayed.
		 *
		 * @access public
		 * @param string $page_type page type.
		 * @param string $ad_unit   ad unit slug.
		 * @return string
		 */
		public function get_path( $page_type, $ad_unit = '' ) {
			// The default path template should always just be the account ID and domain.
			$account_id    = $this->get_setting( 'account_id' );
			$domain        = $this->get_domain();
			$path_template = '/' . $account_id . '/' . $domain;

			if ( ! empty( $ad_unit ) && ! empty( $this->ad_unit_paths[ $ad_unit ] ) ) {
				$path_template = $this->ad_unit_paths[ $ad_unit ];
			} else {
				// Get all available path templates.
				$path_templates = $this->get_setting( 'path_templates' );
				if ( ! empty( $path_templates ) ) {
					// Re-sort by page type.
					$path_templates_by_page_type = wp_list_pluck( $path_templates, 'path_template', 'page_type' );

					// If we have a match, use that template.
					if ( ! empty( $path_templates_by_page_type[ $page_type ] ) ) {
						$path_template = $path_templates_by_page_type[ $page_type ];
					} elseif ( empty( $path_templates_by_page_type[ $page_type ] )
						&& ! empty( $path_templates_by_page_type['all'] ) ) {
						// If the path template is still empty, check if a global template exists for all pages.
						$path_template = $path_templates_by_page_type['all'];
					}
				}
			}

			if ( ! empty( $path_template ) ) {
				$replacements = [];

				// Handle any formatting tags.
				preg_match_all( apply_filters( 'ad_layers_dfp_formatting_tag_pattern', $this->formatting_tag_pattern ), $path_template, $matches );
				if ( ! empty( $matches[0] ) ) {
					// Build a list of found tags for replacement.
					$unique_matches = array_unique( $matches[0] );

					// Iterate over and replace each.
					foreach ( $this->formatting_tags as $tag => $description ) {
						if ( in_array( $tag, $unique_matches, true ) ) {
							$value = null;

							// Handle built-in formatting tags.
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
									} elseif ( is_singular() ) {
										$value = get_post_type();
									}
									break;
								default:
									// This is one of the available taxonomy tags if it's not custom.
									// which would be handled later by the filter.
									$taxonomy = str_replace( '#', '', $tag );
									if ( taxonomy_exists( $taxonomy ) ) {
										if ( is_tax() || is_category() || is_tag() ) {
											$value = $this->get_term_path( get_queried_object()->term_id, $taxonomy );
										} elseif ( is_singular() ) {
											$terms = get_the_terms( get_the_ID(), $taxonomy );
											if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
												$term  = array_shift( $terms );
												$value = $this->get_term_path( $term->term_id, $taxonomy );
											}
										}

										// If nothing was found, strip this off the path.
										if ( null === $value ) {
											$value = '';
										}
									}
									break;
							}

							// Always allow filtering of the value for custom formatting tags.
							$value = apply_filters( 'ad_layers_dfp_formatting_tag_value', $value, $tag, $page_type, $ad_unit );

							// If a value was found, we'll replace it.
							// Otherwise, the "match" will be ignored.
							if ( null !== $value ) {
								$replacements[ $tag ] = $value;
							}
						}
					}
				}

				// Do the replacements and create the final path.
				$path = str_replace( array_keys( $replacements ), array_values( $replacements ), $path_template );
			}

			// Finally, the path should never end in a trailing slash.
			// This would possibly mean a term was stripped that wasn't matched.
			$path = untrailingslashit( $path );

			return apply_filters( 'ad_layers_dfp_path', $path, $page_type, $ad_unit );
		}

		/**
		 * Gets the path for a term. Will just return the term if not hierarchical.
		 *
		 * @access public
		 * @param int    $term_id  Term id.
		 * @param string $taxonomy Taxonomy slug.
		 * @param string $path     Path override value.
		 * @return string
		 */
		public function get_term_path( $term_id, $taxonomy, $path = '' ) {
			// Get the WordPress term object.
			$term = get_term( $term_id, $taxonomy );

			// Add the term to the front of the path and always append a leading slash.
			$path = $term->slug . $path;

			// Determine if this term has a parent. If so, append to the path.
			if ( ! empty( $term->parent ) ) {
				// Add a leading slash since we are appending another level.
				$path = '/' . $path;

				// Add the parent term.
				$path = $this->get_term_path( $term->parent, $taxonomy, $path );
			}

			// Return the path.
			return $path;
		}

		/**
		 * Add tabs to the help menu on the plugin options page.
		 *
		 * @access public
		 */
		public function add_help_tab() {
			get_current_screen()->add_help_tab(
				[
					'id'       => 'dfp-setup-help',
					'title'    => __( 'Ad Server Settings Help', 'ad-layers' ),
					'callback' => [ $this, 'formatting_tags_help_tab' ],
				]
			);
		}

		/**
		 * Render the content of the help tab.
		 * The tab displays a table of each available formatting tab and any.
		 * provided description.
		 *
		 * @access public
		 */
		public function formatting_tags_help_tab() {
			if ( ! empty( $this->formatting_tags ) ) :
				?>
				<aside>
					<h2><?php esc_html_e( 'The following formatting tags are available for the path template:', 'ad-layers' ); ?></h2>
					<dl class="formatting-tags">
						<?php foreach ( $this->formatting_tags as $tag => $description ) : ?>
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
		 * These are cached from the main Ad_Server settings on update for efficiency.
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

			// Return what we have at this point.
			return $settings;
		}

		/**
		 * Cache the settings in a format more conducive to generating tags.
		 * The default Fieldmanager format currently used is great for the user interface.
		 * but not as well suited to how the DFP setup code is actually generated.
		 *
		 * @access public
		 * @param string  $option    option slug.
		 * @param mixed   $old_value old meta value.
		 * @param mixed   $value     current or new meta value.
		 * @param boolean $return    whether or not to return the value after save.
		 * @return mixed
		 */
		public function cache_settings( $option, $old_value, $value, $return = false ) {
			// Make sure this is saving ad server settings.
			if ( $option !== $this->option_name ) {
				return;
			}

			$cached_setup = [];

			// Don't bother if no breakpoints or no ad units are set.
			if ( empty( $value['breakpoints'] ) || empty( $value['ad_units'] ) ) {
				return;
			}

			foreach ( $value['breakpoints'] as &$breakpoint ) {
				// Add ad units to the breakpoint data.
				$breakpoint['ad_units'] = [];

				// Get all ad units for this breakpoint and add their data to the breakpoint.
				if ( ! empty( $value['ad_units'] ) ) {
					foreach ( $value['ad_units'] as $ad_unit ) {
						// Iterate over the sizes and find ones used by this breakpoint.
						if ( ! empty( $ad_unit['sizes'] ) ) {
							foreach ( $ad_unit['sizes'] as $i => $size ) {
								if ( ! isset( $size['breakpoints'] ) ) {
									continue;
								}

								// If this ad unit isn't used by the breakpoint, drop it.
								if ( ! in_array( $breakpoint['title'], $size['breakpoints'], true ) ) {
									unset( $ad_unit['sizes'][ $i ] );
								} else {
									// Leave it alone, but drop the breakpoint info since the cache won't need it.
									unset( $ad_unit['sizes'][ $i ]['breakpoints'] );
								}
							}

							// If there are any ad unit sizes left, add to the breakpoint.
							if ( ! empty( $ad_unit['sizes'] ) ) {
								$breakpoint['ad_units'][] = $ad_unit;
							}
						}
					}
				}
			}

			// Store the cached data.
			update_option( $this->cache_key, $value['breakpoints'] );

			// The action hook doesn't need a return value, but other usage of this plugin might.
			if ( true === $return ) {
				return $value['breakpoints'];
			}
		}
	}

	Ad_Layers_DFP::instance();

endif;
