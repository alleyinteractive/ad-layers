<?php

/**
 * Ad Layers Post Type
 *
 * Implements the custom post type for creating and managing ad layers.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Post_Type' ) ) :

class Ad_Layers_Post_Type extends Ad_Layers_Singleton {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public $name = 'ad-layer';

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Create the post type
		add_action( 'init', array( $this, 'create_post_type' ) );
		
		// Add the custom meta boxes for managing this post type
		add_action( 'fm_post_' . $this->name, array( $this, 'add_meta_boxes' ) );
		
		// Enqueue the Javascript required by the custom meta boxe;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, array(
			'labels' => array(
				'name'               => __( 'Ad Layers', 'ad-layers' ),
				'singular_name'      => __( 'Ad Layer', 'ad-layers' ),
				'add_new'            => __( 'Add New Ad Layer', 'ad-layers' ),
				'add_new_item'       => __( 'Add New Ad Layer', 'ad-layers' ),
				'edit_item'          => __( 'Edit Ad Layer', 'ad-layers' ),
				'new_item'           => __( 'New Ad Layer', 'ad-layers' ),
				'view_item'          => __( 'View Ad Layer', 'ad-layers' ),
				'search_items'       => __( 'Search Ad Layers', 'ad-layers' ),
				'not_found'          => __( 'No ad layers found', 'ad-layers' ),
				'not_found_in_trash' => __( 'No ad layers found in Trash', 'ad-layers' ),
				'menu_name'          => __( 'Ad Layers', 'ad-layers' ),
			),
			'menu_icon' => 'dashicons-schedule',
			'public' => true,
			'publicly_queryable' => false,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'supports' => array( 'title', 'revisions' ),
			'taxonomies' => array(),
		) );
	}
	
	/**
	 * Load scripts used by the admin interface only on the ad layer edit screen.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'edit' == $screen->parent_base && 'post' == $screen->base && ! empty( $screen->post_type ) && $this->name == $screen->post_type ) {
			wp_enqueue_script( 'ad-layers-edit-js', AD_LAYERS_BASE_DIR . '/js/ad-layers-edit.js', array( 'jquery' ), AD_LAYERS_GLOBAL_ASSET_VERSION, false );
			wp_enqueue_style( 'ad-layers-edit-css', AD_LAYERS_BASE_DIR . '/css/ad-layers-edit.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
		}
	}
	
	/**
	 * Adds the meta boxes required to manage an ad layer.
	 *
	 * @param string|array $post_types
	 * @param string $context
	 * @param string $priority
	 */
	public function add_meta_boxes() {
		$fm_ad_units = new Fieldmanager_Select(
			array(
				'name' => 'ad_units',
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'New Ad Unit', 'ad-layers' ),
				'add_more_label' =>  __( 'Add another ad unit', 'ad-layers' ),
			)
		);
		$fm_ad_units->add_meta_box( __( 'Ad Units', 'ad-layers' ), $this->name, 'normal', 'high' );
	}
}

Ad_Layers_Post_Type::instance();

endif;