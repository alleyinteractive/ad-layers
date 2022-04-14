<?php
/**
 * Ad Layers Tests: Test_DFP_Formatting_Tags
 *
 * @package Ad_Layers
 * @subpackage Tests
 */

use Ad_Layers\Ad_Layers;

/**
 * Test_DFP_Formatting_Tags Class.
 */
class Test_DFP_Formatting_Tags extends Ad_Layers_TestCase {

	/**
	 * Actions to be run before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->ad_server = Ad_Layers_DFP::instance();
		$this->ad_server->get_ad_units_for_layer( $this->ad_layer );
	}

	/**
	 * Test token replacement in custom values.
	 */
	public function test_token_replacement() {
		update_option( 'ad_layers_custom_variables', [ 'domain' ] );
		update_post_meta(
			Ad_Layers::instance()->ad_layer['post_id'],
			'ad_layer_custom_targeting',
			[
				[
					'custom_variable' => 'domain',
					'source'          => 'other',
					'values'          => [ '#domain#' ],
				],
			]
		);
		ob_start();
		$this->ad_server->header_setup();
		$header = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'googletag.pubads().setTargeting("domain",["' . WP_TESTS_DOMAIN . '"]);', $header );
	}
}
