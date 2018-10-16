<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

/**
 * Class Wrapper
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer.
 */
class Wrapper implements WrapperInterface {

  /**
   * Source Data.
   *
   * @var array
   */
  protected $sourceData;

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceData($locationId, array $data) {
    $this->sourceData[$locationId] = $data;
  }

  public function getProcessedData() {
    return $this->process($this->getSourceData());
  }

  /**
   * Process data.
   *
   * @param array $data
   *   Source data.
   *
   * @return array
   *  Processed data.
   */
  private function process(array $data) {
    return $data;
  }

}
