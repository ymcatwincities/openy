<?php

namespace Drupal\ymca_mindbody;

/**
 * Interface YmcaMindbodyFailedOrdersNotifierInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface YmcaMindbodyFailedOrdersNotifierInterface {

  /**
   * Checks if notifier could be run.
   */
  public function isAllowed();

  /**
   * Run notifier.
   */
  public function run();

}
