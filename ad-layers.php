<?php
/**
 * Reusable extensions for the Ad Layers site.
 *
 * Plugin Name: Ad Layers
 * Plugin URI: https://github.com/alleyinteractive/ad-layers
 * Description: Manages custom ad layers.
 * Author: Bradford Campeau-Laurion, Matthew Boynes, jomurgel, Alley Interactive
 * Author URI: http://www.alleyinteractive.com/
 * Version: 0.2.0
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

// TODO: List.
// - Remove scaffolder once Gutenberg block(s) are build.
// - Remove or rename redundant asset-loader-bridge functions.

if ( ! class_exists( '\Fieldmanager_Field' ) ) {
	add_action( 'admin_notices', __NAMESPACE__ . '\add_admin_notices' );
} else {
	// Actions.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\ad_layers_init' );

	// Include functions for working with assets (primarily JavaScript).
	require_once __DIR__ . '/inc/assets.php';

	// TODO: Resolve conflict with other plugins or remove once other fields/posts were registered.
	require_once __DIR__ . '/inc/asset-loader-bridge.php';

	// Include functions for working with meta.
	require_once __DIR__ . '/inc/meta.php';

	// Include functions.php for registering custom post types, etc.
	require_once __DIR__ . '/functions.php';
}

/**
 * Add admin notice if needed.
 */
function add_admin_notices() {
	$class   = 'notice notice-error';
	$message = __( 'Fieldmanager must be active to use Ad Layers.', 'ad-layers' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

/**
 * Wait until plugins loaded to load plugin assets.
 */
function ad_layers_init() {
	// Implements common ad server functionality for Ad Layers.
	require_once __DIR__ . '/inc/class-ad-server.php';

	if ( is_admin() ) {
		// Manages the Ad Layers settings page and associated functions.
		require_once __DIR__ . '/inc/class-ad-layers-admin.php';

		// Manages the meta box on the post type edit screen for selecting an ad layer.
		require_once __DIR__ . '/inc/class-ad-layers-meta-boxes.php';
	}
}
