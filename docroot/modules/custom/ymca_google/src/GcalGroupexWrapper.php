<?php

namespace Drupal\ymca_google;


/**
 * Class GcalGroupexWrapper.
 *
 * @package Drupal\ymca_google
 */
class GcalGroupexWrapper implements GcalGroupexWrapperInterface {

  /**
   * Raw source data from source system.
   *
   * @var array
   */
  protected $sourceData = [];

  /**
   * Prepared data for proxy system.
   *
   * @var array
   */
  protected $proxyData = [];

  /**
   * Source data setter.
   *
   * @param $data
   *   Source data from Groupex.
   */
  public function setSourceData($data) {
    $this->sourceData = $data;
  }

  /**
   * Source data getter.
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyData() {
    return $this->proxyData;
  }

  /**
   * {@inheritdoc}
   */
  public function SetProxyData($data) {
    $this->proxyData = $data;
  }

}
