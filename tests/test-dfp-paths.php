<?php
/**
 * Ad Layers Tests: Test_DFP_Paths
 *
 * @package Ad_Layers
 * @subpackage Tests
 */

/**
 * Test_DFP_Paths Class.
 */
class Test_DFP_Paths extends Ad_Layers_TestCase {

	public function setUp() {
		parent::setUp();

		$this->ad_server = Ad_Layers_DFP::instance();
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );
	}

	public function test_path_templates() {
		$this->assertEquals( '/6355419/sidebar/front', $this->ad_server->get_path( 'default', 'sidebar' ) );
	}

	public function test_static_path_templates() {
		$path = rand_str();
		$settings = $this->ad_server_settings;
		$settings['ad_units'][0]['path_override'] = "/{$path}";
		$this->update_ad_server_settings( $settings );
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );

		$this->assertEquals( "/{$path}", $this->ad_server->get_path( 'default', 'sidebar' ) );
	}

	public function test_no_path_templates() {
		$settings = $this->ad_server_settings;
		$settings['path_templates'] = array();
		$this->update_ad_server_settings( $settings );

		$this->assertEquals( '/6355419/' . WP_TESTS_DOMAIN, $this->ad_server->get_path( 'default' ) );
	}

	public function test_global_path_override() {
		$settings = $this->ad_server_settings;
		$settings['ad_units'][0]['path_override'] = '/#account_id#/#ad_unit#/overridden';
		$this->update_ad_server_settings( $settings );
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );

		$this->assertEquals( '/6355419/sidebar/overridden', $this->ad_server->get_path( 'default', 'sidebar' ) );
	}

	public function test_ad_unit_path_override() {
		update_post_meta( $this->ad_layer, 'ad_layer_ad_units', array( array( 'ad_unit' => 'sidebar', 'path_override' => '/#account_id#/#domain#/#ad_unit#/overridden' ) ) );
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );

		$this->assertEquals( '/6355419/' . WP_TESTS_DOMAIN . '/sidebar/overridden', $this->ad_server->get_path( 'default', 'sidebar' ) );
	}

	public function test_ad_unit_path_double_override() {
		$settings = $this->ad_server_settings;
		$settings['ad_units'][0]['path_override'] = '#account_id#/#ad_unit#/overridden';
		$this->update_ad_server_settings( $settings );

		update_post_meta( $this->ad_layer, 'ad_layer_ad_units', array( array( 'ad_unit' => 'sidebar', 'path_override' => '/#account_id#/#ad_unit#/ad_layer_wins' ) ) );
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );

		$this->assertEquals( '/6355419/sidebar/ad_layer_wins', $this->ad_server->get_path( 'default', 'sidebar' ) );
	}
}
