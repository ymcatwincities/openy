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
   * Get processed data.
   */
  public function getProcessedData();

  /**
   * Set source data.
   *
   * @param int $locationId
   *   YGTC Location ID.
   *
   * @param array $data
   *   Source data.
   */
  public function setSourceData($locationId, array $data);

}
