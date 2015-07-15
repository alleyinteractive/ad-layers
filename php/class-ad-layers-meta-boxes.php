<?php

/**
 * Ad Layers Meta Boxes
 *
 * Manages the meta box on the post type edit screen for selecting an ad layer.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Meta_Boxes' ) ) :

class Ad_Layers_Meta_Boxes extends Ad_Layers_Singleton {

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		
	}
}

Ad_Layers_Meta_Boxes::instance();

endif;