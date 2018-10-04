CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Easy Breadcrumb module provides configurable breadcrumbs that improve on
core breadcrumbs by including the current page title as an unlinked crumb which
follows breadcrumb best-practices
(URL "https://www.nngroup.com/articles/breadcrumb-navigation-useful/").
Easy Breadcrumb takes advantage of the work you've already done for generating
your path aliases, while it naturally encourages the creation of semantic
and consistent paths. This module is currently available for Drupal 6.x, 7.x,
and 8.x.x.

Easy Breadcrumb uses the current URL (path alias) and the current page's title
to automatically extract the breadcrumb's segments and its respective links.
The module is really a plug and play module because it auto-generates the
breadcrumb by using the current URL and nothing extra is needed.

 * For a full description of the module visit:
   https://www.drupal.org/project/easy_breadcrumb
   or
   https://www.drupal.org/docs/8/improve-the-breadcrumbs

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/node/2929013


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Easy Breadcrumb module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module. The system
       breadcrumb block has now been updated.
    2. Navigate to Administration > Configuration > User Interface > Easy
       Breadcrumb for configurations. Save Configurations.

Configurable parameters:
 * Include / Exclude the front page as a segment in the breadcrumb.
 * Include / Exclude the current page as the last segment in the breadcrumb.
 * Use the real page title when it is available instead of always deducing it
   from the URL.
 * Print the page's title segment as a link.
 * Make the language path prefix a segment on multilingual sites where a path
   prefix ("/en") is used.
 * Use menu title as fallback instead of raw path component.
 * Remove segments of the breadcrumb that are identical.
 * Use a custom separator between the breadcrumb's segments. (TODO)
 * Choose a transformation mode for the segments' title.(TODO)
 * Make the 'capitalizator' ignore some words. (TODO)


MAINTAINERS
-----------

 * Greg Boggs - https://www.drupal.org/u/greg-boggs
 * Jeff Mahoney (loopduplicate) - https://www.drupal.org/u/loopduplicate

Supporting organization:

 * Hook 42 - https://www.drupal.org/hook-42
