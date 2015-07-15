<?php

/**
 * Ad Layers Singleton
 *
 * Abstract class to be implemented by all other singleton Ad Layers classes.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Singleton' ) ) :

abstract class Ad_Layers_Singleton {
	
	/**
	 * Reference to the singleton instance.
	 *
	 * @var Ad_Layers_Singleton
	 */
	private static $instance;
	
	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public function __clone() { wp_die( __( "Please don't __clone ", 'ad-layers' ) . __CLASS__ ); }

	public function __wakeup() { wp_die( __( "Please don't __wakeup ", 'ad-layers' ) . __CLASS__ ); }
	
	/**
	 * Get an instance of the class.
	 *
	 * @return Ad_Layers_Singleton
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			if ( 'Ad_Layers_Singleton' != $class_name ) {
				self::$instance = new $class_name;
				self::$instance->setup();
			}
		}
		return self::$instance;
	}
	
	/**
	 * Sets up the singleton.
	 */
	abstract public function setup();
}

endif;