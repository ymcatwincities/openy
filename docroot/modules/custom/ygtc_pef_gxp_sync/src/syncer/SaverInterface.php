<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

/**
 * Interface SaverInterface.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
 */
interface SaverInterface {

  /**
   * Save items.
   */
  public function save();

  /**
   * Remove orphaned items from database.
   */
  public function clean();

}
