<?php
/**
 * Post type base class file
 *
 * @package Ad_Layers
 */

/**
 * Abstract class for post type classes.
 */
abstract class Ad_Layers_Post_Type {

	/**
	 * Name of the post type.
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Create the post type.
		add_action( 'init', [ $this, 'create_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'set_post_updated_messages' ] );
	}

	/**
	 * Create the post type.
	 */
	abstract public function create_post_type();

	/**
	 * Set the post type "updated" messages.
	 *
	 * @param array $messages An associative array of post updated messages with post type names as keys.
	 * @return array Updated array of post updated messages.
	 */
	abstract public function set_post_updated_messages( $messages );
}
