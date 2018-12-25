<?php

namespace Drupal\rabbit_hole;

/**
 * Interface EntityExtenderInterface.
 *
 * @package Drupal\rabbit_hole
 */
interface EntityExtenderInterface {

  /**
   * Get the extra fields that should be applied to all rabbit hole entities.
   */
  public function getGeneralExtraFields();

}
