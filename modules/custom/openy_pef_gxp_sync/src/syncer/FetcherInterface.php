<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

/**
 * Interface FetcherInterface.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
interface FetcherInterface {

  /**
   * Fetch data.
   *
   * @throws \Drupal\openy_pef_gxp_sync\OpenYPefGxpSyncException
   */
  public function fetch();

}
