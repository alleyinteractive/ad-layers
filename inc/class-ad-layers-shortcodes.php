<?php
/**
 * Manages the shortcodes for available for ad layers.
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

use Ad_Layers\Ad_Server;

if ( ! class_exists( 'Ad_Layers\Ad_Layers_Shortcodes' ) ) :

	/**
	 * Ad_Layers_Shortcodes Class.
	 */
	class Ad_Layers_Shortcodes extends Ad_Layers_Singleton {

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			// Add the shortcode for ad units.
			add_shortcode( 'ad-unit', [ $this, 'do_ad_unit' ] );
		}

		/**
		 * Add an ad unit to the body of a post
		 *
		 * @access public
		 * @param array  $atts    shortcode attributes.
		 * @param string $content shortocde content/children.
		 * @param string $tag     shortcode tag.
		 * @return string shortcode output.
		 */
		public function do_ad_unit( $atts, $content, $tag ) {
			// Ensure the shortcode is valid.
			if ( empty( $atts['unit'] ) ) {
				return;
			}

			// Attempt to display the specified ad unit.
			// This will just do nothing if the unit is invalid
			// or doesn't exist in the current ad layer.
			// Since the WP shortcode pattern is running and trying to replace
			// our shortcode, we need to return the ad and what to replace there,
			// so we set the echo value to false for get_ad_unit.
			return Ad_Server::instance()->get_ad_unit( $atts['unit'], false );
		}
	}

	Ad_Layers_Shortcodes::instance();

endif;
