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
	 * @access public
	 */
	public function setup() {
		
	}
	
	/**
	 * Gets available ad slots
	 * @access public
	 * @static
	 */
	public static function get_ad_slots() {
		return array(
			'leaderboard' => __( 'Leaderboard', 'ad-layers' ),
			'rectangle' => __( 'Rectangle', 'ad-layers' ),
		);
	}
}

Ad_Layers_Ad_Server::instance();

endif;