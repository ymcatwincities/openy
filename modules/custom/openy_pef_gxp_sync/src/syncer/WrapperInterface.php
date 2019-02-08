<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

/**
 * Interface Wrapper.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer.
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
   * @param array $data
   *   Source data.
   */
  public function setSourceData($locationId, array $data);

}
