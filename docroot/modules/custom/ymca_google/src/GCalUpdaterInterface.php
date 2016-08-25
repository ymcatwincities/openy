<?php

namespace Drupal\ymca_google;

use Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface;

/**
 * An interface for GCal updaters.
 */
interface GCalUpdaterInterface {

  /**
   * Check if we need to perform the update.
   *
   * @param GroupexGoogleCacheInterface $cache
   *   Cache entity.
   * @param \stdClass $item
   *   Class item.
   *
   * @return bool
   *   TRUE if need to mark item for update.
   */
  public function check(GroupexGoogleCacheInterface $cache, \stdClass $item);

}
