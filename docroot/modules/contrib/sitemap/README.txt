Drupal sitemap module:
----------------------
Author - see https://drupal.org/project/sitemap
Requires - Drupal 8
License - GPL (see LICENSE)


Overview:
--------
This module provides a sitemap that gives visitors an overview of
your site. It can also display the RSS feeds for all blogs and
terms. Drupal generates the RSS feeds automatically but few seems
to be aware that they exist.

The sitemap can display the following items:

* A message to be displayed above the sitemap
* The front page.
* Any books that optionally will be displayed fully expanded.
* Any menus that will be displayed fully expanded.
* Any vocabulary with all the terms expanded.
  Optionally with node counts and RSS feeds.
* A syndication block, the "more" link goes to the sitemap.


Installation:
------------
1. Place this module directory in your modules folder (this will
   usually be "modules/" for Drupal 8).
2. Go to Manage -> Extend to enable the module.
3. Check the Manage -> People -> Permissions page to
   enable use and administration of this module for different roles.
4. @TODO
   Make sure the menu item is enabled in
   Manage -> Structure -> Menus -> Tools.
   You may move it to another menu if you like.
5. Have a look at the different settings in
   Administer -> Configuration -> Sitemap
6. Visit http://example.com/sitemap.


Sitemap term path (and Pathauto):
-------------------------------
There is a "depth" setting on the Sitemap settings page where you can adjust
how sitemap constructs the term links.

For making Sitemap build the same path that Pathauto per default generates
alias for you should set this to "-1" I believe.


Last updated:
------------
