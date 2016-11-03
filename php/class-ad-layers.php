<?php

/**
 * Ad Layers
 *
 * Handles basic plugin functionality and enqueuing global scripts
 *
 * @author Bradford Campeau-Laurion
 */

/**
 * Instantiate the Ad Layers base class to handle required plugin setup
 */
if ( ! class_exists( 'Ad_Layers' ) ) :

	class Ad_Layers extends Ad_Layers_Singleton {

		/**
		 * Current ad layers.
		 *
		 * @access public
		 * @var string
		 */
		public $ad_layers;

		/**
		 * Current active ad layer.
		 *
		 * @access public
		 * @var string
		 */
		public $ad_layer;

		/**
		 * Page types available for targeting
		 *
		 * @access public
		 * @var array
		 */
		public $page_types;

		/**
		 * Available custom targeting variables.
		 *
		 * @access public
		 * @var string
		 */
		public $custom_variables;

		/**
		 * Setup the singleton.
		 *
		 * @access public
		 */
		public function setup() {
			// Load current settings
			$this->ad_layers = apply_filters( 'ad_layers', get_option( 'ad_layers' ) );
			$this->custom_variables = apply_filters( 'ad_layers_custom_variables', get_option( 'ad_layers_custom_variables' ) );

			// Set the active ad layer before anything else.
			add_action( 'wp_head', array( $this, 'set_active_ad_layer' ), 1 );
		}

		/**
		 * Get taxonomies that can be used for targeting.
		 *
		 * @access public
		 * @return array Taxonomy names as keys, display labels as values
		 */
		public function get_taxonomies() {
			return $this->get_objects( 'taxonomies' );
		}

		/**
		 * Get post types that can be used for targeting.
		 *
		 * @access public
		 * @return array Post type names as keys, display labels as values
		 */
		public function get_post_types() {
			return $this->get_objects( 'post_types' );
		}

		/**
		 * Get current ad layers in priority order.
		 *
		 * @access public
		 * @return array
		 */
		public function get_ad_layers() {
			return apply_filters( 'ad_layers_get_ad_layers', $this->ad_layers );
		}

		/**
		 * Get the active ad layer.
		 *
		 * @access public
		 * @return array
		 */
		public function get_ad_layer() {
			return apply_filters( 'ad_layers_get_ad_layer', $this->ad_layer );
		}

		/**
		 * Get the the priority of an ad layer.
		 *
		 * @access public
		 * @param int $post_id
		 * @return int
		 */
		public function get_ad_layer_priority( $post_id ) {
			if ( ! empty( $this->ad_layers ) ) {
				foreach ( $this->ad_layers as $i => $ad_layer ) {
					if ( $post_id == $ad_layer['post_id'] ) {
						return absint( $i + 1 );
					}
				}
			}

			return null;
		}

		/**
		 * Get current custom targeting variables.
		 *
		 * @access public
		 * @return array
		 */
		public function get_custom_variables() {
			return apply_filters( 'ad_layers_get_custom_variables', $this->custom_variables );
		}

		/**
		 * Get current custom targeting variables.
		 *
		 * @access public
		 * @return array
		 */
		public function get_edit_link() {
			return 'edit.php?post_type=' . Ad_Layers_Post_Type::instance()->get_post_type();
		}

		/**
		 * Get objects that can be used for targeting.
		 *
		 * @access private
		 * @param string $object_type
		 * @return array Object names as keys, display labels as values
		 */
		private function get_objects( $object_type ) {
			$output = array();

			$objects = call_user_func_array(
				'get_' . $object_type,
				array(
					apply_filters( 'ad_layers_get_' . $object_type . '_args', array(
						'public' => true,
						'show_ui' => true,
					) ),
					'object',
					apply_filters( 'ad_layers_get_' . $object_type . '_operator', 'and' ),
				)
			);

			if ( ! empty( $objects ) ) {
				foreach ( $objects as $object ) {
					$output[ $object->name ] = $object->label;
				}
			}

			return apply_filters( 'ad_layers_get_' . $object_type, $output );
		}

		/**
		 * Set the active ad layer.
		 *
		 * @access public
		 */
		public function set_active_ad_layer() {
			// Get the current queried object
			$queried_object = get_queried_object();

			// If the ad layer is filtered for this page, skip the logic below
			$ad_layer = apply_filters( 'ad_layers_active_ad_layer', array(), $queried_object );

			if ( ! empty( $ad_layer ) ) {
				$this->ad_layer = $ad_layer;
				return;
			}

			// Ad layers are already in priority order.
			// Iterate until we find a match.
			// We will eliminate layers that couldn't match the current page
			// based on the most obvious criteria to keep processing time at a minimum.
			// TODO - ADD CACHING
			if ( empty( $this->ad_layers ) ) {
				return;
			}

			foreach ( $this->ad_layers as $ad_layer ) {
				// Get required info on the ad layer
				$post_types = get_post_meta( $ad_layer['post_id'], 'ad_layer_post_types', true );
				$page_types = get_post_meta( $ad_layer['post_id'], 'ad_layer_page_types', true );
				$taxonomies = get_post_meta( $ad_layer['post_id'], 'ad_layer_taxonomies', true );

				// Build an array of taxonomies and terms
				$taxonomy_terms = array();
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						$taxonomy_terms[ $taxonomy ] = array();
					}
				}

				foreach ( $this->get_taxonomies() as $taxonomy => $label ) {
					$terms = get_the_terms( $ad_layer['post_id'], $taxonomy );
					if ( ! empty( $terms ) ) {
						$taxonomy_terms[ $taxonomy ] = wp_list_pluck( $terms, 'term_id' );
					}
				}

				if ( is_singular() ) {
					// See if a specific ad layer is set
					$ad_layer_id = intval( get_post_meta( get_the_ID(), 'ad_layer', true ) );
					if ( ! empty( $ad_layer_id ) ) {
						$this->ad_layer = array(
							'post_id' => $ad_layer_id,
							'title' => get_the_title( $ad_layer_id ),
						);
						break;
					}

					// Check the page type
					if ( ! empty( $page_types ) && ! in_array( $queried_object->post_type, $page_types ) ) {
						continue;
					}

					// Check the post type
					if ( ! empty( $post_types ) && ! in_array( $queried_object->post_type, $post_types ) ) {
						continue;
					}

					// Check taxonomies
					if ( ! empty( $taxonomy_terms ) ) {
						foreach ( $taxonomy_terms as $taxonomy => $terms ) {
							if ( has_term( $terms, $taxonomy ) ) {
								$taxonomy_match = true;
								break;
							}
						}

						if ( ! $taxonomy_match ) {
							continue;
						}
					}

					// If we made it here, there's a match.
					$this->ad_layer = $ad_layer;
					break;
				} if ( is_home()
					&& empty( $post_types )
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& ( empty( $page_types ) || in_array( 'home', $page_types ) ) ) {
					$this->ad_layer = $ad_layer;
					break;
				} elseif ( ( is_tax() || is_category() || is_tag() )
					&& empty( $post_types )
					&& ( empty( $page_types ) || in_array( $queried_object->taxonomy, $page_types ) ) ) {

					$ad_layer_id = intval( get_term_meta( get_queried_object()->term_id, 'ad_layer', true ) );

					if ( ! empty( $ad_layer_id ) ) {
						$this->ad_layer = array(
							'post_id' => $ad_layer_id,
							'title' => get_the_title( $ad_layer_id ),
						);
						break;
					}
					
					// Check if there is taxonomy data
					if ( ! empty( $taxonomy_terms ) ) {
						// Check if this taxonomy matches
						if ( array_key_exists( $queried_object->taxonomy, $taxonomy_terms )
							&& (
								empty( $taxonomy_terms[ $queried_object->taxonomy ] )
								|| in_array( $queried_object->term_id, $taxonomy_terms[ $queried_object->taxonomy ] )
							)
						) {
							$this->ad_layer = $ad_layer;
							break;
						}
					} else {
						// if there is no taxonomy data, this is a page type match
						$this->ad_layer = $ad_layer;
						break;
					}
				} elseif ( is_post_type_archive()
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& (
						( ! empty( $post_types ) && in_array( $queried_object->name, $post_types ) )
						|| empty( $post_types )
					)
					&& ( empty( $page_types ) || in_array( 'archive::' . $queried_object->name, $page_types ) ) ) {
					$this->ad_layer = $ad_layer;
					break;
				} elseif ( is_author()
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& empty( $post_types )
					&& in_array( 'author', $page_types ) ) {
					$this->ad_layer = $ad_layer;
					break;
				} elseif ( is_date()
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& empty( $post_types )
					&& in_array( 'date', $page_types ) ) {
					$this->ad_layer = $ad_layer;
					break;
				} elseif ( is_404()
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& empty( $post_types )
					&& in_array( 'notfound', $page_types ) ) {
					$this->ad_layer = $ad_layer;
					break;
				} elseif ( is_search()
					&& empty( $taxonomies )
					&& empty( $taxonomy_terms )
					&& empty( $post_types )
					&& in_array( 'search', $page_types ) ) {
					$this->ad_layer = $ad_layer;
					break;
				}
			}
		}

		/**
		 * Get the available page types for all ad servers.
		 * These are especially used by path targeting.
		 * This is kind of expensive so make sure we only do it once.
		 * @access public
		 * @return array
		 */
		public function get_page_types() {
			if ( empty( $this->page_types ) ) {
				// Build the page types.
				// First add global types.
				$page_types = array(
					'home' => __( 'Home Page', 'ad-layers' ),
				);

				// Add single post types
				$single_post_types = apply_filters( 'ad_layers_ad_server_single_post_types', wp_list_filter( get_post_types( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
				if ( ! empty( $single_post_types ) ) {
					foreach ( $single_post_types as $post_type ) {
						if ( Ad_Layers_Post_Type::instance()->get_post_type() != $post_type->name ) {
							$page_types[ $post_type->name ] = $post_type->label;
						}
					}
				}

				// Add archived post types
				$archived_post_types = apply_filters( 'ad_layers_ad_server_archived_post_types', wp_list_filter( get_post_types( array( 'has_archive' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
				if ( ! empty( $archived_post_types ) ) {
					foreach ( $archived_post_types as $post_type ) {
						$page_types[ 'archive::' . $post_type->name ] = $post_type->label . __( ' Archive', 'ad-layers' );
					}
				}

				// Add taxonomies
				$taxonomies = apply_filters( 'ad_layers_ad_server_taxonomies', wp_list_filter( get_taxonomies( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						$page_types[ $taxonomy->name ] = $taxonomy->label . __( ' Archive', 'ad-layers' );
					}
				}

				// Add some other templates at the bottom
				$page_types = array_merge( $page_types, array(
					'author' => __( 'Author Archive', 'ad-layers' ),
					'date' => __( 'Date Archive', 'ad-layers' ),
					'notfound' => __( '404 Page', 'ad-layers' ),
					'search' => __( 'Search Results', 'ad-layers' ),
					'default' => __( 'Default', 'ad-layers' ),
				) );

				$this->page_types = $page_types;
			}

			return apply_filters( 'ad_layers_page_types', $this->page_types );
		}

		/**
		 * Get the current page type.
		 * @access public
		 * @return string
		 */
		public function get_current_page_type() {
			// Get the current page types
			$page_types = $this->get_page_types();

			// Iterate for a match
			$page_type = '';
			foreach ( $page_types as $key => $label ) {
				if (
					( function_exists( 'is_' . $key ) && true === call_user_func( 'is_' . $key ) )
					|| ( 'post_tag' == $key && is_tag() )
					|| ( 'notfound' == $key && is_404() )
					|| ( 'archive::' === substr( $key, 0, 9 ) && is_post_type_archive( substr( $key, 9 ) ) )
					|| ( post_type_exists( $key ) && is_singular( $key ) )
					|| ( taxonomy_exists( $key ) && is_tax( $key ) )
				) {
					$page_type = $key;
					break;
				}
			}

			// Use default if no match
			if ( empty( $page_type ) ) {
				$page_type = 'default';
			}

			return apply_filters( 'ad_layers_current_page_type', $page_type );
		}
	}

	Ad_Layers::instance();

endif;
