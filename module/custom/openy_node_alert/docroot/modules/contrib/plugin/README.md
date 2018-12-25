# Plugin
[![Build Status](https://travis-ci.org/bartfeenstra/drupal-plugin.svg?branch=8.x-2.x)](https://travis-ci.org/bartfeenstra/drupal-plugin)

## About
The plugin module complements Drupal core's 
[plugin system](http://drupal.org/developing/api/8/plugins) in several ways:

* Plugin definition mappers provide an API for handling untyped array plugin
  definitions. See [this issue](http://drupal.org/node/2458789) for an effort to
  fix this in core's plugin system.
* Plugin selectors allow users to select plugins of a particular type and
  configure them using the plugins' configuration forms.
* Filtered plugin manager decorators to easily filter/limit the plugins a
  manager can provide.
* A plugin collection field type that allows plugin instances to be stored on
  content entities.
* Plugin type discovery through the `plugin.plugin_type_manager` service.

