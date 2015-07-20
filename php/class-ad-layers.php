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
	 * @var Ad_Layers
	 */
	public $ad_servers;
	
	/**
	 * Current ad layers settings
	 *
	 * @var string
	 */
	public $settings;
	
	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Load current settings
		$this->settings = get_option( AD_LAYERS_OPTION_NAME );
	
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
}

Ad_Layers::instance();

endif;