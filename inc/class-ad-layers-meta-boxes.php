<?php
/**
 * Manages the meta box on the post type edit screen for selecting an ad layer.
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

if ( ! class_exists( 'Ad_Layers\Ad_Layers_Meta_Boxes' ) ) :

	/**
	 * Ad_Layers_Meta_Boxes Class.
	 */
	class Ad_Layers_Meta_Boxes extends Ad_Layers_Singleton {

		/**
		 * Posts types used by ad layers
		 *
		 * @var string
		 */
		public $post_types = [ 'post' ];

		/**
		 * Capability required to assign ads to posts.
		 *
		 * @var string
		 */
		public $assign_ads_to_posts_capability;

		/**
		 * Setup the singleton.
		 */
		public function setup() {
			/**
			 * Filter the capability required to assign ads to posts.
			 *
			 * @param string $capability. Defaults to `edit_posts`.
			 */
			$this->assign_ads_to_posts_capability = apply_filters( 'ad_layers_assign_ads_to_posts_capability', 'edit_posts' );

			if ( ! current_user_can( $this->assign_ads_to_posts_capability ) ) {
				return;
			}

			// Set post types used by ad layers.
			$this->post_types = apply_filters( 'ad_layers_post_types', $this->post_types );

			// Add the custom meta boxes used for ad layers on posts.
			foreach ( $this->post_types as $post_type ) {
				add_action( 'fm_post_' . $post_type, [ $this, 'add_meta_boxes' ] );
			}
		}

		/**
		 * Adds the meta boxes required to manage ad layers on posts.
		 *
		 * @access public
		 */
		public function add_meta_boxes() {
			// Get the post type name.
			$post_type = str_replace( 'fm_post_', '', current_filter() );

			// Add ad units.
			$fm_ad_layer = new Fieldmanager_Autocomplete(
				[
					'name'        => 'ad_layer',
					'description' => __( 'Select a specific custom ad layer to use with this post.', 'ad-layers' ),
					'datasource'  => new Fieldmanager_Datasource_Post(
						[
							'query_args' => [
								'post_type'   => [ Ad_Layers_Post_Type::instance()->get_post_type() ],
								'post_status' => 'publish',
								'order_by'    => 'title',
							],
						]
					),
				]
			);
			$fm_ad_layer->add_meta_box( __( 'Ad Layer', 'ad-layers' ), $post_type, 'side', 'core' );
		}
	}

	Ad_Layers_Meta_Boxes::instance();

endif;
