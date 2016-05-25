<?php

namespace Drupal\ymca_sync;

use Drupal\ymca_google\GcalGroupexWrapperInterface;


/**
 * Class Syncer.
 *
 * @package Drupal\ymca_sync
 */
class Syncer implements SyncerInterface {

  /**
   * Wrapper to be used.
   *
   * @var GcalGroupexWrapperInterface
   */
  protected $wrapper;

  /**
   * Syncer constructor.
   *
   * @param GcalGroupexWrapperInterface $wrapper
   *   Wrapper to be used.
   */
  public function __construct(GcalGroupexWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

}
