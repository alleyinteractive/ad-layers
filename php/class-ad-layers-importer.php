<?php
/**
 * Allows site admins to export and import Ad Layers settings.
 *
 * @package Ad_Layers
 */

/**
 * The main class.
 */
class Ad_Layers_Importer extends Ad_Layers_Singleton {

	/**
	 * The import/export version.
	 */
	const VERSION = 7;

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	private $nonce_name = 'ad-layers-importer-filter';

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Register the custom importer.
		add_action( 'admin_init', array( $this, 'register_importer' ) );

		// Exporting options.
		add_action( 'export_filters', array( $this, 'export_filters' ) );
		add_filter( 'export_args', array( $this, 'export_args' ) );
		add_action( 'export_wp', array( $this, 'export_wp' ) );
	}

	/**
	 * Register our importer.
	 */
	public function register_importer() {
		if ( function_exists( 'register_importer' ) ) {
			register_importer(
				'ad-layers-import',
				esc_html__( 'Ad Layers', 'ad-layers' ),
				esc_html__( 'Import Ad Layers from a JSON file', 'ad-layers' ),
				array( $this, 'dispatch' )
			);
		}
	}

	/**
	 * Add a radio option to export options.
	 */
	public function export_filters() {
		?>
		<p>
			<label>
				<input type="radio" name="content" value="ad-layers" /> <?php esc_html_e( 'Ad Layers', 'ad-layers' ); ?>
			</label>
		</p>
		<?php

		// Nonce.
		wp_nonce_field( $this->nonce_name, $this->nonce_name );
	}

	/**
	 * If the user selected that they want to export Ad Layers, indicate that in the args and
	 * discard anything else.
	 *
	 * @param  array $args The export args being filtered.
	 * @return array The (possibly modified) export args.
	 */
	public function export_args( $args ) {
		// Verify nonce.
		check_admin_referer( $this->nonce_name, $this->nonce_name );

		if ( ! empty( $_GET['content'] ) && 'ad-layers' === $_GET['content'] ) {
			return array( 'ad-layers' => true );
		}

		return $args;
	}

	/**
	 * Export Ad Layers as a JSON file.
	 *
	 * @param array $args The export arguments.
	 */
	public function export_wp( $args ) {
		if ( ! empty( $args['ad-layers'] ) ) {

			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) ) {
				$sitename .= '.';
			}

			if ( function_exists( 'wp_date' ) ) {
				$date = wp_date( 'Y-m-d' );
			} else {
				$date = gmdate( 'Y-m-d' );
			}

			$filename = $sitename . 'ad_layers.' . $date . '.json';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );

			$export_options = $this->get_export_options();

			if ( ! empty( $export_options ) ) {
				$json_pretty_print = defined( 'JSON_PRETTY_PRINT' ) ? JSON_PRETTY_PRINT : null;

				echo wp_json_encode(
					array(
						'version'          => self::VERSION,
						'layer_priority'   => get_option( 'ad_layers' ),
						'custom_variables' => get_option( 'ad_layers_custom_variables' ),
						'layers'           => array(),
					),
					$json_pretty_print
				);
			}

			// Exit.
			exit;
		}
	}

	/**
	 * Registered callback function for the Options Importer
	 */
	public function dispatch() {
		echo 'Test';
	}
}

// Create the instance.
Ad_Layers_Importer::instance();
