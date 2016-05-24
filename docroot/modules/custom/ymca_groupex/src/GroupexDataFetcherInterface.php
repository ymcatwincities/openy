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
   * @param int $start
   *   Start timestamp.
   * @param int $end
   *   End timestamp.
   *
   * @return mixed
   *   Fetched data.
   */
  public function fetch($start, $end);

}
