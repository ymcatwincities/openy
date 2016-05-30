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
   * Time frame for the data.
   *
   * @var array
   */
  protected $timeFrame = [];

  /**
   * Source data setter.
   *
   * @param array $data
   *   Source data from Groupex.
   */
  public function setSourceData(array $data) {
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
  public function setProxyData(array $data) {
    $this->proxyData = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeFrame(array $frame) {
    $this->timeFrame = $frame;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeFrame() {
    return $this->timeFrame;
  }

}
