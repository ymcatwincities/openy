<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

/**
 * Class Wrapper
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer.
 */
interface WrapperInterface {

  /**
   * Get source data.
   */
  public function getSourceData();

  /**
   * Set source data.
   *
   * @param array $data
   *   Source data.
   */
  public function setSourceData(array $data);

}
