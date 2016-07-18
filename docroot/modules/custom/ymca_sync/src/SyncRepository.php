<?php

namespace Drupal\ymca_sync;

/**
 * Class SyncRepository.
 *
 * @package Drupal\ymca_sync
 */
class SyncRepository {

  /**
   * Syncers.
   *
   * @var array
   */
  protected $syncers;

  /**
   * @inheritDoc
   */
  public function __construct(array $syncers) {
    $this->syncers = $syncers;
  }

  /**
   * Return syncers.
   *
   * @return array
   */
  public function getSyncers() {
    return $this->syncers;
  }

}
