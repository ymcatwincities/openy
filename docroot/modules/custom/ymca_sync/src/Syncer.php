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
   * Setup params.
   *
   * @var array
   */
  protected $setup;

  /**
   * {@inheritdoc}
   */
  public function proceed($params = []) {
    if ($this->setup) {
      $class = $this->setup['plugin'];
      $method = $this->setup['method'];
      $class->$method($params);
    }

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

  /**
   * Setup method.
   *
   * @param object $plugin
   *   Object.
   * @param string $method
   *   Method.
   */
  public function setUp($plugin, $method) {
    $this->setup = [
      'plugin' => $plugin,
      'method' => $method
    ];
  }

}
