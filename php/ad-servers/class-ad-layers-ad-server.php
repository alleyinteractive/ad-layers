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
	 * Built-in ad server support
	 *
	 * @access public
	 * @static
	 * @var array
	 */
	public static $ad_servers;
	
	/**
	 * Current ad server settings
	 *
	 * @access public
	 * @static
	 * @var array
	 */
	public static $settings;

	/**
	 * Setup the singleton.
	 * @access public
	 */
	public function setup() {
		// Load current settings
		self::$settings = apply_filters( 'ad_layers_ad_server_settings', get_option( 'ad_layers_ad_server_settings', array() ) );
	
		// Register the ad server settings page
		add_action( 'init', array( $this, 'add_settings_page' ) );
		
		// Add the required header and footer setup. May differ for each server.
		add_action( 'wp_head', array( $this, 'header_setup' ) );
		add_action( 'wp_footer', array( $this, 'footer_setup' ) );
		
		// Allow additional ad servers to be loaded via filter within a theme
		self::$ad_servers = apply_filters( 'ad_layers_ad_servers', array(
			'dfp' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-ad-layers-dfp.php',
		) );
		
		// Load ad server classes
		if ( ! empty( $this->ad_servers ) && is_array( $this->ad_servers ) ) {
			foreach ( $this->ad_servers as $ad_server ) {
				if ( file_exists( $ad_server ) ) {
					require_once( $ad_server );
				}
			}
		}
	}
	
	/**
	 * Get current available ad servers.
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_ad_servers() {
		return self::$ad_servers;
	}
	
	/**
	 * Gets available ad slots.
	 * TODO - MAKE REAL
	 * @access public
	 * @static
	 */
	public static function get_ad_slots() {
		return array(
			'leaderboard' => __( 'Leaderboard', 'ad-layers' ),
			'rectangle' => __( 'Rectangle', 'ad-layers' ),
		);
	}
	
	/**
	 * Add the ad server settings page.
	 * @access public
	 */
	public function add_settings_page( $args = array() ) {
		// Provide basic ad server selection.
		// Child classes can add additional functionality.
		$fm_ad_servers = new Fieldmanager_Group( array(
			'name' => 'ad_layers_ad_server_settings',
			'label' => __( 'Ad Server Settings', 'ad-layers' ),
			'children' => array(
				'ad_server' => new Fieldmanager_Select(
					array(
						'label' => __( 'Ad Server', 'ad-layers' ),
						'options' => array_keys( self::$ad_servers ),
					)
				),
			)
		) );
		$fm_ad_servers->add_submenu_page( Ad_Layers::get_edit_link(), __( 'Ad Server Settings', 'ad-layers' ) );
	}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes.
	 * @access public
	 * @static
	 * @return array
	 */
	public function header_setup() {}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes.
	 * @access public
	 * @static
	 * @return array
	 */
	public function footer_setup() {}
}

Ad_Layers_Ad_Server::instance();

endif;