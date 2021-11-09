<?php
/**
 * Implements common ad server functionality for Ad Layers.
 *
 * @package Ad_Layers
 */

if ( ! class_exists( 'Ad_Layers_Ad_Server' ) ) :

	/**
	 * Ad_Layers_Ad_Server Class.
	 */
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
		 * Javascript API class name
		 *
		 * @access public
		 * @var string
		 */
		public $js_api_class = 'AdLayersAPI';

		/**
		 * Handle used for scripts
		 *
		 * @access public
		 * @var string
		 */
		public $handle = 'ad-layers';

		/**
		 * Capability required to manage the ad server settings.
		 *
		 * @var string
		 */
		public $settings_capability;

		/**
		 * Setup the singleton.
		 *
		 * @access public
		 */
		public function setup() {
			/**
			 * Filter the capability required to manage the ad server settings.
			 *
			 * @param string $capability. Defaults to `manage_options`.
			 */
			$this->settings_capability = apply_filters( 'ad_layers_settings_capability', 'manage_options' );

			// Register the settings page.
			if ( function_exists( 'fm_register_submenu_page' ) && current_user_can( $this->settings_capability ) ) {
				fm_register_submenu_page( $this->option_name, Ad_Layers::instance()->get_edit_link(), __( 'Ad Server Settings', 'ad-layers' ), null, $this->settings_capability );
			}

			// Hook the ad layer settings page onto Fieldmanager's action.
			add_action( 'fm_submenu_' . $this->option_name, [ $this, 'add_settings_page' ] );

			// Add the required header and footer setup. May differ for each server.
			add_action( 'wp_head', [ $this, 'header_setup' ] );
			add_action( 'wp_footer', [ $this, 'footer_setup' ] );

			// Handle rendering units.
			add_action( 'ad_layers_render_ad_unit', [ $this, 'get_ad_unit' ] );

			// Load the Javascript API early.
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 5 );

			// Load current settings.
			self::$settings = apply_filters( 'ad_layers_ad_server_settings', get_option( $this->option_name, [] ) );

			// Allow additional ad servers to be loaded via filter within a theme.
			$this->ad_servers = apply_filters(
				'ad_layers_ad_servers',
				[
					'Ad_Layers_DFP' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-ad-layers-dfp.php',
				]
			);

			// Load ad server classes.
			if ( ! empty( $this->ad_servers ) && is_array( $this->ad_servers ) ) {
				foreach ( $this->ad_servers as $ad_server ) {
					if ( file_exists( $ad_server ) ) {
						require_once $ad_server; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
					}
				}
			}

			// Set the current ad server class, if defined.
			if ( ! empty( self::$settings['ad_server'] ) && class_exists( self::$settings['ad_server'] ) ) {
				$ad_server       = new self::$settings['ad_server']();
				$this->ad_server = $ad_server::instance();
			}
		}

		/**
		 * Load scripts.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {
			// Set the base dependencies.
			$dependencies = [ 'jquery' ];

			// Load scripts specific to the enabled ad server.
			if ( ! empty( $this->ad_server ) ) {
				$this->ad_server->enqueue_scripts();
				$js_api_class   = $this->ad_server->js_api_class;
				$dependencies[] = $this->ad_server->handle;
			} else {
				$js_api_class = $this->js_api_class;
			}

			// Load the base Javascript library (in header to ensure early ad loading).
			wp_enqueue_script( $this->handle, AD_LAYERS_ASSETS_DIR . 'js/ad-layers.js', $dependencies, AD_LAYERS_GLOBAL_ASSET_VERSION, false );

			// Load the CSS. Mostly used in debug mode.
			wp_enqueue_style( $this->handle, AD_LAYERS_ASSETS_DIR . 'css/ad-layers.css', [], AD_LAYERS_GLOBAL_ASSET_VERSION );

			// Localize the base API with the class name.
			wp_localize_script(
				'ad-layers',
				'adLayersAdServer',
				[
					'jsAPIClass' => $js_api_class,
				]
			);
		}

		/**
		 * Get current available ad servers.
		 *
		 * @access public
		 * @return array
		 */
		public function get_ad_servers() {
			return $this->ad_servers;
		}

		/**
		 * Get the current ad server.
		 *
		 * @access public
		 * @return Ad_Layers_Ad_Server
		 */
		public function get_ad_server() {
			return $this->ad_server;
		}

		/**
		 * Get current available ad servers for use in an option list.
		 *
		 * @access public
		 * @return array
		 */
		public function get_ad_server_options() {
			$options = [];

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
		 * Gets available ad units.
		 *
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
		 *
		 * @param array $ad_unit array of ad unit args.
		 * @param bool  $echo    whether or not to echo output.
		 *
		 * @access public
		 */
		public function get_ad_unit( $ad_unit, $echo = true ) {
			if ( ! empty( $this->ad_server ) ) {
				return $this->ad_server->get_ad_unit( $ad_unit, $echo );
			}
		}

		/**
		 * Gets a particular ad server setting or all settings if none is specified.
		 *
		 * @param string $key settings key.
		 * @access public
		 * @return mixed
		 */
		public function get_setting( $key = '' ) {
			if ( empty( $key ) ) {
				return self::$settings;
			}

			return ( ! empty( self::$settings[ $key ] ) ) ? apply_filters( 'ad_layers_ad_server_setting', self::$settings[ $key ], $key ) : null;
		}

		/**
		 * Add the ad server settings page.
		 *
		 * @param array $args array of settings args.
		 *
		 * @access public
		 */
		public function add_settings_page( $args = [] ) {
			// Provide basic ad server selection.
			$args = [
				'name'     => $this->option_name,
				'label'    => __( 'Ad Server Settings', 'ad-layers' ),
				'children' => [
					'ad_server' => new Fieldmanager_Select(
						[
							'label'       => __( 'Ad Server', 'ad-layers' ),
							'options'     => $this->get_ad_server_options(),
							'first_empty' => true,
						]
					),
				],
			];

			// Child classes can add additional functionality.
			$args['children'] = array_merge( $args['children'], $this->get_settings_fields() );

			$fm_ad_servers = new Fieldmanager_Group( $args );
			$fm_ad_servers->activate_submenu_page();
		}

		/**
		 * Handle ad server header setup code.
		 * Should be implemented by all child classes, if needed.
		 * Since $ad_server will be empty for child classes,
		 * this will automatically do nothing if they choose not to implement it.
		 *
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
		 *
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
		 *
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
		 *
		 * @access public
		 * @return string
		 */
		public function get_display_label() {
			return $this->display_label;
		}

		/**
		 * Returns the ad server settings fields to merge into the ad settings page.
		 * Should be implemented by all child classes.
		 *
		 * @access public
		 * @return array
		 */
		public function get_settings_fields() {
			return ( ! empty( $this->ad_server ) ) ? $this->ad_server->get_settings_fields() : [];
		}

		/**
		 * Gets the domain of the current site.
		 * Useful for virtually any ad server.
		 *
		 * @access public
		 * @return string
		 */
		public function get_domain() {
			return apply_filters(
				'ad_layers_ad_server_get_domain',
				preg_replace( '#^https?://#', '', trim( get_site_url() ) )
			);
		}

		/**
		 * Gets the args used to define a custom targeting field.
		 *
		 * @access public
		 * @param string $name targeting arg name.
		 * @return array
		 */
		public function get_custom_targeting_args( $name = 'ad_layer_custom_targeting' ) {
			if ( ! class_exists( 'Fieldmanager_Field' ) ) {
				return [];
			}

			return apply_filters(
				'ad_layers_custom_targeting_args',
				[
					'name'           => $name,
					'collapsible'    => true,
					'collapsed'      => false,
					'limit'          => 0,
					'extra_elements' => 0,
					'add_more_label' => __( 'Add custom targeting', 'ad-layers' ),
					/* translators: macro title. */
					'label_macro'    => [ __( '%s', 'ad-layers' ), 'title' ], // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
					'children'       => [
						'custom_variable' => new Fieldmanager_Select(
							[
								'label'   => __( 'Custom Variable', 'ad-layers' ),
								'options' => Ad_Layers::instance()->get_custom_variables(),
							]
						),
						'source'          => new Fieldmanager_Select(
							[
								'label'   => __( 'Source', 'ad-layers' ),
								'options' => $this->get_custom_targeting_sources(),
							]
						),
						'values'          => new Fieldmanager_Textfield(
							[
								'add_more_label'     => __( 'Add value', 'ad-layers' ),
								'one_label_per_item' => false,
								'limit'              => 0,
								'extra_elements'     => 0,
								'display_if'         => [
									'src'   => 'source',
									'value' => 'other',
								],
							]
						),
					],
				]
			);
		}

		/**
		 * Gets all available custom targeting sources.
		 *
		 * @access private
		 * @return array
		 */
		private function get_custom_targeting_sources() {
			$options = [];

			// Add all taxonomies available to ad layers.
			$options = array_merge( $options, Ad_Layers::instance()->get_taxonomies() );

			// Add additional options.
			$options = array_merge(
				$options,
				[
					'post_type' => __( 'Post Type', 'ad-layers' ),
					'author'    => __( 'Author', 'ad-layers' ),
					'other'     => __( 'Other', 'ad-layers' ),
				]
			);

			return apply_filters( 'ad_layers_custom_targeting_sources', $options );
		}
	}

	Ad_Layers_Ad_Server::instance();

endif;
