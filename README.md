# Ad Layers

The Ad Layers plugin ("ad-layers") will provide an advanced mechanism for ad ops teams and other digital producers to customize the layout of advertisements in WordPress templates with minimal developer intervention.

# Managing Ad Layers

The plugin adds an Ad Layers custom post type to the WordPress dashboard for managing available add layers. There are multiple options under this menu.

## Ad Layers

Shows a list of all available ad layers. This uses a standard WordPress List Table with columns added for all of the ad layer specific fields listed below.

## Add New Ad Layer

This allows you to create a new ad layer. This is set up as a WordPress custom post type with the following fields:

### Title
The standard WordPress title field acts as a label to reference this ad layer.

### Ad Units
Click "Add an ad unit" to add one or more ad units that are part of this ad layer.

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

This allows the creation of one or more templates to define the path for DFP ad units. Under the help tab in the upper right, there are multiple template tags to make these dynamic. They include:

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

*Ad Units*

You can then click "Add Ad Unit" to add one or more ad units. For each you can add:

Code: This the DFP Ad Unit code. It is preferable to the name since it acts a key without

After that, add one or more sizes with following fields:
Width: Width of the ad
Height: Height of the ad
Default Size: Check this if this is the default size for this ad unit across all breakpoints. This value is required for responsive ad serving. If not checked for at least one size, the ad will not be displayed.
Out of Page: Indicates this size is an Out of Page unit for DFP.
Breakpoints: Check off all breakpoints where this size should be displayed.

## Adding ad units to a template

There are three ways to add an ad unit to a template. The first is to use the built-in action hook directly in a template file:

```
<?php do_action( 'ad_layers_render_ad_unit', 'unitname' ) ?>
```

There is also an Ad Layers Ad Widget that allows for selection of an ad unit from a dropdown and can be placed into a sidebar.

Finally, a shortcode is available for all posts in the format:
```
[ad-unit unit=unitname]
```
In all cases, if the ad unit isn't defined for the current ad layer or is invalid, it will simply be skipped and generate no markup.

## Hooks and Filters

There are numerous action hooks and filters available throughout the plugin to customize the functionality as needed. These are listed below by type and class. For further information on specifically what can be done with each hook, please reference it in the source code which is documented throughout.

### Action Hooks by Class

*Ad_Layers_DFP*

ad_layers_dfp_before_setup

ad_layers_dfp_after_setup

ad_layers_dfp_custom_targeting

ad_layers_dfp_after_ad_units

### Filter Hooks by Class

*Ad_Layers_Ad_Server*

ad_layers_ad_server_settings

ad_layers_ad_servers

ad_layer_ad_server_setting

ad_layers_ad_server_get_domain

ad_layers_custom_targeting_args

ad_layers_custom_targeting_sources

*Ad_Layers_DFP*

ad_layers_dfp_formatting_tags

ad_layers_dfp_ad_unit_prefix

ad_layers_dfp_formatting_tags

ad_layers_dfp_async_rendering

ad_layers_dfp_collapse_empty_divs

ad_layers_dfp_ad_unit_sizes

ad_layers_dfp_targeting_values_by_unit

ad_layers_dfp_mapping_sizes

ad_layers_dfp_mapping_by_unit

ad_layers_dfp_default_by_unit

ad_layers_dfp_targeting_by_unit

ad_layers_dfp_oop_units

ad_layers_dfp_page_level_targeting

ad_layers_dfp_custom_targeting_value

ad_layers_dfp_author_targeting_field

ad_layers_dfp_term_targeting_field

ad_layers_dfp_ad_unit_class

ad_layers_dfp_ad_unit_html

ad_layers_dfp_breakpoint_key

ad_layers_dfp_ad_unit_id

ad_layers_dfp_formatting_tag_pattern

ad_layers_dfp_formatting_tag_value

ad_layers_dfp_path

ad_layers_dfp_get_settings_fields

ad_layers_dfp_custom_targeting_field_args

*Ad_Layers_Meta_Boxes*

ad_layers_post_types

*Ad_Layers_Post_Type*

ad_layers_taxonomies

ad_layers_edit_columns
s
ad_layers_save_post

ad_layers_delete_post

ad_layers_ad_units_field_args

ad_layers_custom_targeting_ad_unit_args

ad_layers_page_types_field_args

ad_layers_taxonomies_field_args

ad_layers_post_types_field_args

ad_layers_custom_targeting_field_args

*Ad_Layers*

ad_layers

ad_layers_custom_variables

ad_layers_get_ad_layers

ad_layers_get_ad_layer

ad_layers_get_custom_variables

ad_layers_get_(object_type)_args

ad_layers_get_(object_type)_operator

ad_layers_get_(object_type)

ad_layers_active_ad_layer

ad_layers_ad_server_single_post_types

ad_layers_ad_server_archived_post_types

ad_layers_ad_server_taxonomies

ad_layers_page_types

ad_layers_current_page_type

## Debug Mode
To enable debug mode, simply append ?adlayers_debug to any URL. This will add a toolbar that shows the current ad layer and placeholders for all visible ad units with links to toggle between sizes.

## Future Enhancements

The next round of development on this plugin will include the following enhancements:
- Lazy loading for ad viewability, and other features.
- Additional caching
