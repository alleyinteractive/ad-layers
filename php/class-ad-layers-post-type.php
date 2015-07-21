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
	 * @access public
	 * @static
	 * @var string
	 */
	public static $post_type = 'ad-layer';

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Create the post type
		add_action( 'init', array( $this, 'create_post_type' ) );
		
		// Add the custom meta boxes for managing this post type
		add_action( 'fm_post_' . self::$post_type, array( $this, 'add_meta_boxes' ) );
		
		// Enqueue the Javascript required by the custom meta boxe;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add and remove data from the options list of available ad layers
		add_action( 'save_post', array( $this, 'save_post' ), 99, 3 );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
	}
	
	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( self::$post_type, array(
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
			'taxonomies' => apply_filters( 'ad_layers_taxonomies', array( 'category', 'post_tag' ) ),
		) );
	}
	
	/**
	 * Load scripts used by the admin interface only on the ad layer edit screen.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'edit' == $screen->parent_base && 'post' == $screen->base && ! empty( $screen->post_type ) && self::$post_type == $screen->post_type ) {
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
		// Add ad units
		$fm_ad_units = new Fieldmanager_Select(
			array(
				'name' => 'ad_layer_ad_units',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'label' => __( 'Select one or more ad units.', 'ad-layers' ),
				'add_more_label' =>  __( 'Add an ad unit', 'ad-layers' ),
				'options' => Ad_Layers_Ad_Server::get_ad_units(),
			)
		);
		$fm_ad_units->add_meta_box( __( 'Ad Units', 'ad-layers' ), self::$post_type, 'normal', 'high' );
		
		// Add taxonomies
		$fm_taxonomies = new Fieldmanager_Select(
			array(
				'name' => 'ad_layer_taxonomies',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'label' => __( 'Select one or more optional taxonomies for targeting. Posts with any term in these taxonomies will get the ad layer.', 'ad-layers' ),
				'add_more_label' =>  __( 'Add a taxonomy', 'ad-layers' ),
				'options' => Ad_Layers::get_taxonomies(),
			)
		);
		$fm_taxonomies->add_meta_box( __( 'Taxonomies', 'ad-layers' ), self::$post_type, 'normal', 'high' );
		
		// Add post types
		$fm_post_types = new Fieldmanager_Select(
			array(
				'name' => 'ad_layer_post_types',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'label' => __( 'Select one or more optional post types for targeting. Any post of this type will get the ad layer.', 'ad-layers' ),
				'add_more_label' =>  __( 'Add a post type', 'ad-layers' ),
				'options' => Ad_Layers::get_post_types(),
			)
		);
		$fm_post_types->add_meta_box( __( 'Post Types', 'ad-layers' ), self::$post_type, 'normal', 'high' );
	}
	
	/**
	 * Gets the currently saved taxonomies for this post.
	 * @access private
	 * @return array
	 */
	private function get_taxonomies() {
		if ( ! isset( $_GET['post'] ) ) {
			return array();
		}
		
		return get_post_meta( intval( $_GET['post'] ), 'ad_layer_taxonomies', true );
	}
	
	/**
	 * Decide how to manage this post in the ad layer list on save.
	 * @access public
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public function save_post( $post_id, $post, $update ) {
		// Get the current global list
		
	}
	
	/**
	 * Remove this post from the ad layer list on delete.
	 * @access public
	 * @param int $post_id
	 */
	public function delete_post( $post_id ) {
		
	}
}

Ad_Layers_Post_Type::instance();

endif;