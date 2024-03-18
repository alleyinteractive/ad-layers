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
	const VERSION = 1;

	/**
	 * The attachment ID.
	 *
	 * @access private
	 *
	 * @var int
	 */
	private $file_id;

	/**
	 * The transient key template used to store the options after upload.
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $transient_key = 'ad-layers-import-%d';

	/**
	 * Stores the import data from the uploaded file.
	 *
	 * @access public
	 *
	 * @var array
	 */
	public $import_data;

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

			$json_pretty_print = defined( 'JSON_PRETTY_PRINT' ) ? JSON_PRETTY_PRINT : null;

			echo wp_json_encode(
				array(
					'version'                      => self::VERSION,
					'ad_layers'                    => get_option( 'ad_layers' ),
					'ad_layers_custom_variables'   => get_option( 'ad_layers_custom_variables' ),
					'ad_layers_ad_server_settings' => get_option( 'ad_layers_ad_server_settings' ),
					'layers'                       => $this->export_ad_layers(),
				),
				$json_pretty_print
			);

			// Exit.
			exit;
		}
	}

	/**
	 * Registered callback function for the importer
	 */
	public function dispatch() {
		$this->header();

		if ( empty( $_GET['step'] ) ) {
			$_GET['step'] = 0;
		}

		switch ( intval( $_GET['step'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			case 0:
				$this->greet();
				break;
			case 1:
				check_admin_referer( 'import-upload' );

				if ( $this->handle_upload() ) {
					$this->pre_import();
				} else {
					echo '<p><a href="' . esc_url( admin_url( 'admin.php?import=ad-layers-import' ) ) . '">' . esc_html__( 'Return to File Upload', 'ad-layers' ) . '</a></p>';
				}

				break;
			case 2:
				check_admin_referer( 'import-wordpress-ad-layers' );

				$this->file_id     = ! empty( $_POST['import_id'] ) ? intval( $_POST['import_id'] ) : 0;
				$this->import_data = get_transient( $this->transient_key() );

				if ( false !== $this->import_data ) {
					$this->import();
				}

				break;
		}

		$this->footer();
	}

	/**
	 * Exports Ad Layer posts and their relevant post meta.
	 *
	 * @return array An array of ad layers.
	 */
	public function export_ad_layers() {
		$ad_layers = array();

		$layer_priority = apply_filters( 'ad_layers', get_option( 'ad_layers' ) );

		// No layers found.
		if ( empty( $layer_priority ) ) {
			return $ad_layers;
		}

		// Loop through layers and populate their export data.
		foreach ( $layer_priority as $layer ) {

			$layer_object = get_post( $layer['post_id'] );

			// Skip if this layer does not have a valid post type.
			if ( ! in_array( $layer_object->post_status, array( 'publish', 'draft', 'future', 'pending' ), true ) ) {
				continue;
			}

			// Get the post meta data.
			$meta_data = array();

			foreach ( $this->post_meta_keys() as $key ) {
				$meta_data[ $key ] = get_post_meta( $layer['post_id'], $key, true );
			}

			$ad_layers[] = array(
				'post_object' => $layer_object,
				'meta'        => $meta_data,
			);
		}

		return $ad_layers;
	}

	/**
	 * Post meta keys to be exported/imported.
	 *
	 * @return array An array of post meta keys that can be imported/exported.
	 */
	public function post_meta_keys() {
		return array(
			'ad_layer_ad_units',
			'ad_layer_page_types',
			'ad_layer_taxonomies',
			'ad_layer_post_types',
			'ad_layer_custom_targeting',
		);
	}

	/**
	 * Provide the user with a choice of which options to import from the JSON
	 * file, pre-selecting known options.
	 */
	private function pre_import() {
		?>
		<form action="<?php echo esc_url( admin_url( 'admin.php?import=ad-layers-import&amp;step=2' ) ); ?>" method="post">
			<?php wp_nonce_field( 'import-wordpress-ad-layers' ); ?>
			<input type="hidden" name="import_id" value="<?php echo absint( $this->file_id ); ?>" />

			<p>
				<input type="checkbox" value="1" name="settings[override]" id="override_current" checked="checked" />
				<label for="override_current"><?php esc_html_e( 'Override existing custom variables and Ad Server settings', 'ad-layers' ); ?></label>
			</p>
			<p class="description"><?php esc_html_e( 'If you uncheck this box, custom variables and ad server settings will be skipped if they currently exist.', 'ad-layers' ); ?></p>

			<h3><?php esc_html_e( 'IMPORTANT', 'ad-layers' ); ?></h3>
			<p><?php esc_html_e( 'Note that the import process will need to remove all existing Ad Layers before importing the Ad Layers specified in the import file. There is no undoing this import process. We suggest only using this feature to import into a site with no prior Ad Layers configured.', 'ad-layers' ); ?></p>

			<?php submit_button( esc_html__( 'Import Ad Layers', 'ad-layers' ) ); ?>
		</form>
		<?php
	}

	/**
	 * The main controller for the actual import stage.
	 */
	private function import() {
		// Nonce check.
		check_admin_referer( 'import-wordpress-ad-layers' );

		if ( $this->run_data_check() ) {
			$override = ( ! empty( $_POST['settings']['override'] ) && '1' === $_POST['settings']['override'] );

			$valid_options = array(
				'ad_layers_ad_server_settings',
				'ad_layers_custom_variables',
			);

			// Import all options.
			foreach ( $valid_options as $valid_option ) {
				$this->import_option( $valid_option, $override );
			}

			// Delete all existing ad layers.
			$this->delete_all_ad_layers();

			// Ad layers.
			$imported_ad_layers = $this->import_all_ad_layers( $override );

			// Clear out layer priority option and set from imported data.
			delete_option( 'ad_layers' );

			$layer_priority = array();

			if ( ! empty( $this->import_data['ad_layers'] ) && is_array( $this->import_data['ad_layers'] ) ) {
				foreach ( $this->import_data['ad_layers'] as $layer ) {
					// No title.
					if ( empty( $layer['title'] ) ) {
						continue;
					}

					// Layer was imported so use its new post ID.
					if ( ! empty( $imported_ad_layers[ $layer['title'] ] ) ) {
						$layer_priority[] = array(
							'post_id' => absint( $imported_ad_layers[ $layer['title'] ] ),
							'title'   => $layer['title'],
						);
					}
				}
			}

			// Set the priority.
			if ( ! empty( $layer_priority ) ) {
				update_option( 'ad_layers', $layer_priority );
			}

			$this->clean_up();
			echo '<p>' . esc_html__( 'All done. That was easy.', 'ad-layers' ) . ' <a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Have fun!', 'ad-layers' ) . '</a></p>';
		}
	}

	/**
	 * Imports a single option from the imported data.
	 *
	 * @param  string  $name     The option name.
	 * @param  boolean $override Whether or not to override existing settings.
	 * @return bool|WP_Error     True if sucessful, otherwise a WP_Error object.
	 */
	public function import_option( $name, $override = false ) {
		// Used to check if a value is set in the DB.
		$hash = '048f8580e913efe41ca7d402cc51e848';

		if ( ! $override ) {
			// We're going to use a random hash as our default, to know if something is set or not.
			$old_value = get_option( $name, $hash );

			// Only import the setting if it's not present.
			if ( $old_value !== $hash ) {
				/* translators: 1. option name */
				return $this->error_message( sprintf( __( 'Skipped option `%s` because it currently exists.', 'ad-layers' ), $name ) );
			}
		}

		if ( isset( $this->import_data[ $name ] ) ) {
			update_option( $name, $this->import_data[ $name ] );
		} else {
			/* translators: 1. option name */
			return $this->error_message( sprintf( __( 'Could not find option `%s` in export file.', 'ad-layers' ), $name ) );
		}

		return true;
	}

	/**
	 * Imports all ad layers.
	 *
	 * @return array $imported_ad_layers All of the imported Ad layers.
	 */
	public function import_all_ad_layers() {
		$imported_ad_layers = array();

		// Bail if there are no layers to import.
		if ( empty( $this->import_data['layers'] ) || ! is_array( $this->import_data['layers'] ) ) {
			$this->error_message( __( 'No layers to import.', 'ad-layers' ) );
		}

		foreach ( $this->import_data['layers'] as $layer ) {
			$layer_id = 0;

			if ( empty( $layer ) || empty( $layer['post_object']['ID'] ) ) {
				$this->error_message( __( 'Skipping: Layer does not have proper data.', 'ad-layers' ) );
				continue;
			}

			// Insert the Ad Layer.
			$layer_id = wp_insert_post( $this->get_postarr_from_data( $layer['post_object'] ) );

			// Skip post meta if layer was not created properly.
			if ( empty( $layer_id ) ) {
				/* translators: 1. Ad Layer name */
				$this->error_message( sprintf( __( 'Skipping: Layer %s could not be created', 'ad-layers' ), $layer['post_object']['post_title'] ) );
				continue;
			}

			$layer_object = get_post( $layer_id );

			// Add layer to imported layers array.
			$imported_ad_layers[ $layer_object->post_title ] = $layer_id;

			// Update post meta.
			foreach ( $layer['meta'] as $key => $value ) {
				// Skip meta key if not in list.
				if ( ! in_array( $key, $this->post_meta_keys(), true ) ) {
					continue;
				}

				update_post_meta( $layer_id, $key, $value );
			}

			// Add custom post meta flagging this layer being imported.
			update_post_meta( $layer_id, 'ad_layers_imported', time() );
		}

		return $imported_ad_layers;
	}

	/**
	 * Deletes all Ad Layers.
	 */
	public function delete_all_ad_layers() {
		$ad_layer_ids = $this->get_all_ad_layers();

		// No ad layers found.
		if ( empty( $ad_layer_ids ) ) {
			return;
		}

		foreach ( $ad_layer_ids as $ad_layer_id ) {
			wp_delete_post( $ad_layer_id, true );
		}
	}

	/**
	 * Gets all Ad Layers.
	 *
	 * @return array All ad layer ids.
	 */
	public function get_all_ad_layers() {
		global $wpdb;

		$ad_layer_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'ad-layer' LIMIT 500" );

		// No ad layers found.
		if ( empty( $ad_layer_ids ) ) {
			return array();
		}

		return (array) $ad_layer_ids;
	}

	/**
	 * Get post array from post object.
	 *
	 * @param  array $data The post object data.
	 * @return array The post attributes array.
	 */
	public function get_postarr_from_data( $data ) {
		if ( empty( $data ) ) {
			return array();
		}

		return array(
			'post_type'   => $data['post_type'],
			'post_title'  => $data['post_title'],
			'post_status' => $data['post_status'],
		);
	}

	/**
	 * Handles the JSON upload and initial parsing of the file to prepare for
	 * displaying author import options
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	private function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				esc_html( $file['error'] )
			);
		}

		if ( ! isset( $file['file'], $file['id'] ) ) {
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				esc_html__( 'The file did not upload properly. Please try again.', 'ad-layers' )
			);
		}

		$this->file_id = intval( $file['id'] );

		if ( ! file_exists( $file['file'] ) ) {
			wp_import_cleanup( $this->file_id );
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				/* translators: 1. filename */
				sprintf( esc_html__( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'ad-layers' ), esc_html( $file['file'] ) )
			);
		}

		if ( ! is_file( $file['file'] ) ) {
			wp_import_cleanup( $this->file_id );
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				esc_html__( 'The path is not a file, please try again.', 'ad-layers' )
			);
		}

		// Get the file source URL.
		$file_url = wp_get_attachment_url( $this->file_id );

		// Unable to get the file URL.
		if ( empty( $file_url ) ) {
			wp_import_cleanup( $this->file_id );
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				esc_html__( 'Unable to fetch the file URL.', 'ad-layers' )
			);
		}

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$file_contents = vip_safe_wp_remote_get( $file_url );
		} else {
			$file_contents = wp_remote_get( $file_url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		}

		// Invalid file or file contents.
		if ( is_wp_error( $file_contents ) || empty( $file_contents['body'] ) ) {
			wp_import_cleanup( $this->file_id );
			return $this->error_message(
				esc_html__( 'Sorry, there has been an error.', 'ad-layers' ),
				esc_html__( 'Unable to fetch the file contents.', 'ad-layers' )
			);
		}

		$this->import_data = json_decode( $file_contents['body'], true );

		set_transient( $this->transient_key(), $this->import_data, DAY_IN_SECONDS );
		wp_import_cleanup( $this->file_id );

		return $this->run_data_check();
	}

	/**
	 * Start the options import page HTML.
	 */
	private function header() {
		echo '<div class="wrap">';
		echo '<h2>' . esc_html__( 'Import Ad Layer Settings', 'ad-layers' ) . '</h2>';
	}

	/**
	 * End the options import page HTML.
	 */
	private function footer() {
		echo '</div>';
	}

	/**
	 * Display introductory text and file upload form.
	 */
	private function greet() {
		echo '<div class="narrow">';
		echo '<p>' . esc_html__( 'Upload your WordPress options JSON file and we&#8217;ll import the desired data. You&#8217;ll have a chance to review the data prior to import.', 'ad-layers' ) . '</p>';
		echo '<p>' . esc_html__( 'Choose a JSON (.json) file to upload, then click Upload file and import.', 'ad-layers' ) . '</p>';
		wp_import_upload_form( 'admin.php?import=ad-layers-import&amp;step=1' );
		echo '</div>';
	}

	/**
	 * Run a series of checks to ensure we're working with a valid JSON export.
	 *
	 * @return bool true if the file and data appear valid, false otherwise.
	 */
	private function run_data_check() {
		if ( empty( $this->import_data['version'] ) ) {
			$this->clean_up();
			return $this->error_message( esc_html__( 'Sorry, there has been an error. This file may not contain data or is corrupt.', 'ad-layers' ) );
		}

		if ( $this->import_data['version'] > self::VERSION ) {
			$this->clean_up();
			/* translators: 1. file version */
			return $this->error_message( sprintf( esc_html__( 'This JSON file (version %s) is from a newer version of this plugin and may not be compatible. Please update this plugin.', 'ad-layers' ), intval( $this->import_data['version'] ) ) );
		}

		if (
			empty( $this->import_data['ad_layers'] )
			&& empty( $this->import_data['ad_layers_custom_variables'] )
			&& empty( $this->import_data['ad_layers_ad_server_settings'] )
			&& empty( $this->import_data['layers'] )
		) {
			$this->clean_up();
			return $this->error_message( esc_html__( 'Sorry, there has been an error. This file appears valid, but does not seem to have any options.', 'ad-layers' ) );
		}

		return true;
	}

	/**
	 * Gets the transient key name.
	 *
	 * @return string The transient key name.
	 */
	private function transient_key() {
		return sprintf( $this->transient_key, $this->file_id );
	}

	/**
	 * Deletes the transient.
	 */
	private function clean_up() {
		delete_transient( $this->transient_key() );
	}

	/**
	 * A helper method to keep DRY with our error messages. Note that the error messages
	 * must be escaped prior to being passed to this method (this allows us to send HTML).
	 *
	 * @param  string $message The main message to output.
	 * @param  string $details Optional. Additional details.
	 * @return bool false
	 */
	private function error_message( $message, $details = '' ) {
		echo '<div class="error"><p><strong>' . esc_html( $message ) . '</strong>';

		if ( ! empty( $details ) ) {
			echo '<br />' . wp_kses_post( $details );
		}

		echo '</p></div>';

		return false;
	}
}

// Create the instance.
Ad_Layers_Importer::instance();