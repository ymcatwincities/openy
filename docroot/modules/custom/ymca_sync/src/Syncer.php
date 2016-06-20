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
   * Array of steps.
   *
   * @var array
   */
  protected $steps;

  /**
   * {@inheritdoc}
   */
  public function proceed() {
    foreach ($this->steps as $id => $step) {
      $step['plugin']->$step['method']($step['args']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addStep($plugin, $method = 'run', array $args = []) {
    $this->steps[] = ['plugin' => $plugin, 'method' => $method, 'args' => $args];
  }

}
