<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Interface WrapperInterface.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
interface WrapperInterface {

  /**
   * Source data getter.
   *
   * @return mixed
   *   Source data.
   */
  public function getSourceData();

  /**
   * Source data setter.
   *
   * @param array $data
   *   Array of data.
   */
  public function setSourceData(array $data);

}
