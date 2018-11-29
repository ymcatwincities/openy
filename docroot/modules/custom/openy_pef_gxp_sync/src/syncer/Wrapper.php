<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

/**
 * Class Wrapper.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer.
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

  /**
   * {@inheritdoc}
   */
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
   *   Processed data.
   */
  private function process(array $data) {
    $processed = [];

    foreach ($data as $locationId => $locationItems) {
      foreach ($locationItems as $item) {
        $item['ygtc_location_id'] = $locationId;
        $processed[] = $item;
      }
    }

    return $processed;
  }

}
