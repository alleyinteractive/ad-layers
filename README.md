# Ad Layers

The Ad Layers plugin ("ad-layers") will provide an advanced mechanism for ad ops teams and other digital producers to customize the layout of advertisements in WordPress templates with minimal developer intervention.

# Managing Ad Layers

The plugin adds an Ad Layers custom post type to the WordPress dashboard for managing available add layers. There are multiple options under this menu.

## Ad Layers

Shows a list of all available ad layers. This uses a standard WordPress List Table.

## Add New Ad Layer

This allows you to create a new ad layer. This is set up as a WordPress custom post type with the following fields:

### Title
The standard WordPress title field acts as a label to reference this ad layer.

### Ad Slots
Click "Add an ad unit" to add one or more ad slots that are part of this ad layer.

### Page Types
Click "Add a page type" to add one or more pages on which this layer can appear. 

### Taxonomies
Click "Add a taxonomy" to add one or more taxonomies associated with this ad layer. Note this only works with taxonomy archives or single posts.

### Post Types
Click "Add a post type" to add one or more post types associated with this ad layer. Note this only works with post type archives or single posts. 

### Terms
All taxonomies that are associated with Ad Layers (category and post_tag by default) will display their standard term selection meta boxes. Choose one or more terms from each for targeting. Note that an "OR" condition is used for matching.

### Custom Targeting
Click "Add custom targeting" to choose one or more DFP custom targeting variables to add to the DFP request produced by this ad layer. The value can be:
- The term(s) associated with the page. This will only be populated on taxonomy archives or single posts. The taxonomies associated with Ad Layers are available for selection.
- The post type of the page. This will only be populated on post type archives or single posts.
- The author. This will only be populated on author archives or single posts.
- Other. This can be any free-form text value and will be static anywhere the ad layer is displayed.

## Taxonomies

As with any WordPress post type, all taxonomies that are associated with ad layers for targeting will appear under this menu. By default, this is Categories and Tags.

## Ad Server Settings

This allows for the selection of the active ad server. Each server will ad its own settings.

## Layer Priority

This provides a simple drag and drop interface for choosing the priority of layers in the event that page matches more than one.

## Custom Variables

This defines what custom variables are available for targeting and makes them available when creating a new ad layer.

## Ad Servers

The architecture of Ad Layers abstracts the functionality that would be common to any ad server and allows for extending the built-in Ad_Layers_Ad_Server class to add support for additional ad servers. Currently, Ad Layers only supports DoubleClick for Publishers (DFP).

### DFP

DFP adds the following settings to the Ad Server Settings page:

*Account ID*

This sets the DFP account ID which is used throughout the DFP header code.

*Path Template*

This allows the creation of one or more templates to define the path for DFP ad slots. Under the help tab in the upper right, there are multiple template tags to make these dynamic. They include:

```
#account_id#
```
Your DFP account ID

```
#domain#
```
The domain of the current site, taken from get_site_url

```
#ad_unit#
```
The ad unit name

```
#post_type#
```
The post type of the current page, if applicable

```
#taxonomy#
```
The current term from the specified taxonomy, if applicable. If the taxonomy is hierarchical, each term in the hierarchy above the current term will be added to the path. If there is more than one term, only the first will be used. This is repeated for each taxonomy used with Ad Layers.

*Breakpoints*

This allows for the addition of one or more breakpoints for responsive ad serving and is the heart of creating the DFP ad setup. For each breakpoint, you can add:

Title: Mostly a display label.
Minimum Width: The minimum width at which this breakpoint is displayed.
Maximum Width: The maximum width at which this breakpoint is displayed.

The above two fields correspond to how DFP handles responsive ad serving. Depending on how your ad units are configured, these might not correspond exactly to your design breakpoints.

You can then click "Add Ad Unit" to add one or more ad units for this breakpoint. For each you can add:

Code: This the DFP Ad Unit code. It is preferable to the name since it acts a key without 

After that, add one or more sizes with following fields:
Width: Width of the ad
Height: Height of the ad
Default Size: Check this if this is the default size for this ad unit across all breakpoints. This value is required for responsive ad serving. If not checked for at least one size, the ad will not be displayed.
Out of Page: Indicates this size is an Out of Page unit for DFP.

## Adding ad units to a template

There are two ways to add an ad unit to a template. The first is to use the built-in action hook directly in a template file:

```
<?php do_action( 'ad_layers_render_slot', 'slotname' ) ?>
```

There is also an Ad Layers Ad Widget that allows for selection of a slot from a dropdown and can be placed into a sidebar.

In both cases, if the slot isn't defined for the current ad layer, it will simply be skipped and generate no markup.

A shortcode will also be added in the next round of development.

## Hooks and Filters

There are numerous action hooks and filters available throughout the plugin to customize the functionality as needed. These can be clearly found in the plugin source and a full digest will be added to this document in the next round of development.

## Future Enhancements

The next round of development on this plugin will include the following enhancements:
- Frontend Javascript framework for ad refresh, lazy loading for ad viewability, and other features.
- Ad Layer shortcode
- Additional caching
- Additional documentation
- Fixes for any reported bugs