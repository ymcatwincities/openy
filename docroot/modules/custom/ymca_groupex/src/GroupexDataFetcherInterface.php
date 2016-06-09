<?php

namespace Drupal\ymca_groupex;

/**
 * Interface GroupexDataFetcherInterface.
 *
 * @package Drupal\ymca_groupex
 */
interface GroupexDataFetcherInterface {

  /**
   * Fetch data from Groupex.
   *
   * @param array $args
   *   Arguments.
   */
  public function fetch(array $args);

}
