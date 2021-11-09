<?php
/**
 * Reusable extensions for the Ad Layers site.
 *
 * Plugin Name: Ad Layers Extensions
 * Plugin URI: https://github.com/alleyinteractive/ad-layers
 * Description: Extensions to the Ad Layers site.
 * Version: 1.0.0
 * Author: Alley
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

// Include functions for working with assets (primarily JavaScript).
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/asset-loader-bridge.php';

// Include functions for working with meta.
require_once __DIR__ . '/inc/meta.php';

// Include functions.php for registering custom post types, etc.
require_once __DIR__ . '/functions.php';
