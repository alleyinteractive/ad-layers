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
		// Register the settings page
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
	}
	
	/**
	 * Register the settings page
	 */
	public function after_setup_theme() {
		// Register the settings page
		fm_register_submenu_page( 'ad_layers_priority', 'ad-layers', __( 'Layer Priority', 'ad-layers' ) );
		add_action( 'fm_submenu_ad_layers_priority', array( $this, 'add_priority_page' ) );
	}
	
	/**
	 * Add the ad layer priority management page
	 */
	public function add_priority_page() {
		$fm_priority = new Fieldmanager_Group( array(
			'name' => 'ad_layers_priority',
			'sortable' => true,
			'collapsible' => true,
			'collapsed' => true,
			'label' => __( 'Ad Layer', 'ad-layers' ),
			'label_macro' => __( '%s', 'title' ),
			'children' => array(
				'title' => new Fieldmanager_Textfield(
					array(
						'label' => __( 'Title', 'ad-layers' ),
						'attributes' => array(
							'readonly' => 'readonly',
						),
					)
				),
				'ad_units' => new Fieldmanager_Select(
					array(
						'label' => __( 'Ad Units', 'ad-layers' ),
						'attributes' => array(
							'readonly' => 'readonly',
						),
					)
				),
				'copy_editors' => new Fieldmanager_Autocomplete(
					array(
						'label' => __( 'Copy Editors', 'pmc-print-workflow' ),
						'description' => __( 'Select which users should appear in the Copy Editors dropdown. Please note that you need to enter the exact username to search.', 'pmc-print-workflow' ),
						'limit' => 0,
						'minimum_count' => 1,
						'extra_elements' => 0,
						'add_more_label' => __( 'Add Copy Editor', 'pmc-print-workflow' ),
						'datasource' => new Fieldmanager_Datasource_User(),
					)
				),
				'designers' => new Fieldmanager_Autocomplete(
					array(
						'label' => __( 'Designers', 'pmc-print-workflow' ),
						'description' => __( 'Select which users should appear in the Designers dropdown. Please note that you need to enter the exact username to search.', 'pmc-print-workflow' ),
						'limit' => 0,
						'minimum_count' => 1,
						'extra_elements' => 0,
						'add_more_label' => __( 'Add Designer', 'pmc-print-workflow' ),
						'datasource' => new Fieldmanager_Datasource_User(),
					)
				),
				'default_publication' => new Fieldmanager_Select( array(
					'label' => __( 'Default Publication', 'pmc-print-workflow' ),
					'first_empty' => true,
					'datasource' => new Fieldmanager_Datasource_Term(
						array(
							'taxonomy' => pmc_print_workflow_get_publication_taxonomy(),
							'taxonomy_save_to_terms' => false,
						)
					),
				) ),
			)
		) );
		$fm_priority->activate_submenu_page();
	}
}

Ad_Layers_Admin::instance();

endif;