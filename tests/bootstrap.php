<?php
/**
 * Ad_Layers Tests: Bootstrap File
 *
 * @package Ad_Layers
 * @subpackage Tests
 */

const WP_TESTS_PHPUNIT_POLYFILLS_PATH = __DIR__ . '/../vendor/yoast/phpunit-polyfills';

// Load Core's test suite.
$ad_layers_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $ad_layers_tests_dir ) {
	$ad_layers_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $ad_layers_tests_dir . '/includes/functions.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

/**
 * Setup our environment.
 */
function ad_layers_manually_load_environment() {
	/*
	 * Tests won't start until the uploads directory is scanned, so use the
	 * lightweight directory from the test install.
	 *
	 * @see https://core.trac.wordpress.org/changeset/29120.
	 */
	add_filter(
		'pre_option_upload_path',
		function () {
			return ABSPATH . 'wp-content/uploads';
		}
	);

	// Load this plugin.
	require_once dirname( __DIR__ ) . '/ad-layers.php';
}
tests_add_filter( 'muplugins_loaded', 'ad_layers_manually_load_environment' );

// Disable the emoji detection script, because it throws unnecessary errors.
tests_add_filter(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	}
);

// Include core's bootstrap.
require $ad_layers_tests_dir . '/includes/bootstrap.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

// Load a custom UnitTestCase for this plugin
require dirname( __FILE__ ) . '/class-ad-layers-testcase.php';

/**
 * Is the current version of WordPress at least ... ?
 *
 * @param  float $min_version Minimum version required, e.g. 3.9.
 * @return bool True if it is, false if it isn't.
 */
function _fm_phpunit_is_wp_at_least( $min_version ) {
	global $wp_version;
	return floatval( $wp_version ) >= $min_version;
}
