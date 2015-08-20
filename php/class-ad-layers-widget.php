<?php

/**
 * Ad Layers Widget
 *
 * Manages the widget for inserting an ad unit.
 *
 * @author Bradford Campeau-Laurion
 */

if ( ! class_exists( 'Ad_Layers_Widget' ) ) :

class Ad_Layers_Widget extends WP_Widget {
	/**
	 * Constructor. Creates the widget using the parent class constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		// Construct the widget
		parent::__construct(
			'ad_layers_ad_widget',
			__( 'Ad Layers Ad Widget', 'ad-layers' ),
			array( 
				'description' => __( 'Adds the specified ad unit to any sidebar.', 'ad-layers' ), 
			)
		);
	}
	
	/**
	 * Handles front end display of the widget.
	 *
	 * @access public
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// Get the specified ad unit
		$ad_unit = ( isset( $instance['ad_unit'] ) ) ? $instance['ad_unit'] : '';
		
		// Ensure there is a valid ad unit to display before continuing.
		// Although the unit may exist on the site, it may not be available on this particular page.
		// In that instance, fail gracefully and just hide the widget to avoid extra whitespace in the sidebar.
		$ad_unit_html = Ad_Layers_Ad_Server::instance()->get_ad_unit( $ad_unit, false );
		if ( empty( $ad_unit_html ) ) {
			return;
		}
		
		// Display the ad unit
		echo wp_kses_post( $args['before_widget'] . $ad_unit_html . $args['after_widget'] );
	}

	/**
	 * Outputs the admin form for the widget.
	 *
	 * @access public
	 * @param array $instance
	 */
	public function form( $instance ) {
		// Check if the ad unit is set for this widget.
		// If not, set the value to be empty.
		$ad_unit = ( isset( $instance['ad_unit'] ) ) ? $instance['ad_unit'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'ad_unit' ) ?>"><?php esc_html_e( 'Ad Unit', 'ad-layers' ) ?></label>
			<br />
			<?php echo $this->ad_unit_select_field( $ad_unit ) ?>
			<br /><i><?php esc_html_e( 'Select an ad unit to display in this widget. The widget will be automatically hidden if the unit is not present in the current ad layer.', 'ad-layers' ); ?></i>
		</p>
		<?php
	}

	/**
	 * Sanitizes the widget options to be saved
	 *
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|boolean
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['ad_unit'] = strval( $new_instance['ad_unit'] );
		
		// If the ad unit is empty, return false and do not save
		if ( empty( $instance['ad_unit'] ) ) {
			return false;
		}
		
		return $instance;
	}
	
	/**
	 * Creates a select field to select the ad unit.
	 *
	 * @access protected
	 * @param string $selected_value The currently selected value
	 * @return string HTML for the field
	 */
	protected function ad_unit_select_field( $selected_value ) {
		// Get all ad units in the system.
		// If none exist, display a message. 
		// This will also prevent the widget from being saved due to the validation rules in update().
		$no_units = '<p>' . esc_html__( 'No ad units are currently available.', 'ad-layers' ) . '</p>';
		
		$ad_units = Ad_Layers_Ad_Server::instance()->get_ad_units();

		// Build the option list.
		$options = '';
		foreach ( $ad_units as $ad_unit ) {
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $ad_unit ),
				selected( $selected_value, $ad_unit, false ),
				esc_html( $ad_unit )
			);
		}
		
		// Return the complete element.
		return sprintf(
			'<select name="%s" id="%s">%s</select>',
			$this->get_field_name( 'ad_unit' ),
			$this->get_field_id( 'ad_unit' ),
			$options
		);
	}
}

// Register the widget
add_action( 'widgets_init', function() {
	register_widget( 'Ad_Layers_Widget' );
} );

endif;