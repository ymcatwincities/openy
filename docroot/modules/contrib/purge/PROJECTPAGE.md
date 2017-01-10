[//]: # ( clear&&curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )
[//]: # ( curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=pdf http://c.docverter.com/convert>PROJECTPAGE.pdf )

_The modular external cache invalidation framework._

The ``purge`` module facilitates cleaning **external caching systems**, **reverse
proxies** and **CDNs** as content actually changes. This allows external caching
layers to keep unchanged content cached infinitely, making content delivery more
efficient, resilient and better guarded against traffic spikes.

## Drupal 8
The ``8.x-3.x`` versions enable invalidation of content from external systems
leveraging Drupal's brand new cache architecture. The technology-agnostic plugin
architecture allows for different server configurations and use cases. Last but
not least, it enforces a separation of concerns and should be seen as a
**middleware** solution (see ``README``
[``.pdf``](http://cgit.drupalcode.org/purge/blob/README.pdf?h=8.x-3.x)
[``.md``](http://cgit.drupalcode.org/purge/plain/README.md?h=8.x-3.x)).

###### Getting started
For most simple configurations, start with:

* ``drush en purge purge_ui purge_drush purge_queuer_coretags purge_processor_cron``
* Head over to [``admin/config/development/performance/purge``](http://mysite/admin/config/development/performance/purge).
* Now you need to install - and probably configure -  a third-party module that
  provides a **purger**. If no module supports invalidation of your cache layer
  and doing so works over HTTP, then use the generic [``purge_purger_http``](https://www.drupal.org/project/purge_purger_http).

###### Third-party integration
This project aims to get all modules dealing with proxies and CDNs on board and
to integrate with Purge. As known to date, these modules are or are being
integrated:

 * [``purge_purger_http``](https://www.drupal.org/project/purge_purger_http) for
   generic HTTP-based invalidation, e.g. Varnish, Squid and Nginx.
 * [``purge_queuer_url``](https://www.drupal.org/project/purge_queuer_url)
 * [``purge_purger_keycdn``](https://www.drupal.org/project/purge_purger_keycdn)
 * [``cloudflare``](https://www.drupal.org/project/cloudflare)
 * [``cloudfront_purger``](https://www.drupal.org/project/cloudfront_purger)
 * [``acquia_purge``](https://www.drupal.org/project/acquia_purge)
 * [``varnish``](https://www.drupal.org/project/varnish_purge)
 * [``akamai``](https://www.drupal.org/project/akamai) ([help needed!](https://www.drupal.org/node/2678496))

Interested? Reach out any time of day and we'll get you going!

## Drupal 7 and Pressflow 6
The current stable ``1.x`` versions depend on the [cache expiration](http://drupal.org/project/expire)
module and act upon events that are likely to expire URLs from external caching
systems. Technically these versions work by sending HTTP out ``PURGE`` requests
per changed item, the request can be defined exactly. You can [find the installation
instructions and frequently asked questions](http://cgit.drupalcode.org/purge/plain/README.md?h=7.x-1.x)
bundled in the code repository.

###### Mind the coverage gap
Due to the architectural nature of Drupal 7 and versions below, it is impossible
for [cache expiration](http://drupal.org/project/expire) to detect _every single
content change_. In some cases you may need to use the [expire](http://drupal.org/project/expire)
module API or [rules](http://drupal.org/project/rules) integration to cover
views, blocks and places left undetected. In all cases we recommend testing
thoroughly before increasing your ``page_cache_maximum_age`` variable.

###### Release expectations
As our focus is on Drupal 8, the ``1.x`` branch is in maintenance-only mode and
receives mostly bug- and security fixes. The ``2.x`` branch is considered
experimental, unmaintained and will likely never reach a production release.
