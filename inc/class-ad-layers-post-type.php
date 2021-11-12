<?php
/**
 * Implements the custom post type for creating and managing ad layers.
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

use Ad_Layers\Ad_Layers;
use Ad_Layers\Ad_Servers\Ad_Server;
use \Fieldmanager_Select;
use \Fieldmanager_Group;
use \Fieldmanager_Checkbox;

if ( ! class_exists( 'Ad_Layers_Post_Type' ) ) :

	/**
	 * Ad_Layers_Post_Type Class.
	 */
	class Ad_Layers_Post_Type extends Ad_Layers_Singleton {

		/**
		 * Post type name.
		 *
		 * @access public
		 * @var string
		 */
		public $post_type = 'ad-layer';

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			// Add the custom meta boxes for managing this post type.
			add_action( 'fm_post_' . $this->post_type, [ $this, 'add_meta_boxes' ] );

			// Add custom columns for the list table.
			add_filter( 'manage_' . $this->post_type . '_posts_columns', [ $this, 'manage_edit_columns' ], 15, 1 );
			add_action( 'manage_' . $this->post_type . '_posts_custom_column', [ $this, 'manage_custom_columns' ], 10, 2 );

			// Add and remove data from the options list of available ad layers.
			add_action( 'save_post_' . $this->post_type, [ $this, 'save_post' ], 99, 3 );
			add_action( 'delete_post', [ $this, 'delete_post' ] );
		}

		/**
		 * Manage available columns on the edit posts table
		 *
		 * @access public
		 * @param array $columns admin columns array.
		 * @return array updated columns array.
		 */
		public function manage_edit_columns( $columns ) {
			// Add columns for custom fields.
			$columns['ad_layer_page_types'] = __( 'Page Type', 'ad-layers' );
			$columns['ad_layer_post_types'] = __( 'Post Types', 'ad-layers' );
			$columns['ad_layer_taxonomies'] = __( 'Taxonomies', 'ad-layers' );
			$columns['ad_layer_terms']      = __( 'Terms', 'ad-layers' );
			$columns['ad_layer_ad_units']   = __( 'Ad Units', 'ad-layers' );
			$columns['ad_layer_priority']   = __( 'Priority', 'ad-layers' );

			// Remove Date.
			unset( $columns['date'] );
			unset( $columns['categories'] );
			unset( $columns['tags'] );

			return apply_filters( 'ad_layers_edit_columns', $columns );
		}

		/**
		 * Manage custom column values.
		 *
		 * @access public
		 * @param string $column single column slug.
		 * @param int    $post_id Post id.
		 */
		public function manage_custom_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'ad_layer_ad_units':
					$value = get_post_meta( $post_id, $column, true );
					if ( ! empty( $value[0]['ad_unit'] ) ) {
						$this->column_listify( wp_list_pluck( $value, 'ad_unit' ) );
					}
					break;

				case 'ad_layer_page_types':
					$value      = get_post_meta( $post_id, $column, true );
					$page_types = Ad_Layers::instance()->get_page_types();
					if ( is_array( $value ) ) {
						$values = [];
						foreach ( $value as $page_type ) {
							$values[] = ( ! empty( $page_types[ $page_type ] ) ? $page_types[ $page_type ] : $page_type );
						}
						$this->column_listify( $values );
					} else {
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

				case 'ad_layer_taxonomies':
					$taxonomies = $this->get_taxonomy_names( get_post_meta( $post_id, $column, true ) );
					$this->column_listify( $taxonomies );
					break;

				case 'ad_layer_terms':
					// Normally, we'd avoid use of wp_get_object_terms, but here
					// it's a bit more efficient than get_post_terms. It's also,
					// less critical since this is the edit.php view in admin.
					$terms = wp_get_object_terms( $post_id, get_object_taxonomies( get_post( $post_id ) ) );
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						$term_list = [];
						foreach ( $terms as $term ) {
							list( $taxonomy_name ) = $this->get_taxonomy_names( (array) $term->taxonomy );
							$term_list[]           = sprintf( '%s: %s', $taxonomy_name, $term->name );
						}
						$this->column_listify( $term_list );
					}
					break;

				case 'ad_layer_post_types':
					$post_types = $this->get_post_type_names( get_post_meta( $post_id, $column, true ) );
					$this->column_listify( $post_types );
					break;
			}
		}

		/**
		 * Adds the meta boxes required to manage an ad layer.
		 */
		public function add_meta_boxes() {
			if ( ! class_exists( '\Fieldmanager_Field' ) ) {
				return;
			}

			// Add ad units.
			$ad_unit_args = [
				'name'               => 'ad_layer_ad_units',
				'limit'              => 0,
				'extra_elements'     => 0,
				'one_label_per_item' => false,
				'sortable'           => true,
				'label'              => __( 'Select one or more ad units.', 'ad-layers' ),
				'add_more_label'     => __( 'Add an ad unit', 'ad-layers' ),
				'children'           => [
					'ad_unit'       => new Fieldmanager_Select(
						[
							'label'   => __( 'Ad Unit', 'ad-layers' ),
							'options' => Ad_Server::instance()->get_ad_units(),
						]
					),
					'do_not_render' => new Fieldmanager_Checkbox( __( 'Do not render the ad on load', 'ad-layers' ) ),
				],
			];

			$targeting_args = Ad_Server::instance()->get_custom_targeting_args( 'custom_targeting' );
			if ( ! empty( $targeting_args ) ) {
				$ad_unit_args['children']['custom_targeting'] = new Fieldmanager_Group( apply_filters( 'ad_layers_custom_targeting_ad_unit_args', $targeting_args ) );
			}

			$fm_ad_units = new Fieldmanager_Group( apply_filters( 'ad_layers_ad_units_field_args', $ad_unit_args ) );
			$fm_ad_units->add_meta_box( __( 'Ad Units', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add page types.
			$fm_page_types = new Fieldmanager_Select(
				apply_filters(
					'ad_layers_page_types_field_args',
					[
						'name'               => 'ad_layer_page_types',
						'limit'              => 0,
						'extra_elements'     => 0,
						'one_label_per_item' => false,
						'label'              => __( 'Select one or more page types to be targeted with this ad layer.', 'ad-layers' ),
						'add_more_label'     => __( 'Add a page type', 'ad-layers' ),
						'options'            => Ad_Layers::instance()->get_page_types(),
					]
				)
			);
			$fm_page_types->add_meta_box( __( 'Page Types', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add taxonomies.
			$fm_taxonomies = new Fieldmanager_Select(
				apply_filters(
					'ad_layers_taxonomies_field_args',
					[
						'name'               => 'ad_layer_taxonomies',
						'limit'              => 0,
						'extra_elements'     => 0,
						'one_label_per_item' => false,
						'label'              => __( 'Select one or more optional taxonomies for targeting. Posts with any term in these taxonomies will get the ad layer.', 'ad-layers' ),
						'add_more_label'     => __( 'Add a taxonomy', 'ad-layers' ),
						'options'            => Ad_Layers::instance()->get_taxonomies(),
					]
				)
			);
			$fm_taxonomies->add_meta_box( __( 'Taxonomies', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Add post types.
			$fm_post_types = new Fieldmanager_Select(
				apply_filters(
					'ad_layers_post_types_field_args',
					[
						'name'               => 'ad_layer_post_types',
						'limit'              => 0,
						'extra_elements'     => 0,
						'one_label_per_item' => false,
						'label'              => __( 'Select one or more optional post types for targeting. Any post of this type will get the ad layer.', 'ad-layers' ),
						'add_more_label'     => __( 'Add a post type', 'ad-layers' ),
						'options'            => Ad_Layers::instance()->get_post_types(),
					]
				)
			);
			$fm_post_types->add_meta_box( __( 'Post Types', 'ad-layers' ), $this->post_type, 'normal', 'high' );

			// Custom targeting variables.
			$targeting_args = Ad_Server::instance()->get_custom_targeting_args();
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
			/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
			if ( ! isset( $_GET['post'] ) ) {
				return [];
			}

			return get_post_meta( intval( $_GET['post'] ), 'ad_layer_taxonomies', true );
			/* phpcs:enable WordPress.Security.NonceVerification.Recommended */
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
		 * @param int     $post_id Post id.
		 * @param WP_Post $post WP Post object.
		 * @param boolean $update whether or not this is an update.
		 */
		public function save_post( $post_id, $post, $update ) {
			if ( 'auto-draft' === $post->post_status ) {
				return;
			}

			// Get the current global list.
			$ad_layers = get_option( 'ad_layers', [] );

			// Create the data to be saved.
			$new_layer = [
				'post_id' => $post_id,
				'title'   => $post->post_title,
			];

			// If this is not an update, just append it.
			// Otherwise, find and update the layer.
			$position = null;
			if ( $update ) {
				// If this was an unpublish, delete instead.
				if ( 'publish' !== $post->post_status ) {
					$this->delete_post( $post_id );
					return;
				}

				// Otherwise, find and update the layer.
				foreach ( $ad_layers as $i => $layer ) {
					if ( $layer['post_id'] === $post_id ) {
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
		 * @param int $post_id Post id.
		 */
		public function delete_post( $post_id ) {
			// Get the current global list.
			$ad_layers = get_option( 'ad_layers' );

			if ( empty( $ad_layers ) ) {
				return;
			}

			// Find and remove the layer.
			foreach ( $ad_layers as $i => $layer ) {
				if ( $layer['post_id'] === $post_id ) {
					unset( $ad_layers[ $i ] );
					break;
				}
			}

			update_option( 'ad_layers', apply_filters( 'ad_layers_delete_post', $ad_layers ) );
		}

		/**
		 * Get the human-readable names for given taxonomies.
		 *
		 * This is a helper for custom column values, so the user sees e.g.
		 * "Tags" instead of "post_tag".
		 *
		 * @param  array $taxonomies Taxonomy slugs.
		 * @return array Taxonomy names.
		 */
		public function get_taxonomy_names( $taxonomies ) {
			if ( empty( $taxonomies ) ) {
				return [];
			}
			foreach ( (array) $taxonomies as $i => $taxonomy ) {
				$tax_obj          = get_taxonomy( $taxonomy );
				$taxonomies[ $i ] = $tax_obj->labels->name;
			}
			return $taxonomies;
		}

		/**
		 * Get the human-readable names for given post types.
		 *
		 * This is a helper for custom column values, so the user sees e.g.
		 * "My Custom Posts" instead of "my-custom-posts".
		 *
		 * @param  array $post_types Post type slugs.
		 * @return array Post type names.
		 */
		public function get_post_type_names( $post_types ) {
			if ( empty( $post_types ) ) {
				return [];
			}
			foreach ( (array) $post_types as $i => $post_type ) {
				$post_type_obj    = get_post_type_object( $post_type );
				$post_types[ $i ] = $post_type_obj->labels->name;
			}
			return $post_types;
		}

		/**
		 * Output an array as an unordered list for custom columns.
		 *
		 * @param  array $array The array to output.
		 */
		protected function column_listify( $array ) {
			echo '<ul class="ad-layers-column-list"><li>' . implode( '</li><li>', array_map( 'esc_html', $array ) ) . '</li></ul>';
		}
	}

	Ad_Layers_Post_Type::instance();

endif;
