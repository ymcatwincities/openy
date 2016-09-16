<?php

namespace Drupal\ymca_google;

/**
 * Interface IcsFetcherInterface.
 *
 * @package Drupal\ymca_groupex
 */
interface IcsFetcherInterface {

  /**
   * Fetch data from ICS groupex api.
   *
   * @param array $args
   *   Arguments.
   */
  public function fetch(array $args);

}
