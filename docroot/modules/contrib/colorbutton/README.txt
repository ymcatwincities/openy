CKEditor Color Button

Installation
============

This module requires the core CKEditor module and also the Color Button plugin
from CKEditor.com.

1. Download the plugin from http://ckeditor.com/addon/colorbutton at least
version 4.5.6.
2. Place the plugin in the root libraries folder (/libraries).
3. Enable Color Button module in the Drupal admin.
4. Configure your CKEditor toolbar to include the button (either text or
background color, or both).
5. You can enter a list of hex values to support to limit the user on what
colors they can use. Leave blank to use the default values from the plugin.

Follow these steps to make sure the plugin works for Basic HTML text format:

1. Drag the button from the toolbar to the active items
2. Make sure you add the style attribute to span tag from the list of allowed
html tags, see example what you need to add - "<span style>".
