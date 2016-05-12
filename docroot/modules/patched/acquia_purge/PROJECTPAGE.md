[//]: # ( clear&&curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2|sed 's/\&#39;/\"/g'|sed 's/\&amp;/\&/g'|sed 's/\&quot;/\"/g' )
[//]: # ( curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=pdf http://c.docverter.com/convert>PROJECTPAGE.pdf )

**_Top-notch cache invalidation on Acquia Cloud!_**

The ``acquia_purge`` module invalidates your
[Varnish caches](https://www.varnish-cache.org/about) on your Acquia Cloud site.
When this is combined by setting Drupal's _time to live (TTL)_ extremely high,
your stack requires less servers, becomes much more resilient against _DDOS
attacks_ and performance dramatically improves!

## When do I need this?
Although we **recommend every Acquia Cloud customer** to use this module, you
will absolutely need to start using it when any of these things sound familiar:

* You're often getting the infamous ``Temporary Unavailable`` error.
* Pages are often slow and take more than 2-3 seconds to load.
* Traffic peaks quickly take down your site.
* You have many ``web`` servers and would like to reduce costs.
* Heavy processing (e.g. slow queries, cron imports) take your site down.

## What time does it take?
Drupal 8 site owners have a true _turn-key_ experience as the module integrates
heavily with the ``purge`` [cache invalidation framework](https://www.drupal.org/project/purge)
and should be finished within minutes, get started with the
[installation instructions](http://cgit.drupalcode.org/acquia_purge/plain/INSTALL.md).

###### Drupal 7
Owners of Drupal 7 sites are advised to schedule _at least one week_ of testing
and tuning to ensure that every section of their site is covered, as the
``expire`` [module](https://www.drupal.org/project/expire) won't cover
everything and requires them to set up rules. See its
[README](http://cgit.drupalcode.org/acquia_purge/plain/README.md?h=7.x-1.x),
[installation instructions](http://cgit.drupalcode.org/acquia_purge/plain/INSTALL.md?h=7.x-1.x)
and especially its
[domains](http://cgit.drupalcode.org/acquia_purge/plain/DOMAINS.md?h=7.x-1.x)
documentation.

## Temporary but important Drupal 8 information!
The Drupal 8 version is under full development but cannot be considered stable
as of March 2016. The following limitations currently block full production
usage:

###### Load balancer discovery fails
Upon installation and after enabling the purger, the diagnostics will block
cache invalidation with: "_No balancers were discovered, therefore cache
invalidation has been disabled._". Acquia is aware of this issue and busy
resolving it, in the meantime you can work around this by temporarily placing
this in ``settings.php`` after your _Acquia Cloud include statement_:

```
/**
 * Acquia Purge - temporary workaround as of March 2016.
 */
if (!isset($settings['reverse_proxies']) && isset($trusted_reverse_proxy_ips)) {
  $settings['reverse_proxies'] = $trusted_reverse_proxy_ips;
}
```

###### For now, tag-based invalidation isn't supported.
Acquia is busy overhauling its Varnish configuration to - among other reasons -
support tag-based invalidation and make Acquia Cloud the best supported platform
for this. Since this process is still ongoing, only URL-based invalidation works
and requires you to temporarily install and use the
[URLs queuer module](https://www.drupal.org/project/purge_queuer_url), which is
considered inferior but functional for those that cannot wait. Make sure to stop
using this module when tag-based invalidation is officially rolled out and when
invalidating your CDN doesn't require it either.
