<?php

namespace Drupal\ymca_mindbody;

/**
 * Interface YmcaMindbodyRequestGuardInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface YmcaMindbodyRequestGuardInterface {

  /**
   * Checks current status.
   *
   * @return bool
   *   TRUE if requests to MindBody are available. FALSE if there are no allowed calls.
   */
  public function status();

}
