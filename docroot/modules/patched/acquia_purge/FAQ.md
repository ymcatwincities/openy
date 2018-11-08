[//]: # ( clear&&curl -s -F input_files[]=@FAQ.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )
[//]: # ( curl -s -F input_files[]=@FAQ.md -F from=markdown -F to=pdf http://c.docverter.com/convert>FAQ.pdf )

# THIS DOCUMENT IS OUTDATED AND NEEDS TO BE REWRITTEN!
---

#  Frequently asked questions

#### I'm running a multisite, will it work?
<!-- Yes but it needs configuration, see ``DOMAINS.md``. -->

#### My site runs behind a Amazon Elastic Load Balancer (ELB), will it work?
<!-- Yes it will work without any required action. ELBs spread the traffic across
your two load balancers active-active and ``acquia_purge`` always purges both
your primary as your passive load balancer. -->

#### My site runs behind a CDN, will it work?
<!-- That's hard to answer. Acquia Purge ensures that every single path that's
being purged from within Drupal will be wiped out of your two load balancers,
that ensures that the CDN's "origin" (Acquia's primary LB or ELB) is fresh
at all times. Acquia Purge doesn't actively wipe or purge anything on your CDN
of choice so you are dependent on how well it respects the ``Cache-Control``
header set by the pages Drupal generates. Extensive testing will be required. -->

#### Will Acquia Purge wipe SSL/https:// paths on my site?
<!-- Yes it will, once you added ``$conf['acquia_purge_https'] = TRUE;`` to your
``settings.php`` file. It is important to understand that just adding this
setting will DOUBLE the total amount of purging going on as http:// will also
see its paths purged, so you will need to monitor your site extra careful as the
queue can go up without getting purged fast enough. If the queue does go up
quickly, consider enabling cron processing (with 1-5 minute cron intervals) or
disable http:// purging as well, see ``INSTALL.md``. -->

#### My site redirects all http:// to https://, should Acquia Purge know about that?
<!-- Yes definitely, as that means that without your interference Acquia Purge will
continue to wipe http:// paths and essentially ends up double purging with
high queues and resource waste as result. By adding this to ``settings.php``:
``$conf['acquia_purge_http'] = FALSE;``, you essentially disable all http://
purging. -->

#### My site visitors can go to www.mysite.com and to mysite.com, is that bad?
<!-- Yes, at least I recommend against it. By default Acquia Purge will purge all
domains it detected including your www and your bare domain, the more domains
it has to purge the slower it will become. For SEO reasons it adds another
problem, being that all of your content is now available on the web via TWO
domains which might degrade the ratings of your site. It's best to use a
``.htaccess`` redirect from bare-->www domain and to only purge your www domain
name. See the ``DOMAINS.md`` file on how to achieve this. -->

#### Can I see the contents of the Acquia Purge queue?
<!-- You can at all times visit the status report page to see what the status of
the queue is and how many items are in it. Whenever it is "idle", it's safe to
assume a empty queue. You can print the full queue using "``drush ap-list``" -->

#### Does Acquia Purge process its queue from cron? Should I?
<!-- Not by default, as the client-side AJAX processor takes care of this. When you
are noticing big queue loads - for instance due heavy content processing from
within cron runs - it is possible to enable cron mode. You can enable it by
adding ``$conf['acquia_purge_cron'] = TRUE;`` to ``settings.php``. Once enabled,
it will contribute to speedy queue processing but doesn't replace other means
of processing. It is important - if you run cron through Drush - that the
``--uri`` parameter gets passed in and that it points to the right URL. -->

#### Can I disable the client-side progressbar based purging?
<!-- No, this is not possible. It serves as last resort when nothing else processed
the queue and takes processing out of the main HTTP requests, which helps to
assure a fast Drupal backend experience. However, it can be visually hidden by
revoking the 'purge on-screen' permission, or for every user including the
administrator with: ``$conf['acquia_purge_silentmode'] = TRUE;``. -->

#### With previous versions, I had set up "drush ap-process" in cron, is this okay?
<!-- It still works, though Acquia Purge now has a cleaner way by adding this to
your ``settings.php``: setting ``$conf['acquia_purge_cron'] = TRUE;``. The only use
case not to use the built-in cron processing, is when you need more granular
control over when processing happens. Defining every-minute cron rules that
call ``drush ap-process``, is considered better than relying on the built-in
cron mode as that would mean that all other modules run too which might cause
cache clears and other types of unwanted harm. -->

#### My logs are flooded by the Acquia Purge module, help!
<!-- I'm sorry about that. Logging is meant to help keep a trail for when things go
bad and also to confirm if things work, which is especially helpful for those
new to this module and still in the process of setting it up. But if your site
is running stable and fine with Acquia Purge it makes sense to reduce records
in your logs by setting ``$conf['acquia_purge_log_success'] = FALSE;``, which
will suppress reporting successful purges but it will still log any failure. -->

#### Why do I need "Acquia Purge" instead of "Purge", Purge works fine here!
<!-- The Purge module - written by co-Acquian Paul Krischer - has never been
designed specifically for Acquia Cloud and supporting it becomes more and
more difficult as our products change over time. In the future Purge will be
redesigned technology agnostic making it possible for this module to become
one of its "platform plugins" whilst sharing common infrastructure. In the
meanwhile the usage of purge on Acquia Cloud is discouraged. -->

#### Can I use 'Acquia Purge' on a third-party hosting environment?
Yes this is technically possible, but doing so is officially unsupported.

Let me explain you why. The problem of cache invalidation is incredibly hard
and it is a whole lot more than a simple HTTP PURGE/BAN request when nodes are
saved, in fact, it is close to black magic. Supporting all kinds of hosting
environments would call for a serious API that currently both Acquia Purge
and the Purge module are not, and above all of that, purging paths works but
isn't as elegant as it will get in Drupal 8. Therefore we decided to build a
focused and working module for Acquia Cloud customers but can't support cases
where this module is used outside of our platform.

I already mentioned D8, where things will get inherently better as its whole
architecture supports reverse proxies. All pages/urls D8 generates are tagged
with little strings that represent everything that's rendered into HTML and
includes things as entities, menu items, blocks and much more. Whenever
content changes, the tag gets invalidated which external proxies like Varnish
can also support with features like BAN. The Purge module for Drupal 8 (which
I'm working on) will provide a proper API and its submodules will provide the
necessary plugins to make it work on all environments.

Having that said, Acquia Purge *can be tricked* into thinking it is on Acquia
Cloud and that will make its diagnostic message go silent. The HTTP requests
it makes should work on most Varnish configurations but if it doesn't, it is
up to you to bring things in line and potentially patch the module if
necessary.

```
/**
 * Trick Acquia Purge into thinking we are on Acquia Cloud.
 */
if (!function_exists('ah_site_info_keyed')) {
  $settings['reverse_proxies'] = ['127.0.0.1'];
  function ah_site_info_keyed() {
    return ['sitename' => 'fdev', 'sitegroup' => 'f', 'environment' => 'dev'];
  }
}
```

#### Why does admin/uid 1 always see the progressbar since 7.x-1.0-beta2?
<!-- By default, only the 'purge on-screen' permission is consulted. This depends
on Drupal's permission system which always grants administrators access. It is
not recommended to use UID 0 to edit content, but if you insist, the processor
can be visually hidden - not disabled - with putting this into ``settings.php``:
``$conf['acquia_purge_silentmode'] = TRUE;``. -->

#### When I tested before it was slow and the queue exploded, should I reconsider?
<!-- Yes, absolutely! Before Acquia Purge was a official project there has long
been a branch called ``queuing-elb-support`` that many customers used. It was
the predecessor of the current queuing-based engine and worked but allowed too
many domains to be purged in combination with a deadly payload on Drupal's
queue table. That caused the database to crash and purges to be dreadfully
slow. As of 7.x-1.0-alpha2 the module processes up to 6 purges in parallel,
reduced the database payload drastically and as of version 7.x-1.0-alpha3
many built-in diagnostic tests protect sites against issues from the past. -->

#### My views and XYZ paths aren't getting purged, this is a bug!
<!-- The Acquia Purge module purges whatever it is being told to purge, either via
expire, a rule action or via custom code you wrote. The expire module has
the difficult task of detecting what pages need to be wiped based on changing
entities (nodes, taxonomy, menu items..) and does a quite good job for simple
sites. However, it can't just automatically detect your views or other custom
paths specific to your site and will therefore almost always miss pieces.

The Acquia Purge module exposes the rule action "Clear pages on Acquia Cloud"
which allows you to purge paths that weren't automatically cleared, for
instance a view you created on the path ``news`` or your contact form on the
path ``contact``. Although discouraged, it is also technically possible to use
tokens that generate full URLs (domains will get stripped off). -->

####  What about the rule action 'Purge a path from Varnish on Acquia Cloud'?
<!-- This is the original rule action that shipped with Acquia Purge prior to
version 7.x-1.0 and is still available but no longer recommended to be used,
on your site. The obsoleted rule action now maps its behavior to the new rule
action ``Clear pages on Acquia Cloud`` and therefore also supports URLs. If
your site has rules using the obsoleted rule, you will get periodic error
messages in your logs as it will be deleted in the future. -->

####  Where did the 'Clear URL(s) from the page cache' rule action go?
<!-- It got renamed and now gets reimplemented by the Acquia Purge module, as we
learned that it confused many users and was also less efficient as it mangled
paths through all of Expire's code. The rule got renamed into ``Clear pages on
Acquia Cloud`` and is the only one rule action you should use now. -->

#### Why doesn't Acquia Purge work with "Expiration of cached pages" disabled?
<!-- Because it will be ineffective. The main reason for implementing expire and
Acquia Purge is that you can increase the value of this setting up to many
hours, a day or even months. Once this setting is set to a high and sane value
all pages served by Drupal will be kept within Varnish as long as possible and
anonymous traffic won't ever cause your Drupal site to bootstrap leaving your
Acquia Cloud web servers available for other important resource needs like
editors and site administrators or cron for instance. -->

#### Will my sites performance improve once Acquia Purge and expire are set up?
<!-- Yes, drastically even once you've increased your "Expiration of cached pages"
setting to a high value (rather days than hours). The higher it is set, the
higher the time limit in the ``Cache-Control`` HTTP response header will be set.
That will make your Acquia Cloud site's Varnish instances keep the pages in
cache longer and frees up many PHP processing slots on your web servers. -->

#### Can anonymous traffic cause me unwanted purges to happen?
<!-- Acquia Purge will always *queue* pages that are requested purging by expire,
for instance articles when anonymous users commented on them. However, a
anonymous user will *never* trigger the AJAX-based client-side processor to
prevent misuse and exposing Acquia Purge as public DDOS-tool. That means that
comments and other anonymously queued paths will be purged as soon as a logged
in user triggers a purge or when "``drush ap-process``" is called. If this is a
limitation to you, please file a ticket and we will add a special permission. -->

#### Can I apply globs or wildcards to purge multiple paths at once? Like news/*
<!-- As of this moment this is not possible. There is a open feature request ticket
on https://drupal.org/node/2155319 and we really like to have this in but our
systems will have to be changed for it and Acquia Purge needs to be updated
once it becomes available in Varnish. -->

#### I'm including JS-code that does AJAX requests with changing variables, bad?
<!-- Yes, don't even consider doing this. It happens every once in a while that we
find a site that has a client-side script that contacts Drupal with ever
changing request URLs, e.g. ``/mycallback?t=1384274831``. Because both Varnish
and Drupal's page cache see the full absolute URL as the unique identifier to
base caching on, a randomly changing URL will continuously wake up your web
servers and could kill the performance of your site. -->

#### Can I use Acquia Purge to programmatically purge paths I want to be cleaned?
<!-- Yes, the module has been purely designed to purge things on Acquia Cloud and
to do it well! Its relation with the expire module is very thin for instance,
it receives paths and wraps those to its own publicly facing API functions.

You can queue items for purging like this:

```
$service = _acquia_purge_service();
$service->addPath('node/5?parameter');
$service->addPaths(array('news/section1', 'contact'));
```

If you run the code above as authenticated user during a web request (e.g.
not via Drush), it will trigger the AJAX processor for that users next page
load. However, if you want to process directly yourself, you can work a chunk
from the queue with this:

```
$service = _acquia_purge_service();
if ($service->lockAcquire()) {
  $service->process();
  $service->lockRelease();
}
```

The ``AcquiaPurgeService::process()`` call will run as long as PHP has resources
for and depends on Acquia Purge's internal capacity calculation. When it runs
from the CLI, it has a higher ``max_execution_time`` and will therefore process
much more at once, then it will when triggered through a web server.

You can always check upon the queue state by querying:

```
var_dump(_acquia_purge_service()->stats());
```
-->
#### Should I disable Drupal's page cache because of my special module or feature?
<!-- No, never. -->
