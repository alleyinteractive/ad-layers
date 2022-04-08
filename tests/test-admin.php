<?php
/**
 * Ad Layers Tests: Admin tests
 *
 * @package Ad_Layers
 * @subpackage Tests
 */

use Ad_Layers\Ad_Layers;

/**
 * Test_Active_Layer Class.
 */
class Test_Admin extends Ad_Layers_TestCase {
	/**
	 * Ensures that when layers are created, renamed, and removed, they appear
	 * correctly in layer priority, not creating duplicates or orphans.
	 */
	public function test_layer_priority_sync() {
		// Ensure that we start out with only one item in layer priority (from parent setUp).
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 1, count( $layer_priority ) );
		$layer_1_id = $layer_priority[0]['post_id'];

		// Ensure new layers show up in layer priority.
		$layer_2        = self::factory()->post->create_and_get( [ 'post_type' => 'ad-layer' ] );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 2, count( $layer_priority ) );
		$this->assertEquals( $layer_2->ID, $layer_priority[1]['post_id'] );

		// Ensure that renaming a layer does not create duplicates in layer priority.
		$layer_2->post_title = 'New Layer Title';
		wp_update_post( $layer_2 );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 2, count( $layer_priority ) );
		$this->assertEquals( $layer_2->ID, $layer_priority[1]['post_id'] );

		// Ensure that removing a layer does not result in orphans in layer priority.
		wp_delete_post( $layer_2->ID );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 1, count( $layer_priority ) );
		$this->assertEquals( $layer_1_id, $layer_priority[0]['post_id'] );

		// Add another layer so it gets automatically added to layer priority.
		$layer_3        = self::factory()->post->create_and_get( [ 'post_type' => 'ad-layer' ] );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 2, count( $layer_priority ) );
		$this->assertEquals( $layer_3->ID, $layer_priority[1]['post_id'] );

		// Simulate the (legacy) behavior of layer priority changes resulting in post IDs being cast to strings.
		$layer_priority[1]['post_id'] = (string) $layer_3->ID;
		update_option( 'ad_layers', $layer_priority );

		// Ensure that renaming a layer does not create duplicates in layer priority.
		$layer_3->post_title = 'New Layer Title 2';
		wp_update_post( $layer_3 );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 2, count( $layer_priority ) );
		$this->assertEquals( $layer_3->ID, (int) $layer_priority[1]['post_id'] );

		// Ensure that removing a layer does not result in orphans in layer priority.
		wp_delete_post( $layer_3->ID );
		$layer_priority = get_option( 'ad_layers' );
		$this->assertEquals( 1, count( $layer_priority ) );
		$this->assertEquals( $layer_1_id, (int) $layer_priority[0]['post_id'] );
	}
}
