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

  /**
   * Get current hashes.
   *
   * @return array
   *   Hashes grouped by location and class ID.
   */
  public function getCurrentHashes();

  /**
   * Get saved hashes.
   *
   * @return array
   *   Hashes grouped by location and class ID.
   */
  public function getSavedHashes();

  /**
   * Save current hashes to state.
   */
  public function setSavedHashes();

  /**
   * Prepare data for creation and deletion.
   */
  public function prepare();

  /**
   * Get items to be removed.
   *
   * @return array
   *   Items to be removed.
   */
  public function getDataToRemove();

  /**
   * Get items to be created.
   *
   * @return array
   *   Items to be removed.
   */
  public function getDataToCreate();

}
