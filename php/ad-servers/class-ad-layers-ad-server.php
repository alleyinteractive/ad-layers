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
	 * @var array
	 */
	public $ad_servers;
	
	/**
	 * Current ad server settings
	 *
	 * @access public
	 * @static
	 * @var array
	 */
	public static $settings;
	
	/**
	 * Option name for ad settings
	 *
	 * @access public
	 * @var array
	 */
	public $option_name = 'ad_layers_ad_server_settings';
	
	/**
	 * Instance of the current ad server class
	 *
	 * @access public
	 * @var mixed
	 */
	public $ad_server;
	
	/**
	 * Display label. Used by child classes.
	 *
	 * @access public
	 * @var mixed
	 */
	public $display_label;

	/**
	 * Setup the singleton.
	 * @access public
	 */
	public function setup() {
		// Register the ad server settings page
		add_action( 'init', array( $this, 'add_settings_page' ) );
		
		// Add the required header and footer setup. May differ for each server.
		add_action( 'wp_head', array( $this, 'header_setup' ) );
		add_action( 'wp_footer', array( $this, 'footer_setup' ) );
		
		// Handle rendering units
		add_action( 'ad_layers_render_ad_unit', array( $this, 'get_ad_unit' ) );
		
		// Load current settings
		self::$settings = apply_filters( 'ad_layers_ad_server_settings', get_option( $this->option_name, array() ) );
		
		// Allow additional ad servers to be loaded via filter within a theme
		$this->ad_servers = apply_filters( 'ad_layers_ad_servers', array(
			'Ad_Layers_DFP' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-ad-layers-dfp.php',
		) );
		
		// Load ad server classes
		if ( ! empty( $this->ad_servers ) && is_array( $this->ad_servers ) ) {
			foreach ( $this->ad_servers as $ad_server ) {
				if ( file_exists( $ad_server ) ) {
					require_once( $ad_server );
				}
			}
		}
		
		// Set the current ad server class, if defined.
		if ( ! empty( self::$settings['ad_server'] ) && class_exists( self::$settings['ad_server'] ) ) {
			$ad_server = new self::$settings['ad_server'];
			$this->ad_server = $ad_server::instance();
		}
	}
	
	/**
	 * Get current available ad servers.
	 * @access public
	 * @return array
	 */
	public function get_ad_servers() {
		return $this->ad_servers;
	}
	
	/**
	 * Get current available ad servers for use in an option list.
	 * @access public
	 * @return array
	 */
	public function get_ad_server_options() {
		$options = array();
		
		if ( ! empty( $this->ad_servers ) ) {
			foreach ( array_keys( $this->ad_servers ) as $ad_server ) {
				if ( class_exists( $ad_server ) ) {
					$options[ $ad_server ] = $ad_server::instance()->get_display_label();
				}
			}
		}
		
		return $options;
	}
	
	/**
	 * Gets available ad unitss.
	 * @access public
	 * @return array
	 */
	public function get_ad_units() {
		if ( ! empty( $this->ad_server ) ) {
			return $this->ad_server->get_ad_units();
		}
	}
	
	/**
	 * Get the code for a specific ad unit.
	 * Should be implemented by all child classes.
	 * Since $ad_server will be empty for child classes,
	 * this will automatically do nothing if they choose not to implement it.
	 * @access public
	 */
	public function get_ad_unit( $ad_unit ) {
		if ( ! empty( $this->ad_server ) ) {
			$this->ad_server->get_ad_unit( $ad_unit );
		}
	}
	
	/**
	 * Gets a particular ad server setting or all settings if none is specified.
	 * @access public
	 * @return mixed
	 */
	public function get_setting( $key = '' ) {
		if ( empty( $key ) ) {
			return self::$settings;
		}
		
		return ( ! empty( self::$settings[ $key ] ) ) ? apply_filters( 'ad_layer_ad_server_setting', self::$settings[ $key ], $key ) : null;
	}
	
	/**
	 * Add the ad server settings page.
	 * @access public
	 */
	public function add_settings_page( $args = array() ) {
		// Provide basic ad server selection.
		$args = array(
			'name' => $this->option_name,
			'label' => __( 'Ad Server Settings', 'ad-layers' ),
			'children' => array(
				'ad_server' => new Fieldmanager_Select(
					array(
						'label' => __( 'Ad Server', 'ad-layers' ),
						'options' => $this->get_ad_server_options(),
						'first_empty' => true,
					)
				),
			)
		);
		
		// Child classes can add additional functionality.
		$args['children'] = array_merge( $args['children'], $this->get_settings_fields() );
		
		$fm_ad_servers = new Fieldmanager_Group( $args );
		$fm_ad_servers->add_submenu_page( Ad_Layers::instance()->get_edit_link(), __( 'Ad Server Settings', 'ad-layers' ) );
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
			$this->ad_server->footer_setup();
		}
	}
	
	/**
	 * Handle adding a help tab for the current ad server.
	 * Should be implemented by all child classes, if needed.
	 * Since $ad_server will be empty for child classes,
	 * this will automatically do nothing if they choose not to implement it.
	 * @access public
	 */
	public function add_help_tab() {
		if ( ! empty( $this->ad_server ) ) {
			$this->ad_server->add_help_tab();
		}
	}
	
	/**
	 * Returns the ad server display label.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return string
	 */
	public function get_display_label() {
		return $this->display_label;
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
	
	/**
	 * Gets the domain of the current site.
	 * Useful for virtually any ad server.
	 * @access public
	 * @return string
	 */
	public function get_domain() {
		return apply_filters(
			'ad_layers_ad_server_get_domain', 
			preg_replace( '#^https?://#', '', trim( get_site_url() ) )
		);
	}
}

Ad_Layers_Ad_Server::instance();

endif;