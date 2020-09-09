<?php

/**
 * Ad Layers Shortcodes
 *
 * Manages the shortcodes for available for ad layers.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Shortcodes' ) ) :

	class Ad_Layers_Shortcodes extends Ad_Layers_Singleton {

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			// Add the shortcode for ad units
			add_shortcode( 'ad-unit', array( $this, 'do_ad_unit' ) );
		}

		/**
		 * Add an ad unit to the body of a post
		 *
		 * @access public
		 * @param array $atts
		 * @param string $content
		 * @param string $tag
		 * @return string
		 */
		public function do_ad_unit( $atts, $content, $tag ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable, WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			// Ensure the shortcode is valid
			if ( empty( $atts['unit'] ) ) {
				return;
			}

			// Attempt to display the specified ad unit.
			// This will just do nothing if the unit is invalid
			// or doesn't exist in the current ad layer.
			// Since the WP shortcode pattern is running and trying to replace
			// our shortcode, we need to return the ad and what to replace there,
			// so we set the echo value to false for get_ad_unit.
			return Ad_Layers_Ad_Server::instance()->get_ad_unit( $atts['unit'], false );
		}
	}

	Ad_Layers_Shortcodes::instance();

endif;
