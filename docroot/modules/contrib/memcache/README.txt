## IMPORTANT NOTE ##

This file contains installation instructions for the 8.x-1.x version of the
Drupal Memcache module. Configuration differs between 8.x and 7.x versions
of the module, so be sure to follow the 7.x instructions if you are configuring
the 7.x-1.x version of this module!

## REQUIREMENTS ##

- PHP 5.5 or greater
- Availability of a memcached daemon: http://memcached.org/
- One of the two PECL memcache packages:
  - http://pecl.php.net/package/memcache (recommended)
  - http://pecl.php.net/package/memcached

For more detailed instructions on installing a memcached daemon or either of the
memcache PECL extensions, please see the documentation online at
https://www.drupal.org/node/1131458 which includes links to external
walk-throughs for various operating systems.

## INSTALLATION ##

These are the steps you need to take in order to use this software. Order
is important.

 1. Make sure you have one of the PECL memcache packages installed.
 2. Enable the memcache module.
    You need to enable the module in Drupal before you can configure it to run
    as the default backend. This is so Drupal knows where to find everything.
 2. Edit settings.php to configure the servers, clusters and bins that memcache
    is supposed to use. You do not need to set this if the only memcache backend
    is localhost on port 11211. By default the main settings will be:
      $settings['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];
      $settings['memcache']['bins'] = ['default' => 'default'];
      $settings['memcache']['key_prefix'] = '';
 7. Edit settings.php to make memcache the default cache class, for example:
      $settings['cache']['default'] = 'cache.backend.memcache';
 8. If you wish to arbitrarily send cache bins to memcache, then you can do the
    following. E.g. for the cache_render bin:
      $settings['cache']['bins']['render'] = 'cache.backend.memcache';

## ADVANCED CONFIGURATION ##

### Multiple memcache backends ###

  $settings['memcache']['servers'] = [
    '127.0.0.1:11211' => 'default', // Default host and port
    '127.0.0.1:11212' => 'default', // Default host with port 11212
    '127.0.0.2:11211' => 'default', // Default port, different IP
    'server1.com:11211' => 'default', // Default port with hostname
    'unix:///path/to/socket' => 'default', 'Unix socket'
  ];

### Multiple servers, bins and clusters ###

  $settings['memcache'] = [
    'servers' = [
      'server1:port' => 'default',
      'server2:port' => 'default',
      'server3:port' => 'cluster1',
      'serverN:port' => 'clusterN',
      'unix:///path/to/socket' => 'clusterS',
    ],
    'bins' => [
      'default' => 'default',
      'bin1' => 'cluster1',
      'binN' => 'clusterN',
      'binX' => 'cluster1',
      'binS' => 'clusterS',
    ],
  ];

The bin/cluster/server model can be described as follows:

- Servers are memcached instances identified by host:port.

- Clusters are groups of servers that act as a memory pool. Each cluster can
  contain one or more servers.

- Multiple bins can be assigned to a cluster.

- The default cluster is 'default'.

- If a bin can not be found it will map to 'default'.

### Stampede Protection ###

Memcache includes stampede protection for rebuilding expired and invalid cache
items. To enable stampede protection, add the following config in settings.php:

$settings['memcache']['stampede_protection'] = TRUE;

To avoid lock stampedes, it is important that you enable the memcache lock
implementation when enabling stampede protection -- enabling stampede protection
without enabling the Memcache lock implementation can cause worse performance.

Only change the following values if you're sure you know what you're doing,
which requires reading the memcachie.inc code.

The value passed to lock_acquire, defaults to '15':
  $conf['memcache_stampede_semaphore'] = 15;

The value passed to lock_wait, defaults to 5:
  $conf['memcache_stampede_wait_time'] = 5;

The maximum number of calls to lock_wait() due to stampede protection during a
single request, defaults to 3:
  $conf['memcache_stampede_wait_limit'] = 3;

When adjusting these variables, be aware that:
 - there is unlikely to be a good use case for setting wait_time higher
   than stampede_semaphore;
 - wait_time * wait_limit is designed to default to a number less than
   standard web server timeouts (i.e. 15 seconds vs. apache's default of
   30 seconds).

### Prefixing ###

If you want to have multiple Drupal installations share memcached instances,
you need to include a unique prefix for each Drupal installation in the memcache
config in settings.php:

  $settings['memcache']['key_prefix'] = 'something_unique';

### Key Hash Algorithm

Note: if the length of your prefix + key + bin combine to be more than 250
characters, they will be automatically hashed. Memcache only supports key
lengths up to 250 bytes. You can optionally configure the hashing algorithm
used, however sha1 was selected as the default because it performs quickly with
minimal collisions.

  $settings['memcache']['key_hash_algorithm'] = 'sha1';

Visit http://www.php.net/manual/en/function.hash-algos.php to learn more about
which hash algorithms are available.

### Memcache Distribution ###

To use this module with multiple memcached servers, it is important that you set
the hash strategy to consistent. This is controlled in the PHP extension, not the
Drupal module.

If using PECL memcache:
Edit /etc/php.d/memcache.ini (path may changed based on package/distribution) and
set the following:
memcache.hash_strategy=consistent

You need to reload apache httpd after making that change.

If using PECL memcached:
Memcached options can be controlled in settings.php. Consistent distribution is
the default in this case but could be set using:

  $setting['memcache']['options'] = [
    Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
  ];

## LOCKS ##

Locks have not yet been implemented using the memcache module.

## TROUBLESHOOTING ##

PROBLEM:
Error:
Failed to set key: Failed to set key: cache_page-......

SOLUTION:
Upgrade your PECL library to PECL package (2.2.1) (or higher).

WARNING:
Zlib compression at the php.ini level and Memcache conflict.
See http://drupal.org/node/273824

## MEMCACHE ADMIN ##

A module offering a UI for memcache is included. It provides aggregated and
per-page statistics for memcache.

## OTHER NOTES ##

### Memcached PECL Extension Support ###

We also support the Memcached PECL extension. This extension backends
to libmemcached and allows you to use some of the newer advanced features in
memcached 1.4.

NOTE: It is important to realize that the memcache php.ini options do not impact
the memcached extension, this new extension doesn't read in options that way.
Instead, it takes options directly from Drupal. Because of this, you must
configure memcached in settings.php. Please look here for possible options:

https://secure.php.net/manual/en/memcached.constants.php

An example configuration block is below, this block also illustrates our
default options (selected through performance testing). These options will be
set unless overridden in settings.php.

  $settings['memcache']['options'] = [
    Memcached::OPT_COMPRESSION => FALSE,
    Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
  ];

These are as follows:

 * Turn off compression, as this takes more CPU cycles than it's worth for most
   users
 * Turn on consistent distribution, which allows you to add/remove servers
   easily

Other options you could experiment with:
 + Memcached::OPT_BINARY_PROTOCOL => TRUE,
    * This enables the Memcache binary protocol (only available in Memcached
      1.4 and later). Note that some users have reported SLOWER performance
      with this feature enabled. It should only be enabled on extremely high
      traffic networks where memcache network traffic is a bottleneck.
      Additional reading about the binary protocol:
        https://raw.githubusercontent.com/memcached/old-wiki/master/MemcacheBinaryProtocol.wiki
        Note: The information on the link above will eventually be ported to
        the new wiki under https://github.com/memcached/memcached/wiki.

 + Memcached::OPT_TCP_NODELAY => TRUE,
    * This enables the no-delay feature for connecting sockets; it's been
      reported that this can speed up the Binary protocol (see above). This
      tells the TCP stack to send packets immediately and without waiting for
      a full payload, reducing per-packet network latency (disabling "Nagling").
