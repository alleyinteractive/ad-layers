<?php
/**
 * Custom post type for Ad Layers
 *
 * @package Ad_Layers
 */

/**
 * Class for the ad-layer post type.
 */
class Ad_Layers_Post_Type_Ad_Layer extends Ad_Layers_Post_Type {

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	public $name = 'ad-layer';

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type(
			$this->name,
			[
				'labels'       => [
					'name'                     => __( 'Ad Layers', 'ad-layers' ),
					'singular_name'            => __( 'Ad Layer', 'ad-layers' ),
					'add_new'                  => __( 'Add New Ad Layer', 'ad-layers' ),
					'add_new_item'             => __( 'Add New Ad Layer', 'ad-layers' ),
					'edit_item'                => __( 'Edit Ad Layer', 'ad-layers' ),
					'new_item'                 => __( 'New Ad Layer', 'ad-layers' ),
					'view_item'                => __( 'View Ad Layer', 'ad-layers' ),
					'view_items'               => __( 'View Ad Layers', 'ad-layers' ),
					'search_items'             => __( 'Search Ad Layers', 'ad-layers' ),
					'not_found'                => __( 'No ad layers found', 'ad-layers' ),
					'not_found_in_trash'       => __( 'No ad layers found in Trash', 'ad-layers' ),
					'parent_item_colon'        => __( 'Parent Ad Layer:', 'ad-layers' ),
					'all_items'                => __( 'All Ad Layers', 'ad-layers' ),
					'archives'                 => __( 'Ad Layer Archives', 'ad-layers' ),
					'attributes'               => __( 'Ad Layer Attributes', 'ad-layers' ),
					'insert_into_item'         => __( 'Insert into ad layer', 'ad-layers' ),
					'uploaded_to_this_item'    => __( 'Uploaded to this ad layer', 'ad-layers' ),
					'featured_image'           => __( 'Featured image', 'ad-layers' ),
					'set_featured_image'       => __( 'Set featured image', 'ad-layers' ),
					'remove_featured_image'    => __( 'Remove featured image', 'ad-layers' ),
					'use_featured_image'       => __( 'Use as featured image', 'ad-layers' ),
					'filter_items_list'        => __( 'Filter ad layers list', 'ad-layers' ),
					'items_list_navigation'    => __( 'Ad Layers list navigation', 'ad-layers' ),
					'items_list'               => __( 'Ad Layers list', 'ad-layers' ),
					'item_published'           => __( 'Ad Layer published.', 'ad-layers' ),
					'item_published_privately' => __( 'Ad Layer published privately.', 'ad-layers' ),
					'item_reverted_to_draft'   => __( 'Ad Layer reverted to draft.', 'ad-layers' ),
					'item_scheduled'           => __( 'Ad Layer scheduled.', 'ad-layers' ),
					'item_updated'             => __( 'Ad Layer updated.', 'ad-layers' ),
					'menu_name'                => __( 'Ad Layers', 'ad-layers' ),
				],
				'menu_icon'       => 'dashicons-schedule',
				'show_ui'         => true,
				'supports'        => [ 'title', 'revisions' ],
				'map_meta_cap'    => true,

				/**
				 * Filter ad layer post type supported taxonomies.
				 *
				 * This is passed to the register_post_type function.
				 *
				 * @param array $taxonomies. Defaults to [ 'category', 'post_tag' ].
				 */
				'taxonomies'      => apply_filters( 'ad_layers_taxonomies', [ 'category', 'post_tag' ] ),

				/**
				 * Filter the capability required to manage the ad layers post type.
				 *
				 * This is passed to the "capability_type" arg in
				 * `register_post_type()`, and becomes the base for all post-related
				 * capabilities (e.g. edit_posts, create_posts, delete_post, etc.).
				 *
				 * @param string $capability_type. Defaults to `post`.
				 */
				'capability_type' => apply_filters( 'ad_layers_post_type_capability', 'post' ),
			]
		);
	}

	/**
	 * Set post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Post updated. {View Post}"
	 *   2 => "Custom field updated."
	 *   3 => "Custom field deleted."
	 *   4 => "Post updated."
	 *   5 => "Post restored to revision from [date]."
	 *   6 => "Post published. {View post}"
	 *   7 => "Post saved."
	 *   8 => "Post submitted. {Preview post}"
	 *   9 => "Post scheduled for: [date]. {Preview post}"
	 *  10 => "Post draft updated. {Preview post}"
	 *
	 * (Via https://github.com/johnbillion/extended-cpts.)
	 *
	 * @param array $messages An associative array of post updated messages with post type as keys.
	 * @return array Updated array of post updated messages.
	 */
	public function set_post_updated_messages( $messages ) {
		global $post;

		$preview_url    = get_preview_post_link( $post );
		$permalink      = get_permalink( $post );
		$scheduled_date = date_i18n( 'M j, Y @ H:i', strtotime( $post->post_date ) );

		$preview_post_link_html   = '';
		$scheduled_post_link_html = '';
		$view_post_link_html      = '';

		if ( is_post_type_viewable( $this->name ) ) {
			// Preview-post link.
			$preview_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $preview_url ),
				__( 'Preview ad layer', 'ad-layers' )
			);

			// Scheduled post preview link.
			$scheduled_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'Preview ad layer', 'ad-layers' )
			);

			// View-post link.
			$view_post_link_html = sprintf(
				' <a href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'View ad layer', 'ad-layers' )
			);
		}

		$messages[ $this->name ] = [
			1  => __( 'Ad Layer updated.', 'ad-layers' ) . $view_post_link_html,
			2  => __( 'Custom field updated.', 'ad-layers' ),
			3  => __( 'Custom field updated.', 'ad-layers' ),
			4  => __( 'Ad Layer updated.', 'ad-layers' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ad Layer restored to revision from %s.', 'ad-layers' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Ad Layer published.', 'ad-layers' ) . $view_post_link_html,
			7  => __( 'Ad Layer saved.', 'ad-layers' ),
			8  => __( 'Ad Layer submitted.', 'ad-layers' ) . $preview_post_link_html,
			/* translators: %s: date on which the ad layer is currently scheduled to be published */
			9  => sprintf( __( 'Ad Layer scheduled for: %s.', 'ad-layers' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
			10 => __( 'Ad Layer draft updated.', 'ad-layers' ) . $preview_post_link_html,
		];

		return $messages;
	}
}
$ad_layers_post_type_ad_layer = new Ad_Layers_Post_Type_Ad_Layer();
