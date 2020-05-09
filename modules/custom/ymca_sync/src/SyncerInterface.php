<?php

namespace Drupal\ymca_sync;

/**
 * Interface SyncerInterface.
 *
 * @package Drupal\ymca_sync
 */
interface SyncerInterface {

  /**
   * Run the sync process.
   *
   * @param array $options
   *   Options from drush.
   */
  public function proceed(array $options);

  /**
   * Add task.
   *
   * @param mixed $object
   *   Object.
   * @param string $method
   *   Method.
   * @param array $args
   *   Arguments.
   */
  public function addStep($object, $method = 'run', array $args = []);

}
