<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

/**
 * Interface CleanerInterface.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
interface CleanerInterface {

  /**
   * Clean outdated data.
   */
  public function clean();

}
