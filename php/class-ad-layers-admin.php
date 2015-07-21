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
		// Register the ad layer priority settings page
		add_action( 'init', array( $this, 'add_priority_page' ) );
		
		// Register the ad layer custom variables page
	}
	
	/**
	 * Add the ad layer priority management page
	 */
	public function add_priority_page() {
		$fm_priority = new Fieldmanager_Group( array(
			'name' => 'ad_layers',
			'sortable' => true,
			'collapsible' => true,
			'collapsed' => true,
			'label' => __( 'Ad Layer', 'ad-layers' ),
			'label_macro' => array( __( '%s', 'ad-layers' ), 'title' ),
			'children' => array(
				'post_id' => new Fieldmanager_Hidden(),
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
						'multiple' => true,
						'attributes' => array(
							'readonly' => 'readonly',
						),
					)
				),
			)
		) );
		$fm_priority->add_submenu_page( 'edit.php?post_type=ad-layer', __( 'Layer Priority', 'ad-layers' ) );
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
			'label_macro' => array( __( '%s', 'ad-layers' ), 'title' ),
			'children' => array(
				'post_id' => new Fieldmanager_Hidden(),
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
						'multiple' => true,
						'attributes' => array(
							'readonly' => 'readonly',
						),
					)
				),
			)
		) );
		$fm_priority->add_submenu_page( 'edit.php?post_type=ad-layer', __( 'Layer Priority', 'ad-layers' ) );
	}
}

Ad_Layers_Admin::instance();

endif;