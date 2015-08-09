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
	 * Page types available for targeting
	 *
	 * @access public
	 * @var array
	 */
	public $page_types;
	
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
	 * Setup the singleton.
	 * @access public
	 */
	public function setup() {
		// Register the ad server settings page
		add_action( 'init', array( $this, 'add_settings_page' ) );
		
		// Add the required header and footer setup. May differ for each server.
		add_action( 'wp_head', array( $this, 'header_setup' ) );
		add_action( 'wp_footer', array( $this, 'footer_setup' ) );
		
		// Load current settings
		self::$settings = apply_filters( 'ad_layers_ad_server_settings', get_option( $this->option_name, array() ) );
		
		// Set the page types available to all ad servers
		$this->ad_servers = apply_filters( 'ad_layers_ad_server_page_types', $this->get_page_types() );
		
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
			$this->ad_server = new self::$settings['ad_server'];
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
	 * Gets available ad slots.
	 * @access public
	 * @return array
	 */
	public function get_ad_slots() {
		if ( ! empty( $this->ad_server ) ) {
			return $this->ad_server->get_ad_slots( $ad_slot );
		}
	}
	
	/**
	 * Get the code for a specific ad slot.
	 * Should be implemented by all child classes.
	 * Since $ad_server will be empty for child classes,
	 * this will automatically do nothing if they choose not to implement it.
	 * @access public
	 */
	public function get_ad_slot( $ad_slot ) {
		if ( ! empty( $this->ad_server ) ) {
			$this->ad_server->get_ad_slot( $ad_slot );
		}
	}
	
	/**
	 * Get the available page types for all ad servers.
	 * These are especially used by path targeting.
	 * @access public
	 * @return array
	 */
	public function get_page_types() {
		if ( ! empty( $this->page_types ) ) {
			return $this->page_types;
		}
		
		// Build the page types.
		// First add global types.
		$page_types = array(
			'home' => __( 'Home Page', 'ad-layers' ),
		);
		
		// Add single post types
		$single_post_types = apply_filters( 'ad_layers_ad_server_single_post_types', wp_list_filter( get_post_types( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
		if ( ! empty( $single_post_types ) ) {
			foreach ( $single_post_types as $post_type ) {
				if ( Ad_Layers_Post_Type::instance()->get_post_type() != $post_type->name ) {
					$page_types[ $post_type->name ] = $post_type->label;
				}
			}
		}

		// Add archived post types
		$archived_post_types = apply_filters( 'ad_layers_ad_server_archived_post_types', wp_list_filter( get_post_types( array( 'has_archive' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
		if ( ! empty( $archived_post_types ) ) {
			foreach ( $archived_post_types as $post_type ) {
				$page_types[ $post_type->name ] = $post_type->label . __( ' Archive', 'ad-layers' );
			}
		}

		// Add taxonomies
		$taxonomies = apply_filters( 'ad_layers_ad_server_taxonomies', wp_list_filter( get_taxonomies( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$page_types[ $taxonomy->name ] = $taxonomy->label . __( ' Archive', 'ad-layers' );
			}
		}
		
		// Add some other templates at the bottom
		$page_types = array_merge( $page_types, array(
			'author' => __( 'Author Archive', 'ad-layers' ),
			'date' => __ ( 'Date Archive', 'ad-layers' ),
			'notfound' => __( '404 Page', 'ad-layers' ),
			'search' => __( 'Search Results', 'ad-layers' ),
			'default' => __( 'Default', 'ad-layers' ),
		) );
		
		return $page_types;
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
		
		return ( ! empty( self::$settings[ $key ] ) ) ? self::$settings[ $key ] : null;
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