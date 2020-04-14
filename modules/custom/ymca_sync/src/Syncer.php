<?php

namespace Drupal\ymca_sync;

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
  public function proceed(array $options = []) {
    foreach ($this->steps as $step) {
      $args = array_merge($options, $step['args']);
      $step['plugin']->{$step['method']}($args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addStep($plugin, $method = 'run', array $args = []) {
    $this->steps[] = ['plugin' => $plugin, 'method' => $method, 'args' => $args];
  }

}
