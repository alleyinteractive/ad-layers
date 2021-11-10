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

// Content types and taxonomies should be included below. In order to scaffold
// them, leave the Begin and End comments in place.
/* Begin Data Structures */

// Post Type Base Class.
require_once __DIR__ . '/inc/post-types/class-ad-layers-post-type.php';

// Ad Layers Post Type (cpt:ad-layer).
require_once __DIR__ . '/inc/post-types/class-ad-layers-post-type-ad-layer.php';

/* End Data Structures */
