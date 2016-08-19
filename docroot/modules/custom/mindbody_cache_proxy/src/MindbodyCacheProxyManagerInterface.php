<?php

namespace Drupal\mindbody_cache_proxy;

/**
 * Interface MindbodyCacheProxyManagerInterface.
 *
 * @package Drupal\mindbody_cache_proxy
 */
interface MindbodyCacheProxyManagerInterface {

  /**
   * Flush the stats.
   */
  public function flushStats();

  /**
   * Reset the cache.
   */
  public function resetCache();

}
