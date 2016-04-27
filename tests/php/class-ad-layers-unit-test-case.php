<?php

class Ad_Layers_UnitTestCase extends WP_UnitTestCase {
	protected $ad_server_parent, $ad_server, $ad_layer, $ad_server_settings;

	public function setUp() {
		parent::setUp();

		$this->ad_server_parent = Ad_Layers_Ad_Server::instance();

		$this->ad_server_settings = array(
			'ad_server' => 'Ad_Layers_DFP',
			'account_id' => '6355419',
			'path_templates' => array( array(
				'path_template' => '/#account_id#/#ad_unit#/front',
				'page_type' => 'default',
			) ),
			'breakpoints' => array(),
			'ad_units' => array(
				array(
					'code' => 'sidebar',
					'sizes' => array(
						array(
							'width' => 300,
							'height' => 250,
							'default_size' => 'default',
						),
						array(
							'width' => 300,
							'height' => 600,
						),
					)
				),
			),
		);
		update_option( $this->ad_server_parent->option_name, $this->ad_server_settings );

		// Add an ad layer
		$this->ad_layer = $this->factory->post->create( array( 'post_type' => 'ad-layer' ) );
		add_post_meta( $this->ad_layer, 'ad_layer_ad_units', array( array( 'ad_unit' => 'sidebar' ) ) );
		// add_post_meta( $this->ad_layer, 'ad_layer_page_types', array() );

		Ad_Layers::instance()->setup();
		$this->ad_server_parent->setup();
		$this->ad_server = $this->ad_server_parent->get_ad_server();
	}

	protected function update_ad_server_settings( $settings ) {
		update_option( $this->ad_server_parent->option_name, $settings );

		// Reload the ad server class to reload the settings
		$this->ad_server_parent->setup();
	}
}
