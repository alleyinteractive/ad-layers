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
	 * Built-in ad server support
	 *
	 * @access public
	 * @var Ad_Layers
	 */
	public $ad_servers;
	
	/**
	 * Current ad layers
	 *
	 * @access public
	 * @var string
	 */
	public $ad_layers;
	
	/**
	 * Available custom targeting variables
	 *
	 * @access public
	 * @var string
	 */
	public $custom_varibles;
	
	/**
	 * Setup the singleton.
	 * @access public
	 */
	public function setup() {
		// Load current settings
		$this->ad_layers = get_option( 'ad_layers' );
		$this->custom_variables = get_option( 'ad_layers_custom_variables' );
	
		// Allow additional ad servers to be loaded via filter within a theme
		$this->ad_servers = apply_filters( 'ad_layers_ad_servers', array(
			'DFP' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-dfp.php',
		) );
		
		if ( ! empty( $this->ad_servers ) && is_array( $this->ad_servers ) ) {
			foreach ( $this->ad_servers as $ad_server ) {
				if ( file_exists( $ad_server ) ) {
					require_once( $ad_server );
				}
			}
		}
		
		// Load the base Javascript library early
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5 );
	}
	
	/**
	 * Load scripts.
	 * @access public
	 */
	public function enqueue_scripts() {
		// Load the base Javascript library
		wp_enqueue_script( 'ad-layers-js', AD_LAYERS_BASE_DIR . '/js/ad-layers.js', array( 'jquery' ), AD_LAYERS_GLOBAL_ASSET_VERSION, false );
		
		// If set, localize with the active ad server
		if ( ! empty( $this->settings['ad_server'] ) ) {
			wp_localize_script( 'ad-layers-js', 'ad-layers', array(
				'ad_server' => $this->settings['ad_server'],
			) );
		}
		
		// Load the CSS. Mostly used in debug mode.
		wp_enqueue_style( 'ad-layers-css', AD_LAYERS_BASE_DIR . '/css/ad-layers.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
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
	public static function get_ad_servers() {
		return $this->ad_servers;
	}
	
	/**
	 * Get current ad layers in priority order
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_ad_layers() {
		return $this->ad_layers;
	}
	
	/**
	 * Get current custom targeting variables
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_custom_variables() {
		return $this->custom_variables;
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