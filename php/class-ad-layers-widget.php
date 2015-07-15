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
	 * @return void
	 */
	public function __construct() {
		// Construct the widget
		parent::__construct(
			'ad_layers_ad_widget',
			__( 'Ad Layers Ad Widget', 'ad-layers' ),
			array( 
				'description' => esc_html__( 'Adds the specified ad slot to any sidebar.', 'ad-layers' ), 
			)
		);
	}
	
	/**
	 * Handles front end display of the widget
	 *
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		// Get the specified ad slot
		$ad_slot = ( isset( $instance['ad_slot'] ) ) ? $instance['ad_slot'] : '';
		
		// Ensure there is a valid ad slot to display before continuing.
		// Although the slot may exist on the site, it may not be available on this particular page.
		// In that instance, fail gracefully and just hide the widget to avoid extra whitespace in the sidebar.
		$ad_server = Ad_Layers_Ad_Server::instance();
		$ad_slot_html = $ad_server->ad_slot( $ad_slot );
		if ( empty( $ad_slot_html ) ) {
			return;
		}
		
		// Display the ad slot
		echo wp_kses_post( $args['before_widget'] . $ad_slot_html . $args['after_widget'] );
	}

	/**
	 * Outputs the admin form for the widget
	 *
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	public function form( $instance ) {
		// Check if the ad slot is set for this widget.
		// If not, set the value to be empty.
		$ad_slot = ( isset( $instance['ad_slot'] ) ) ? $instance['ad_slot'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'ad_slot' ) ?>"><?php esc_html_e( 'Ad Slot', 'ad-layers' ) ?></label>
			<br />
			<?php echo $this->ad_slot_select_field( $ad_slot ) ?>
		</p>
		<?php
		
		// Call the parent function to add global fields
		return parent::form( $instance );
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
		$instance['ad_slot'] = strval( $new_instance['ad_slot'] );
		
		// If the ad slot is empty, return false and do not save
		if ( empty( $instance['ad_slot'] ) ) {
			return false;
		}
		
		// Call the parent function for sidebar validation
		return parent::validate( $instance, $new_instance, $old_instance );
	}
	
	/**
	 * Creates a select field to select the ad slot
	 *
	 * @access protected
	 * @param string $selected_value The currently selected value
	 * @return string HTML for the field
	 */
	protected function ad_slot_select_field( $selected_value ) {
		// Get all ad slots in the system.
		// If none exist, display a message. 
		// This will also prevent the widget from being saved due to the validation rules in update().
		$no_slots = '<p>' . esc_html__( 'No ad slots are currently available.', 'ad-layers' ) . '</p>';
		
		$ad_server = Ad_Layers_Ad_Server::instance();
		$ad_slots = $ad_server->ad_slots();

		// Build the option list.
		$options = '';
		foreach ( $ad_slots as $ad_slot ) {
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $ad_slot ),
				selected( $selected_value, $ad_slot, false ),
				esc_html( $ad_slot )
			);
		}
		
		// Return the complete element.
		return sprintf(
			'<select name="%s" id="%s">%s</select>',
			$this->get_field_name( 'ad_slot' ),
			$this->get_field_id( 'ad_slot' ),
			$options
		);
	}
}

// Register the widget
add_action( 'widgets_init', function() {
	register_widget( 'Ad_Layers_Widget' );
} );

endif;