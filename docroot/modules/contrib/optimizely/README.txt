CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This Optimizely module integrates a Drupal site with Optimizely. This module
allows sites to only load the Optimizely JavaScript on specific pages
helping keep load times down.

Optimizely.com is a A/B testing tool that helps you optimize your site's
conversion rate. In order to use this module, you'll need an Optimizely account.
A Free 30 day trial account is available.

 * For a full description on Optimizely services visit:
  https://www.optimizely.com/

 * For a full description of the module visit:
  https://www.drupal.org/project/optimizely

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/optimizely


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

The Apache mod_rewrite module is required to support the project
enable/disable checkboxes on the Project Listing page. Use the project form to
change the status if mod_rewrite is not available.


INSTALLATION
------------

Install the optimizely module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

    1. Navigate to Administration > Extend and enable the Optimizely module.
    2. Navigate to Administration > Configuration > System > Optimizely.
    3. A default project was created when the module was enabled.
    4. Navigate to the Account Info tab and add the Optimizely account ID supplied
       by the Optimizely website. On the Optimizely site, navigate to Settings >
       Implementation to find the Project ID. It is the number after "/js/" in the
       Optimizely Tracking Code snippet.
    5. Navigate to the "Add Project" tab and enter a descriptive name for the
       project in the "Project Title" field.
    6. In the "Optimizely Project Code" field enter the code for the specific
       project. This can be found on the Optimizely web site. Navigate to Settings >
       Overview and click into the specific project. A unique URL will be created
       and the last set of numbers in the URL is the project code.
    7. Each project can be enabled or disabled. When enabled, the project will be
       applied site wide.
    8. Set the path where the Optimizely code snippet will appear. For Example:
       "/clubs/*" causes the snippet to appear on all pages below "/clubs" in the
       URL but not on the actual "/clubs" page itself.
    9. Add the project. It should now appear in the Project Listing tab.

Most of the configuration and designing of the A/B tests is done by logging into
the account on the Optimizely website.


MAINTAINERS
-----------

The 7.x-1.x branch was created by:

 * Yannis Karampelas (yannisc) - https://www.drupal.org/u/yannisc

This module was created and sponsored by Netstudio, a drupal development company
in Athens, Greece.

 * Netstudio - https://www.netstudio.co.uk/

---

The 7.x-2.x branch was created by:

 * Darren Douglas Lee (DeeZone) - https://www.drupal.org/u/deezone

Sponsored by DoSomething.org, a New York City based non-profit working towards
empowering youth (16-24) in social good around the world.

 * DoSomething.org - https://www.dosomething.org/us

---

The 8.x branches were created by:

 * Earl Fong (tz_earl) - https://www.drupal.org/u/tz_earl

as an independent volunteer
