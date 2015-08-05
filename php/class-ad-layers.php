<?php

/**
 * Ad Layers
 *
 * Handles basic plugin functionality and enqueuing global scripts
 *
 * @author Bradford Campeau-Laurion
 */

/**
 * Instantiate the Ad Layers base class to handle required plugin setup
 */
if ( ! class_exists( 'Ad_Layers' ) ) :

class Ad_Layers extends Ad_Layers_Singleton {
	
	/**
	 * Current ad layers
	 *
	 * @access public
	 * @static
	 * @var string
	 */
	public static $ad_layers;
	
	/**
	 * Available custom targeting variables
	 *
	 * @access public
	 * @static
	 * @var string
	 */
	public static $custom_variables;
	
	/**
	 * Setup the singleton.
	 * @access public
	 */
	public function setup() {
		// Load current settings
		self::$ad_layers = apply_filters( 'ad_layers', get_option( 'ad_layers' ) );
		self::$custom_variables = apply_filters( 'ad_layers_custom_variables', get_option( 'ad_layers_custom_variables' ) );
		
		// Load the base Javascript library early
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5 );
	}
	
	/**
	 * Load scripts.
	 * @access public
	 */
	public function enqueue_scripts() {
		// Load the base Javascript library
		wp_enqueue_script( 'ad-layers-js', AD_LAYERS_ASSETS_DIR . '/js/ad-layers.js', array( 'jquery' ), AD_LAYERS_GLOBAL_ASSET_VERSION, false );
		
		// Load the CSS. Mostly used in debug mode.
		wp_enqueue_style( 'ad-layers-css', AD_LAYERS_ASSETS_DIR . '/css/ad-layers.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
	}
	
	/**
	 * Get taxonomies that can be used for targeting.
	 *
	 * @access public
	 * @static
	 * @return array Taxonomy names as keys, display labels as values
	 */
	public static function get_taxonomies() {
		return self::get_objects( 'taxonomies' );
	}
	
	/**
	 * Get post types that can be used for targeting.
	 *
	 * @access public
	 * @static
	 * @return array Post type names as keys, display labels as values
	 */
	public static function get_post_types() {
		return self::get_objects( 'post_types' );
	}
	
	/**
	 * Get current ad layers in priority order
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_ad_layers() {
		return self::$ad_layers;
	}
	
	/**
	 * Get current custom targeting variables
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_custom_variables() {
		return self::$custom_variables;
	}
	
	/**
	 * Get current custom targeting variables
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_edit_link() {
		return 'edit.php?post_type=' . Ad_Layers_Post_Type::$post_type;
	}
	
	/**
	 * Get objects that can be used for targeting.
	 *
	 * @access private
	 * @param string $object_type
	 * @return array Object names as keys, display labels as values
	 */
	private function get_objects( $object_type ) {
		$output = array();
	
		$objects = call_user_func_array( 
			'get_' . $object_type, 
			array( 
				apply_filters( 'ad_layers_get_' . $object_type . '_args', array(
					'public' => true,
					'show_ui' => true,
				) ),
				'object',
				apply_filters( 'ad_layers_get_' . $object_type . '_operator', 'and' ),
			)
		);
		
		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				$output[ $object->name ] = $object->label;
			}
		}
		
		return $output;
	}
}

Ad_Layers::instance();

endif;