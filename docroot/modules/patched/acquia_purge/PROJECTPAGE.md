[//]: # ( clear&&curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2|sed 's/\&#39;/\"/g'|sed 's/\&amp;/\&/g'|sed 's/\&quot;/\"/g' )
[//]: # ( curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=pdf http://c.docverter.com/convert>PROJECTPAGE.pdf )

**_Top-notch cache invalidation on Acquia Cloud!_**

The ``acquia_purge`` module invalidates your
[Varnish caches](https://www.varnish-cache.org/about) on your Acquia Cloud site.
When combined with setting Drupal's _time to live (TTL)_ very high,
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

###### Drupal 8 testing program!
The Drupal 8 version is currently **being tested** by a select group of Acquia
Clients, so that we learn what it does to hardware requirements and find
potential bugs and quirks. Everyone with a production environment is **more than
welcome** to join our program, just file a support ticket or ask your
Acquia representative!

We're currently in **private beta**, which means you can't just download and
enable the module just yet... Once we're through this phase, the **public beta**
will follow, leading up to release candidates and a stable version for all.

###### Drupal 7
Owners of Drupal 7 sites are advised to schedule _at least one day_ of testing
and tuning to ensure that every section of their site is covered, as the
``expire`` [module](https://www.drupal.org/project/expire) won't cover
everything and requires them to set up rules. See its
[README](http://cgit.drupalcode.org/acquia_purge/plain/README.md?h=7.x-1.x),
[installation instructions](http://cgit.drupalcode.org/acquia_purge/plain/INSTALL.md?h=7.x-1.x)
and especially its
[domains](http://cgit.drupalcode.org/acquia_purge/plain/DOMAINS.md?h=7.x-1.x)
documentation.
