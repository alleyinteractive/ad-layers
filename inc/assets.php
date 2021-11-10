<?php
/**
 * Contains functions for working with assets (primarily JavaScript).
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

define( 'AD_LAYERS_ASSET_MAP', read_asset_map( dirname( __DIR__ ) . '/build/assetMap.json' ) );
define( 'AD_LAYERS_ASSET_MODE', AD_LAYERS_ASSET_MAP['mode'] ?? 'production' );

// Register action and filter hooks.
add_action(
	'enqueue_block_editor_assets',
	__NAMESPACE__ . '\action_enqueue_block_editor_assets'
);

/*
 * Unhook CSS and JS script concatenation on VIP Go.
 *
 * Script concatenation interferes with a lot of Gutenberg scripts—both ones
 * that are written deliberately in this plugin, and ones that are written by
 * WordPress itself in response to actions invoked via PHP. For safety's sake,
 * we are unhooking both CSS and JS concatenation here. Script optimizations
 * should be made at the level of the script build process in this plugin and
 * in the theme, rather than relying on VIP's catch-all concatenation script.
 */
remove_action( 'init', 'css_concat_init' );
remove_action( 'init', 'js_concat_init' );

/**
 * A callback for the enqueue_block_editor_assets action hook.
 */
function action_enqueue_block_editor_assets() {
	// TODO: Remove this action if it remains unused.
}

/**
 * Gets asset dependencies from the generated asset manifest.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 *
 * @return array An array of dependencies for this asset.
 */
function get_asset_dependencies( string $asset ) : array {
	// Get the path to the PHP file containing the dependencies.
	$dependency_file = get_asset_path( $asset, true );
	if ( empty( $dependency_file ) ) {
		return [];
	}

	// Ensure the filepath is valid.
	if ( ! file_exists( $dependency_file ) || 0 !== validate_file( $dependency_file ) ) {
		return [];
	}

	// Try to load the dependencies.
	// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	$dependencies = require $dependency_file;
	if ( empty( $dependencies['dependencies'] ) || ! is_array( $dependencies['dependencies'] ) ) {
		return [];
	}

	return $dependencies['dependencies'];
}

/**
 * Get the contentHash for a given asset.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 *
 * @return string The asset's hash.
 */
function get_asset_hash( string $asset ) : string {
	return get_asset_property( $asset, 'hash' )
		?? AD_LAYERS_ASSET_MAP['hash']
		?? '1.0.0';
}

/**
 * Get the URL for a given asset.
 *
 * @param string  $asset Entry point and asset type separated by a '.'.
 * @param boolean $dir   Optional. Whether to return the directory path or the plugin URL path. Defaults to false (returns URL).
 *
 * @return string The asset URL.
 */
function get_asset_path( string $asset, bool $dir = false ) : string {
	// Try to get the relative path.
	$relative_path = get_asset_property( $asset, 'path' );
	if ( empty( $relative_path ) ) {
		return '';
	}

	// Negotiate the base path.
	$base_path = true === $dir
		? dirname( __DIR__ ) . '/build'
		: plugins_url( 'build', __DIR__ );

	return trailingslashit( $base_path ) . $relative_path;
}

/**
 * Get a property for a given asset.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 * @param string $prop The property to get from the entry object.
 *
 * @return string|null The asset property based on entry and type.
 */
function get_asset_property( string $asset, string $prop ) : ?string {
	/*
	 * Appending a '.' ensures the explode() doesn't generate a notice while
	 * allowing the variable names to be more readable via list().
	 */
	list( $entrypoint, $type ) = explode( '.', "$asset." );

	$asset_property = AD_LAYERS_ASSET_MAP[ $entrypoint ][ $type ][ $prop ] ?? null;

	return $asset_property ? $asset_property : null;
}

/**
 * Creates a new Jed instance with specified locale data configuration.
 *
 * @param string $to_handle The script handle to attach the inline script to.
 */
function inline_locale_data( string $to_handle ) {
	// Define locale data for Jed.
	$locale_data = [
		'' => [
			'domain' => 'ad-layers',
			'lang'   => is_admin() ? get_user_locale() : get_locale(),
		],
	];

	// Pass the Jed configuration to the admin to properly register i18n.
	wp_add_inline_script(
		$to_handle,
		'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ", 'ad-layers' );"
	);
}

/**
 * Decode the asset map at the given file path.
 *
 * @param string $path File path.
 *
 * @return array The asset map.
 */
function read_asset_map( string $path ) : array {
	if ( file_exists( $path ) && 0 === validate_file( $path ) ) {
		ob_start();
		include $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		return json_decode( ob_get_clean(), true );
	}

	return [];
}
