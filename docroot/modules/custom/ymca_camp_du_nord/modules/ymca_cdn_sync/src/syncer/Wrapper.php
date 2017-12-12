<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Class Saver.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class Wrapper implements WrapperInterface {

  /**
   * Source data.
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
  public function setSourceData(array $data) {
    $this->sourceData = $data;
  }

}
