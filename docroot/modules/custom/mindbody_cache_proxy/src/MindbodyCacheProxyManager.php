<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\mindbody_cache_proxy\Entity\MindbodyCache;

/**
 * Class MindbodyCacheProxyManager.
 *
 * @package Drupal\mindbody_cache_proxy
 */
class MindbodyCacheProxyManager implements MindbodyCacheProxyManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'mindbody_cache_proxy';

  /**
   * Collection name.
   */
  const STORAGE = 'mindbody_cache_proxy';

  /**
   * QueryFactory definition.
   *
   * @var QueryFactory
   */
  protected $entityQuery;

  /**
   * EntityTypeManager definition.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Drupal\Core\State\State definition.
   *
   * @var State
   */
  protected $state;

  /**
   * Constructor.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManager $entity_type_manager, LoggerChannelFactory $logger_factory, State $state) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function flushStats() {
    $data = new \stdClass();
    $data->timestamp = REQUEST_TIME;
    $data->hit = 0;
    $data->miss = 0;

    $this->state->set(self::STORAGE, $data);
    \Drupal::moduleHandler()->invokeAll('mindbody_cache_proxy_flush_stats', [$data]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $result = $this->entityQuery->get('mindbody_cache')->execute();
    if (empty($result)) {
      return;
    }

    $this->deleteCacheItems($result);

    $this->logger->info('The cache was cleared.');
    \Drupal::moduleHandler()->invokeAll('mindbody_cache_proxy_reset_cache');
  }

  /**
   * Reset cache of Bookable items by Location ID.
   *
   * @param int $location
   *   Location ID.
   */
  public function resetBookableItemsCacheByLocation($location) {
    $result = $this->entityQuery->get('mindbody_cache')
      ->condition('field_mindbody_cache_location', $location)
      ->execute();

    if (empty($result)) {
      return;
    }

    $this->deleteCacheItems($result);
  }

  /**
   * Delete cache items by IDs.
   *
   * @param array $ids
   *   Cache item IDs.
   */
  private function deleteCacheItems(array $ids) {
    $storage = $this->entityTypeManager->getStorage('mindbody_cache');
    $chunks = array_chunk($ids, 10);
    foreach ($chunks as $chunk) {
      $entities = MindbodyCache::loadMultiple($chunk);
      $storage->delete($entities);
    }
  }

}
