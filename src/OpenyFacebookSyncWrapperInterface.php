<?php

namespace Drupal\openy_facebook_sync;

/**
 * Interface WrapperInterface.
 *
 * @package Drupal\openy_facebook_sync
 */
interface OpenyFacebookSyncWrapperInterface {

  /**
   * Get array of source data.
   *
   * @return array
   *   Array of source data.
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
