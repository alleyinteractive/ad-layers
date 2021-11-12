<?php
/**
 * A bridge between the namespaced function for getting assets and the
 * scaffolded block files. Can be removed once namespacing is fully
 * supported by the scaffolder in a way that won't break integrations
 * with existing sites.
 *
 * This file MUST NOT have a namespace defined in order to work!
 *
 * @package Ad_Layers
 */

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound */

if ( ! function_exists( 'get_asset_dependencies' ) ) {
	/**
	 * A helper function to bridge the gap between the non-namespaced scaffolded
	 * block files and the namespaced block loader in the plugin. Calls
	 * get_asset_dependencies.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 *
	 * @return array The asset dependencies.
	 */
	function get_asset_dependencies( $asset ) {
		return Ad_Layers\get_asset_dependencies( $asset );
	}
}

if ( ! function_exists( 'get_asset_hash' ) ) {
	/**
	 * A helper function to bridge the gap between the non-namespaced scaffolded
	 * block files and the namespaced block loader in the plugin. Calls
	 * get_asset_hash.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 *
	 * @return string The asset version hash.
	 */
	function get_asset_hash( $asset ) {
		return Ad_Layers\get_asset_hash( $asset );
	}
}

if ( ! function_exists( 'get_asset_path' ) ) {
	/**
	 * A helper function to bridge the gap between the non-namespaced scaffolded
	 * block files and the namespaced block loader in the plugin. Calls
	 * get_asset_hash.
	 *
	 * @param string  $asset Entry point and asset type separated by a '.'.
	 * @param boolean $dir   Optional. Whether to return the directory path or the plugin URL path. Defaults to false (returns URL).
	 *
	 * @return string The asset URL.
	 */
	function get_asset_path( $asset, $dir = false ) {
		return Ad_Layers\get_asset_path( $asset, $dir );
	}
}

if ( ! function_exists( 'inline_locale_data' ) ) {
	/**
	 * A helper function to bridge the gap between the non-namespaced scaffolded
	 * block files and the namespaced inline locale function in the plugin.
	 *
	 * @param string $to_handle The script handle to attach the inline script to.
	 */
	function inline_locale_data( $to_handle ) {
		Ad_Layers\inline_locale_data( $to_handle );
	}
}
