Token Filter
============

Description
-----------

This is a very simple module to make global token values available as an input
filter. The module is integrated with Token [http://drupal.org/project/token]
module.

Usage
-----

Install the module as any other module. Visit the text format administration
page at /admin/config/content/formats/filters and edit a text format. Check the
'Replaces global tokens with their value' filter and save the text format.

When editing a form where this text format is used in a field, you can type
global tokens that will be replaced when the filed is rendered.

Additionally, if the Token [http://drupal.org/project/token] module is enabled,
the token browser is available. You can pick-up the desired token from the
browser by clicking 'Browse available tokens'.

Tokens typically available
--------------------------

Tokens in the next groups are available on a standard installation:

- random
- current-date
- site
- current-page
- current-user
