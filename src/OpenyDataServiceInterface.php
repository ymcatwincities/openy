<?php

namespace Drupal\openy_socrates;

/**
 * Interface OpenyDataServiceInterface.
 *
 * @package Drupal\openy_socrates
 */
interface OpenyDataServiceInterface {

  /**
   * Every OpenY Data Service need to implement this method which returns list
   * of available global methods and priorities.
   *
   * @param array $services
   */
  public function addDataServices($services);
}