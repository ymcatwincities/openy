README.txt
==========

A module containing helper functions for Drupal developers and inquisitive admins.
This module can print a log of all database queries for each page request at
the bottom of each page. The summary includes how many times each query was
executed on a page, and how long each query took.

 It also offers
 - a block for running custom PHP on a page
 - a block for quickly accessing devel pages
 - a block for masquerading as other users (useful for testing)
 - reports memory usage at bottom of page
 - A mail-system class which redirects outbound email to files
 - more

 This module is safe to use on a production site. Just be sure to only grant
 'access development information' permission to developers.

Enabling the Devel Kint module gives you a dpr() function, which pretty prints variables.
Useful during development. Also see similar helpers like dpm(), dvm().

AJAX developers in particular ought to install FirePHP Core from
http://www.firephp.org/ and put it in the devel directory. You may
use the devel-download Drush command to download the library. If downloading by hand,
your path to fb.php should look like libraries/FirePHPCore/lib/FirePHPCore/fb.php.
You can use svn checkout http://firephp.googlecode.com/svn/trunk/trunk/Libraries/FirePHPCore.
Then you can log php variables to the Firebug console. Is quite useful.

Included in this package is also:

- devel_node_access module which prints out the node_access records for a given node. Also offers hook_node_access_explain for all node access modules to implement. Handy.
- devel_generate.module which bulk creates nodes, users, comment, terms for development.

Some nifty Drush integration ships with devel and devel_generate. See `drush help` for details.

DEVEL GENERATE EXTENSIONS
=========================
Devel Images Provider [http://drupal.org/project/devel_image_provider] allows to configure external providers for images.

DRUSH UNIT TEST
==================
See develDrushTest.php for an example of unit testing of the Drush integration.
This uses Drush's own test framework, based on PHPUnit. To run the tests, use
run-tests-drush.sh. You may pass in any arguments that are valid for `phpunit`.

AUTHOR/MAINTAINER
======================
- Moshe Weitzman <weitzman at tejasa DOT com> http://www.acquia.com
- Hans Salvisberg <drupal at salvisberg DOT com>
- Pedro Cambra <https://drupal.org/user/122101/contact> http://www.ymbra.com/
