<?php

namespace Drupal\ymca_groupex;

/**
 * Interface DrupalProxyInterface.
 *
 * @package Drupal\ymca_groupex
 */
interface DrupalProxyInterface {

  /**
   * Save source data to Drupal mapping entities.
   */
  public function saveEntities();

}
