Views Advanced Routing

Views Advanced Routing is a module providing a custom display extender for Views
allowing you to take advantage of Drupal 8's new routing system. You can set the
defaults, requirements, and options for each route.

See Drupal 8 documentation on routing YAML: https://www.drupal.org/node/2092643

Copyright (C) 2015 Daniel Phin (@dpi)

# License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

# Usage

## Enable Views Advanced Routing

 1. Enable the module.
 2. Go to *Administration -> Structure -> Views*
    Click the 'Settings' tab.
    Click the 'Advanced' sub tab.
 3. Under __Display extenders__, enable the 'Route' checkbox.
 4. Save configuration.

## Using Routes

 1. Open a view with a 'page' or 'feed' display.
 2. In the center column, a new 'Route' setting will be displayed.
    Click the link too set your custom route settings.
 3. Route YAML is in the same format as Drupals '*routing.yml' files.
    Strip top level YAML keys such as defaults, requirements, options and paste
    values directly into the textbox.
 4. Save the view.

## FAQ

### Path

Use the built-in views path setting to set path. If you have need parameter
converters you should change the format from `{node}` to `%node`.

If you require __entity parameter converters__, you can use this code in your 
custom 'options' YAML:

```yaml
parameters:
  parameter_name:
    type: 'entity:my_entity_type'
```

Where parameter_name is the %parameter_name used in the path. It is usually the
same name as the entity_type. For example, a tab attached to a node page:

__Path: node/%node/my_view_name__

```yaml
parameters:
  node:
    type: 'entity:node'
```