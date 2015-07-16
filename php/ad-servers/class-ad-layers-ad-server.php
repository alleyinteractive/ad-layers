<?php

/**
 * Ad Layers Ad Server
 *
 * Implements common ad server functionality for Ad Layers.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Ad_Server' ) ) :

class Ad_Layers_Ad_Server extends Ad_Layers_Singleton {

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		
	}
}

Ad_Layers_Ad_Server::instance();

endif;