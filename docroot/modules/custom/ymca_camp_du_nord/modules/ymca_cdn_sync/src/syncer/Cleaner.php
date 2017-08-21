<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Class Cleaner.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class Cleaner implements CleanerInterface {

  /**
   * {@inheritdoc}
   */
  public function clean() {
    // @todo Remove cache entities with "DateBegin" in the past.
  }

}
