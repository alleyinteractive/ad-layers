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
		// Add the shortcode for ad slots
		add_shortcode( 'ad-slot', array( $this, 'do_ad_slot' ) );
	}
	
	/**
	 * Create the script tag for the Infogr.am shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @param string $content
	 * @param string $tag
	 * @return string
	 */
	public function do_ad_slot( $atts, $content, $tag ) {
		// Ensure the shortcode is valid
		if ( empty( $atts['slot'] ) ) {
			return;
		}
	
		// Attempt to display the specified ad slot.
		// This will just do nothing if the slot is invalid 
		// or doesn't exist in the current ad layer.
		return Ad_Layers_Ad_Server::instance()->get_ad_slot( $atts['slot'] );
	}
}

Ad_Layers_Shortcodes::instance();

endif;