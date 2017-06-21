This module makes it easy to add the Optimizely tracking code in your page's <HEAD>. Through project / snippet setting entries specific javascript files are called from the Optimizely website on specific website paths.

Optimizely.com is a A/B testing tool that helps you optimize your site's conversion rate.

After you enable the module, go to the module settings page (/admin/config/system/optimizely/settings) and add your Optimizely account ID. A default project was created when the module was enabled. The Optimizely account ID value will be used in the default project entry. When enabled, the project will be applied site wide. Additional project entries can be made to control the calling of additional Optimizely javascript / snippet files on specified paths. Each entry can also be enabled / disabled.

This module is created and sponsored by netstudio.gr, a drupal services company based in Athens, Greece.

The 7.x-2.x branch was created by Darren "Dee" Lee (DeeZone: http://drupal.org/user/288060) and Peter Lehrer (plehrer: http://drupal.org/user/2257350). 8.x-2.15 conversion is by Earl Fong (tz_earl: http://drupal.org/user/2531114).

Sponsored by DoSomething.org, a New York City based non-profit working towards empowering youth (16-24) in social good around the world.

NOTE: The Apache mod_rewrite module is required to support the project enable/disable checkboxes on the Project Listing page. Use the project form to change the status if mod_rewrite is not available.
