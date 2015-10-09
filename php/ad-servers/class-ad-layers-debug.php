<?php

/**
 * Ad Layers Debug
 *
 * Implements the Debug Mode Ad Server for Ad Layers.
 */

if ( ! class_exists( 'Ad_Layers_Debug' ) ) :

	class Ad_Layers_Debug extends Ad_Layers_Ad_Server {

		/**
		 * The display label for this ad server.
		 *
		 * @access public
		 * @var array
		 */
		public $display_label;

		/**
		 * Handle used for scripts
		 *
		 * @access public
		 * @var string
		 */
		public $handle = 'ad-layers-debug';

		public $ad_units;

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			$this->display_label = __( 'Debug Mode', 'ad-layers' );
		}

		/**
		 * Load scripts.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {
			// Load the CSS. Temporarily disabled.
			// wp_enqueue_style( $this->handle . '-css', AD_LAYERS_ASSETS_DIR . 'css/ad-layers-debug.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
		}

		/**
		 * Returns the ad server settings fields to merge into the ad settings page.
		 *
		 * @access public
		 * @return array
		 */
		public function get_settings_fields() {
			if ( ! class_exists( 'Fieldmanager_Field' ) ) {
				return array();
			}

			return apply_filters( 'ad_layers_debug_get_settings_fields', array(
				'ad_units' => new Fieldmanager_Group( array(
					'collapsed' => true,
					'limit' => 0,
					'extra_elements' => 0,
					'label' => __( 'Ad Units', 'ad-layers' ),
					'label_macro' => array( __( 'Ad Unit: %s', 'ad-layers' ), 'code' ),
					'add_more_label' => __( 'Add Ad Unit', 'ad-layers' ),
					'children' => array(
						'code' => new Fieldmanager_Textfield( __( 'Code', 'ad-layers' ) ),
						'width' => new Fieldmanager_Textfield( array(
							'label' => __( 'Width', 'ad-layers' ),
							'sanitize' => 'absint',
						) ),
						'height' => new Fieldmanager_Textfield( array(
							'label' => __( 'Height', 'ad-layers' ),
							'sanitize' => 'absint',
						) ),
					),
				) ),
			) );
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

		public function load_ad_units() {
			$ad_units = $this->get_setting( 'ad_units' );
			foreach ( (array) $ad_units as $ad ) {
				if ( empty( $ad['code'] ) ) {
					continue;
				}

				$this->ad_units[ $ad['code'] ] = wp_parse_args( $ad, array(
					'width' => 0,
					'height' => 0,
				) );
			}
		}

		/**
		 * Generate the code for a single ad unit.
		 *
		 * @access public
		 * @param string $ad_unit
		 * @param boolean $echo
		 * @return string
		 */
		public function get_ad_unit( $ad_unit, $echo = true ) {
			if ( ! isset( $this->ad_units ) ) {
				$this->load_ad_units();
			}

			// Make sure this is in the current ad layer and an ad layer is defined
			if ( empty( $this->ad_units[ $ad_unit ] ) ) {
				return;
			}

			$output_html = sprintf(
				'<div id="ad-layers-debug-ad-%s" class="ad-layers-debug-ad">
					<img src="//placehold.it/%dx%d" />
				</div>',
				esc_attr( $ad_unit ),
				intval( $this->ad_units[ $ad_unit ]['width'] ),
				intval( $this->ad_units[ $ad_unit ]['height'] )
			);

			$output_html = apply_filters( 'ad_layers_debug_ad_unit_output_html', $output_html, $ad_unit );

			if ( $echo ) {
				echo $output_html;
			} else {
				return $output_html;
			}
		}
	}

	Ad_Layers_Debug::instance();

endif;
