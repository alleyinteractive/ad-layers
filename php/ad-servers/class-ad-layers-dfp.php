<?php

/**
 * Ad Layers DFP
 *
 * Implements the DFP Ad Server for Ad Layers.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_DFP' ) ) :

class Ad_Layers_DFP extends Ad_Layers_Ad_Server {
	
	/**
	 * All available ad servers
	 *
	 * @access public
	 * @static
	 * @var array
	 */
	public static $display_name = 'DoubleClick for Publishers (DFP)';


	/**
	 * Setup the singleton.
	 */
	public function setup() {
		
	}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes, if needed.
	 * @access public
	 * @static
	 * @return array
	 */
	public function header_setup() {
		?>
		<script type='text/javascript'>
		var dfp_ad_slots = [];
		var googletag = googletag || {};
		googletag.cmd = googletag.cmd || [];
		(function() {
		var gads = document.createElement('script');
		gads.async = true;
		gads.type = 'text/javascript';
		var useSSL = 'https:' == document.location.protocol;
		gads.src = (useSSL ? 'https:' : 'http:') + 
		'//www.googletagservices.com/tag/js/gpt.js';
		var node = document.getElementsByTagName('script')[0];
		node.parentNode.insertBefore(gads, node);
		})();
		</script>
		<?php do_action( 'ad_layers_dfp_after_setup' ); ?>
		<script type="text/javascript">
		googletag.cmd.push(function() {
			<?php
				// Add mapping JS for responsive ad serving
				$this->mapping_js();
				
				// Add the ad slots
				$this->ad_slot_js();
				
				// Add custom targeting
				$this->targeting_js();
			?>
			
			<?php do_action( 'ad_layers_dfp_custom_targeting' ); ?>
			
			googletag.pubads().enableAsyncRendering();
			googletag.pubads().collapseEmptyDivs();
			googletag.enableServices();
		});
		<?php do_action( 'ad_layers_dfp_after_ad_slots' ); ?>
		</script>
		<?php
	}
	
	/**
	 * Returns the ad server display label.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_display_label() {
		return self::$display_label;
	}
	
	/**
	 * Returns the ad server settings fields to merge into the ad settings page.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'path_template' => new Fieldmanager_Textfield(
				array(
					'label' => __( 'Path Template', 'ad-layers' ),
				)
			),
			'ad_setup' => new Fieldmanager_Group( array(
				'collapsible' => true,
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Breakpoint', 'ad-layers' ),
				'label_macro' => array( __( 'Breakpoint: %s', 'ad-layers' ), 'name' ),
				'add_more_label' => __( 'Add Breakpoint', 'ad-layers' ),
				'children' => array(
					'key' => new Fieldmanager_Hidden(),
					'title' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Title', 'ad-layers' ),
						)
					),
					'min_width' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Minimum Width', 'ad-layers' ),
						)
					),
					'min_height' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Minimum Height', 'ad-layers' ),
						)
					),
					'ad_units' => new Fieldmanager_Group( array(
						'limit' => 0,
						'extra_elements' => 0,
						'label' => __( 'Ad Units', 'ad-layers' ),
						'label_macro' => array( __( 'Ad Unit: %s', 'ad-layers' ), 'name' ),
						'add_more_label' => __( 'Add Ad Unit', 'ad-layers' ),
						'children' => array(
							'code' => new Fieldmanager_Textfield(
								array(
									'label' => __( 'code', 'ad-layers' ),
								)
							),
							'sizes' => new Fieldmanager_Group( array(
								'limit' => 0,
								'extra_elements' => 0,
								'one_label_per_item' => false,
								'label' => __( 'Sizes', 'ad-layers' ),
								'add_more_label' => __( 'Add Size', 'ad-layers' ),
								'children' => array(
									'width' => new Fieldmanager_Textfield(
										array(
											'label' => __( 'Width', 'ad-layers' ),
										)
									),
									'height' => new Fieldmanager_Textfield(
										array(
											'label' => __( 'Height', 'ad-layers' ),
										)
									),
								)
							) )
						)
					) )
				)
			) )
		);
	}
	
	/**
	 * Creates the mapping Javascript required for responsive ad serving.
	 * @access private
	 */
	private function mapping_js() {}
	
	/**
	 * Creates the ad slot Javascript.
	 * @access private
	 */
	private function ad_slot_js() {}
	
	/**
	 * Creates the DFP targeting Javascript.
	 * @access private
	 */
	private function targeting_js() {
		/*if ( ! empty( $targeting_js ) ) {
			echo 'googletag.pubads()' . $targeting_js . ";\n";
		}*/
	}
}

Ad_Layers_DFP::instance();

endif;