# Open Y Home Branch

This module provides Home Branch customization for already existing location 
based parts of the Open Y profile, like programs, schedules, and more.

As Home Branch storage we use cookies (See hb_cookies_storage.js), so any plugin 
can access selected Home Branch location and react on storage change. 

Also, this module provides the concept of HomeBranchLibrary plugins.

## HomeBranchLibrary plugins

HomeBranchLibrary plugins consist of back-end and front-end parts.

__Back-end part__ - Drupal plugin instance (See \Drupal\openy_home_branch\Annotation\HomeBranchLibrary).
This plugin contains the next properties:

1. `Id` - The HomeBranchLibrary plugin ID.
2. `Title` - The human-readable name of the HomeBranchLibrary plugin.
3. `Entity` - For this entity will be attached library that referenced in a plugin.

Plugin instance should provide next methods (See HomeBranchLibraryInterface):

1. `isAllowedForAttaching()` - HomeBranchLibrary plugin rules for attaching to entity.
2. `getLibrary()` - Library name for attaching to entity.
3. `getLibrarySettings()` - library settings that used on front-end (drupalSettings).

You can find examples here - `openy_home_branch/src/Plugin/HomeBranchLibrary/*`

__Front-end part__ - js libraries that provide markup or front-end logic.

For convenience, was created `hb-plugin-base.js`, it provides `hbPlugin` jQuery plugin.

This jQuery plugin is customizable, so you can add your custom settings or arguments.

`hbPlugin` contains some predefined properties and methods, but you can leave them empty by default. For example:

1. `selector` - Selector that related to the event (on-change, on-click). Your plugin can create new
DOM element on the page and in case this element should react on some event - 
add this element selector to this property. Example: `selector: '.hb-location-checkbox'`.

2. `event` - Event that trigger `onChange` function. Additionally to `selector` 
you can set jQuery event, this can be `onChange` or `onClick`. Example - `event: 'change'`.

3. `element` - Storage for created element, this is useful to access element inside functions.

4. `init()` - This function should contain base logic related to plugin instance.

5. `onChange()` - This function should contain change logic related to plugin instance.
This function is related to `selector` and `event`  properties.

6. `addMarkup()` - This function should provide markup related to plugin instance.

All those functions executed automatically:

- `init()` triggered on plugin init and on `hb-after-storage-update` jQuery event

- `onChange()` - triggered on jQuery event that was set in `event` property

- `addMarkup()` - triggered right before plugin init

More examples you can find here - `openy_home_branch/js/hb-plugins/*`

## How to override plugins logic

Most HomeBranchLibrary plugins settings attached to drupalSettings, so we can override this in
the theme_preprocess functions.

Or you can attach to page your custom js file and override plugin here. Example:

```js
  /**
   * Override hb-location-finder.js plugin.
   *
   * @type {Drupal~behavior}
   */
  if (Drupal.homeBranch.plugins.length > 0) {
    for (var key in Drupal.homeBranch.plugins) {
      // First of all we need to find `hb-location-finder` in home branch
      // plugins list.
      if (Drupal.homeBranch.plugins.hasOwnProperty(key) && Drupal.homeBranch.plugins[key]['name'] === 'hb-location-finder') {
        // Some logic here.
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
```

More examples with override cases you can find in `openy_hb_override_example` sub-module.

For back-end plugins we need this in Drupal core - https://www.drupal.org/project/drupal/issues/2958184


