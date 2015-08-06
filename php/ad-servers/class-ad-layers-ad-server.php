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
	 * All available ad servers
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
	 * Instance of the current ad server class
	 *
	 * @access public
	 * @var mixed
	 */
	public $ad_server;

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
			'Ad_Layers_DFP' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-ad-layers-dfp.php',
		) );
		
		// Load ad server classes
		if ( ! empty( self::$ad_servers ) && is_array( self::$ad_servers ) ) {
			foreach ( self::$ad_servers as $ad_server ) {
				if ( file_exists( $ad_server ) ) {
					require_once( $ad_server );
				}
			}
		}
		
		// Set the current ad server class, if defined.
		if ( ! empty( self::$settings['ad_server'] ) && class_exists( self::$settings['ad_server'] ) ) {
			$this->ad_server = new self::$settings['ad_server'];
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
		$args = array(
			'name' => 'ad_layers_ad_server_settings',
			'label' => __( 'Ad Server Settings', 'ad-layers' ),
			'children' => array(
				'ad_server' => new Fieldmanager_Select(
					array(
						'label' => __( 'Ad Server', 'ad-layers' ),
						'options' => array_keys( self::$ad_servers ),
						'first_empty' => true,
					)
				),
			)
		);
		
		// Child classes can add additional functionality.
		$args['children'] = array_merge( $args['children'], $this->get_settings_fields() );
		
		$fm_ad_servers = new Fieldmanager_Group( $args );
		$fm_ad_servers->add_submenu_page( Ad_Layers::get_edit_link(), __( 'Ad Server Settings', 'ad-layers' ) );
	}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes, if needed.
	 * Since $ad_server will be empty for child classes,
	 * this will automatically do nothing if they choose not to implement it.
	 * @access public
	 */
	public function header_setup() {
		if ( ! empty( $this->ad_server ) ) {
			$this->ad_server->header_setup();
		}
	}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes, if needed.
	 * Since $ad_server will be empty for child classes,
	 * this will automatically do nothing if they choose not to implement it.
	 * @access public
	 */
	public function footer_setup() {
		if ( ! empty( $this->ad_server ) ) {
			$this->ad_server->header_setup();
		}
	}
	
	/**
	 * Returns the ad server display label.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return string
	 */
	public function get_display_label() {
		return ( ! empty( $this->ad_server ) ) ? $this->ad_server->get_display_label() : '';
	}
	
	/**
	 * Returns the ad server settings fields to merge into the ad settings page.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_settings_fields() {
		return ( ! empty( $this->ad_server ) ) ? $this->ad_server->get_settings_fields() : array();
	}
}

Ad_Layers_Ad_Server::instance();

endif;