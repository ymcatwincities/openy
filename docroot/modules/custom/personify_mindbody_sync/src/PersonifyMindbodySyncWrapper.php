<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache;

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
   * Overall timezone.
   */
  const TIMEZONE = 'UTC';

  /**
   * Initial sync date.
   */
  const INITIAL_DATE = '2000-01-01T11:20:00';

  /**
   * Offset in seconds for getting data from Personify.
   */
  const DATE_OFFSET = 86400;

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
   * Query Factory.
   *
   * @var
   */
  protected $query;

  /**
   * PersonifyMindbodySyncWrapper constructor.
   *
   * @param QueryFactory $query
   *   Query factory.
   */
  public function __construct(QueryFactory $query) {
    $this->query = $query;
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
   * Find the first failed push.
   */
  public function findFirstFailTime() {
    $result = $this->query->get('personify_mindbody_cache')
      ->notExists('field_pmc_mindbody_order_data')
      ->sort('field_pmc_personify_order_date', 'ASC')
      ->execute();

    if (!$result) {
      return FALSE;
    }

    $entity = PersonifyMindbodyCache::load(reset($result));
    return $entity->field_pmc_personify_order_date->value;
  }

}
