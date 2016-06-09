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
   * Array of steps.
   *
   * @var array
   */
  protected $steps;

  /**
   * Syncer constructor.
   *
   * @param GcalGroupexWrapperInterface $wrapper
   *   Wrapper to be used.
   */
  public function __construct(GcalGroupexWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

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
