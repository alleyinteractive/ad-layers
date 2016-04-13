<?php

/**
 * Ad Layers Admin
 *
 * Manages the Ad Layers settings page and associated functions
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Admin' ) ) :

	class Ad_Layers_Admin extends Ad_Layers_Singleton {

		/**
		 * Capability required to manage the layer priority settings.
		 *
		 * @var string
		 */
		public $layer_priority_capability;

		/**
		 * Capability required to manage the custom variables settings.
		 *
		 * @var string
		 */
		public $custom_variables_capability;

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			/**
			 * Filter the capability required to manage the layer priority settings.
			 *
			 * @param string $capability. Defaults to `manage_options`.
			 */
			$this->layer_priority_capability = apply_filters( 'ad_layers_layer_priority_capability', 'manage_options' );

			/**
			 * Filter the capability required to manage the custom variables settings.
			 *
			 * @param string $capability. Defaults to `manage_options`.
			 */
			$this->custom_variables_capability = apply_filters( 'ad_layers_custom_variables_capability', 'manage_options' );

			// Register the settings pages
			if ( function_exists( 'fm_register_submenu_page' ) ) {
				if ( current_user_can( $this->layer_priority_capability ) ) {
					fm_register_submenu_page( 'ad_layers', Ad_Layers::instance()->get_edit_link(), __( 'Layer Priority', 'ad-layers' ), null, $this->layer_priority_capability );
				}
				if ( current_user_can( $this->custom_variables_capability ) ) {
					fm_register_submenu_page( 'ad_layers_custom_variables', Ad_Layers::instance()->get_edit_link(), __( 'Custom Variables', 'ad-layers' ), null, $this->custom_variables_capability );
				}
			}

			// Hook the ad layer settings pages onto Fieldmanager's actions
			add_action( 'fm_submenu_ad_layers', array( $this, 'add_layer_priority_settings' ) );
			add_action( 'fm_submenu_ad_layers_custom_variables', array( $this, 'add_custom_variables_settings' ) );

			// Load admin-only JS and CSS
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Load scripts.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {
			// Load the CSS to customize some Fieldmanager features
			$current_screen = get_current_screen();
			if ( 'edit-ad-layer' == $current_screen->id || 'ad-layer_page_ad_layers' == $current_screen->base ) {
				wp_enqueue_style( 'ad-layers-admin-css', AD_LAYERS_ASSETS_DIR . '/css/ad-layers-admin.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
			}
		}

		/**
		 * Add the ad layer priority management page.
		 *
		 * @access public
		 */
		public function add_layer_priority_settings() {
			$fm_priority = new Fieldmanager_Group( array(
				'name' => 'ad_layers',
				'sortable' => true,
				'collapsible' => true,
				'collapsed' => true,
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Ad Layer', 'ad-layers' ),
				'label_macro' => array( __( '%s', 'ad-layers' ), 'title' ),
				'children' => array(
					'post_id' => new Fieldmanager_Hidden(),
					'title' => new Fieldmanager_Textfield( array(
						'label' => __( 'Title', 'ad-layers' ),
						'attributes' => array(
							'readonly' => 'readonly',
						),
					) ),
				),
			) );
			$fm_priority->activate_submenu_page();
		}

		/**
		 * Add the custom variables management page.
		 *
		 * @access public
		 */
		public function add_custom_variables_settings() {
			$fm_custom = new Fieldmanager_Textfield( array(
				'name' => 'ad_layers_custom_variables',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'label' => __( 'Add one or more custom variables for targeting.', 'ad-layers' ),
				'add_more_label' => __( 'Add a custom variable', 'ad-layers' ),
			) );
			$fm_custom->activate_submenu_page();
		}
	}

	Ad_Layers_Admin::instance();

endif;
