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
	Author: Bradford Campeau-Laurion, Alley Interactive
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
define( 'AD_LAYERS_VERSION', '0.0.1' );

/**
 * Filesystem path to Ad Layers.
 *
 * @var string
 */
define( 'AD_LAYERS_BASE_DIR', dirname( __FILE__ ) );

/**
 * Default version number for static assets.
 *
 * @var int
 */
define( 'AD_LAYERS_GLOBAL_ASSET_VERSION', '0.0.1' );

/**
 * Option name for ad layers settings.
 *
 * @var string
 */
define( 'AD_LAYERS_OPTION_NAME', 'ad_layers_settings' );

/**
 * Base singleton class for Ad Layers classes.
 */
require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-singleton.php' );

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
require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-shortcode.php' );

/**
 * Admin dashboard features.
 */
if ( is_admin() ) {
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-meta-boxes.php' );
	require_once( AD_LAYERS_BASE_DIR . '/php/class-ad-layers-admin.php' );
}

/**
 * Instantiate the Ad Layers base class to handle required plugin setup
 */
if ( ! class_exists( 'Ad_Layers' ) ) :

class Ad_Layers extends Ad_Layers_Singleton {
	
	/**
	 * Built-in ad server support
	 *
	 * @var Ad_Layers
	 */
	private $ad_servers = array(
		'DFP' => AD_LAYERS_BASE_DIR . '/php/ad-servers/class-dfp.php',
	);
	
	/**
	 * Current ad layers settings
	 *
	 * @var string
	 */
	private $settings = 'ad_layers_options';
	
	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Load current settings
		$this->settings = get_option( AD_LAYERS_OPTION_NAME );
	
		// Allow additional ad servers to be loaded via filter within a theme
		$ad_servers = apply_filters( 'ad_layers_ad_servers', $this->ad_servers );
		
		if ( ! empty( $ad_servers ) && is_array( $ad_servers ) ) {
			foreach ( $ad_servers as $ad_server ) {
				if ( file_exists( $ad_server ) ) {
					require_once( $ad_server );
				}
			}
		}
		
		// Load the base Javascript library early
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5 );
	}
	
	/**
	 * Load scripts.
	 */
	public function enqueue_scripts() {
		// Load the base Javascript library
		wp_enqueue_script( 'ad-layers-js', AD_LAYERS_BASE_DIR . '/js/ad-layers.js', array( 'jquery' ), AD_LAYERS_GLOBAL_ASSET_VERSION, false );
		
		// If set, localize with the active ad server
		if ( ! empty( $this->settings['ad_server'] ) ) {
			wp_localize_script( 'ad-layers-js', 'ad-layers', array(
				'ad_server' => $this->settings['ad_server'],
			) );
		}
		
		// Load the CSS. Mostly used in debug mode.
		wp_enqueue_style( 'ad-layers-css', AD_LAYERS_BASE_DIR . '/css/ad-layers.css', array(), AD_LAYERS_GLOBAL_ASSET_VERSION );
	}
}

Ad_Layers::instance();

endif;