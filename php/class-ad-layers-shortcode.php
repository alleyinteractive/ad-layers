<?php

/**
 * Ad Layers Shortcode
 *
 * Manages the shortcode for inserting an ad unit.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Shortcode' ) ) :

class Ad_Layers_Shortcode extends Ad_Layers_Singleton {

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		
	}
}

Ad_Layers_Shortcode::instance();

endif;