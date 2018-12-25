<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Interface PersonifyMindbodySyncWrapperInterface.
 *
 * @package Drupal\personify_mindbody_sync
 */
interface PersonifyMindbodySyncWrapperInterface {

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

  /**
   * Proxy data getter.
   *
   * @return mixed
   *   Proxy data.
   */
  public function getProxyData();

  /**
   * Proxy data setter.
   *
   * @param array $data
   *   Array of data.
   */
  public function setProxyData(array $data);

}
