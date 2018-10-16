<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

/**
 * Interface FetcherInterface.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
 */
interface FetcherInterface {

  /**
   * Fetch data.
   */
  public function fetch();

}
