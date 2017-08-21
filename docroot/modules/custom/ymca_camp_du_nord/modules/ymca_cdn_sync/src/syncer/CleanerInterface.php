<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Interface CleanerInterface.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
interface CleanerInterface {

  /**
   * Remove stale cache entities.
   */
  public function clean();

}
