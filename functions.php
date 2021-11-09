<?php
/**
 * Ad Layers: Custom post type and taxonomy registration
 *
 * @package Ad_Layers
 */

namespace Ad_Layers;

// Base abstract singleton.
require_once __DIR__ . '/inc/class-ad-layers-singleton.php';

// Base plugin functionality and global scripts.
require_once __DIR__ . '/inc/class-ad-layers.php';

// Implements the custom post type for creating and managing ad layers.
require_once __DIR__ . '/inc/class-ad-layers-post-type.php';

// Manages the shortcodes for available for ad layers.
require_once __DIR__ . '/inc/class-ad-layers-shortcodes.php';

// Manages the widget for inserting an ad unit.
require_once __DIR__ . '/inc/class-ad-layers-widget.php';

// Implements common ad server functionality for Ad Layers.
require_once __DIR__ . '/inc/ad-layers/class-ad-layers-ad-server.php';

// Content types and taxonomies should be included below. In order to scaffold
// them, leave the Begin and End comments in place.
/* Begin Data Structures */

/* End Data Structures */
