<?php

namespace Drupal\ymca_google;

/**
 * Interface GroupexRepositoryInterface.
 *
 * @package Drupal\ymca_google
 */
interface GroupexRepositoryInterface {

  /**
   * Save Groupex data to the database.
   *
   * @param array $data
   *   Data fetched from Groupex.
   * @param int $start
   *   Start timestamp.
   * @param int $end
   *   End timestamp.
   */
  public function save(array $data, $start, $end);

}
