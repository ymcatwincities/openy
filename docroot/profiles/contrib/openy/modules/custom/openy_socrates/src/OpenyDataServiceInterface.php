<?php

namespace Drupal\openy_socrates;

/**
 * Interface OpenyDataServiceInterface.
 *
 * @package Drupal\openy_socrates
 */
interface OpenyDataServiceInterface {

  /**
   * Add Data Services.
   *
   * Every OpenY Data Service needs to implement this method which returns list
   * of available global methods via Socrates service.
   *
   * @param array $services
   *   Services.
   */
  public function addDataServices(array $services);

}
