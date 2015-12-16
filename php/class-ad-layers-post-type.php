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
		 * @var string
		 */
		public $post_type = 'ad-layer';

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			// Create the post type
			add_action( 'init', array( $this, 'create_post_type' ) );

			// Add the custom meta boxes for managing this post type
			add_action( 'fm_post_' . $this->post_type, array( $this, 'add_meta_boxes' ) );

			// Add custom columns for the list table
			add_filter( 'manage_' . $this->post_type . '_posts_columns' , array( $this, 'manage_edit_columns' ), 15, 1 );
			add_action( 'manage_' . $this->post_type . '_posts_custom_column' , array( $this, 'manage_custom_columns' ), 10, 2 );

			// Add and remove data from the options list of available ad layers
			add_action( 'save_post_' . $this->post_type, array( $this, 'save_post' ), 99, 3 );
			add_action( 'delete_post', array( $this, 'delete_post' ) );
		}

		/**
		 * Creates the post type.
		 */
		public function create_post_type() {
			register_post_type( $this->post_type, array(
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
				'public' => false,
				'show_ui' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'supports' => array( 'title', 'revisions' ),
				'taxonomies' => apply_filters( 'ad_layers_taxonomies', array( 'category', 'post_tag' ) ),
			) );
		}

		/**
		 * Manage available columns on the edit posts table
		 *
		 * @access public
		 * @param array $columns
		 * @return array
		 */
		public function manage_edit_columns( $columns ) {
	  		// Add columns for custom fields
	  		$columns['ad_layer_page_types'] = __( 'Page Type', 'ad-layers' );
	  		$columns['ad_layer_post_types'] = __( 'Post Types', 'ad-layers' );
	  		$columns['ad_layer_taxonomies'] = __( 'Taxonomies', 'ad-layers' );
	  		$columns['ad_layer_ad_units'] = __( 'Ad Units', 'ad-layers' );
	  		$columns['ad_layer_priority'] = __( 'Priority', 'ad-layers' );

	  		// Move date back to the end
	  		unset( $columns['date'] );
	  		$columns['date'] = __( 'Date', 'ad-layers' );

	  		return apply_filters( 'ad_layers_edit_columns', $columns );
		}

		/**
		 * Manage custom column values.
		 *
		 * @access public
		 * @param string $column
		 * @param int $post_id
		 */
		public function manage_custom_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'ad_layer_page_types':
				case 'ad_layer_post_types':
				case 'ad_layer_taxonomies':
				case 'ad_layer_ad_units':
					$value = get_post_meta( $post_id, $column, true );
					if ( ! empty( $value ) ) {
						if ( is_array( $value ) ) {
							$value = implode( ', ', array_map( function ( $value ) { return $value['ad_unit']; }, $value ));
						}
						echo esc_html( $value );
					}
					break;
				case 'ad_layer_priority':
					$priority = Ad_Layers::instance()->get_ad_layer_priority( $post_id );
					if ( ! empty( $priority ) ) {
						echo absint( $priority );
					} else {
						echo '&mdash;';
					}
					break;
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
			if ( ! class_exists( 'Fieldmanager_Field' ) ) {
				return;
			}

			// Add ad units
			$ad_unit_args = array(
				'name' => 'ad_layer_ad_units',
				'limit' => 0,
				'extra_elements' => 0,
				'one_label_per_item' => false,
				'sortable' => true,
				'label' => __( 'Select one or more ad units.', 'ad-layers' ),
				'add_more_label' => __( 'Add an ad unit', 'ad-layers' ),
				'children' => array(
					'ad_unit' => new Fieldmanager_Select( array(
						'label' => __( 'Ad Unit', 'ad-layers' ),
						'options' => Ad_Layers_Ad_Server::instance()->get_ad_units(),
					) ),
					'do_not_render' => new Fieldmanager_Checkbox( __( 'Do not render the ad on load', 'ad-layers' ) ),
				),
			);

			$targeting_args = Ad_Layers_Ad_Server::instance()->get_custom_targeting_args( 'custom_targeting' );
			if ( ! empty( $targeting_args ) ) {
				$ad_unit_args['children']['custom_targeting'] = new Fieldmanager_Group( apply_filters( 'ad_layers_custom_targeting_ad_unit_args', $targeting_args ) );
			}

			$fm_ad_units = new Fieldmanager_Group( apply_filters( 'ad_layers_ad_units_field_args', $ad_unit_args ) );
			$fm_ad_units->add_meta_box( __( 'Ad Units', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add page types
			$fm_page_types = new Fieldmanager_Select(
				apply_filters( 'ad_layers_page_types_field_args', array(
					'name' => 'ad_layer_page_types',
					'limit' => 0,
					'extra_elements' => 0,
					'one_label_per_item' => false,
					'label' => __( 'Select one or more page types to be targeted with this ad layer.', 'ad-layers' ),
					'add_more_label' => __( 'Add a page type', 'ad-layers' ),
					'options' => Ad_Layers::instance()->get_page_types(),
				) )
			);
			$fm_page_types->add_meta_box( __( 'Page Types', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add taxonomies
			$fm_taxonomies = new Fieldmanager_Select(
				apply_filters( 'ad_layers_taxonomies_field_args', array(
					'name' => 'ad_layer_taxonomies',
					'limit' => 0,
					'extra_elements' => 0,
					'one_label_per_item' => false,
					'label' => __( 'Select one or more optional taxonomies for targeting. Posts with any term in these taxonomies will get the ad layer.', 'ad-layers' ),
					'add_more_label' => __( 'Add a taxonomy', 'ad-layers' ),
					'options' => Ad_Layers::instance()->get_taxonomies(),
				) )
			);
			$fm_taxonomies->add_meta_box( __( 'Taxonomies', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add post types
			$fm_post_types = new Fieldmanager_Select(
				apply_filters( 'ad_layers_post_types_field_args', array(
					'name' => 'ad_layer_post_types',
					'limit' => 0,
					'extra_elements' => 0,
					'one_label_per_item' => false,
					'label' => __( 'Select one or more optional post types for targeting. Any post of this type will get the ad layer.', 'ad-layers' ),
					'add_more_label' => __( 'Add a post type', 'ad-layers' ),
					'options' => Ad_Layers::instance()->get_post_types(),
				) )
			);
			$fm_post_types->add_meta_box( __( 'Post Types', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Custom targeting variables
			$targeting_args = Ad_Layers_Ad_Server::instance()->get_custom_targeting_args();
			if ( ! empty( $targeting_args ) ) {
				$fm_custom = new Fieldmanager_Group( apply_filters( 'ad_layers_custom_targeting_field_args', $targeting_args ) );
				$fm_custom->add_meta_box( __( 'Page Level Custom Targeting', 'ad-layers' ), $this->post_type, 'normal', 'low' );
			}
		}

		/**
		 * Gets the currently saved taxonomies for this post.
		 *
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
		 * Gets the post type name used for ad layers.
		 *
		 * @access public
		 * @return string
		 */
		public function get_post_type() {
			return $this->post_type;
		}

		/**
		 * Decide how to manage this post in the ad layer list on save.
		 *
		 * @access public
		 * @param int $post_id
		 * @param WP_Post $post
		 * @param boolean $update
		 */
		public function save_post( $post_id, $post, $update ) {
			if ( 'auto-draft' == $post->post_status ) {
				return;
			}

			// Get the current global list
			$ad_layers = get_option( 'ad_layers', array() );

			// Create the data to be saved
			$new_layer = array(
				'post_id' => $post_id,
				'title' => $post->post_title,
			);

			// If this is not an update, just append it.
			// Otherwise, find and update the layer.
			$position = null;
			if ( $update ) {
				// If this was an unpublish, delete instead.
				if ( 'publish' != $post->post_status ) {
					$this->delete_post( $post_id );
					return;
				}

				// Otherwise, find and update the layer.
				foreach ( $ad_layers as $i => $layer ) {
					if ( $layer['post_id'] == $post_id ) {
						$position = $i;
						break;
					}
				}
			}

			if ( null === $position ) {
				$position = count( $ad_layers );
			}

			$ad_layers[ $position ] = $new_layer;

			update_option( 'ad_layers', apply_filters( 'ad_layers_save_post', $ad_layers ) );
		}

		/**
		 * Remove this post from the ad layer list on delete.
		 *
		 * @access public
		 * @param int $post_id
		 */
		public function delete_post( $post_id ) {
			// Get the current global list
			$ad_layers = get_option( 'ad_layers' );

			if ( empty( $ad_layers ) ) {
				return;
			}

			// Find and remove the layer
			foreach ( $ad_layers as $i => $layer ) {
				if ( $layer['post_id'] == $post_id ) {
					unset( $ad_layers[ $i ] );
					break;
				}
			}

			update_option( 'ad_layers', apply_filters( 'ad_layers_delete_post', $ad_layers ) );
		}
	}

	Ad_Layers_Post_Type::instance();

endif;
