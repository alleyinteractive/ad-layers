<?php
/**
 * Manages the Ad Layers settings page and associated functions
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

use \Fieldmanager_TextField;
use \Fieldmanager_Hidden;
use \Fieldmanager_Group;

if ( ! class_exists( 'Ad_Layers\Ad_Layers_Admin' ) ) :

	/**
	 * Ad_Layers_Admin Class.
	 */
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

			// Register the settings pages.
			if ( function_exists( 'fm_register_submenu_page' ) ) {
				if ( current_user_can( $this->layer_priority_capability ) ) {
					fm_register_submenu_page( 'ad_layers', Ad_Layers::instance()->get_edit_link(), __( 'Layer Priority', 'ad-layers' ), null, $this->layer_priority_capability );
				}
				if ( current_user_can( $this->custom_variables_capability ) ) {
					fm_register_submenu_page( 'ad_layers_custom_variables', Ad_Layers::instance()->get_edit_link(), __( 'Custom Variables', 'ad-layers' ), null, $this->custom_variables_capability );
				}
			}

			// Hook the ad layer settings pages onto Fieldmanager's actions.
			add_action( 'fm_submenu_ad_layers', [ $this, 'add_layer_priority_settings' ] );
			add_action( 'fm_submenu_ad_layers_custom_variables', [ $this, 'add_custom_variables_settings' ] );

			// Load admin-only JS and CSS.
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		/**
		 * Load scripts.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {
			// Load the CSS to customize some Fieldmanager features.
			$current_screen = get_current_screen();
			if ( 'edit-ad-layer' === $current_screen->id || 'ad-layer_page_ad_layers' === $current_screen->base ) {
				wp_enqueue_script(
					'ad-layers-admin',
					get_ad_layers_path( 'adLayersAdmin.js' ),
					get_ad_layers_dependencies( 'adLayersAdmin.php' ),
					get_ad_layers_hash( 'adLayersAdmin.js' ),
					true
				);
			}
		}

		/**
		 * Add the ad layer priority management page.
		 *
		 * @access public
		 */
		public function add_layer_priority_settings() {
			$fm_priority = new Fieldmanager_Group(
				[
					'name'           => 'ad_layers',
					'sortable'       => true,
					'collapsible'    => true,
					'collapsed'      => true,
					'limit'          => 0,
					'extra_elements' => 0,
					'label'          => __( 'Ad Layer', 'ad-layers' ),
					/* translators: macro title. */
					'label_macro'    => [ __( '%s', 'ad-layers' ), 'title' ], // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
					'children'       => [
						'post_id' => new Fieldmanager_Hidden(),
						'title'   => new Fieldmanager_Textfield(
							[
								'label'      => __( 'Title', 'ad-layers' ),
								'attributes' => [
									'readonly' => 'readonly',
								],
							]
						),
					],
				]
			);
			$fm_priority->activate_submenu_page();
		}

		/**
		 * Add the custom variables management page.
		 *
		 * @access public
		 */
		public function add_custom_variables_settings() {
			$fm_custom = new Fieldmanager_Textfield(
				[
					'name'               => 'ad_layers_custom_variables',
					'limit'              => 0,
					'extra_elements'     => 0,
					'one_label_per_item' => false,
					'label'              => __( 'Add one or more custom variables for targeting.', 'ad-layers' ),
					'add_more_label'     => __( 'Add a custom variable', 'ad-layers' ),
				]
			);
			$fm_custom->activate_submenu_page();
		}
	}

	Ad_Layers_Admin::instance();

endif;
