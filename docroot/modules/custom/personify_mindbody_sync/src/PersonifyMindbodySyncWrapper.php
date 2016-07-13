<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncWrapper.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncWrapper implements PersonifyMindbodySyncWrapperInterface {

  /**
   * Logger channel name.
   */
  const CHANNEL = 'personify_mindbody_sync';

  /**
   * Cache entity name.
   */
  const CACHE_ENTITY = 'personify_mindbody_cache';

  /**
   * Source data fetched from Personify.
   *
   * @var array
   */
  private $sourceData = [];

  /**
   * Data fetched and saved to Drupal database.
   *
   * @var array
   */
  private $proxyData = [];

  /**
   * Parameters.
   *
   * @var array
   */
  private $params = [];

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

  /**
   * Setup parameters.
   *
   * @param array $params
   *   Parameters.
   */
  public function setUp($params) {
    $this->params = $params;
  }

  /**
   * Get parameters.
   *
   * @return array
   *   Parameters.
   */
  public function getParams() {
    return $this->params;
  }

}
