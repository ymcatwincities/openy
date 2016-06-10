<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\State\State;
use Drupal\mindbody\MindbodyClientInterface;
use Drupal\mindbody_cache_proxy\Entity\MindbodyCache;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class MindbodyProxy.
 *
 * @package Drupal\ymca_mindbody
 */
class MindbodyCacheProxy implements MindbodyCacheProxyInterface {

  /**
   * Collection name.
   */
  const STORAGE = 'mindbody_cache_proxy';

  /**
   * MindbodyClient.
   *
   * @var MindbodyClientInterface
   */
  protected $mindbodyClient;

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * State.
   *
   * @var State
   */
  protected $state;

  /**
   * MindbodyProxy constructor.
   *
   * @param MindbodyClientInterface $mindbody_client
   *   MindBody client.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param State $state
   *   State.
   */
  public function __construct(MindbodyClientInterface $mindbody_client, QueryFactory $query_factory, State $state) {
    $this->mindbodyClient = $mindbody_client;
    $this->queryFactory = $query_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function call($service, $endpoint, array $params = []) {
    $params_str = serialize($params);

    // Check whether the cache exists. If so, return it.
    $result = $this->checkCache($service, $endpoint, $params_str);
    if ($result) {
      $this->updateStats('hit');
      return $result;
    }

    // There is no cache. Make the call and create cache item.
    $result = $this->mindbodyClient->call($service, $endpoint, $params);
    $cache = MindbodyCache::create([
      'field_mindbody_cache_service' => $service,
      'field_mindbody_cache_endpoint' => $endpoint,
      'field_mindbody_cache_params' => $params_str,
      'field_mindbody_cache_data' => serialize($result),
    ]);
    $cache->setName(sprintf('Cache item: %s, %s', $service, $endpoint));
    $cache->save();

    $this->updateStats('miss');

    return $result;
  }

  /**
   * Update stats.
   *
   * @param string $type
   *   Either: 'hit' or 'miss' string.
   */
  protected function updateStats($type) {
    $current = $this->state->get(self::STORAGE);
    $date_time = new \DateTime();
    $date_time->setTimezone(new \DateTimeZone('UTC'));

    // Create new stats item.
    if (!$current) {
      $this->flushStats($type);
      return;
    }

    // If current stats timestamp older than 24 hours update it.
    if ((REQUEST_TIME - $current->timestamp) > 86400) {
      $this->flushStats($type);
      return;
    }

    $current->{$type}++;
    $this->state->set(self::STORAGE, $current);
  }

  /**
   * Flush stats object in the database.
   *
   * @param string $type
   *   Either: 'hit' or 'miss' string.
   */
  private function flushStats($type) {
    $date_time = new \DateTime();
    $date_time->setTimezone(new \DateTimeZone('UTC'));
    $date_time->setTime(0, 0, 0);

    $data = new \stdClass();
    $data->timestamp = $date_time->getTimestamp();
    $data->hit = 0;
    $data->miss = 0;

    $data->{$type}++;

    $this->state->set(self::STORAGE, $data);
  }

  /**
   * Check whether the cache exists.
   *
   * @param string $service
   *   Service name.
   * @param string $endpoint
   *   Endpoint name.
   * @param string $params
   *   Parameters.
   *
   * @return mixed
   *   stdClass object or FALSE if there is no cache.
   */
  protected function checkCache($service, $endpoint, $params) {
    $result = $this->queryFactory->get('mindbody_cache')
      ->condition('field_mindbody_cache_service', $service)
      ->condition('field_mindbody_cache_endpoint', $endpoint)
      ->condition('field_mindbody_cache_params', $params)
      ->execute();

    if (!empty($result)) {
      $id = reset($result);
      $cache = MindbodyCache::load($id);
      return unserialize($cache->field_mindbody_cache_data->value);
    }

    return FALSE;
  }

}
