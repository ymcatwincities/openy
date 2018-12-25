<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Interface SaverInterface.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
interface SaverInterface {

  /**
   * Save data.
   */
  public function save();

}
