<?php

namespace Drupal\ymca_google;

/**
 * Interface GcalGroupexWrapperInterface.
 *
 * @package Drupal\ymca_google
 */
interface GcalGroupexWrapperInterface {

  /**
   * Array of raw entities from source system.
   * 
   * @return array
   */
  public function getDrupalEntitiesFromSource();

  /**
   * Array of entities, prepared for destination system push.
   *
   * @return array
   */
  public function getDestinationEntitiesFromProxy();

  /**
   * Array of entities to be cached and enriched on host system.
   *
   * @return array
   */
  public function getProxyData();
}
