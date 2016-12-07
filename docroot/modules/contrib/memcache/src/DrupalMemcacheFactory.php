<?php

/**
 * @file
 * Contains \Drupal\memcache\DrupalMemcacheFactory.
 */

namespace Drupal\memcache;

use Psr\Log\LogLevel;

/**
 * Factory class for creation of Memcache objects.
 */
class DrupalMemcacheFactory {

  /**
   * The settings object.
   *
   * @var \Drupal\memcache\DrupalMemcacheConfig
   */
  protected $settings;

  /**
   * @var string
   */
  protected $extension;

  /**
   * @var bool
   */
  protected $memcachePersistent;

  /**
   * @var \Drupal\memcache\DrupalMemcacheInterface[]
   */
  protected $memcacheCache = array();

  /**
   * @var array
   */
  protected $memcacheServers = array();

  /**
   * @var array
   */
  protected $memcacheBins = array();

  /**
   * @var array
   */
  protected $failedConnectionCache = array();

  /**
   * Constructs a DrupalMemcacheFactory object.
   *
   * @param \Drupal\memcache\DrupalMemcacheConfig $settings
   */
  public function __construct(DrupalMemcacheConfig $settings) {
    $this->settings = $settings;

    $this->initialize();
  }

  /**
   * Returns a Memcache object based on settings and the bin requested.
   *
   * @param string $bin
   *   The bin which is to be used.
   *
   * @param bool $flush
   *   Rebuild the bin/server/cache mapping.
   *
   * @return \Drupal\memcache\DrupalMemcacheInterface
   *   A Memcache object.
   */
  public function get($bin = NULL, $flush = FALSE) {
    if ($flush) {
      $this->flush();
    }

    if (empty($this->memcacheCache) || empty($this->memcacheCache[$bin])) {
      // If there is no cluster for this bin in $memcache_bins, cluster is
      // 'default'.
      $cluster = empty($this->memcacheBins[$bin]) ? 'default' : $this->memcacheBins[$bin];

      // If this bin isn't in our $memcacheBins configuration array, and the
      // 'default' cluster is already initialized, map the bin to 'default'
      // because we always map the 'default' bin to the 'default' cluster.
      if (empty($this->memcacheBins[$bin]) && !empty($this->memcacheCache['default'])) {
        $this->memcacheCache[$bin] = &$this->memcacheCache['default'];
      }
      else {
        // Create a new Memcache object. Each cluster gets its own Memcache
        // object.
        // @todo Can't add a custom memcache class here yet.
        if ($this->extension == 'Memcached') {
          $memcache = new DrupalMemcached($this->settings);
        }
        elseif ($this->extension == 'Memcache') {
          $memcache = new DrupalMemcache($this->settings);
        }

        // A variable to track whether we've connected to the first server.
        $init = FALSE;

        // Link all the servers to this cluster.
        foreach ($this->memcacheServers as $s => $c) {
          if ($c == $cluster && !isset($this->failedConnectionCache[$s])) {
            if ($memcache->addServer($s, $this->memcachePersistent) && !$init) {
              $init = TRUE;
            }

            if (!$init) {
              // We can't use watchdog because this happens in a bootstrap phase
              // where watchdog is non-functional. Register a shutdown handler
              // instead so it gets recorded at the end of page load.
              register_shutdown_function('memcache_log_warning', LogLevel::ERROR, 'Failed to connect to memcache server: !server', array('!server' => $s));
              $this->failedConnectionCache[$s] = FALSE;
            }
          }
        }

        if ($init) {
          // Map the current bin with the new Memcache object.
          $this->memcacheCache[$bin] = $memcache;

          // Now that all the servers have been mapped to this cluster, look for
          // other bins that belong to the cluster and map them too.
          foreach ($this->memcacheBins as $b => $c) {
            if (($c == $cluster) && ($b != $bin)) {
              // Map this bin and cluster by reference.
              $this->memcacheCache[$b] = &$this->memcacheCache[$bin];
            }
          }
        }
        else {
          throw new MemcacheException('Memcache instance could not be initialized. Check memcache is running and reachable');
        }
      }
    }

    return empty($this->memcacheCache[$bin]) ? FALSE : $this->memcacheCache[$bin];
  }

  /**
   * Initializes memcache settings.
   */
  protected function initialize() {
    // If an extension is specified in settings.php, use that when available.
    $preferred = $this->settings->get('extension', NULL);
    if (isset($preferred) && class_exists($preferred)) {
      $this->extension = $preferred;
    }
    // If no extension is set, default to Memcache. The Memcached extension has
    // some features that the older extension lacks but also an unfixed bug that
    // affects cache clears.
    // @see http://pecl.php.net/bugs/bug.php?id=16829
    elseif (class_exists('Memcache')) {
      $this->extension = 'Memcache';
    }
    elseif (class_exists('Memcached')) {
      $this->extension = 'Memcached';
    }
    else {
      throw new MemcacheException('No Memcache extension found');
    }

    // Values from settings.php
    $this->memcacheServers = $this->settings->get('servers', ['127.0.0.1:11211' => 'default']);
    $this->memcacheBins = $this->settings->get('bins', ['default' => 'default']);

    // Indicate whether to connect to memcache using a persistent connection.
    // Note: this only affects the Memcache PECL extension, and does not affect
    // the Memcached PECL extension.  For a detailed explanation see:
    // http://drupal.org/node/822316#comment-4427676
    $this->memcachePersistent = $this->settings->get('persistent', FALSE);
  }

  /**
   * Flushes the memcache bin/server/cache mappings and closes connections.
   */
  protected function flush() {
    foreach ($this->memcacheCache as $cluster) {
      $cluster->close();
    }

    $this->memcacheCache = array();
  }

}
