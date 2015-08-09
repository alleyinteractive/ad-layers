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
	 * @var array
	 */
	public $display_label = 'DoubleClick for Publishers (DFP)';
	
	/**
	 * Formatting tags
	 *
	 * @access public
	 * @var array
	 */
	public $formatting_tags;

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Define the available formatting tags
		$this->formatting_tags = apply_filters( 'ad_layers_dfp_formatting_tags', $this->set_formatting_tags() );
		
		// Add a help tab
		add_action( 'load-' . Ad_Layers_Post_Type::instance()->get_post_type() . '_page_' . $this->option_name, array( $this, 'add_help_tab' ) );
	}
	
	/**
	 * Returns the ad server display label.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_display_label() {
		return $this->display_label;
	}
	
	/**
	 * Set the available formatting tags
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function set_formatting_tags() {
		// Set the base options
		$formatting_tags = array(
			'#account_id#' => __( 'Your DFP account ID', 'ad-layers' ),
			'#domain#' => __( 'The domain of the current site, taken from home_url', 'ad-layers' ),
			'#ad_unit#' => __( 'The ad unit name', 'ad-layers' ),
			'#post_type#' => __( 'The post type of the current page, if applicable', 'ad-layers' ),
		);
		
		// Add all registered taxonomies as an option since these are commonly used
		$taxonomies = Ad_Layers::instance()->get_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy_name => $taxonomy_label ) {
				$formatting_tags[ '#' . $taxonomy_name . '#' ] = sprintf( __( 'The current term from the %s taxonomy, if applicable. If the taxonomy is hierarchical, each term in the hierarchy above the current term will be added to the path.', 'ad-layers' ), $taxonomy_label );
			}
		}
		
		return $formatting_tags;
	}
	
	/**
	 * Handle ad server header setup code.
	 * Should be implemented by all child classes, if needed.
	 * @access public
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
	 * Returns the ad server settings fields to merge into the ad settings page.
	 * Should be implemented by all child classes.
	 * @access public
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'account_id' => new Fieldmanager_Textfield(
				array(
					'label' => __( 'DFP Account ID', 'ad-layers' ),
				)
			),
			'path_templates' => new Fieldmanager_Group( array(
				'collapsible' => true,
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Path Templates', 'ad-layers' ),
				'add_more_label' => __( 'Add Path Template', 'ad-layers' ),
				'children' => array(
					'path_template' => new Fieldmanager_Textfield(
						array(
							'label' => __( 'Path Template', 'ad-layers' ),
							'description' => __( 'See the Help tab above for formatting tags', 'ad-layers' ),
						)
					),
					'page_type' => new Fieldmanager_Select(
						array(
							'label' => __( 'Page Type', 'ad-layers' ),
							'options' => $this->get_page_types(),
						)
					),
				),
			) ),
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
									'label' => __( 'Code', 'ad-layers' ),
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
	
	/**
	 * Gets available ad slots.
	 * @access public
	 * @return array
	 */
	public function get_ad_slots() {
		$ad_slots = array();
		// TODO
		return $ad_slots;
	}
	
	/**
	 * Generate the code for a single ad slot.
	 * @access public
	 */
	public function get_ad_slot() {
		// TODO
	}
	
	/**
	 * Add tabs to the help menu on the plugin options page.
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'       => 'dfp-setup-help',
			'title'    => __( 'Ad Server Settings Help', 'wp-seo' ),
			'callback' => array( $this, 'formatting_tags_help_tab' ),
		) );
	}
	
	/**
	 * Render the content of the "Formatting Tags" help tab.
	 *
	 * The tab displays a table of each available formatting tab and any
	 * provided description.
	 */
	public function formatting_tags_help_tab() {
		if ( ! empty( $this->formatting_tags ) ) :
			?>
			<aside>
				<h2><?php esc_html_e( 'The following formatting tags are available for the path template:', 'wp-seo' ); ?></h2>
				<dl class="formatting-tags">
					<?php foreach( $this->formatting_tags as $tag => $description ) : ?>
						<div class="formatting-tag-wrapper">
							<dt class="formatting-tag-name"><?php echo esc_html( $tag ); ?></dt>
							<dd class="formatting-tag-description"><?php echo esc_html( $description ); ?></dd>
						</div><!-- .formatting-tag-wrapper -->
					<?php endforeach; ?>
				</dl>
			</aside>
			<?php
		endif;
	}
}

Ad_Layers_DFP::instance();

endif;