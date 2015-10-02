<?php
/**
 * Ad Layers Base Plugin File.
 *
 * @package AdLayers
 * @version 0.0.1
 */

/*
	Plugin Name: Ad Layers
	Plugin URI: https://github.com/alleyinteractive/ad-layers
	Description: Manages custom ad layers.
	Author: Bradford Campeau-Laurion, Matthew Boynes, Alley Interactive
	Version: 0.0.1
	Author URI: http://www.alleyinteractive.com/
*/

/*
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Version number.
 *
 * @var string
 */
define( 'AD_LAYERS_VERSION', '0.0.2' );

/**
 * Filesystem path to Ad Layers.
 *
 * @var string
 */
define( 'AD_LAYERS_BASE_DIR', dirname( __FILE__ ) );

/**
 * Asset path to Ad Layers.
 *
 * @var string
 */
define( 'AD_LAYERS_ASSETS_DIR', plugin_dir_url( __FILE__ ) );

/**
 * Default version number for static assets.
 *
 * @var int
 */
define( 'AD_LAYERS_GLOBAL_ASSET_VERSION', '0.0.1' );

/**
 * Load the plugin after the theme loads, to allow for customizations.
 */
function ad_layers_load_files() {
	if ( ! defined( 'AD_LAYERS_OPTION_NAME' ) ) {
		/**
		 * Option name for ad layers settings.
		 *
		 * @var string
		 */
		define( 'AD_LAYERS_OPTION_NAME', 'ad_layers' );
	}

	/**
	 * Base singleton class for Ad Layers classes.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-singleton.php' );

	/**
	 * Base plugin functionality and global scripts.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers.php' );

	/**
	 * Custom post type.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-post-type.php' );

	/**
	 * Ad widget.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-widget.php' );

	/**
	 * Ad shortcode.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-shortcodes.php' );

	/**
	 * Ad servers.
	 */
	require_once( AD_LAYERS_BASE_DIR . '/php/ad-servers/class-ad-layers-ad-server.php' );

	/**
	 * Admin dashboard features.
	 */
	if ( is_admin() ) {
		require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-meta-boxes.php' );
		require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-admin.php' );
	}
}
add_action( 'after_setup_theme', 'ad_layers_load_files' );
