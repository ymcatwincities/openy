<?php

namespace Drupal\personify_mindbody_sync;


/**
 * Class PersonifyMindbodySyncWrapper.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncWrapper implements PersonifyMindbodySyncWrapperInterface {

  /**
   * Source data fetched from Personify.
   *
   * @var array
   */
  private $sourceData;

  /**
   * Data fetched and saved to Drupal database.
   *
   * @var array
   */
  private $proxyData;

  /**
   * Constructor.
   */
  public function __construct() {
  }

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

}
