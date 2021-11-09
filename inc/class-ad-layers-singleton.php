<?php
/**
 * Abstract class to be implemented by all other singleton Ad Layers classes.
 *
 * @package Ad_Layers
 */

if ( ! class_exists( 'Ad_Layers_Singleton' ) ) :

	/**
	 * Abstract Ad_Layers_Singleton Class.
	 */
	abstract class Ad_Layers_Singleton {

		/**
		 * Reference to the singleton instance.
		 *
		 * @var Ad_Layers_Singleton
		 */
		private static $instances;

		/**
		 * Constructor.
		 */
		protected function __construct() {
			/* Don't do anything, needs to be initialized via instance() method */
		}

		/**
		 * Get an instance of the class.
		 *
		 * @return Ad_Layers_Singleton
		 */
		public static function instance() {
			$class_name = get_called_class();
			if ( ! isset( self::$instances[ $class_name ] ) ) {
				self::$instances[ $class_name ] = new $class_name();
				self::$instances[ $class_name ]->setup();
			}
			return self::$instances[ $class_name ];
		}

		/**
		 * Sets up the singleton.
		 */
		abstract public function setup();
	}

endif;
