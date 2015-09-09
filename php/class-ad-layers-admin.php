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
		 * Setup the singleton.
		 */
		public function setup() {
			// Register the ad layer settings pages
			add_action( 'init', array( $this, 'add_settings_pages' ) );

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
			if ( 'ad-layer_page_ad_layers' == $current_screen->base ) {
				wp_enqueue_style( 'ad-layers-admin-css', AD_LAYERS_ASSETS_DIR . '/css/ad-layers-admin.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
			}
		}

		/**
		 * Add the ad layer priority management page.
		 *
		 * @access public
		 */
		public function add_settings_pages() {
			if ( ! class_exists( 'Fieldmanager_Field' ) ) {
				return;
			}

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
			$fm_priority->add_submenu_page( Ad_Layers::instance()->get_edit_link(), __( 'Layer Priority', 'ad-layers' ) );

			$fm_custom = new Fieldmanager_Textfield( array(
				'name' => 'ad_layers_custom_variables',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'label' => __( 'Add one or more custom variables for targeting.', 'ad-layers' ),
				'add_more_label' => __( 'Add a custom variable', 'ad-layers' ),
			) );
			$fm_custom->add_submenu_page( Ad_Layers::instance()->get_edit_link(), __( 'Custom Variables', 'ad-layers' ) );
		}
	}

	Ad_Layers_Admin::instance();

endif;
